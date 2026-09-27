<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

function slugify(string $s): string
{
    $s = strtolower(trim($s));
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
    $s = trim($s, '-');
    return $s !== '' ? $s : 'produto';
}

$siteName = setting('site_name', 'Meu Gateway Pix');
$success  = '';
$error    = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $name  = trim($_POST['name'] ?? '');
        $price = (float) ($_POST['price'] ?? 0);
        $desc  = trim($_POST['description'] ?? '');

        if ($name === '') {
            $error = 'Dê um nome pro produto.';
        } elseif ($price < 5) {
            $error = 'O valor mínimo é R$ 5,00 (é o mínimo aceito pela LunarPay).';
        } else {
            $baseSlug = slugify($name);
            $slug = $baseSlug;
            $check = db()->prepare("SELECT COUNT(*) FROM products WHERE slug = ?");
            $i = 1;
            do {
                $check->execute([$slug]);
                if ((int) $check->fetchColumn() === 0) break;
                $i++;
                $slug = $baseSlug . '-' . $i;
            } while (true);

            db()->prepare("INSERT INTO products (slug, name, description, price, active) VALUES (?, ?, ?, ?, 1)")
                ->execute([$slug, $name, $desc, $price]);
            $success = 'Produto criado! O link já está pronto na lista abaixo.';
        }
    } elseif ($action === 'toggle') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare("UPDATE products SET active = 1 - active WHERE id = ?")->execute([$id]);
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        db()->prepare("DELETE FROM products WHERE id = ?")->execute([$id]);
        $success = 'Produto removido. Links já compartilhados param de funcionar.';
    }
}

$products = db()->query("SELECT * FROM products ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
$scheme   = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$baseUrl  = $scheme . $_SERVER['HTTP_HOST'];
$csrf     = csrf_token();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produtos — <?php echo htmlspecialchars($siteName, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="admin-wrap admin-wrap-wide">
        <nav class="admin-nav">
            <a href="index.php">Dashboard</a>
            <a href="vendas.php">Vendas</a>
            <a href="produtos.php" class="active">Produtos</a>
            <a href="settings.php">Configurações</a>
            <a href="change_admin.php">Usuário e senha</a>
            <a href="ia.php">Prompts de IA</a>
            <a href="logout.php">Sair</a>
        </nav>

        <h2>Produtos e links de pagamento</h2>
        <p class="hint" style="margin-top:-0.5rem;">
            Cadastre um produto uma vez e ganhe um link fixo pra compartilhar
            (WhatsApp, bio do Instagram, etc.) — sempre cobra o mesmo valor,
            sem o cliente precisar digitar nada.
        </p>

        <?php if ($success): ?><div class="alert-ok"><?php echo htmlspecialchars($success, ENT_QUOTES); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error-box"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div><?php endif; ?>

        <div class="card">
            <strong>Novo produto</strong>
            <form method="post">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES); ?>">
                <input type="hidden" name="action" value="create">

                <label for="name">Nome do produto</label>
                <input type="text" id="name" name="name" maxlength="120" required>

                <label for="price">Valor (R$)</label>
                <input type="number" id="price" name="price" min="5" step="0.01" required>

                <label for="description">Descrição (opcional, aparece na página de pagamento)</label>
                <input type="text" id="description" name="description" maxlength="200">

                <button type="submit">Gerar link de pagamento</button>
            </form>
        </div>

        <div class="card">
            <strong>Seus produtos</strong>
            <?php if (!$products): ?>
                <p class="hint">Nenhum produto cadastrado ainda.</p>
            <?php endif; ?>
            <?php foreach ($products as $p): ?>
                <?php $link = $baseUrl . '/pagar.php?p=' . urlencode($p['slug']); ?>
                <div style="border: 1px solid var(--border); border-radius: 12px; padding: 1rem; margin-top: 1rem;">
                    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:0.5rem;">
                        <div>
                            <strong><?php echo htmlspecialchars($p['name'], ENT_QUOTES); ?></strong>
                            — R$ <?php echo number_format((float)$p['price'], 2, ',', '.'); ?>
                            <span class="status-tag <?php echo $p['active'] ? 'status-paid' : 'status-pending'; ?>" style="margin-left:0.5rem;">
                                <?php echo $p['active'] ? 'Ativo' : 'Desativado'; ?>
                            </span>
                        </div>
                        <div style="display:flex; gap:0.5rem;">
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES); ?>">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                <button type="submit" class="btn-outline" style="width:auto; margin:0; padding:0.4rem 0.8rem; font-size:0.75rem;">
                                    <?php echo $p['active'] ? 'Desativar' : 'Ativar'; ?>
                                </button>
                            </form>
                            <form method="post" style="display:inline;" onsubmit="return confirm('Remover este produto? Links já compartilhados param de funcionar.');">
                                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES); ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo (int)$p['id']; ?>">
                                <button type="submit" class="btn-outline" style="width:auto; margin:0; padding:0.4rem 0.8rem; font-size:0.75rem; color: var(--danger); border-color: var(--danger);">Remover</button>
                            </form>
                        </div>
                    </div>
                    <?php if ($p['description']): ?>
                        <p class="hint" style="margin-top:0.5rem;"><?php echo htmlspecialchars($p['description'], ENT_QUOTES); ?></p>
                    <?php endif; ?>
                    <div class="pix-code" style="margin-top:0.75rem; margin-bottom:0;" id="link-<?php echo (int)$p['id']; ?>"><?php echo htmlspecialchars($link, ENT_QUOTES); ?></div>
                    <button type="button" class="btn-outline" style="margin-top:0.5rem;" onclick="copiarLink(<?php echo (int)$p['id']; ?>, this)">Copiar link</button>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function copiarLink(id, btn) {
            const el = document.getElementById('link-' + id);
            navigator.clipboard.writeText(el.textContent);
            const original = btn.textContent;
            btn.textContent = 'Copiado!';
            setTimeout(() => { btn.textContent = original; }, 2000);
        }
    </script>
</body>
</html>
