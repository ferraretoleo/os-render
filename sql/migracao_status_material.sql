-- Execute no SQL Editor do Supabase para adicionar
-- a situação Aprovado/Rejeitado aos materiais.

alter table public.materiais
    add column if not exists status_material text not null default 'aprovado';

alter table public.materiais
    drop constraint if exists materiais_status_material_check;

alter table public.materiais
    add constraint materiais_status_material_check
    check (status_material in ('aprovado', 'rejeitado'));
