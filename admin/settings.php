<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $newName   = trim($_POST['site_name'] ?? '');
    $newApiKey = trim($_POST['lunarpay_api_key'] ?? '');

    if ($newName === '') {
        $error = 'O nome do gateway não pode ficar vazio.';
    } else {
        set_setting('site_name', $newName);
        if ($newApiKey !== '') {
            set_setting('lunarpay_api_key', $newApiKey);
        }
        $success = 'Configurações salvas.';
    }
}

$siteName = setting('site_name', 'Meu Gateway Pix');
$apiKey   = setting('lunarpay_api_key', '');
$csrf     = csrf_token();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configurações — <?php echo htmlspecialchars($siteName, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="admin-wrap">
        <nav class="admin-nav">
            <a href="index.php">Dashboard</a>
            <a href="vendas.php">Vendas</a>
            <a href="produtos.php">Produtos</a>
            <a href="settings.php" class="active">Configurações</a>
            <a href="change_admin.php">Usuário e senha</a>
            <a href="ia.php">Prompts de IA</a>
            <a href="logout.php">Sair</a>
        </nav>

        <h2>Configurações</h2>

        <?php if ($success): ?><div class="alert-ok"><?php echo htmlspecialchars($success, ENT_QUOTES); ?></div><?php endif; ?>
        <?php if ($error): ?><div class="error-box"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div><?php endif; ?>

        <div class="card">
            <form method="post">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES); ?>">

                <label for="site_name">Nome do gateway (aparece pro seu cliente)</label>
                <input type="text" id="site_name" name="site_name" value="<?php echo htmlspecialchars($siteName, ENT_QUOTES); ?>" maxlength="80" required>

                <label for="lunarpay_api_key">Chave da API da LunarPay</label>
                <input type="text" id="lunarpay_api_key" name="lunarpay_api_key"
                       value="<?php echo $apiKey ? htmlspecialchars(substr($apiKey, 0, 10) . str_repeat('•', 10), ENT_QUOTES) : ''; ?>"
                       placeholder="ghost_...">
                <p class="hint">
                    Deixe em branco pra manter a chave atual (ela já está salva, o campo só mostra os primeiros caracteres por segurança).
                    Pra trocar, cole a chave nova completa aqui.
                </p>

                <button type="submit">Salvar</button>
            </form>
        </div>

        <div class="card">
            <strong>Como pegar sua chave da LunarPay (passo a passo)</strong>
            <ol class="hint" style="padding-left: 1.1rem; font-size: 0.85rem;">
                <li>Crie sua conta (ou faça login) em <a href="https://lunarpay.site" target="_blank" style="color: var(--accent);">lunarpay.site</a> — é grátis, sem mensalidade.</li>
                <li>Dentro do painel da LunarPay, no menu lateral esquerdo, clique em <strong>Configurações</strong>.</li>
                <li>Dentro de Configurações, clique na aba <strong>Integrações</strong> (fica ao lado de "Geral").</li>
                <li>Procure a seção <strong>"Acesso Desenvolvedor"</strong> — tem um campo escrito "Chave Privada (API Token)".</li>
                <li>Se o campo estiver vazio ("Nenhuma chave gerada"), clique em <strong>"Gerar Nova Chave"</strong>.</li>
                <li>Clique no ícone do olho 👁 pra revelar a chave, ou direto no botão de copiar ao lado do campo.</li>
                <li>Volte aqui, cole a chave completa no campo acima (ela começa com <code>ghost_</code>) e clique em <strong>Salvar</strong>.</li>
            </ol>
            <div class="error-box" style="margin-top:0.75rem;">
                ⚠️ Nunca compartilhe essa chave com ninguém, nem cole ela em nenhum outro site — quem tiver ela consegue gerar cobrança e (se você tiver habilitado) até mover seu saldo. Se desconfiar que ela vazou, volte na LunarPay e clique em "Gerar Nova Chave" pra invalidar a antiga na hora.
            </div>
            <p class="hint">Passo a passo completo da API, com exemplos de código em várias linguagens: <a href="https://lunarpay.site/aprenda.php" target="_blank" style="color: var(--accent);">lunarpay.site/aprenda.php</a>.</p>
        </div>
    </div>
</body>
</html>
