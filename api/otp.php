<?php
function requestOtp($msisdn) {
    $url = 'https://web-otp-xl.netlify.app/api/otp/request';
    $payload = json_encode(['msisdn' => $msisdn]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}

function verifyOtp($msisdn, $otp) {
    $url = 'https://web-otp-xl.netlify.app/api/otp/verify';
    $payload = json_encode([
        'msisdn' => $msisdn,
        'otp'    => $otp
    ]);

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $payload,
        CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    return json_decode($response, true);
}
