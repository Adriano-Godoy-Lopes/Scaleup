# ScaleUp LATAM & Europe

Plataforma PHP 8+ e MySQL para apresentação e planejamento estratégico acadêmico de expansão internacional.

## Instalação no XAMPP

1. Copie esta pasta para `C:\xampp\htdocs\scaleup`.
2. Inicie Apache e MySQL pelo painel XAMPP.
3. Abra o phpMyAdmin, importe `database/scaleup_latam_europe.sql` e confirme a criação do banco. Em seguida visite uma vez `http://localhost/scaleup/install.php`; ele cria o administrador usando `password_hash()`. Remova `install.php` depois.
4. Se necessário, ajuste host, usuário e senha do MySQL em `config/database.php`.
5. Acesse `http://localhost/scaleup/index.php`.

## Administração

Acesse `http://localhost/scaleup/admin/login.php`.

- E-mail: `admin@scaleup.local`
- Senha inicial: `ScaleUp@2026`

Altere a senha inicial diretamente no banco gerando um novo hash com `password_hash()` (por exemplo, com um pequeno script PHP temporário). Nunca salve senha em texto puro.

O painel permite editar projeto, integrantes, estratégias (inclusive o campo `ativo`), KPIs, cronograma e conteúdos; além de ler ou apagar mensagens. O banco traz somente indicadores explicitamente marcados como simulados.

## Estrutura

- `config/`: conexão PDO
- `includes/`: componentes e autenticação
- `admin/`: painel e CRUDs
- `database/`: script MySQL de instalação
- `css/` e `js/`: interface responsiva e gráfico

## Segurança

O projeto usa PDO, prepared statements, `password_verify`, sessão, proteção das rotas administrativas e escape de saída HTML. Para produção, configure credenciais fortes, HTTPS e permissões adequadas de servidor.
