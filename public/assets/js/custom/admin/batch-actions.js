/**
 * Batch Actions - Funções genéricas para ações em lote
 * 
 * Este arquivo contém funções reutilizáveis para ações em lote
 * que podem ser utilizadas em diferentes partes do sistema.
 * 
 * @author Sistema CBA
 * @version 1.0
 */

// Namespace para evitar conflitos
window.BatchActions = window.BatchActions || {};

/**
 * Configurações padrão
 */
BatchActions.config = {
    loadingOverlayId: 'batch-loading',
    animationDuration: 500,
    successTimer: 2000
};

/**
 * Mostra feedback de loading
 */
BatchActions.showLoadingFeedback = function() {
    // Criar overlay de loading se não existir
    let loadingOverlay = document.getElementById(BatchActions.config.loadingOverlayId);
    if (!loadingOverlay) {
        loadingOverlay = document.createElement('div');
        loadingOverlay.id = BatchActions.config.loadingOverlayId;
        loadingOverlay.innerHTML = `
            <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                        background: rgba(0,0,0,0.5); z-index: 9999; display: flex; 
                        align-items: center; justify-content: center;">
                <div style="background: white; padding: 20px; border-radius: 8px; text-align: center;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Carregando...</span>
                    </div>
                    <p class="mt-2 mb-0">Processando ação...</p>
                </div>
            </div>
        `;
        document.body.appendChild(loadingOverlay);
    }
    loadingOverlay.style.display = 'block';
};

/**
 * Esconde feedback de loading
 */
BatchActions.hideLoadingFeedback = function() {
    const loadingOverlay = document.getElementById(BatchActions.config.loadingOverlayId);
    if (loadingOverlay) {
        loadingOverlay.style.display = 'none';
    }
};

/**
 * Mostra feedback de sucesso
 * @param {string} message - Mensagem de sucesso
 * @param {string} title - Título da mensagem (opcional)
 */
BatchActions.showSuccessFeedback = function(message, title = 'Sucesso!') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'success',
            title: title,
            text: message,
            timer: BatchActions.config.successTimer,
            showConfirmButton: false
        });
    } else {
        alert(title + ': ' + message);
    }
};

/**
 * Mostra feedback de erro
 * @param {string} message - Mensagem de erro
 * @param {string} title - Título da mensagem (opcional)
 */
BatchActions.showErrorFeedback = function(message, title = 'Erro!') {
    if (typeof Swal !== 'undefined') {
        Swal.fire({
            icon: 'error',
            title: title,
            text: message
        });
    } else {
        alert(title + ': ' + message);
    }
};

/**
 * Atualiza o badge de status na linha da tabela
 * @param {HTMLElement} row - Elemento da linha da tabela
 * @param {string|number} status - Status a ser aplicado
 */
BatchActions.updateStatusBadge = function(row, status) {
    const statusCell = row.querySelector('.status-badge');
    if (statusCell) {
        let badgeClass = '';
        let statusText = '';
        let iconClass = '';
        
        switch (String(status)) {
            case '1':
                badgeClass = 'badge status-badge bg-success-subtle text-success';
                statusText = 'Publicado';
                iconClass = 'ti-check';
                break;
            case '2':
                badgeClass = 'badge status-badge bg-danger-subtle text-danger';
                statusText = 'Lixeira';
                iconClass = 'ti-trash';
                break;
            case '3':
                badgeClass = 'badge status-badge bg-warning-subtle text-warning';
                statusText = 'Rascunho';
                iconClass = 'ti-file';
                break;
            default:
                badgeClass = 'badge status-badge bg-secondary-subtle text-secondary';
                statusText = 'Indefinido';
                iconClass = 'ti-help';
                break;
        }
        
        statusCell.className = badgeClass;
        statusCell.innerHTML = `<i class="ti ${iconClass} me-1"></i>${statusText}`;
    }
};

