-- ============================================================
-- CONTROLE DE ACESSO: ADMIN / EXECUTANTE
-- Execute este arquivo no SQL Editor do Supabase.
-- ============================================================

-- 1. Tabela de perfis vinculada ao Supabase Auth.
create table if not exists public.perfis (
    user_id uuid primary key references auth.users(id) on delete cascade,
    email text not null,
    nome text,
    nivel text not null default 'executante'
        check (nivel in ('admin', 'executante')),
    criado_em timestamptz not null default now(),
    atualizado_em timestamptz not null default now()
);

alter table public.perfis enable row level security;

-- 2. Cria automaticamente um perfil Executante quando um novo usuário
-- é criado em Authentication > Users.
create or replace function public.criar_perfil_novo_usuario()
returns trigger
language plpgsql
security definer
set search_path = public
as $$
begin
    insert into public.perfis (user_id, email, nome, nivel)
    values (
        new.id,
        coalesce(new.email, ''),
        coalesce(new.raw_user_meta_data->>'name', ''),
        'executante'
    )
    on conflict (user_id) do nothing;

    return new;
end;
$$;

drop trigger if exists trg_criar_perfil_usuario on auth.users;
create trigger trg_criar_perfil_usuario
after insert on auth.users
for each row execute function public.criar_perfil_novo_usuario();

-- 3. Cria perfis para usuários que já existem.
insert into public.perfis (user_id, email, nome, nivel)
select
    u.id,
    coalesce(u.email, ''),
    coalesce(u.raw_user_meta_data->>'name', ''),
    'executante'
from auth.users u
on conflict (user_id) do nothing;

-- 4. Função segura para verificar se o usuário atual é Admin.
create or replace function public.usuario_e_admin()
returns boolean
language sql
stable
security definer
set search_path = public
as $$
    select exists (
        select 1
        from public.perfis p
        where p.user_id = auth.uid()
          and p.nivel = 'admin'
    );
$$;

-- 5. RLS da tabela perfis.
drop policy if exists "perfil_usuario_le_proprio" on public.perfis;
create policy "perfil_usuario_le_proprio"
on public.perfis
for select
to authenticated
using (user_id = auth.uid() or public.usuario_e_admin());

drop policy if exists "admin_atualiza_perfis" on public.perfis;
create policy "admin_atualiza_perfis"
on public.perfis
for update
to authenticated
using (public.usuario_e_admin())
with check (public.usuario_e_admin());

grant select, update on public.perfis to authenticated;

-- 6. Vincula solicitações ao usuário que criou.
alter table public.solicitacoes_compra
    add column if not exists solicitado_por uuid references auth.users(id) on delete set null;

create index if not exists idx_solicitacoes_solicitado_por
    on public.solicitacoes_compra(solicitado_por);

-- 7. Revoga as políticas amplas antigas e cria as novas.
-- MATERIAIS
drop policy if exists "authenticated_read_materiais" on public.materiais;
drop policy if exists "authenticated_insert_materiais" on public.materiais;
drop policy if exists "authenticated_update_materiais" on public.materiais;

create policy "usuarios_leem_materiais"
on public.materiais
for select
to authenticated
using (true);

create policy "admin_insere_materiais"
on public.materiais
for insert
to authenticated
with check (public.usuario_e_admin());

create policy "admin_atualiza_materiais"
on public.materiais
for update
to authenticated
using (public.usuario_e_admin())
with check (public.usuario_e_admin());

-- MOVIMENTAÇÕES
drop policy if exists "authenticated_read_movimentacoes" on public.movimentacoes;
drop policy if exists "authenticated_insert_movimentacoes" on public.movimentacoes;

create policy "usuarios_leem_movimentacoes"
on public.movimentacoes
for select
to authenticated
using (true);

create policy "usuarios_inserem_movimentacoes"
on public.movimentacoes
for insert
to authenticated
with check (true);

-- SOLICITAÇÕES DE COMPRA
drop policy if exists "authenticated_read_solicitacoes" on public.solicitacoes_compra;
drop policy if exists "authenticated_insert_solicitacoes" on public.solicitacoes_compra;
drop policy if exists "authenticated_update_solicitacoes" on public.solicitacoes_compra;

create policy "usuario_le_proprias_solicitacoes_ou_admin"
on public.solicitacoes_compra
for select
to authenticated
using (
    solicitado_por = auth.uid()
    or public.usuario_e_admin()
);

create policy "usuarios_criam_solicitacoes"
on public.solicitacoes_compra
for insert
to authenticated
with check (
    solicitado_por = auth.uid()
);

create policy "admin_atualiza_solicitacoes"
on public.solicitacoes_compra
for update
to authenticated
using (public.usuario_e_admin())
with check (public.usuario_e_admin());

-- 8. Grants continuam necessários para PostgREST.
grant select, insert, update on public.materiais to authenticated;
grant select, insert on public.movimentacoes to authenticated;
grant select, insert, update on public.solicitacoes_compra to authenticated;

-- ============================================================
-- IMPORTANTE: DEFINA O PRIMEIRO ADMIN
-- ============================================================
-- Após executar este script, todos os usuários existentes começam como Executante.
--
-- Troque o e-mail abaixo pelo seu usuário administrador e execute:
--
-- update public.perfis
-- set nivel = 'admin'
-- where email = 'SEU_EMAIL_DE_LOGIN';
--
-- Depois faça logout e login novamente no sistema.
