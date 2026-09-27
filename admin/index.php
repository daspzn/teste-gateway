<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$siteName  = setting('site_name', 'Meu Gateway Pix');
$hasApiKey = setting('lunarpay_api_key', '') !== '';

$totals = db()->query("
    SELECT
        SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) AS total_pago,
        SUM(CASE WHEN status = 'paid' AND date(paid_at) = date('now') THEN amount ELSE 0 END) AS hoje,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pendentes,
        SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS pagos,
        COUNT(*) AS total_pedidos
    FROM orders
")->fetch(PDO::FETCH_ASSOC);

$ticketMedio = ((int)$totals['pagos'] > 0) ? ((float)$totals['total_pago'] / (int)$totals['pagos']) : 0.0;

// Últimos 7 dias, pra montar um mini-gráfico só com CSS (sem biblioteca
// nenhuma — mantém o pacote leve).
$porDia = db()->query("
    SELECT date(paid_at) AS dia, SUM(amount) AS total
    FROM orders
    WHERE status = 'paid' AND paid_at >= date('now', '-6 days')
    GROUP BY dia
")->fetchAll(PDO::FETCH_KEY_PAIR);

$dias = [];
for ($i = 6; $i >= 0; $i--) {
    $data = date('Y-m-d', strtotime("-{$i} days"));
    $dias[$data] = (float) ($porDia[$data] ?? 0);
}
$maxDia = max($dias) ?: 1;

$orders = db()->query("SELECT * FROM orders ORDER BY id DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — <?php echo htmlspecialchars($siteName, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="admin-wrap">
        <nav class="admin-nav">
            <a href="index.php" class="active">Dashboard</a>
            <a href="vendas.php">Vendas</a>
            <a href="produtos.php">Produtos</a>
            <a href="settings.php">Configurações</a>
            <a href="change_admin.php">Usuário e senha</a>
            <a href="ia.php">Prompts de IA</a>
            <a href="logout.php">Sair</a>
        </nav>

        <h2><?php echo htmlspecialchars($siteName, ENT_QUOTES); ?></h2>

        <?php if (!$hasApiKey): ?>
            <div class="error-box">
                Você ainda não cadastrou sua chave da LunarPay — nenhum Pix vai ser gerado até isso ser feito.
                <a href="settings.php" style="color:inherit; text-decoration:underline;">Configurar agora</a>.
            </div>
        <?php endif; ?>

        <div class="alert-ok">
            💡 Quer um link fixo pra compartilhar (WhatsApp, Instagram) que já cobra o valor certo sem o cliente digitar nada? Cadastre em <a href="produtos.php" style="color:inherit; text-decoration:underline;">Produtos</a>.
        </div>

        <div class="stat-grid">
            <div class="stat-box">
                <span class="stat-label">Recebido hoje</span>
                <span class="stat-value">R$ <?php echo number_format((float)$totals['hoje'], 2, ',', '.'); ?></span>
            </div>
            <div class="stat-box">
                <span class="stat-label">Recebido no total</span>
                <span class="stat-value">R$ <?php echo number_format((float)$totals['total_pago'], 2, ',', '.'); ?></span>
            </div>
            <div class="stat-box">
                <span class="stat-label">Ticket médio</span>
                <span class="stat-value">R$ <?php echo number_format($ticketMedio, 2, ',', '.'); ?></span>
            </div>
            <div class="stat-box">
                <span class="stat-label">Pendentes agora</span>
                <span class="stat-value"><?php echo (int)$totals['pendentes']; ?></span>
            </div>
        </div>

        <div class="card">
            <p class="hint" style="margin-top:0;">Últimos 7 dias (Pix pagos)</p>
            <div class="mini-chart">
                <?php foreach ($dias as $data => $valor): ?>
                    <div class="mini-chart-col">
                        <div class="mini-chart-bar" style="height: <?php echo max(4, (int) round(($valor / $maxDia) * 100)); ?>%;" title="R$ <?php echo number_format($valor, 2, ',', '.'); ?>"></div>
                        <span class="mini-chart-label"><?php echo date('d/m', strtotime($data)); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="card">
            <p class="hint" style="margin-top:0;">Últimos pedidos</p>
            <table>
                <thead>
                    <tr><th>Data</th><th>Produto</th><th>Cliente</th><th>Valor</th><th>Status</th></tr>
                </thead>
                <tbody>
                    <?php if (!$orders): ?>
                        <tr><td colspan="5" style="color: var(--text-2);">Nenhum pedido ainda.</td></tr>
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
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <p class="hint"><a href="vendas.php" style="color: var(--accent);">Ver todas as vendas →</a></p>
        </div>
    </div>
</body>
</html>
