/**
 * Inicialização do Froala Editor
 * 
 * Este arquivo contém a configuração e inicialização do editor Froala
 * para uso em formulários do sistema.
 */

document.addEventListener('DOMContentLoaded', function() {
  console.log('Froala init script carregado');
  console.log('jQuery disponível:', typeof jQuery !== 'undefined');
  console.log('FroalaEditor disponível:', typeof FroalaEditor !== 'undefined');
  
  // Função para detectar o tema atual do sistema
  function getCurrentTheme() {
    return document.documentElement.getAttribute('data-bs-theme') || 'light';
  }
  
  // Função para aplicar tema no Froala Editor
  function applyFroalaTheme(theme) {
    console.log('=== APLICANDO TEMA FROALA ===');
    console.log('Tema solicitado:', theme);
    
    var froalaContainer = document.querySelector('.fr-wrapper');
    var froalaElement = document.getElementById('froala-editor');
    var froalaBox = document.querySelector('.fr-box');
    
    console.log('Elementos encontrados:');
    console.log('- froalaContainer (.fr-wrapper):', froalaContainer);
    console.log('- froalaElement (#froala-editor):', froalaElement);
    console.log('- froalaBox (.fr-box):', froalaBox);
    
    // Aplica tema no container principal
    if (froalaContainer) {
      console.log('Aplicando tema no container .fr-wrapper');
      if (theme === 'dark') {
        froalaContainer.classList.add('dark-theme');
        froalaContainer.classList.remove('light-theme');
        console.log('Classes após aplicar dark:', froalaContainer.className);
      } else {
        froalaContainer.classList.add('light-theme');
        froalaContainer.classList.remove('dark-theme');
        console.log('Classes após aplicar light:', froalaContainer.className);
      }
    } else {
      console.warn('Container .fr-wrapper não encontrado!');
    }
    
    // Aplica tema no elemento do editor
    if (froalaElement) {
      console.log('Aplicando tema no elemento #froala-editor');
      if (theme === 'dark') {
        froalaElement.classList.add('dark-theme');
        froalaElement.classList.remove('light-theme');
      } else {
        froalaElement.classList.add('light-theme');
        froalaElement.classList.remove('dark-theme');
      }
    } else {
      console.warn('Elemento #froala-editor não encontrado!');
    }
    
    // Aplica tema no box do Froala
    if (froalaBox) {
      console.log('Aplicando tema no elemento .fr-box');
      if (theme === 'dark') {
        froalaBox.classList.add('dark-theme');
        froalaBox.classList.remove('light-theme');
      } else {
        froalaBox.classList.add('light-theme');
        froalaBox.classList.remove('dark-theme');
      }
    } else {
      console.warn('Elemento .fr-box não encontrado!');
    }
  }
  
  // Função para aplicar tema dark no Code View
  function applyCodeViewTheme(theme) {
    setTimeout(function() {
      var codeElements = document.querySelectorAll('.fr-code-view, .fr-code-view *, [contenteditable="false"], textarea[data-froala]');
      codeElements.forEach(function(element) {
        if (theme === 'dark') {
          element.style.setProperty('background', '#1a2537', 'important');
          element.style.setProperty('background-color', '#1a2537', 'important');
          element.style.setProperty('color', '#e0e0e0', 'important');
          element.style.setProperty('border-color', '#313e54', 'important');
        } else {
          element.style.removeProperty('background');
          element.style.removeProperty('background-color');
          element.style.removeProperty('color');
          element.style.removeProperty('border-color');
        }
      });
    }, 100);
  }
  
  // Inicializa o editor Froala em todos os elementos com id 'froala-editor'
  var editorElement = document.getElementById('froala-editor');
  console.log('Elemento froala-editor encontrado:', editorElement !== null);
  
  if (editorElement) {
    if (typeof FroalaEditor !== 'undefined') {
      console.log('Inicializando Froala Editor...');
      
      // Detecta o tema inicial
      var initialTheme = getCurrentTheme();
      console.log('Tema inicial detectado:', initialTheme);
      
      var editor = new FroalaEditor('#froala-editor', {
        // Configuração do tema baseado no tema atual do sistema
        theme: initialTheme === 'dark' ? 'dark' : 'light',
        toolbarButtons: [
          'bold', 'italic', 'underline', 'strikeThrough', 'subscript', 'superscript', '|', 
          'fontFamily', 'fontSize', 'color', 'paragraphFormat', 'align', 
          'formatOL', 'formatUL', 'outdent', 'indent', '|', 
          'insertLink', 'insertImage', 'insertVideo', 'insertFile', 'insertTable', '|', 
          'quote', 'insertHR', 'undo', 'redo', 'clearFormatting', 'selectAll', '|',
          'codeView', 'html'
        ],
        heightMin: 500,
        heightMax: 700,
        language: 'pt_br',
        // Habilita o plugin de visualização de código
        pluginsEnabled: ['codeView', 'link', 'image', 'video', 'file', 'table', 'lists', 'colors', 'paragraphFormat', 'align', 'quote', 'save'],
        // Configurações para o modo de código
        codeViewKeepActiveButtons: ['bold', 'italic'],
        htmlAllowedTags: ['.*'],
        htmlAllowedAttrs: ['.*'],
        // Eventos para aplicar tema no Code View baseado no tema atual
        events: {
          'initialized': function () {
            console.log('Froala Editor inicializado - aplicando tema inicial');
            var currentTheme = getCurrentTheme();
            applyFroalaTheme(currentTheme);
          },
          'codeView.update': function () {
            console.log('Code View ativado - aplicando tema atual');
            var currentTheme = getCurrentTheme();
            applyCodeViewTheme(currentTheme);
          },
          'commands.after': function (cmd) {
            if (cmd === 'codeView') {
              console.log('Comando Code View executado - aplicando tema atual');
              var currentTheme = getCurrentTheme();
              applyCodeViewTheme(currentTheme);
            }
          }
        }
      });
      
      // Observer para detectar mudanças de tema em tempo real
      var themeObserver = new MutationObserver(function(mutations) {
        console.log('=== MUTATION OBSERVER ATIVADO ===');
        console.log('Número de mutações:', mutations.length);
        
        mutations.forEach(function(mutation) {
          console.log('Tipo de mutação:', mutation.type);
          console.log('Atributo alterado:', mutation.attributeName);
          console.log('Valor antigo:', mutation.oldValue);
          console.log('Valor atual:', document.documentElement.getAttribute('data-bs-theme'));
          
          if (mutation.type === 'attributes' && mutation.attributeName === 'data-bs-theme') {
            var newTheme = getCurrentTheme();
            console.log('=== MUDANÇA DE TEMA DETECTADA ===');
            console.log('Novo tema:', newTheme);
            
            // Aplica o novo tema no Froala Editor
            applyFroalaTheme(newTheme);
            
            // Se estiver no Code View, aplica o tema também
            var codeViewActive = document.querySelector('.fr-code-view');
            if (codeViewActive) {
              console.log('Code View ativo - aplicando tema também');
              applyCodeViewTheme(newTheme);
            }
          }
        });
      });
      
      // Inicia o observer no documentElement para detectar mudanças no atributo data-bs-theme
      console.log('=== CONFIGURANDO MUTATION OBSERVER ===');
      console.log('Elemento observado:', document.documentElement);
      console.log('Tema inicial:', getCurrentTheme());
      
      themeObserver.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ['data-bs-theme']
      });
      
      console.log('MutationObserver configurado com sucesso!');
      
      console.log('Froala Editor inicializado com sucesso!');
        console.log('Observer de tema ativado');
        
        // Teste manual - expor funções globalmente para debug
        window.testFroalaTheme = function(theme) {
          console.log('=== TESTE MANUAL DE TEMA ===');
          console.log('Testando tema:', theme);
          applyFroalaTheme(theme);
        };
        
        window.getCurrentFroalaTheme = getCurrentTheme;
        
        console.log('Funções de teste expostas: window.testFroalaTheme() e window.getCurrentFroalaTheme()');
    } else {
      console.error('FroalaEditor não está disponível!');
    }
  } else {
    console.log('Elemento #froala-editor não encontrado na página');
  }
});