/**
 * Remove elemento com animação suave
 * @param {HTMLElement} element - Elemento a ser removido
 * @param {number} duration - Duração da animação em ms (opcional)
 */
BatchActions.removeElementWithAnimation = function(element, duration = BatchActions.config.animationDuration) {
    if (!element) return;
    
    // Adicionar animação de fade out
    element.style.transition = `opacity ${duration}ms ease-out, transform ${duration}ms ease-out`;
    element.style.opacity = '0';
    element.style.transform = 'scale(0.8)';
    
    // Remover o elemento após a animação
    setTimeout(() => {
        element.remove();
    }, duration);
};

/**
 * Inicializa a funcionalidade de mover para lixeira individual
 * @param {Object} options - Opções de configuração
 * @param {string} options.selector - Seletor CSS para os botões de lixeira (padrão: '.trash-action')
 * @param {boolean} options.removeElement - Se deve remover o elemento visualmente (padrão: false)
 * @param {string} options.containerSelector - Seletor do container a ser removido (opcional)
 */
BatchActions.initTrashAction = function(options = {}) {
    const {
        selector = '.trash-action',
        removeElement = false,
        containerSelector = null
    } = options;
    
    const trashButtons = document.querySelectorAll(selector);
    
    trashButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            const itemId = this.getAttribute('data-id');
            if (itemId) {
                BatchActions.executeTrashAction(itemId, {
                    removeElement: removeElement,
                    containerSelector: containerSelector
                });
            }
        });
    });
};

/**
 * Inicializa a funcionalidade de mover para lixeira para usuários (com remoção visual)
 * @param {string} selector - Seletor CSS para os botões de lixeira (padrão: '.trash-action')
 */
BatchActions.initUserTrashAction = function(selector = '.trash-action') {
    BatchActions.initTrashAction({
        selector: selector,
        removeElement: true,
        containerSelector: '.col-12' // Container específico para cards de usuários (responsive)
    });
};

/**
 * Inicializa a funcionalidade de ativar usuários
 * @param {string} selector - Seletor CSS para os botões de ativar (padrão: '.activate-action')
 */
BatchActions.initUserActivateAction = function(selector = '.activate-action') {
    document.addEventListener('click', function(e) {
        if (e.target.matches(selector) || e.target.closest(selector)) {
            e.preventDefault();
            e.stopPropagation();
            
            const button = e.target.matches(selector) ? e.target : e.target.closest(selector);
            const itemId = button.getAttribute('data-id');
            
            if (!itemId) {
                BatchActions.showErrorFeedback('ID do item não encontrado.');
                return;
            }
            
            // Confirmar ação
            Swal.fire({
                title: 'Ativar usuário?',
                text: 'Tem certeza que deseja ativar este usuário?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#28a745',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Sim, ativar!',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    BatchActions.executeActivateAction(itemId);
                }
            });
        }
    });
};

/**
 * Executa a ação de mover para lixeira individual
 * @param {string} itemId - ID do item
 * @param {Object} options - Opções de configuração
 * @param {string} options.urlEndpoint - URL do endpoint (opcional, usa window.urlFormLote por padrão)
 * @param {boolean} options.removeElement - Se deve remover o elemento visualmente (padrão: false)
 * @param {string} options.containerSelector - Seletor do container a ser removido (opcional)
 */
