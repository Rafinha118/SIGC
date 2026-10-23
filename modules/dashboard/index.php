<?php
/**
 * Dashboard Central - SIGC Lee Market
 * Adaptado dinamicamente para Dono, Gerente e Operador de Caixa
 */

require_once __DIR__ . '/../../config/bd.php';
require_once __DIR__ . '/../../config/auth.php';

exigirAutenticacao();

$user = usuarioAtual();
$cargo = strtolower($user['cargo']);
$pdo = getDB();

$pageTitle = 'Dashboard — Lee Market';
require_once __DIR__ . '/../../includes/header.php';
require_once __DIR__ . '/../../includes/navbar.php';

// Consultas agregadas para as métricas
try {
    // 1. Contagem de produtos e estoque baixo
    $stmtProd = $pdo->query("SELECT COUNT(*) AS total, SUM(CASE WHEN estoque_atual <= estoque_minimo THEN 1 ELSE 0 END) AS baixo FROM produtos WHERE ativo = 1");
    $metricasProd = $stmtProd->fetch() ?: ['total' => 0, 'baixo' => 0];

    // 2. Vendas de Hoje
    $hojeInicio = date('Y-m-d 00:00:00');
    $hojeFim = date('Y-m-d 23:59:59');
    
    $stmtVendasHoje = $pdo->prepare("SELECT COUNT(*) AS qtd, COALESCE(SUM(total), 0) AS total FROM vendas WHERE criado_em BETWEEN ? AND ?");
    $stmtVendasHoje->execute([$hojeInicio, $hojeFim]);
    $vendasHoje = $stmtVendasHoje->fetch() ?: ['qtd' => 0, 'total' => 0];

    // 3. Saldo do Fluxo de Caixa (Dono)
    $stmtFluxo = $pdo->query("
        SELECT 
            COALESCE(SUM(CASE WHEN tipo = 'ENTRADA' THEN valor ELSE 0 END), 0) AS entradas,
            COALESCE(SUM(CASE WHEN tipo = 'SAIDA' THEN valor ELSE 0 END), 0) AS saidas
        FROM fluxo_caixa
    ");
    $fluxo = $stmtFluxo->fetch() ?: ['entradas' => 0, 'saidas' => 0];
    $saldoCaixa = (float)$fluxo['entradas'] - (float)$fluxo['saidas'];

    // 4. Total de Colaboradores (Dono)
    $totalColab = (int)$pdo->query("SELECT COUNT(*) FROM usuarios WHERE status = 1")->fetchColumn();

    // 5. Total de Fornecedores
    $totalFornec = (int)$pdo->query("SELECT COUNT(*) FROM fornecedores")->fetchColumn();

    // 6. Últimos logs de auditoria (Dono)
    $ultimosLogs = [];
    if ($cargo === 'dono') {
        $stmtLogs = $pdo->query("SELECT * FROM auditoria_logs ORDER BY criado_em DESC LIMIT 6");
        $ultimosLogs = $stmtLogs->fetchAll();
    }

    // 7. Produtos em alerta de estoque (Dono e Gerente)
    $produtosAlerta = $pdo->query("SELECT id, codigo_barras, nome, categoria, estoque_atual, estoque_minimo FROM produtos WHERE ativo = 1 AND estoque_atual <= estoque_minimo ORDER BY estoque_atual ASC LIMIT 5")->fetchAll();

    // 8. Minhas vendas hoje (Caixa)
    $stmtMinhasVendas = $pdo->prepare("SELECT COUNT(*) AS qtd, COALESCE(SUM(total), 0) AS total FROM vendas WHERE usuario_id = ? AND criado_em BETWEEN ? AND ?");
    $stmtMinhasVendas->execute([$user['id'], $hojeInicio, $hojeFim]);
    $minhasVendas = $stmtMinhasVendas->fetch() ?: ['qtd' => 0, 'total' => 0];

} catch (Throwable $e) {
    error_log("Erro no dashboard: " . $e->getMessage());
}
?>

<main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full flex-1">
    <!-- Boas-vindas e Banner -->
    <div class="bg-gradient-to-r from-lee-primary to-blue-900 rounded-2xl p-6 sm:p-8 text-white shadow-lg mb-8 relative overflow-hidden">
        <div class="relative z-10">
            <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-lee-accent/20 text-lee-accent text-xs font-bold uppercase tracking-wider mb-3">
                <span class="w-2 h-2 rounded-full bg-lee-accent animate-pulse"></span>
                Perfil Conectado: <?= htmlspecialchars(ucfirst($cargo)) ?>
            </div>
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight">
                Olá, <?= htmlspecialchars($user['nome']) ?>!
            </h1>
            <p class="text-blue-100 text-sm sm:text-base mt-1 max-w-2xl">
                Bem-vindo ao painel central do <strong>Lee Market</strong>. Aqui você gerencia todas as rotinas operacionais, comerciais e financeiras do supermercado.
            </p>
        </div>
        <!-- Efeito decorativo -->
        <div class="absolute -right-8 -bottom-10 w-48 h-48 bg-white/5 rounded-full pointer-events-none"></div>
    </div>

    <!-- ===================================================================== -->
    <!-- VISÃO ESPECÍFICA: DONO (Acesso Geral, Financeiro, RH, Auditoria) -->
    <!-- ===================================================================== -->
    <?php if ($cargo === 'dono'): ?>
    <div class="space-y-8">
        <!-- Cards de Métricas do Dono -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <!-- Faturamento Hoje -->
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Vendas de Hoje</span>
                    <h3 class="text-2xl font-bold text-gray-900">R$ <?= number_format((float)$vendasHoje['total'], 2, ',', '.') ?></h3>
                    <span class="text-xs text-gray-500"><?= (int)$vendasHoje['qtd'] ?> transações hoje</span>
                </div>
            </div>

            <!-- Saldo em Caixa -->
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-lee-primary flex items-center justify-center flex-shrink-0">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"></path></svg>
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Saldo do Caixa</span>
                    <h3 class="text-2xl font-bold <?= $saldoCaixa >= 0 ? 'text-gray-900' : 'text-red-600' ?>">
                        R$ <?= number_format($saldoCaixa, 2, ',', '.') ?>
                    </h3>
                    <a href="<?= baseUrl('modules/financeiro/fluxo_caixa.php') ?>" class="text-xs text-lee-primary font-bold hover:underline">Ver fluxo completo &rarr;</a>
                </div>
            </div>

            <!-- Alerta de Estoque -->
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl <?= (int)$metricasProd['baixo'] > 0 ? 'bg-amber-50 text-amber-600' : 'bg-gray-50 text-gray-500' ?> flex items-center justify-center flex-shrink-0">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Estoque Baixo</span>
                    <h3 class="text-2xl font-bold <?= (int)$metricasProd['baixo'] > 0 ? 'text-amber-600' : 'text-gray-900' ?>">
                        <?= (int)$metricasProd['baixo'] ?>
                    </h3>
                    <span class="text-xs text-gray-500">de <?= (int)$metricasProd['total'] ?> cadastrados</span>
                </div>
            </div>

            <!-- Equipe / Colaboradores -->
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Colaboradores</span>
                    <h3 class="text-2xl font-bold text-gray-900"><?= $totalColab ?> ativos</h3>
                    <a href="<?= baseUrl('modules/rh/index.php') ?>" class="text-xs text-lee-primary font-bold hover:underline">Gestão de RH &rarr;</a>
                </div>
            </div>
        </div>

        <!-- Ações Rápidas de Gestão -->
        <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
            <h2 class="text-base font-bold text-gray-900 mb-4">Ações Rápidas de Gestão Comercial</h2>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-6 gap-3">
                <a href="<?= baseUrl('modules/vendas/pdv.php') ?>" class="p-3 bg-amber-50 hover:bg-amber-100 border border-amber-200 rounded-lg text-center transition-all group">
                    <div class="text-lee-black font-bold text-sm group-hover:scale-105 transition-transform">Frente de Caixa</div>
                    <div class="text-xs text-gray-500 mt-0.5">Abrir PDV</div>
                </a>
                <a href="<?= baseUrl('modules/produtos/form.php') ?>" class="p-3 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg text-center transition-all group">
                    <div class="text-lee-primary font-bold text-sm group-hover:scale-105 transition-transform">+ Novo Produto</div>
                    <div class="text-xs text-gray-500 mt-0.5">Cadastrar item</div>
                </a>
                <a href="<?= baseUrl('modules/financeiro/fluxo_caixa.php') ?>" class="p-3 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg text-center transition-all group">
                    <div class="text-gray-900 font-bold text-sm group-hover:scale-105 transition-transform">Fluxo de Caixa</div>
                    <div class="text-xs text-gray-500 mt-0.5">Entradas e saídas</div>
                </a>
                <a href="<?= baseUrl('modules/financeiro/faturamento.php') ?>" class="p-3 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg text-center transition-all group">
                    <div class="text-gray-900 font-bold text-sm group-hover:scale-105 transition-transform">Faturamento</div>
                    <div class="text-xs text-gray-500 mt-0.5">Receitas e margens</div>
                </a>
                <a href="<?= baseUrl('modules/rh/form.php') ?>" class="p-3 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg text-center transition-all group">
                    <div class="text-gray-900 font-bold text-sm group-hover:scale-105 transition-transform">+ Colaborador</div>
                    <div class="text-xs text-gray-500 mt-0.5">Gestão de equipe</div>
                </a>
                <a href="<?= baseUrl('modules/auditoria/index.php') ?>" class="p-3 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg text-center transition-all group">
                    <div class="text-gray-900 font-bold text-sm group-hover:scale-105 transition-transform">Auditoria</div>
                    <div class="text-xs text-gray-500 mt-0.5">Logs completos</div>
                </a>
            </div>
        </div>

        <!-- Seção Dupla: Alertas de Estoque & Histórico de Auditoria em Tempo Real -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Alertas de Reposição de Estoque -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        Reposição de Estoque Recomendada
                    </h2>
                    <a href="<?= baseUrl('modules/estoque/index.php') ?>" class="text-xs font-bold text-lee-primary hover:underline">Ver estoque completo &rarr;</a>
                </div>

                <?php if (empty($produtosAlerta)): ?>
                <div class="p-6 text-center text-gray-500 text-sm bg-gray-50 rounded-lg">
                    Nenhum produto com estoque crítico no momento.
                </div>
                <?php else: ?>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead>
                            <tr class="text-left text-xs font-semibold text-gray-500 uppercase">
                                <th class="pb-2">Produto</th>
                                <th class="pb-2">Categoria</th>
                                <th class="pb-2 text-center">Atual</th>
                                <th class="pb-2 text-center">Mínimo</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php foreach ($produtosAlerta as $p): ?>
                            <tr>
                                <td class="py-2.5 font-medium text-gray-900"><?= htmlspecialchars($p['nome']) ?></td>
                                <td class="py-2.5 text-gray-500 text-xs"><?= htmlspecialchars($p['categoria']) ?></td>
                                <td class="py-2.5 text-center font-bold text-red-600"><?= (int)$p['estoque_atual'] ?></td>
                                <td class="py-2.5 text-center text-gray-500"><?= (int)$p['estoque_minimo'] ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php endif; ?>
            </div>

            <!-- Últimas Ações de Auditoria -->
            <div class="bg-white rounded-xl p-6 shadow-sm border border-gray-100">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-base font-bold text-gray-900 flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-lee-primary"></span>
                        Últimos Registros de Auditoria
                    </h2>
                    <a href="<?= baseUrl('modules/auditoria/index.php') ?>" class="text-xs font-bold text-lee-primary hover:underline">Histórico completo &rarr;</a>
                </div>

                <div class="space-y-3">
                    <?php if (empty($ultimosLogs)): ?>
                    <p class="text-sm text-gray-500 text-center py-4">Nenhum log registrado ainda.</p>
                    <?php else: ?>
                    <?php foreach ($ultimosLogs as $log): ?>
                    <div class="p-3 rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors flex items-start justify-between gap-3 text-xs">
                        <div>
                            <div class="font-bold text-gray-900">
                                <?= htmlspecialchars($log['usuario_nome'] ?: 'Sistema') ?> 
                                <span class="font-normal text-gray-500">(<?= htmlspecialchars($log['setor']) ?>)</span>
                            </div>
                            <div class="text-gray-600 mt-0.5"><?= htmlspecialchars($log['acao']) ?>: <?= htmlspecialchars($log['detalhes']) ?></div>
                        </div>
                        <span class="text-gray-400 whitespace-nowrap text-[11px]"><?= date('d/m H:i', strtotime($log['criado_em'])) ?></span>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- VISÃO ESPECÍFICA: GERENTE (Estoque, Vendas, Fornecedores) -->
    <!-- ===================================================================== -->
    <?php elseif ($cargo === 'gerente'): ?>
    <div class="space-y-8">
        <!-- Cards de Métricas do Gerente -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-lee-primary flex items-center justify-center flex-shrink-0">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path></svg>
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Produtos Ativos</span>
                    <h3 class="text-2xl font-bold text-gray-900"><?= (int)$metricasProd['total'] ?></h3>
                    <a href="<?= baseUrl('modules/produtos/index.php') ?>" class="text-xs text-lee-primary font-bold hover:underline">Ver catálogo &rarr;</a>
                </div>
            </div>

            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Abaixo do Mínimo</span>
                    <h3 class="text-2xl font-bold text-amber-600"><?= (int)$metricasProd['baixo'] ?></h3>
                    <a href="<?= baseUrl('modules/estoque/index.php') ?>" class="text-xs text-amber-600 font-bold hover:underline">Repor estoque &rarr;</a>
                </div>
            </div>

            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Vendas do Dia</span>
                    <h3 class="text-2xl font-bold text-gray-900"><?= (int)$vendasHoje['qtd'] ?></h3>
                    <span class="text-xs text-gray-500">R$ <?= number_format((float)$vendasHoje['total'], 2, ',', '.') ?></span>
                </div>
            </div>

            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Fornecedores</span>
                    <h3 class="text-2xl font-bold text-gray-900"><?= $totalFornec ?></h3>
                    <a href="<?= baseUrl('modules/fornecedores/index.php') ?>" class="text-xs text-lee-primary font-bold hover:underline">Ver fornecedores &rarr;</a>
                </div>
            </div>
        </div>

        <!-- Ações do Gerente -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
            <a href="<?= baseUrl('modules/estoque/entrada.php') ?>" class="p-6 bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-all flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-100 text-emerald-700 flex items-center justify-center flex-shrink-0 font-bold text-xl">+</div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Registrar Entrada / Ajuste de Estoque</h3>
                    <p class="text-xs text-gray-500 mt-1">Dar entrada em mercadorias recebidas de fornecedores</p>
                </div>
            </a>

            <a href="<?= baseUrl('modules/relatorios/vendas.php') ?>" class="p-6 bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-all flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-blue-100 text-lee-primary flex items-center justify-center flex-shrink-0 font-bold text-xl">📊</div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Relatórios de Vendas por Período</h3>
                    <p class="text-xs text-gray-500 mt-1">Analisar volume vendido e produtos mais procurados</p>
                </div>
            </a>

            <a href="<?= baseUrl('modules/fornecedores/index.php') ?>" class="p-6 bg-white rounded-xl border border-gray-200 shadow-sm hover:shadow-md transition-all flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-purple-100 text-purple-700 flex items-center justify-center flex-shrink-0 font-bold text-xl">🚚</div>
                <div>
                    <h3 class="text-base font-bold text-gray-900">Consulta de Fornecedores</h3>
                    <p class="text-xs text-gray-500 mt-1">Contatos e catálogo de produtos vinculados</p>
                </div>
            </a>
        </div>
    </div>

    <!-- ===================================================================== -->
    <!-- VISÃO ESPECÍFICA: OPERADOR DE CAIXA (PDV, Saída de Estoque, Consulta) -->
    <!-- ===================================================================== -->
    <?php else: ?>
    <div class="space-y-8">
        <!-- Banner Central para Abertura Imediata do PDV -->
        <div class="bg-white rounded-2xl p-8 border-2 border-lee-accent shadow-md text-center">
            <div class="max-w-md mx-auto">
                <div class="w-16 h-16 rounded-full bg-amber-100 text-amber-600 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                </div>
                <h2 class="text-2xl font-extrabold text-gray-900 mb-2">Frente de Caixa (PDV)</h2>
                <p class="text-sm text-gray-600 mb-6">
                    Inicie o atendimento aos clientes para bipar produtos, selecionar formas de pagamento e finalizar as vendas com emissão de recibo.
                </p>
                <a href="<?= baseUrl('modules/vendas/pdv.php') ?>" 
                   class="btn-lee-accent w-full py-4 text-lg shadow-lg hover:shadow-xl justify-center">
                    ABRIR CAIXA E VENDER AGORA
                </a>
            </div>
        </div>

        <!-- Cards Operacionais do Caixa -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <!-- Minhas Vendas Hoje -->
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 flex items-center gap-4">
                <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Minhas Vendas Hoje</span>
                    <h3 class="text-2xl font-bold text-gray-900">R$ <?= number_format((float)$minhasVendas['total'], 2, ',', '.') ?></h3>
                    <span class="text-xs text-gray-500"><?= (int)$minhasVendas['qtd'] ?> clientes atendidos</span>
                </div>
            </div>

            <!-- Saída de Estoque (Avaria / Vencimento) -->
            <a href="<?= baseUrl('modules/estoque/saida.php') ?>" class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:border-lee-primary flex items-center gap-4 transition-all">
                <div class="w-12 h-12 rounded-xl bg-rose-50 text-rose-600 flex items-center justify-center flex-shrink-0">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Operações de Loja</span>
                    <h3 class="text-base font-bold text-gray-900">Registrar Saída de Estoque</h3>
                    <span class="text-xs text-rose-600 font-semibold">Baixa por avaria ou perda &rarr;</span>
                </div>
            </a>

            <!-- Consulta Rápida de Preço e Estoque -->
            <a href="<?= baseUrl('modules/produtos/index.php') ?>" class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 hover:border-lee-primary flex items-center gap-4 transition-all">
                <div class="w-12 h-12 rounded-xl bg-blue-50 text-lee-primary flex items-center justify-center flex-shrink-0">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
                <div>
                    <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Atendimento</span>
                    <h3 class="text-base font-bold text-gray-900">Consultar Preço & Estoque</h3>
                    <span class="text-xs text-lee-primary font-semibold">Busca instantânea de produtos &rarr;</span>
                </div>
            </a>
        </div>
    </div>
    <?php endif; ?>
</main>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
