<?php
/**
 * Ponto de Entrada Central - SIGC Lee Market
 * Redireciona para o Dashboard (se autenticado) ou Tela de Login
 */

require_once __DIR__ . '/config/auth.php';

if (estaAutenticado()) {
    header('Location: ' . baseUrl('modules/dashboard/index.php'));
} else {
    header('Location: ' . baseUrl('modules/auth/login.php'));
}
exit;
