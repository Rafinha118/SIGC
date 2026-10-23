<?php
/**
 * Barra de Navegação Responsiva (Mobile First) - Lee Market
 * Adapta dinamicamente as opções com base no perfil do colaborador
 */

require_once __DIR__ . '/../config/auth.php';
$user = usuarioAtual();
$cargo = $user ? strtolower($user['cargo']) : null;
$currentScript = $_SERVER['SCRIPT_NAME'] ?? '';
?>
<?php if ($user): ?>
<header class="bg-lee-primary text-white shadow-md sticky top-0 z-40">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Brand / Logo -->
            <div class="flex items-center gap-3">
                <a href="<?= baseUrl('modules/dashboard/index.php') ?>" class="flex items-center gap-2 group">
                    <div class="w-9 h-9 rounded-lg bg-lee-accent flex items-center justify-center text-lee-black font-black text-xl shadow-sm group-hover:scale-105 transition-transform">
                        L
                    </div>
                    <div>
                        <span class="text-xl font-bold tracking-tight text-white">Lee <span class="text-lee-accent">Market</span></span>
                        <span class="hidden sm:inline-block text-[11px] uppercase tracking-wider text-blue-200 ml-1.5 font-semibold">SIGC</span>
                    </div>
                </a>
            </div>

            <!-- Navegação Desktop -->
            <nav class="hidden md:flex items-center space-x-1 lg:space-x-2 text-sm font-semibold">
                <a href="<?= baseUrl('modules/dashboard/index.php') ?>" 
                   class="px-3 py-2 rounded-md hover:bg-white/10 transition-colors <?= strpos($currentScript, 'dashboard') !== false ? 'bg-white/15 text-lee-accent' : 'text-gray-100' ?>">
                    Dashboard
                </a>

                <!-- Vendas / PDV (Todos têm acesso ou operadores específicos) -->
                <?php if ($cargo === 'caixa' || $cargo === 'dono'): ?>
                <a href="<?= baseUrl('modules/vendas/pdv.php') ?>" 
                   class="px-3 py-2 rounded-md bg-lee-accent text-lee-black hover:bg-lee-accent-dark font-bold shadow-sm transition-all flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    PDV
                </a>
                <?php endif; ?>

                <?php if ($cargo === 'dono' || $cargo === 'gerente'): ?>
                <a href="<?= baseUrl('modules/vendas/historico.php') ?>" 
                   class="px-3 py-2 rounded-md hover:bg-white/10 transition-colors <?= strpos($currentScript, 'vendas/historico') !== false ? 'bg-white/15 text-lee-accent' : 'text-gray-100' ?>">
                    Vendas
                </a>
                <?php endif; ?>

                <!-- Produtos & Estoque -->
                <a href="<?= baseUrl('modules/produtos/index.php') ?>" 
                   class="px-3 py-2 rounded-md hover:bg-white/10 transition-colors <?= strpos($currentScript, 'produtos') !== false ? 'bg-white/15 text-lee-accent' : 'text-gray-100' ?>">
                    Produtos
                </a>

                <a href="<?= baseUrl('modules/estoque/index.php') ?>" 
                   class="px-3 py-2 rounded-md hover:bg-white/10 transition-colors <?= strpos($currentScript, 'estoque') !== false ? 'bg-white/15 text-lee-accent' : 'text-gray-100' ?>">
                    Estoque
                </a>

                <!-- Fornecedores (Dono e Gerente) -->
                <?php if ($cargo === 'dono' || $cargo === 'gerente'): ?>
                <a href="<?= baseUrl('modules/fornecedores/index.php') ?>" 
                   class="px-3 py-2 rounded-md hover:bg-white/10 transition-colors <?= strpos($currentScript, 'fornecedores') !== false ? 'bg-white/15 text-lee-accent' : 'text-gray-100' ?>">
                    Fornecedores
                </a>
                <?php endif; ?>

                <!-- Relatórios (Dono e Gerente) -->
                <?php if ($cargo === 'dono' || $cargo === 'gerente'): ?>
                <a href="<?= baseUrl('modules/relatorios/vendas.php') ?>" 
                   class="px-3 py-2 rounded-md hover:bg-white/10 transition-colors <?= strpos($currentScript, 'relatorios') !== false ? 'bg-white/15 text-lee-accent' : 'text-gray-100' ?>">
                    Relatórios
                </a>
                <?php endif; ?>

                <!-- Módulos Exclusivos do Dono: Financeiro, RH, Auditoria -->
                <?php if ($cargo === 'dono'): ?>
                <div class="relative group">
                    <button class="px-3 py-2 rounded-md hover:bg-white/10 transition-colors text-gray-100 flex items-center gap-1">
                        Financeiro
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>
                    <div class="absolute left-0 mt-1 w-48 bg-white text-gray-800 rounded-lg shadow-xl py-2 hidden group-hover:block border border-gray-100 z-50">
                        <a href="<?= baseUrl('modules/financeiro/fluxo_caixa.php') ?>" class="block px-4 py-2 hover:bg-gray-100 text-sm font-medium">Fluxo de Caixa</a>
                        <a href="<?= baseUrl('modules/financeiro/faturamento.php') ?>" class="block px-4 py-2 hover:bg-gray-100 text-sm font-medium">Faturamento</a>
                    </div>
                </div>

                <a href="<?= baseUrl('modules/rh/index.php') ?>" 
                   class="px-3 py-2 rounded-md hover:bg-white/10 transition-colors <?= strpos($currentScript, 'rh') !== false ? 'bg-white/15 text-lee-accent' : 'text-gray-100' ?>">
                    RH
                </a>

                <a href="<?= baseUrl('modules/auditoria/index.php') ?>" 
                   class="px-3 py-2 rounded-md hover:bg-white/10 transition-colors <?= strpos($currentScript, 'auditoria') !== false ? 'bg-white/15 text-lee-accent' : 'text-gray-100' ?>">
                    Auditoria
                </a>
                <?php endif; ?>
            </nav>

            <!-- Usuário e Logout Desktop -->
            <div class="hidden md:flex items-center gap-3">
                <div class="text-right">
                    <div class="text-sm font-bold text-white leading-tight"><?= htmlspecialchars($user['nome']) ?></div>
                    <div class="text-xs text-lee-accent font-semibold uppercase tracking-wider">
                        <?= htmlspecialchars($user['cargo']) ?>
                    </div>
                </div>
                <a href="<?= baseUrl('modules/auth/logout.php') ?>" 
                   title="Sair do Sistema"
                   class="p-2 rounded-lg bg-white/10 hover:bg-red-600/80 text-white transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                </a>
            </div>

            <!-- Botão Mobile Menu (Hambúrguer) -->
            <div class="flex md:hidden items-center gap-2">
                <button type="button" id="mobile-menu-btn" class="p-2 rounded-lg bg-white/10 text-white hover:bg-white/20 focus:outline-none">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path></svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Menu Gaveta Mobile -->
    <div id="mobile-menu" class="hidden md:hidden bg-lee-primary-dark border-t border-white/10 px-4 pt-3 pb-5 space-y-2 text-sm font-medium">
        <div class="flex items-center justify-between pb-3 border-b border-white/10 mb-2">
            <div>
                <div class="font-bold text-white"><?= htmlspecialchars($user['nome']) ?></div>
                <div class="text-xs text-lee-accent font-semibold uppercase"><?= htmlspecialchars($user['cargo']) ?></div>
            </div>
            <a href="<?= baseUrl('modules/auth/logout.php') ?>" class="px-3 py-1 bg-red-600 text-white rounded text-xs font-semibold hover:bg-red-700">
                Sair
            </a>
        </div>

        <a href="<?= baseUrl('modules/dashboard/index.php') ?>" class="block px-3 py-2 rounded text-white hover:bg-white/10">Dashboard</a>
        
        <?php if ($cargo === 'caixa' || $cargo === 'dono'): ?>
        <a href="<?= baseUrl('modules/vendas/pdv.php') ?>" class="block px-3 py-2 rounded bg-lee-accent text-lee-black font-bold">PDV - Frente de Caixa</a>
        <?php endif; ?>

        <?php if ($cargo === 'dono' || $cargo === 'gerente'): ?>
        <a href="<?= baseUrl('modules/vendas/historico.php') ?>" class="block px-3 py-2 rounded text-white hover:bg-white/10">Histórico de Vendas</a>
        <?php endif; ?>

        <a href="<?= baseUrl('modules/produtos/index.php') ?>" class="block px-3 py-2 rounded text-white hover:bg-white/10">Produtos</a>
        <a href="<?= baseUrl('modules/estoque/index.php') ?>" class="block px-3 py-2 rounded text-white hover:bg-white/10">Estoque</a>

        <?php if ($cargo === 'dono' || $cargo === 'gerente'): ?>
        <a href="<?= baseUrl('modules/fornecedores/index.php') ?>" class="block px-3 py-2 rounded text-white hover:bg-white/10">Fornecedores</a>
        <a href="<?= baseUrl('modules/relatorios/vendas.php') ?>" class="block px-3 py-2 rounded text-white hover:bg-white/10">Relatórios</a>
        <?php endif; ?>

        <?php if ($cargo === 'dono'): ?>
        <a href="<?= baseUrl('modules/financeiro/fluxo_caixa.php') ?>" class="block px-3 py-2 rounded text-white hover:bg-white/10">Fluxo de Caixa</a>
        <a href="<?= baseUrl('modules/financeiro/faturamento.php') ?>" class="block px-3 py-2 rounded text-white hover:bg-white/10">Faturamento</a>
        <a href="<?= baseUrl('modules/rh/index.php') ?>" class="block px-3 py-2 rounded text-white hover:bg-white/10">Gestão de RH</a>
        <a href="<?= baseUrl('modules/auditoria/index.php') ?>" class="block px-3 py-2 rounded text-white hover:bg-white/10">Auditoria do Sistema</a>
        <?php endif; ?>
    </div>
</header>
<script>
    const mobileBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');
    if (mobileBtn && mobileMenu) {
        mobileBtn.addEventListener('click', () => {
            mobileMenu.classList.toggle('hidden');
        });
    }
</script>
<?php endif; ?>
