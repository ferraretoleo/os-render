# Controle de Orçamentos Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Adicionar um módulo isolado para cadastrar, consultar, baixar, aprovar, reprovar e excluir orçamentos com documentos privados.

**Architecture:** Os dados ficam em duas novas tabelas protegidas por RLS e os arquivos em um bucket privado do Supabase Storage. Páginas PHP separadas usam a sessão e os níveis de acesso já existentes, com alterações mínimas no menu e no painel.

**Tech Stack:** PHP 8, Supabase Auth, PostgreSQL/PostgREST, Supabase Storage, Tailwind CSS e Render.

**Spec:** `docs/superpowers/specs/2026-11-10-controle-orcamentos-design.md`

## Global Constraints

- Não alterar o comportamento dos módulos existentes.
- Aceitar PDF, DOC, DOCX, JPG, JPEG e PNG, com limite de 15 MB.
- Manter o bucket `orcamentos` privado.
- Permitir decisão e exclusão somente para `admin`.
- Usar o padrão visual e a autenticação atuais.

## Review Focus

- Arquivo com extensão permitida e MIME falso deve ser recusado.
- Falha entre upload e insert não pode deixar arquivo órfão.
- Usuário `executante` não pode chamar diretamente endpoints administrativos.
- Reenvio de formulário de decisão não pode duplicar o histórico.
- Nome de arquivo malicioso não pode controlar o caminho salvo nem o cabeçalho de download.

---

### Task 1: Banco, bucket e políticas

**Files:**
- Create: `sql/migracao_orcamentos.sql`
- Create: `tests/orcamentos_schema_test.sql`

**Interfaces:**
- Consumes: tabela `perfis` e função `usuario_e_admin()` da migração de níveis.
- Produces: tabelas `orcamentos`, `orcamento_historico`, índices, RLS e bucket privado `orcamentos`.

- [ ] **Step 1: Escrever o teste SQL das tabelas, restrições, políticas e bucket.**
- [ ] **Step 2: Executar o teste e confirmar falha porque as estruturas ainda não existem.**
- [ ] **Step 3: Criar a migração idempotente com tabelas, índices, bucket e políticas.**
- [ ] **Step 4: Executar migração e teste em uma base de validação e confirmar aprovação.**
- [ ] **Step 5: Commitar a migração e o teste.**

### Task 2: Cliente de Storage e validação de arquivo

**Files:**
- Modify: `src/SupabaseClient.php`
- Create: `src/OrcamentoArquivo.php`
- Create: `tests/orcamento_arquivo_test.php`

**Interfaces:**
- Consumes: URL, chave anon e JWT já configurados no cliente.
- Produces: `storageUpload(string $bucket, string $path, string $bytes, string $mime): void`, `storageDownload(string $bucket, string $path): array`, `storageDelete(string $bucket, string $path): void` e `OrcamentoArquivo::validar(array $file): array`.

- [ ] **Step 1: Criar testes para formatos válidos, MIME falso, tamanho, erro de upload e nome de caminho seguro.**
- [ ] **Step 2: Executar os testes e confirmar falha pela ausência das interfaces.**
- [ ] **Step 3: Implementar validação e operações autenticadas do Storage.**
- [ ] **Step 4: Executar os testes e confirmar aprovação.**
- [ ] **Step 5: Commitar cliente, validador e testes.**

### Task 3: Cadastro e listagem

**Files:**
- Create: `orcamentos.php`
- Create: `orcamento_form.php`
- Create: `tests/orcamento_fluxo_test.php`

**Interfaces:**
- Consumes: `SupabaseClient`, `OrcamentoArquivo`, `requireLogin()` e layout atual.
- Produces: listagem filtrável e cadastro com upload compensado em caso de falha no banco.

- [ ] **Step 1: Criar testes do filtro, autorização, validação do formulário e compensação de upload.**
- [ ] **Step 2: Executar os testes e confirmar falha porque as páginas ainda não existem.**
- [ ] **Step 3: Implementar a listagem e o cadastro usando os padrões do projeto.**
- [ ] **Step 4: Executar testes e análise de sintaxe PHP.**
- [ ] **Step 5: Commitar páginas e testes.**

### Task 4: Detalhe, download e decisão

**Files:**
- Create: `orcamento_detalhe.php`
- Create: `orcamento_arquivo.php`
- Create: `orcamento_decisao.php`
- Modify: `src/helpers.php`
- Modify: `tests/orcamento_fluxo_test.php`

**Interfaces:**
- Consumes: tabelas da Task 1 e métodos de Storage da Task 2.
- Produces: detalhe com histórico, download autenticado e decisão administrativa protegida por POST e CSRF.

- [ ] **Step 1: Criar testes de download autenticado, cabeçalhos seguros, CSRF, permissão e histórico único.**
- [ ] **Step 2: Executar os testes e confirmar falha.**
- [ ] **Step 3: Implementar helpers CSRF, detalhe, download e decisão.**
- [ ] **Step 4: Executar testes e análise de sintaxe PHP.**
- [ ] **Step 5: Commitar páginas, helper e testes.**

### Task 5: Exclusão administrativa

**Files:**
- Create: `orcamento_excluir.php`
- Modify: `tests/orcamento_fluxo_test.php`

**Interfaces:**
- Consumes: `requireAdmin()`, CSRF e `storageDelete()`.
- Produces: exclusão por POST que remove primeiro o arquivo e depois os dados.

- [ ] **Step 1: Criar testes de permissão, CSRF e falha de remoção do arquivo.**
- [ ] **Step 2: Executar os testes e confirmar falha.**
- [ ] **Step 3: Implementar exclusão administrativa com tratamento de falhas.**
- [ ] **Step 4: Executar testes e análise de sintaxe PHP.**
- [ ] **Step 5: Commitar endpoint e testes.**

### Task 6: Integração visual mínima

**Files:**
- Modify: `_layout_top.php`
- Modify: `dashboard.php`
- Modify: `README_RENDER.md`

**Interfaces:**
- Consumes: `orcamentos.php` e consulta de pendências.
- Produces: link no menu, cartão de pendências e instruções de implantação.

- [ ] **Step 1: Criar verificações para link por nível e contagem de pendências.**
- [ ] **Step 2: Confirmar que as verificações falham antes da integração.**
- [ ] **Step 3: Acrescentar somente o link e o cartão, sem mudar os itens atuais.**
- [ ] **Step 4: Rodar todos os testes, `php -l` em todos os PHP e revisar o diff.**
- [ ] **Step 5: Commitar integração e documentação.**

### Task 7: Validação final para Render e Supabase

**Files:**
- Modify: `README_RENDER.md`

**Interfaces:**
- Consumes: módulo completo e migração.
- Produces: sequência validada de implantação e rollback.

- [ ] **Step 1: Executar a migração em ambiente de validação e confirmar RLS e bucket privado.**
- [ ] **Step 2: Testar cadastro, download, aprovação, reprovação e exclusão com contas `admin` e `executante`.**
- [ ] **Step 3: Confirmar que materiais, movimentações, compras, conferência e usuários mantêm o comportamento anterior.**
- [ ] **Step 4: Documentar implantação, teste de fumaça e rollback sem remover dados atuais.**
- [ ] **Step 5: Commitar a documentação final.**
