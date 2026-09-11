<?php

require_once __DIR__ . '/src/bootstrap.php';

requireAdmin();

$dataInicio = trim((string)($_GET['data_inicio'] ?? ''));
$dataFim = trim((string)($_GET['data_fim'] ?? ''));
$status = trim((string)($_GET['status'] ?? ''));

$query = [
    'select' => 'id,quantidade_solicitada,quantidade_estoque_no_momento,solicitante,status,observacao,status_atualizado_em,status_atualizado_por,data_solicitacao,materiais(nome,unidade_medida,categoria)',
    'order' => 'data_solicitacao.desc',
    'limit' => 1000,
];

if ($status !== '') {
    $query['status'] = 'eq.' . $status;
}

$dateFilters = [];

if ($dataInicio !== '') {
    $dateFilters[] = 'data_solicitacao.gte.' . $dataInicio . 'T00:00:00-03:00';
}

if ($dataFim !== '') {
    $nextDay = date('Y-m-d', strtotime($dataFim . ' +1 day'));
    $dateFilters[] = 'data_solicitacao.lt.' . $nextDay . 'T00:00:00-03:00';
}

if ($dateFilters) {
    $query['and'] = '(' . implode(',', $dateFilters) . ')';
}

$rows = $supabase->select('solicitacoes_compra', $query);

$pageTitle = 'Relatório de compras';
require __DIR__ . '/_layout_top.php';
?>

<style>
@media print {
    header,
    .no-print {
        display: none !important;
    }

    body { background: #fff !important; }
    main { max-width: none !important; padding: 0 !important; }
    .print-card { box-shadow: none !important; border: 0 !important; }
    table { font-size: 10px !important; }

    @page {
        size: A4 landscape;
        margin: 8mm;
    }
}
</style>

<div class="print-card rounded-2xl bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold">Relatório de Solicitações de Compra</h2>
            <p class="text-sm text-slate-500">Residencial Vivere Palhano</p>
            <p class="mt-1 text-xs text-slate-400">Emitido em <?= e(date('d/m/Y H:i')) ?></p>
        </div>

        <button type="button" onclick="window.print()"
                class="no-print rounded-lg bg-slate-900 px-5 py-2.5 font-semibold text-white hover:bg-slate-800">
            Imprimir
        </button>
    </div>

    <form method="get" class="no-print mt-6 grid gap-3 rounded-xl bg-slate-50 p-4 md:grid-cols-5">
        <label>
            <span class="mb-1 block text-xs font-medium">Data inicial</span>
            <input type="date" name="data_inicio" value="<?= e($dataInicio) ?>"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>

        <label>
            <span class="mb-1 block text-xs font-medium">Data final</span>
            <input type="date" name="data_fim" value="<?= e($dataFim) ?>"
                   class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>

        <label>
            <span class="mb-1 block text-xs font-medium">Status</span>
            <select name="status" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                <option value="">Todos</option>
                <?php foreach (['solicitado','comprado','recusado'] as $st): ?>
                    <option value="<?= e($st) ?>" <?= $status === $st ? 'selected' : '' ?>>
                        <?= e(ucfirst($st)) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <div class="flex items-end">
            <button class="w-full rounded-lg bg-blue-600 px-4 py-2 text-white hover:bg-blue-500">Filtrar</button>
        </div>

        <div class="flex items-end">
            <a href="relatorio_pedidos.php"
               class="w-full rounded-lg border border-slate-300 px-4 py-2 text-center hover:bg-white">
                Limpar
            </a>
        </div>
    </form>

    <div class="mt-6 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-100 text-left text-slate-700">
                <tr>
                    <th class="px-3 py-3">Data</th>
                    <th class="px-3 py-3">Produto</th>
                    <th class="px-3 py-3">Estoque</th>
                    <th class="px-3 py-3">Solicitado</th>
                    <th class="px-3 py-3">Solicitante</th>
                    <th class="px-3 py-3">Status</th>
                    <th class="px-3 py-3">OBS / Motivo</th>
                    <th class="no-print px-3 py-3">Ações</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-200">
            <?php foreach ($rows as $row): ?>
                <?php
                    $material = $row['materiais'] ?? [];
                    $unit = $material['unidade_medida'] ?? '';
                    $statusAtual = $row['status'] ?? 'solicitado';

                    $statusClass = match ($statusAtual) {
                        'comprado' => 'bg-emerald-100 text-emerald-700',
                        'recusado' => 'bg-red-100 text-red-700',
                        default => 'bg-amber-100 text-amber-700',
                    };
                ?>
                <tr class="align-top">
                    <td class="px-3 py-3 whitespace-nowrap">
                        <?= e(date('d/m/Y H:i', strtotime($row['data_solicitacao']))) ?>
                    </td>

                    <td class="px-3 py-3">
                        <div class="font-medium"><?= e($material['nome'] ?? '') ?></div>
                        <div class="text-xs text-slate-500"><?= e($material['categoria'] ?? '') ?></div>
                    </td>

                    <td class="px-3 py-3">
                        <?= e((string)$row['quantidade_estoque_no_momento']) ?> <?= e($unit) ?>
                    </td>

                    <td class="px-3 py-3 font-semibold">
                        <?= e((string)$row['quantidade_solicitada']) ?> <?= e($unit) ?>
                    </td>

                    <td class="px-3 py-3"><?= e($row['solicitante']) ?></td>

                    <td class="px-3 py-3">
                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusClass ?>">
                            <?= e(ucfirst($statusAtual)) ?>
                        </span>

                        <?php if (!empty($row['status_atualizado_em'])): ?>
                            <div class="mt-2 text-xs text-slate-400">
                                <?= e(date('d/m/Y H:i', strtotime($row['status_atualizado_em']))) ?>
                            </div>
                        <?php endif; ?>
                    </td>

                    <td class="px-3 py-3 min-w-48">
                        <?= nl2br(e($row['observacao'] ?? '')) ?>
                    </td>

                    <td class="no-print px-3 py-3 min-w-72">
                        <?php if ($statusAtual === 'solicitado'): ?>
                            <form method="post" action="atualizar_pedido.php" class="space-y-2">
                                <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                                <input type="hidden" name="id" value="<?= e($row['id']) ?>">

                                <textarea
                                    name="observacao"
                                    rows="2"
                                    placeholder="OBS. Para recusar, informe o motivo."
                                    class="w-full rounded-lg border border-slate-300 px-3 py-2 text-xs"
                                ></textarea>

                                <div class="flex gap-2">
                                    <button
                                        type="submit"
                                        name="acao"
                                        value="comprado"
                                        class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold text-white hover:bg-emerald-500"
                                    >
                                        Comprado
                                    </button>

                                    <button
                                        type="submit"
                                        name="acao"
                                        value="recusado"
                                        class="rounded-lg bg-red-600 px-3 py-2 text-xs font-semibold text-white hover:bg-red-500"
                                        onclick="return confirm('Confirma a recusa desta solicitação?');"
                                    >
                                        Recusado
                                    </button>
                                </div>
                            </form>
                        <?php else: ?>
                            <span class="text-xs text-slate-400">Finalizado</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (!$rows): ?>
                <tr>
                    <td colspan="8" class="px-4 py-10 text-center text-slate-500">
                        Nenhuma solicitação encontrada.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-5 text-right text-sm font-semibold">
        Total de solicitações: <?= count($rows) ?>
    </div>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
