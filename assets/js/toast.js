/**
 * Gerenciador de Notificações Toast do SIGC - Lee Market
 * Responsivo, elegante e compatível com as diretrizes do projeto.
 */

const Toast = {
    container: null,

    init() {
        if (!this.container) {
            this.container = document.getElementById('toast-container');
            if (!this.container) {
                this.container = document.createElement('div');
                this.container.id = 'toast-container';
                document.body.appendChild(this.container);
            }
        }
    },

    show(mensagem, tipo = 'info', duracao = 4500) {
        this.init();

        const toast = document.createElement('div');
        toast.className = `toast-item toast-${tipo}`;

        // Ícones específicos para cada tipo
        let iconeSvg = '';
        if (tipo === 'sucesso') {
            iconeSvg = `<svg class="w-6 h-6 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>`;
        } else if (tipo === 'erro') {
            iconeSvg = `<svg class="w-6 h-6 text-red-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
        } else if (tipo === 'aviso') {
            iconeSvg = `<svg class="w-6 h-6 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>`;
        } else {
            iconeSvg = `<svg class="w-6 h-6 text-blue-700 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`;
        }

        const tituloTipo = {
            sucesso: 'Sucesso',
            erro: 'Erro / Atenção',
            aviso: 'Aviso',
            info: 'Informação'
        }[tipo] || 'Notificação';

        toast.innerHTML = `
            ${iconeSvg}
            <div class="flex-1">
                <div class="text-xs uppercase tracking-wider font-bold text-gray-500 mb-0.5">${tituloTipo}</div>
                <div class="text-sm font-medium text-gray-900 leading-snug">${mensagem}</div>
            </div>
            <button type="button" class="text-gray-400 hover:text-gray-700 ml-2 focus:outline-none" onclick="this.parentElement.remove()">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        `;

        this.container.appendChild(toast);

        setTimeout(() => {
            if (toast.parentElement) {
                toast.style.animation = 'toastSlideOut 0.3s forwards';
                setTimeout(() => toast.remove(), 300);
            }
        }, duracao);
    }
};

window.Toast = Toast;
