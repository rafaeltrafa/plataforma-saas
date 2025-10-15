/**
 * Script de filtro AJAX para a página de usuários
 * Implementa filtros por nome e status sem recarregar a página
 */
document.addEventListener('DOMContentLoaded', function() {
    // Elementos da página
    const nomeInput = document.getElementById('nome');
    const statusSelect = document.getElementById('status');
    const cardsContainer = document.querySelector('.row.justify-content-center');
    const clearButton = document.getElementById('clear-filters');
    
    // Verificar se os elementos existem
    if (!nomeInput || !statusSelect || !cardsContainer) {
        console.error('Elementos necessários não encontrados na página');
        return;
    }
    
    // Função para aplicar filtros via AJAX
    function applyFilters() {
        const nome = nomeInput.value.trim();
        const status = statusSelect.value;
        
        // Construir URL com parâmetros
        const params = new URLSearchParams();
        if (nome) params.append('nome', nome);
        if (status) params.append('status', status);
        
        // Mostrar loading
        showLoading();
        
        // Fazer requisição AJAX
        const baseUrl = window.location.pathname;
        const fetchUrl = params.toString() ? `${baseUrl}?${params.toString()}` : baseUrl;
        
        fetch(fetchUrl, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Erro na requisição');
            }
            return response.text();
        })
        .then(html => {
            // Extrair apenas o conteúdo dos cards da resposta
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newCardsContainer = doc.querySelector('.row.justify-content-center');
            
            if (newCardsContainer && cardsContainer) {
                cardsContainer.innerHTML = newCardsContainer.innerHTML;
                
                // Reinicializar os event listeners dos botões de ação
                if (typeof BatchActions !== 'undefined') {
                    BatchActions.initUserTrashAction();
                    BatchActions.initUserActivateAction();
                }
            }
            
            // Atualizar URL sem recarregar
            const newUrl = params.toString() ? `${baseUrl}?${params.toString()}` : baseUrl;
            window.history.pushState({}, '', newUrl);
        })
        .catch(error => {
            console.error('Erro ao filtrar usuários:', error);
            showError('Erro ao carregar os dados. Tente novamente.');
        });
    }
    
    // Função para mostrar loading
    function showLoading() {
        if (cardsContainer) {
            cardsContainer.innerHTML = `
                <div class="col-12">
                    <div class="text-center py-5">
                        <div class="d-flex align-items-center justify-content-center">
                            <div class="spinner-border spinner-border-sm me-2" role="status">
                                <span class="visually-hidden">Carregando...</span>
                            </div>
                            Buscando usuários...
                        </div>
                    </div>
                </div>
            `;
        }
    }
    
    // Função para mostrar erro
    function showError(message) {
        if (cardsContainer) {
            cardsContainer.innerHTML = `
                <div class="col-12">
                    <div class="text-center py-5 text-danger">
                        <i class="ti ti-alert-circle me-2"></i>${message}
                    </div>
                </div>
            `;
        }
    }
    
    // Event listeners para filtros em tempo real
    let debounceTimer;
    
    function debounceFilter() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(applyFilters, 500);
    }
    
    // Event listeners para filtros em tempo real
    nomeInput.addEventListener('input', debounceFilter);
    statusSelect.addEventListener('change', applyFilters);
    
    // Botão de limpar filtros
    if (clearButton) {
        clearButton.addEventListener('click', function() {
            nomeInput.value = '';
            statusSelect.value = '';
            applyFilters();
        });
    }
    
    // Inicializar ações de lixeira e ativação na página
    if (typeof BatchActions !== 'undefined') {
        BatchActions.initUserTrashAction();
        BatchActions.initUserActivateAction();
    }
    
    console.log('Sistema de filtro AJAX de usuários inicializado');
});