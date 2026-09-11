-- ============================================================
-- SISTEMA DE CONTROLE DE ESTOQUE
-- Supabase / PostgreSQL
-- ============================================================

create extension if not exists "pgcrypto";

-- ---------------------------
-- TABELA: materiais
-- ---------------------------
create table if not exists public.materiais (
    id uuid primary key default gen_random_uuid(),
    nome text not null,
    categoria text not null,
    unidade_medida text not null check (char_length(trim(unidade_medida)) > 0),
    quantidade_atual numeric(14,3) not null default 0 check (quantidade_atual >= 0),
    quantidade_minima numeric(14,3) not null default 0 check (quantidade_minima >= 0),
    localizacao text,
    observacoes text,
    status_material text not null default 'aprovado'
        check (status_material in ('aprovado', 'rejeitado')),
    criado_em timestamptz not null default now(),
    atualizado_em timestamptz not null default now()
);

create index if not exists idx_materiais_nome on public.materiais(nome);
create index if not exists idx_materiais_categoria on public.materiais(categoria);

-- ---------------------------
-- TABELA: movimentacoes
-- ---------------------------
create table if not exists public.movimentacoes (
    id uuid primary key default gen_random_uuid(),
    material_id uuid not null references public.materiais(id) on delete restrict,
    tipo text not null check (tipo in ('entrada', 'saida')),
    quantidade numeric(14,3) not null check (quantidade > 0),
    destino_aplicacao text,
    responsavel text not null,
    data_movimentacao timestamptz not null default now()
);

create index if not exists idx_movimentacoes_material_id on public.movimentacoes(material_id);
create index if not exists idx_movimentacoes_data on public.movimentacoes(data_movimentacao desc);
create index if not exists idx_movimentacoes_tipo on public.movimentacoes(tipo);

-- ---------------------------
-- TABELA: solicitacoes_compra
-- ---------------------------
create table if not exists public.solicitacoes_compra (
    id uuid primary key default gen_random_uuid(),
    material_id uuid not null references public.materiais(id) on delete restrict,
    quantidade_solicitada numeric(14,3) not null check (quantidade_solicitada > 0),
    quantidade_estoque_no_momento numeric(14,3) not null check (quantidade_estoque_no_momento >= 0),
    solicitante text not null,
    status text not null default 'solicitado'
        check (status in ('solicitado', 'comprado', 'recusado')),
    data_solicitacao timestamptz not null default now(),
    email_enviado boolean not null default false,
    email_enviado_em timestamptz,
    email_erro text
);

create index if not exists idx_solicitacoes_material on public.solicitacoes_compra(material_id);
create index if not exists idx_solicitacoes_data on public.solicitacoes_compra(data_solicitacao desc);

-- ---------------------------
-- atualizado_em automático
-- ---------------------------
create or replace function public.set_updated_at()
returns trigger
language plpgsql
as $$
begin
    new.atualizado_em = now();
    return new;
end;
$$;

drop trigger if exists trg_materiais_updated_at on public.materiais;
create trigger trg_materiais_updated_at
before update on public.materiais
for each row execute function public.set_updated_at();

-- ---------------------------
-- Atualização automática de estoque
-- Regras:
-- entrada soma
-- saída subtrai
-- saída não permite saldo negativo
-- UPDATE bloqueado em movimentacoes para preservar auditoria
-- DELETE de movimentações bloqueado para preservar auditoria
-- ---------------------------
create or replace function public.aplicar_movimentacao_estoque()
returns trigger
language plpgsql
security definer
set search_path = public
as $$
declare
    saldo_atual numeric(14,3);
begin
    select quantidade_atual
      into saldo_atual
      from public.materiais
     where id = new.material_id
     for update;

    if not found then
        raise exception 'Material não encontrado.';
    end if;

    if new.tipo = 'entrada' then
        update public.materiais
           set quantidade_atual = quantidade_atual + new.quantidade
         where id = new.material_id;

    elsif new.tipo = 'saida' then
        if saldo_atual < new.quantidade then
            raise exception 'Saldo insuficiente. Saldo atual: %, saída solicitada: %',
                saldo_atual, new.quantidade;
        end if;

        update public.materiais
           set quantidade_atual = quantidade_atual - new.quantidade
         where id = new.material_id;
    else
        raise exception 'Tipo de movimentação inválido.';
    end if;

    return new;
end;
$$;

drop trigger if exists trg_aplicar_movimentacao_estoque on public.movimentacoes;
create trigger trg_aplicar_movimentacao_estoque
after insert on public.movimentacoes
for each row execute function public.aplicar_movimentacao_estoque();

