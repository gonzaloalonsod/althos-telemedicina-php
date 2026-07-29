<?php

declare(strict_types=1);

namespace AlthoSalud\Telemedicina;

use AlthoSalud\Telemedicina\Contract\HttpTransportInterface;
use AlthoSalud\Telemedicina\Contract\TelemedicineClientInterface;
use AlthoSalud\Telemedicina\Dto\ApplicationCredentials;
use AlthoSalud\Telemedicina\Dto\Session;
use AlthoSalud\Telemedicina\Exception\ApiException;
use AlthoSalud\Telemedicina\Exception\ConfigurationException;
use AlthoSalud\Telemedicina\Exception\InvalidResponseException;
use AlthoSalud\Telemedicina\Http\CurlTransport;

final readonly class TelemedicineClient implements TelemedicineClientInterface
{
    public function __construct(
        private string $baseUrl,
        private ?string $apiKey = null,
        private HttpTransportInterface $transport = new CurlTransport(),
    ) {
    }

    public function isConfigured(): bool
    {
        return '' !== trim($this->baseUrl) && null !== $this->apiKey && '' !== trim($this->apiKey);
    }

    public function withApiKey(string $apiKey): TelemedicineClientInterface
    {
        return new self($this->baseUrl, $apiKey, $this->transport);
    }

    public function me(): ApplicationCredentials
    {
        return ApplicationCredentials::fromArray($this->request('GET', '/api/v1/me'));
    }

    public function createSession(string $externalReference, array $metadata = []): Session
    {
        if ('' === trim($externalReference)) {
            throw new \InvalidArgumentException('External reference must not be empty.');
        }

        return Session::fromArray($this->request('POST', '/api/v1/sessions', [
            'external_reference' => trim($externalReference),
            'metadata' => $metadata,
        ]));
    }

    public function getSession(string $sessionId): Session
    {
        return Session::fromArray($this->request(
            'GET',
            '/api/v1/sessions/'.rawurlencode($this->sessionId($sessionId)),
        ));
    }

    public function endSession(string $sessionId): Session
    {
        return Session::fromArray($this->request(
            'POST',
            '/api/v1/sessions/'.rawurlencode($this->sessionId($sessionId)).'/end',
        ));
    }

    /**
     * @param array<string, mixed>|null $jsonBody
     *
     * @return array<string, mixed>
     */
    private function request(string $method, string $path, ?array $jsonBody = null): array
    {
        $baseUrl = rtrim(trim($this->baseUrl), '/');
        if ('' === $baseUrl) {
            throw ConfigurationException::missingBaseUrl();
        }

        $apiKey = null === $this->apiKey ? '' : trim($this->apiKey);
        if ('' === $apiKey) {
            throw ConfigurationException::missingApiKey();
        }

        $headers = [
            'Authorization' => 'Bearer '.$apiKey,
            'Accept' => 'application/json',
        ];

        $body = null;
        if (null !== $jsonBody) {
            $headers['Content-Type'] = 'application/json';
            try {
                $body = json_encode($jsonBody, \JSON_THROW_ON_ERROR);
            } catch (\JsonException $exception) {
                throw InvalidResponseException::message('request body is not valid JSON.', $exception);
            }
        }

        $response = $this->transport->request($method, $baseUrl.$path, $body, $headers);
        $payload = $this->decode($response->body);

        if ($response->statusCode < 200 || $response->statusCode >= 300) {
            $errorCode = \is_string($payload['error'] ?? null) ? $payload['error'] : null;
            $message = \is_string($payload['message'] ?? null)
                ? $payload['message']
                : 'AlthoTelemedicina returned HTTP '.$response->statusCode.'.';

            throw new ApiException($response->statusCode, $errorCode, $message, $payload);
        }

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $content): array
    {
        if ('' === $content) {
            throw InvalidResponseException::message('empty response body.');
        }

        try {
            $payload = json_decode($content, true, 512, \JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw InvalidResponseException::message('body is not valid JSON.', $exception);
        }

        if (!\is_array($payload)) {
            throw InvalidResponseException::message('JSON root must be an object.');
        }

        /** @var array<string, mixed> $payload */
        return $payload;
    }

    private function sessionId(string $sessionId): string
    {
        $sessionId = trim($sessionId);
        if ('' === $sessionId) {
            throw new \InvalidArgumentException('Session ID must not be empty.');
        }

        return $sessionId;
    }
}
