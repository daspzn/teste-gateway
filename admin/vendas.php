<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$siteName = setting('site_name', 'Meu Gateway Pix');
$filtro   = $_GET['status'] ?? 'todos';

$sql = "SELECT * FROM orders";
$params = [];
if ($filtro === 'pago') {
    $sql .= " WHERE status = 'paid'";
} elseif ($filtro === 'pendente') {
    $sql .= " WHERE status = 'pending'";
}
$sql .= " ORDER BY id DESC LIMIT 200";

$stmt = db()->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vendas — <?php echo htmlspecialchars($siteName, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="admin-wrap">
        <nav class="admin-nav">
            <a href="index.php">Dashboard</a>
            <a href="vendas.php" class="active">Vendas</a>
            <a href="produtos.php">Produtos</a>
            <a href="settings.php">Configurações</a>
            <a href="change_admin.php">Usuário e senha</a>
            <a href="ia.php">Prompts de IA</a>
            <a href="logout.php">Sair</a>
        </nav>

        <h2>Vendas</h2>

        <div class="card" style="display:flex; gap:0.6rem; flex-wrap:wrap;">
            <a href="?status=todos" class="btn-outline btn-tab <?php echo $filtro === 'todos' ? 'btn-tab-active' : ''; ?>" style="width:auto; margin:0;">Todos</a>
            <a href="?status=pago" class="btn-outline btn-tab <?php echo $filtro === 'pago' ? 'btn-tab-active' : ''; ?>" style="width:auto; margin:0;">Pagos</a>
            <a href="?status=pendente" class="btn-outline btn-tab <?php echo $filtro === 'pendente' ? 'btn-tab-active' : ''; ?>" style="width:auto; margin:0;">Pendentes</a>
        </div>

        <div class="card">
            <table>
                <thead>
                    <tr><th>Data</th><th>Produto</th><th>Cliente</th><th>Valor</th><th>Status</th><th>ID externo</th></tr>
                </thead>
                <tbody>
                    <?php if (!$orders): ?>
                        <tr><td colspan="6" style="color: var(--text-2);">Nenhum pedido encontrado.</td></tr>
                    <?php endif; ?>
                    <?php foreach ($orders as $o): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($o['created_at'], ENT_QUOTES); ?></td>
                            <td><?php echo htmlspecialchars($o['product_name'] ?: '—', ENT_QUOTES); ?></td>
                            <td><?php echo htmlspecialchars($o['customer_name'] ?: '—', ENT_QUOTES); ?></td>
                            <td>R$ <?php echo number_format((float)$o['amount'], 2, ',', '.'); ?></td>
                            <td>
                                <span class="status-tag <?php echo $o['status'] === 'paid' ? 'status-paid' : 'status-pending'; ?>">
                                    <?php echo $o['status'] === 'paid' ? 'Pago' : 'Pendente'; ?>
                                </span>
                            </td>
                            <td style="font-size:0.75rem; color: var(--text-2);"><?php echo htmlspecialchars($o['external_id'], ENT_QUOTES); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
