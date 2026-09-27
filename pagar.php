<?php
require_once __DIR__ . '/includes/db.php';

$slug = $_GET['p'] ?? '';
$stmt = db()->prepare("SELECT * FROM products WHERE slug = ? AND active = 1");
$stmt->execute([$slug]);
$product = $stmt->fetch(PDO::FETCH_ASSOC);

$siteName = setting('site_name', 'Meu Gateway Pix');

if (!$product) {
    http_response_code(404);
    ?>
    <!doctype html>
    <html lang="pt-BR">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title><?php echo htmlspecialchars($siteName, ENT_QUOTES); ?></title>
        <link rel="stylesheet" href="assets/style.css">
    </head>
    <body>
        <div class="wrap">
            <div class="brand"><?php echo htmlspecialchars($siteName, ENT_QUOTES); ?></div>
            <div class="card error-box">Este link de pagamento não existe ou foi desativado.</div>
        </div>
    </body>
    </html>
    <?php
    exit;
}
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?> — <?php echo htmlspecialchars($siteName, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="wrap">
        <div class="brand"><?php echo htmlspecialchars($siteName, ENT_QUOTES); ?></div>

        <div class="card" id="form-card">
            <h3 style="margin-top:0;"><?php echo htmlspecialchars($product['name'], ENT_QUOTES); ?></h3>
            <?php if ($product['description']): ?>
                <p class="hint" style="font-size:0.85rem;"><?php echo htmlspecialchars($product['description'], ENT_QUOTES); ?></p>
            <?php endif; ?>
            <p style="font-size:1.6rem; font-weight:800; margin: 1rem 0;">R$ <?php echo number_format((float)$product['price'], 2, ',', '.'); ?></p>

            <label for="customer_name">Seu nome (opcional)</label>
            <input type="text" id="customer_name" maxlength="120">

            <button id="btn-gerar">Pagar com Pix</button>
            <div id="erro" class="error-box" style="display:none;"></div>
        </div>

        <div class="card pix-box" id="pix-card" style="display:none;">
            <span class="status-tag status-pending" id="status-tag">Aguardando pagamento</span>
            <img id="qr-img" src="" alt="QR Code Pix">
            <div class="pix-code" id="pix-code"></div>
            <button class="btn-outline" id="btn-copiar">Copiar código</button>
        </div>
    </div>

    <script>
        const slug       = <?php echo json_encode($product['slug']); ?>;
        const btnGerar   = document.getElementById('btn-gerar');
        const erroBox    = document.getElementById('erro');
        const formCard   = document.getElementById('form-card');
        const pixCard    = document.getElementById('pix-card');
        const qrImg      = document.getElementById('qr-img');
        const pixCodeBox = document.getElementById('pix-code');
        const statusTag  = document.getElementById('status-tag');
        const btnCopiar  = document.getElementById('btn-copiar');

        let pollTimer = null;

        btnGerar.addEventListener('click', async () => {
            erroBox.style.display = 'none';
            btnGerar.disabled = true;
            btnGerar.textContent = 'Gerando...';

            try {
                const res = await fetch('api/create_charge.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        product_slug: slug,
                        customer_name: document.getElementById('customer_name').value,
                    }),
                });
                const data = await res.json();

                if (!res.ok) {
                    throw new Error(data.error || 'Erro ao gerar o Pix.');
                }

                qrImg.src = data.qr_image;
                pixCodeBox.textContent = data.pix_code;
                formCard.style.display = 'none';
                pixCard.style.display = 'block';

                pollTimer = setInterval(() => checarStatus(data.external_id), 5000);
            } catch (e) {
                erroBox.textContent = e.message;
                erroBox.style.display = 'block';
            } finally {
                btnGerar.disabled = false;
                btnGerar.textContent = 'Pagar com Pix';
            }
        });

        btnCopiar.addEventListener('click', () => {
            navigator.clipboard.writeText(pixCodeBox.textContent);
            btnCopiar.textContent = 'Copiado!';
            setTimeout(() => { btnCopiar.textContent = 'Copiar código'; }, 2000);
        });

        async function checarStatus(externalId) {
            try {
                const res = await fetch('api/check_status.php?external_id=' + encodeURIComponent(externalId));
                const data = await res.json();
                if (data.status === 'paid') {
                    statusTag.textContent = 'Pagamento confirmado!';
                    statusTag.className = 'status-tag status-paid';
                    clearInterval(pollTimer);
                }
            } catch (e) { /* tenta de novo no próximo ciclo */ }
        }
    </script>
</body>
</html>
