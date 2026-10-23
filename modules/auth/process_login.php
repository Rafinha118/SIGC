<?php
/**
 * Processamento de Autenticação - SIGC Lee Market
 * Valida CPF, Nome Completo e Senha (com verificação bcrypt)
 */

require_once __DIR__ . '/../../config/bd.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/audit.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . baseUrl('modules/auth/login.php'));
    exit;
}

$cpf = trim($_POST['cpf'] ?? '');
$nome = trim($_POST['nome'] ?? '');
$senha = trim($_POST['senha'] ?? '');

if (empty($cpf) || empty($nome) || empty($senha)) {
    definirToast('erro', 'Todos os campos (CPF, Nome Completo e Senha) são obrigatórios.');
    header('Location: ' . baseUrl('modules/auth/login.php'));
    exit;
}

// Limpa caracteres do CPF para consulta flexível
$cpfLimpo = preg_replace('/\D/', '', $cpf);

try {
    $pdo = getDB();

    // Busca usuário pelo CPF formatado ou limpo
    $stmt = $pdo->prepare("
        SELECT * FROM usuarios 
        WHERE (cpf = ? OR REPLACE(REPLACE(cpf, '.', ''), '-', '') = ?)
        LIMIT 1
    ");
    $stmt->execute([$cpf, $cpfLimpo]);
    $usuario = $stmt->fetch();

    $loginValido = false;

    if ($usuario && (int)$usuario['status'] === 1) {
        // Valida Nome Completo (case-insensitive e remoção de espaços duplicados)
        $nomeCadastrado = mb_strtolower(trim(preg_replace('/\s+/', ' ', $usuario['nome'])));
        $nomeInformado = mb_strtolower(trim(preg_replace('/\s+/', ' ', $nome)));

        // Valida Senha com hash Bcrypt
        if ($nomeCadastrado === $nomeInformado && password_verify($senha, $usuario['senha'])) {
            $loginValido = true;
        }
    }

    if ($loginValido) {
        // Regenera ID de sessão para segurança
        session_regenerate_id(true);

        $_SESSION['usuario'] = [
            'id' => (int)$usuario['id'],
            'cpf' => $usuario['cpf'],
            'nome' => $usuario['nome'],
            'cargo' => strtolower($usuario['cargo'])
        ];

        // Registra log de auditoria
        registrarAuditoria('Autenticação', 'LOGIN_SUCESSO', "Login efetuado com sucesso como " . ucfirst($usuario['cargo']), $_SESSION['usuario']);

        definirToast('sucesso', "Bem-vindo(a), {$usuario['nome']}! Acesso liberado como " . ucfirst($usuario['cargo']) . ".");

        // Se for operador de caixa, pode ir direto ao PDV ou Dashboard
        if (strtolower($usuario['cargo']) === 'caixa') {
            header('Location: ' . baseUrl('modules/vendas/pdv.php'));
        } else {
            header('Location: ' . baseUrl('modules/dashboard/index.php'));
        }
        exit;
    } else {
        // Registra falha de tentativa para auditoria de segurança
        registrarAuditoria('Autenticação', 'LOGIN_FALHA', "Tentativa de login inválida. CPF informado: {$cpf}, Nome: {$nome}");

        definirToast('erro', 'Dados incorretos! Verifique o CPF, nome completo e senha informados.');
        header('Location: ' . baseUrl('modules/auth/login.php'));
        exit;
    }

} catch (Throwable $e) {
    error_log("Erro no login: " . $e->getMessage());
    definirToast('erro', 'Ocorreu um erro no servidor ao validar suas credenciais. Tente novamente.');
    header('Location: ' . baseUrl('modules/auth/login.php'));
    exit;
}
