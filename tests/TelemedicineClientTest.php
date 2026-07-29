<?php

declare(strict_types=1);

namespace AlthoSalud\Telemedicina\Tests;

use AlthoSalud\Telemedicina\Exception\ApiException;
use AlthoSalud\Telemedicina\Exception\ConfigurationException;
use AlthoSalud\Telemedicina\Http\HttpResponse;
use AlthoSalud\Telemedicina\TelemedicineClient;
use PHPUnit\Framework\TestCase;

final class TelemedicineClientTest extends TestCase
{
    public function test_create_session_sends_bearer_and_returns_typed_session(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(201, json_encode($this->sessionPayload(), \JSON_THROW_ON_ERROR)),
        ]);
        $client = new TelemedicineClient('https://telemedicina.example/', 'tm_secret', $transport);

        $session = $client->createSession('appointment:123', ['source' => 'test']);

        self::assertSame('session-1', $session->id);
        self::assertSame('https://video.example/pro', $session->professionalJoinUrl);
        self::assertCount(1, $transport->requests);
        self::assertSame('POST', $transport->requests[0]['method']);
        self::assertSame('https://telemedicina.example/api/v1/sessions', $transport->requests[0]['url']);
        self::assertSame('Bearer tm_secret', $transport->requests[0]['headers']['Authorization']);

        $body = json_decode((string) $transport->requests[0]['body'], true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('appointment:123', $body['external_reference']);
        self::assertSame('test', $body['metadata']['source']);
    }

    public function test_with_api_key_is_immutable_and_supports_tenant_key(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(200, json_encode($this->sessionPayload(), \JSON_THROW_ON_ERROR)),
        ]);
        $shared = new TelemedicineClient('https://telemedicina.example', transport: $transport);
        $tenant = $shared->withApiKey('tenant-key');

        self::assertFalse($shared->isConfigured());
        self::assertTrue($tenant->isConfigured());

        $tenant->getSession('session-1');
        self::assertSame('Bearer tenant-key', $transport->requests[0]['headers']['Authorization']);
    }

    public function test_missing_key_throws_configuration_exception(): void
    {
        $client = new TelemedicineClient('https://telemedicina.example', transport: new MockHttpTransport());

        $this->expectException(ConfigurationException::class);
        $client->me();
    }

    public function test_api_error_exposes_status_and_code_without_key(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(422, json_encode([
                'error' => 'validation_error',
                'message' => 'external_reference is required.',
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new TelemedicineClient('https://telemedicina.example', 'secret-key', $transport);

        try {
            $client->createSession('reference');
            self::fail('Expected ApiException.');
        } catch (ApiException $exception) {
            self::assertSame(422, $exception->statusCode);
            self::assertSame('validation_error', $exception->apiErrorCode);
            self::assertStringNotContainsString('secret-key', $exception->getMessage());
        }
    }

    public function test_me_returns_application_credentials(): void
    {
        $transport = new MockHttpTransport([
            new HttpResponse(200, json_encode([
                'application' => [
                    'id' => 7,
                    'name' => 'Clinic',
                    'slug' => 'clinic',
                    'status' => 'active',
                    'preferred_provider' => 'whereby',
                    'rate_limit_per_minute' => 60,
                ],
                'api_key' => [
                    'prefix' => 'abc123',
                    'scopes' => ['sessions:read', 'sessions:write'],
                    'label' => 'production',
                ],
            ], \JSON_THROW_ON_ERROR)),
        ]);
        $client = new TelemedicineClient('https://telemedicina.example', 'tm_secret', $transport);

        $credentials = $client->me();

        self::assertSame(7, $credentials->applicationId);
        self::assertSame('clinic', $credentials->applicationSlug);
        self::assertSame(['sessions:read', 'sessions:write'], $credentials->scopes);
    }

    /**
     * @return array<string, mixed>
     */
    private function sessionPayload(): array
    {
        return [
            'id' => 'session-1',
            'external_reference' => 'appointment:123',
            'status' => 'created',
            'provider' => 'whereby',
            'provider_room_id' => 'room-1',
            'professional_join_url' => 'https://video.example/pro',
            'patient_join_url' => 'https://video.example/patient',
            'embed_url' => 'https://video.example/embed',
            'metadata' => ['source' => 'test'],
            'started_at' => '2026-07-29T10:00:00+00:00',
            'ended_at' => null,
            'created_at' => '2026-07-29T10:00:00+00:00',
        ];
    }
}
