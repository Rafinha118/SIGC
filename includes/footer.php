<?php
/**
 * Rodapé Unificado - SIGC Lee Market
 * Inicialização de scripts, modais globais e renderizador de Toast
 */
?>
    <footer class="mt-auto bg-white border-t border-gray-200 py-4 text-center text-xs text-gray-500">
        <div class="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
            <div>
                &copy; <?= date('Y') ?> <strong class="text-lee-primary">Lee Market</strong> — Sistema Integrado de Gestão Comercial (SIGC).
            </div>
            <div class="flex items-center gap-3">
                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-blue-50 text-lee-primary border border-blue-200">
                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span> Sistema Ativo
                </span>
            </div>
        </div>
    </footer>

    <!-- Contêiner de Notificações Toast -->
    <div id="toast-container"></div>

    <!-- Modal Global Lee Market -->
    <div id="lee-global-modal" class="lee-modal-overlay"></div>

    <!-- Scripts de Interface -->
    <script src="<?= baseUrl('assets/js/toast.js') ?>"></script>
    <script src="<?= baseUrl('assets/js/modal.js') ?>"></script>

    <!-- Disparador Automático de Toast via Sessão PHP -->
    <?php if (isset($_SESSION['toast'])): ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const toastData = <?= json_encode($_SESSION['toast']) ?>;
            if (toastData && toastData.mensagem) {
                Toast.show(toastData.mensagem, toastData.tipo || 'info');
            }
        });
    </script>
    <?php unset($_SESSION['toast']); endif; ?>
</body>
</html>
