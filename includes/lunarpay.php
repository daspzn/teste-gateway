<?php
/**
 * Wrapper fino em cima da API da LunarPay (https://lunarpay.site).
 * Documentação completa: https://lunarpay.site/api-docs.php
 * Passo a passo de como pegar sua chave: veja o README.md deste projeto.
 */
require_once __DIR__ . '/db.php';

define('LUNARPAY_BASE_URL', 'https://lunarpay.site');

function lunarpay_request(string $method, string $path, ?array $body = null): array
{
    $apiKey = (string) setting('lunarpay_api_key', '');

    $ch = curl_init(LUNARPAY_BASE_URL . $path);
    $headers = ['Authorization: Bearer ' . $apiKey];

    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);

    if ($body !== null) {
        $headers[] = 'Content-Type: application/json';
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    }
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $raw      = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    $data = $raw ? (json_decode($raw, true) ?? []) : [];

    return [
        'http_code'  => $httpCode,
        'data'       => $data,
        'curl_error' => $curlErr,
        'raw'        => $raw,
    ];
}

/** Gera uma cobrança Pix real na LunarPay. */
function lunarpay_create_charge(float $amount, string $externalId, string $callbackUrl): array
{
    return lunarpay_request('POST', '/api.php', [
        'amount'       => $amount,
        'external_id'  => $externalId,
        'callback_url' => $callbackUrl,
    ]);
}

/** Consulta o status de uma cobrança direto na LunarPay (fallback caso o
 *  webhook nunca chegue — ver aprenda.php da LunarPay, etapa 5). */
function lunarpay_check_status(string $externalId): array
{
    return lunarpay_request('GET', '/check_status.php?external_id=' . urlencode($externalId));
}
