/**
 * Admin Notícias JavaScript
 * Funcionalidades específicas para o módulo de notícias do admin
 * 
 * Dependências:
 * - batch-actions.js (funções genéricas de ações em lote)
 * - select-all.js (funcionalidades de seleção)
 */

document.addEventListener('DOMContentLoaded', function() {
    console.log('Admin Notícias JS carregado');
    
    // Verificar se as dependências estão carregadas
    if (typeof window.BatchActions === 'undefined') {
        console.error('BatchActions não encontrado. Certifique-se de que batch-actions.js está carregado.');
        return;
    }
    
    // Inicializar SelectAllManager para notícias
    const selectAllManager = window.initSelectAll({
        selectAllButtonId: 'selectAllCheckboxes',
        checkboxClass: 'noticia-checkbox',
        onSelectionChange: function(selectedCount, totalCount) {
            console.log(`Selecionados: ${selectedCount} de ${totalCount}`);
        }
    });
    
    // Inicializar funcionalidades
    initBatchActions();
    initIndividualDelete();
    
    // Usar a função genérica para inicializar ações de lixeira
    BatchActions.initTrashAction();
});

/**
 * Inicializa as ações em lote
 */
function initBatchActions() {
    console.log('Inicializando ações em lote...');
    
    // Buscar ações do dropdown pelo texto
    const dropdownItems = document.querySelectorAll('.dropdown-item[href="javascript:void(0)"]');
    console.log('Encontrados', dropdownItems.length, 'itens do dropdown');
    
    dropdownItems.forEach((item, index) => {
        const text = item.textContent.trim();
        console.log(`Item ${index}: "${text}"`);
        
        if (text.includes('Publicar')) {
            console.log('Adicionando listener para Publicar');
            item.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Clique em Publicar detectado');
                handleBatchAction(1, 'Publicar');
            });
        } else if (text.includes('Rascunho')) {
            console.log('Adicionando listener para Rascunho');
            item.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Clique em Rascunho detectado');
                handleBatchAction(3, 'Rascunho');
            });
        } else if (text.includes('Lixeira')) {
            console.log('Adicionando listener para Lixeira');
            item.addEventListener('click', function(e) {
                e.preventDefault();
                console.log('Clique em Lixeira detectado');
                handleBatchAction(2, 'Lixeira');
            });
        }
    });
}

/**
 * Manipula as ações em lote
 * @param {number} status - Status da ação (1=Publicado, 2=Lixeira, 3=Rascunho)
 * @param {string} actionName - Nome da ação para exibição
 */
function handleBatchAction(status, actionName) {
    console.log(`handleBatchAction chamada: status=${status}, actionName=${actionName}`);
    
    // Verificar se há checkboxes selecionados
    const selectedCheckboxes = document.querySelectorAll('.noticia-checkbox:checked');
    console.log('Checkboxes selecionados:', selectedCheckboxes.length);
    
    if (selectedCheckboxes.length === 0) {
        console.log('Nenhum checkbox selecionado, mostrando alerta');
        alert('Por favor, selecione pelo menos uma notícia para realizar esta ação.');
        return;
    }
    
    // Executar ação via AJAX diretamente (sem confirmação)
    executeBatchActionAjax(selectedCheckboxes, status, actionName);
}

/**
 * Executa ação em lote via AJAX
 */
function executeBatchActionAjax(selectedCheckboxes, status, actionName) {
    // Usar a função genérica do BatchActions
    const ids = Array.from(selectedCheckboxes).map(checkbox => checkbox.value);
    
    // Executar ação em lote usando a função genérica
    BatchActions.executeBatchAction(ids, status);
    
    // Limpar seleções após sucesso (será chamado automaticamente pela função genérica)
    clearSelections();
}

/**
 * Limpa todas as seleções
 */
function clearSelections() {
    const checkboxes = document.querySelectorAll('.noticia-checkbox:checked');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    
    // Atualizar botão "Selecionar Todas" se existir
    const selectAllButton = document.getElementById('selectAllCheckboxes');
    if (selectAllButton) {
        const selectAllText = selectAllButton.querySelector('.select-all-text');
        if (selectAllText) {
            selectAllText.textContent = 'Selecionar Todos';
        }
    }
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
            
            const noticiaId = e.target.getAttribute('data-id');
            if (!noticiaId) {
                console.error('ID da notícia não encontrado');
                return;
            }
            
            // Usar a função genérica do BatchActions com configurações específicas para notícias
            BatchActions.executeIndividualDelete(noticiaId, {
                confirmTitle: "Tem certeza?",
                confirmText: "Esta notícia será excluída DEFINITIVAMENTE e não poderá ser recuperada!",
                confirmButtonText: "Sim, excluir definitivamente!",
                cancelButtonText: "Não, cancelar!"
            });
        }
    });
}