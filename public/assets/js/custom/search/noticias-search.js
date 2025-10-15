/**
 * Script de busca em tempo real para a página de notícias
 * Inicializa o sistema de filtro da tabela de notícias
 */
document.addEventListener('DOMContentLoaded', function() {
    // Inicializar busca em tempo real para filtrar a tabela
    const tableSearch = new RealTimeSearch({
        inputSelector: '#titulo',
        searchUrl: window.searchUrl,
        tableBodySelector: '#noticias-table tbody',
        debounceTime: 300,
        minChars: 0, // Permitir busca vazia para mostrar todos os resultados
        onSearch: function(searchTerm) {
            console.log('Buscando por:', searchTerm);
        },
        onClear: function() {
            console.log('Busca limpa - mostrando todos os resultados');
        }
    });
    
    console.log('Sistema de filtro em tempo real da tabela inicializado');
});