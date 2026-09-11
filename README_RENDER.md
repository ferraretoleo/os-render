# Controle de Estoque Vivere Palhano - V12 para Render

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

## Banco

Nenhuma migração SQL nova é necessária para a V12.
