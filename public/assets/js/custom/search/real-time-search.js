/**
 * Classe para busca em tempo real que filtra a tabela de listagem
 * Substitui o conteúdo da tabela com os resultados filtrados
 */
class RealTimeSearch {
    constructor(options = {}) {
        // Configurações padrão
        this.config = {
            inputSelector: options.inputSelector || '#search-input',
            searchUrl: options.searchUrl || '/search',
            tableBodySelector: options.tableBodySelector || 'tbody',
            debounceTime: options.debounceTime || 300,
            minChars: options.minChars || 0, // Permitir busca vazia para mostrar todos
            onSearch: options.onSearch || null,
            onClear: options.onClear || null
        };

        this.searchInput = null;
        this.tableBody = null;
        this.debounceTimer = null;
        this.currentRequest = null;
        this.originalContent = '';

        this.init();
    }

    /**
     * Inicializa o componente
     */
    init() {
        this.searchInput = document.querySelector(this.config.inputSelector);
        this.tableBody = document.querySelector(this.config.tableBodySelector);

        if (!this.searchInput) {
            console.error('RealTimeSearch: Input element not found:', this.config.inputSelector);
            return;
        }

        if (!this.tableBody) {
            console.error('RealTimeSearch: Table body not found:', this.config.tableBodySelector);
            return;
        }

        // Salvar conteúdo original da tabela
        this.originalContent = this.tableBody.innerHTML;

        this.bindEvents();
    }

    /**
     * Vincula eventos aos elementos
     */
    bindEvents() {
        // Evento de input com debounce
        this.searchInput.addEventListener('input', (e) => {
            this.handleInput(e.target.value);
        });

        // Limpar busca quando o campo for limpo
        this.searchInput.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.clearSearch();
            }
        });
    }

    /**
     * Manipula entrada de texto com debounce
     */
    handleInput(value) {
        clearTimeout(this.debounceTimer);
        
        this.debounceTimer = setTimeout(() => {
            this.performSearch(value.trim());
        }, this.config.debounceTime);
    }

    /**
     * Realiza busca AJAX
     */
    async performSearch(searchTerm) {
        // Cancelar requisição anterior se existir
        if (this.currentRequest) {
            this.currentRequest.abort();
        }

        this.currentRequest = new AbortController();
        
        try {
            this.showLoading();

            // Callback antes da busca
            if (this.config.onSearch) {
                this.config.onSearch(searchTerm);
            }

            const formData = new FormData();
            formData.append('search', searchTerm);

            const response = await fetch(this.config.searchUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: this.currentRequest.signal
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const data = await response.json();

            if (data.success) {
                this.updateTable(data.html, data);
            } else {
                this.showError(data.message || 'Erro na busca');
            }

        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Erro na busca:', error);
                this.showError('Erro na conexão. Tente novamente.');
            }
        } finally {
            this.currentRequest = null;
        }
    }

    /**
     * Exibe loading na tabela
     */
    showLoading() {
        this.tableBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4">
                    <div class="d-flex align-items-center justify-content-center">
                        <div class="spinner-border spinner-border-sm me-2" role="status">
                            <span class="visually-hidden">Carregando...</span>
                        </div>
                        Buscando notícias...
                    </div>
                </td>
            </tr>
        `;
    }

    /**
     * Exibe erro na tabela
     */
    showError(message) {
        this.tableBody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center py-4 text-danger">
                    <i class="ti ti-alert-circle me-2"></i>${message}
                </td>
            </tr>
        `;
    }

    /**
     * Atualiza conteúdo da tabela
     */
    updateTable(html, data = null) {
        this.tableBody.innerHTML = html;
        
        // Exibir informações de paginação se houver muitos resultados
        if (data && data.total_records > data.per_page) {
            this.showPaginationInfo(data);
        }
        
        // Reinicializar tooltips se existirem
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            const tooltipTriggerList = this.tableBody.querySelectorAll('[data-bs-toggle="tooltip"]');
            tooltipTriggerList.forEach(tooltipTriggerEl => {
                new bootstrap.Tooltip(tooltipTriggerEl);
            });
        }

        // Reinicializar eventos específicos da tabela se necessário
        this.reinitializeTableEvents();
    }

    /**
     * Exibe informações de paginação
     */
    showPaginationInfo(data) {
        // Procurar por um elemento onde exibir as informações de paginação
        let infoElement = document.querySelector('.search-pagination-info');
        
        if (!infoElement) {
            // Criar elemento se não existir
            infoElement = document.createElement('div');
            infoElement.className = 'search-pagination-info alert alert-info mt-2';
            
            // Inserir após a tabela
            const table = document.querySelector(this.config.tableBodySelector).closest('table');
            if (table && table.parentNode) {
                table.parentNode.insertBefore(infoElement, table.nextSibling);
            }
        }
        
        const message = data.search_term 
            ? `Mostrando ${data.total} de ${data.total_records} resultados para "${data.search_term}" (página ${data.current_page} de ${data.total_pages})`
            : `Mostrando ${data.total} de ${data.total_records} notícias (página ${data.current_page} de ${data.total_pages})`;
            
        infoElement.innerHTML = `
            <i class="ti ti-info-circle me-2"></i>
            ${message}
        `;
        
        infoElement.style.display = 'block';
    }

    /**
     * Reinicializa eventos específicos da tabela
     */
    reinitializeTableEvents() {
        // Reinicializar checkboxes
        const checkboxes = this.tableBody.querySelectorAll('.noticia-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                // Lógica para checkboxes se necessário
            });
        });

        // Reinicializar botões de ação
        const trashButtons = this.tableBody.querySelectorAll('.trash-action');
        trashButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                // Lógica para lixeira
                console.log('Mover para lixeira:', id);
            });
        });

        const deleteButtons = this.tableBody.querySelectorAll('.sa-passparameter');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function() {
                const id = this.getAttribute('data-id');
                // Lógica para exclusão
                console.log('Excluir:', id);
            });
        });
    }

    /**
     * Limpa a busca e restaura conteúdo original
     */
    clearSearch() {
        this.searchInput.value = '';
        this.tableBody.innerHTML = this.originalContent;
        
        // Ocultar informações de paginação
        this.hidePaginationInfo();
        
        if (this.config.onClear) {
            this.config.onClear();
        }

        // Reinicializar eventos
        this.reinitializeTableEvents();
    }

    /**
     * Oculta as informações de paginação
     */
    hidePaginationInfo() {
        const infoElement = document.querySelector('.search-pagination-info');
        if (infoElement) {
            infoElement.style.display = 'none';
        }
    }

    /**
     * Atualiza o conteúdo original (útil após inserções/edições)
     */
    updateOriginalContent() {
        if (!this.searchInput.value.trim()) {
            this.originalContent = this.tableBody.innerHTML;
        }
    }

    /**
     * Destrói o componente
     */
    destroy() {
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }
        if (this.currentRequest) {
            this.currentRequest.abort();
        }
    }
}