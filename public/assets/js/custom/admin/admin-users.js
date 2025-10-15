/**
 * Admin Usuários JavaScript
 * Funcionalidades específicas para o módulo de usuários do admin
 * 
 * Dependências:
 * - batch-actions.js (funções genéricas de ações em lote)
 * - select-all.js (funcionalidades de seleção)
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin Usuários JS carregado');
    
    // Verificar se as dependências estão carregadas
    if (typeof window.BatchActions === 'undefined') {
        console.error('BatchActions não encontrado. Certifique-se de que batch-actions.js está carregado.');
        return;
    }
    
    // Inicializar SelectAllManager para usuários
    const selectAllManager = window.initSelectAll({
        selectAllButtonId: 'selectAllCheckboxes',
        checkboxClass: 'user-checkbox',
        onSelectionChange: function(selectedCount, totalCount) {
            console.log(`Selecionados: ${selectedCount} de ${totalCount}`);
        }
    });
    
    // Inicializar funcionalidades
    initBatchActions();
    initIndividualDelete();
    
    // Usar a função específica para usuários (com remoção visual)
    BatchActions.initUserTrashAction();
    BatchActions.initUserActivateAction();
});

/**
 * Inicializa as ações em lote
 */
function initBatchActions() {
    const batchForm = document.getElementById('batchForm');
    if (batchForm) {
        batchForm.addEventListener('submit', function(e) {
            e.preventDefault();
            executeBatchActionAjax();
        });
    }
}

/**
 * Executa ação em lote via AJAX usando a função genérica
 */
function executeBatchActionAjax() {
    const selectedCheckboxes = document.querySelectorAll('.user-checkbox:checked');
    const selectedIds = Array.from(selectedCheckboxes).map(cb => cb.value);
    const action = document.getElementById('batchAction').value;
    
    if (selectedIds.length === 0) {
        BatchActions.showErrorFeedback('Selecione pelo menos um usuário.');
        return;
    }
    
    if (!action) {
        BatchActions.showErrorFeedback('Selecione uma ação.');
        return;
    }
    
    // Usar a função genérica de batch actions
    BatchActions.executeBatchAction(selectedIds, action, window.urlFormLote);
}

/**
 * Inicializa a funcionalidade de exclusão individual
 */
function initIndividualDelete() {
    // Intercepta cliques nos botões de exclusão individual
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('sa-passparameter')) {
            e.preventDefault();
            e.stopPropagation();
            
            const userId = e.target.getAttribute('data-id');
            if (!userId) {
                console.error('ID do usuário não encontrado');
                return;
            }
            
            // Usar a função genérica do BatchActions com configurações específicas para usuários
            BatchActions.executeIndividualDelete(userId, {
                confirmTitle: "Tem certeza?",
                confirmText: "Este usuário será excluído DEFINITIVAMENTE e não poderá ser recuperado!",
                confirmButtonText: "Sim, excluir definitivamente!",
                cancelButtonText: "Não, cancelar!",
                onSuccess: function(data, itemId) {
                    // Callback específico para usuários: remover o card com animação
                    const userCard = document.querySelector(`[data-id="${itemId}"].trash-action`);
                    if (userCard) {
                        const cardContainer = userCard.closest('.col-md-2');
                        if (cardContainer) {
                            BatchActions.removeElementWithAnimation(cardContainer);
                        }
                    }
                }
            });
        }
    });
}