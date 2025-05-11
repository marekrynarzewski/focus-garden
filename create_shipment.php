<?php

require 'vendor/autoload.php';

use Dotenv\Dotenv;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;

// Załaduj zmienne środowiskowe (np. PACZKOMATY_INPOST_APITOKEN)
$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$apiToken = $_ENV['PACZKOMATY_INPOST_APITOKEN'] ?? '';
$baseUri = 'https://sandbox-api-shipx-pl.easypack24.net'; // Adres produkcyjny lub sandboxowy z dokumentacji

$client = new Client([
    'base_uri' => $baseUri,
    'headers' => [
        'Authorization' => 'Bearer ' . $apiToken,
        'Accept' => 'application/json',
        'Content-Type' => 'application/json',
    ]
]);

/**
 * Przykładowe dane przesyłki typu "courier standard"
 */
$shipmentData = [
    // tu wstawiamy dane odbiorcy
    'receiver' => [
        'email' => 'jan.kowalski@example.com',
        'phone' => '600700800',
        'name' => 'Jan Kowalski',
        'address' => [
            'line1' => 'ul. Zielona 10',
            'city' => 'Warszawa',
            'postcode' => '00-001',
            'country_code' => 'PL'
        ],

        'name' => null,
        'company_name' => 'company_name',
        'first_name' => null,
        'last_name' => null,
        'email' => null,
        'phone' => null,
        'address' => [
            'street' => null,
            'building_number' => null,
            'city' => null,
            'post_code' => null,
            'country_code' => null,
        ],
    ],
    // tu wstawiamy nasze dane
    'sender' => [
        'name' => null,
        'company_name' => 'company_name',
        'first_name' => null,
        'last_name' => null,
        'email' => null,
        'phone' => null,
        'address' => [
            'street' => null,
            'building_number' => null,
            'city' => null,
            'post_code' => null,
            'country_code' => null,
        ]
    ],
    'parcels' => [
        [
            'dimensions' => ['length' => 20, 'width' => 20, 'height' => 10],
            'weight' => 1.5
        ]
    ],
    // tu ustawiamy typ przesyłki
    'service' => 'inpost_courier_standard',
];

try {
    // Utworzenie przesyłki
    $response = $client->post('/v1/organizations/'.$_ENV['PACZKOMATY_INPOST_ORGANIZATIONID'].'/shipments', [
        'json' => $shipmentData
    ]);

    $body = $response->getBody()->getContents();
    echo "Shipment created:\n$body\n";
    file_put_contents('log.txt', "Shipment Response:\n$body\n", FILE_APPEND);

    $shipment = json_decode($body, true);
    $shipmentId = $shipment['id'] ?? null;

    if ($shipmentId) {
        // Zamów kuriera
        $orderResponse = $client->post("/v1/shipments/$shipmentId/dispatch");
        $orderBody = $orderResponse->getBody()->getContents();
        echo "Courier ordered:\n$orderBody\n";
        file_put_contents('log.txt', "Dispatch Response:\n$orderBody\n", FILE_APPEND);
    } else {
        throw new Exception("Brak shipment ID w odpowiedzi.");
    }
} catch (RequestException $e) {
    $error = $e->hasResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
    echo "API error:\n$error\n";
    file_put_contents('log.txt', "Error:\n$error\n", FILE_APPEND);
} catch (Exception $e) {
    echo "Exception:\n" . $e->getMessage() . "\n";
    file_put_contents('log.txt', "Exception:\n" . $e->getMessage() . "\n", FILE_APPEND);
} catch (GuzzleException $e) {
    echo "Exception:\n" . $e->getMessage() . "\n";
    file_put_contents('log.txt', "Exception:\n" . $e->getMessage() . "\n", FILE_APPEND);
}
