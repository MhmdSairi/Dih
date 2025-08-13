<?php
function getProdukList() {
    $env = parse_ini_file(__DIR__ . '/../.env');
    $baseUrl = rtrim($env['BASE_URL'], '/');
    $tokenData = json_decode(file_get_contents(__DIR__ . '/../token.json'), true);
    $token = $tokenData['token'];

    $ch = curl_init("$baseUrl/api/products");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            "authorization: Bearer $token",
        ],
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data['products'] ?? [];
}
