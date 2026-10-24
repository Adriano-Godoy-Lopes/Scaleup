# ScaleUp LATAM & Europe

Landing page de captação de leads + painel de KPIs para a estratégia de marketing e vendas digitais em LATAM e Europa (projeto Eyegis / Pierre Yves Claudon).

PHP puro com PDO e banco **MySQL** (XAMPP/phpMyAdmin). O banco `scaleup_landing` e as tabelas são criados automaticamente no primeiro acesso. Também funciona com **SQLite** embutido, sem instalar nada (`DB_DRIVER = sqlite`).

## Funcionalidades

- **Landing page por mercado**: Brasil (pt-BR), LATAM hispânica (es) e Europa (en), com mensagens, CTAs, canais e consentimento (LGPD/GDPR) adaptados a cada região. Responsiva (mobile e desktop).
- **Captura de leads** com validação, anti-spam (honeypot) e atribuição de origem via UTM (`?utm_source=google&utm_campaign=...`).
- **Rastreamento do funil**: visitas, cliques em CTA, início de formulário e leads.
- **Painel (`admin.php`)**: KPIs (conversão, CPL, CAC, ROI, ticket médio), funil Lead → MQL → SQL → Cliente, comparação LATAM x Europa, gráficos por dia/país, ROI por canal, gestão de campanhas e orçamento simulado, atualização de etapa/valor de cada lead, exportação CSV e gerador de dados simulados.

## Como rodar no XAMPP (MySQL)

1. Copie a pasta para `C:\xampp\htdocs\scaleup-landing`.
2. No painel do XAMPP, inicie **Apache** e **MySQL**.
3. (Opcional) No phpMyAdmin (`http://localhost/phpmyadmin`), importe `database/scaleup_landing.sql`. Se você não importar, o site cria o banco e as tabelas sozinho.
4. Confira a conexão em `config.php` (padrão do XAMPP: `localhost`, usuário `root`, senha vazia, banco `scaleup_landing`).
5. Acesse `http://localhost/scaleup-landing/` (site) e `http://localhost/scaleup-landing/admin.php` (painel).
6. No painel, clique em **Gerar dados simulados** para preencher os KPIs para a apresentação.

Senha padrão do painel: `scaleup2026`. Para mudar, altere `config.php` ou defina a variável de ambiente `ADMIN_PASSWORD`.

## Rodar sem MySQL (SQLite)

Em `config.php`, troque `DB_DRIVER` para `'sqlite'` (ou defina a variável de ambiente `DB_DRIVER=sqlite`) e rode:

```bash
php -S localhost:8080 router.php
```

O arquivo `data/scaleup.sqlite` é criado automaticamente. Os arquivos `.htaccess` bloqueiam o acesso direto a `data/` e `database/`.

## Estrutura

| Arquivo | Função |
|---|---|
| `index.php` | Landing page |
| `content.php` | Textos de cada mercado (edite aqui para mudar o conteúdo) |
| `api.php` | Recebe leads e eventos do funil |
| `admin.php` | Painel de KPIs e gestão |
| `export.php` | Exportação CSV dos leads |
| `seed.php` | Dados simulados para apresentação |
| `db.php` / `config.php` | Conexão (MySQL ou SQLite), criação das tabelas e configuração |
| `database/scaleup_landing.sql` | Script para importar no phpMyAdmin |
| `database/tables_mysql.sql` | Tabelas MySQL usadas na criação automática |
