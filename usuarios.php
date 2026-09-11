<?php

require_once __DIR__ . '/src/bootstrap.php';

requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    validateCsrf();

    try {
        $userId = postString('user_id', true);
        $nivel = postString('nivel', true);
        $nome = postString('nome');

        if (!in_array($nivel, ['admin', 'executante'], true)) {
            throw new InvalidArgumentException('Nível de acesso inválido.');
        }

        // Impede que o administrador remova o próprio acesso administrativo por engano.
        if ($userId === ($_SESSION['user']['id'] ?? '') && $nivel !== 'admin') {
            throw new InvalidArgumentException('Você não pode retirar seu próprio acesso de Administrador.');
        }

        $supabase->update('perfis', [
            'nivel' => $nivel,
            'nome' => $nome !== '' ? $nome : null,
            'atualizado_em' => date('c'),
        ], [
            'user_id' => 'eq.' . $userId,
        ]);

        flash('success', 'Nível de acesso atualizado com sucesso.');
        redirect('usuarios.php');
    } catch (Throwable $e) {
        flash('error', $e->getMessage());
        redirect('usuarios.php');
    }
}

$profiles = $supabase->select('perfis', [
    'select' => 'user_id,email,nome,nivel,criado_em,atualizado_em',
    'order' => 'email.asc',
]);

$pageTitle = 'Usuários';
require __DIR__ . '/_layout_top.php';
?>

<div class="rounded-2xl bg-white p-5 shadow-sm">
    <div class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
        <div>
            <h2 class="text-2xl font-bold">Usuários e níveis de acesso</h2>
            <p class="mt-1 text-sm text-slate-500">
                Os usuários são criados no Supabase Auth. Nesta tela você define se serão Administradores ou Executantes.
            </p>
        </div>
    </div>

    <div class="mt-5 rounded-xl border border-blue-200 bg-blue-50 p-4 text-sm text-blue-900">
        <strong>Executante:</strong> Movimentação, Histórico e Compra.
        <br>
        <strong>Administrador:</strong> acesso completo ao sistema.
    </div>

    <div class="mt-6 overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="bg-slate-50 text-left text-slate-600">
                <tr>
                    <th class="px-4 py-3">E-mail</th>
                    <th class="px-4 py-3">Nome</th>
                    <th class="px-4 py-3">Nível</th>
                    <th class="px-4 py-3">Ação</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
            <?php foreach ($profiles as $profile): ?>
                <tr>
                    <form method="post">
                        <input type="hidden" name="csrf" value="<?= e(csrfToken()) ?>">
                        <input type="hidden" name="user_id" value="<?= e($profile['user_id']) ?>">

                        <td class="px-4 py-3 font-medium"><?= e($profile['email']) ?></td>

                        <td class="px-4 py-3">
                            <input
                                name="nome"
                                value="<?= e($profile['nome'] ?? '') ?>"
                                placeholder="Nome do usuário"
                                class="w-full min-w-44 rounded-lg border border-slate-300 px-3 py-2"
                            >
                        </td>

                        <td class="px-4 py-3">
                            <select name="nivel" class="rounded-lg border border-slate-300 px-3 py-2">
                                <option value="executante" <?= $profile['nivel'] === 'executante' ? 'selected' : '' ?>>
                                    Executante
                                </option>
                                <option value="admin" <?= $profile['nivel'] === 'admin' ? 'selected' : '' ?>>
                                    Administrador
                                </option>
                            </select>
                        </td>

                        <td class="px-4 py-3">
                            <button class="rounded-lg bg-blue-600 px-4 py-2 font-semibold text-white hover:bg-blue-500">
                                Salvar
                            </button>
                        </td>
                    </form>
                </tr>
            <?php endforeach; ?>

            <?php if (!$profiles): ?>
                <tr>
                    <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                        Nenhum perfil encontrado.
                    </td>
                </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/_layout_bottom.php'; ?>