BatchActions.executeTrashAction = function(itemId, options = {}) {
    const {
        urlEndpoint = null,
        removeElement = false,
        containerSelector = null
    } = options;
    
    const url = urlEndpoint || window.urlFormLote;
    
    if (!url) {
        BatchActions.showErrorFeedback('URL do endpoint não configurada. Verifique se window.urlFormLote está definida.');
        return;
    }
    
    BatchActions.showLoadingFeedback();
    
    const formData = new FormData();
    formData.append('ids[]', itemId);
    formData.append('status', '2'); // Status 2 = Lixeira
    
    // Capturar parâmetros dos campos hidden do formulário
    const dbsInput = document.querySelector('input[name="dbs"]');
    const folderInput = document.querySelector('input[name="folder"]');
    const idDestinyInput = document.querySelector('input[name="idDestiny"]');
    
    if (dbsInput && dbsInput.value) {
        formData.append('dbs', dbsInput.value);
    }
    if (folderInput && folderInput.value) {
        formData.append('folder', folderInput.value);
    }
    if (idDestinyInput && idDestinyInput.value) {
        formData.append('idDestiny', idDestinyInput.value);
    }
    
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        BatchActions.hideLoadingFeedback();
        
        if (data.success) {
            // Mostrar sucesso
            BatchActions.showSuccessFeedback(data.message, "Movido para Lixeira!");
            
            // Remover elemento apenas se solicitado
            if (removeElement) {
                const trashButton = document.querySelector(`[data-id="${itemId}"].trash-action`);
                if (trashButton) {
                    let elementToRemove = null;
                    
                    if (containerSelector) {
                        elementToRemove = trashButton.closest(containerSelector);
                    } else {
                        // Tentar encontrar diferentes tipos de containers
                        elementToRemove = trashButton.closest('.col-md-2') || 
                                        trashButton.closest('.card') || 
                                        trashButton.closest('.item-container');
                    }
                    
                    if (elementToRemove) {
                        BatchActions.removeElementWithAnimation(elementToRemove);
                    }
                }
            }
            
            // Atualizar o badge de status na linha (para views de tabela)
            const row = document.querySelector(`tr[data-id="${itemId}"]`);
            if (row) {
                BatchActions.updateStatusBadge(row, 2); // Status 2 = Lixeira
            }
        } else {
            BatchActions.showErrorFeedback(data.message);
        }
    })
    .catch(error => {
        BatchActions.hideLoadingFeedback();
        console.error('Erro na requisição:', error);
        BatchActions.showErrorFeedback('Erro ao mover para lixeira. Tente novamente.');
    });
};

/**
 * Executa a ação de ativar usuário individual
 * @param {string} itemId - ID do item
 * @param {Object} options - Opções de configuração
 * @param {string} options.urlEndpoint - URL do endpoint (opcional, usa window.urlFormLote por padrão)
 */
BatchActions.executeActivateAction = function(itemId, options = {}) {
    const {
        urlEndpoint = null
    } = options;
    
    const url = urlEndpoint || window.urlFormLote;
    
    if (!url) {
        BatchActions.showErrorFeedback('URL do endpoint não configurada. Verifique se window.urlFormLote está definida.');
        return;
    }
    
    BatchActions.showLoadingFeedback();
    
    const formData = new FormData();
    formData.append('ids[]', itemId);
    formData.append('status', '1'); // Status 1 = Ativo
    
    // Capturar parâmetros dos campos hidden do formulário
    const dbsInput = document.querySelector('input[name="dbs"]');
    const folderInput = document.querySelector('input[name="folder"]');
    const idDestinyInput = document.querySelector('input[name="idDestiny"]');
    
    if (dbsInput && dbsInput.value) {
        formData.append('dbs', dbsInput.value);
    }
    if (folderInput && folderInput.value) {
        formData.append('folder', folderInput.value);
    }
    if (idDestinyInput && idDestinyInput.value) {
        formData.append('idDestiny', idDestinyInput.value);
    }
    
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        BatchActions.hideLoadingFeedback();
        
        if (data.success) {
            // Mostrar sucesso
            BatchActions.showSuccessFeedback(data.message, "Usuário Ativado!");
            
            // Remover o card da tela
            const activateButton = document.querySelector(`[data-id="${itemId}"].activate-action`);
            if (activateButton) {
                const elementToRemove = activateButton.closest('.col-12');
                if (elementToRemove) {
                    BatchActions.removeElementWithAnimation(elementToRemove);
                }
            }
        } else {
            BatchActions.showErrorFeedback(data.message);
        }
    })
    .catch(error => {
        BatchActions.hideLoadingFeedback();
        console.error('Erro na requisição:', error);
        BatchActions.showErrorFeedback('Erro ao ativar usuário. Tente novamente.');
    });
};

