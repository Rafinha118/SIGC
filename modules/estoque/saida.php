<?php
/**
 * Registro de Saída de Estoque - SIGC Lee Market
 * Requisitos:
 * 1. Seleciona o produto -> Exibe os dados do produto
 * 2. Informa a quantidade -> Verifica a disponibilidade em estoque
 * 3. Confirma a saída -> Registra a saída e atualiza o estoque
 */

require_once __DIR__ . '/../../config/bd.php';
require_once __DIR__ . '/../../config/auth.php';

exigirAutenticacao();

$pdo = getDB();
$produtoIdPreSelecionado = (int)($_GET['produto_id'] ?? 0);

// Lista todos os produtos ativos para preenchimento
$produtos = $pdo->query("SELECT id, codigo_barras, nome, categoria, preco_venda, estoque_atual, estoque_minimo FROM produtos WHERE ativo = 1 ORDER BY nome ASC")->fetchAll();

$pageTitle = 'Registrar Saída de Estoque — Lee Market';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
    <div class="mb-6">
        <a href="<?= baseUrl('modules/estoque/index.php') ?>" class="text-xs font-bold text-lee-primary hover:underline flex items-center gap-1 mb-2">
            &larr; Voltar ao Controle de Estoque
        </a>
        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Registrar Saída de Estoque</h1>
        <p class="text-sm text-gray-500">
            Dê baixa em produtos por motivos de avaria, validade vencida, consumo interno ou perdas operacionais.
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sm:p-8">
        <form id="form-saida-estoque" action="<?= baseUrl('modules/estoque/acoes.php?acao=registrar_saida') ?>" method="POST" class="space-y-6">
            <!-- 1. Seleção do Produto -->
            <div>
                <label for="produto_id" class="block text-sm font-semibold text-gray-800">
                    1. Selecione o Produto *
                </label>
                <select name="produto_id" id="produto_id" required onchange="atualizarInfoProduto()"
                        class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm font-medium focus:ring-2 focus:ring-lee-primary focus:border-lee-primary">
                    <option value="">-- Escolha um produto do catálogo --</option>
                    <?php foreach ($produtos as $p): ?>
                    <option value="<?= $p['id'] ?>" 
                            data-nome="<?= htmlspecialchars($p['nome']) ?>"
                            data-codigo="<?= htmlspecialchars($p['codigo_barras']) ?>"
                            data-categoria="<?= htmlspecialchars($p['categoria']) ?>"
                            data-estoque="<?= (int)$p['estoque_atual'] ?>"
                            data-preco="<?= (float)$p['preco_venda'] ?>"
                            <?= $produtoIdPreSelecionado === (int)$p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['nome']) ?> (Estoque: <?= (int)$p['estoque_atual'] ?> un) - <?= htmlspecialchars($p['codigo_barras']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Box Dinâmico com Dados do Produto Selecionado -->
            <div id="box-dados-produto" class="hidden bg-blue-50/60 border border-blue-200 rounded-xl p-4">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-lee-primary">Dados do Produto Selecionado</span>
                    <span id="prod-categoria" class="text-xs bg-white px-2 py-0.5 rounded border border-blue-200 text-gray-700 font-semibold"></span>
                </div>
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-4 text-sm mt-3">
                    <div>
                        <span class="block text-xs text-gray-500">Código de Barras</span>
                        <span id="prod-codigo" class="font-mono font-bold text-gray-800"></span>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-500">Preço de Venda</span>
                        <span id="prod-preco" class="font-bold text-gray-900"></span>
                    </div>
                    <div>
                        <span class="block text-xs text-gray-500">Estoque Disponível</span>
                        <span id="prod-estoque" class="font-extrabold text-base text-lee-primary"></span>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- 2. Quantidade da Saída -->
                <div>
                    <label for="quantidade" class="block text-sm font-semibold text-gray-800">
                        2. Quantidade da Baixa *
                    </label>
                    <input type="number" min="1" name="quantidade" id="quantidade" required placeholder="0" oninput="validarQuantidadeDisponivel()"
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-lg font-bold text-gray-900 focus:ring-2 focus:ring-lee-primary focus:border-lee-primary">
                    <p id="msg-erro-estoque" class="hidden text-xs text-red-600 font-bold mt-1">
                        A quantidade informada excede o estoque disponível!
                    </p>
                </div>

                <!-- 3. Motivo da Saída -->
                <div>
                    <label for="tipo_saida" class="block text-sm font-semibold text-gray-800">
                        3. Motivo da Saída *
                    </label>
                    <select name="tipo_saida" id="tipo_saida" required
                            class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary focus:border-lee-primary">
                        <option value="SAIDA_AVARIA">Avaria / Embalagem Danificada</option>
                        <option value="SAIDA_VENCIMENTO">Produto Vencido / Validade</option>
                        <option value="SAIDA_CONSUMO">Consumo Interno do Mercado</option>
                        <option value="SAIDA_OUTROS">Perda / Furto / Outros</option>
                    </select>
                </div>
            </div>

            <!-- Observações / Justificativa -->
            <div>
                <label for="motivo" class="block text-sm font-semibold text-gray-800">
                    Observações / Justificativa Detalhada
                </label>
                <textarea name="motivo" id="motivo" rows="2" placeholder="Descreva brevemente o motivo da baixa (opcional)..."
                          class="mt-1 block w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary focus:border-lee-primary"></textarea>
            </div>

            <!-- Botões de Ação -->
            <div class="pt-6 border-t border-gray-200 flex items-center justify-end gap-3">
                <a href="<?= baseUrl('modules/estoque/index.php') ?>" class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancelar
                </a>
                <button type="button" onclick="confirmarSaida()" class="btn-lee-accent text-sm">
                    Confirmar Saída de Estoque
                </button>
            </div>
        </form>
    </div>
</main>

<script>
    let estoqueAtualDisponivel = 0;
    let nomeProdutoAtual = '';

    function atualizarInfoProduto() {
        const select = document.getElementById('produto_id');
        const selected = select.options[select.selectedIndex];
        const box = document.getElementById('box-dados-produto');

        if (select.value === '') {
            box.classList.add('hidden');
            estoqueAtualDisponivel = 0;
            nomeProdutoAtual = '';
            return;
        }

        nomeProdutoAtual = selected.getAttribute('data-nome');
        document.getElementById('prod-categoria').innerText = selected.getAttribute('data-categoria');
        document.getElementById('prod-codigo').innerText = selected.getAttribute('data-codigo');
        document.getElementById('prod-preco').innerText = 'R$ ' + parseFloat(selected.getAttribute('data-preco')).toFixed(2).replace('.', ',');
        
        estoqueAtualDisponivel = parseInt(selected.getAttribute('data-estoque'), 10);
        document.getElementById('prod-estoque').innerText = estoqueAtualDisponivel + ' unidades';

        box.classList.remove('hidden');
        validarQuantidadeDisponivel();
    }

    function validarQuantidadeDisponivel() {
        const inputQtd = document.getElementById('quantidade');
        const msgErro = document.getElementById('msg-erro-estoque');
        const qtd = parseInt(inputQtd.value, 10) || 0;

        if (qtd > estoqueAtualDisponivel) {
            msgErro.classList.remove('hidden');
            inputQtd.classList.add('border-red-500', 'bg-red-50');
            return false;
        } else {
            msgErro.classList.add('hidden');
            inputQtd.classList.remove('border-red-500', 'bg-red-50');
            return true;
        }
    }

    function confirmarSaida() {
        const select = document.getElementById('produto_id');
        const inputQtd = document.getElementById('quantidade');
        const qtd = parseInt(inputQtd.value, 10) || 0;

        if (!select.value) {
            Toast.show('Por favor, selecione um produto.', 'aviso');
            return;
        }

        if (qtd <= 0) {
            Toast.show('Por favor, informe uma quantidade válida maior que zero.', 'aviso');
            return;
        }

        if (!validarQuantidadeDisponivel()) {
            Toast.show('A quantidade não pode ser maior que o estoque atual.', 'erro');
            return;
        }

        // Substituição de prompt/confirm nativo por modal personalizado Lee Market
        LeeModal.confirm(
            'Confirmar Baixa no Estoque',
            `Confirma a saída de <strong>${qtd} unidade(s)</strong> do produto "<strong>${nomeProdutoAtual}</strong>"? O saldo restante será de <strong>${estoqueAtualDisponivel - qtd} unidades</strong>.`,
            function() {
                document.getElementById('form-saida-estoque').submit();
            },
            null,
            'Sim, Baixar Estoque',
            'Voltar'
        );
    }

    // Se já havia produto pré-selecionado na URL
    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('produto_id').value !== '') {
            atualizarInfoProduto();
        }
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
