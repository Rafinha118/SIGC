<?php
/**
 * Processamento de Estoque - SIGC Lee Market
 * Processa saídas (avarias/vencimentos/consumo) e entradas/ajustes
 */

require_once __DIR__ . '/../../config/bd.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/audit.php';

exigirAutenticacao();

$acao = $_GET['acao'] ?? '';
$pdo = getDB();
$user = usuarioAtual();

switch ($acao) {
    case 'registrar_saida':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . baseUrl('modules/estoque/index.php'));
            exit;
        }

        $produtoId = (int)($_POST['produto_id'] ?? 0);
        $quantidade = (int)($_POST['quantidade'] ?? 0);
        $tipoSaida = trim($_POST['tipo_saida'] ?? 'SAIDA_OUTROS');
        $motivo = trim($_POST['motivo'] ?? '');

        if ($produtoId <= 0 || $quantidade <= 0) {
            definirToast('erro', 'Produto ou quantidade inválida informada.');
            header('Location: ' . baseUrl('modules/estoque/saida.php'));
            exit;
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT id, nome, estoque_atual FROM produtos WHERE id = ? AND ativo = 1 FOR UPDATE");
            $stmt->execute([$produtoId]);
            $prod = $stmt->fetch();

            if (!$prod) {
                $pdo->rollBack();
                definirToast('erro', 'Produto não localizado.');
                header('Location: ' . baseUrl('modules/estoque/saida.php'));
                exit;
            }

            $estoqueAntes = (int)$prod['estoque_atual'];
            if ($quantidade > $estoqueAntes) {
                $pdo->rollBack();
                definirToast('erro', "Estoque insuficiente! Disponível: {$estoqueAntes} un.");
                header('Location: ' . baseUrl('modules/estoque/saida.php?produto_id=' . $produtoId));
                exit;
            }

            $estoqueNovo = $estoqueAntes - $quantidade;
            $agora = date('Y-m-d H:i:s');

            // 1. Atualiza estoque do produto
            $stmtUp = $pdo->prepare("UPDATE produtos SET estoque_atual = ?, atualizado_em = ? WHERE id = ?");
            $stmtUp->execute([$estoqueNovo, $agora, $produtoId]);

            // 2. Insere registro na tabela de movimentações
            $stmtMov = $pdo->prepare("
                INSERT INTO movimentacoes_estoque 
                (produto_id, usuario_id, tipo, quantidade, quantidade_anterior, quantidade_nova, motivo, criado_em)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtMov->execute([
                $produtoId,
                $user['id'],
                $tipoSaida,
                $quantidade,
                $estoqueAntes,
                $estoqueNovo,
                $motivo,
                $agora
            ]);

            // 3. Auditoria
            registrarAuditoria(
                'Estoque',
                'SAIDA_ESTOQUE',
                "Saída de {$quantidade} un do produto '{$prod['nome']}' ({$tipoSaida}). Saldo: {$estoqueAntes} -> {$estoqueNovo}. Motivo: {$motivo}"
            );

            $pdo->commit();

            definirToast('sucesso', "Saída de {$quantidade} un do produto '{$prod['nome']}' registrada com sucesso!");
            header('Location: ' . baseUrl('modules/estoque/index.php'));
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Erro ao registrar saída de estoque: " . $e->getMessage());
            definirToast('erro', 'Erro interno ao processar baixa de estoque.');
            header('Location: ' . baseUrl('modules/estoque/saida.php'));
            exit;
        }

    case 'registrar_entrada':
        exigirCargo(['gerente', 'dono']);

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: ' . baseUrl('modules/estoque/index.php'));
            exit;
        }

        $produtoId = (int)($_POST['produto_id'] ?? 0);
        $quantidade = (int)($_POST['quantidade'] ?? 0);
        $tipoMovimento = trim($_POST['tipo_movimento'] ?? 'ENTRADA');
        $motivo = trim($_POST['motivo'] ?? '');

        if ($produtoId <= 0 || $quantidade < 0) {
            definirToast('erro', 'Valores inválidos.');
            header('Location: ' . baseUrl('modules/estoque/entrada.php'));
            exit;
        }

        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("SELECT id, nome, estoque_atual FROM produtos WHERE id = ? AND ativo = 1 FOR UPDATE");
            $stmt->execute([$produtoId]);
            $prod = $stmt->fetch();

            if (!$prod) {
                $pdo->rollBack();
                definirToast('erro', 'Produto não encontrado.');
                header('Location: ' . baseUrl('modules/estoque/entrada.php'));
                exit;
            }

            $estoqueAntes = (int)$prod['estoque_atual'];
            $qtdMovimentada = $quantidade;

            if ($tipoMovimento === 'ENTRADA') {
                $estoqueNovo = $estoqueAntes + $quantidade;
            } else { // AJUSTE
                $estoqueNovo = $quantidade;
                $qtdMovimentada = abs($estoqueNovo - $estoqueAntes);
            }

            $agora = date('Y-m-d H:i:s');

            $stmtUp = $pdo->prepare("UPDATE produtos SET estoque_atual = ?, atualizado_em = ? WHERE id = ?");
            $stmtUp->execute([$estoqueNovo, $agora, $produtoId]);

            $stmtMov = $pdo->prepare("
                INSERT INTO movimentacoes_estoque 
                (produto_id, usuario_id, tipo, quantidade, quantidade_anterior, quantidade_nova, motivo, criado_em)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmtMov->execute([
                $produtoId,
                $user['id'],
                $tipoMovimento,
                $qtdMovimentada,
                $estoqueAntes,
                $estoqueNovo,
                $motivo,
                $agora
            ]);

            registrarAuditoria(
                'Estoque',
                $tipoMovimento === 'ENTRADA' ? 'ENTRADA_ESTOQUE' : 'AJUSTE_ESTOQUE',
                "Movimentação ({$tipoMovimento}) no produto '{$prod['nome']}'. Saldo: {$estoqueAntes} -> {$estoqueNovo}. Obs: {$motivo}"
            );

            $pdo->commit();

            definirToast('sucesso', "Estoque do produto '{$prod['nome']}' atualizado para {$estoqueNovo} un!");
            header('Location: ' . baseUrl('modules/estoque/index.php'));
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            error_log("Erro ao registrar entrada/ajuste: " . $e->getMessage());
            definirToast('erro', 'Erro ao salvar alteração de estoque.');
            header('Location: ' . baseUrl('modules/estoque/entrada.php'));
            exit;
        }

    default:
        header('Location: ' . baseUrl('modules/estoque/index.php'));
        exit;
}