/**
 * Atualiza a interface após sucesso de ação em lote
 * @param {Array} processedIds - Array de IDs processados
 * @param {string|number} action - Ação executada
 */
BatchActions.updateInterfaceAfterSuccess = function(processedIds, action) {
    processedIds.forEach(id => {
        const row = document.querySelector(`tr[data-id="${id}"]`);
        if (row) {
            if (action == 4) {
                // Deletar permanentemente - remover linha
                BatchActions.removeElementWithAnimation(row);
            } else {
                // Atualizar status - atualizar badge de status
                BatchActions.updateStatusBadge(row, action);
            }
        }
    });
};

/**
 * Executa exclusão individual definitiva (status 4)
 * @param {string} itemId - ID do item a ser excluído
 * @param {Object} options - Opções de configuração
 * @param {string} options.urlEndpoint - URL do endpoint (opcional, usa window.urlFormLote por padrão)
 * @param {string} options.confirmTitle - Título da confirmação (opcional)
 * @param {string} options.confirmText - Texto da confirmação (opcional)
 * @param {string} options.confirmButtonText - Texto do botão de confirmação (opcional)
 * @param {string} options.cancelButtonText - Texto do botão de cancelamento (opcional)
 * @param {boolean} options.showConfirmation - Se deve mostrar confirmação (padrão: true)
 * @param {Function} options.onSuccess - Callback executado em caso de sucesso (opcional)
 * @param {Function} options.onError - Callback executado em caso de erro (opcional)
 */
BatchActions.executeIndividualDelete = function(itemId, options = {}) {
    const {
        urlEndpoint = null,
        confirmTitle = "Tem certeza?",
        confirmText = "Este item será excluído DEFINITIVAMENTE e não poderá ser recuperado!",
        confirmButtonText = "Sim, excluir definitivamente!",
        cancelButtonText = "Não, cancelar!",
        showConfirmation = true,
        onSuccess = null,
        onError = null
    } = options;
    
    const executeDelete = () => {
        const url = urlEndpoint || window.urlFormLote;
        
        if (!url) {
            BatchActions.showErrorFeedback('URL do endpoint não configurada. Verifique se window.urlFormLote está definida.');
            return;
        }
        
        BatchActions.showLoadingFeedback();
        
        // Criar FormData
        const formData = new FormData();
        formData.append('ids[]', itemId);
        formData.append('status', '4'); // Status 4 = exclusão definitiva

        // Capturar e incluir campos hidden do formulário (dbs, folder, idDestiny)
        const hiddenInputs = document.querySelectorAll('input[type="hidden"]');
        hiddenInputs.forEach(input => {
            if (input.name && input.value) {
                formData.append(input.name, input.value);
            }
        });
        
        // Fazer requisição AJAX
        fetch(url, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            BatchActions.hideLoadingFeedback();
            
            if (data.success) {
                // Mostrar sucesso
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        title: "Excluído!",
                        text: data.message,
                        icon: "success",
                        timer: 2000,
                        showConfirmButton: false
                    });
                } else {
                    alert('Excluído: ' + data.message);
                }
                
                // Remover a linha da tabela
                const row = document.querySelector(`tr[data-id="${itemId}"]`);
                if (row) {
                    BatchActions.removeElementWithAnimation(row);
                }
                
                // Executar callback de sucesso se fornecido
                if (onSuccess && typeof onSuccess === 'function') {
                    onSuccess(data, itemId);
                }
            } else {
                const errorMessage = data.message || 'Erro ao processar a exclusão. Tente novamente.';
                BatchActions.showErrorFeedback(errorMessage);
                
                // Executar callback de erro se fornecido
                if (onError && typeof onError === 'function') {
                    onError(data, itemId);
                }
            }
        })
        .catch(error => {
            BatchActions.hideLoadingFeedback();
            console.error('Erro na requisição:', error);
            const errorMessage = 'Erro ao processar a exclusão. Tente novamente.';
            BatchActions.showErrorFeedback(errorMessage);
            
            // Executar callback de erro se fornecido
            if (onError && typeof onError === 'function') {
                onError(error, itemId);
            }
        });
    };
    
    if (showConfirmation && typeof Swal !== 'undefined') {
        // Configurar SweetAlert personalizado para exclusão definitiva
        const swalWithBootstrapButtons = Swal.mixin({
            customClass: {
                confirmButton: "btn btn-success",
                cancelButton: "me-6 btn btn-danger",
            },
            buttonsStyling: false,
        });

        swalWithBootstrapButtons
            .fire({
                title: confirmTitle,
                text: confirmText,
                icon: "warning",
                showCancelButton: true,
                confirmButtonText: confirmButtonText,
                cancelButtonText: cancelButtonText,
                reverseButtons: true,
            })
            .then((result) => {
                if (result.isConfirmed) {
                    executeDelete();
                }
            });
    } else {
        // Executar sem confirmação ou usar confirm nativo se SweetAlert não estiver disponível
        if (!showConfirmation) {
            executeDelete();
        } else {
            if (confirm(confirmText)) {
                executeDelete();
            }
        }
    }
};

