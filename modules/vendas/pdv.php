<?php
/**
 * Frente de Caixa / PDV - SIGC Lee Market
 * Requisitos:
 * 1. Seleciona os produtos -> Exibe produtos e preços
 * 2. Informa quantidades -> Calcula total da venda
 * 3. Informa forma de pagamento -> Registra a venda
 * 4. Confirma a venda -> Atualiza estoque, gera cupom/recibo e finaliza
 */

require_once __DIR__ . '/../../config/bd.php';
require_once __DIR__ . '/../../config/auth.php';

// Acesso para Caixa e Dono
exigirCargo(['caixa', 'dono']);

$pdo = getDB();
$user = usuarioAtual();

// Lista todos os produtos com estoque disponível para a grade de seleção rápida
$stmt = $pdo->query("SELECT id, codigo_barras, nome, categoria, preco_venda, estoque_atual FROM produtos WHERE ativo = 1 ORDER BY nome ASC");
$produtos = $stmt->fetchAll();

$categorias = array_unique(array_column($produtos, 'categoria'));

$pageTitle = 'PDV — Frente de Caixa | Lee Market';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 w-full flex-1">
    <!-- Cabeçalho do Caixa -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 mb-6 bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-lee-accent text-lee-black font-extrabold flex items-center justify-center text-lg">
                🛒
            </div>
            <div>
                <h1 class="text-xl font-extrabold text-gray-900 tracking-tight">Frente de Caixa (PDV)</h1>
                <p class="text-xs text-gray-500">Operador conectado: <strong class="text-lee-primary"><?= htmlspecialchars($user['nome']) ?></strong></p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button type="button" onclick="PDV.limparCarrinho()" class="px-3 py-1.5 rounded-lg border border-rose-300 text-rose-700 hover:bg-rose-50 text-xs font-bold transition-colors">
                Cancelar Venda Atual
            </button>
            <a href="<?= baseUrl('modules/vendas/historico.php') ?>" class="px-3 py-1.5 rounded-lg border border-gray-300 text-gray-700 hover:bg-gray-100 text-xs font-bold transition-colors">
                Histórico de Vendas
            </a>
        </div>
    </div>

    <!-- Layout Dividido do PDV (Mobile First: Carrinho no topo/coluna, produtos ao lado) -->
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
        <!-- Coluna Esquerda (7 cols): Cupom Fiscal e Carrinho de Itens -->
        <div class="lg:col-span-7 bg-white rounded-xl shadow-sm border border-gray-100 flex flex-col overflow-hidden">
            <!-- Barra de Leitura do Código de Barras -->
            <div class="p-4 bg-lee-primary text-white">
                <label for="pdv-busca-input" class="block text-xs font-bold uppercase tracking-wider text-lee-accent mb-1">
                    Bipar Código de Barras ou Digitar Nome (Pressione Enter)
                </label>
                <div class="relative">
                    <input type="text" id="pdv-busca-input" autofocus
                           placeholder="Digite o código de barras ou nome do produto..."
                           class="w-full pl-10 pr-4 py-3 rounded-lg text-gray-900 font-mono text-base font-bold focus:ring-4 focus:ring-lee-accent border-none shadow-inner">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"></path></svg>
                    </div>
                </div>
            </div>

            <!-- Tabela dos Itens no Carrinho -->
            <div class="overflow-y-auto max-h-[360px] p-2">
                <table class="min-w-full text-left text-sm">
                    <thead class="text-xs font-semibold text-gray-400 uppercase border-b bg-gray-50/50">
                        <tr>
                            <th class="py-2 px-3">#</th>
                            <th class="py-2 px-3">Item</th>
                            <th class="py-2 px-3 text-center">Qtd</th>
                            <th class="py-2 px-3 text-right">Unitário</th>
                            <th class="py-2 px-3 text-right">Subtotal</th>
                            <th class="py-2 px-2 text-center"></th>
                        </tr>
                    </thead>
                    <tbody id="pdv-itens-tabela">
                        <!-- Preenchido dinamicamente por PDV.js -->
                    </tbody>
                </table>
            </div>

            <!-- Resumo Financeiro da Venda -->
            <div class="p-4 bg-gray-50 border-t border-gray-200">
                <div class="flex items-center justify-between mb-3 text-sm text-gray-600">
                    <span>Quantidade Total de Unidades:</span>
                    <span id="pdv-qtd-itens" class="font-bold text-gray-900 text-base">0</span>
                </div>

                <div class="flex items-center justify-between py-2 border-t border-gray-200">
                    <span class="text-lg font-bold text-gray-800">TOTAL DA COMPRA:</span>
                    <span id="pdv-total-valor" class="text-3xl font-black text-lee-primary">R$ 0,00</span>
                </div>

                <!-- Pagamento -->
                <div class="mt-4 pt-3 border-t border-gray-200 grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label for="forma_pagamento" class="block text-xs font-bold uppercase text-gray-600 mb-1">
                            Forma de Pagamento
                        </label>
                        <select id="forma_pagamento" class="w-full py-2.5 px-3 border border-gray-300 rounded-lg font-bold text-gray-800 text-sm focus:ring-2 focus:ring-lee-primary">
                            <option value="Dinheiro">Dinheiro</option>
                            <option value="PIX">PIX</option>
                            <option value="Cartão de Débito">Cartão de Débito</option>
                            <option value="Cartão de Crédito">Cartão de Crédito</option>
                        </select>
                    </div>

                    <div id="box-pagamento-dinheiro">
                        <label for="valor_recebido" class="block text-xs font-bold uppercase text-gray-600 mb-1">
                            Valor Entregue pelo Cliente (R$)
                        </label>
                        <input type="number" step="0.01" min="0" id="valor_recebido" placeholder="0.00"
                               class="w-full py-2 px-3 border border-gray-300 rounded-lg font-bold text-gray-900 text-base focus:ring-2 focus:ring-lee-primary">
                    </div>
                </div>

                <!-- Troco -->
                <div class="mt-3 flex items-center justify-between bg-white p-3 rounded-lg border border-gray-200">
                    <span class="text-xs font-bold uppercase text-gray-500">Troco a Devolver:</span>
                    <span id="pdv-troco-valor" class="text-xl font-bold text-gray-700">R$ 0,00</span>
                </div>

                <!-- Botão Finalizar Venda -->
                <button type="button" id="pdv-btn-finalizar" onclick="PDV.abrirModalConfirmacao()"
                        class="w-full mt-4 btn-lee-accent py-3.5 text-base font-extrabold shadow-md hover:shadow-lg justify-center tracking-wide disabled:opacity-50 disabled:cursor-not-allowed">
                    CONFIRMAR E FINALIZAR VENDA
                </button>
            </div>
        </div>

        <!-- Coluna Direita (5 cols): Grade Rápida de Produtos (Clique para adicionar) -->
        <div class="lg:col-span-5 bg-white rounded-xl shadow-sm border border-gray-100 p-4">
            <h2 class="text-sm font-bold uppercase tracking-wider text-gray-700 mb-3 flex items-center justify-between">
                <span>Catálogo Rápido</span>
                <span class="text-xs text-gray-400 font-normal">Clique para adicionar</span>
            </h2>

            <!-- Filtro de Categorias Rápido -->
            <div class="flex gap-1.5 overflow-x-auto pb-2 mb-3">
                <button type="button" onclick="filtrarGradeProdutos('')" class="cat-pill active px-3 py-1 rounded-full text-xs font-bold bg-lee-primary text-white">
                    Todos
                </button>
                <?php foreach ($categorias as $cat): ?>
                <button type="button" onclick="filtrarGradeProdutos('<?= htmlspecialchars($cat) ?>')" class="cat-pill px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700 hover:bg-gray-200 whitespace-nowrap">
                    <?= htmlspecialchars($cat) ?>
                </button>
                <?php endforeach; ?>
            </div>

            <!-- Grade de Itens Clicáveis -->
            <div class="grid grid-cols-2 gap-2.5 max-h-[580px] overflow-y-auto p-1" id="grade-produtos">
                <?php foreach ($produtos as $p): 
                    $disponivel = (int)$p['estoque_atual'] > 0;
                ?>
                <button type="button" 
                        data-categoria="<?= htmlspecialchars($p['categoria']) ?>"
                        onclick="PDV.adicionarItem(<?= $p['id'] ?>)"
                        class="prod-card text-left p-2.5 rounded-lg border border-gray-200 hover:border-lee-primary hover:bg-blue-50/50 transition-all flex flex-col justify-between h-28 <?= !$disponivel ? 'opacity-40 cursor-not-allowed pointer-events-none' : '' ?>">
                    <div>
                        <div class="text-[10px] uppercase font-bold text-gray-400"><?= htmlspecialchars($p['categoria']) ?></div>
                        <div class="text-xs font-bold text-gray-900 line-clamp-2 leading-tight mt-0.5"><?= htmlspecialchars($p['nome']) ?></div>
                    </div>
                    <div class="flex items-center justify-between pt-1 border-t border-gray-100">
                        <span class="text-xs font-extrabold text-lee-primary">R$ <?= number_format((float)$p['preco_venda'], 2, ',', '.') ?></span>
                        <span class="text-[10px] text-gray-500"><?= (int)$p['estoque_atual'] ?> un</span>
                    </div>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</main>

<script src="<?= baseUrl('assets/js/pdv.js') ?>"></script>
<script>
    // Inicializa os dados no PDV
    const produtosJson = <?= json_encode($produtos) ?>;
    document.addEventListener('DOMContentLoaded', () => {
        PDV.init(produtosJson);
    });

    function filtrarGradeProdutos(categoria) {
        document.querySelectorAll('.cat-pill').forEach(btn => {
            btn.classList.remove('bg-lee-primary', 'text-white', 'active');
            btn.classList.add('bg-gray-100', 'text-gray-700');
        });
        event.target.classList.remove('bg-gray-100', 'text-gray-700');
        event.target.classList.add('bg-lee-primary', 'text-white', 'active');

        document.querySelectorAll('.prod-card').forEach(card => {
            if (!categoria || card.getAttribute('data-categoria') === categoria) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
