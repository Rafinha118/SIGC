/**
 * PDV - Frente de Caixa Interativo (Lee Market)
 * Gerencia carrinho de compras, cálculo de troco e envio de pedidos
 */

const PDV = {
    carrinho: [],
    produtosCatalogo: [],

    init(produtos) {
        this.produtosCatalogo = produtos || [];
        this.renderCarrinho();
        this.setupBusca();
        this.setupFormaPagamento();
    },

    adicionarItem(produtoId, quantidade = 1) {
        const prod = this.produtosCatalogo.find(p => parseInt(p.id, 10) === parseInt(produtoId, 10));
        if (!prod) {
            Toast.show('Produto não localizado.', 'erro');
            return;
        }

        const estoqueDisponivel = parseInt(prod.estoque_atual, 10);
        const itemExistente = this.carrinho.find(i => i.id === prod.id);
        const qtdAtualNoCarrinho = itemExistente ? itemExistente.quantidade : 0;

        if (qtdAtualNoCarrinho + quantidade > estoqueDisponivel) {
            Toast.show(`Estoque insuficiente! Disponível: ${estoqueDisponivel} un.`, 'aviso');
            return;
        }

        if (itemExistente) {
            itemExistente.quantidade += quantidade;
            itemExistente.subtotal = itemExistente.quantidade * itemExistente.preco;
        } else {
            this.carrinho.push({
                id: prod.id,
                nome: prod.nome,
                codigo: prod.codigo_barras,
                preco: parseFloat(prod.preco_venda),
                quantidade: quantidade,
                subtotal: quantidade * parseFloat(prod.preco_venda)
            });
        }

        Toast.show(`Item adicionado: ${prod.nome}`, 'info', 2000);
        this.renderCarrinho();
    },

    alterarQuantidade(produtoId, novaQtd) {
        const item = this.carrinho.find(i => i.id === produtoId);
        if (!item) return;

        const prod = this.produtosCatalogo.find(p => p.id === produtoId);
        const max = prod ? parseInt(prod.estoque_atual, 10) : 999;

        novaQtd = parseInt(novaQtd, 10);
        if (isNaN(novaQtd) || novaQtd <= 0) {
            this.removerItem(produtoId);
            return;
        }

        if (novaQtd > max) {
            Toast.show(`Quantidade máxima permitida pelo estoque: ${max} un.`, 'aviso');
            novaQtd = max;
        }

        item.quantidade = novaQtd;
        item.subtotal = item.quantidade * item.preco;
        this.renderCarrinho();
    },

    removerItem(produtoId) {
        this.carrinho = this.carrinho.filter(i => i.id !== produtoId);
        this.renderCarrinho();
    },

    limparCarrinho() {
        if (this.carrinho.length === 0) return;
        LeeModal.confirm('Limpar Carrinho', 'Deseja cancelar a venda atual e limpar todos os itens?', () => {
            this.carrinho = [];
            this.renderCarrinho();
            Toast.show('Venda cancelada e carrinho limpo.', 'info');
        });
    },

    calcularTotal() {
        return this.carrinho.reduce((acc, item) => acc + item.subtotal, 0);
    },

    renderCarrinho() {
        const tbody = document.getElementById('pdv-itens-tabela');
        const totalSpan = document.getElementById('pdv-total-valor');
        const qtdItensSpan = document.getElementById('pdv-qtd-itens');
        const btnFinalizar = document.getElementById('pdv-btn-finalizar');

        if (!tbody) return;

        tbody.innerHTML = '';
        const total = this.calcularTotal();

        if (this.carrinho.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="5" class="py-12 text-center text-gray-400">
                        <svg class="w-12 h-12 mx-auto mb-2 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                        Nenhum item adicionado ao cupom fiscal ainda.<br>
                        Bipe o código de barras ou selecione os itens ao lado.
                    </td>
                </tr>
            `;
            if (btnFinalizar) btnFinalizar.disabled = true;
        } else {
            if (btnFinalizar) btnFinalizar.disabled = false;

            this.carrinho.forEach((item, index) => {
                const tr = document.createElement('tr');
                tr.className = 'border-b border-gray-100 hover:bg-blue-50/30 transition-colors';
                tr.innerHTML = `
                    <td class="py-3 px-3 text-xs text-gray-400 font-mono">${index + 1}</td>
                    <td class="py-3 px-3">
                        <div class="font-bold text-gray-900 text-sm">${item.nome}</div>
                        <div class="text-[11px] font-mono text-gray-400">${item.codigo}</div>
                    </td>
                    <td class="py-3 px-3 text-center whitespace-nowrap">
                        <div class="inline-flex items-center border border-gray-300 rounded-md">
                            <button type="button" class="px-2 py-0.5 text-gray-600 hover:bg-gray-100 font-bold" onclick="PDV.alterarQuantidade(${item.id}, ${item.quantidade - 1})">-</button>
                            <input type="number" min="1" value="${item.quantidade}" 
                                   class="w-12 text-center text-xs font-bold border-none focus:ring-0 py-0.5" 
                                   onchange="PDV.alterarQuantidade(${item.id}, this.value)">
                            <button type="button" class="px-2 py-0.5 text-gray-600 hover:bg-gray-100 font-bold" onclick="PDV.alterarQuantidade(${item.id}, ${item.quantidade + 1})">+</button>
                        </div>
                    </td>
                    <td class="py-3 px-3 text-right text-xs text-gray-600 whitespace-nowrap">
                        R$ ${item.preco.toFixed(2).replace('.', ',')}
                    </td>
                    <td class="py-3 px-3 text-right font-extrabold text-gray-900 whitespace-nowrap">
                        R$ ${item.subtotal.toFixed(2).replace('.', ',')}
                    </td>
                    <td class="py-3 px-2 text-center">
                        <button type="button" class="text-rose-500 hover:text-rose-700" title="Remover item" onclick="PDV.removerItem(${item.id})">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </td>
                `;
                tbody.appendChild(tr);
            });
        }

        if (totalSpan) totalSpan.innerText = 'R$ ' + total.toFixed(2).replace('.', ',');
        if (qtdItensSpan) qtdItensSpan.innerText = this.carrinho.reduce((acc, i) => acc + i.quantidade, 0);

        this.calcularTroco();
    },

    setupBusca() {
        const inputBusca = document.getElementById('pdv-busca-input');
        if (!inputBusca) return;

        inputBusca.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const termo = inputBusca.value.trim().toLowerCase();
                if (!termo) return;

                // Tenta correspondência exata por código de barras primeiro
                const matchCodigo = this.produtosCatalogo.find(p => p.codigo_barras.toLowerCase() === termo);
                if (matchCodigo) {
                    this.adicionarItem(matchCodigo.id, 1);
                    inputBusca.value = '';
                    return;
                }

                // Tenta busca por nome
                const matchNome = this.produtosCatalogo.find(p => p.nome.toLowerCase().includes(termo));
                if (matchNome) {
                    this.adicionarItem(matchNome.id, 1);
                    inputBusca.value = '';
                } else {
                    Toast.show('Nenhum produto encontrado com este código ou nome.', 'aviso');
                }
            }
        });
    },

    setupFormaPagamento() {
        const select = document.getElementById('forma_pagamento');
        const boxDinheiro = document.getElementById('box-pagamento-dinheiro');
        if (!select || !boxDinheiro) return;

        select.addEventListener('change', () => {
            if (select.value === 'Dinheiro') {
                boxDinheiro.classList.remove('hidden');
                document.getElementById('valor_recebido').focus();
            } else {
                boxDinheiro.classList.add('hidden');
            }
            this.calcularTroco();
        });

        const inputRecebido = document.getElementById('valor_recebido');
        if (inputRecebido) {
            inputRecebido.addEventListener('input', () => this.calcularTroco());
        }
    },

    calcularTroco() {
        const total = this.calcularTotal();
        const select = document.getElementById('forma_pagamento');
        const inputRecebido = document.getElementById('valor_recebido');
        const trocoSpan = document.getElementById('pdv-troco-valor');
        if (!inputRecebido || !trocoSpan || !select) return;

        if (select.value !== 'Dinheiro') {
            trocoSpan.innerText = 'R$ 0,00';
            return;
        }

        const recebido = parseFloat(inputRecebido.value) || 0;
        const troco = recebido - total;

        if (troco > 0) {
            trocoSpan.innerText = 'R$ ' + troco.toFixed(2).replace('.', ',');
            trocoSpan.className = 'text-xl font-black text-emerald-600';
        } else if (troco === 0) {
            trocoSpan.innerText = 'R$ 0,00';
            trocoSpan.className = 'text-xl font-bold text-gray-700';
        } else {
            trocoSpan.innerText = 'Faltam R$ ' + Math.abs(troco).toFixed(2).replace('.', ',');
            trocoSpan.className = 'text-sm font-bold text-red-600';
        }
    },

    abrirModalConfirmacao() {
        if (this.carrinho.length === 0) {
            Toast.show('O carrinho está vazio!', 'aviso');
            return;
        }

        const total = this.calcularTotal();
        const formaPagamento = document.getElementById('forma_pagamento').value;
        const inputRecebido = document.getElementById('valor_recebido');
        const recebido = parseFloat(inputRecebido ? inputRecebido.value : 0) || 0;

        if (formaPagamento === 'Dinheiro' && recebido < total) {
            Toast.show('O valor recebido em dinheiro é inferior ao total da compra!', 'erro');
            return;
        }

        const troco = formaPagamento === 'Dinheiro' ? (recebido - total) : 0;

        LeeModal.confirm(
            'Finalizar Venda',
            `
                <div class="space-y-2">
                    <p class="text-sm">Confirma a conclusão da venda no valor de <strong class="text-lee-primary text-base">R$ ${total.toFixed(2).replace('.', ',')}</strong>?</p>
                    <div class="bg-gray-50 p-3 rounded-lg text-xs space-y-1">
                        <div><strong>Itens:</strong> ${this.carrinho.length} produtos distintos</div>
                        <div><strong>Forma de Pagamento:</strong> ${formaPagamento}</div>
                        ${formaPagamento === 'Dinheiro' ? `<div><strong>Recebido:</strong> R$ ${recebido.toFixed(2).replace('.', ',')} | <strong>Troco:</strong> R$ ${troco.toFixed(2).replace('.', ',')}</div>` : ''}
                    </div>
                </div>
            `,
            () => this.enviarVenda(),
            null,
            'Sim, Concluir Venda',
            'Voltar ao Caixa'
        );
    },

    enviarVenda() {
        const total = this.calcularTotal();
        const formaPagamento = document.getElementById('forma_pagamento').value;
        const recebido = parseFloat(document.getElementById('valor_recebido').value) || total;
        const troco = Math.max(0, recebido - total);

        const dados = {
            itens: this.carrinho,
            total: total,
            forma_pagamento: formaPagamento,
            valor_recebido: recebido,
            troco: troco
        };

        // Envia para o backend via fetch
        fetch('processar_venda.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(dados)
        })
        .then(res => res.json())
        .then(data => {
            if (data.sucesso) {
                this.exibirReciboVenda(data.venda);
                this.carrinho = [];
                this.renderCarrinho();
                Toast.show('Venda finalizada com sucesso!', 'sucesso');
            } else {
                Toast.show(data.mensagem || 'Erro ao processar venda.', 'erro');
            }
        })
        .catch(err => {
            console.error(err);
            Toast.show('Erro de comunicação com o servidor.', 'erro');
        });
    },

    exibirReciboVenda(venda) {
        const overlay = document.getElementById('lee-global-modal');
        if (!overlay) return;

        let itensHtml = '';
        venda.itens.forEach(it => {
            itensHtml += `
                <div class="flex justify-between py-1 border-b border-dashed border-gray-200">
                    <div>
                        <div>${it.nome}</div>
                        <div class="text-[10px] text-gray-500">${it.quantidade}x R$ ${parseFloat(it.preco_unitario).toFixed(2).replace('.', ',')}</div>
                    </div>
                    <div class="font-bold">R$ ${parseFloat(it.subtotal).toFixed(2).replace('.', ',')}</div>
                </div>
            `;
        });

        overlay.innerHTML = `
            <div class="lee-modal-box max-w-md">
                <div class="p-6 font-mono text-xs text-gray-800" id="cupom-impressao">
                    <div class="text-center pb-3 border-b-2 border-gray-800 mb-3">
                        <div class="text-base font-black">LEE MARKET - SUPERMERCADO</div>
                        <div>SIGC - GESTÃO COMERCIAL INTEGRADA</div>
                        <div>CUPOM NÃO FISCAL DE VENDA</div>
                        <div class="text-[10px] text-gray-500 mt-1">CÓDIGO: ${venda.codigo_venda} | DATA: ${venda.data}</div>
                        <div class="text-[10px] text-gray-500">OPERADOR: ${venda.operador}</div>
                    </div>

                    <div class="space-y-1 mb-3">
                        ${itensHtml}
                    </div>

                    <div class="pt-3 border-t-2 border-gray-800 space-y-1 text-sm font-bold">
                        <div class="flex justify-between text-base font-black">
                            <span>TOTAL:</span>
                            <span>R$ ${parseFloat(venda.total).toFixed(2).replace('.', ',')}</span>
                        </div>
                        <div class="flex justify-between text-xs font-normal">
                            <span>FORMA DE PAGAMENTO:</span>
                            <span>${venda.forma_pagamento}</span>
                        </div>
                        ${venda.forma_pagamento === 'Dinheiro' ? `
                        <div class="flex justify-between text-xs font-normal">
                            <span>VALOR RECEBIDO:</span>
                            <span>R$ ${parseFloat(venda.valor_recebido).toFixed(2).replace('.', ',')}</span>
                        </div>
                        <div class="flex justify-between text-xs font-normal">
                            <span>TROCO:</span>
                            <span>R$ ${parseFloat(venda.troco).toFixed(2).replace('.', ',')}</span>
                        </div>
                        ` : ''}
                    </div>

                    <div class="mt-4 pt-3 border-t border-dashed border-gray-400 text-center text-[10px] text-gray-500">
                        Obrigado pela preferência! Volte sempre!
                    </div>
                </div>

                <div class="p-4 bg-gray-50 border-t flex justify-end gap-2 font-sans">
                    <button type="button" class="btn-lee-outline text-xs" onclick="window.print()">
                        🖨️ Imprimir Cupom
                    </button>
                    <button type="button" class="btn-lee-primary text-xs" onclick="LeeModal.close()">
                        Concluir e Novo Atendimento
                    </button>
                </div>
            </div>
        `;

        overlay.classList.add('active');
    }
};

window.PDV = PDV;
