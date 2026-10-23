<?php
/**
 * Gestão de Recursos Humanos (RH) e Funções - SIGC Lee Market
 * Restrito ao perfil Dono (Administrador)
 * Requisitos:
 * - Organizar as funções de cada colaborador (Dono, Gerente, Operador de Caixa)
 * - Tratar senhas com hash bcrypt
 * - Ativação/Desativação de colaboradores e auditoria
 */

require_once __DIR__ . '/../../config/bd.php';
require_once __DIR__ . '/../../config/auth.php';

exigirCargo('dono');

$pdo = getDB();
$userLogado = usuarioAtual();

$busca = trim($_GET['busca'] ?? '');
$cargoFiltro = trim($_GET['cargo'] ?? '');

$sql = "SELECT * FROM usuarios WHERE 1=1";
$params = [];

if ($busca !== '') {
    $sql .= " AND (nome LIKE ? OR cpf LIKE ?)";
    $params[] = "%{$busca}%";
    $params[] = "%{$busca}%";
}

if ($cargoFiltro !== '') {
    $sql .= " AND cargo = ?";
    $params[] = $cargoFiltro;
}

$sql .= " ORDER BY cargo ASC, nome ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$colaboradores = $stmt->fetchAll();

// Métricas da Equipe
$stmtContagem = $pdo->query("
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN cargo = 'dono' THEN 1 ELSE 0 END) AS donos,
        SUM(CASE WHEN cargo = 'gerente' THEN 1 ELSE 0 END) AS gerentes,
        SUM(CASE WHEN cargo = 'caixa' THEN 1 ELSE 0 END) AS caixas
    FROM usuarios WHERE status = 1
");
$contagem = $stmtContagem->fetch();

