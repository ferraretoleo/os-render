<?php

require_once __DIR__ . '/src/bootstrap.php';

$status = trim((string)($_GET['status'] ?? ''));
$tipo = trim((string)($_GET['tipo'] ?? ''));
$fornecedor = trim((string)($_GET['fornecedor'] ?? ''));
$dataInicio = trim((string)($_GET['data_inicio'] ?? ''));
$dataFim = trim((string)($_GET['data_fim'] ?? ''));

$query = [
    'select' => 'id,titulo,tipo,fornecedor,valor,data_orcamento,validade,status,arquivo_nome_original,criado_em',
    'order' => 'criado_em.desc',
];
if (in_array($status, ['pendente', 'aprovado', 'reprovado'], true)) {
    $query['status'] = 'eq.' . $status;
}
if (in_array($tipo, ['manutencao', 'obra', 'outro'], true)) {
    $query['tipo'] = 'eq.' . $tipo;
}
if ($fornecedor !== '') {
    $search = preg_replace('/[^\p{L}\p{N}\s.\-]/u', '', $fornecedor);
    if ($search !== '') {
        $query['fornecedor'] = 'ilike.*' . $search . '*';
    }
}
if ($dataInicio !== '' && $dataFim !== '') {
    $query['and'] = '(data_orcamento.gte.' . $dataInicio . ',data_orcamento.lte.' . $dataFim . ')';
} elseif ($dataInicio !== '') {
    $query['data_orcamento'] = 'gte.' . $dataInicio;
} elseif ($dataFim !== '') {
    $query['data_orcamento'] = 'lte.' . $dataFim;
}

try {
    $budgets = $supabase->select('orcamentos', $query);
} catch (Throwable $e) {
    $budgets = [];
    flash('error', 'Não foi possível carregar os orçamentos. Confirme se a migração do módulo foi executada.');
}

$statusLabels = ['pendente' => 'Pendente', 'aprovado' => 'Aprovado', 'reprovado' => 'Reprovado'];
$typeLabels = ['manutencao' => 'Manutenção', 'obra' => 'Obra', 'outro' => 'Outro'];
$statusClasses = [
    'pendente' => 'bg-amber-100 text-amber-800',
    'aprovado' => 'bg-emerald-100 text-emerald-800',
    'reprovado' => 'bg-red-100 text-red-800',
];

$pageTitle = 'Orçamentos';
require __DIR__ . '/_layout_top.php';
?>

<div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h2 class="text-2xl font-bold">Controle de orçamentos</h2>
        <p class="mt-1 text-sm text-slate-500">Documentos de manutenção e obras enviados para aprovação.</p>
    </div>
    <a href="orcamento_form.php" class="rounded-lg bg-blue-600 px-5 py-2.5 text-center font-semibold text-white hover:bg-blue-500">
        Novo orçamento
    </a>
</div>

<form method="get" class="mt-6 grid gap-3 rounded-2xl bg-white p-4 shadow-sm md:grid-cols-6">
    <select name="status" class="rounded-lg border border-slate-300 px-3 py-2">
        <option value="">Todos os status</option>
        <?php foreach ($statusLabels as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= $status === $value ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <select name="tipo" class="rounded-lg border border-slate-300 px-3 py-2">
        <option value="">Todos os tipos</option>
        <?php foreach ($typeLabels as $value => $label): ?>
            <option value="<?= e($value) ?>" <?= $tipo === $value ? 'selected' : '' ?>><?= e($label) ?></option>
        <?php endforeach; ?>
    </select>
    <input name="fornecedor" value="<?= e($fornecedor) ?>" placeholder="Fornecedor"
           class="rounded-lg border border-slate-300 px-3 py-2">
    <input type="date" name="data_inicio" value="<?= e($dataInicio) ?>" title="Data inicial"
           class="rounded-lg border border-slate-300 px-3 py-2">
    <input type="date" name="data_fim" value="<?= e($dataFim) ?>" title="Data final"
           class="rounded-lg border border-slate-300 px-3 py-2">
    <div class="flex gap-2">
        <button class="flex-1 rounded-lg bg-slate-800 px-3 py-2 font-medium text-white hover:bg-slate-700">Filtrar</button>
        <a href="orcamentos.php" class="rounded-lg border border-slate-300 px-3 py-2 hover:bg-slate-50">Limpar</a>
    </div>
</form>

<div class="mt-6 overflow-x-auto rounded-2xl bg-white shadow-sm">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left text-slate-600">
        <tr>
            <th class="px-4 py-3">Orçamento</th>
            <th class="px-4 py-3">Tipo</th>
            <th class="px-4 py-3">Fornecedor</th>
            <th class="px-4 py-3">Valor</th>
            <th class="px-4 py-3">Data</th>
            <th class="px-4 py-3">Status</th>
            <th class="px-4 py-3">Ação</th>
        </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
        <?php foreach ($budgets as $budget): ?>
            <tr class="hover:bg-slate-50">
                <td class="px-4 py-3 font-medium"><?= e($budget['titulo']) ?></td>
                <td class="px-4 py-3"><?= e($typeLabels[$budget['tipo']] ?? $budget['tipo']) ?></td>
                <td class="px-4 py-3"><?= e($budget['fornecedor']) ?></td>
                <td class="px-4 py-3">R$ <?= number_format((float)$budget['valor'], 2, ',', '.') ?></td>
                <td class="px-4 py-3"><?= e(date('d/m/Y', strtotime($budget['data_orcamento']))) ?></td>
                <td class="px-4 py-3">
                    <span class="rounded-full px-2.5 py-1 text-xs font-semibold <?= $statusClasses[$budget['status']] ?? 'bg-slate-100 text-slate-700' ?>">
                        <?= e($statusLabels[$budget['status']] ?? $budget['status']) ?>
                    </span>
                </td>
                <td class="px-4 py-3">
                    <a href="orcamento_detalhe.php?id=<?= urlencode($budget['id']) ?>" class="font-medium text-blue-600 hover:underline">Abrir</a>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$budgets): ?>
            <tr><td colspan="7" class="px-4 py-10 text-center text-slate-500">Nenhum orçamento encontrado.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
