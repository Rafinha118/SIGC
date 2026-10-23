<?php
/**
 * Gestão e Consulta de Fornecedores - SIGC Lee Market
 * Requisitos:
 * - Consulta das informações dos fornecedores (Nome, CNPJ, Telefone, E-mail, Endereço)
 * - Consulta dos produtos fornecidos por cada parceiro
 * - Pesquisa em tempo real de fornecedores
 */

require_once __DIR__ . '/../../config/bd.php';
require_once __DIR__ . '/../../config/auth.php';

// Acesso para Dono e Gerente
exigirCargo(['dono', 'gerente']);

$user = usuarioAtual();
$cargo = strtolower($user['cargo']);
$pdo = getDB();

$busca = trim($_GET['busca'] ?? '');
$fornecedorSelecionadoId = (int)($_GET['fornecedor_id'] ?? 0);

$query = "
    SELECT f.*, 
           (SELECT COUNT(*) FROM produtos p WHERE p.fornecedor_id = f.id AND p.ativo = 1) AS total_produtos
    FROM fornecedores f
";
$params = [];

if ($busca !== '') {
    $query .= " WHERE f.nome LIKE ? OR f.cnpj LIKE ? OR f.email LIKE ?";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

$query .= " ORDER BY f.nome ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$fornecedores = $stmt->fetchAll();

// Se nenhum fornecedor estiver selecionado e houver fornecedores, seleciona o primeiro
if ($fornecedorSelecionadoId === 0 && !empty($fornecedores)) {
    $fornecedorSelecionadoId = (int)$fornecedores[0]['id'];
}

// Busca detalhes e produtos do fornecedor selecionado
$fornecedorAtivo = null;
$produtosFornecidos = [];

if ($fornecedorSelecionadoId > 0) {
    foreach ($fornecedores as $f) {
        if ((int)$f['id'] === $fornecedorSelecionadoId) {
            $fornecedorAtivo = $f;
            break;
        }
    }

    if ($fornecedorAtivo) {
        $stmtProd = $pdo->prepare("
            SELECT id, codigo_barras, nome, categoria, preco_venda, estoque_atual 
            FROM produtos 
            WHERE fornecedor_id = ? AND ativo = 1 
            ORDER BY nome ASC
        ");
        $stmtProd->execute([$fornecedorSelecionadoId]);
        $produtosFornecidos = $stmtProd->fetchAll();
    }
}

$pageTitle = 'Fornecedores — Lee Market';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Fornecedores & Catálogo Fornecido</h1>
            <p class="text-sm text-gray-500">Consulte contatos corporativos e os produtos relacionados a cada distribuidor</p>
        </div>

        <?php if ($cargo === 'dono'): ?>
        <div>
            <a href="<?= baseUrl('modules/fornecedores/form.php') ?>" class="btn-lee-primary shadow-sm text-sm">
                + Novo Fornecedor
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Filtro de Busca -->
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 mb-6">
        <form method="GET" class="flex gap-3">
            <div class="relative flex-1">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" 
                       placeholder="Pesquisar fornecedor por razão social, CNPJ ou e-mail..."
                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary focus:border-lee-primary">
            </div>
            <button type="submit" class="btn-lee-primary px-5 py-2 text-sm">
                Buscar
            </button>
            <?php if ($busca !== ''): ?>
            <a href="<?= baseUrl('modules/fornecedores/index.php') ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-100 text-sm flex items-center justify-center">
                ✕
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Layout Master-Detail: Lista à esquerda, Detalhes e Produtos à direita -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Lista de Fornecedores (5 cols) -->
        <div class="lg:col-span-5 bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="p-4 border-b border-gray-100 bg-gray-50 flex items-center justify-between">
                <span class="text-xs font-bold uppercase tracking-wider text-gray-500">Parceiros Cadastrados</span>
                <span class="text-xs font-bold bg-blue-100 text-lee-primary px-2 py-0.5 rounded-full"><?= count($fornecedores) ?></span>
            </div>

            <?php if (empty($fornecedores)): ?>
            <div class="p-8 text-center text-gray-400 text-sm">
                Nenhum fornecedor encontrado.
            </div>
            <?php else: ?>
            <div class="divide-y divide-gray-100 max-h-[600px] overflow-y-auto">
                <?php foreach ($fornecedores as $f): 
                    $isSelected = ($fornecedorAtivo && $fornecedorAtivo['id'] == $f['id']);
                ?>
                <a href="<?= baseUrl('modules/fornecedores/index.php?fornecedor_id=' . $f['id'] . ($busca !== '' ? '&busca=' . urlencode($busca) : '')) ?>"
                   class="block p-4 transition-colors <?= $isSelected ? 'bg-blue-50/80 border-l-4 border-lee-primary' : 'hover:bg-gray-50' ?>">
                    <div class="flex items-start justify-between">
                        <div class="font-bold text-gray-900 text-sm leading-tight"><?= htmlspecialchars($f['nome']) ?></div>
                        <span class="text-[11px] font-bold px-2 py-0.5 rounded bg-gray-100 text-gray-600 whitespace-nowrap ml-2">
                            <?= (int)$f['total_produtos'] ?> itens
                        </span>
                    </div>
                    <div class="text-xs text-gray-500 mt-1 flex items-center gap-1">
                        <span>📞 <?= htmlspecialchars($f['telefone']) ?></span>
                    </div>
                    <div class="text-[11px] text-gray-400 mt-0.5 font-mono">
                        CNPJ: <?= htmlspecialchars($f['cnpj'] ?: 'Não informado') ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Detalhes do Fornecedor e Produtos Fornecidos (7 cols) -->
        <div class="lg:col-span-7 bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <?php if (!$fornecedorAtivo): ?>
            <div class="p-12 text-center text-gray-400">
                Selecione um fornecedor na lista ao lado para ver contatos e produtos.
            </div>
            <?php else: ?>
            <!-- Card de Dados do Fornecedor -->
            <div class="pb-6 border-b border-gray-200">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-4">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-lee-primary">Ficha do Fornecedor</span>
                        <h2 class="text-xl font-black text-gray-900"><?= htmlspecialchars($fornecedorAtivo['nome']) ?></h2>
                    </div>

                    <?php if ($cargo === 'dono'): ?>
                    <div class="flex items-center gap-2">
                        <a href="<?= baseUrl('modules/fornecedores/form.php?id=' . $fornecedorAtivo['id']) ?>" class="btn-lee-outline text-xs py-1.5 px-3">
                            Editar Dados
                        </a>
                        <button type="button" onclick="confirmarExclusaoFornecedor(<?= $fornecedorAtivo['id'] ?>, '<?= htmlspecialchars(addslashes($fornecedorAtivo['nome'])) ?>')" class="px-3 py-1.5 rounded-lg border border-rose-300 text-rose-700 hover:bg-rose-50 text-xs font-bold transition-colors">
                            Excluir
                        </button>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs bg-gray-50 p-4 rounded-xl">
                    <div>
                        <span class="block text-gray-400 font-bold uppercase">CNPJ</span>
                        <span class="font-mono font-bold text-gray-800 text-sm"><?= htmlspecialchars($fornecedorAtivo['cnpj'] ?: 'Não informado') ?></span>
                    </div>
                    <div>
                        <span class="block text-gray-400 font-bold uppercase">Telefone de Contato</span>
                        <span class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($fornecedorAtivo['telefone']) ?></span>
                    </div>
                    <div>
                        <span class="block text-gray-400 font-bold uppercase">E-mail Comercial</span>
                        <span class="font-bold text-gray-800 text-sm"><?= htmlspecialchars($fornecedorAtivo['email']) ?></span>
                    </div>
                    <div>
                        <span class="block text-gray-400 font-bold uppercase">Endereço Operacional</span>
                        <span class="text-gray-700"><?= htmlspecialchars($fornecedorAtivo['endereco'] ?: 'Não informado') ?></span>
                    </div>
                </div>
            </div>

            <!-- Lista de Produtos Fornecidos -->
            <div class="mt-6">
                <h3 class="text-sm font-bold uppercase tracking-wider text-gray-900 mb-3 flex items-center justify-between">
                    <span>Produtos Relacionados a Este Fornecedor</span>
                    <span class="text-xs text-gray-400 font-normal"><?= count($produtosFornecidos) ?> produtos catalogados</span>
                </h3>

                <?php if (empty($produtosFornecidos)): ?>
                <div class="p-6 bg-amber-50 rounded-xl text-center text-amber-800 text-xs font-medium">
                    Nenhum produto cadastrado está atualmente vinculado a este fornecedor.
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-xs">
                        <thead class="bg-gray-50">
                            <tr class="text-left font-bold text-gray-500 uppercase">
                                <th class="py-2.5 px-3">Produto</th>
                                <th class="py-2.5 px-3">Categoria</th>
                                <th class="py-2.5 px-3 text-right">Preço de Venda</th>
                                <th class="py-2.5 px-3 text-center">Estoque</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($produtosFornecidos as $prod): ?>
                            <tr class="hover:bg-gray-50">
                                <td class="py-2.5 px-3">
                                    <div class="font-bold text-gray-900"><?= htmlspecialchars($prod['nome']) ?></div>
                                    <div class="text-[10px] text-gray-400 font-mono"><?= htmlspecialchars($prod['codigo_barras']) ?></div>
                                </td>
                                <td class="py-2.5 px-3 text-gray-500"><?= htmlspecialchars($prod['categoria']) ?></td>
                                <td class="py-2.5 px-3 text-right font-bold text-gray-900">
                                    R$ <?= number_format((float)$prod['preco_venda'], 2, ',', '.') ?>
                                </td>
                                <td class="py-2.5 px-3 text-center">
                                    <span class="px-2 py-0.5 rounded-full font-bold <?= (int)$prod['estoque_atual'] <= 5 ? 'bg-red-100 text-red-700' : 'bg-emerald-100 text-emerald-700' ?>">
                                        <?= (int)$prod['estoque_atual'] ?> un
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</main>

<script>
    function confirmarExclusaoFornecedor(id, nome) {
        LeeModal.confirm(
            'Excluir Fornecedor',
            `Deseja realmente remover o parceiro "<strong>${nome}</strong>"? Caso possua produtos vinculados, os vínculos serão desfeitos.`,
            function() {
                window.location.href = `<?= baseUrl('modules/fornecedores/acoes.php?acao=excluir&id=') ?>` + id;
            },
            null,
            'Sim, Excluir Fornecedor',
            'Cancelar'
        );
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
