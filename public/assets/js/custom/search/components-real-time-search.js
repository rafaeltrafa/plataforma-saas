/**
 * Real-Time Search Component
 * Sistema genérico de busca em tempo real reutilizável
 * 
 * @author Sistema CBA
 * @version 1.0
 */

class RealTimeSearch {
    constructor(options = {}) {
        // Configurações padrão
        this.config = {
            inputSelector: null,           // Seletor do campo de input (obrigatório)
            searchUrl: null,              // URL do endpoint de busca (obrigatório)
            resultsContainer: null,       // Container para exibir resultados (obrigatório)
            debounceTime: 300,           // Tempo de debounce em ms
            minChars: 2,                 // Mínimo de caracteres para buscar
            maxResults: 10,              // Máximo de resultados
            searchFields: ['titulo'],     // Campos para buscar no backend
            showImages: true,            // Mostrar imagens nos resultados
            showStatus: true,            // Mostrar status nos resultados
            onSelect: null,              // Callback quando um item é selecionado
            onClear: null,               // Callback quando a busca é limpa
            customTemplate: null,        // Template customizado para resultados
            loadingText: 'Buscando...',
            noResultsText: 'Nenhum resultado encontrado',
            errorText: 'Erro na busca. Tente novamente.',
            ...options
        };

        // Validações obrigatórias
        if (!this.config.inputSelector || !this.config.searchUrl || !this.config.resultsContainer) {
            throw new Error('RealTimeSearch: inputSelector, searchUrl e resultsContainer são obrigatórios');
        }

        // Elementos DOM
        this.input = document.querySelector(this.config.inputSelector);
        this.resultsContainer = document.querySelector(this.config.resultsContainer);
        
        if (!this.input || !this.resultsContainer) {
            throw new Error('RealTimeSearch: Elementos DOM não encontrados');
        }

        // Estado interno
        this.debounceTimer = null;
        this.currentRequest = null;
        this.isVisible = false;

        // Inicializar
        this.init();
    }

    /**
     * Inicializa o componente
     */
    init() {
        this.setupStyles();
        this.bindEvents();
        console.log('RealTimeSearch inicializado:', this.config.inputSelector);
    }

    /**
     * Configura estilos básicos para o container de resultados
     */
    setupStyles() {
        this.resultsContainer.style.cssText = `
            position: absolute;
            z-index: 1050;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0.375rem;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
            max-height: 300px;
            overflow-y: auto;
            display: none;
            width: 100%;
            margin-top: 2px;
        `;
    }

    /**
     * Vincula eventos
     */
    bindEvents() {
        // Input events
        this.input.addEventListener('input', (e) => this.handleInput(e));
        this.input.addEventListener('focus', (e) => this.handleFocus(e));
        this.input.addEventListener('blur', (e) => this.handleBlur(e));
        this.input.addEventListener('keydown', (e) => this.handleKeydown(e));

        // Document click para fechar resultados
        document.addEventListener('click', (e) => {
            if (!this.input.contains(e.target) && !this.resultsContainer.contains(e.target)) {
                this.hideResults();
            }
        });
    }

    /**
     * Manipula input do usuário
     */
    handleInput(e) {
        const query = e.target.value.trim();
        
        // Limpar timer anterior
        if (this.debounceTimer) {
            clearTimeout(this.debounceTimer);
        }

        // Cancelar requisição anterior
        if (this.currentRequest) {
            this.currentRequest.abort();
        }

        if (query.length < this.config.minChars) {
            this.hideResults();
            if (this.config.onClear) {
                this.config.onClear();
            }
            return;
        }

        // Debounce
        this.debounceTimer = setTimeout(() => {
            this.performSearch(query);
        }, this.config.debounceTime);
    }

    /**
     * Manipula foco no input
     */
    handleFocus(e) {
        const query = e.target.value.trim();
        if (query.length >= this.config.minChars) {
            this.showResults();
        }
    }

    /**
     * Manipula blur do input (com delay para permitir cliques)
     */
    handleBlur(e) {
        setTimeout(() => {
            if (!this.resultsContainer.matches(':hover')) {
                this.hideResults();
            }
        }, 150);
    }

    /**
     * Manipula teclas especiais
     */
    handleKeydown(e) {
        if (!this.isVisible) return;

        const items = this.resultsContainer.querySelectorAll('.search-result-item');
        const activeItem = this.resultsContainer.querySelector('.search-result-item.active');
        
        switch (e.key) {
            case 'ArrowDown':
                e.preventDefault();
                this.navigateResults(items, activeItem, 'down');
                break;
            case 'ArrowUp':
                e.preventDefault();
                this.navigateResults(items, activeItem, 'up');
                break;
            case 'Enter':
                e.preventDefault();
                if (activeItem) {
                    this.selectItem(activeItem);
                }
                break;
            case 'Escape':
                this.hideResults();
                this.input.blur();
                break;
        }
    }

