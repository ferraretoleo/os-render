<?php

require_once __DIR__ . '/src/bootstrap.php';

requireAdmin();

$id = trim((string)($_GET['id'] ?? ''));
$editing = $id !== '';
$material = [
    'nome' => '',
    'categoria' => '',
    'unidade_medida' => 'un',
    'quantidade_atual' => 0,
    'quantidade_minima' => 0,
    'localizacao' => '',
    'observacoes' => '',
    'status_material' => 'aprovado',
];

if ($editing) {
    $rows = $supabase->select('materiais', [
        'select' => '*',
        'id' => 'eq.' . $id,
        'limit' => 1,
    ]);
    if (!$rows) {
        http_response_code(404);
        exit('Material não encontrado.');
    }
    $material = $rows[0];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    try {
        $payload = [
            'nome' => postString('nome', true),
            'categoria' => postString('categoria', true),
            'unidade_medida' => postString('unidade_medida', true),
            'quantidade_minima' => postFloat('quantidade_minima', true),
            'localizacao' => postString('localizacao'),
            'observacoes' => postString('observacoes'),
            'status_material' => postString('status_material', true),
        ];

        if (!in_array($payload['status_material'], ['aprovado', 'rejeitado'], true)) {
            throw new InvalidArgumentException('Status do material inválido.');
        }

        if (!$editing) {
            $payload['quantidade_atual'] = postFloat('quantidade_atual');
            $supabase->insert('materiais', $payload);
            flash('success', 'Material cadastrado com sucesso.');
        } else {
            $supabase->update('materiais', $payload, ['id' => 'eq.' . $id]);
            flash('success', 'Material atualizado com sucesso.');
        }

        redirect('dashboard.php');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
    }
}

$pageTitle = $editing ? 'Editar material' : 'Novo material';
require __DIR__ . '/_layout_top.php';
?>

<div class="mx-auto max-w-3xl rounded-2xl bg-white p-6 shadow-sm">
    <h2 class="text-2xl font-bold"><?= e($pageTitle) ?></h2>

    <form method="post" class="mt-6 grid gap-4 md:grid-cols-2">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">

        <label class="md:col-span-2">
            <span class="mb-1 block text-sm font-medium">Nome</span>
            <input name="nome" required value="<?= e($material['nome']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>

        <label>
            <span class="mb-1 block text-sm font-medium">Categoria</span>
            <input name="categoria" required value="<?= e($material['categoria']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>

        <label>
            <span class="mb-1 block text-sm font-medium">Unidade de medida</span>
            <select name="unidade_medida" class="w-full rounded-lg border border-slate-300 px-3 py-2">
                <?php foreach (['un','kg','g','l','ml','pct','cx','m','m²','rl'] as $unit): ?>
                    <option value="<?= e($unit) ?>" <?= ($material['unidade_medida'] ?? '') === $unit ? 'selected' : '' ?>><?= e($unit) ?></option>
                <?php endforeach; ?>
            </select>
        </label>

        <?php if (!$editing): ?>
        <label>
            <span class="mb-1 block text-sm font-medium">Quantidade inicial</span>
            <input name="quantidade_atual" type="number" min="0" step="0.001" value="<?= e((string)$material['quantidade_atual']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>
        <?php endif; ?>

        <label>
            <span class="mb-1 block text-sm font-medium">Quantidade mínima</span>
            <input name="quantidade_minima" required type="number" min="0" step="0.001" value="<?= e((string)$material['quantidade_minima']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>

        <label class="md:col-span-2">
            <span class="mb-1 block text-sm font-medium">Localização</span>
            <input name="localizacao" value="<?= e($material['localizacao']) ?>" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>

        <label>
            <span class="mb-1 block text-sm font-medium">Situação</span>
            <select name="status_material" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                <option value="aprovado" <?= ($material['status_material'] ?? 'aprovado') === 'aprovado' ? 'selected' : '' ?>>
                    Aprovado
                </option>
                <option value="rejeitado" <?= ($material['status_material'] ?? '') === 'rejeitado' ? 'selected' : '' ?>>
                    Rejeitado
                </option>
            </select>
        </label>

        <label class="md:col-span-2">
            <span class="mb-1 block text-sm font-medium">Observações</span>
            <textarea name="observacoes" rows="4" class="w-full rounded-lg border border-slate-300 px-3 py-2"><?= e($material['observacoes']) ?></textarea>
        </label>

        <div class="md:col-span-2 flex gap-3">
            <button class="rounded-lg bg-blue-600 px-5 py-2.5 font-semibold text-white hover:bg-blue-500">Salvar</button>
            <a href="dashboard.php" class="rounded-lg border border-slate-300 px-5 py-2.5 hover:bg-slate-50">Cancelar</a>
        </div>
    </form>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
