# Controle de orçamentos

## Objetivo

Adicionar ao Controle de Estoque um módulo independente para cadastrar orçamentos de manutenção e obras, guardar o documento original e controlar a aprovação do síndico.

## Limites da mudança

- Não alterar regras, telas ou tabelas dos módulos de materiais, movimentações, compras, conferência e usuários.
- Acrescentar somente o link `Orçamentos` na navegação e um indicador de orçamentos pendentes no painel.
- Manter PHP, Supabase Auth, Supabase PostgreSQL, Supabase Storage e a hospedagem atual no Render.
- Manter os níveis de acesso atuais: `admin` e `executante`.

## Modelo funcional

Cada cadastro representa um orçamento e um arquivo. O orçamento terá:

- título;
- descrição;
- tipo: `manutencao`, `obra` ou `outro`;
- fornecedor;
- valor;
- data do orçamento;
- validade opcional;
- documento anexado;
- status: `pendente`, `aprovado` ou `reprovado`;
- observação da decisão;
- usuário e data do cadastro;
- administrador, data e hora da decisão.

Usuários `admin` e `executante` poderão consultar e cadastrar. Somente `admin` poderá aprovar, reprovar ou excluir. Uma decisão ficará registrada em histórico, inclusive quando o status for alterado novamente.

## Arquivos

Os documentos ficarão no bucket privado `orcamentos` do Supabase Storage. O banco guardará apenas metadados e o caminho do objeto.

Formatos aceitos: PDF, DOC, DOCX, JPG, JPEG e PNG. O limite inicial será 15 MB por arquivo. A validação usará extensão, MIME detectado no servidor e tamanho. O nome físico será gerado pela aplicação e não reutilizará o nome enviado pelo usuário.

O download será feito por uma rota PHP autenticada. A rota verificará a sessão e solicitará o objeto ao Storage usando o JWT do usuário, sem expor o bucket publicamente.

## Banco de dados

Novas tabelas independentes:

- `orcamentos`: dados principais, metadados do arquivo e decisão atual;
- `orcamento_historico`: registro de criação e alterações de status.

As tabelas terão RLS. Usuários autenticados poderão consultar e inserir. Atualização de decisão e exclusão serão permitidas somente para quem tiver perfil `admin`.

## Telas

- `orcamentos.php`: lista com filtros de status, tipo, fornecedor e período.
- `orcamento_form.php`: cadastro e envio do documento.
- `orcamento_detalhe.php`: dados, visualização do histórico e ações administrativas.
- `orcamento_arquivo.php`: download autenticado do documento.
- `orcamento_decisao.php`: processamento POST de aprovação ou reprovação.
- `orcamento_excluir.php`: exclusão administrativa do registro e do arquivo.

O visual seguirá o padrão atual, usando os mesmos layouts e Tailwind já carregado pela aplicação.

## Tratamento de falhas

- Se o upload falhar, nenhum orçamento será gravado.
- Se a gravação no banco falhar após o upload, a aplicação tentará remover o objeto enviado.
- Se a exclusão do arquivo falhar, o registro não será apagado e a tela mostrará o erro.
- Ações de decisão aceitarão somente POST e validarão um token CSRF armazenado na sessão.
- Mensagens técnicas e credenciais não serão exibidas ao usuário.

## Critérios de aceite

- Os módulos atuais continuam funcionando sem mudança de comportamento.
- Um usuário autenticado consegue cadastrar e baixar um orçamento válido.
- Arquivos fora da lista ou acima de 15 MB são recusados.
- Um executante não consegue aprovar, reprovar ou excluir.
- Um administrador consegue aprovar ou reprovar e a decisão aparece no histórico com data e hora.
- O bucket não permite acesso público direto.
- O painel informa a quantidade de orçamentos pendentes.
