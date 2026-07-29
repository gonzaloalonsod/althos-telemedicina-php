<?php

declare(strict_types=1);

namespace AlthoSalud\Telemedicina\Contract;

use AlthoSalud\Telemedicina\Dto\ApplicationCredentials;
use AlthoSalud\Telemedicina\Dto\Session;

interface TelemedicineClientInterface
{
    public function isConfigured(): bool;

    public function withApiKey(string $apiKey): self;

    public function me(): ApplicationCredentials;

    /**
     * @param array<string, mixed> $metadata
     */
    public function createSession(string $externalReference, array $metadata = []): Session;

    public function getSession(string $sessionId): Session;

    public function endSession(string $sessionId): Session;
}
