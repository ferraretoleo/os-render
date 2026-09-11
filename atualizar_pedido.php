<?php

require_once __DIR__ . '/src/bootstrap.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido.');
}

validateCsrf();

try {
    $id = postString('id', true);
    $acao = postString('acao', true);
    $observacao = postString('observacao');

    $status = match ($acao) {
        'comprado' => 'comprado',
        'recusado' => 'recusado',
        default => throw new InvalidArgumentException('Ação inválida.'),
    };

    if ($status === 'recusado' && $observacao === '') {
        throw new InvalidArgumentException('Informe o motivo da recusa.');
    }

    $supabase->update('solicitacoes_compra', [
        'status' => $status,
        'observacao' => $observacao !== '' ? $observacao : null,
        'status_atualizado_em' => date('c'),
        'status_atualizado_por' => $_SESSION['user']['email'] ?? 'usuário',
    ], [
        'id' => 'eq.' . $id,
    ]);

    flash('success', $status === 'comprado'
        ? 'Pedido marcado como comprado.'
        : 'Pedido recusado e motivo registrado.'
    );
} catch (Throwable $e) {
    flash('error', $e->getMessage());
}

redirect('relatorio_pedidos.php');
