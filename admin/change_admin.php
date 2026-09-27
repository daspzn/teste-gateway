<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$forced  = isset($_GET['forced']);
$success = '';
$error   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $currentPass = (string) ($_POST['current_pass'] ?? '');
    $newUser     = trim($_POST['new_user'] ?? '');
    $newPass     = (string) ($_POST['new_pass'] ?? '');
    $newPass2    = (string) ($_POST['new_pass2'] ?? '');

    $storedHash = (string) setting('admin_pass_hash', '');

    if (!password_verify($currentPass, $storedHash)) {
        $error = 'Senha atual incorreta.';
    } elseif ($newUser === '') {
        $error = 'O usuário não pode ficar vazio.';
    } elseif (strlen($newPass) < 8) {
        $error = 'A nova senha precisa ter pelo menos 8 caracteres.';
    } elseif ($newPass !== $newPass2) {
        $error = 'As duas senhas novas não são iguais.';
    } else {
        set_setting('admin_user', $newUser);
        set_setting('admin_pass_hash', password_hash($newPass, PASSWORD_DEFAULT));
        set_setting('must_change_password', '0');
        $success = 'Usuário e senha atualizados. Use os novos dados no próximo login.';
        $forced = false;
    }
}

$siteName   = setting('site_name', 'Meu Gateway Pix');
$adminUser  = setting('admin_user', 'admin');
$csrf       = csrf_token();
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuário e senha — <?php echo htmlspecialchars($siteName, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="admin-wrap">
        <?php if (!$forced): ?>
        <nav class="admin-nav">
            <a href="index.php">Dashboard</a>
            <a href="vendas.php">Vendas</a>
            <a href="produtos.php">Produtos</a>
            <a href="settings.php">Configurações</a>
            <a href="change_admin.php" class="active">Usuário e senha</a>
            <a href="ia.php">Prompts de IA</a>
            <a href="logout.php">Sair</a>
        </nav>
        <?php endif; ?>

        <h2>Usuário e senha do admin</h2>

        <?php if ($forced): ?>
            <div class="error-box">Você está usando a senha padrão (admin123). Troque antes de continuar.</div>
        <?php endif; ?>
        <?php if ($success): ?><div class="alert-ok"><?php echo htmlspecialchars($success, ENT_QUOTES); ?> <a href="index.php" style="color:inherit;">Ir pro painel →</a></div><?php endif; ?>
        <?php if ($error): ?><div class="error-box"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div><?php endif; ?>

        <div class="card">
            <form method="post">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES); ?>">

                <label for="current_pass">Senha atual</label>
                <input type="password" id="current_pass" name="current_pass" required autofocus>

                <label for="new_user">Novo usuário (atual: <?php echo htmlspecialchars($adminUser, ENT_QUOTES); ?>)</label>
                <input type="text" id="new_user" name="new_user" value="<?php echo htmlspecialchars($adminUser, ENT_QUOTES); ?>" required>

                <label for="new_pass">Nova senha (mínimo 8 caracteres)</label>
                <input type="password" id="new_pass" name="new_pass" minlength="8" required>

                <label for="new_pass2">Repita a nova senha</label>
                <input type="password" id="new_pass2" name="new_pass2" minlength="8" required>

                <button type="submit">Salvar</button>
            </form>
        </div>
    </div>
</body>
</html>
