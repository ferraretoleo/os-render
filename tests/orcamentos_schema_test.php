<?php

declare(strict_types=1);

$migration = dirname(__DIR__) . '/sql/migracao_orcamentos.sql';

if (!is_file($migration)) {
    fwrite(STDERR, "FALHA: migração de orçamentos não encontrada.\n");
    exit(1);
}

$sql = file_get_contents($migration);
$required = [
    'create table if not exists public.orcamentos',
    'create table if not exists public.orcamento_historico',
    "'pendente', 'aprovado', 'reprovado'",
    "'manutencao', 'obra', 'outro'",
    "values ('orcamentos', 'orcamentos', false",
    'alter table public.orcamentos enable row level security',
    'alter table public.orcamento_historico enable row level security',
    'public.usuario_e_admin()',
    'storage.objects',
    'trg_registrar_historico_orcamento',
];

foreach ($required as $needle) {
    if (!str_contains(strtolower($sql), strtolower($needle))) {
        fwrite(STDERR, "FALHA: trecho ausente na migração: {$needle}\n");
        exit(1);
    }
}

echo "OK: estrutura SQL de orçamentos validada.\n";
