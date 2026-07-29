<?php

declare(strict_types=1);

namespace AlthoSalud\Telemedicina\Contract;

use AlthoSalud\Telemedicina\Http\HttpResponse;

interface HttpTransportInterface
{
    /**
     * @param array<string, string> $headers
     */
    public function request(string $method, string $url, ?string $body = null, array $headers = []): HttpResponse;
}
