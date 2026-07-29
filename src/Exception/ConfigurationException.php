<?php

declare(strict_types=1);

namespace AlthoSalud\Telemedicina\Exception;

final class ConfigurationException extends TelemedicineException
{
    public static function missingBaseUrl(): self
    {
        return new self('AlthoTelemedicina base URL is not configured.');
    }

    public static function missingApiKey(): self
    {
        return new self('AlthoTelemedicina API key is not configured.');
    }
}
