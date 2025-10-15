/**
 * Select All Functionality - Reutilizável
 * 
 * Este script fornece funcionalidade genérica de "Selecionar Todos" para checkboxes
 * Pode ser usado em qualquer área do projeto (notícias, usuários, etc.)
 * 
 * @author CBA System
 * @version 1.0
 */

class SelectAllManager {
    constructor(config) {
        this.config = {
            selectAllButtonId: config.selectAllButtonId || 'selectAllCheckboxes',
            checkboxClass: config.checkboxClass || 'item-checkbox',
            selectAllText: config.selectAllText || 'Selecionar Todos',
            deselectAllText: config.deselectAllText || 'Desmarcar Todos',
            selectAllIcon: config.selectAllIcon || 'ti-circle-dot',
            deselectAllIcon: config.deselectAllIcon || 'ti-circle-check',
            onSelectionChange: config.onSelectionChange || null // Callback opcional
        };
        
        this.init();
    }
    
    init() {
        this.selectAllButton = document.getElementById(this.config.selectAllButtonId);
        this.checkboxes = document.querySelectorAll(`.${this.config.checkboxClass}`);
        
        if (!this.selectAllButton || this.checkboxes.length === 0) {
            console.warn('SelectAllManager: Botão ou checkboxes não encontrados');
            return;
        }
        
        this.bindEvents();
        this.updateSelectAllButton();
    }
    
    bindEvents() {
        // Event listener para o botão "Selecionar Todos"
        this.selectAllButton.addEventListener('click', (e) => {
            e.preventDefault();
            this.toggleAllCheckboxes();
        });
        
        // Event listeners para checkboxes individuais
        this.checkboxes.forEach(checkbox => {
            checkbox.addEventListener('change', () => {
                this.updateSelectAllButton();
                this.triggerSelectionChangeCallback();
            });
        });
    }
    
    toggleAllCheckboxes() {
        const allChecked = this.areAllCheckboxesChecked();
        
        this.checkboxes.forEach(checkbox => {
            checkbox.checked = !allChecked;
        });
        
        this.updateSelectAllButton();
        this.triggerSelectionChangeCallback();
    }
    
    areAllCheckboxesChecked() {
        return Array.from(this.checkboxes).every(checkbox => checkbox.checked);
    }
    
    getSelectedCount() {
        return Array.from(this.checkboxes).filter(checkbox => checkbox.checked).length;
    }
    
    getSelectedValues() {
        return Array.from(this.checkboxes)
            .filter(checkbox => checkbox.checked)
            .map(checkbox => checkbox.value);
    }
    
    updateSelectAllButton() {
        const allChecked = this.areAllCheckboxesChecked();
        const icon = this.selectAllButton.querySelector('i');
        const text = this.selectAllButton.querySelector('.select-all-text');
        
        if (allChecked) {
            // Todos selecionados - mostrar "Desmarcar Todos"
            if (icon) {
                icon.className = `ti ${this.config.deselectAllIcon}`;
            }
            if (text) {
                text.textContent = this.config.deselectAllText;
            }
        } else {
            // Nem todos selecionados - mostrar "Selecionar Todos"
            if (icon) {
                icon.className = `ti ${this.config.selectAllIcon}`;
            }
            if (text) {
                text.textContent = this.config.selectAllText;
            }
        }
    }
    
    triggerSelectionChangeCallback() {
        if (typeof this.config.onSelectionChange === 'function') {
            const selectedCount = this.getSelectedCount();
            const selectedValues = this.getSelectedValues();
            const totalCount = this.checkboxes.length;
            
            this.config.onSelectionChange({
                selectedCount,
                selectedValues,
                totalCount,
                allSelected: this.areAllCheckboxesChecked()
            });
        }
    }
    
    // Métodos públicos para controle externo
    selectAll() {
        this.checkboxes.forEach(checkbox => {
            checkbox.checked = true;
        });
        this.updateSelectAllButton();
        this.triggerSelectionChangeCallback();
    }
    
    deselectAll() {
        this.checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        this.updateSelectAllButton();
        this.triggerSelectionChangeCallback();
    }
    
    refresh() {
        // Recarrega os checkboxes (útil para conteúdo dinâmico)
        this.checkboxes = document.querySelectorAll(`.${this.config.checkboxClass}`);
        this.bindEvents();
        this.updateSelectAllButton();
    }
}

// Função helper para inicialização rápida
window.initSelectAll = function(config) {
    return new SelectAllManager(config);
};

// Export para uso em módulos (se necessário)
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SelectAllManager;
}