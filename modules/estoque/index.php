<?php
/**
 * Gerenciamento e Controle de Estoque - SIGC Lee Market
 * Requisitos:
 * - Acompanhamento e gerenciamento dos produtos em estoque
 * - Alerta de produtos com estoque baixo
 * - Ações para registrar saída (avarias, perdas) e registrar entradas/ajustes
 */

require_once __DIR__ . '/../../config/bd.php';
require_once __DIR__ . '/../../config/auth.php';

exigirAutenticacao();

$user = usuarioAtual();
$cargo = strtolower($user['cargo']);
$pdo = getDB();

$filtroStatus = $_GET['status'] ?? '';
$busca = trim($_GET['busca'] ?? '');

$sql = "
    SELECT p.*, f.nome AS fornecedor_nome 
    FROM produtos p
    LEFT JOIN fornecedores f ON p.fornecedor_id = f.id
    WHERE p.ativo = 1
";
$params = [];

if ($busca !== '') {
    $sql .= " AND (p.nome LIKE ? OR p.codigo_barras LIKE ?)";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

if ($filtroStatus === 'baixo') {
    $sql .= " AND p.estoque_atual <= p.estoque_minimo";
} elseif ($filtroStatus === 'zerado') {
    $sql .= " AND p.estoque_atual = 0";
}

$sql .= " ORDER BY (p.estoque_atual <= p.estoque_minimo) DESC, p.estoque_atual ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produtos = $stmt->fetchAll();

// Histórico recente de movimentações
$movimentacoes = $pdo->query("
    SELECT m.*, p.nome AS produto_nome, u.nome AS usuario_nome
    FROM movimentacoes_estoque m
    JOIN produtos p ON m.produto_id = p.id
    JOIN usuarios u ON m.usuario_id = u.id
    ORDER BY m.criado_em DESC
    LIMIT 10
")->fetchAll();

$pageTitle = 'Controle de Estoque — Lee Market';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
    <!-- Cabeçalho com Ações Rápidas -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Gerenciamento de Estoque</h1>
            <p class="text-sm text-gray-500">Monitore as quantidades em tempo real e realize movimentações operacionais</p>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            <!-- Qualquer perfil autorizado (Caixa, Gerente, Dono) pode registrar saída avulsa -->
            <a href="<?= baseUrl('modules/estoque/saida.php') ?>" class="btn-lee-outline text-sm">
                <svg class="w-4 h-4 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Registrar Saída (Avaria / Perda)
            </a>

            <?php if ($cargo === 'gerente' || $cargo === 'dono'): ?>
            <a href="<?= baseUrl('modules/estoque/entrada.php') ?>" class="btn-lee-primary text-sm shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Registrar Entrada / Ajuste
            </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filtros de Busca -->
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 mb-6">
        <form method="GET" class="flex flex-col sm:flex-row items-center gap-3">
            <div class="relative flex-1 w-full">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" 
                       placeholder="Buscar por produto ou código de barras..."
                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary focus:border-lee-primary">
            </div>

            <div class="flex items-center gap-2 w-full sm:w-auto">
                <a href="<?= baseUrl('modules/estoque/index.php?status=baixo') ?>" 
                   class="px-3 py-2 rounded-lg text-xs font-bold border transition-colors <?= $filtroStatus === 'baixo' ? 'bg-amber-100 border-amber-300 text-amber-800' : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100' ?>">
                    Abaixo do Mínimo
                </a>
                <a href="<?= baseUrl('modules/estoque/index.php?status=zerado') ?>" 
                   class="px-3 py-2 rounded-lg text-xs font-bold border transition-colors <?= $filtroStatus === 'zerado' ? 'bg-red-100 border-red-300 text-red-800' : 'bg-gray-50 border-gray-200 text-gray-600 hover:bg-gray-100' ?>">
                    Esgotados (0)
                </a>
                <button type="submit" class="btn-lee-primary py-2 px-4 text-xs font-bold">
                    Pesquisar
                </button>
            </div>
        </form>
    </div>

    <!-- Tabela de Posição de Estoque -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden mb-8">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-base font-bold text-gray-900">Posição Atual de Produtos em Estoque</h2>
            <span class="text-xs text-gray-500 font-medium"><?= count($produtos) ?> itens listados</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-3.5">Produto</th>
                        <th class="px-6 py-3.5">Categoria</th>
                        <th class="px-6 py-3.5 text-center">Estoque Atual</th>
                        <th class="px-6 py-3.5 text-center">Estoque Mínimo</th>
                        <th class="px-6 py-3.5 text-center">Status</th>
                        <th class="px-6 py-3.5 text-right">Ação Rápida</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($produtos)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-8 text-center text-gray-500">Nenhum registro encontrado.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($produtos as $p): 
                        $qtd = (int)$p['estoque_atual'];
                        $min = (int)$p['estoque_minimo'];
                        $statusClass = $qtd === 0 ? 'bg-red-100 text-red-700 border-red-300' : ($qtd <= $min ? 'bg-amber-100 text-amber-800 border-amber-300' : 'bg-emerald-100 text-emerald-800 border-emerald-300');
                        $statusText = $qtd === 0 ? 'Esgotado' : ($qtd <= $min ? 'Estoque Baixo' : 'Normal');
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900"><?= htmlspecialchars($p['nome']) ?></div>
                            <div class="text-xs font-mono text-gray-500"><?= htmlspecialchars($p['codigo_barras']) ?></div>
                        </td>
                        <td class="px-6 py-4 text-gray-600 text-xs">
                            <?= htmlspecialchars($p['categoria']) ?>
                        </td>
                        <td class="px-6 py-4 text-center font-extrabold text-base text-gray-900">
                            <?= $qtd ?>
                        </td>
                        <td class="px-6 py-4 text-center text-gray-500 text-xs font-medium">
                            <?= $min ?>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border <?= $statusClass ?>">
                                <?= $statusText ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right space-x-1">
                            <a href="<?= baseUrl('modules/estoque/saida.php?produto_id=' . $p['id']) ?>" 
                               class="inline-block px-2.5 py-1 rounded bg-rose-50 hover:bg-rose-100 text-rose-700 text-xs font-bold transition-colors">
                                Dar Saída
                            </a>
                            <?php if ($cargo === 'gerente' || $cargo === 'dono'): ?>
                            <a href="<?= baseUrl('modules/estoque/entrada.php?produto_id=' . $p['id']) ?>" 
                               class="inline-block px-2.5 py-1 rounded bg-emerald-50 hover:bg-emerald-100 text-emerald-700 text-xs font-bold transition-colors">
                                Repor
                            </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Histórico Recente de Movimentações (Auditoria Operacional) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
        <h2 class="text-base font-bold text-gray-900 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-lee-primary" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            Histórico das Últimas Movimentações de Estoque
        </h2>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-xs">
                <thead class="bg-gray-50">
                    <tr class="text-left font-semibold text-gray-500 uppercase">
                        <th class="px-4 py-2.5">Data/Hora</th>
                        <th class="px-4 py-2.5">Produto</th>
                        <th class="px-4 py-2.5">Tipo</th>
                        <th class="px-4 py-2.5 text-center">Qtd</th>
                        <th class="px-4 py-2.5 text-center">Antes &rarr; Depois</th>
                        <th class="px-4 py-2.5">Operador</th>
                        <th class="px-4 py-2.5">Motivo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($movimentacoes)): ?>
                    <tr>
                        <td colspan="7" class="px-4 py-4 text-center text-gray-400">Nenhuma movimentação registrada.</td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($movimentacoes as $m): 
                        $isEntrada = strpos($m['tipo'], 'ENTRADA') !== false;
                    ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-2.5 text-gray-500 whitespace-nowrap"><?= date('d/m/Y H:i', strtotime($m['criado_em'])) ?></td>
                        <td class="px-4 py-2.5 font-bold text-gray-900"><?= htmlspecialchars($m['produto_nome']) ?></td>
                        <td class="px-4 py-2.5">
                            <span class="inline-flex px-2 py-0.5 rounded text-[11px] font-bold <?= $isEntrada ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800' ?>">
                                <?= htmlspecialchars($m['tipo']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-center font-bold <?= $isEntrada ? 'text-emerald-600' : 'text-rose-600' ?>">
                            <?= $isEntrada ? '+' : '-' ?><?= (int)$m['quantidade'] ?>
                        </td>
                        <td class="px-4 py-2.5 text-center text-gray-600 font-mono">
                            <?= (int)$m['quantidade_anterior'] ?> &rarr; <?= (int)$m['quantidade_nova'] ?>
                        </td>
                        <td class="px-4 py-2.5 text-gray-700"><?= htmlspecialchars($m['usuario_nome']) ?></td>
                        <td class="px-4 py-2.5 text-gray-500 italic"><?= htmlspecialchars($m['motivo'] ?: 'Sem observação') ?></td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
