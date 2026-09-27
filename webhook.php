<?php
/**
 * Recebe a confirmação de pagamento da LunarPay.
 * Ordem importa: valida a assinatura ANTES de confiar em qualquer coisa
 * do corpo da requisição. Ver aprenda.php da LunarPay, etapa 4, pra
 * entender cada passo abaixo.
 */
require_once __DIR__ . '/includes/db.php';

$rawBody         = file_get_contents('php://input');
$signatureHeader = $_SERVER['HTTP_X_LUNARPAY_SIGNATURE'] ?? '';
$apiKey          = (string) setting('lunarpay_api_key', '');

if ($apiKey === '') {
    http_response_code(500);
    exit('Gateway sem chave da LunarPay configurada.');
}

$expected = 'sha256=' . hash_hmac('sha256', $rawBody, $apiKey);
if ($signatureHeader === '' || !hash_equals($expected, $signatureHeader)) {
    http_response_code(401);
    exit('Assinatura inválida.');
}

$payload    = json_decode($rawBody, true) ?: [];
$externalId = (string) ($payload['external_id'] ?? '');
$status     = (string) ($payload['status'] ?? '');

if ($externalId === '') {
    http_response_code(200);
    exit('ok');
}

$pdo = db();
$stmt = $pdo->prepare("SELECT status FROM orders WHERE external_id = ?");
$stmt->execute([$externalId]);
$currentStatus = $stmt->fetchColumn();

if ($currentStatus === false) {
    // Pedido que a LunarPay conhece mas que não existe aqui (ex: teste
    // manual). Responde 200 pra LunarPay não ficar reenviando à toa.
    http_response_code(200);
    exit('pedido desconhecido');
}

if ($currentStatus === 'pending' && $status === 'paid') {
    // O "AND status = 'pending'" garante idempotência: se a LunarPay
    // reenviar o mesmo webhook duas vezes, a segunda vez não faz nada.
    $upd = $pdo->prepare(
        "UPDATE orders SET status = 'paid', paid_at = datetime('now')
         WHERE external_id = ? AND status = 'pending'"
    );
    $upd->execute([$externalId]);

    // ── Aqui é o lugar de liberar o produto/acesso do seu cliente ──
    // Exemplos: enviar e-mail com o link de download, liberar acesso
    // num grupo, chamar outra API sua, etc.
}

http_response_code(200);
echo 'ok';
