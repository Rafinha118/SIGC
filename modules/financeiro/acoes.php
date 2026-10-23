<?php
/**
 * Ações Financeiras - SIGC Lee Market
 * Restrito ao perfil Dono
 */

require_once __DIR__ . '/../../config/bd.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/audit.php';

exigirCargo('dono');

$acao = $_GET['acao'] ?? '';
$pdo = getDB();
$user = usuarioAtual();

switch ($acao) {
    case 'lancar_movimento':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . baseUrl('modules/financeiro/fluxo_caixa.php'));
            exit;
        }

        $tipo = trim($_POST['tipo'] ?? 'SAIDA');
        $categoria = trim($_POST['categoria'] ?? 'Despesa Operacional');
        $valor = (float)str_replace(',', '.', $_POST['valor'] ?? '0');
        $descricao = trim($_POST['descricao'] ?? '');

        if ($valor <= 0 || empty($descricao)) {
            definirToast('erro', 'Informe um valor válido e a descrição da movimentação.');
            header('Location: ' . baseUrl('modules/financeiro/fluxo_caixa.php'));
            exit;
        }

        try {
            $agora = date('Y-m-d H:i:s');
            $stmt = $pdo->prepare("
                INSERT INTO fluxo_caixa (tipo, categoria, descricao, valor, usuario_id, data_movimento)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$tipo, $categoria, $descricao, $valor, $user['id'], $agora]);

            registrarAuditoria(
                'Financeiro',
                'MOVIMENTACAO_MANUAL',
                "Lançamento manual de {$tipo} (Cat: {$categoria}) no valor de R$ " . number_format($valor, 2, ',', '.') . ". Descrição: {$descricao}"
            );

            definirToast('sucesso', "Movimentação financeira lançada com sucesso!");
            header('Location: ' . baseUrl('modules/financeiro/fluxo_caixa.php'));
            exit;

        } catch (Throwable $e) {
            error_log("Erro ao lançar movimentação financeira: " . $e->getMessage());
            definirToast('erro', 'Erro ao registrar movimentação no banco de dados.');
            header('Location: ' . baseUrl('modules/financeiro/fluxo_caixa.php'));
            exit;
        }

    default:
        header('Location: ' . baseUrl('modules/financeiro/fluxo_caixa.php'));
        exit;
}
