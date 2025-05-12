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
 * Przykładowe dane przesyłki typu "inpost_courier standard"
 */
$shipmentData = [
    // tu wstawiamy dane odbiorcy
    'receiver' => [
        'company_name' => 'Przykładowa Nazwa',
        'first_name' => 'Jan',
        'last_name' => 'Kowalski',
        'email' => 'jan.kowalski@example.com',
        'phone' => '600700800',
        'name' => 'Jan Kowalski',
        'address' => [
            'street' => 'Zielona',
            'building_number' => '10',
            'city' => 'Warszawa',
            'post_code' => '00-001',
            'country_code' => 'PL',
        ],
    ],
    // tu wstawiamy nasze dane
    'sender' => [
        'name' => 'Maciej',
        'company_name' => 'company_name',
        'first_name' => null,
        'last_name' => null,
        'email' => 'example@domain.pl',
        'phone' => '500400300',
        'address' => [
            'street' => 'Czerniakowska',
            'building_number' => '18',
            'city' => 'Warszawa',
            'post_code' => '10-000',
            'country_code' => 'PL',
        ]
    ],
    'parcels' => [
       [
            "id" => "small package",
            "dimensions" => [
                "length" => "80",
                "width" => "360",
                "height" => "640",
                "unit" => "mm"
            ],
            "weight" => [
                "amount" => "25",
                "unit" => "kg"
            ],
            "is_non_standard" => false
        ]
    ],
    // tu ustawiamy typ przesyłki
    'service' => 'inpost_courier_standard',
    'only_choice_of_offer' => true,
];

try {
    $organizationId = $_ENV['PACZKOMATY_INPOST_ORGANIZATIONID'];
    // Utworzenie przesyłki
    $response = $client->post('/v1/organizations/'.$organizationId.'/shipments', [
        'json' => $shipmentData
    ]);

    $body = $response->getBody()->getContents();
    echo "Shipment created:\n$body\n";
    file_put_contents('log.txt', "Shipment Response:\n$body\n", FILE_APPEND);

    $shipment = json_decode($body, true);
    $shipmentId = $shipment['id'] ?? null;

    do {
        sleep(5); // Odczekaj 5 sekund przed kolejnym sprawdzeniem
        $response = $client->get("/v1/shipments/{$shipmentId}");
        $shipment = json_decode($response->getBody(), true);
        $status = $shipment['status'];
        echo "Aktualny status: {$status}\n";
        file_put_contents('log.txt', "Shipment $shipmentId has offer selected\n", FILE_APPEND);
    } while ($status !== 'offer_selected');

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
