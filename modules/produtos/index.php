<?php
/**
 * Catálogo e Consulta de Produtos - SIGC Lee Market
 * Requisitos:
 * - Consulta dos produtos e quantidades disponíveis
 * - Pesquisa em tempo real por nome ou código de barras
 * - Ações específicas: Dono altera preço, edita e remove produtos com modal Lee Market
 */

require_once __DIR__ . '/../../config/bd.php';
require_once __DIR__ . '/../../config/auth.php';

exigirAutenticacao();

$user = usuarioAtual();
$cargo = strtolower($user['cargo']);
$pdo = getDB();

$busca = trim($_GET['busca'] ?? '');
$categoriaFiltro = trim($_GET['categoria'] ?? '');

$query = "
    SELECT p.*, f.nome AS fornecedor_nome 
    FROM produtos p
    LEFT JOIN fornecedores f ON p.fornecedor_id = f.id
    WHERE p.ativo = 1
";
$params = [];

if ($busca !== '') {
    $query .= " AND (p.nome LIKE ? OR p.codigo_barras LIKE ?)";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

if ($categoriaFiltro !== '') {
    $query .= " AND p.categoria = ?";
    $params[] = $categoriaFiltro;
}

$query .= " ORDER BY p.nome ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$produtos = $stmt->fetchAll();

// Categorias para filtro
$categorias = $pdo->query("SELECT DISTINCT categoria FROM produtos WHERE ativo = 1 ORDER BY categoria ASC")->fetchAll(PDO::FETCH_COLUMN);

$pageTitle = 'Catálogo de Produtos — Lee Market';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
    <!-- Cabeçalho do Módulo -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Produtos & Estoque</h1>
            <p class="text-sm text-gray-500">Consulta de preços, quantidades disponíveis e catálogo de itens</p>
        </div>

        <?php if ($cargo === 'dono'): ?>
        <div>
            <a href="<?= baseUrl('modules/produtos/form.php') ?>" class="btn-lee-primary shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                Cadastrar Novo Produto
            </a>
        </div>
        <?php endif; ?>
    </div>

    <!-- Filtros e Busca Rápida -->
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 mb-6">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <div class="sm:col-span-6 relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" 
                       placeholder="Pesquisar por nome ou código de barras..."
                       class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary focus:border-lee-primary">
            </div>

            <div class="sm:col-span-4">
                <select name="categoria" class="block w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary focus:border-lee-primary">
                    <option value="">Todas as categorias</option>
                    <?php foreach ($categorias as $cat): ?>
                    <option value="<?= htmlspecialchars($cat) ?>" <?= $categoriaFiltro === $cat ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="sm:col-span-2 flex gap-2">
                <button type="submit" class="btn-lee-primary w-full py-2 text-sm">
                    Filtrar
                </button>
                <?php if ($busca !== '' || $categoriaFiltro !== ''): ?>
                <a href="<?= baseUrl('modules/produtos/index.php') ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-100 text-sm flex items-center justify-center" title="Limpar filtros">
                    ✕
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabela de Produtos (Mobile First e Responsiva) -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <?php if (empty($produtos)): ?>
        <div class="p-12 text-center">
            <svg class="w-12 h-12 text-gray-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
            <h3 class="text-base font-bold text-gray-800">Nenhum produto encontrado</h3>
            <p class="text-sm text-gray-500 mt-1">Ajuste os filtros de pesquisa ou cadastre novos itens.</p>
        </div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-3.5">Código / Nome</th>
                        <th class="px-6 py-3.5">Categoria</th>
                        <th class="px-6 py-3.5 text-right">Preço de Venda</th>
                        <?php if ($cargo === 'dono'): ?>
                        <th class="px-6 py-3.5 text-right">Preço de Custo</th>
                        <?php endif; ?>
                        <th class="px-6 py-3.5 text-center">Estoque Atual</th>
                        <th class="px-6 py-3.5">Fornecedor</th>
                        <?php if ($cargo === 'dono'): ?>
                        <th class="px-6 py-3.5 text-right">Ações</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <?php foreach ($produtos as $p): 
                        $estoqueCritico = (int)$p['estoque_atual'] <= (int)$p['estoque_minimo'];
                    ?>
                    <tr class="hover:bg-blue-50/40 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900"><?= htmlspecialchars($p['nome']) ?></div>
                            <div class="text-xs font-mono text-gray-500 tracking-wide"><?= htmlspecialchars($p['codigo_barras']) ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                <?= htmlspecialchars($p['categoria']) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-right">
                            <span class="font-bold text-gray-900 text-base">
                                R$ <?= number_format((float)$p['preco_venda'], 2, ',', '.') ?>
                            </span>
                        </td>
                        <?php if ($cargo === 'dono'): ?>
                        <td class="px-6 py-4 text-right text-gray-500">
                            R$ <?= number_format((float)$p['preco_custo'], 2, ',', '.') ?>
                        </td>
                        <?php endif; ?>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold <?= $estoqueCritico ? 'bg-red-100 text-red-700 border border-red-200' : 'bg-emerald-100 text-emerald-700' ?>">
                                <?= (int)$p['estoque_atual'] ?> un
                                <?php if ($estoqueCritico): ?>
                                    <span class="ml-1 text-[10px] uppercase font-bold">(Baixo)</span>
                                <?php endif; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-gray-600 text-xs">
                            <?= htmlspecialchars($p['fornecedor_nome'] ?? 'Não informado') ?>
                        </td>
                        <?php if ($cargo === 'dono'): ?>
                        <td class="px-6 py-4 text-right space-x-2 whitespace-nowrap">
                            <!-- Botão Alteração Rápida de Preço -->
                            <button type="button" 
                                    onclick="abrirModalPreco(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['nome'])) ?>', <?= (float)$p['preco_venda'] ?>)"
                                    title="Modificar Preço"
                                    class="px-2.5 py-1.5 rounded bg-amber-100 hover:bg-amber-200 text-amber-900 text-xs font-bold transition-colors">
                                R$ Preço
                            </button>

                            <!-- Editar Completo -->
                            <a href="<?= baseUrl('modules/produtos/form.php?id=' . $p['id']) ?>" 
                               title="Editar Produto"
                               class="px-2.5 py-1.5 rounded bg-blue-100 hover:bg-blue-200 text-lee-primary text-xs font-bold transition-colors">
                                Editar
                            </a>

                            <!-- Remover Produto (com Modal Customizado Lee Market) -->
                            <button type="button" 
                                    onclick="confirmarExclusao(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['nome'])) ?>')"
                                    title="Remover Produto"
                                    class="px-2.5 py-1.5 rounded bg-rose-100 hover:bg-rose-200 text-rose-700 text-xs font-bold transition-colors">
                                Excluir
                            </button>
                        </td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</main>

<!-- Modal de Alteração de Preço (Exclusivo Dono) -->
<div id="modal-alterar-preco" class="lee-modal-overlay">
    <div class="lee-modal-box">
        <form action="<?= baseUrl('modules/produtos/acoes.php?acao=alterar_preco') ?>" method="POST" class="p-6">
            <input type="hidden" name="id" id="preco-prod-id">
            
            <div class="flex items-center gap-3 mb-4">
                <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0 text-amber-600 font-bold">
                    R$
                </div>
                <div>
                    <h3 class="text-lg font-bold text-gray-900">Modificar Preço do Produto</h3>
                    <p class="text-xs text-gray-500" id="preco-prod-nome"></p>
                </div>
            </div>

            <div class="space-y-4 my-5">
                <div>
                    <label class="block text-xs font-semibold text-gray-500 uppercase">Preço Atual</label>
                    <div class="text-lg font-bold text-gray-900" id="preco-prod-atual"></div>
                </div>

                <div>
                    <label for="novo_preco" class="block text-sm font-semibold text-gray-700">Novo Preço de Venda (R$)</label>
                    <input type="number" step="0.01" min="0.01" name="novo_preco" id="novo_preco" required
                           class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg text-lg font-bold focus:ring-2 focus:ring-lee-primary focus:border-lee-primary">
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-3 border-t">
                <button type="button" onclick="fecharModalPreco()" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 text-sm font-semibold hover:bg-gray-100">
                    Cancelar
                </button>
                <button type="submit" class="btn-lee-accent text-sm">
                    Confirmar Alteração
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModalPreco(id, nome, precoAtual) {
        document.getElementById('preco-prod-id').value = id;
        document.getElementById('preco-prod-nome').innerText = nome;
        document.getElementById('preco-prod-atual').innerText = 'R$ ' + precoAtual.toFixed(2).replace('.', ',');
        document.getElementById('novo_preco').value = precoAtual.toFixed(2);
        
        const modal = document.getElementById('modal-alterar-preco');
        modal.classList.add('active');
        document.getElementById('novo_preco').focus();
    }

    function fecharModalPreco() {
        document.getElementById('modal-alterar-preco').classList.remove('active');
    }

    function confirmarExclusao(id, nome) {
        // Substitui o confirm nativo pelo modal personalizado Lee Market
        LeeModal.confirm(
            'Remover Produto',
            `Deseja realmente remover o produto "<strong>${nome}</strong>" do catálogo? Esta ação será registrada no histórico de auditoria.`,
            function() {
                window.location.href = `<?= baseUrl('modules/produtos/acoes.php?acao=excluir&id=') ?>` + id;
            },
            null,
            'Sim, Remover',
            'Cancelar'
        );
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
