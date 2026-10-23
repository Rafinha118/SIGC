<?php
/**
 * Tela de Login - SIGC Lee Market
 * Requisitos:
 * 1. Exibe os campos de CPF, nome completo e senha
 * 2. Valida os dados e identifica o perfil do usuário (Dono, Gerente, Operador de Caixa)
 * 3. Identidade visual Lee Market (Azul-escuro #111184, Amarelo #FFD32C, Fonte Isometra)
 */

require_once __DIR__ . '/../../config/auth.php';

// Se já estiver logado, vai direto para o dashboard
if (estaAutenticado()) {
    header('Location: ' . baseUrl('modules/dashboard/index.php'));
    exit;
}

$pageTitle = 'Login — SIGC Lee Market';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="min-h-screen flex flex-col justify-center py-12 sm:px-6 lg:px-8 bg-gradient-to-br from-blue-50 via-white to-amber-50">
    <div class="sm:mx-auto sm:w-full sm:max-w-md">
        <!-- Logo Lee Market -->
        <div class="flex justify-center">
            <div class="w-16 h-16 rounded-2xl bg-lee-primary shadow-lg flex items-center justify-center border-2 border-lee-accent">
                <span class="text-3xl font-black text-lee-accent">L</span>
            </div>
        </div>
        <h2 class="mt-4 text-center text-3xl font-extrabold text-lee-primary tracking-tight">
            Lee <span class="text-amber-500">Market</span>
        </h2>
        <p class="mt-1 text-center text-sm font-medium text-gray-600">
            Sistema Integrado de Gestão Comercial — SIGC
        </p>
    </div>

    <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md px-4">
        <div class="bg-white py-8 px-6 shadow-xl rounded-2xl sm:px-10 border border-gray-100">
            <h3 class="text-lg font-bold text-gray-900 mb-6 text-center border-b pb-3">
                Acesse sua conta
            </h3>

            <form action="<?= baseUrl('modules/auth/process_login.php') ?>" method="POST" class="space-y-5" id="form-login">
                <!-- 1. Campo CPF -->
                <div>
                    <label for="cpf" class="block text-sm font-semibold text-gray-800">
                        CPF
                    </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
                        </div>
                        <input type="text" name="cpf" id="cpf" required maxlength="14" placeholder="000.000.000-00"
                               class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lee-primary focus:border-lee-primary text-sm font-medium transition-all">
                    </div>
                </div>

                <!-- 2. Campo Nome Completo -->
                <div>
                    <label for="nome" class="block text-sm font-semibold text-gray-800">
                        Nome Completo
                    </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        </div>
                        <input type="text" name="nome" id="nome" required placeholder="Ex: Carlos Dono"
                               class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lee-primary focus:border-lee-primary text-sm font-medium transition-all">
                    </div>
                </div>

                <!-- 3. Campo Senha -->
                <div>
                    <label for="senha" class="block text-sm font-semibold text-gray-800">
                        Senha de Acesso
                    </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        </div>
                        <input type="password" name="senha" id="senha" required placeholder="••••••••"
                               class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-lee-primary focus:border-lee-primary text-sm font-medium transition-all">
                    </div>
                </div>

                <!-- Botão de Login -->
                <div class="pt-2">
                    <button type="submit" 
                            class="w-full btn-lee-primary py-3 text-base shadow-md hover:shadow-lg flex items-center justify-center gap-2">
                        <span>Acessar o Sistema</span>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                    </button>
                </div>
            </form>

            <!-- Acesso Rápido para Demonstração / Testes dos Requisitos -->
            <div class="mt-8 pt-6 border-t border-gray-200">
                <p class="text-xs font-bold text-gray-500 uppercase tracking-wider text-center mb-3">
                    Acesso Rápido (Contas de Demonstração):
                </p>
                <div class="grid grid-cols-3 gap-2">
                    <button type="button" onclick="preencherLogin('111.111.111-11', 'Carlos Dono', 'dono123')"
                            class="px-2 py-1.5 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded text-center transition-colors">
                        <div class="font-bold text-xs text-lee-primary">Dono</div>
                        <div class="text-[10px] text-gray-500">Acesso Total</div>
                    </button>

                    <button type="button" onclick="preencherLogin('222.222.222-22', 'Mariana Gerente', 'gerente123')"
                            class="px-2 py-1.5 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded text-center transition-colors">
                        <div class="font-bold text-xs text-amber-700">Gerente</div>
                        <div class="text-[10px] text-gray-500">Estoque/Vendas</div>
                    </button>

                    <button type="button" onclick="preencherLogin('333.333.333-33', 'Lucas Caixa', 'caixa123')"
                            class="px-2 py-1.5 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded text-center transition-colors">
                        <div class="font-bold text-xs text-emerald-700">Caixa</div>
                        <div class="text-[10px] text-gray-500">PDV/Saídas</div>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Máscara dinâmica de CPF
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

    function preencherLogin(cpf, nome, senha) {
        document.getElementById('cpf').value = cpf;
        document.getElementById('nome').value = nome;
        document.getElementById('senha').value = senha;
    }
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
