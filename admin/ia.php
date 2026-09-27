<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();

$siteName = setting('site_name', 'Meu Gateway Pix');
?>
<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Prompts de IA — <?php echo htmlspecialchars($siteName, ENT_QUOTES); ?></title>
    <link rel="stylesheet" href="../assets/style.css">
</head>
<body>
    <div class="admin-wrap admin-wrap-wide">
        <nav class="admin-nav">
            <a href="index.php">Dashboard</a>
            <a href="vendas.php">Vendas</a>
            <a href="produtos.php">Produtos</a>
            <a href="settings.php">Configurações</a>
            <a href="change_admin.php">Usuário e senha</a>
            <a href="ia.php" class="active">Prompts de IA</a>
            <a href="logout.php">Sair</a>
        </nav>

        <h2>Mexendo neste projeto com IA</h2>
        <p class="hint" style="font-size:0.85rem;">
            Você não precisa saber programar pra mudar este gateway. Copie o
            <strong>prompt de contexto</strong> abaixo, cole na IA (Claude,
            ChatGPT, Gemini — qualquer uma) junto com os arquivos do projeto,
            e depois peça o que quiser usando os exemplos logo abaixo.
        </p>

        <div class="card">
            <strong>1. Prompt de contexto (cole sempre primeiro, numa conversa nova)</strong>
            <div class="code-block">
