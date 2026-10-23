/**
 * Gerenciador de Modais Personalizados do SIGC - Lee Market
 * Substitui caixas de diálogo nativas (alert/confirm) mantendo a identidade visual.
 */

const LeeModal = {
    overlay: null,

    init() {
        if (!this.overlay) {
            this.overlay = document.getElementById('lee-global-modal');
            if (!this.overlay) {
                this.overlay = document.createElement('div');
                this.overlay.id = 'lee-global-modal';
                this.overlay.className = 'lee-modal-overlay';
                document.body.appendChild(this.overlay);
            }
        }
    },

    alert(titulo, mensagem, callback = null) {
        this.init();

        this.overlay.innerHTML = `
            <div class="lee-modal-box">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center flex-shrink-0 text-lee-primary">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">${titulo}</h3>
                    </div>
                    <div class="text-gray-700 text-sm mb-6 leading-relaxed">
                        ${mensagem}
                    </div>
                    <div class="flex justify-end">
                        <button type="button" id="lee-modal-ok-btn" class="btn-lee-primary">
                            Entendi
                        </button>
                    </div>
                </div>
            </div>
        `;

        this.overlay.classList.add('active');

        const btnOk = document.getElementById('lee-modal-ok-btn');
        btnOk.focus();
        btnOk.onclick = () => {
            this.close();
            if (typeof callback === 'function') callback();
        };
    },

    confirm(titulo, mensagem, onConfirm, onCancel = null, confirmText = 'Confirmar', cancelText = 'Cancelar') {
        this.init();

        this.overlay.innerHTML = `
            <div class="lee-modal-box">
                <div class="p-6">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center flex-shrink-0 text-amber-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">${titulo}</h3>
                    </div>
                    <div class="text-gray-700 text-sm mb-6 leading-relaxed">
                        ${mensagem}
                    </div>
                    <div class="flex justify-end gap-3">
                        <button type="button" id="lee-modal-cancel-btn" class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 font-semibold hover:bg-gray-100 transition-colors">
                            ${cancelText}
                        </button>
                        <button type="button" id="lee-modal-confirm-btn" class="btn-lee-accent">
                            ${confirmText}
                        </button>
                    </div>
                </div>
            </div>
        `;

        this.overlay.classList.add('active');

        const btnConfirm = document.getElementById('lee-modal-confirm-btn');
        const btnCancel = document.getElementById('lee-modal-cancel-btn');

        btnConfirm.onclick = () => {
            this.close();
            if (typeof onConfirm === 'function') onConfirm();
        };

        btnCancel.onclick = () => {
            this.close();
            if (typeof onCancel === 'function') onCancel();
        };
    },

    close() {
        if (this.overlay) {
            this.overlay.classList.remove('active');
        }
    }
};

window.LeeModal = LeeModal;