/**
 * Executa ação em lote genérica
 * @param {Array} selectedIds - Array de IDs selecionados
 * @param {string|number} action - Ação a ser executada
 * @param {string} urlEndpoint - URL do endpoint (opcional, usa window.urlFormLote por padrão)
 */
BatchActions.executeBatchAction = function(selectedIds, action, urlEndpoint = null) {
    const url = urlEndpoint || window.urlFormLote;
    
    if (!url) {
        BatchActions.showErrorFeedback('URL do endpoint não configurada. Verifique se window.urlFormLote está definida.');
        return;
    }
    
    if (!selectedIds || selectedIds.length === 0) {
        BatchActions.showErrorFeedback('Nenhum item selecionado.');
        return;
    }
    
    BatchActions.showLoadingFeedback();

    // Criar FormData e incluir campos hidden do formulário
    const formData = new FormData();
    
    // Adicionar IDs selecionados
    selectedIds.forEach(id => {
        formData.append('ids[]', id);
    });
    formData.append('status', action);
    
    // Capturar e incluir campos hidden do formulário (dbs, folder, idDestiny)
    const hiddenInputs = document.querySelectorAll('input[type="hidden"]');
    hiddenInputs.forEach(input => {
        if (input.name && input.value) {
            formData.append(input.name, input.value);
        }
    });
    
    fetch(url, {
        method: 'POST',
        body: formData,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        BatchActions.hideLoadingFeedback();
        
        if (data.success) {
            BatchActions.showSuccessFeedback(data.message);
            BatchActions.updateInterfaceAfterSuccess(selectedIds, action);
        } else {
            BatchActions.showErrorFeedback(data.message);
        }
    })
    .catch(error => {
        BatchActions.hideLoadingFeedback();
        console.error('Erro na requisição:', error);
        BatchActions.showErrorFeedback('Erro ao processar ação em lote. Tente novamente.');
    });
};

// Compatibilidade com funções globais (para não quebrar código existente)
window.showLoadingFeedback = BatchActions.showLoadingFeedback;
window.hideLoadingFeedback = BatchActions.hideLoadingFeedback;
window.showSuccessFeedback = BatchActions.showSuccessFeedback;
window.showErrorFeedback = BatchActions.showErrorFeedback;
window.updateStatusBadge = BatchActions.updateStatusBadge;
window.initTrashAction = BatchActions.initTrashAction;
window.executeTrashAction = BatchActions.executeTrashAction;