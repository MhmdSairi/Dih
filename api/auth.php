<?php

function loadEnv($path = __DIR__ . '/../.env') {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($name, $value) = explode('=', $line, 2);
        putenv(trim($name) . '=' . trim($value));
    }
}

function httpPostJson($url, $data) {
    $payload = json_encode($data);
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "Content-Type: application/json"
        ],
        CURLOPT_POSTFIELDS => $payload
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        echo "CURL error: $error\n";
        exit;
    }

    return json_decode($response, true);
}

function simpanToken($data) {
    file_put_contents(__DIR__ . '/../token.json', json_encode([
        'token' => $data['token'],
        'refreshToken' => $data['refreshToken'],
        'user' => $data['user']
    ]));
}

function auth() {
    loadEnv();
    $base_url = rtrim(getenv("BASE_URL"), '/');

    $tokenFile = __DIR__ . '/../token.json';

    if (file_exists($tokenFile)) {
        $saved = json_decode(file_get_contents($tokenFile), true);
        if (!empty($saved['refreshToken'])) {
            // Coba refresh token
            $refreshUrl = $base_url . "/api/auth/refresh";
            $response = httpPostJson($refreshUrl, [
                "refreshToken" => $saved['refreshToken']
            ]);

            if (isset($response['token'])) {
                simpanToken($response);
                return $response['user'];
            } else {
                echo "Refresh token gagal: " . ($response['message'] ?? 'Tidak diketahui') . "\n";
            }
        }
    }

    // Jika tidak ada token, atau refresh gagal → login manual
    $loginUrl = $base_url . "/api/auth/login";
    $response = httpPostJson($loginUrl, [
        "username" => getenv("MhmdSairi"),
        "password" => getenv("Sairi131220"),
        "rememberMe" => true
    ]);

    if (!isset($response['token'])) {
        echo "Login gagal: " . ($response['message'] ?? 'Tidak diketahui') . "\n";
        exit;
    }

    simpanToken($response);
    return $response['user'];
}
