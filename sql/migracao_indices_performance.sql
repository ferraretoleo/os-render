-- ============================================================
-- CONTROLE DE ESTOQUE - ÍNDICES DE PERFORMANCE
-- V9
--
-- Pode ser executado sem apagar dados.
-- CREATE INDEX IF NOT EXISTS evita duplicidade.
-- ============================================================

-- Atualiza estatísticas antes/depois dos índices.
analyze public.materiais;
analyze public.movimentacoes;
analyze public.solicitacoes_compra;

-- ============================================================
-- MATERIAIS
-- Conferência:
--   ORDER BY categoria, nome
-- Dashboard:
--   filtro por categoria + ORDER BY nome
-- ============================================================

create index if not exists idx_materiais_categoria_nome
    on public.materiais (categoria, nome);

create index if not exists idx_materiais_nome
    on public.materiais (nome);

-- Índice parcial para consultas de materiais com categoria preenchida.
create index if not exists idx_materiais_categoria
    on public.materiais (categoria)
    where categoria is not null;


-- ============================================================
-- MOVIMENTAÇÕES
-- Histórico:
--   ORDER BY data_movimentacao DESC
--   filtro por tipo
-- ============================================================

create index if not exists idx_movimentacoes_data_desc
    on public.movimentacoes (data_movimentacao desc);

create index if not exists idx_movimentacoes_tipo_data_desc
    on public.movimentacoes (tipo, data_movimentacao desc);

create index if not exists idx_movimentacoes_material_data_desc
    on public.movimentacoes (material_id, data_movimentacao desc);


-- ============================================================
-- SOLICITAÇÕES DE COMPRA
-- Relatório:
--   filtro por status
--   filtro por período
--   ORDER BY data_solicitacao DESC
-- Dashboard:
--   status = solicitado
-- ============================================================

create index if not exists idx_solicitacoes_data_desc
    on public.solicitacoes_compra (data_solicitacao desc);

create index if not exists idx_solicitacoes_status_data_desc
    on public.solicitacoes_compra (status, data_solicitacao desc);

create index if not exists idx_solicitacoes_material_data_desc
    on public.solicitacoes_compra (material_id, data_solicitacao desc);

create index if not exists idx_solicitacoes_usuario_data_desc
    on public.solicitacoes_compra (solicitado_por, data_solicitacao desc);

-- Índice menor e mais eficiente para o painel de solicitações pendentes.
create index if not exists idx_solicitacoes_pendentes_data
    on public.solicitacoes_compra (data_solicitacao desc)
    where status = 'solicitado';


-- ============================================================
-- ESTATÍSTICAS
-- ============================================================

analyze public.materiais;
analyze public.movimentacoes;
analyze public.solicitacoes_compra;


-- ============================================================
-- CONSULTA OPCIONAL PARA CONFERIR OS ÍNDICES CRIADOS
-- ============================================================

select
    schemaname,
    tablename,
    indexname,
    indexdef
from pg_indexes
where schemaname = 'public'
  and tablename in (
      'materiais',
      'movimentacoes',
      'solicitacoes_compra'
  )
order by tablename, indexname;
