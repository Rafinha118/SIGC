<?php
/**
 * Acompanhamento de Faturamento - SIGC Lee Market
 * Requisitos:
 * - Acompanhamento do faturamento obtido com as vendas
 * - Seleção do período e cálculo do total faturado
 * - Análise de faturamento bruto, custos e margem estimada
 * - Detalhamento por meio de pagamento e produtos mais faturados
 */

require_once __DIR__ . '/../../config/bd.php';
require_once __DIR__ . '/../../config/auth.php';

exigirCargo('dono');

$pdo = getDB();

$dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
$dataFim = $_GET['data_fim'] ?? date('Y-m-d');

// 1. Vendas no período
$stmtVendas = $pdo->prepare("
    SELECT v.*, u.nome AS operador_nome
    FROM vendas v
    JOIN usuarios u ON v.usuario_id = u.id
    WHERE v.criado_em BETWEEN ? AND ?
    ORDER BY v.criado_em DESC
");
$stmtVendas->execute([$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59']);
$vendas = $stmtVendas->fetchAll();

$faturamentoTotal = array_sum(array_column($vendas, 'total'));
$totalVendas = count($vendas);
$ticketMedio = $totalVendas > 0 ? ($faturamentoTotal / $totalVendas) : 0;

// 2. Faturamento agrupado por Forma de Pagamento
$stmtPagto = $pdo->prepare("
    SELECT forma_pagamento, COUNT(*) AS qtd, SUM(total) AS total_pagto
    FROM vendas
    WHERE criado_em BETWEEN ? AND ?
    GROUP BY forma_pagamento
");
$stmtPagto->execute([$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59']);
$pagamentos = $stmtPagto->fetchAll();

// 3. Custo dos itens vendidos e lucro bruto estimado
$stmtCusto = $pdo->prepare("
    SELECT 
        COALESCE(SUM(iv.quantidade * p.preco_custo), 0) AS custo_total
    FROM itens_venda iv
    JOIN vendas v ON iv.venda_id = v.id
    JOIN produtos p ON iv.produto_id = p.id
    WHERE v.criado_em BETWEEN ? AND ?
");
$stmtCusto->execute([$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59']);
$custoTotal = (float)$stmtCusto->fetchColumn();

$lucroBruto = $faturamentoTotal - $custoTotal;
$margemLucro = $faturamentoTotal > 0 ? (($lucroBruto / $faturamentoTotal) * 100) : 0;

// 4. Top 5 produtos com maior faturamento no período
$stmtTopProd = $pdo->prepare("
    SELECT 
        p.nome, p.categoria,
        SUM(iv.quantidade) AS qtd_vendida,
        SUM(iv.subtotal) AS faturamento_produto
    FROM itens_venda iv
    JOIN vendas v ON iv.venda_id = v.id
    JOIN produtos p ON iv.produto_id = p.id
    WHERE v.criado_em BETWEEN ? AND ?
    GROUP BY p.id, p.nome, p.categoria
    ORDER BY faturamento_produto DESC
    LIMIT 5
");
$stmtTopProd->execute([$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59']);
$topProdutos = $stmtTopProd->fetchAll();

$pageTitle = 'Faturamento Comercial — Lee Market';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
    <!-- Cabeçalho -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Faturamento Comercial</h1>
            <p class="text-sm text-gray-500">Métricas analíticas de receitas obtidas com as vendas do supermercado</p>
        </div>

        <div class="flex gap-2">
            <a href="<?= baseUrl('modules/financeiro/fluxo_caixa.php') ?>" class="btn-lee-outline text-xs">
                Ver Fluxo de Caixa
            </a>
            <a href="<?= baseUrl('modules/relatorios/vendas.php') ?>" class="btn-lee-primary text-xs shadow-sm">
                Relatório de Vendas
            </a>
        </div>
    </div>

    <!-- Filtro de Período -->
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 mb-6">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
            <div class="sm:col-span-5">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Período - Início</label>
                <input type="date" name="data_inicio" value="<?= htmlspecialchars($dataInicio) ?>"
                       class="block w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary">
            </div>

            <div class="sm:col-span-5">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Período - Fim</label>
                <input type="date" name="data_fim" value="<?= htmlspecialchars($dataFim) ?>"
                       class="block w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary">
            </div>

            <div class="sm:col-span-2">
                <button type="submit" class="btn-lee-primary w-full py-2 text-sm justify-center">
                    Calcular Faturamento
                </button>
            </div>
        </form>
    </div>

    <!-- Painel de Indicadores Financeiros (Cards) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
        <!-- Faturamento Bruto -->
        <div class="bg-white rounded-xl p-5 border-l-4 border-lee-accent shadow-sm">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Faturamento Bruto</span>
            <h3 class="text-2xl font-black text-lee-primary mt-1">R$ <?= number_format($faturamentoTotal, 2, ',', '.') ?></h3>
            <span class="text-xs text-gray-400"><?= $totalVendas ?> vendas no período</span>
        </div>

        <!-- Custo das Mercadorias -->
        <div class="bg-white rounded-xl p-5 border-l-4 border-gray-400 shadow-sm">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Custo dos Produtos (CMV)</span>
            <h3 class="text-2xl font-bold text-gray-700 mt-1">R$ <?= number_format($custoTotal, 2, ',', '.') ?></h3>
            <span class="text-xs text-gray-400">Baseado no preço de custo</span>
        </div>

        <!-- Lucro Bruto Estimado -->
        <div class="bg-white rounded-xl p-5 border-l-4 border-emerald-500 shadow-sm">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Lucro Bruto Estimado</span>
            <h3 class="text-2xl font-black text-emerald-600 mt-1">R$ <?= number_format($lucroBruto, 2, ',', '.') ?></h3>
            <span class="text-xs text-emerald-600 font-bold">Margem: <?= number_format($margemLucro, 1, ',', '.') ?>%</span>
        </div>

        <!-- Ticket Médio -->
        <div class="bg-white rounded-xl p-5 border-l-4 border-blue-500 shadow-sm">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Ticket Médio</span>
            <h3 class="text-2xl font-bold text-gray-900 mt-1">R$ <?= number_format($ticketMedio, 2, ',', '.') ?></h3>
            <span class="text-xs text-gray-400">Por transação finalizada</span>
        </div>
    </div>

    <!-- Seção de Análise: Formas de Pagamento & Top Produtos -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
        <!-- Distribuição por Forma de Pagamento -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <h2 class="text-base font-bold text-gray-900 mb-4">Faturamento por Forma de Pagamento</h2>

            <?php if (empty($pagamentos)): ?>
            <p class="text-sm text-gray-400 py-6 text-center">Nenhum pagamento registrado no período.</p>
            <?php else: ?>
            <div class="space-y-4">
                <?php foreach ($pagamentos as $pg): 
                    $perc = $faturamentoTotal > 0 ? (($pg['total_pagto'] / $faturamentoTotal) * 100) : 0;
                ?>
                <div>
                    <div class="flex justify-between text-xs font-bold text-gray-700 mb-1">
                        <span><?= htmlspecialchars($pg['forma_pagamento']) ?> (<?= $pg['qtd'] ?>x)</span>
                        <span>R$ <?= number_format((float)$pg['total_pagto'], 2, ',', '.') ?> (<?= number_format($perc, 1, ',', '.') ?>%)</span>
                    </div>
                    <div class="w-full bg-gray-100 rounded-full h-3 overflow-hidden">
                        <div class="bg-lee-primary h-3 rounded-full transition-all duration-500" style="width: <?= min(100, max(4, $perc)) ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Top Produtos Mais Faturados -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <h2 class="text-base font-bold text-gray-900 mb-4">Produtos Mais Vendidos (em Receita)</h2>

            <?php if (empty($topProdutos)): ?>
            <p class="text-sm text-gray-400 py-6 text-center">Sem dados de itens no período.</p>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead>
                        <tr class="text-left font-bold text-gray-500 uppercase">
                            <th class="pb-2">Produto</th>
                            <th class="pb-2 text-center">Qtd</th>
                            <th class="pb-2 text-right">Faturamento</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($topProdutos as $tp): ?>
                        <tr>
                            <td class="py-2.5 font-bold text-gray-900">
                                <?= htmlspecialchars($tp['nome']) ?>
                                <span class="block font-normal text-gray-400 text-[10px]"><?= htmlspecialchars($tp['categoria']) ?></span>
                            </td>
                            <td class="py-2.5 text-center font-semibold text-gray-700">
                                <?= (int)$tp['qtd_vendida'] ?> un
                            </td>
                            <td class="py-2.5 text-right font-black text-lee-primary text-sm">
                                R$ <?= number_format((float)$tp['faturamento_produto'], 2, ',', '.') ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
