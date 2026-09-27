<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/lunarpay.php';

header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true) ?: [];

$productSlug  = trim((string) ($input['product_slug'] ?? ''));
$customerName = trim((string) ($input['customer_name'] ?? ''));
$productId    = null;

if ($productSlug !== '') {
    // Veio de um link de pagamento (pagar.php) — o preço é sempre lido do
    // banco aqui no servidor, nunca confiamos num valor que o navegador
    // do cliente possa ter mandado junto (evita alguém pagar menos do
    // que o produto realmente custa).
    $stmt = db()->prepare("SELECT * FROM products WHERE slug = ? AND active = 1");
    $stmt->execute([$productSlug]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$product) {
        http_response_code(404);
        echo json_encode(['error' => 'Este produto não existe mais ou foi desativado.']);
        exit;
    }

    $amount      = (float) $product['price'];
    $productName = $product['name'];
    $productId   = (int) $product['id'];
} else {
    // Checkout de valor livre (index.php) — aqui o valor É digitado pelo
    // cliente de propósito (cobrança avulsa, sem produto fixo).
    $amount      = (float) ($input['amount'] ?? 0);
    $productName = trim((string) ($input['product_name'] ?? 'Produto'));
}

if ($amount < 5) {
    http_response_code(400);
    echo json_encode(['error' => 'Valor mínimo é R$ 5,00.']);
    exit;
}

if (setting('lunarpay_api_key', '') === '') {
    http_response_code(500);
    echo json_encode(['error' => 'Este gateway ainda não foi configurado. Peça pro dono cadastrar a chave da LunarPay no painel de admin.']);
    exit;
}

$externalId  = 'ord_' . bin2hex(random_bytes(8));
$scheme      = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$callbackUrl = $scheme . $_SERVER['HTTP_HOST'] . '/webhook.php';

$res = lunarpay_create_charge($amount, $externalId, $callbackUrl);

if ($res['http_code'] !== 200 || empty($res['data']['pix_id'])) {
    http_response_code(502);
    echo json_encode([
        'error' => $res['data']['error'] ?? 'Falha ao gerar o Pix. Tente de novo em alguns segundos.',
    ]);
    exit;
}

$stmt = db()->prepare(
    "INSERT INTO orders (external_id, pix_id, amount, product_id, product_name, customer_name, status)
     VALUES (?, ?, ?, ?, ?, ?, 'pending')"
);
$stmt->execute([$externalId, $res['data']['pix_id'], $amount, $productId, $productName, $customerName]);

echo json_encode([
    'external_id' => $externalId,
    'qr_image'    => $res['data']['qr_image'],
    'pix_code'    => $res['data']['pix_code'],
    'amount'      => $amount,
]);
