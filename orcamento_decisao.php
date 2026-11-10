<?php

require_once __DIR__ . '/src/bootstrap.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('orcamentos.php');
}

validateCsrf();
$id = postString('id', true);

try {
    $status = postString('status', true);
    $observacao = postString('observacao');
    if (!in_array($status, ['aprovado', 'reprovado'], true)) {
        throw new InvalidArgumentException('Decisão inválida.');
    }

    $rows = $supabase->select('orcamentos', [
        'select' => 'id,status',
        'id' => 'eq.' . $id,
        'limit' => 1,
    ]);
    if (!$rows) {
        throw new RuntimeException('Orçamento não encontrado.');
    }
    if ($rows[0]['status'] === $status) {
        flash('success', 'O orçamento já está com esse status.');
        redirect('orcamento_detalhe.php?id=' . urlencode($id));
    }

    $supabase->update('orcamentos', [
        'status' => $status,
        'observacao_decisao' => $observacao !== '' ? truncateText($observacao, 1000) : null,
        'decidido_por' => $_SESSION['user']['id'],
        'decidido_em' => date('c'),
        'atualizado_em' => date('c'),
    ], ['id' => 'eq.' . $id]);

    flash('success', $status === 'aprovado' ? 'Orçamento aprovado.' : 'Orçamento reprovado.');
} catch (InvalidArgumentException $e) {
    flash('error', $e->getMessage());
} catch (Throwable $e) {
    flash('error', 'Não foi possível registrar a decisão.');
}

redirect('orcamento_detalhe.php?id=' . urlencode($id));
