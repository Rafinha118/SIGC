<?php
/**
 * Formulário de Cadastro e Edição de Produtos - SIGC Lee Market
 * Restrito ao perfil Dono (conforme especificação dos requisitos)
 */

require_once __DIR__ . '/../../config/bd.php';
require_once __DIR__ . '/../../config/auth.php';

exigirCargo('dono');

$pdo = getDB();
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$produto = null;

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM produtos WHERE id = ? AND ativo = 1 LIMIT 1");
    $stmt->execute([$id]);
    $produto = $stmt->fetch();

    if (!$produto) {
        definirToast('erro', 'Produto não encontrado ou desativado.');
        header('Location: ' . baseUrl('modules/produtos/index.php'));
        exit;
    }
}

// Lista de fornecedores para vinculação
$fornecedores = $pdo->query("SELECT id, nome FROM fornecedores ORDER BY nome ASC")->fetchAll();

$pageTitle = ($id > 0 ? 'Editar Produto' : 'Cadastrar Novo Produto') . ' — Lee Market';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
    <div class="mb-6">
        <a href="<?= baseUrl('modules/produtos/index.php') ?>" class="text-xs font-bold text-lee-primary hover:underline flex items-center gap-1 mb-2">
            &larr; Voltar para Catálogo de Produtos
        </a>
        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">
            <?= $id > 0 ? 'Editar Produto' : 'Cadastrar Novo Produto' ?>
        </h1>
        <p class="text-sm text-gray-500">
            <?= $id > 0 ? 'Atualize as informações cadastrais e comerciais do item' : 'Preencha os dados abaixo para disponibilizar o produto no supermercado' ?>
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sm:p-8">
        <form action="<?= baseUrl('modules/produtos/acoes.php?acao=salvar') ?>" method="POST" class="space-y-6">
            <input type="hidden" name="id" value="<?= $id ?>">

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Código de Barras -->
                <div>
                    <label for="codigo_barras" class="block text-sm font-semibold text-gray-800">
                        Código de Barras (EAN / SKU) *
                    </label>
                    <input type="text" name="codigo_barras" id="codigo_barras" required
                           value="<?= htmlspecialchars($produto['codigo_barras'] ?? '') ?>"
                           placeholder="Ex: 7891000100101"
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-lee-primary focus:border-lee-primary">
                    <p class="text-[11px] text-gray-400 mt-1">Identificador único utilizado pelo leitor do caixa</p>
                </div>

                <!-- Categoria -->
                <div>
                    <label for="categoria" class="block text-sm font-semibold text-gray-800">
                        Categoria do Produto *
                    </label>
                    <input type="text" name="categoria" id="categoria" required list="lista-categorias"
                           value="<?= htmlspecialchars($produto['categoria'] ?? '') ?>"
                           placeholder="Ex: Mercearia, Bebidas, Laticínios..."
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary focus:border-lee-primary">
                    <datalist id="lista-categorias">
                        <option value="Mercearia">
                        <option value="Laticínios">
                        <option value="Bebidas">
                        <option value="Limpeza">
                        <option value="Hortifrúti">
                        <option value="Padaria">
                        <option value="Carnes e Frios">
                        <option value="Higiene Pessoal">
                    </datalist>
                </div>

                <!-- Nome Completo do Produto -->
                <div class="sm:col-span-2">
                    <label for="nome" class="block text-sm font-semibold text-gray-800">
                        Nome Descritivo do Produto *
                    </label>
                    <input type="text" name="nome" id="nome" required
                           value="<?= htmlspecialchars($produto['nome'] ?? '') ?>"
                           placeholder="Ex: Arroz Branco Tipo 1 5kg"
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm font-medium focus:ring-2 focus:ring-lee-primary focus:border-lee-primary">
                </div>

                <!-- Preço de Custo -->
                <div>
                    <label for="preco_custo" class="block text-sm font-semibold text-gray-800">
                        Preço de Custo (R$) *
                    </label>
                    <input type="number" step="0.01" min="0" name="preco_custo" id="preco_custo" required
                           value="<?= htmlspecialchars($produto['preco_custo'] ?? '0.00') ?>"
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary focus:border-lee-primary">
                </div>

                <!-- Preço de Venda -->
                <div>
                    <label for="preco_venda" class="block text-sm font-semibold text-gray-800">
                        Preço de Venda ao Consumidor (R$) *
                    </label>
                    <input type="number" step="0.01" min="0.01" name="preco_venda" id="preco_venda" required
                           value="<?= htmlspecialchars($produto['preco_venda'] ?? '') ?>"
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm font-bold text-gray-900 focus:ring-2 focus:ring-lee-primary focus:border-lee-primary">
                </div>

                <!-- Estoque Atual / Inicial -->
                <div>
                    <label for="estoque_atual" class="block text-sm font-semibold text-gray-800">
                        <?= $id > 0 ? 'Estoque Atual (Unidades) *' : 'Estoque Inicial (Unidades) *' ?>
                    </label>
                    <input type="number" min="0" name="estoque_atual" id="estoque_atual" required
                           value="<?= htmlspecialchars($produto['estoque_atual'] ?? '0') ?>"
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary focus:border-lee-primary">
                </div>

                <!-- Estoque Mínimo -->
                <div>
                    <label for="estoque_minimo" class="block text-sm font-semibold text-gray-800">
                        Estoque Mínimo de Alerta *
                    </label>
                    <input type="number" min="1" name="estoque_minimo" id="estoque_minimo" required
                           value="<?= htmlspecialchars($produto['estoque_minimo'] ?? '5') ?>"
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary focus:border-lee-primary">
                    <p class="text-[11px] text-gray-400 mt-1">Dispara aviso visual no dashboard quando atingir este limite</p>
                </div>

                <!-- Fornecedor -->
                <div class="sm:col-span-2">
                    <label for="fornecedor_id" class="block text-sm font-semibold text-gray-800">
                        Fornecedor Vinculado
                    </label>
                    <select name="fornecedor_id" id="fornecedor_id"
                            class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary focus:border-lee-primary">
                        <option value="">Selecione um fornecedor (opcional)</option>
                        <?php foreach ($fornecedores as $f): ?>
                        <option value="<?= $f['id'] ?>" <?= ($produto && $produto['fornecedor_id'] == $f['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($f['nome']) ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Botões de Ação -->
            <div class="pt-6 border-t border-gray-200 flex items-center justify-end gap-3">
                <a href="<?= baseUrl('modules/produtos/index.php') ?>" class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancelar
                </a>
                <button type="submit" class="btn-lee-accent text-sm">
                    <?= $id > 0 ? 'Salvar Alterações' : 'Confirmar Cadastro do Produto' ?>
                </button>
            </div>
        </form>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
