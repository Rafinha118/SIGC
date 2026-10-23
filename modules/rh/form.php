<?php
/**
 * Formulário de Colaborador (RH) - SIGC Lee Market
 * Restrito ao perfil Dono
 */

require_once __DIR__ . '/../../config/bd.php';
require_once __DIR__ . '/../../config/auth.php';

exigirCargo('dono');

$pdo = getDB();
$id = (int)($_GET['id'] ?? 0);
$colaborador = null;

if ($id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE id = ?");
    $stmt->execute([$id]);
    $colaborador = $stmt->fetch();

    if (!$colaborador) {
        definirToast('erro', 'Colaborador não encontrado.');
        header('Location: ' . baseUrl('modules/rh/index.php'));
        exit;
    }
}

$pageTitle = ($id > 0 ? 'Editar Colaborador' : 'Cadastrar Colaborador') . ' — Lee Market';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
    <div class="mb-6">
        <a href="<?= baseUrl('modules/rh/index.php') ?>" class="text-xs font-bold text-lee-primary hover:underline flex items-center gap-1 mb-2">
            &larr; Voltar para Gestão de RH
        </a>
        <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">
            <?= $id > 0 ? 'Editar Colaborador' : 'Novo Colaborador' ?>
        </h1>
        <p class="text-sm text-gray-500">Cadastre os dados pessoais, perfil de acesso e credenciais de segurança</p>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 sm:p-8">
        <form action="<?= baseUrl('modules/rh/acoes.php?acao=salvar') ?>" method="POST" class="space-y-6">
            <input type="hidden" name="id" value="<?= $id ?>">

            <div>
                <label for="nome" class="block text-sm font-semibold text-gray-800">Nome Completo *</label>
                <input type="text" name="nome" id="nome" required
                       value="<?= htmlspecialchars($colaborador['nome'] ?? '') ?>"
                       placeholder="Ex: João da Silva"
                       class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm font-medium focus:ring-2 focus:ring-lee-primary">
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="cpf" class="block text-sm font-semibold text-gray-800">CPF *</label>
                    <input type="text" name="cpf" id="cpf" required maxlength="14"
                           value="<?= htmlspecialchars($colaborador['cpf'] ?? '') ?>"
                           placeholder="000.000.000-00"
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm font-mono focus:ring-2 focus:ring-lee-primary">
                </div>

                <div>
                    <label for="cargo" class="block text-sm font-semibold text-gray-800">Cargo / Função *</label>
                    <select name="cargo" id="cargo" required
                            class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm font-bold focus:ring-2 focus:ring-lee-primary">
                        <option value="caixa" <?= ($colaborador && $colaborador['cargo'] === 'caixa') ? 'selected' : '' ?>>Operador de Caixa (PDV e Baixas)</option>
                        <option value="gerente" <?= ($colaborador && $colaborador['cargo'] === 'gerente') ? 'selected' : '' ?>>Gerente (Estoque e Relatórios)</option>
                        <option value="dono" <?= ($colaborador && $colaborador['cargo'] === 'dono') ? 'selected' : '' ?>>Dono (Administrador Geral)</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                <div>
                    <label for="senha" class="block text-sm font-semibold text-gray-800">
                        <?= $id > 0 ? 'Nova Senha (deixe em branco para manter)' : 'Senha de Acesso *' ?>
                    </label>
                    <input type="password" name="senha" id="senha" <?= $id === 0 ? 'required' : '' ?>
                           placeholder="••••••••"
                           class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary">
                    <p class="text-[11px] text-gray-400 mt-1">Criptografia Bcrypt segura aplicada automaticamente</p>
                </div>

                <div>
                    <label for="status" class="block text-sm font-semibold text-gray-800">Situação do Acesso *</label>
                    <select name="status" id="status" required
                            class="mt-1 block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary">
                        <option value="1" <?= (!$colaborador || (int)$colaborador['status'] === 1) ? 'selected' : '' ?>>Ativo (Liberado)</option>
                        <option value="0" <?= ($colaborador && (int)$colaborador['status'] === 0) ? 'selected' : '' ?>>Inativo (Bloqueado)</option>
                    </select>
                </div>
            </div>

            <div class="pt-6 border-t border-gray-200 flex items-center justify-end gap-3">
                <a href="<?= baseUrl('modules/rh/index.php') ?>" class="px-5 py-2.5 border border-gray-300 rounded-lg text-sm font-semibold text-gray-700 hover:bg-gray-50">
                    Cancelar
                </a>
                <button type="submit" class="btn-lee-accent text-sm">
                    <?= $id > 0 ? 'Salvar Colaborador' : 'Concluir Cadastro' ?>
                </button>
            </div>
        </form>
    </div>
</main>

<script>
    const cpfInput = document.getElementById('cpf');
    if (cpfInput) {
        cpfInput.addEventListener('input', function(e) {
            let v = e.target.value.replace(/\D/g, '');
            if (v.length > 11) v = v.substring(0, 11);
            if (v.length > 9) {
                v = v.replace(/(\d{3})(\d{3})(\d{3})(\d{1,2})/, '$1.$2.$3-$4');
            } else if (v.length > 6) {
                v = v.replace(/(\d{3})(\d{3})(\d{1,3})/, '$1.$2.$3');
            } else if (v.length > 3) {
                v = v.replace(/(\d{3})(\d{1,3})/, '$1.$2');
            }
            e.target.value = v;
        });
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
