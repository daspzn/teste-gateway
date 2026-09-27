# Gateway Pix — Base pra você personalizar

Ponto de partida pronto pra você (ou seus seguidores) colocarem no ar um
gateway de pagamentos Pix próprio, usando a **LunarPay** (https://lunarpay.site)
como processador por trás. Sem mensalidade de servidor complicado: só PHP
+ SQLite (o banco é um arquivo só, não precisa configurar MySQL).

Zero dependência externa — sem CDN, sem biblioteca JS, sem framework.
Só PHP, HTML, CSS e um punhado de JavaScript puro. O pacote inteiro pesa
menos de 100 KB, ideal pra hospedar em qualquer lugar (Mediafire, Google
Drive, hospedagem compartilhada básica).

## O que já vem pronto

- **Checkout de valor livre** (`index.php`): cliente digita o valor, gera Pix real, QR Code + copia-e-cola, confirma sozinho.
- **Gerador de link de pagamento** (`admin/produtos.php` + `pagar.php`): cadastre um produto UMA vez (nome, preço, descrição) e ganhe um link fixo pra compartilhar — no WhatsApp, na bio do Instagram, onde quiser. O cliente só clica e paga, sem digitar valor nenhum. Você pode ativar/desativar ou remover cada link quando quiser.
- **Confirmação automática de pagamento** (`webhook.php`) com verificação de assinatura HMAC — idêntico ao mecanismo real da LunarPay.
- **Painel de admin** (`/admin`) com:
  - **Dashboard** (`index.php`): recebido hoje, recebido total, ticket médio, pendentes, gráfico dos últimos 7 dias e últimos pedidos.
  - **Vendas** (`vendas.php`): lista completa de pedidos, com filtro por pago/pendente.
  - **Produtos** (`produtos.php`): o gerador de link de pagamento descrito acima.
  - **Configurações** (`settings.php`): trocar o **nome do gateway** e a **chave da API da LunarPay**.
  - **Usuário e senha** (`change_admin.php`): trocar o login do admin (obrigatório no primeiro acesso).
  - **Prompts de IA** (`ia.php`): prompts prontos pra pedir mudanças no projeto pra qualquer IA (Claude, ChatGPT, Gemini), sem precisar saber programar.

## 1. Como subir isso no ar

Precisa de uma hospedagem com **PHP 7.4+** e a extensão `pdo_sqlite`
habilitada — praticamente qualquer hospedagem compartilhada já vem assim
por padrão (inclusive Hostinger, que é o mais comum entre quem segue
esse conteúdo).

### Opção A — pelo Gerenciador de Arquivos (mais fácil, sem instalar nada)

1. Entre no painel da sua hospedagem (no caso da Hostinger: **hPanel → seu site → Arquivos → Gerenciador de Arquivos**).
2. Entre na pasta `public_html` do seu domínio (é a raiz que o navegador enxerga).
3. Clique em **Fazer upload** e envie o arquivo `.zip` deste projeto direto ali (não precisa extrair no seu PC antes).
4. Depois do upload, clique com o botão direito no `.zip` dentro do Gerenciador de Arquivos e escolha **Extrair**.
5. Confirme que os arquivos ficaram direto dentro de `public_html` (e não dentro de uma subpasta `public_html/gateway-base-seguidores/`) — se caiu numa subpasta, mova tudo pra fora dela e apague a subpasta vazia.

### Opção B — por FTP (FileZilla ou similar)

1. Pegue os dados de FTP no painel da sua hospedagem (host, usuário, senha, porta — geralmente 21).
2. Conecte com um cliente FTP (ex: FileZilla, gratuito).
3. Extraia o `.zip` no seu computador primeiro.
4. Arraste o **conteúdo** da pasta (não a pasta em si) pra dentro de `public_html` no servidor.

### Depois de subir, em qualquer uma das opções

1. Acesse `https://SEUDOMINIO.com/admin/login.php` no navegador.
2. Se aparecer a tela de login, deu certo — o sistema já criou o banco
   (`data/gateway.sqlite`) sozinho na primeira visita.
3. Se der erro de PHP (tela branca ou "internal server error"), veja a
   seção **Problemas comuns** mais abaixo.

## 2. Primeiro login e configuração

1. Login padrão na primeira vez:
   - Usuário: `admin`
   - Senha: `admin123`
2. O sistema vai **forçar você a trocar a senha** antes de liberar o resto
   do painel — escolha um usuário e senha novos (senha com 8+ caracteres).
   Guarde isso num lugar seguro, não tem "esqueci minha senha" aqui ainda.
3. Vá em **Configurações** e cadastre o **nome do seu gateway** e a
   **chave da LunarPay** (veja o passo a passo completo abaixo).

## 3. Como pegar sua chave da LunarPay (passo a passo)

1. Crie sua conta (ou faça login) em **https://lunarpay.site** — grátis, sem mensalidade, sem cartão de crédito.
2. No menu lateral esquerdo do painel da LunarPay, clique em **Configurações**.
3. Dentro de Configurações, clique na aba **Integrações** (ao lado de "Geral").
4. Procure a seção **"Acesso Desenvolvedor"** — tem um campo "Chave Privada (API Token)".
5. Se estiver vazio ("Nenhuma chave gerada"), clique em **"Gerar Nova Chave"**.
6. Clique no ícone do olho para revelar a chave, ou direto no botão de copiar ao lado do campo.
7. Volte no painel **deste gateway** → Configurações → cole a chave completa (começa com `ghost_`) → clique em **Salvar**.

Sem isso cadastrado, os botões "Gerar Pix" e "Pagar com Pix" não
funcionam — o dashboard mostra um alerta vermelho enquanto a chave não
estiver configurada.

⚠️ **Nunca compartilhe essa chave com ninguém**, nem cole em outro site.
Se desconfiar que vazou, volte na LunarPay e clique em "Gerar Nova
Chave" — invalida a antiga na hora.

## 4. Como criar seu primeiro link de pagamento

1. No painel deste gateway, vá em **Produtos**.
2. Preencha nome, valor e (opcional) uma descrição curta.
3. Clique em **"Gerar link de pagamento"**.
4. O link aparece na lista logo abaixo, pronto pra copiar e compartilhar
   (ex: `https://SEUDOMINIO.com/pagar.php?p=meu-produto`).
5. Quem abrir esse link vê o nome, valor e descrição do produto, e paga
   direto — sem precisar digitar nada.
6. Quer parar de vender algo sem apagar o histórico? Clique em
   **Desativar** em vez de Remover — o link para de funcionar, mas as
   vendas antigas continuam no seu histórico.

## 5. Como personalizar

- **Nome do gateway**: painel de admin → Configurações → campo "Nome do
  gateway". Aparece no título da página e no topo do checkout.
- **Cores e visual**: edite `assets/style.css` — as cores principais estão
  todas no bloco `:root` no topo do arquivo, é só trocar os valores.
- **Checkout de valor livre**: edite os campos iniciais em `index.php`
  (`value="Produto Digital"`, `value="29.90"`).
- **Qualquer mudança maior** (carrinho com vários produtos, cupom de
  desconto, e-mail automático, migrar pra MySQL etc.): abra **Prompts de
  IA** dentro do painel de admin — tem prompt pronto pra cada uma dessas
  mudanças, já explicando pra IA a estrutura do projeto e o que ela NUNCA
  pode quebrar (validação do webhook, chave da API, proteção contra SQL
  injection e CSRF).

## 6. Como o pagamento é confirmado

Quando você gera o Pix (tanto pelo checkout livre quanto por um link de
produto), o sistema já avisa a LunarPay pra mandar a confirmação pra
`https://SEUDOMINIO.com/webhook.php` automaticamente — **você não
precisa configurar nada manualmente na LunarPay pra isso**, o próprio
código já manda o endereço certo a cada cobrança criada
(`api/create_charge.php`, campo `callback_url`).

O `webhook.php`:
1. Confere a assinatura (`X-LunarPay-Signature`) usando sua chave como
   segredo — se não bater, rejeita com 401.
2. Só marca como pago se o pedido ainda estiver `pending` (evita marcar
   duas vezes se a LunarPay reenviar o mesmo aviso).
3. É o lugar certo pra você adicionar sua lógica de entrega (enviar
   e-mail, liberar acesso, etc.) — tem um comentário indicando onde.

## 7. Segurança — não pule isso

- **Troque a senha padrão** assim que instalar (o sistema já obriga).
- **Nunca compartilhe a pasta `data/`** nem seu conteúdo — é onde fica sua
  chave da LunarPay e a senha do admin (com hash, mas ainda assim).
  As pastas `data/` e `includes/` já vêm bloqueadas por `.htaccess` pra
  acesso direto pelo navegador — não apague esses arquivos `.htaccess`.
- **Não coloque sua chave da LunarPay em nenhum HTML/JS que o navegador
  do cliente carrega.** Neste projeto ela já fica só no servidor
  (`data/gateway.sqlite`), nunca é enviada pro front-end.
- O preço de um produto vendido por link (`pagar.php`) é sempre lido do
  banco no servidor — o cliente nunca consegue alterar o valor pelo
  navegador, mesmo mexendo no código da página.
- O site precisa rodar em **HTTPS** — sem isso, tanto a chave da API
  quanto os dados do seu cliente trafegam sem proteção. Hostinger e a
  maioria das hospedagens já ativam certificado SSL grátis sozinhas
  (procure por "SSL" no painel se seu domínio ainda estiver em `http://`).

## 8. Problemas comuns

- **Tela branca / "Internal Server Error"**: normalmente é a extensão
  `pdo_sqlite` desligada. No hPanel da Hostinger: **Avançado → PHP
  Configuration** (ou "Selecionar versão do PHP") → marque `pdo_sqlite`
  e `sqlite3` → salvar.
- **"Failed to open stream" ou erro na pasta `data/`**: a hospedagem não
  deixou o PHP criar arquivo na pasta. Dê permissão de escrita na pasta
  `data/` (no Gerenciador de Arquivos: botão direito → Permissões →
  755, ou 775 se 755 não resolver).
- **Arquivos foram parar dentro de uma subpasta**: aconteceu na extração
  do zip — mova o conteúdo pra raiz de `public_html` (ver Opção A acima).
- **"Gerar Pix" sempre dá erro**: normalmente é a chave da LunarPay vazia
  ou errada — confira em Configurações se ela foi salva certinho.

## 9. Quer entender o passo a passo completo por trás disso?

https://lunarpay.site/aprenda.php — tem o contrato completo da API,
prompts prontos pra IA montar variações desse gateway do zero, e uma
seção explicando como usar a LunarPay em modelo BaaS (atender mais de
um cliente com o mesmo sistema).
