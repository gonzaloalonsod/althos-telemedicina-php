# AlthoTelemedicina PHP SDK

Cliente PHP tipado para integrar sesiones de video de
[AlthoTelemedicina](https://telemedicina.althosalud.com).

La librería no depende de Symfony, Laravel ni de clientes HTTP de terceros:
usa **cURL** (extensión PHP) y la misma clase `TelemedicineClient` en cualquier
proyecto.

## Instalación

```bash
composer require althosalud/telemedicina
```

Requisitos: PHP 8.2+ y extensión `ext-curl`. Versión estable actual: **v0.1.0**.

- Packagist: [althosalud/telemedicina](https://packagist.org/packages/althosalud/telemedicina)
- Repositorio: [gonzaloalonsod/althos-telemedicina-php](https://github.com/gonzaloalonsod/althos-telemedicina-php)
- Changelog: [CHANGELOG.md](CHANGELOG.md)

## PHP

```php
use AlthoSalud\Telemedicina\TelemedicineClient;

$client = new TelemedicineClient(
    baseUrl: 'https://telemedicina.althosalud.com',
    apiKey: 'tm_...',
);

$session = $client->createSession(
    externalReference: 'appointment:123',
    metadata: ['source' => 'my-product'],
);

$professionalUrl = $session->professionalJoinUrl;
$patientUrl = $session->patientJoinUrl;
```

## Symfony

Instalá el paquete y registrá el cliente como servicio. No hay bundle propio:
configurás la misma clase que en PHP puro.

```dotenv
# .env.local
TELEMEDICINA_API_BASE_URL=https://telemedicina.althosalud.com
TELEMEDICINA_API_KEY=tm_...
```

```yaml
# config/services.yaml
services:
    AlthoSalud\Telemedicina\TelemedicineClient:
        arguments:
            $baseUrl: '%env(TELEMEDICINA_API_BASE_URL)%'
            $apiKey: '%env(TELEMEDICINA_API_KEY)%'
```

Inyectalo donde lo necesites:

```php
use AlthoSalud\Telemedicina\Contract\TelemedicineClientInterface;
use AlthoSalud\Telemedicina\TelemedicineClient;

final class StartVisit
{
    public function __construct(
        private TelemedicineClient $telemedicine,
    ) {
    }

    public function __invoke(string $attentionId): void
    {
        $session = $this->telemedicine->createSession(
            externalReference: 'attention:'.$attentionId,
            metadata: ['source' => 'my-product'],
        );
    }
}
```

Opcional: alias de la interfaz al servicio concreto en `services.yaml`:

```yaml
    AlthoSalud\Telemedicina\Contract\TelemedicineClientInterface:
        alias: AlthoSalud\Telemedicina\TelemedicineClient
```

## Laravel

Instalá el paquete y centralizá la instancia en un servicio de aplicación.

```dotenv
# .env
TELEMEDICINA_API_BASE_URL=https://telemedicina.althosalud.com
TELEMEDICINA_API_KEY=tm_...
```

```php
// app/Services/TelemedicinaClientFactory.php
namespace App\Services;

use AlthoSalud\Telemedicina\TelemedicineClient;

final class TelemedicinaClientFactory
{
    public function client(): TelemedicineClient
    {
        return new TelemedicineClient(
            baseUrl: (string) config('services.telemedicina.base_url'),
            apiKey: (string) config('services.telemedicina.api_key'),
        );
    }
}
```

```php
// config/services.php
return [
    'telemedicina' => [
        'base_url' => env('TELEMEDICINA_API_BASE_URL'),
        'api_key' => env('TELEMEDICINA_API_KEY'),
    ],
];
```

```php
// app/Http/Controllers/StartVisitController.php
public function __invoke(TelemedicinaClientFactory $factory)
{
    $session = $factory->client()->createSession('appointment:123');
}
```

## Operaciones

```php
$credentials = $client->me();

$created = $client->createSession('appointment:123', [
    'source' => 'my-product',
]);

$current = $client->getSession($created->id);
$ended = $client->endSession($created->id);
```

No envíes nombres, documentos ni notas clínicas en `metadata`; usá
`externalReference` para correlacionar con tu sistema.

## Aplicaciones multi-tenant

Si cada Organization/tenant guarda su propia API key, omití la key global (o
dejala vacía) y creá un cliente inmutable por operación:

```php
$tenantClient = $client->withApiKey($decryptedOrganizationApiKey);
$session = $tenantClient->createSession('org:7:appointment:123');
```

`withApiKey()` no modifica el cliente compartido.

## Errores

- `ConfigurationException`: falta URL o API key.
- `TransportException`: no se pudo contactar la plataforma.
- `ApiException`: respuesta HTTP no exitosa; expone `statusCode`,
  `apiErrorCode` y `details`.
- `InvalidResponseException`: la respuesta no cumple el contrato esperado.

Las excepciones no incluyen la API key.

## Transporte HTTP

Por defecto el cliente usa `CurlTransport`. Para tests o integraciones
avanzadas podés inyectar cualquier implementación de
`HttpTransportInterface`.

```php
use AlthoSalud\Telemedicina\Contract\HttpTransportInterface;

$client = new TelemedicineClient(
    baseUrl: 'https://telemedicina.althosalud.com',
    apiKey: 'tm_...',
    transport: $customTransport,
);
```
