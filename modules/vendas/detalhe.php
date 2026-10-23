<?php
/**
 * Detalhe e Comprovante de Venda - SIGC Lee Market
 */

require_once __DIR__ . '/../../config/bd.php';
require_once __DIR__ . '/../../config/auth.php';

exigirAutenticacao();

$id = (int)($_GET['id'] ?? 0);
$pdo = getDB();

$stmt = $pdo->prepare("
    SELECT v.*, u.nome AS operador_nome, u.cpf AS operador_cpf
    FROM vendas v
    JOIN usuarios u ON v.usuario_id = u.id
    WHERE v.id = ?
");
$stmt->execute([$id]);
$venda = $stmt->fetch();

if (!$venda) {
    definirToast('erro', 'Venda não localizada.');
    header('Location: ' . baseUrl('modules/vendas/historico.php'));
    exit;
}

// Busca itens da venda
$stmtItens = $pdo->prepare("
    SELECT iv.*, p.nome AS produto_nome, p.codigo_barras
    FROM itens_venda iv
    JOIN produtos p ON iv.produto_id = p.id
    WHERE iv.venda_id = ?
");
$stmtItens->execute([$id]);
$itens = $stmtItens->fetchAll();

$pageTitle = 'Comprovante da Venda ' . $venda['codigo_venda'] . ' — Lee Market';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
    <div class="mb-4 flex items-center justify-between">
        <a href="<?= baseUrl('modules/vendas/historico.php') ?>" class="text-xs font-bold text-lee-primary hover:underline flex items-center gap-1">
            &larr; Voltar para Histórico
        </a>
        <button type="button" onclick="window.print()" class="btn-lee-outline text-xs py-1.5">
            🖨️ Imprimir Cupom
        </button>
    </div>

    <!-- Cupom / Comprovante Estilizado -->
    <div class="bg-white rounded-xl shadow-md border border-gray-200 p-6 sm:p-8 font-mono text-xs text-gray-800" id="cupom-impresso">
        <div class="text-center pb-4 border-b-2 border-gray-800 mb-4">
            <h2 class="text-base font-black tracking-tight text-gray-900">LEE MARKET SUPERMERCADOS</h2>
            <div class="text-[11px] text-gray-600">SISTEMA INTEGRADO DE GESTÃO COMERCIAL (SIGC)</div>
            <div class="mt-2 text-xs font-bold uppercase tracking-wider bg-gray-100 py-1 rounded">
                DOCUMENTO NÃO FISCAL DE VENDA
            </div>
            <div class="mt-2 text-[11px] text-gray-500">
                CÓDIGO: <strong class="text-gray-900"><?= htmlspecialchars($venda['codigo_venda']) ?></strong><br>
                DATA/HORA: <?= date('d/m/Y H:i:s', strtotime($venda['criado_em'])) ?><br>
                OPERADOR DE CAIXA: <?= htmlspecialchars($venda['operador_nome']) ?> (<?= htmlspecialchars($venda['operador_cpf']) ?>)
            </div>
        </div>

        <!-- Tabela de Itens Comprados -->
        <table class="min-w-full divide-y divide-gray-200 mb-4">
            <thead>
                <tr class="text-left font-bold text-[11px] text-gray-600 uppercase">
                    <th class="py-1">Item / Descrição</th>
                    <th class="py-1 text-center">Qtd</th>
                    <th class="py-1 text-right">Unitário</th>
                    <th class="py-1 text-right">Total</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-dashed divide-gray-200">
                <?php foreach ($itens as $it): ?>
                <tr>
                    <td class="py-2 pr-2">
                        <div class="font-bold text-gray-900"><?= htmlspecialchars($it['produto_nome']) ?></div>
                        <div class="text-[10px] text-gray-400"><?= htmlspecialchars($it['codigo_barras']) ?></div>
                    </td>
                    <td class="py-2 text-center font-bold"><?= (int)$it['quantidade'] ?></td>
                    <td class="py-2 text-right">R$ <?= number_format((float)$it['preco_unitario'], 2, ',', '.') ?></td>
                    <td class="py-2 text-right font-bold text-gray-900">R$ <?= number_format((float)$it['subtotal'], 2, ',', '.') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- Totais e Pagamento -->
        <div class="pt-3 border-t-2 border-gray-800 space-y-1.5 text-xs">
            <div class="flex justify-between text-base font-black text-gray-900">
                <span>VALOR TOTAL PAGO:</span>
                <span>R$ <?= number_format((float)$venda['total'], 2, ',', '.') ?></span>
            </div>
            <div class="flex justify-between text-gray-600">
                <span>FORMA DE PAGAMENTO:</span>
                <span class="font-bold text-gray-900"><?= htmlspecialchars($venda['forma_pagamento']) ?></span>
            </div>
            <?php if ($venda['forma_pagamento'] === 'Dinheiro'): ?>
            <div class="flex justify-between text-gray-600">
                <span>VALOR ENTREGUE:</span>
                <span>R$ <?= number_format((float)$venda['valor_recebido'], 2, ',', '.') ?></span>
            </div>
            <div class="flex justify-between text-gray-600">
                <span>TROCO:</span>
                <span class="font-bold text-gray-900">R$ <?= number_format((float)$venda['troco'], 2, ',', '.') ?></span>
            </div>
            <?php endif; ?>
        </div>

        <div class="mt-6 pt-4 border-t border-dashed border-gray-300 text-center text-[10px] text-gray-400">
            Agradecemos a sua preferência no Lee Market!<br>
            Conserve este comprovante para eventuais trocas.
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
