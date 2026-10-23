<?php
/**
 * Histórico de Vendas - SIGC Lee Market
 * Requisitos:
 * - Consulta das vendas realizadas
 * - Filtro por período e forma de pagamento
 * - Análise de totais e ticket médio
 */

require_once __DIR__ . '/../../config/bd.php';
require_once __DIR__ . '/../../config/auth.php';

exigirAutenticacao();

$user = usuarioAtual();
$cargo = strtolower($user['cargo']);
$pdo = getDB();

$dataInicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-7 days'));
$dataFim = $_GET['data_fim'] ?? date('Y-m-d');
$formaPagto = trim($_GET['forma_pagamento'] ?? '');

$sql = "
    SELECT v.*, u.nome AS operador_nome,
           (SELECT COUNT(*) FROM itens_venda iv WHERE iv.venda_id = v.id) AS total_itens
    FROM vendas v
    JOIN usuarios u ON v.usuario_id = u.id
    WHERE v.criado_em BETWEEN ? AND ?
";
$params = [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59'];

// Se for operador de caixa, limita às suas próprias vendas por padrão, a não ser que tenha permissão
if ($cargo === 'caixa') {
    $sql .= " AND v.usuario_id = ?";
    $params[] = $user['id'];
}

if ($formaPagto !== '') {
    $sql .= " AND v.forma_pagamento = ?";
    $params[] = $formaPagto;
}

$sql .= " ORDER BY v.criado_em DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$vendas = $stmt->fetchAll();

// Métricas do período filtrado
$somaTotal = array_sum(array_column($vendas, 'total'));
$qtdTotal = count($vendas);
$ticketMedio = $qtdTotal > 0 ? ($somaTotal / $qtdTotal) : 0;

$pageTitle = 'Histórico de Vendas — Lee Market';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Histórico de Vendas</h1>
            <p class="text-sm text-gray-500">Consulte todas as transações finalizadas nos terminais de atendimento</p>
        </div>

        <?php if ($cargo === 'caixa' || $cargo === 'dono'): ?>
        <div>
            <a href="<?= baseUrl('modules/vendas/pdv.php') ?>" class="btn-lee-accent text-sm">
                Abrir Frente de Caixa (PDV)
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Cards de Resumo do Período -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
        <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Faturamento no Período</span>
            <h3 class="text-2xl font-black text-lee-primary mt-1">R$ <?= number_format($somaTotal, 2, ',', '.') ?></h3>
            <span class="text-xs text-gray-400">Total acumulado das vendas</span>
        </div>

        <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Quantidade de Vendas</span>
            <h3 class="text-2xl font-bold text-gray-900 mt-1"><?= $qtdTotal ?></h3>
            <span class="text-xs text-gray-400">Cupons emitidos</span>
        </div>

        <div class="bg-white rounded-xl p-5 border border-gray-100 shadow-sm">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Ticket Médio</span>
            <h3 class="text-2xl font-bold text-gray-900 mt-1">R$ <?= number_format($ticketMedio, 2, ',', '.') ?></h3>
            <span class="text-xs text-gray-400">Gasto médio por cliente</span>
        </div>
    </div>

    <!-- Filtros de Período -->
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 mb-6">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
            <div class="sm:col-span-4">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Data Início</label>
                <input type="date" name="data_inicio" value="<?= htmlspecialchars($dataInicio) ?>"
                       class="block w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary">
            </div>

            <div class="sm:col-span-4">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Data Fim</label>
                <input type="date" name="data_fim" value="<?= htmlspecialchars($dataFim) ?>"
                       class="block w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary">
            </div>

            <div class="sm:col-span-3">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Forma de Pagamento</label>
                <select name="forma_pagamento" class="block w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary">
                    <option value="">Todas</option>
                    <option value="Dinheiro" <?= $formaPagto === 'Dinheiro' ? 'selected' : '' ?>>Dinheiro</option>
                    <option value="PIX" <?= $formaPagto === 'PIX' ? 'selected' : '' ?>>PIX</option>
                    <option value="Cartão de Débito" <?= $formaPagto === 'Cartão de Débito' ? 'selected' : '' ?>>Cartão de Débito</option>
                    <option value="Cartão de Crédito" <?= $formaPagto === 'Cartão de Crédito' ? 'selected' : '' ?>>Cartão de Crédito</option>
                </select>
            </div>

            <div class="sm:col-span-1">
                <button type="submit" class="btn-lee-primary w-full py-2 text-sm justify-center">
                    Filtrar
                </button>
            </div>
        </form>
    </div>

    <!-- Tabela de Vendas -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-3.5">Código / Data</th>
                        <th class="px-6 py-3.5">Operador</th>
                        <th class="px-6 py-3.5 text-center">Itens</th>
                        <th class="px-6 py-3.5">Forma Pagto</th>
                        <th class="px-6 py-3.5 text-right">Total Venda</th>
                        <th class="px-6 py-3.5 text-right">Ação</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($vendas)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                            Nenhuma venda registrada no período selecionado.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($vendas as $v): ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-bold font-mono text-gray-900"><?= htmlspecialchars($v['codigo_venda']) ?></div>
                            <div class="text-xs text-gray-500"><?= date('d/m/Y H:i', strtotime($v['criado_em'])) ?></div>
                        </td>
                        <td class="px-6 py-4 text-gray-700 font-medium">
                            <?= htmlspecialchars($v['operador_nome']) ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-lee-primary">
                                <?= (int)$v['total_itens'] ?> produto(s)
                            </span>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-800">
                                <?= htmlspecialchars($v['forma_pagamento']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right font-extrabold text-base text-gray-900">
                            R$ <?= number_format((float)$v['total'], 2, ',', '.') ?>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <a href="<?= baseUrl('modules/vendas/detalhe.php?id=' . $v['id']) ?>" 
                               class="btn-lee-outline py-1 px-3 text-xs">
                                Ver Recibo
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
