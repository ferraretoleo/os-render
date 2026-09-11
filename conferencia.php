<?php

require_once __DIR__ . '/src/bootstrap.php';

requireAdmin();

$materials = $supabase->select('materiais', [
    'select' => 'id,nome,categoria,unidade_medida,quantidade_atual,quantidade_minima,localizacao',
    'order' => 'categoria.asc,nome.asc',
]);

$pageTitle = 'Conferência de Estoque';
require __DIR__ . '/_layout_top.php';

$totalMateriais = count($materials);
$criticos = array_values(array_filter(
    $materials,
    static fn(array $m): bool =>
        (float)$m['quantidade_atual'] <= (float)$m['quantidade_minima']
));
?>

<style>
    .campo-conferencia {
        min-width: 92px;
        height: 34px;
    }

    @media print {
        header,
        .no-print {
            display: none !important;
        }

        body {
            background: #fff !important;
            color: #000 !important;
        }

        main {
            max-width: none !important;
            padding: 0 !important;
        }

        .print-card {
            box-shadow: none !important;
            border: 0 !important;
            padding: 0 !important;
        }

        table {
            width: 100% !important;
            font-size: 9px !important;
            border-collapse: collapse !important;
        }

        th,
        td {
            border: 1px solid #94a3b8 !important;
            padding: 5px !important;
        }

        thead {
            display: table-header-group;
        }

        tr {
            page-break-inside: avoid;
        }

        .campo-conferencia {
            border: 0 !important;
            border-bottom: 1px solid #334155 !important;
            background: transparent !important;
            width: 100% !important;
            height: 26px !important;
        }

        .assinaturas {
            display: grid !important;
            grid-template-columns: 1fr 1fr !important;
            gap: 40px !important;
            margin-top: 35px !important;
        }

        @page {
            size: A4 landscape;
            margin: 8mm;
        }
    }
</style>

<div class="print-card rounded-2xl bg-white p-6 shadow-sm">
    <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
        <div>
            <h2 class="text-2xl font-bold">Conferência de Estoque</h2>
            <p class="mt-1 text-sm text-slate-500">Residencial Vivere Palhano</p>
            <p class="mt-1 text-xs text-slate-400">
                Emitido em <?= e(date('d/m/Y H:i')) ?>
            </p>
        </div>

        <div class="no-print flex flex-wrap gap-2">
            <button
                type="button"
                onclick="window.print()"
                class="rounded-lg bg-slate-900 px-5 py-2.5 font-semibold text-white hover:bg-slate-800"
            >
                Imprimir conferência
            </button>
        </div>
    </div>

    <div class="no-print mt-6 grid gap-4 md:grid-cols-3">
        <div class="rounded-xl bg-slate-50 p-4">
            <p class="text-sm text-slate-500">Total de materiais</p>
            <p class="mt-1 text-2xl font-bold"><?= $totalMateriais ?></p>
        </div>

        <div class="rounded-xl bg-red-50 p-4">
            <p class="text-sm text-red-600">Itens críticos</p>
            <p class="mt-1 text-2xl font-bold text-red-700"><?= count($criticos) ?></p>
        </div>

        <div class="rounded-xl bg-blue-50 p-4">
            <p class="text-sm text-blue-600">Objetivo</p>
            <p class="mt-1 text-sm font-semibold text-blue-900">
                Comparar o saldo do sistema com a contagem física.
            </p>
        </div>
    </div>

    <div class="mt-6 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-100 text-left text-slate-700">
                <tr>
                    <th class="px-3 py-3">Material</th>
                    <th class="px-3 py-3">Categoria</th>
                    <th class="px-3 py-3">Localização</th>
                    <th class="px-3 py-3">Un.</th>
                    <th class="px-3 py-3 text-right">Saldo Sistema</th>
                    <th class="px-3 py-3 text-right">Mínimo</th>
                    <th class="px-3 py-3">Qtd. Conferida</th>
                    <th class="px-3 py-3">Diferença</th>
                    <th class="px-3 py-3">OBS</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-200">
            <?php foreach ($materials as $material): ?>
                <?php
                    $critico = (float)$material['quantidade_atual'] <= (float)$material['quantidade_minima'];
                ?>
                <tr class="<?= $critico ? 'bg-red-50/40' : '' ?>">
                    <td class="px-3 py-3 font-medium"><?= e($material['nome']) ?></td>
                    <td class="px-3 py-3"><?= e($material['categoria']) ?></td>
                    <td class="px-3 py-3"><?= e($material['localizacao'] ?? '') ?></td>
                    <td class="px-3 py-3"><?= e($material['unidade_medida']) ?></td>
                    <td class="px-3 py-3 text-right font-semibold"><?= e((string)$material['quantidade_atual']) ?></td>
                    <td class="px-3 py-3 text-right"><?= e((string)$material['quantidade_minima']) ?></td>
                    <td class="px-3 py-3">
                        <input type="text" class="campo-conferencia w-full rounded border border-slate-300 px-2">
                    </td>
                    <td class="px-3 py-3">
                        <input type="text" class="campo-conferencia w-full rounded border border-slate-300 px-2">
                    </td>
                    <td class="px-3 py-3">
                        <input type="text" class="campo-conferencia w-full rounded border border-slate-300 px-2">
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (!$materials): ?>
                <tr>
                    <td colspan="9" class="px-4 py-10 text-center text-slate-500">
                        Nenhum material cadastrado.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <div class="mt-8 grid gap-5 md:grid-cols-3">
        <div>
            <p class="text-sm font-semibold">Data da conferência:</p>
            <div class="mt-3 h-8 border-b border-slate-400"></div>
        </div>
        <div>
            <p class="text-sm font-semibold">Responsável pela conferência:</p>
            <div class="mt-3 h-8 border-b border-slate-400"></div>
        </div>
        <div>
            <p class="text-sm font-semibold">Horário:</p>
            <div class="mt-3 h-8 border-b border-slate-400"></div>
        </div>
    </div>

    <div class="assinaturas mt-10 grid gap-10 md:grid-cols-2">
        <div class="pt-8 text-center">
            <div class="border-t border-slate-500 pt-2 text-sm">
                Responsável pela conferência
            </div>
        </div>

        <div class="pt-8 text-center">
            <div class="border-t border-slate-500 pt-2 text-sm">
                Administração
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
