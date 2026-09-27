<?php
/**
 * Sessão + CSRF do painel de admin. Simples de propósito (este projeto é
 * um ponto de partida pra quem está aprendendo, não um framework).
 */
require_once __DIR__ . '/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/** Bloqueia a página pra quem não estiver logado, e força a troca de senha
 *  padrão antes de liberar qualquer outra página do admin. */
function require_login(): void
{
    if (empty($_SESSION['admin_logged_in'])) {
        header('Location: login.php');
        exit;
    }

    $mustChange = setting('must_change_password', '0') === '1';
    $current    = basename($_SERVER['SCRIPT_NAME']);
    if ($mustChange && !in_array($current, ['change_admin.php', 'logout.php'], true)) {
        header('Location: change_admin.php?forced=1');
        exit;
    }
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function check_csrf(): void
{
    $sent = $_POST['csrf'] ?? '';
    if ($sent === '' || empty($_SESSION['csrf']) || !hash_equals($_SESSION['csrf'], $sent)) {
        http_response_code(403);
        exit('Token de segurança inválido. Volte e tente de novo.');
    }
}