$pageTitle = 'Gestão de RH e Funções — Lee Market';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-extrabold text-gray-900 tracking-tight">Gestão de RH & Colaboradores</h1>
            <p class="text-sm text-gray-500">Defina os cargos, credenciais e permissões operacionais de cada membro da equipe</p>
        </div>

        <div>
            <a href="<?= baseUrl('modules/rh/form.php') ?>" class="btn-lee-primary shadow-sm text-sm">
                + Novo Colaborador
            </a>
        </div>
    </div>

    <!-- Cards de Estrutura da Equipe -->
    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-xl p-4 border border-gray-100 shadow-sm">
            <span class="text-xs font-semibold text-gray-500 uppercase">Equipe Ativa</span>
            <h3 class="text-2xl font-black text-gray-900 mt-1"><?= (int)$contagem['total'] ?></h3>
            <span class="text-xs text-gray-400">Total de acessos</span>
        </div>

        <div class="bg-white rounded-xl p-4 border border-blue-100 shadow-sm">
            <span class="text-xs font-semibold text-lee-primary uppercase">Donos (Diretoria)</span>
            <h3 class="text-2xl font-black text-lee-primary mt-1"><?= (int)$contagem['donos'] ?></h3>
            <span class="text-xs text-gray-400">Acesso irrestrito</span>
        </div>

        <div class="bg-white rounded-xl p-4 border border-amber-100 shadow-sm">
            <span class="text-xs font-semibold text-amber-700 uppercase">Gerentes</span>
            <h3 class="text-2xl font-black text-amber-600 mt-1"><?= (int)$contagem['gerentes'] ?></h3>
            <span class="text-xs text-gray-400">Operação e Estoque</span>
        </div>

        <div class="bg-white rounded-xl p-4 border border-emerald-100 shadow-sm">
            <span class="text-xs font-semibold text-emerald-700 uppercase">Operadores de Caixa</span>
            <h3 class="text-2xl font-black text-emerald-600 mt-1"><?= (int)$contagem['caixas'] ?></h3>
            <span class="text-xs text-gray-400">Frente de caixa / PDV</span>
        </div>
    </div>

    <!-- Filtros de Busca -->
    <div class="bg-white rounded-xl p-4 shadow-sm border border-gray-100 mb-6">
        <form method="GET" class="grid grid-cols-1 sm:grid-cols-12 gap-3 items-center">
            <div class="sm:col-span-7 relative">
                <input type="text" name="busca" value="<?= htmlspecialchars($busca) ?>" 
                       placeholder="Buscar por nome ou CPF..."
                       class="block w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary">
            </div>

            <div class="sm:col-span-3">
                <select name="cargo" class="block w-full py-2 px-3 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-lee-primary">
                    <option value="">Todos os cargos</option>
                    <option value="dono" <?= $cargoFiltro === 'dono' ? 'selected' : '' ?>>Dono</option>
                    <option value="gerente" <?= $cargoFiltro === 'gerente' ? 'selected' : '' ?>>Gerente</option>
                    <option value="caixa" <?= $cargoFiltro === 'caixa' ? 'selected' : '' ?>>Operador de Caixa</option>
                </select>
            </div>

            <div class="sm:col-span-2 flex gap-2">
                <button type="submit" class="btn-lee-primary w-full py-2 text-sm justify-center">
                    Filtrar
                </button>
                <?php if ($busca !== '' || $cargoFiltro !== ''): ?>
                <a href="<?= baseUrl('modules/rh/index.php') ?>" class="px-3 py-2 border border-gray-300 rounded-lg text-gray-600 hover:bg-gray-100 text-sm flex items-center justify-center">
                    ✕
                </a>
                <?php endif; ?>
            </div>
        </form>
    </div>

    <!-- Tabela de Colaboradores -->
    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr class="text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        <th class="px-6 py-3.5">Nome Completo / CPF</th>
                        <th class="px-6 py-3.5">Cargo / Função</th>
                        <th class="px-6 py-3.5 text-center">Status</th>
                        <th class="px-6 py-3.5">Data de Admissão</th>
                        <th class="px-6 py-3.5 text-right">Ações</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    <?php foreach ($colaboradores as $colab): 
                        $isAtivo = (int)$colab['status'] === 1;
                        $badgeCargo = [
                            'dono' => 'bg-blue-100 text-lee-primary border-blue-200',
                            'gerente' => 'bg-amber-100 text-amber-800 border-amber-200',
                            'caixa' => 'bg-emerald-100 text-emerald-800 border-emerald-200'
                        ][$colab['cargo']] ?? 'bg-gray-100 text-gray-700';
                    ?>
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-bold text-gray-900"><?= htmlspecialchars($colab['nome']) ?></div>
                            <div class="text-xs font-mono text-gray-500"><?= htmlspecialchars($colab['cpf']) ?></div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold border <?= $badgeCargo ?>">
                                <?= ucfirst(htmlspecialchars($colab['cargo'])) ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold <?= $isAtivo ? 'bg-emerald-50 text-emerald-700' : 'bg-red-50 text-red-700' ?>">
                                <?= $isAtivo ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-xs text-gray-500">
                            <?= date('d/m/Y H:i', strtotime($colab['criado_em'])) ?>
                        </td>
                        <td class="px-6 py-4 text-right space-x-2">
                            <a href="<?= baseUrl('modules/rh/form.php?id=' . $colab['id']) ?>" 
                               class="btn-lee-outline py-1 px-2.5 text-xs">
                                Editar
                            </a>

                            <?php if ($colab['id'] != $userLogado['id']): ?>
                            <button type="button" 
                                    onclick="confirmarAlternarStatus(<?= $colab['id'] ?>, '<?= htmlspecialchars(addslashes($colab['nome'])) ?>', <?= $isAtivo ? 0 : 1 ?>)"
                                    class="px-2.5 py-1 rounded text-xs font-bold transition-colors <?= $isAtivo ? 'bg-rose-50 text-rose-700 hover:bg-rose-100' : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100' ?>">
                                <?= $isAtivo ? 'Desativar' : 'Ativar' ?>
                            </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</main>

<script>
    function confirmarAlternarStatus(id, nome, novoStatus) {
        const acaoTxt = novoStatus === 1 ? 'ativar' : 'desativar';
        LeeModal.confirm(
            'Alterar Acesso de Colaborador',
            `Deseja realmente <strong>${acaoTxt}</strong> o colaborador "<strong>${nome}</strong>"?`,
            function() {
                window.location.href = `<?= baseUrl('modules/rh/acoes.php?acao=status&id=') ?>` + id + '&status=' + novoStatus;
            },
            null,
            'Confirmar',
            'Cancelar'
        );
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
