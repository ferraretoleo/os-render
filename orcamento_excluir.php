<?php

require_once __DIR__ . '/src/bootstrap.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('orcamentos.php');
}

validateCsrf();
$id = postString('id', true);

try {
    $rows = $supabase->select('orcamentos', [
        'select' => 'id,arquivo_caminho',
        'id' => 'eq.' . $id,
        'limit' => 1,
    ]);
    if (!$rows) {
        throw new RuntimeException('Orçamento não encontrado.');
    }

    $supabase->storageDelete('orcamentos', $rows[0]['arquivo_caminho']);
    $supabase->delete('orcamentos', ['id' => 'eq.' . $id]);
    flash('success', 'Orçamento excluído com sucesso.');
    redirect('orcamentos.php');
} catch (Throwable $e) {
    flash('error', 'Não foi possível excluir o orçamento. O registro foi mantido.');
    redirect('orcamento_detalhe.php?id=' . urlencode($id));
}
