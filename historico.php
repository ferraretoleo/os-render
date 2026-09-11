<?php

require_once __DIR__ . '/src/bootstrap.php';

$query = [
    'select' => 'id,tipo,quantidade,destino_aplicacao,responsavel,data_movimentacao,materiais(nome,unidade_medida)',
    'order' => 'data_movimentacao.desc',
    'limit' => 500,
];

$tipo = trim((string)($_GET['tipo'] ?? ''));
if ($tipo !== '') {
    $query['tipo'] = 'eq.' . $tipo;
}

$rows = $supabase->select('movimentacoes', $query);

$pageTitle = 'Histórico de movimentações';
require __DIR__ . '/_layout_top.php';
?>

<div class="rounded-2xl bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold">Auditoria de movimentações</h2>
            <p class="text-sm text-slate-500">Últimos 500 registros.</p>
        </div>

        <form method="get" class="flex gap-2">
            <select name="tipo" class="rounded-lg border border-slate-300 px-3 py-2">
                <option value="">Todos</option>
                <option value="entrada" <?= $tipo === 'entrada' ? 'selected' : '' ?>>Entrada</option>
                <option value="saida" <?= $tipo === 'saida' ? 'selected' : '' ?>>Saída</option>
            </select>
            <button class="rounded-lg bg-slate-900 px-4 py-2 text-white">Filtrar</button>
        </form>
    </div>

    <div class="mt-5 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
            <tr>
                <th class="px-4 py-3">Data</th>
                <th class="px-4 py-3">Material</th>
                <th class="px-4 py-3">Tipo</th>
                <th class="px-4 py-3">Quantidade</th>
                <th class="px-4 py-3">Destino</th>
                <th class="px-4 py-3">Responsável</th>
            </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td class="px-4 py-3"><?= e(date('d/m/Y H:i', strtotime($r['data_movimentacao']))) ?></td>
                    <td class="px-4 py-3 font-medium"><?= e($r['materiais']['nome'] ?? '') ?></td>
                    <td class="px-4 py-3">
                        <span class="rounded-full px-2 py-1 text-xs font-semibold <?= $r['tipo'] === 'entrada' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700' ?>">
                            <?= e(ucfirst($r['tipo'])) ?>
                        </span>
                    </td>
                    <td class="px-4 py-3"><?= e((string)$r['quantidade']) ?> <?= e($r['materiais']['unidade_medida'] ?? '') ?></td>
                    <td class="px-4 py-3"><?= e($r['destino_aplicacao'] ?? '') ?></td>
                    <td class="px-4 py-3"><?= e($r['responsavel'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$rows): ?>
                <tr><td colspan="6" class="px-4 py-8 text-center text-slate-500">Nenhuma movimentação encontrada.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
