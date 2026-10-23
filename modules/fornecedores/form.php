<?php
/**
 * Formulário de Fornecedor - SIGC Lee Market
 * Restrito ao perfil Dono
 */

require_once __DIR__ . '/../../config/bd.php';
require_once __DIR__ . '/../../config/auth.php';

exigirCargo('dono');

$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
$fornecedor = null;

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM fornecedores WHERE id = ?");
    $stmt->execute([$id]);
    $fornecedor = $stmt->fetch();

    if (!$fornecedor) {
        definirToast('erro', 'Fornecedor não encontrado.');
        header('Location: ' . baseUrl('modules/fornecedores/index.php'));
        exit;
    }
}

$pageTitle = ($id > 0 ? 'Editar Fornecedor' : 'Cadastrar Fornecedor') . ' — Lee Market';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
    <div class="mb-6">
        <a href="<?= baseUrl('modules/fornecedores/index.php') ?>" class="text-xs font-bold text-lee-primary hover:underline flex items-center gap-1 mb-2">
            &larr; Voltar para Lista de Fornecedores
        </a>
        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">
            <?= $id > 0 ? 'Editar Fornecedor' : 'Cadastrar Novo Fornecedor' ?>
        </h1>
        <p class="text-sm text-gray-500">Mantenha os canais de suprimentos e contatos comerciais atualizados</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sm:p-8">
        <form action="<?= baseUrl('modules/fornecedores/acoes.php?acao=salvar') ?>" method="POST" class="space-y-6">
            <input type="hidden" name="id" value="<?= $id ?>">

            <div>
                <label for="nome" class="block text-sm font-semibold text-gray-800">Razão Social / Nome Fantasia *</label>
                <input type="text" name="nome" id="nome" required
                       value="<?= htmlspecialchars($fornecedor['nome'] ?? '') ?>"
                       placeholder="Ex: Distribuidora Alimentos Brasil Ltda"
                       class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm font-medium focus:ring-2 focus:ring-lee-primary">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="cnpj" class="block text-sm font-semibold text-gray-800">CNPJ</label>
                    <input type="text" name="cnpj" id="cnpj"
                           value="<?= htmlspecialchars($fornecedor['cnpj'] ?? '') ?>"
                           placeholder="00.000.000/0000-00"
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-lee-primary">
                </div>

                <div>
                    <label for="telefone" class="block text-sm font-semibold text-gray-800">Telefone de Contato *</label>
                    <input type="text" name="telefone" id="telefone" required
                           value="<?= htmlspecialchars($fornecedor['telefone'] ?? '') ?>"
                           placeholder="(00) 0000-0000"
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary">
                </div>
            </div>

            <div>
                <label for="email" class="block text-sm font-semibold text-gray-800">E-mail Comercial *</label>
                <input type="email" name="email" id="email" required
                       value="<?= htmlspecialchars($fornecedor['email'] ?? '') ?>"
                       placeholder="pedidos@empresa.com.br"
                       class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary">
            </div>

            <div>
                <label for="endereco" class="block text-sm font-semibold text-gray-800">Endereço Completo</label>
                <input type="text" name="endereco" id="endereco"
                       value="<?= htmlspecialchars($fornecedor['endereco'] ?? '') ?>"
                       placeholder="Rua, Número, Bairro, Cidade - UF"
                       class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary">
            </div>

            <div class="pt-6 border-t border-gray-200 flex items-center justify-end gap-3">
                <a href="<?= baseUrl('modules/fornecedores/index.php') ?>" class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit" class="btn-lee-accent text-sm">
                    <?= $id > 0 ? 'Salvar Alterações' : 'Cadastrar Fornecedor' ?>
                </button>
            </div>
        </form>
    </div>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
