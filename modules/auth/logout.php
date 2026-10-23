<?php
/**
 * Logout do Sistema - SIGC Lee Market
 */

require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/audit.php';

if (estaAutenticado()) {
    $user = usuarioAtual();
    registrarAuditoria('Autenticação', 'LOGOUT', "Usuário efetuou logout do sistema.", $user);
}

// Limpa dados de sessão
$_SESSION = [];
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}
session_destroy();

// Inicia nova sessão limpa apenas para exibir mensagem Toast amigável
session_start();
definirToast('info', 'Sessão encerrada com sucesso. Até logo!');
header('Location: ' . baseUrl('modules/auth/login.php'));
exit;
