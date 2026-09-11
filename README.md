# Controle de Estoque para InfinityFree

Versão preparada especificamente para hospedagem gratuita InfinityFree.

## O que foi removido

Esta versão não usa:

- Composer
- PHPMailer
- pasta `vendor`
- SMTP
- envio de e-mail

A solicitação de compra é registrada no Supabase e fica disponível no relatório para impressão.

## Estrutura pronta para o htdocs

Envie o conteúdo desta pasta diretamente para:

```text
htdocs/
```

A estrutura deve ficar assim:

```text
htdocs/
├── .htaccess
├── config.php
├── index.php
├── login.php
├── logout.php
├── material_form.php
├── movimentacao.php
├── historico.php
├── pedido.php
├── relatorio_pedidos.php
├── _layout_top.php
├── _layout_bottom.php
├── src/
│   ├── AuthClient.php
│   ├── SupabaseClient.php
│   ├── bootstrap.php
│   └── helpers.php
└── sql/
    └── schema.sql
```

Não coloque esses arquivos dentro de outra pasta `public`.

## 1. Criar banco no Supabase

No Supabase:

1. Crie um projeto.
2. Entre em `SQL Editor`.
3. Abra o arquivo `sql/schema.sql`.
4. Copie todo o conteúdo.
5. Execute no SQL Editor.

Isso cria:

- materiais
- movimentações
- solicitações de compra
- triggers de estoque
- view de status
- RLS
- políticas para usuários autenticados

## 2. Pegar as credenciais

No Supabase abra:

```text
Project Settings
API
```

Pegue:

```text
Project URL
anon/public key
```

Edite o arquivo `config.php`:

```php
'supabase' => [
    'url' => 'https://SEU-PROJETO.supabase.co',
    'anon_key' => 'SUA_SUPABASE_ANON_KEY',
],
```

Não é necessário colocar Service Role nesta versão.

## 3. Criar usuário do sistema

No Supabase abra:

```text
Authentication
Users
Add user
```

Crie um usuário com e-mail e senha.

Esse usuário será usado no `login.php`.

## 4. Enviar para InfinityFree

Entre no painel do InfinityFree.

Abra o File Manager.

Entre em:

```text
htdocs
```

Apague o arquivo padrão `index2.html`, caso exista.

Envie todo o conteúdo desta pasta para `htdocs`.

Ao final deve existir:

```text
htdocs/index.php
htdocs/login.php
htdocs/config.php
htdocs/src/bootstrap.php
```

## 5. Acessar

Abra seu domínio:

```text
https://SEU-SUBDOMINIO.infinityfreeapp.com/
```

Se não estiver autenticado, o sistema direcionará para:

```text
login.php
```

Entre com o usuário criado no Supabase.

## 6. Funcionalidades

### Dashboard

Mostra:

- materiais
- categoria
- saldo atual
- quantidade mínima
- localização
- status normal ou crítico

### Cadastro de materiais

Campos:

- nome
- categoria
- unidade de medida
- quantidade inicial
- quantidade mínima
- localização
- observações

### Movimentações

Permite:

- entrada
- saída
- quantidade
- destino/aplicação
- responsável

O saldo é atualizado pelo PostgreSQL.

Uma saída maior que o estoque disponível é rejeitada.

### Histórico

Exibe as movimentações registradas.

As movimentações são protegidas contra alteração e exclusão para preservar auditoria.

### Solicitação de compra

Registra:

- produto
- quantidade atual no momento do pedido
- quantidade solicitada
- solicitante
- data/hora
- status

### Relatório de compras

Disponível em:

```text
relatorio_pedidos.php
```

Permite:

- filtro por período
- filtro por status
- impressão pelo navegador
- impressão em A4 paisagem

## Segurança

O sistema usa Supabase Auth.

Após o login, o JWT do usuário fica na sessão PHP.

As consultas REST são enviadas ao Supabase usando:

```text
anon key + JWT do usuário
```

Dessa forma as políticas RLS são aplicadas.

O arquivo `.htaccess` bloqueia acesso HTTP direto ao `config.php`.


## Atualização: e-mail + aprovação de compras

Esta versão inclui envio de e-mail por API HTTPS usando `src/EmailService.php`, sem Composer e sem PHPMailer.

No `config.php`, configure:

```php
'resend' => [
    'api_key' => 'SUA_API_KEY',
    'from' => 'Controle de Estoque <REMETENTE_AUTORIZADO>',
    'to' => 'residencialviverepalhano@gmail.com',
],
```

Para um banco já criado, execute no Supabase SQL Editor:

```text
sql/migracao_compras_status.sql
```

O relatório de compras permite:

- Comprado
- Recusado
- motivo obrigatório na recusa
- observação opcional na compra
- data/hora da alteração
- usuário que alterou
- impressão do relatório


