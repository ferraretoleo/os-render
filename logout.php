<?php

require_once __DIR__ . '/src/bootstrap.php';

if (!empty($_SESSION['access_token'])) {
    try {
        $auth->signOut($_SESSION['access_token']);
    } catch (Throwable $e) {
        // A sessão local será encerrada mesmo se o endpoint remoto falhar.
    }
}

clearLocalSession();

header('Location: login.php');
exit;