create or replace function public.bloquear_alteracao_movimentacao()
returns trigger
language plpgsql
as $$
begin
    raise exception 'Movimentações são registros de auditoria e não podem ser alteradas ou excluídas.';
end;
$$;

drop trigger if exists trg_bloquear_update_movimentacao on public.movimentacoes;
create trigger trg_bloquear_update_movimentacao
before update on public.movimentacoes
for each row execute function public.bloquear_alteracao_movimentacao();

drop trigger if exists trg_bloquear_delete_movimentacao on public.movimentacoes;
create trigger trg_bloquear_delete_movimentacao
before delete on public.movimentacoes
for each row execute function public.bloquear_alteracao_movimentacao();

-- ---------------------------
-- VIEW para dashboard
-- ---------------------------
create or replace view public.vw_estoque_status as
select
    m.*,
    case
        when m.quantidade_atual <= m.quantidade_minima then 'critico'
        else 'normal'
    end as status_estoque
from public.materiais m;

-- ============================================================
-- RLS
-- ============================================================

alter table public.materiais enable row level security;
alter table public.movimentacoes enable row level security;
alter table public.solicitacoes_compra enable row level security;

-- Não criamos políticas abertas para anon.
-- O PHP autentica o usuário pelo Supabase Auth e envia o JWT nas chamadas REST.
-- Dessa forma, as políticas abaixo são aplicadas à role authenticated.
-- A service_role deve ser reservada a rotinas administrativas no servidor.

drop policy if exists "authenticated_read_materiais" on public.materiais;
create policy "authenticated_read_materiais"
on public.materiais
for select
to authenticated
using (true);

drop policy if exists "authenticated_insert_materiais" on public.materiais;
create policy "authenticated_insert_materiais"
on public.materiais
for insert
to authenticated
with check (true);

drop policy if exists "authenticated_update_materiais" on public.materiais;
create policy "authenticated_update_materiais"
on public.materiais
for update
to authenticated
using (true)
with check (true);

drop policy if exists "authenticated_read_movimentacoes" on public.movimentacoes;
create policy "authenticated_read_movimentacoes"
on public.movimentacoes
for select
to authenticated
using (true);

drop policy if exists "authenticated_insert_movimentacoes" on public.movimentacoes;
create policy "authenticated_insert_movimentacoes"
on public.movimentacoes
for insert
to authenticated
with check (true);

drop policy if exists "authenticated_read_solicitacoes" on public.solicitacoes_compra;
create policy "authenticated_read_solicitacoes"
on public.solicitacoes_compra
for select
to authenticated
using (true);

drop policy if exists "authenticated_insert_solicitacoes" on public.solicitacoes_compra;
create policy "authenticated_insert_solicitacoes"
on public.solicitacoes_compra
for insert
to authenticated
with check (true);

drop policy if exists "authenticated_update_solicitacoes" on public.solicitacoes_compra;
create policy "authenticated_update_solicitacoes"
on public.solicitacoes_compra
for update
to authenticated
using (true)
with check (true);

-- Permissões para usuários autenticados via Supabase Auth.
grant select, insert, update on public.materiais to authenticated;
grant select, insert on public.movimentacoes to authenticated;
grant select, insert, update on public.solicitacoes_compra to authenticated;
grant select on public.vw_estoque_status to authenticated;

-- A role anon permanece sem políticas de acesso às tabelas.
-- A service_role, se usada em alguma rotina administrativa no servidor, ignora RLS.


-- ============================================================
-- MIGRAÇÃO: STATUS DE COMPRA + OBSERVAÇÃO
-- Execute este bloco se a tabela solicitacoes_compra já existir.
-- ============================================================

alter table public.solicitacoes_compra
    add column if not exists observacao text,
    add column if not exists status_atualizado_em timestamptz,
    add column if not exists status_atualizado_por text;

alter table public.solicitacoes_compra
    drop constraint if exists solicitacoes_compra_status_check;

alter table public.solicitacoes_compra
    add constraint solicitacoes_compra_status_check
    check (status in ('solicitado', 'comprado', 'recusado'));

-- Caso existam registros antigos em status não utilizado nesta versão:
update public.solicitacoes_compra
set status = 'solicitado'
where status not in ('solicitado', 'comprado', 'recusado');


-- ============================================================
-- MIGRAÇÃO: STATUS DO MATERIAL
-- ============================================================

alter table public.materiais
    add column if not exists status_material text not null default 'aprovado';

alter table public.materiais
    drop constraint if exists materiais_status_material_check;

alter table public.materiais
    add constraint materiais_status_material_check
    check (status_material in ('aprovado', 'rejeitado'));
