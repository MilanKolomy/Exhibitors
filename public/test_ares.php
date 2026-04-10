<?php
require __DIR__ . '/../vendor/autoload.php';

$ico = '27082440'; // Škoda Auto — testovací IČ

$client = new GuzzleHttp\Client(['timeout' => 5]);

try {
    $response = $client->get(
        'https://ares.gov.cz/ekonomicke-subjekty-v-be/rest/ekonomicke-subjekty/' . $ico,
        ['headers' => ['Accept' => 'application/json']]
    );

    echo '<pre>';
    echo 'Status: ' . $response->getStatusCode() . "\n\n";
    echo 'Response: ' . $response->getBody()->getContents();
    echo '</pre>';

} catch (\Exception $e) {
    echo '<pre>';
    echo 'Chyba: ' . get_class($e) . "\n";
    echo 'Zpráva: ' . $e->getMessage();
    echo '</pre>';
}