    /**
     * Navega pelos resultados com teclado
     */
    navigateResults(items, activeItem, direction) {
        if (items.length === 0) return;

        // Remover classe active atual
        if (activeItem) {
            activeItem.classList.remove('active');
        }

        let newIndex = 0;
        if (activeItem) {
            const currentIndex = Array.from(items).indexOf(activeItem);
            newIndex = direction === 'down' 
                ? (currentIndex + 1) % items.length 
                : (currentIndex - 1 + items.length) % items.length;
        }

        items[newIndex].classList.add('active');
        items[newIndex].scrollIntoView({ block: 'nearest' });
    }

    /**
     * Executa a busca
     */
    async performSearch(query) {
        this.showLoading();

        const formData = new FormData();
        formData.append('search', query);
        formData.append('limit', this.config.maxResults);
        formData.append('fields', JSON.stringify(this.config.searchFields));

        try {
            const controller = new AbortController();
            this.currentRequest = controller;

            const response = await fetch(this.config.searchUrl, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                signal: controller.signal
            });

            const data = await response.json();

            if (data.success) {
                this.displayResults(data.data, query);
            } else {
                this.showError(data.message || this.config.errorText);
            }

        } catch (error) {
            if (error.name !== 'AbortError') {
                console.error('Erro na busca:', error);
                this.showError(this.config.errorText);
            }
        } finally {
            this.currentRequest = null;
        }
    }

    /**
     * Exibe loading
     */
    showLoading() {
        this.resultsContainer.innerHTML = `
            <div class="search-loading p-3 text-center">
                <div class="spinner-border spinner-border-sm me-2" role="status"></div>
                ${this.config.loadingText}
            </div>
        `;
        this.showResults();
    }

    /**
     * Exibe erro
     */
    showError(message) {
        this.resultsContainer.innerHTML = `
            <div class="search-error p-3 text-center text-danger">
                <i class="ti ti-alert-circle me-2"></i>
                ${message}
            </div>
        `;
        this.showResults();
    }

    /**
     * Exibe resultados da busca
     */
    displayResults(results, query) {
        if (results.length === 0) {
            this.resultsContainer.innerHTML = `
                <div class="search-no-results p-3 text-center text-muted">
                    <i class="ti ti-search-off me-2"></i>
                    ${this.config.noResultsText}
                </div>
            `;
            this.showResults();
            return;
        }

        const html = results.map(item => this.renderResultItem(item, query)).join('');
        this.resultsContainer.innerHTML = html;

        // Adicionar eventos de clique
        this.resultsContainer.querySelectorAll('.search-result-item').forEach(item => {
            item.addEventListener('click', () => this.selectItem(item));
            item.addEventListener('mouseenter', () => {
                // Remover active de outros itens
                this.resultsContainer.querySelectorAll('.search-result-item.active').forEach(el => {
                    el.classList.remove('active');
                });
                item.classList.add('active');
            });
        });

        this.showResults();
    }

    /**
     * Renderiza um item de resultado
     */
    renderResultItem(item, query) {
        if (this.config.customTemplate) {
            return this.config.customTemplate(item, query);
        }

        const highlightedTitle = this.highlightText(item.titulo, query);
        const imageHtml = this.config.showImages && item.image_url 
            ? `<img src="${item.image_url}" class="rounded me-3" style="width: 40px; height: 40px; object-fit: cover;" alt="Imagem">` 
            : '';
        
        const statusHtml = this.config.showStatus 
            ? `<span class="badge bg-secondary-subtle text-secondary ms-2">${item.status_text}</span>` 
            : '';

        return `
            <div class="search-result-item p-3 border-bottom cursor-pointer" data-id="${item.id}" data-item='${JSON.stringify(item)}'>
                <div class="d-flex align-items-center">
                    ${imageHtml}
                    <div class="flex-grow-1">
                        <div class="fw-semibold">${highlightedTitle}</div>
                        ${statusHtml}
                    </div>
                </div>
            </div>
        `;
    }

    /**
     * Destaca o texto da busca
     */
    highlightText(text, query) {
        if (!query) return text;
        const regex = new RegExp(`(${query})`, 'gi');
        return text.replace(regex, '<mark>$1</mark>');
    }

    /**
     * Seleciona um item
     */
    selectItem(itemElement) {
        const itemData = JSON.parse(itemElement.getAttribute('data-item'));
        
        // Preencher o input com o título
        this.input.value = itemData.titulo;
        
        // Esconder resultados
        this.hideResults();
        
        // Callback personalizado
        if (this.config.onSelect) {
            this.config.onSelect(itemData, itemElement);
        }
    }

    /**
     * Mostra container de resultados
     */
    showResults() {
        this.resultsContainer.style.display = 'block';
        this.isVisible = true;
    }

    /**
     * Esconde container de resultados
     */
    hideResults() {
        this.resultsContainer.style.display = 'none';
        this.isVisible = false;
        
        // Remover classes active
        this.resultsContainer.querySelectorAll('.search-result-item.active').forEach(item => {
            item.classList.remove('active');
        });
    }

    /**
     * Limpa a busca
     */
    clear() {
        this.input.value = '';
        this.hideResults();
        if (this.config.onClear) {
            this.config.onClear();
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
        // Remover eventos seria ideal, mas como usamos addEventListener, 
        // o garbage collector cuidará quando o elemento for removido
    }
}

// Exportar para uso global
window.RealTimeSearch = RealTimeSearch;