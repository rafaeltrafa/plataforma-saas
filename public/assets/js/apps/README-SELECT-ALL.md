# Select All - Script Reutilizável

## Visão Geral

O `select-all.js` é um script JavaScript genérico e reutilizável que fornece funcionalidade de "Selecionar Todos" para checkboxes em qualquer área do projeto.

## Arquivos

- **`select-all.js`** - Script principal reutilizável
- **`admin-noticias.js`** - Exemplo de implementação para a área de notícias

## Como Usar

### 1. Estrutura HTML Necessária

#### Botão "Selecionar Todos"
```html
<a href="javascript:void(0)" id="selectAllCheckboxes">
    <i class="ti ti-circle-dot fs-5 me-2"></i>
    <span class="select-all-text">Selecionar Todos</span>
</a>
```

#### Checkboxes
```html
<input type="checkbox" class="form-check-input item-checkbox" 
       id="item1" value="1" name="selected_items[]">
<input type="checkbox" class="form-check-input item-checkbox" 
       id="item2" value="2" name="selected_items[]">
```

### 2. Incluir os Scripts

```html
<script src="<?= base_url('assets/js/apps/select-all.js') ?>"></script>
<script src="<?= base_url('assets/js/apps/sua-area.js') ?>"></script>
```

### 3. Inicialização Básica

```javascript
document.addEventListener('DOMContentLoaded', function() {
    const selectAllManager = window.initSelectAll({
        selectAllButtonId: 'selectAllCheckboxes',
        checkboxClass: 'item-checkbox'
    });
});
```

## Configurações Disponíveis

| Opção | Tipo | Padrão | Descrição |
|-------|------|--------|-----------|
| `selectAllButtonId` | string | 'selectAllCheckboxes' | ID do botão "Selecionar Todos" |
| `checkboxClass` | string | 'item-checkbox' | Classe CSS dos checkboxes |
| `selectAllText` | string | 'Selecionar Todos' | Texto quando nenhum está selecionado |
| `deselectAllText` | string | 'Desmarcar Todos' | Texto quando todos estão selecionados |
| `selectAllIcon` | string | 'ti-circle-dot' | Ícone quando nenhum está selecionado |
| `deselectAllIcon` | string | 'ti-circle-check' | Ícone quando todos estão selecionados |
| `onSelectionChange` | function | null | Callback executado quando seleção muda |

## Exemplos de Implementação

### Exemplo 1: Usuários
```javascript
const usuariosSelectAll = window.initSelectAll({
    selectAllButtonId: 'selectAllUsers',
    checkboxClass: 'user-checkbox',
    selectAllText: 'Selecionar Todos os Usuários',
    deselectAllText: 'Desmarcar Todos os Usuários',
    onSelectionChange: function(data) {
        console.log(`${data.selectedCount} usuários selecionados`);
        updateUserActions(data.selectedCount > 0);
    }
});
```

### Exemplo 2: Produtos
```javascript
const produtosSelectAll = window.initSelectAll({
    selectAllButtonId: 'selectAllProducts',
    checkboxClass: 'product-checkbox',
    onSelectionChange: function(data) {
        // Habilitar/desabilitar botões de ação em massa
        const massActionBtns = document.querySelectorAll('.mass-action-btn');
        massActionBtns.forEach(btn => {
            btn.disabled = data.selectedCount === 0;
        });
        
        // Mostrar contador
        const counter = document.getElementById('selection-counter');
        if (counter) {
            counter.textContent = `${data.selectedCount} produto(s) selecionado(s)`;
            counter.style.display = data.selectedCount > 0 ? 'block' : 'none';
        }
    }
});
```

### Exemplo 3: Com Callback Avançado
```javascript
const selectAllManager = window.initSelectAll({
    selectAllButtonId: 'selectAllItems',
    checkboxClass: 'item-checkbox',
    onSelectionChange: function(data) {
        // data contém:
        // - selectedCount: número de itens selecionados
        // - selectedValues: array com os valores selecionados
        // - totalCount: total de checkboxes
        // - allSelected: boolean se todos estão selecionados
        
        console.log('Dados da seleção:', data);
        
        // Exemplo: enviar para analytics
        if (data.selectedCount > 0) {
            analytics.track('items_selected', {
                count: data.selectedCount,
                total: data.totalCount
            });
        }
    }
});
```

## Métodos Públicos

### `selectAll()`
Seleciona todos os checkboxes programaticamente.
```javascript
selectAllManager.selectAll();
```

### `deselectAll()`
Desmarca todos os checkboxes programaticamente.
```javascript
selectAllManager.deselectAll();
```

### `getSelectedCount()`
Retorna o número de checkboxes selecionados.
```javascript
const count = selectAllManager.getSelectedCount();
```

### `getSelectedValues()`
Retorna um array com os valores dos checkboxes selecionados.
```javascript
const values = selectAllManager.getSelectedValues();
```

### `refresh()`
Recarrega os checkboxes (útil para conteúdo dinâmico/AJAX).
```javascript
selectAllManager.refresh();
```

## Integração com AJAX/Conteúdo Dinâmico

Quando o conteúdo da página é atualizado via AJAX, chame o método `refresh()`:

```javascript
// Após carregar conteúdo via AJAX
fetch('/api/load-more-items')
    .then(response => response.json())
    .then(data => {
        // Atualizar HTML
        document.getElementById('items-container').innerHTML = data.html;
        
        // Refresh do select all
        selectAllManager.refresh();
    });
```

## Estrutura de Pastas Recomendada

```
public/assets/js/apps/
├── select-all.js           # Script principal reutilizável
├── admin-noticias.js       # Implementação para notícias
├── admin-usuarios.js       # Implementação para usuários
├── admin-produtos.js       # Implementação para produtos
└── README-SELECT-ALL.md    # Esta documentação
```

## Boas Práticas

1. **Sempre use classes CSS específicas** para os checkboxes (ex: `user-checkbox`, `product-checkbox`)
2. **Implemente callbacks** para ações específicas da área
3. **Use IDs únicos** para botões de diferentes áreas
4. **Chame `refresh()`** após atualizações dinâmicas
5. **Teste a funcionalidade** após implementar em nova área

## Troubleshooting

### Problema: Botão não funciona
- Verifique se o ID do botão está correto
- Confirme se o script `select-all.js` foi carregado
- Verifique se há erros no console

### Problema: Checkboxes não são selecionados
- Confirme se a classe CSS dos checkboxes está correta
- Verifique se os checkboxes existem no DOM quando o script executa

### Problema: Callback não executa
- Verifique se a função callback está definida corretamente
- Confirme se não há erros JavaScript na função

## Suporte

Para dúvidas ou problemas, consulte:
1. Esta documentação
2. Código de exemplo em `admin-noticias.js`
3. Console do navegador para erros JavaScript