## Fluxo de solicitação via WhatsApp

Esta versão não envia e-mail.

Ao registrar uma solicitação:

1. o pedido é salvo no Supabase;
2. abre um pop-up com a mensagem pronta;
3. o usuário pode clicar em `Copiar mensagem`;
4. ou clicar em `Abrir WhatsApp`;
5. o pedido também fica disponível no Relatório de Compras.

O relatório mantém:

- status Solicitado
- botão Comprado
- botão Recusado
- motivo obrigatório na recusa
- OBS opcional na compra
- data/hora da decisão
- usuário responsável pela decisão


## Atualização V4

Alterações:

- botão `Solicitar compra` renomeado para `Compra`;
- cadastro/edição de material agora possui situação:
  - Aprovado
  - Rejeitado
- situação aparece também no Dashboard.

Para banco já existente, execute:

```text
sql/migracao_status_material.sql
```

## Atualização V5

A situação Aprovado/Rejeitado continua disponível na edição do material, mas não é exibida no Dashboard.


## Atualização V6

O Dashboard foi dividido em duas áreas.

À esquerda existe um painel menor de Solicitações de Compra que mostra:

- quantidade de solicitações pendentes;
- até cinco solicitações mais recentes;
- produto;
- quantidade solicitada;
- solicitante;
- data e hora;
- botão `Analisar solicitações`.

Ao abrir, o sistema direciona para o Relatório de Compras filtrado por `Solicitado`, onde permanecem as ações:

- Comprado;
- Recusado;
- OBS / motivo.


## Atualização V7: níveis de acesso

Foram criados dois níveis:

### Executante
Pode acessar somente:

- Movimentação
- Histórico
- Compra

### Administrador
Pode acessar tudo:

- Dashboard
- Cadastro e edição de materiais
- Movimentação
- Histórico
- Compra
- Relatório de compras
- Comprar/recusar solicitações
- Usuários e níveis de acesso

### Instalação

Execute no Supabase SQL Editor:

```text
sql/migracao_niveis_acesso.sql
```

Depois defina o primeiro administrador:

```sql
update public.perfis
set nivel = 'admin'
where email = 'SEU_EMAIL_DE_LOGIN';
```

Faça logout e login novamente.

Novos usuários criados em `Authentication > Users` entram automaticamente como `Executante`.

O Admin poderá depois acessar o menu `Usuários` e alterar o nível de cada usuário.


## Atualização V8: Conferência de Estoque

Foi criada a página `conferencia.php`, disponível apenas para Administradores.

O botão `Conferência` aparece no menu do Admin.

O relatório apresenta todo o estoque com material, categoria, localização, unidade, saldo do sistema, quantidade mínima, quantidade conferida, diferença e observação. A impressão é preparada para A4 em orientação paisagem.


## Atualização V9: performance dos relatórios

Foram adicionados índices específicos para:

- Conferência de estoque;
- Histórico de movimentações;
- Relatório de compras;
- Solicitações pendentes no Dashboard.

Execute no Supabase SQL Editor:

```text
sql/migracao_indices_performance.sql
```

Também foram otimizadas as consultas PHP:

- `conferencia.php` não busca mais colunas que não são exibidas;
- `relatorio_pedidos.php` passa data inicial e final para o PostgreSQL em vez de filtrar a data final no PHP;
- `index.php` deixou de usar `select=*` para reduzir o tráfego entre Supabase e hospedagem.

Nenhum dado é apagado por esta migração.


## Atualização V10: entrada pelo login e sessão robusta

- `index.php` agora sempre direciona para `login.php`.
- O Dashboard do administrador passou para `dashboard.php`.
- Ao abrir o login, uma sessão local antiga é descartada para evitar redirecionamentos indevidos.
- O token JWT é renovado automaticamente com o refresh token quando necessário.
- Sessões vencidas, incompletas ou inválidas retornam automaticamente ao login.
- O perfil do usuário é validado antes da abertura de páginas protegidas.
- Não há alteração de banco de dados nesta versão.


## Atualização V11: correção de Token CSRF no login

Correções aplicadas:

- o login não usa mais CSRF, evitando falhas ao abrir o sistema em outro computador ou após cache do navegador;
- a tela de login recebe cabeçalhos `no-store/no-cache`;
- a sessão PHP usa cookie próprio `vivere_estoque_session`;
- cookie com `HttpOnly`, `SameSite=Lax` e `Secure` quando o site está em HTTPS;
- o ID da sessão é regenerado após login bem-sucedido;
- formulários internos continuam protegidos por CSRF;
- se uma sessão expirar durante um formulário interno, o usuário volta ao login com uma mensagem amigável, em vez de receber `Token CSRF inválido`.

Não há alteração de banco de dados nesta versão.
