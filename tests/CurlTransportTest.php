<?php

declare(strict_types=1);

namespace AlthoSalud\Telemedicina\Tests;

use AlthoSalud\Telemedicina\Exception\TransportException;
use AlthoSalud\Telemedicina\Http\CurlTransport;
use PHPUnit\Framework\TestCase;

final class CurlTransportTest extends TestCase
{
    /** @var resource|null */
    private $serverProcess;

    private ?int $serverPort = null;

    protected function tearDown(): void
    {
        $this->stopServer();
    }

    public function test_connection_failure_throws_transport_exception(): void
    {
        $transport = new CurlTransport();

        $this->expectException(TransportException::class);
        $transport->request('GET', 'http://127.0.0.1:65530/unreachable');
    }

    public function test_sends_method_headers_and_body(): void
    {
        $this->startServer();
        $transport = new CurlTransport();

        $response = $transport->request(
            'POST',
            sprintf('http://127.0.0.1:%d/echo', $this->serverPort),
            '{"hello":"world"}',
            [
                'Authorization' => 'Bearer secret',
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ],
        );

        self::assertSame(201, $response->statusCode);

        $payload = json_decode($response->body, true, 512, \JSON_THROW_ON_ERROR);
        self::assertSame('POST', $payload['method']);
        self::assertSame('Bearer secret', $payload['headers']['authorization']);
        self::assertSame('application/json', $payload['headers']['content-type']);
        self::assertSame('{"hello":"world"}', $payload['body']);
    }

    private function startServer(): void
    {
        $socket = stream_socket_server('tcp://127.0.0.1:0', $errno, $errstr);
        if (false === $socket) {
            self::fail('Could not bind test server: '.$errstr);
        }

        $address = stream_socket_get_name($socket, false);
        fclose($socket);

        if (!\is_string($address) || !preg_match('/:(\d+)$/', $address, $matches)) {
            self::fail('Could not resolve test server port.');
        }

        $this->serverPort = (int) $matches[1];
        $router = escapeshellarg(__DIR__.'/fixtures/http-router.php');
        $command = sprintf(
            'php -S 127.0.0.1:%d %s',
            $this->serverPort,
            $router,
        );

        $serverProcess = proc_open(
            $command,
            [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ],
            $pipes,
            __DIR__.'/fixtures',
        );

        if (!\is_resource($serverProcess)) {
            self::fail('Could not start PHP built-in server.');
        }
        $this->serverProcess = $serverProcess;

        foreach ($pipes as $pipe) {
            if (\is_resource($pipe)) {
                fclose($pipe);
            }
        }

        usleep(200000);
    }

    private function stopServer(): void
    {
        if (\is_resource($this->serverProcess)) {
            proc_terminate($this->serverProcess);
            proc_close($this->serverProcess);
            $this->serverProcess = null;
        }
    }
}
