<?php

declare(strict_types=1);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$headers = [];
foreach ($_SERVER as $name => $value) {
    if (!str_starts_with($name, 'HTTP_')) {
        continue;
    }

    $header = str_replace('_', '-', mb_strtolower(mb_substr($name, 5)));
    $headers[$header] = $value;
}

if (isset($_SERVER['CONTENT_TYPE'])) {
    $headers['content-type'] = $_SERVER['CONTENT_TYPE'];
}

$body = file_get_contents('php://input');

if ('/echo' === parse_url($_SERVER['REQUEST_URI'] ?? '', \PHP_URL_PATH)) {
    http_response_code(201);
    header('Content-Type: application/json');
    echo json_encode([
        'method' => $method,
        'headers' => $headers,
        'body' => $body,
    ], \JSON_THROW_ON_ERROR);

    return true;
}

http_response_code(404);

return false;
