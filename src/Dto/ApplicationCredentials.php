<?php

declare(strict_types=1);

namespace AlthoSalud\Telemedicina\Dto;

use AlthoSalud\Telemedicina\Exception\InvalidResponseException;

final readonly class ApplicationCredentials
{
    /**
     * @param list<string> $scopes
     */
    public function __construct(
        public int $applicationId,
        public string $applicationName,
        public string $applicationSlug,
        public string $applicationStatus,
        public ?string $preferredProvider,
        public int $rateLimitPerMinute,
        public string $apiKeyPrefix,
        public array $scopes,
        public ?string $apiKeyLabel,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $application = $payload['application'] ?? null;
        $apiKey = $payload['api_key'] ?? null;
        if (!\is_array($application) || !\is_array($apiKey)) {
            throw InvalidResponseException::message('Response must contain application and api_key objects.');
        }

        $scopes = $apiKey['scopes'] ?? null;
        if (
            !\is_array($scopes)
            || !array_is_list($scopes)
            || array_filter($scopes, static fn (mixed $scope): bool => !\is_string($scope))
        ) {
            throw InvalidResponseException::field('api_key.scopes', 'list of strings');
        }

        /** @var list<string> $scopes */
        return new self(
            applicationId: self::integer($application, 'id'),
            applicationName: self::string($application, 'name'),
            applicationSlug: self::string($application, 'slug'),
            applicationStatus: self::string($application, 'status'),
            preferredProvider: self::nullableString($application, 'preferred_provider'),
            rateLimitPerMinute: self::integer($application, 'rate_limit_per_minute'),
            apiKeyPrefix: self::string($apiKey, 'prefix'),
            scopes: $scopes,
            apiKeyLabel: self::nullableString($apiKey, 'label'),
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function string(array $payload, string $field): string
    {
        $value = $payload[$field] ?? null;
        if (!\is_string($value) || '' === $value) {
            throw InvalidResponseException::field($field, 'non-empty string');
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function nullableString(array $payload, string $field): ?string
    {
        $value = $payload[$field] ?? null;
        if (null === $value) {
            return null;
        }

        if (!\is_string($value)) {
            throw InvalidResponseException::field($field, 'string or null');
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function integer(array $payload, string $field): int
    {
        $value = $payload[$field] ?? null;
        if (!\is_int($value)) {
            throw InvalidResponseException::field($field, 'integer');
        }

        return $value;
    }
}