"Este é um gateway de pagamentos Pix em PHP puro + SQLite. Estrutura:<br>
- index.php → checkout público (gera Pix, mostra QR Code, faz polling de status)<br>
- webhook.php → recebe confirmação de pagamento da LunarPay (valida assinatura HMAC-SHA256 antes de tudo)<br>
- api/create_charge.php e api/check_status.php → chamadas de API<br>
- includes/db.php → banco SQLite (tabelas settings e orders)<br>
- includes/auth.php → login/sessão/CSRF do admin<br>
- includes/lunarpay.php → wrapper da API da LunarPay (https://lunarpay.site)<br>
- pagar.php → página pública de pagamento de um produto específico (link fixo, preço travado)<br>
- admin/produtos.php → cadastro de produtos, gera o link de pagar.php pra cada um<br>
- admin/ → painel (dashboard, vendas, produtos, configurações, troca de usuário/senha)<br>
- assets/style.css → todo o visual do site (cores no bloco :root no topo)<br><br>
Regras que você NUNCA deve quebrar ao mexer no código:<br>
1. webhook.php sempre valida a assinatura (hash_equals) ANTES de processar qualquer coisa.<br>
2. A chave da LunarPay nunca pode aparecer no HTML/JS que o navegador do cliente carrega — ela só fica no banco (data/gateway.sqlite), lida no servidor.<br>
3. Toda query no banco usa PDO com parâmetros (?) — nunca concatenar variável direto no SQL.<br>
4. Os formulários do admin sempre mandam o campo csrf e o backend chama check_csrf().<br><br>
Confirma que entendeu a estrutura antes de eu pedir a próxima mudança."
            </div>
        </div>

        <div class="card">
            <strong>2. Mudar as cores / visual</strong>
            <div class="code-block">
"Quero mudar a cor principal do site de verde para [SUA COR, ex: azul #3b82f6]. Troque só as variáveis do bloco :root em assets/style.css (--accent e o que mais depender dela), sem mudar a estrutura do HTML de nenhuma página."
            </div>
        </div>

        <div class="card">
            <strong>3. Deixar a página de pagamento do produto (pagar.php) mais bonita</strong>
            <div class="code-block">
"Quero deixar pagar.php mais parecido com uma página de vendas de verdade: adicione uma imagem do produto (campo image_url na tabela products, com upload em admin/produtos.php), depoimentos fixos ou uma lista de benefícios acima do botão 'Pagar com Pix'. Não mude a lógica de geração de Pix nem o polling de status que já existe — só o visual e o conteúdo acima do formulário."
            </div>
        </div>

        <div class="card">
            <strong>4. Carrinho com vários produtos numa cobrança só</strong>
            <div class="code-block">
"Já existe uma tabela 'products' e admin/produtos.php cadastra produtos com link individual (pagar.php?p=slug). Quero criar uma página nova de carrinho: o cliente escolhe vários produtos da lista, o total é somado, e só então chama api/create_charge.php UMA vez com o valor total. Crie uma tabela 'order_items' (order_id, product_id, quantity, unit_price) pra guardar quais produtos entraram em cada pedido — sempre recalculando o total a partir dos preços salvos no banco, nunca confiando num total que venha pronto do front-end."
            </div>
        </div>

        <div class="card">
            <strong>5. Cupom de desconto</strong>
            <div class="code-block">
"Adicione suporte a cupom de desconto no checkout (index.php + api/create_charge.php):<br>
1. Tabela 'coupons' no SQLite (code, percent_off, active).<br>
2. Campo opcional de cupom no checkout.<br>
3. Em api/create_charge.php, valide o cupom (existe, está ativo) e recalcule o valor ANTES de chamar a LunarPay — nunca confie num valor com desconto que venha pronto do front-end.<br>
4. Salve o código do cupom usado na tabela orders (adicione uma coluna coupon_code)."
            </div>
        </div>

        <div class="card">
            <strong>6. Avisar por e-mail quando alguém pagar</strong>
            <div class="code-block">
"Em webhook.php, no trecho onde o pedido é marcado como 'paid' (dentro do bloco 'Aqui é o lugar de liberar o produto/acesso'), adicione o envio de um e-mail pra [SEU EMAIL] usando a função mail() nativa do PHP (ou PHPMailer se a hospedagem exigir SMTP autenticado), avisando o nome do produto, valor e nome do cliente. Não deixe isso travar a resposta 200 pro webhook se o e-mail falhar — envolva em try/catch."
            </div>
        </div>

        <div class="card">
            <strong>7. Já vendendo muito? Migrar de SQLite pra MySQL</strong>
            <div class="code-block">
"Este projeto usa SQLite (includes/db.php, função db()). Quero migrar pra MySQL porque [motivo, ex: já tenho hospedagem com MySQL e quero acessar o banco de outros sistemas também]. Reescreva includes/db.php pra conectar em MySQL via PDO usando variáveis de ambiente ou um config.php separado (host, dbname, user, password), mantendo exatamente as mesmas funções setting(), set_setting() e as tabelas settings/orders/products com os mesmos nomes de coluna — pra eu não precisar mudar mais nenhum outro arquivo do projeto."
            </div>
        </div>

        <div class="card">
            <strong>8. Pedir um campo a mais no checkout (ex: CPF, telefone)</strong>
            <div class="code-block">
"Adicione um campo de [CPF/telefone/o que precisar] no formulário de index.php, envie ele pra api/create_charge.php, salve numa coluna nova da tabela orders, e mostre essa informação também na lista de vendas do admin (admin/vendas.php). Valide o formato do campo antes de salvar."
            </div>
        </div>

        <div class="card">
            <strong>Checklist antes de subir qualquer mudança pra produção</strong>
            <ul class="ai-checklist">
                <li>webhook.php ainda valida a assinatura antes de processar (não peça pra IA "simplificar" removendo essa checagem).</li>
                <li>Nenhuma chave/senha foi parar em HTML, JS ou código que o navegador do cliente carrega.</li>
                <li>Toda query nova usa <code>?</code> com PDO (nunca concatenar valor direto no SQL).</li>
                <li>Todo formulário novo do admin manda <code>csrf</code> e chama <code>check_csrf()</code>.</li>
                <li>Testou localmente (<code>php -S localhost:8000</code>) antes de subir pra hospedagem de verdade.</li>
            </ul>
        </div>
    </div>
</body>
</html>
