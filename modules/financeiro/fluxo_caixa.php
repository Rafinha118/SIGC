<?php
/**
 * Fluxo de Caixa - SIGC Lee Market
 * Requisitos:
 * - Acompanhamento das entradas e saídas financeiras
 * - Cálculo automático dos valores e saldo do período
 * - Filtro por data e categoria
 * - Lançamento de movimentações manuais (sangria, suprimento, despesas)
 */

require_once __DIR__ . '/../../config/bd.php';
require_once __DIR__ . '/../../config/auth.php';

exigirCargo('dono');

$pdo = getDB();
$user = usuarioAtual();

$dataInicio = $_GET['data_inicio'] ?? date('Y-m-01');
$dataFim = $_GET['data_fim'] ?? date('Y-m-d');
$filtroTipo = $_GET['tipo'] ?? '';

$sql = "
    SELECT fc.*, u.nome AS usuario_nome
    FROM fluxo_caixa fc
    JOIN usuarios u ON fc.usuario_id = u.id
    WHERE fc.data_movimento BETWEEN ? AND ?
";
$params = [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59'];

if ($filtroTipo !== '') {
    $sql .= " AND fc.tipo = ?";
    $params[] = $filtroTipo;
}

$sql .= " ORDER BY fc.data_movimento DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$movimentacoes = $stmt->fetchAll();

// Totais de entradas e saídas
$totalEntradas = 0.0;
$totalSaidas = 0.0;

foreach ($movimentacoes as $m) {
    if ($m['tipo'] === 'ENTRADA') {
        $totalEntradas += (float)$m['valor'];
    } else {
        $totalSaidas += (float)$m['valor'];
    }
}
$saldoPeriodo = $totalEntradas - $totalSaidas;

$pageTitle = 'Fluxo de Caixa — Lee Market';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Fluxo de Caixa</h1>
            <p class="text-sm text-gray-500">Controle completo de entradas, saídas e saldo financeiro do supermercado</p>
        </div>

        <div>
            <button type="button" onclick="abrirModalMovimentacao()" class="btn-lee-primary text-sm shadow-sm">
                + Nova Movimentação Financeira
            </button>
        </div>
    </div>

    <!-- Cards de Totais Financeiros -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 mb-6">
        <!-- Entradas -->
        <div class="bg-white rounded-xl p-5 border border-emerald-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12"></path></svg>
            </div>
            <div>
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Entradas</span>
                <h3 class="text-2xl font-bold text-emerald-600">R$ <?= number_format($totalEntradas, 2, ',', '.') ?></h3>
                <span class="text-xs text-gray-400">Vendas e suprimentos</span>
            </div>
        </div>

        <!-- Saídas -->
        <div class="bg-white rounded-xl p-5 border border-rose-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6"></path></svg>
            </div>
            <div>
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Total Saídas</span>
                <h3 class="text-2xl font-bold text-rose-600">R$ <?= number_format($totalSaidas, 2, ',', '.') ?></h3>
                <span class="text-xs text-gray-400">Despesas, sangrias e contas</span>
            </div>
        </div>

        <!-- Saldo Líquido -->
        <div class="bg-white rounded-xl p-5 border border-blue-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-lee-primary flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Saldo do Período</span>
                <h3 class="text-2xl font-black <?= $saldoPeriodo >= 0 ? 'text-lee-primary' : 'text-rose-600' ?>">
                    R$ <?= number_format($saldoPeriodo, 2, ',', '.') ?>
                </h3>
                <span class="text-xs font-semibold <?= $saldoPeriodo >= 0 ? 'text-emerald-600' : 'text-rose-600' ?>">
                    <?= $saldoPeriodo >= 0 ? 'Superávit no período' : 'Déficit no período' ?>
                </span>
            </div>
        </div>
    </div>

    <!-- Filtros de Consulta -->
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
                <label class="block text-xs font-semibold text-gray-600 mb-1">Tipo de Movimento</label>
                <select name="tipo" class="block w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary">
                    <option value="">Todos</option>
                    <option value="ENTRADA" <?= $filtroTipo === 'ENTRADA' ? 'selected' : '' ?>>Apenas Entradas</option>
                    <option value="SAIDA" <?= $filtroTipo === 'SAIDA' ? 'selected' : '' ?>>Apenas Saídas</option>
                </select>
            </div>

            <div class="sm:col-span-1">
                <button type="submit" class="btn-lee-primary w-full py-2 text-sm justify-center">
                    Filtrar
                </button>
            </div>
        </form>
    </div>

    <!-- Tabela de Movimentações Financeiras -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-3.5">Data/Hora</th>
                        <th class="px-6 py-3.5">Tipo</th>
                        <th class="px-6 py-3.5">Categoria</th>
                        <th class="px-6 py-3.5">Descrição / Justificativa</th>
                        <th class="px-6 py-3.5">Responsável</th>
                        <th class="px-6 py-3.5 text-right">Valor</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($movimentacoes)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-400">
                            Nenhuma movimentação financeira encontrada para o período selecionado.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($movimentacoes as $m): 
                        $isEntrada = $m['tipo'] === 'ENTRADA';
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 text-gray-500 whitespace-nowrap text-xs">
                            <?= date('d/m/Y H:i', strtotime($m['data_movimento'])) ?>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold <?= $isEntrada ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' ?>">
                                <?= $m['tipo'] ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 font-semibold text-gray-800 text-xs">
                            <?= htmlspecialchars($m['categoria']) ?>
                        </td>
                        <td class="px-6 py-4 text-gray-600">
                            <?= htmlspecialchars($m['descricao']) ?>
                        </td>
                        <td class="px-6 py-4 text-gray-600 text-xs">
                            <?= htmlspecialchars($m['usuario_nome']) ?>
                        </td>
                        <td class="px-6 py-4 text-right font-extrabold text-base whitespace-nowrap <?= $isEntrada ? 'text-emerald-600' : 'text-rose-600' ?>">
                            <?= $isEntrada ? '+' : '-' ?> R$ <?= number_format((float)$m['valor'], 2, ',', '.') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<!-- Modal de Lançamento Manual de Movimentação Financeira -->
<div id="modal-movimentacao" class="lee-modal-overlay">
    <div class="lee-modal-box">
        <form action="<?= baseUrl('modules/financeiro/acoes.php?acao=lancar_movimento') ?>" method="POST" class="p-6">
            <h3 class="text-lg font-bold text-gray-900 mb-4 pb-2 border-b">Nova Movimentação Financeira</h3>

            <div class="space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Tipo de Movimento *</label>
                        <select name="tipo" id="modal_tipo" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-bold">
                            <option value="ENTRADA">Entrada (Suprimento)</option>
                            <option value="SAIDA">Saída (Despesa / Sangria)</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Categoria *</label>
                        <select name="categoria" id="modal_categoria" required class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm font-semibold">
                            <option value="Suprimento">Suprimento de Caixa</option>
                            <option value="Sangria">Sangria de Caixa</option>
                            <option value="Despesa Operacional">Despesa Operacional</option>
                            <option value="Pagamento Fornecedor">Pagamento de Fornecedor</option>
                            <option value="Água / Luz / Internet">Água / Luz / Internet</option>
                            <option value="Manutenção">Manutenção e Reparos</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Valor (R$) *</label>
                    <input type="number" step="0.01" min="0.01" name="valor" required placeholder="0.00"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-lg font-bold text-gray-900 focus:ring-2 focus:ring-lee-primary">
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 uppercase mb-1">Descrição / Justificativa *</label>
                    <input type="text" name="descricao" required placeholder="Ex: Pagamento conta de energia elétrica mês 10"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary">
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-5 mt-5 border-t">
                <button type="button" onclick="fecharModalMovimentacao()" class="px-4 py-2 border border-gray-300 rounded-lg text-sm text-gray-700 font-semibold hover:bg-gray-100">
                    Cancelar
                </button>
                <button type="submit" class="btn-lee-accent text-sm">
                    Confirmar Lançamento
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModalMovimentacao() {
        document.getElementById('modal-movimentacao').classList.add('active');
    }
    function fecharModalMovimentacao() {
        document.getElementById('modal-movimentacao').classList.remove('active');
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
