<?php

declare(strict_types=1);

namespace AlthoSalud\Telemedicina\Exception;

final class TransportException extends TelemedicineException
{
    public static function fromPrevious(\Throwable $previous): self
    {
        return new self('Could not contact AlthoTelemedicina.', 0, $previous);
    }
}
