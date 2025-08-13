<?php
function beliProduk($paymentMethod, $phoneNumber, $price, $productCode, $productId, $quantity = 1) {
    $env = parse_ini_file(__DIR__ . '/../.env');
    $baseUrl = rtrim($env['BASE_URL'], '/');
    $tokenData = json_decode(file_get_contents(__DIR__ . '/../token.json'), true);
    $token = $tokenData['token'];

    $payload = json_encode([
        "paymentMethod" => $paymentMethod,
        "phoneNumber"   => $phoneNumber,
        "price"         => (int)$price,
        "productCode"   => $productCode,
        "productId"     => $productId,
        "quantity"      => $quantity
    ]);

    $ch = curl_init("$baseUrl/api/transactions");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            "Content-Type: application/json",
            "Authorization: Bearer $token"
        ],
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
