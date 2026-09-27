<?php
require_once __DIR__ . '/includes/db.php';
$siteName = setting('site_name', 'Meu Gateway Pix');
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

        <div class="card" id="form-card">
            <label for="product_name">Produto</label>
            <input type="text" id="product_name" value="Produto Digital" maxlength="120">

            <label for="amount">Valor (R$)</label>
            <input type="number" id="amount" min="5" step="0.01" value="29.90">

            <label for="customer_name">Seu nome (opcional)</label>
            <input type="text" id="customer_name" maxlength="120">

            <button id="btn-gerar">Gerar Pix</button>
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
                        amount: parseFloat(document.getElementById('amount').value || '0'),
                        product_name: document.getElementById('product_name').value,
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
                btnGerar.textContent = 'Gerar Pix';
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
