-- Execute no SQL Editor do Supabase para atualizar um projeto já existente.

alter table public.solicitacoes_compra
    add column if not exists observacao text,
    add column if not exists status_atualizado_em timestamptz,
    add column if not exists status_atualizado_por text;

alter table public.solicitacoes_compra
    drop constraint if exists solicitacoes_compra_status_check;

update public.solicitacoes_compra
set status = 'solicitado'
where status not in ('solicitado', 'comprado', 'recusado');

alter table public.solicitacoes_compra
    add constraint solicitacoes_compra_status_check
    check (status in ('solicitado', 'comprado', 'recusado'));
