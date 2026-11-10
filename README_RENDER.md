# Controle de Estoque Vivere Palhano para Render

Versão preparada para Render usando Docker + Apache + PHP 8.3.

## Variáveis de ambiente

Configure no Render:

- APP_BASE_URL
- SUPABASE_URL
- SUPABASE_PUBLISHABLE_KEY

Use somente a Publishable Key do Supabase. Não use service_role nem Secret Key.

## Publicação

1. Crie um repositório no GitHub.
2. Envie todos os arquivos desta pasta para a raiz.
3. No Render, crie um Web Service.
4. Conecte o repositório GitHub.
5. Escolha Runtime: Docker.
6. Escolha Instance Type: Free.
7. Adicione as variáveis de ambiente.
8. Faça o deploy.
9. Depois do Render gerar a URL final, atualize APP_BASE_URL.
10. Teste /health.php, /login.php e a raiz do site.

## Módulo de orçamentos

Antes de publicar as novas páginas, abra o SQL Editor do Supabase e execute, nesta ordem:

1. `sql/migracao_niveis_acesso.sql`, caso ainda não tenha sido executado.
2. `sql/migracao_orcamentos.sql`.

A segunda migração cria as tabelas, o histórico, as políticas de acesso e o bucket privado `orcamentos`. Não crie o bucket manualmente e não o marque como público.

Depois do deploy:

1. Entre com um usuário `executante` e confirme cadastro, listagem e download.
2. Confirme que esse usuário não vê ações de aprovação ou exclusão.
3. Entre com um usuário `admin` e aprove um orçamento.
4. Confira se o histórico mostra a decisão, a data e o responsável.
5. Exclua um orçamento de teste e confirme que o arquivo também foi removido do Storage.

Para desfazer somente a implantação do código, volte o deploy do Render. Não apague as tabelas nem o bucket, pois isso excluiria os documentos cadastrados.
