# ScaleUp LATAM & Europe

Landing page de captação de leads + painel de KPIs para a estratégia de marketing e vendas digitais em LATAM e Europa (projeto Eyegis / Pierre Yves Claudon).

PHP puro com banco **SQLite embutido** (PDO): o arquivo `data/scaleup.sqlite` e as tabelas são criados automaticamente no primeiro acesso — não precisa instalar MySQL.

## Funcionalidades

- **Landing page por mercado**: Brasil (pt-BR), LATAM hispânica (es) e Europa (en), com mensagens, CTAs, canais e consentimento (LGPD/GDPR) adaptados a cada região. Responsiva (mobile e desktop).
- **Captura de leads** com validação, anti-spam (honeypot) e atribuição de origem via UTM (`?utm_source=google&utm_campaign=...`).
- **Rastreamento do funil**: visitas, cliques em CTA, início de formulário e leads.
- **Painel (`admin.php`)**: KPIs (conversão, CPL, CAC, ROI, ticket médio), funil Lead → MQL → SQL → Cliente, comparação LATAM x Europa, gráficos por dia/país, ROI por canal, gestão de campanhas e orçamento simulado, atualização de etapa/valor de cada lead, exportação CSV e gerador de dados simulados.

## Como rodar

Requisitos: PHP 8.1+ com `pdo_sqlite` (já vem no XAMPP/WAMP/Laragon e na maioria das hospedagens).

```bash
php -S localhost:8080 router.php
```

Acesse http://localhost:8080 (site) e http://localhost:8080/admin.php (painel).

Senha padrão do painel: `scaleup2026` — altere em `config.php` ou defina a variável de ambiente `ADMIN_PASSWORD`.

Em Apache (XAMPP/hospedagem), basta copiar a pasta para o `htdocs`/`public_html`; o `.htaccess` bloqueia o acesso direto ao banco.

## Estrutura

| Arquivo | Função |
|---|---|
| `index.php` | Landing page |
| `content.php` | Textos de cada mercado (edite aqui para mudar o conteúdo) |
| `api.php` | Recebe leads e eventos do funil |
| `admin.php` | Painel de KPIs e gestão |
| `export.php` | Exportação CSV dos leads |
| `seed.php` | Dados simulados para apresentação |
| `db.php` / `config.php` | Conexão SQLite, criação das tabelas e configuração |
