<?php
/**
 * Entrada e Ajuste de Estoque - SIGC Lee Market
 * Restrito a Gerente e Dono
 */

require_once __DIR__ . '/../../config/bd.php';
require_once __DIR__ . '/../../config/auth.php';

exigirCargo(['gerente', 'dono']);

$pdo = getDB();
$produtoIdPre = (int)($_GET['produto_id'] ?? 0);

$produtos = $pdo->query("SELECT id, codigo_barras, nome, categoria, estoque_atual, preco_custo FROM produtos WHERE ativo = 1 ORDER BY nome ASC")->fetchAll();

$pageTitle = 'Entrada / Ajuste de Estoque — Lee Market';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
    <div class="mb-6">
        <a href="<?= baseUrl('modules/estoque/index.php') ?>" class="text-xs font-bold text-lee-primary hover:underline flex items-center gap-1 mb-2">
            &larr; Voltar ao Controle de Estoque
        </a>
        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Entrada / Ajuste de Estoque</h1>
        <p class="text-sm text-gray-500">
            Adicione novas mercadorias recebidas de fornecedores ou faça correções de balanço físico.
        </p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sm:p-8">
        <form id="form-entrada-estoque" action="<?= baseUrl('modules/estoque/acoes.php?acao=registrar_entrada') ?>" method="POST" class="space-y-6">
            <!-- Produto -->
            <div>
                <label for="produto_id" class="block text-sm font-semibold text-gray-800">
                    Selecione o Produto *
                </label>
                <select name="produto_id" id="produto_id" required onchange="atualizarDados()"
                        class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm font-medium focus:ring-2 focus:ring-lee-primary focus:border-lee-primary">
                    <option value="">-- Escolha um item do catálogo --</option>
                    <?php foreach ($produtos as $p): ?>
                    <option value="<?= $p['id'] ?>"
                            data-nome="<?= htmlspecialchars($p['nome']) ?>"
                            data-estoque="<?= (int)$p['estoque_atual'] ?>"
                            <?= $produtoIdPre === (int)$p['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($p['nome']) ?> (Estoque atual: <?= (int)$p['estoque_atual'] ?> un)
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Box informativo -->
            <div id="info-estoque" class="p-4 bg-emerald-50 border border-emerald-200 rounded-lg hidden">
                <span class="text-xs font-bold uppercase text-emerald-800">Estoque Atual em Prateleira/Depósito:</span>
                <span id="valor-estoque" class="text-lg font-extrabold text-emerald-900 ml-2"></span>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <!-- Tipo de Movimento -->
                <div>
                    <label for="tipo_movimento" class="block text-sm font-semibold text-gray-800">
                        Tipo de Operação *
                    </label>
                    <select name="tipo_movimento" id="tipo_movimento" required
                            class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary focus:border-lee-primary">
                        <option value="ENTRADA">Entrada / Recebimento de Mercadorias (Soma)</option>
                        <option value="AJUSTE">Ajuste / Balanço Físico (Define nova quantidade)</option>
                    </select>
                </div>

                <!-- Quantidade -->
                <div>
                    <label for="quantidade" class="block text-sm font-semibold text-gray-800">
                        Quantidade a Adicionar / Nova Quantidade *
                    </label>
                    <input type="number" min="0" name="quantidade" id="quantidade" required placeholder="0"
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-lg font-bold text-gray-900 focus:ring-2 focus:ring-lee-primary focus:border-lee-primary">
                </div>
            </div>

            <!-- Motivo / Nota Fiscal -->
            <div>
                <label for="motivo" class="block text-sm font-semibold text-gray-800">
                    Número da NF / Nota de Recebimento / Observação
                </label>
                <input type="text" name="motivo" id="motivo" placeholder="Ex: NF 10423 - Laticínios Vale Verde"
                       class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary focus:border-lee-primary">
            </div>

            <!-- Botões -->
            <div class="pt-6 border-t border-gray-200 flex items-center justify-end gap-3">
                <a href="<?= baseUrl('modules/estoque/index.php') ?>" class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50 transition-colors">
                    Cancelar
                </a>
                <button type="button" onclick="confirmarEntrada()" class="btn-lee-primary text-sm">
                    Confirmar Entrada
                </button>
            </div>
        </form>
    </div>
</main>

<script>
    function atualizarDados() {
        const select = document.getElementById('produto_id');
        const selected = select.options[select.selectedIndex];
        const box = document.getElementById('info-estoque');

        if (select.value === '') {
            box.classList.add('hidden');
            return;
        }

        const estoque = selected.getAttribute('data-estoque');
        document.getElementById('valor-estoque').innerText = estoque + ' unidades';
        box.classList.remove('hidden');
    }

    function confirmarEntrada() {
        const select = document.getElementById('produto_id');
        const qtd = document.getElementById('quantidade').value;
        const tipo = document.getElementById('tipo_movimento').value;

        if (!select.value) {
            Toast.show('Selecione um produto.', 'aviso');
            return;
        }

        if (qtd === '' || parseInt(qtd) < 0) {
            Toast.show('Informe uma quantidade válida.', 'aviso');
            return;
        }

        const nome = select.options[select.selectedIndex].getAttribute('data-nome');
        const msg = tipo === 'ENTRADA' 
            ? `Confirma a entrada de <strong>${qtd} unidades</strong> para o produto "<strong>${nome}</strong>"?` 
            : `Confirma o ajuste do produto "<strong>${nome}</strong>" para exatamente <strong>${qtd} unidades</strong>?`;

        LeeModal.confirm('Confirmar Movimentação', msg, () => {
            document.getElementById('form-entrada-estoque').submit();
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        if (document.getElementById('produto_id').value !== '') {
            atualizarDados();
        }
    });
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
