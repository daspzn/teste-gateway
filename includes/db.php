<?php
/**
 * Banco local (SQLite, um arquivo só, sem precisar configurar MySQL).
 * Guarda as configurações do gateway (nome, chave da LunarPay, login do
 * admin) e os pedidos gerados. O arquivo fica em data/gateway.sqlite,
 * protegido por .htaccess (ver data/.htaccess) — nunca deve ser acessível
 * direto pelo navegador.
 */

define('DB_PATH', __DIR__ . '/../data/gateway.sqlite');

function db(): PDO
{
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $isNew = !file_exists(DB_PATH);
    if (!is_dir(dirname(DB_PATH))) {
        mkdir(dirname(DB_PATH), 0755, true);
    }

    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $pdo->exec("CREATE TABLE IF NOT EXISTS settings (
        `key` TEXT PRIMARY KEY,
        `value` TEXT
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS orders (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        external_id TEXT UNIQUE NOT NULL,
        pix_id TEXT,
        amount REAL NOT NULL,
        product_id INTEGER,
        product_name TEXT,
        customer_name TEXT,
        status TEXT NOT NULL DEFAULT 'pending',
        created_at TEXT NOT NULL DEFAULT (datetime('now')),
        paid_at TEXT
    )");

    // Produtos com link de pagamento fixo — o "gerador de link" do painel
    // (admin/produtos.php): cadastra uma vez, gera uma URL pra compartilhar
    // (ex: WhatsApp, bio do Instagram) que sempre cobra o mesmo valor.
    $pdo->exec("CREATE TABLE IF NOT EXISTS products (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        slug TEXT UNIQUE NOT NULL,
        name TEXT NOT NULL,
        description TEXT,
        price REAL NOT NULL,
        active INTEGER NOT NULL DEFAULT 1,
        created_at TEXT NOT NULL DEFAULT (datetime('now'))
    )");

    if ($isNew) {
        // Primeiro boot: cria as configurações padrão. Login inicial é
        // admin/admin123 — o painel força a troca no primeiro acesso
        // (ver includes/auth.php, require_login()).
        $defaults = [
            'site_name'            => 'Meu Gateway Pix',
            'lunarpay_api_key'     => '',
            'admin_user'           => 'admin',
            'admin_pass_hash'      => password_hash('admin123', PASSWORD_DEFAULT),
            'must_change_password' => '1',
        ];
        $stmt = $pdo->prepare("INSERT INTO settings (`key`, `value`) VALUES (?, ?)");
        foreach ($defaults as $k => $v) {
            $stmt->execute([$k, $v]);
        }
    } else {
        // Migração leve pra quem já tinha instalado uma versão anterior
        // (antes da tabela 'products' existir): adiciona a coluna que
        // faltar sem apagar nada do banco existente.
        $cols = array_column($pdo->query("PRAGMA table_info(orders)")->fetchAll(PDO::FETCH_ASSOC), 'name');
        if (!in_array('product_id', $cols, true)) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN product_id INTEGER");
        }
    }

    return $pdo;
}

function setting(string $key, $default = null)
{
    $stmt = db()->prepare("SELECT `value` FROM settings WHERE `key` = ?");
    $stmt->execute([$key]);
    $v = $stmt->fetchColumn();
    return $v === false ? $default : $v;
}

function set_setting(string $key, string $value): void
{
    $stmt = db()->prepare(
        "INSERT INTO settings (`key`, `value`) VALUES (?, ?)
         ON CONFLICT(`key`) DO UPDATE SET `value` = excluded.value"
    );
    $stmt->execute([$key, $value]);
}
