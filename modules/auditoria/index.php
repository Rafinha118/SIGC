<?php
/**
 * Histórico de Auditoria e Logs do Sistema (CORE) - SIGC Lee Market
 * Restrito ao perfil Dono (Administrador Geral)
 * Requisitos:
 * - O sistema deve ter uma página de históricos e manter sempre os logs de qualquer alteração
 * - Histórico de auditoria de todos os setores (Autenticação, Vendas, Estoque, Produtos, Fornecedores, Financeiro, RH)
 */

require_once __DIR__ . '/../../config/bd.php';
require_once __DIR__ . '/../../config/auth.php';

exigirCargo('dono');

$pdo = getDB();

$setorFiltro = trim($_GET['setor'] ?? '');
$busca = trim($_GET['busca'] ?? '');
$dataInicio = $_GET['data_inicio'] ?? date('Y-m-d', strtotime('-30 days'));
$dataFim = $_GET['data_fim'] ?? date('Y-m-d');

$sql = "
    SELECT * 
    FROM auditoria_logs
    WHERE criado_em BETWEEN ? AND ?
";
$params = [$dataInicio . ' 00:00:00', $dataFim . ' 23:59:59'];

if ($setorFiltro !== '') {
    $sql .= " AND setor = ?";
    $params[] = $setorFiltro;
}

if ($busca !== '') {
    $sql .= " AND (usuario_nome LIKE ? OR usuario_cpf LIKE ? OR acao LIKE ? OR detalhes LIKE ?)";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

$sql .= " ORDER BY criado_em DESC LIMIT 200";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Estatísticas dos logs
$totalRegistros = count($logs);
$setoresDisponiveis = ['Autenticação', 'Vendas', 'Estoque', 'Produtos', 'Fornecedores', 'Financeiro', 'RH', 'Sistema'];

$pageTitle = 'Auditoria do Sistema — Lee Market';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
    <!-- Cabeçalho -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <div class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-800 border border-amber-200 mb-2">
                🔒 Área Restrita à Diretoria (Dono)
            </div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Histórico de Auditoria Geral</h1>
            <p class="text-sm text-gray-500">Rastreabilidade completa de todas as operações, alterações e acessos de usuários</p>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" onclick="window.print()" class="btn-lee-outline text-xs py-2">
                🖨️ Imprimir Trilha de Auditoria
            </button>
        </div>
    </div>

    <!-- Filtros de Consulta -->
    <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 mb-6">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-end">
            <div class="sm:col-span-3">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Setor do Sistema</label>
                <select name="setor" class="block w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary">
                    <option value="">Todos os Setores</option>
                    <?php foreach ($setoresDisponiveis as $s): ?>
                    <option value="<?= $s ?>" <?= $setorFiltro === $s ? 'selected' : '' ?>><?= $s ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="sm:col-span-3">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Palavra-chave / Usuário</label>
                <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" placeholder="Buscar termo, nome ou CPF..."
                       class="block w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Data Início</label>
                <input type="date" name="data_inicio" value="<?= htmlspecialchars($dataInicio) ?>"
                       class="block w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary">
            </div>

            <div class="sm:col-span-2">
                <label class="block text-xs font-semibold text-gray-600 mb-1">Data Fim</label>
                <input type="date" name="data_fim" value="<?= htmlspecialchars($dataFim) ?>"
                       class="block w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary">
            </div>

            <div class="sm:col-span-2 flex gap-1.5">
                <button type="submit" class="btn-lee-primary w-full py-2 text-sm justify-center">
                    Filtrar
                </button>
                <?php if ($setorFiltro !== '' || $busca !== ''): ?>
                <a href="<?= baseUrl('modules/auditoria/index.php') ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-100 text-sm flex items-center justify-center">
                    ✕
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabela de Logs de Auditoria -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="p-4 bg-gray-50 border-b border-gray-100 flex items-center justify-between">
            <h2 class="text-sm font-bold uppercase tracking-wider text-gray-900">Registros Localizados</h2>
            <span class="text-xs text-gray-500 font-medium"><?= $totalRegistros ?> logs exibidos</span>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-xs">
                <thead class="bg-gray-50/50">
                    <tr class="text-left font-bold text-gray-500 uppercase tracking-wider">
                        <th class="px-4 py-3">Data / Hora</th>
                        <th class="px-4 py-3">Setor</th>
                        <th class="px-4 py-3">Ação</th>
                        <th class="px-4 py-3">Usuário Responsável</th>
                        <th class="px-4 py-3">Detalhes da Alteração</th>
                        <th class="px-4 py-3">IP</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php if (empty($logs)): ?>
                    <tr>
                        <td colspan="6" class="px-4 py-8 text-center text-gray-400">
                            Nenhum registro de auditoria corresponde aos critérios informados.
                        </td>
                    </tr>
                    <?php else: ?>
                    <?php foreach ($logs as $log): 
                        $badgeSetor = [
                            'Autenticação' => 'bg-purple-100 text-purple-800',
                            'Vendas' => 'bg-emerald-100 text-emerald-800',
                            'Estoque' => 'bg-amber-100 text-amber-800',
                            'Produtos' => 'bg-blue-100 text-lee-primary',
                            'Fornecedores' => 'bg-indigo-100 text-indigo-800',
                            'Financeiro' => 'bg-rose-100 text-rose-800',
                            'RH' => 'bg-teal-100 text-teal-800',
                        ][$log['setor']] ?? 'bg-gray-100 text-gray-800';
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 text-gray-500 whitespace-nowrap font-mono">
                            <?= date('d/m/Y H:i:s', strtotime($log['criado_em'])) ?>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <span class="inline-flex px-2 py-0.5 rounded-full font-bold <?= $badgeSetor ?>">
                                <?= htmlspecialchars($log['setor']) ?>
                            </span>
                        </td>
                        <td class="px-4 py-3 font-bold text-gray-900 whitespace-nowrap">
                            <?= htmlspecialchars($log['acao']) ?>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="font-bold text-gray-900"><?= htmlspecialchars($log['usuario_nome'] ?: 'Sistema / Anônimo') ?></div>
                            <div class="text-[10px] text-gray-400"><?= htmlspecialchars($log['usuario_cpf'] ?: '-') ?> (<?= htmlspecialchars($log['usuario_cargo'] ?: '-') ?>)</div>
                        </td>
                        <td class="px-4 py-3 text-gray-700 max-w-md break-words">
                            <?= htmlspecialchars($log['detalhes']) ?>
                        </td>
                        <td class="px-4 py-3 text-gray-400 font-mono text-[11px] whitespace-nowrap">
                            <?= htmlspecialchars($log['ip_address'] ?: '127.0.0.1') ?>
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
