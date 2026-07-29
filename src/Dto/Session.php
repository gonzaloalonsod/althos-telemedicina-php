<?php

declare(strict_types=1);

namespace AlthoSalud\Telemedicina\Dto;

use AlthoSalud\Telemedicina\Exception\InvalidResponseException;

final readonly class Session
{
    /**
     * @param array<string, mixed> $metadata
     */
    public function __construct(
        public string $id,
        public string $externalReference,
        public string $status,
        public string $provider,
        public ?string $providerRoomId,
        public ?string $professionalJoinUrl,
        public ?string $patientJoinUrl,
        public ?string $embedUrl,
        public array $metadata,
        public ?\DateTimeImmutable $startedAt,
        public ?\DateTimeImmutable $endedAt,
        public \DateTimeImmutable $createdAt,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $id = self::requiredString($payload, 'id');
        $externalReference = self::requiredString($payload, 'external_reference');
        $status = self::requiredString($payload, 'status');
        $provider = self::requiredString($payload, 'provider');
        $createdAt = self::requiredDate($payload['created_at'] ?? null, 'created_at');
        $metadata = $payload['metadata'] ?? [];

        if (!\is_array($metadata)) {
            throw InvalidResponseException::field('metadata', 'array');
        }

        /** @var array<string, mixed> $metadata */
        return new self(
            id: $id,
            externalReference: $externalReference,
            status: $status,
            provider: $provider,
            providerRoomId: self::nullableString($payload, 'provider_room_id'),
            professionalJoinUrl: self::nullableString($payload, 'professional_join_url'),
            patientJoinUrl: self::nullableString($payload, 'patient_join_url'),
            embedUrl: self::nullableString($payload, 'embed_url'),
            metadata: $metadata,
            startedAt: self::date($payload['started_at'] ?? null, 'started_at'),
            endedAt: self::date($payload['ended_at'] ?? null, 'ended_at'),
            createdAt: $createdAt,
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function requiredString(array $payload, string $field): string
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

    private static function date(mixed $value, string $field, bool $required = false): ?\DateTimeImmutable
    {
        if (null === $value && !$required) {
            return null;
        }

        if (!\is_string($value) || '' === $value) {
            throw InvalidResponseException::field($field, $required ? 'date string' : 'date string or null');
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Exception $exception) {
            throw InvalidResponseException::field($field, 'valid date string', $exception);
        }
    }

    private static function requiredDate(mixed $value, string $field): \DateTimeImmutable
    {
        $date = self::date($value, $field, true);
        if (null === $date) {
            throw InvalidResponseException::field($field, 'date string');
        }

        return $date;
    }
}
