-- ============================================================
-- MÓDULO DE ORÇAMENTOS
-- Execute no SQL Editor do Supabase após migracao_niveis_acesso.sql.
-- ============================================================

begin;

do $validacao$
begin
    if to_regclass('public.perfis') is null then
        raise exception 'A tabela public.perfis não existe. Execute primeiro sql/migracao_niveis_acesso.sql';
    end if;

    if to_regprocedure('public.usuario_e_admin()') is null then
        raise exception 'A função public.usuario_e_admin() não existe. Execute primeiro sql/migracao_niveis_acesso.sql';
    end if;
end;
$validacao$;

create extension if not exists pgcrypto;

create table if not exists public.orcamentos (
    id uuid primary key default gen_random_uuid(),
    titulo text not null check (char_length(trim(titulo)) between 3 and 150),
    descricao text,
    tipo text not null check (tipo in ('manutencao', 'obra', 'outro')),
    fornecedor text not null check (char_length(trim(fornecedor)) between 2 and 150),
    valor numeric(14,2) not null check (valor > 0),
    data_orcamento date not null,
    validade date,
    arquivo_nome_original text not null,
    arquivo_caminho text not null unique,
    arquivo_mime text not null,
    arquivo_tamanho bigint not null check (arquivo_tamanho > 0 and arquivo_tamanho <= 15728640),
    status text not null default 'pendente'
        check (status in ('pendente', 'aprovado', 'reprovado')),
    observacao_decisao text,
    criado_por uuid not null references auth.users(id) on delete restrict default auth.uid(),
    criado_em timestamptz not null default now(),
    atualizado_em timestamptz not null default now(),
    decidido_por uuid references auth.users(id) on delete set null,
    decidido_em timestamptz,
    constraint orcamentos_validade_check
        check (validade is null or validade >= data_orcamento),
    constraint orcamentos_decisao_check check (
        (status = 'pendente' and decidido_por is null and decidido_em is null) or
        (status in ('aprovado', 'reprovado') and decidido_por is not null and decidido_em is not null)
    )
);

create table if not exists public.orcamento_historico (
    id bigint generated always as identity primary key,
    orcamento_id uuid not null references public.orcamentos(id) on delete cascade,
    status_anterior text check (status_anterior is null or status_anterior in ('pendente', 'aprovado', 'reprovado')),
    status_novo text not null check (status_novo in ('pendente', 'aprovado', 'reprovado')),
    observacao text,
    alterado_por uuid references auth.users(id) on delete set null,
    alterado_em timestamptz not null default now()
);

create index if not exists idx_orcamentos_status on public.orcamentos(status);
create index if not exists idx_orcamentos_tipo on public.orcamentos(tipo);
create index if not exists idx_orcamentos_fornecedor on public.orcamentos(fornecedor);
create index if not exists idx_orcamentos_data on public.orcamentos(data_orcamento desc);
create index if not exists idx_orcamento_historico_orcamento
    on public.orcamento_historico(orcamento_id, alterado_em desc);

create or replace function public.registrar_historico_orcamento()
returns trigger
language plpgsql
security definer
set search_path = public
as $$
begin
    if tg_op = 'INSERT' then
        insert into public.orcamento_historico (
            orcamento_id, status_anterior, status_novo, observacao, alterado_por
        ) values (
            new.id, null, new.status, 'Orçamento cadastrado.', new.criado_por
        );
    elsif old.status is distinct from new.status then
        insert into public.orcamento_historico (
            orcamento_id, status_anterior, status_novo, observacao, alterado_por
        ) values (
            new.id, old.status, new.status, new.observacao_decisao, auth.uid()
        );
    end if;

    return new;
end;
$$;

drop trigger if exists trg_registrar_historico_orcamento on public.orcamentos;
create trigger trg_registrar_historico_orcamento
after insert or update of status on public.orcamentos
for each row execute function public.registrar_historico_orcamento();

alter table public.orcamentos enable row level security;
alter table public.orcamento_historico enable row level security;

drop policy if exists "usuarios_leem_orcamentos" on public.orcamentos;
create policy "usuarios_leem_orcamentos"
on public.orcamentos for select to authenticated using (true);

drop policy if exists "usuarios_criam_orcamentos" on public.orcamentos;
create policy "usuarios_criam_orcamentos"
on public.orcamentos for insert to authenticated
with check (criado_por = auth.uid() and status = 'pendente');

drop policy if exists "admin_atualiza_orcamentos" on public.orcamentos;
create policy "admin_atualiza_orcamentos"
on public.orcamentos for update to authenticated
using (public.usuario_e_admin())
with check (public.usuario_e_admin());

drop policy if exists "admin_exclui_orcamentos" on public.orcamentos;
create policy "admin_exclui_orcamentos"
on public.orcamentos for delete to authenticated
using (public.usuario_e_admin());

drop policy if exists "usuarios_leem_historico_orcamentos" on public.orcamento_historico;
create policy "usuarios_leem_historico_orcamentos"
on public.orcamento_historico for select to authenticated using (true);

grant select, insert, update, delete on public.orcamentos to authenticated;
grant select on public.orcamento_historico to authenticated;

insert into storage.buckets (id, name, public, file_size_limit, allowed_mime_types)
values ('orcamentos', 'orcamentos', false, 15728640, array[
    'application/pdf',
    'application/msword',
    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    'image/jpeg',
    'image/png'
])
on conflict (id) do update set
    public = excluded.public,
    file_size_limit = excluded.file_size_limit,
    allowed_mime_types = excluded.allowed_mime_types;

drop policy if exists "usuarios_enviam_arquivos_orcamentos" on storage.objects;
create policy "usuarios_enviam_arquivos_orcamentos"
on storage.objects for insert to authenticated
with check (bucket_id = 'orcamentos');

drop policy if exists "usuarios_baixam_arquivos_orcamentos" on storage.objects;
create policy "usuarios_baixam_arquivos_orcamentos"
on storage.objects for select to authenticated
using (bucket_id = 'orcamentos');

drop policy if exists "admin_exclui_arquivos_orcamentos" on storage.objects;
create policy "admin_exclui_arquivos_orcamentos"
on storage.objects for delete to authenticated
using (
    bucket_id = 'orcamentos'
    and (
        public.usuario_e_admin() or (
            owner_id = auth.uid()::text
            and not exists (
                select 1
                from public.orcamentos o
                where o.arquivo_caminho = storage.objects.name
            )
        )
    )
);

-- Confirmação rápida após executar:
-- select to_regclass('public.orcamentos'),
--        to_regclass('public.orcamento_historico');
-- select id, public, file_size_limit from storage.buckets where id = 'orcamentos';

commit;
