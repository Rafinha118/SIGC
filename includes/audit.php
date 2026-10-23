<?php
/**
 * Helper de Auditoria e Logs do Sistema (CORE)
 * Registra todas as ações e alterações para auditorias futuras
 */

require_once __DIR__ . '/../config/bd.php';
require_once __DIR__ . '/../config/auth.php';

function registrarAuditoria(string $setor, string $acao, string $detalhes, ?array $usuarioManual = null): bool {
    try {
        $pdo = getDB();
        $user = $usuarioManual ?? usuarioAtual();

        $usuarioId = $user['id'] ?? null;
        $usuarioNome = $user['nome'] ?? 'Sistema / Visitante';
        $usuarioCpf = $user['cpf'] ?? null;
        $usuarioCargo = $user['cargo'] ?? null;

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $agora = date('Y-m-d H:i:s');

        $stmt = $pdo->prepare("
            INSERT INTO auditoria_logs 
            (usuario_id, usuario_nome, usuario_cpf, usuario_cargo, setor, acao, detalhes, ip_address, criado_em)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        return $stmt->execute([
            $usuarioId,
            $usuarioNome,
            $usuarioCpf,
            $usuarioCargo,
            $setor,
            $acao,
            $detalhes,
            $ip,
            $agora
        ]);
    } catch (Throwable $e) {
        error_log("Erro ao registrar auditoria: " . $e->getMessage());
        return false;
    }
}
