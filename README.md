# ScaleUp LATAM & Europe

Plataforma web de planejamento estratégico para expansão internacional, criada para o desafio **163241** e projeto **22782**. O sistema transforma o projeto acadêmico em uma experiência digital organizada e administrável, reunindo marketing digital, vendas, dados e planejamento para os mercados da América Latina e Europa.

> Os indicadores exibidos são **dados simulados para fins acadêmicos e estratégicos**. A aplicação não representa campanhas, clientes, vendas ou resultados reais.

## Visão do projeto

O ScaleUp organiza a expansão internacional em uma plataforma única, com estratégia de marketing e vendas digitais, funil comercial, comparação conceitual de mercados, KPIs demonstrativos, cronograma, equipe e painel de gestão de conteúdo.

## Como funciona

### Área pública

A página inicial apresenta contexto, objetivo, estratégia, funil de vendas, LATAM x Europa, indicadores, equipe, cronograma e contato. Os conteúdos principais vêm do MySQL. O gráfico de KPIs usa Chart.js e todos os valores são identificados como simulados. O formulário de contato valida os campos no navegador e no servidor, armazenando as mensagens no banco.

### Área administrativa

Em `/admin`, usuários autenticados podem manter a plataforma sem editar código:

| Recurso | Operações |
| --- | --- |
| Projeto | Editar nome, objetivo, justificativa e datas |
| Equipe | Criar, editar e excluir integrantes |
| Estratégias | Criar, editar, excluir e controlar o campo ativo |
| KPIs | Criar, editar e excluir indicadores demonstrativos |
| Cronograma | Criar, editar e excluir marcos |
| Conteúdos | Manter títulos, textos e seções do site |
| Contatos | Visualizar, marcar como lida e excluir mensagens |
| Imagens | Enviar JPEG, PNG ou WebP de até 2 MB |

O dashboard resume conteúdos, KPIs, integrantes e mensagens recebidas.

## Stack e arquitetura

- **Backend:** PHP 8+ com PDO e MySQL;
- **Frontend:** HTML5, CSS3, JavaScript e Chart.js;
- **Servidor local:** Apache e MySQL via XAMPP;
- **Banco:** `scaleup_latam_europe`.

```text
config/       Conexão segura com MySQL
database/     Script SQL de estrutura e dados iniciais
includes/     Funções reutilizáveis, autenticação, cabeçalho e rodapé
admin/        Login, dashboard, CRUDs, mensagens e upload
css/          Estilos responsivos
js/           Interações e gráfico de indicadores
```

## Instalação local (XAMPP)

1. Copie o projeto para `C:\xampp\htdocs\scaleup`.
2. No painel do XAMPP, inicie **Apache** e **MySQL**.
3. Acesse `http://localhost/phpmyadmin` e importe [database/scaleup_latam_europe.sql](database/scaleup_latam_europe.sql).
4. Confira as credenciais em `config/database.php`. No XAMPP padrão, o usuário é `root` e a senha é vazia.
5. Abra uma única vez `http://localhost/scaleup/install.php` para criar o administrador inicial.
6. Remova `install.php` imediatamente após a confirmação.
7. Abra `http://localhost/scaleup/`.

Se a pasta estiver nomeada `scale up`, use `http://localhost/scale%20up/` no lugar de `/scaleup/`.

## Primeiro acesso administrativo

- URL: `http://localhost/scaleup/admin/login.php`
- E-mail: `admin@scaleup.local`
- Senha inicial: `ScaleUp@2026`

Altere a senha inicial após o primeiro acesso. Senhas não são armazenadas em texto puro: o sistema as gera com `password_hash()` e verifica com `password_verify()`.

## Segurança implementada

- Conexão centralizada com PDO;
- Queries parametrizadas (prepared statements), reduzindo risco de SQL Injection;
- Escape de saída HTML, reduzindo risco de XSS;
- Sessões e proteção das páginas administrativas;
- Validação de campos em frontend e backend;
- Upload limitado por MIME, extensão e tamanho;
- Credenciais do banco mantidas no backend.

Para produção, configure credenciais exclusivas de banco, HTTPS, backups e permissões mínimas de arquivos.

## Equipe e período

- **Gerente:** Mauro Roberto Claro
- **Mentor:** Lucas
- **Desenvolvedores:** Adriano Godoy, Gabryel Rodrigues e Guilherme dos Santos
- **Início:** 03/08/2026
- **Conclusão:** 19/09/2026
- **Carga estimada:** 114 horas
