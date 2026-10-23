<?php
/**
 * Gerenciamento de Sessão e Controle de Acesso (RBAC)
 * Sistema Integrado de Gestão Comercial (SIGC) - Lee Market
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Retorna a URL base do sistema de forma dinâmica
 */
function baseUrl(string $path = ''): string {
    $scriptDir = dirname($_SERVER['SCRIPT_NAME'] ?? '');
    
    // Normaliza para barras normais
    $scriptDir = str_replace('\\', '/', $scriptDir);
    
    // Se estivermos dentro de /modules/..., sobe até a raiz do projeto
    $pos = strpos($scriptDir, '/modules');
    if ($pos !== false) {
        $base = substr($scriptDir, 0, $pos);
    } else {
        $base = rtrim($scriptDir, '/');
    }

    $path = ltrim($path, '/');
    return ($base === '' ? '/' : $base . '/') . $path;
}

/**
 * Verifica se há um usuário autenticado na sessão
 */
function estaAutenticado(): bool {
    return isset($_SESSION['usuario']) && !empty($_SESSION['usuario']['id']);
}

/**
 * Retorna os dados do usuário autenticado ou null
 */
function usuarioAtual(): ?array {
    return $_SESSION['usuario'] ?? null;
}

/**
 * Exige autenticação para acessar a página
 */
function exigirAutenticacao(): void {
    if (!estaAutenticado()) {
        $_SESSION['toast'] = [
            'tipo' => 'aviso',
            'mensagem' => 'Por favor, realize login para acessar o sistema.'
        ];
        header('Location: ' . baseUrl('modules/auth/login.php'));
        exit;
    }
}

/**
 * Exige um ou mais cargos específicos para acessar a página
 * @param array|string $cargosPermitidos Ex: 'dono' ou ['dono', 'gerente']
 */
function exigirCargo($cargosPermitidos): void {
    exigirAutenticacao();
    
    if (is_string($cargosPermitidos)) {
        $cargosPermitidos = [$cargosPermitidos];
    }
    
    $cargoUsuario = strtolower($_SESSION['usuario']['cargo'] ?? '');
    $permitidos = array_map('strtolower', $cargosPermitidos);

    if (!in_array($cargoUsuario, $permitidos, true)) {
        $_SESSION['toast'] = [
            'tipo' => 'erro',
            'mensagem' => 'Acesso negado: seu perfil (' . ucfirst($cargoUsuario) . ') não tem permissão para esta funcionalidade.'
        ];
        header('Location: ' . baseUrl('modules/dashboard/index.php'));
        exit;
    }
}

/**
 * Verifica se o usuário atual possui determinado cargo
 */
function temCargo($cargos): bool {
    if (!estaAutenticado()) return false;
    if (is_string($cargos)) $cargos = [$cargos];
    $cargoUsuario = strtolower($_SESSION['usuario']['cargo'] ?? '');
    return in_array($cargoUsuario, array_map('strtolower', $cargos), true);
}

/**
 * Define uma mensagem Toast para exibição na próxima tela
 */
function definirToast(string $tipo, string $mensagem): void {
    $_SESSION['toast'] = [
        'tipo' => $tipo, // 'sucesso', 'erro', 'aviso', 'info'
        'mensagem' => $mensagem
    ];
}
