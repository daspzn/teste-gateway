<?php
require_once __DIR__ . '/../includes/auth.php';

if (!empty($_SESSION['admin_logged_in'])) {
    header('Location: index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    $user = trim($_POST['user'] ?? '');
    $pass = (string) ($_POST['pass'] ?? '');

    $storedUser = (string) setting('admin_user', 'admin');
    $storedHash = (string) setting('admin_pass_hash', '');

    if ($user !== '' && hash_equals($storedUser, $user) && password_verify($pass, $storedHash)) {
        session_regenerate_id(true);
        $_SESSION['admin_logged_in'] = true;
        header('Location: index.php');
        exit;
    }
    $error = 'Usuário ou senha inválidos.';
}
$csrf = csrf_token();
$siteName = setting('site_name', 'Meu Gateway Pix');
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login — <?php echo htmlspecialchars($siteName, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="wrap">
        <div class="brand"><?php echo htmlspecialchars($siteName, ENT_QUOTES); ?> <span>· Admin</span></div>
        <div class="card">
            <?php if ($error): ?>
                <div class="error-box"><?php echo htmlspecialchars($error, ENT_QUOTES); ?></div>
            <?php endif; ?>
            <form method="post">
                <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES); ?>">
                <label for="user">Usuário</label>
                <input type="text" id="user" name="user" required autofocus>
                <label for="pass">Senha</label>
                <input type="password" id="pass" name="pass" required>
                <button type="submit">Entrar</button>
            </form>
            <p class="hint">Primeiro acesso? Usuário e senha padrão: <code>admin</code> / <code>admin123</code> — você vai ser obrigado a trocar a senha assim que entrar.</p>
        </div>
    </div>
</body>
</html>
