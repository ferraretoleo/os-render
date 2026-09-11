<?php

require_once __DIR__ . '/src/bootstrap.php';

$materials = $supabase->select('materiais', [
    'select' => 'id,nome,unidade_medida,quantidade_atual',
    'order' => 'nome.asc',
]);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    try {
        $materialId = postString('material_id', true);
        $tipo = postString('tipo', true);
        $quantidade = postFloat('quantidade', true);

        if (!in_array($tipo, ['entrada', 'saida'], true)) {
            throw new InvalidArgumentException('Tipo de movimentação inválido.');
        }

        if ($quantidade <= 0) {
            throw new InvalidArgumentException('A quantidade deve ser maior que zero.');
        }

        $supabase->insert('movimentacoes', [
            'material_id' => $materialId,
            'tipo' => $tipo,
            'quantidade' => $quantidade,
            'destino_aplicacao' => postString('destino_aplicacao'),
            'responsavel' => postString('responsavel', true),
        ]);

        flash('success', 'Movimentação registrada e saldo atualizado.');
        redirect('movimentacao.php');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        redirect('movimentacao.php');
    }
}

$pageTitle = 'Registrar movimentação';
require __DIR__ . '/_layout_top.php';
?>

<div class="mx-auto max-w-3xl rounded-2xl bg-white p-6 shadow-sm">
    <h2 class="text-2xl font-bold">Entrada ou saída de material</h2>
    <p class="mt-1 text-sm text-slate-500">O saldo é atualizado automaticamente pelo trigger do PostgreSQL.</p>

    <form method="post" class="mt-6 grid gap-4 md:grid-cols-2">
        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">

        <label class="md:col-span-2">
            <span class="mb-1 block text-sm font-medium">Material</span>
            <select name="material_id" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                <option value="">Selecione</option>
                <?php foreach ($materials as $m): ?>
                    <option value="<?= e($m['id']) ?>">
                        <?= e($m['nome']) ?> | saldo: <?= e((string)$m['quantidade_atual']) ?> <?= e($m['unidade_medida']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </label>

        <label>
            <span class="mb-1 block text-sm font-medium">Tipo</span>
            <select name="tipo" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
                <option value="entrada">Entrada</option>
                <option value="saida">Saída</option>
            </select>
        </label>

        <label>
            <span class="mb-1 block text-sm font-medium">Quantidade</span>
            <input name="quantidade" required type="number" min="0.001" step="0.001" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>

        <label class="md:col-span-2">
            <span class="mb-1 block text-sm font-medium">Destino / aplicação</span>
            <input name="destino_aplicacao" placeholder="Ex.: Torre A, manutenção hidráulica" class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>

        <label class="md:col-span-2">
            <span class="mb-1 block text-sm font-medium">Responsável</span>
            <input name="responsavel" required class="w-full rounded-lg border border-slate-300 px-3 py-2">
        </label>

        <div class="md:col-span-2">
            <button class="rounded-lg bg-blue-600 px-5 py-2.5 font-semibold text-white hover:bg-blue-500">Registrar movimentação</button>
        </div>
    </form>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
