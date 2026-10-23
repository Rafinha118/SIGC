<?php
/**
 * Relatório de Vendas por Período - SIGC Lee Market
 * Requisitos:
 * - Análise dos relatórios de vendas de determinado período
 * - Processamento dos dados das vendas
 * - Exibição de relatórios sintéticos, por produto, por operador e por forma de pagamento
 */

require_once __DIR__ . '/../../config/bd.php';
require_once __DIR__ . '/../../config/auth.php';

// Dono e Gerente têm acesso conforme a matriz de permissões
exigirCargo(['dono', 'gerente']);

$pdo = getDB();

$dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
$dataFim = $_GET['data_fim'] ?? date('Y-m-d');

// 1. Resumo Geral de Vendas no Período
$stmtResumo = $pdo->prepare("
    SELECT 
        COUNT(*) AS total_transacoes,
        COALESCE(SUM(total), 0) AS faturamento_total
    FROM vendas
    WHERE criado_em BETWEEN ? AND ?
");
$stmtResumo->execute([$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59']);
$resumo = $stmtResumo->fetch();

$totalTransacoes = (int)$resumo['total_transacoes'];
$faturamentoTotal = (float)$resumo['faturamento_total'];
$ticketMedio = $totalTransacoes > 0 ? ($faturamentoTotal / $totalTransacoes) : 0;

// 2. Ranking de Produtos Vendidos
$stmtProd = $pdo->prepare("
    SELECT 
        p.codigo_barras, p.nome, p.categoria,
        SUM(iv.quantidade) AS qtd_total,
        SUM(iv.subtotal) AS valor_total,
        AVG(iv.preco_unitario) AS preco_medio
    FROM itens_venda iv
    JOIN vendas v ON iv.venda_id = v.id
    JOIN produtos p ON iv.produto_id = p.id
    WHERE v.criado_em BETWEEN ? AND ?
    GROUP BY p.id, p.codigo_barras, p.nome, p.categoria
    ORDER BY qtd_total DESC
");
$stmtProd->execute([$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59']);
$produtosVendidos = $stmtProd->fetchAll();

// 3. Vendas por Operador de Caixa
$stmtOp = $pdo->prepare("
    SELECT 
        u.nome AS operador_nome, u.cpf AS operador_cpf,
        COUNT(v.id) AS qtd_atendimentos,
        SUM(v.total) AS total_operador
    FROM vendas v
    JOIN usuarios u ON v.usuario_id = u.id
    WHERE v.criado_em BETWEEN ? AND ?
    GROUP BY u.id, u.nome, u.cpf
    ORDER BY total_operador DESC
");
$stmtOp->execute([$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59']);
$vendasOperador = $stmtOp->fetchAll();

// 4. Vendas por Forma de Pagamento
$stmtPgto = $pdo->prepare("
    SELECT 
        forma_pagamento,
        COUNT(*) AS qtd,
        SUM(total) AS total_forma
    FROM vendas
    WHERE criado_em BETWEEN ? AND ?
    GROUP BY forma_pagamento
    ORDER BY total_forma DESC
");
$stmtPgto->execute([$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59']);
$vendasPgto = $stmtPgto->fetchAll();

$pageTitle = 'Relatórios de Vendas — Lee Market';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
    <!-- Cabeçalho com Botão de Impressão -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Relatórios de Vendas</h1>
            <p class="text-sm text-gray-500">Analise o desempenho comercial por período, produtos e operadores</p>
        </div>

        <button type="button" onclick="window.print()" class="btn-lee-outline text-xs py-2">
            🖨️ Imprimir Relatório
        </button>
    </div>

    <!-- Filtro de Seleção do Período -->
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 mb-6">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
            <div class="sm:col-span-5">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Data Início</label>
                <input type="date" name="data_inicio" value="<?= htmlspecialchars($dataInicio) ?>"
                       class="block w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary">
            </div>

            <div class="sm:col-span-5">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Data Fim</label>
                <input type="date" name="data_fim" value="<?= htmlspecialchars($dataFim) ?>"
                       class="block w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary">
            </div>

            <div class="sm:col-span-2">
                <button type="submit" class="btn-lee-primary w-full py-2 text-sm justify-center">
                    Gerar Relatório
                </button>
            </div>
        </form>
    </div>

    <!-- Cards Síntese do Relatório -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-8">
        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Volume Faturado</span>
            <h3 class="text-3xl font-black text-lee-primary mt-1">R$ <?= number_format($faturamentoTotal, 2, ',', '.') ?></h3>
            <span class="text-xs text-gray-400">Total no período de <?= date('d/m/Y', strtotime($dataInicio)) ?> até <?= date('d/m/Y', strtotime($dataFim)) ?></span>
        </div>

        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Atendimentos / Vendas</span>
            <h3 class="text-3xl font-bold text-gray-900 mt-1"><?= $totalTransacoes ?></h3>
            <span class="text-xs text-gray-400">Transações confirmadas</span>
        </div>

        <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Ticket Médio</span>
            <h3 class="text-3xl font-bold text-gray-900 mt-1">R$ <?= number_format($ticketMedio, 2, ',', '.') ?></h3>
            <span class="text-xs text-gray-400">Média por cupom emitido</span>
        </div>
    </div>

    <!-- Tabela 1: Produtos Vendidos -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <div class="p-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-bold uppercase tracking-wider text-gray-900">1. Desempenho por Produto</h2>
            <span class="text-xs text-gray-500"><?= count($produtosVendidos) ?> itens comercializados</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50/50">
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase">
                        <th class="px-6 py-3">Código / Nome</th>
                        <th class="px-6 py-3">Categoria</th>
                        <th class="px-6 py-3 text-center">Qtd Vendida</th>
                        <th class="px-6 py-3 text-right">Preço Médio</th>
                        <th class="px-6 py-3 text-right">Total Faturado</th>
                        <th class="px-6 py-3 text-right">% Receita</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($produtosVendidos)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-400">Nenhuma venda de produtos no período selecionado.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($produtosVendidos as $pv): 
                        $perc = $faturamentoTotal > 0 ? (($pv['valor_total'] / $faturamentoTotal) * 100) : 0;
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-3.5">
                            <div class="font-bold text-gray-900"><?= htmlspecialchars($pv['nome']) ?></div>
                            <div class="text-[11px] font-mono text-gray-400"><?= htmlspecialchars($pv['codigo_barras']) ?></div>
                        </td>
                        <td class="px-6 py-3.5 text-xs text-gray-500"><?= htmlspecialchars($pv['categoria']) ?></td>
                        <td class="px-6 py-3.5 text-center font-bold text-gray-800"><?= (int)$pv['qtd_total'] ?> un</td>
                        <td class="px-6 py-3.5 text-right text-gray-600">R$ <?= number_format((float)$pv['preco_medio'], 2, ',', '.') ?></td>
                        <td class="px-6 py-3.5 text-right font-black text-lee-primary">R$ <?= number_format((float)$pv['valor_total'], 2, ',', '.') ?></td>
                        <td class="px-6 py-3.5 text-right text-xs font-bold text-gray-500"><?= number_format($perc, 1, ',', '.') ?>%</td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Tabela 2 & 3: Operadores e Meios de Pagamento -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        <!-- Vendas por Operador -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 bg-gray-50 border-b border-gray-100">
                <h2 class="text-sm font-bold uppercase tracking-wider text-gray-900">2. Vendas por Operador de Caixa</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead>
                        <tr class="text-left font-bold text-gray-500 uppercase bg-gray-50/50">
                            <th class="px-4 py-2.5">Operador</th>
                            <th class="px-4 py-2.5 text-center">Atendimentos</th>
                            <th class="px-4 py-2.5 text-right">Total Faturado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($vendasOperador)): ?>
                        <tr><td colspan="3" class="p-4 text-center text-gray-400">Sem dados.</td></tr>
                        <?php else: ?>
                        <?php foreach ($vendasOperador as $op): ?>
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-bold text-gray-900"><?= htmlspecialchars($op['operador_nome']) ?></div>
                                <div class="text-[10px] text-gray-400"><?= htmlspecialchars($op['operador_cpf']) ?></div>
                            </td>
                            <td class="px-4 py-3 text-center font-semibold text-gray-700"><?= (int)$op['qtd_atendimentos'] ?></td>
                            <td class="px-4 py-3 text-right font-black text-gray-900">R$ <?= number_format((float)$op['total_operador'], 2, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Vendas por Meio de Pagamento -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 bg-gray-50 border-b border-gray-100">
                <h2 class="text-sm font-bold uppercase tracking-wider text-gray-900">3. Vendas por Forma de Pagamento</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-xs">
                    <thead>
                        <tr class="text-left font-bold text-gray-500 uppercase bg-gray-50/50">
                            <th class="px-4 py-2.5">Método</th>
                            <th class="px-4 py-2.5 text-center">Transações</th>
                            <th class="px-4 py-2.5 text-right">Volume (R$)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php if (empty($vendasPgto)): ?>
                        <tr><td colspan="3" class="p-4 text-center text-gray-400">Sem dados.</td></tr>
                        <?php else: ?>
                        <?php foreach ($vendasPgto as $pg): ?>
                        <tr>
                            <td class="px-4 py-3 font-bold text-gray-900"><?= htmlspecialchars($pg['forma_pagamento']) ?></td>
                            <td class="px-4 py-3 text-center font-semibold text-gray-700"><?= (int)$pg['qtd'] ?></td>
                            <td class="px-4 py-3 text-right font-black text-gray-900">R$ <?= number_format((float)$pg['total_forma'], 2, ',', '.') ?></td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
