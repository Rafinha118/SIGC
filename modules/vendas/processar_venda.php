<?php
/**
 * Processamento Backend da Venda - SIGC Lee Market
 * Recebe payload JSON, decrementa estoque, registra movimentações, fluxo de caixa e auditoria
 */

require_once __DIR__ . '/../../config/bd.php';
require_once __DIR__ . '/../../config/auth.php';
require_once __DIR__ . '/../../includes/audit.php';

header('Content-Type: application/json; charset=utf-8');

if (!estaAutenticado()) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Sessão expirada. Faça login novamente.']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true);

if (!$data || empty($data['itens']) || !is_array($data['itens'])) {
    echo json_encode(['sucesso' => false, 'mensagem' => 'Dados da venda inválidos ou carrinho vazio.']);
    exit;
}

$pdo = getDB();
$user = usuarioAtual();

try {
    $pdo->beginTransaction();

    $totalCalculado = 0;
    $itensParaGravar = [];

    // 1. Validação de estoque e preços em lote com lock
    foreach ($data['itens'] as $item) {
        $prodId = (int)($item['id'] ?? 0);
        $qtd = (int)($item['quantidade'] ?? 0);

        if ($prodId <= 0 || $qtd <= 0) {
            throw new Exception("Item ou quantidade inválida.");
        }

        $stmt = $pdo->prepare("SELECT id, nome, preco_venda, estoque_atual FROM produtos WHERE id = ? AND ativo = 1 FOR UPDATE");
        $stmt->execute([$prodId]);
        $prod = $stmt->fetch();

        if (!$prod) {
            throw new Exception("Produto ID {$prodId} não encontrado.");
        }

        $estoqueAtual = (int)$prod['estoque_atual'];
        if ($qtd > $estoqueAtual) {
            throw new Exception("Estoque insuficiente para o produto '{$prod['nome']}'. Disponível: {$estoqueAtual} un.");
        }

        $precoUnit = (float)$prod['preco_venda'];
        $subtotal = $qtd * $precoUnit;
        $totalCalculado += $subtotal;

        $itensParaGravar[] = [
            'id' => $prodId,
            'nome' => $prod['nome'],
            'quantidade' => $qtd,
            'preco_unitario' => $precoUnit,
            'subtotal' => $subtotal,
            'estoque_anterior' => $estoqueAtual,
            'estoque_novo' => $estoqueAtual - $qtd
        ];
    }

    $formaPagamento = trim($data['forma_pagamento'] ?? 'Dinheiro');
    $valorRecebido = isset($data['valor_recebido']) ? (float)$data['valor_recebido'] : $totalCalculado;
    $troco = isset($data['troco']) ? (float)$data['troco'] : 0.00;

    // Gera código da venda único
    $codigoVenda = 'VD-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -5));
    $agora = date('Y-m-d H:i:s');

    // 2. Insere a venda
    $stmtVenda = $pdo->prepare("
        INSERT INTO vendas 
        (codigo_venda, usuario_id, total, forma_pagamento, valor_recebido, troco, status, criado_em)
        VALUES (?, ?, ?, ?, ?, ?, 'CONCLUIDA', ?)
    ");
    $stmtVenda->execute([
        $codigoVenda,
        $user['id'],
        $totalCalculado,
        $formaPagamento,
        $valorRecebido,
        $troco,
        $agora
    ]);
    $vendaId = $pdo->lastInsertId();

    // 3. Insere itens, atualiza estoque e registra movimentação
    $stmtItem = $pdo->prepare("
        INSERT INTO itens_venda (venda_id, produto_id, quantidade, preco_unitario, subtotal)
        VALUES (?, ?, ?, ?, ?)
    ");

    $stmtUpEstoque = $pdo->prepare("UPDATE produtos SET estoque_atual = ?, atualizado_em = ? WHERE id = ?");

    $stmtMov = $pdo->prepare("
        INSERT INTO movimentacoes_estoque 
        (produto_id, usuario_id, tipo, quantidade, quantidade_anterior, quantidade_nova, motivo, criado_em)
        VALUES (?, ?, 'SAIDA_VENDA', ?, ?, ?, ?, ?)
    ");

    foreach ($itensParaGravar as $it) {
        $stmtItem->execute([
            $vendaId,
            $it['id'],
            $it['quantidade'],
            $it['preco_unitario'],
            $it['subtotal']
        ]);

        $stmtUpEstoque->execute([$it['estoque_novo'], $agora, $it['id']]);

        $stmtMov->execute([
            $it['id'],
            $user['id'],
            $it['quantidade'],
            $it['estoque_anterior'],
            $it['estoque_novo'],
            "Venda {$codigoVenda}",
            $agora
        ]);
    }

    // 4. Registra no Fluxo de Caixa (Entrada Financeira)
    $stmtFluxo = $pdo->prepare("
        INSERT INTO fluxo_caixa (tipo, categoria, descricao, valor, referencia_venda_id, usuario_id, data_movimento)
        VALUES ('ENTRADA', 'Venda', ?, ?, ?, ?, ?)
    ");
    $stmtFluxo->execute([
        "Venda no PDV {$codigoVenda} ({$formaPagamento})",
        $totalCalculado,
        $vendaId,
        $user['id'],
        $agora
    ]);

    // 5. Registra log de auditoria
    registrarAuditoria(
        'Vendas',
        'VENDA_FINALIZADA',
        "Venda {$codigoVenda} finalizada com sucesso. Total: R$ " . number_format($totalCalculado, 2, ',', '.') . " em {$formaPagamento}. Qtd Itens: " . count($itensParaGravar)
    );

    $pdo->commit();

    echo json_encode([
        'sucesso' => true,
        'mensagem' => 'Venda realizada com sucesso!',
        'venda' => [
            'id' => $vendaId,
            'codigo_venda' => $codigoVenda,
            'data' => date('d/m/Y H:i:s'),
            'total' => $totalCalculado,
            'forma_pagamento' => $formaPagamento,
            'valor_recebido' => $valorRecebido,
            'troco' => $troco,
            'operador' => $user['nome'],
            'itens' => $itensParaGravar
        ]
    ]);

} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log("Erro ao processar venda: " . $e->getMessage());
    echo json_encode([
        'sucesso' => false,
        'mensagem' => $e->getMessage() ?: 'Erro ao gravar a venda no banco de dados.'
    ]);
}
