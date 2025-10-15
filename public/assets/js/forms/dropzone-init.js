/**
 * Dropzone Initialization - Configuração Flexível
 * Suporta diferentes configurações baseadas em atributos data-*
 */

// Desabilita o auto discover do Dropzone
Dropzone.autoDiscover = false;

// Inicialização do Dropzone quando o documento estiver pronto
document.addEventListener('DOMContentLoaded', function() {
    // Busca todos os elementos com classe dropzone
    const dropzoneElements = document.querySelectorAll('.dropzone');
    
    dropzoneElements.forEach(function(element, index) {
        // Lê configurações dos atributos data-*
        const maxFiles = parseInt(element.dataset.maxFiles) || 1; // Padrão: 1 arquivo
        const acceptedFiles = element.dataset.acceptedFiles || 'image/*'; // Padrão: apenas imagens
        const uploadUrl = element.dataset.uploadUrl || '#'; // URL de upload
        const allowMultiple = element.dataset.allowMultiple === 'true'; // Permite múltiplos arquivos
        
        // Configuração dinâmica baseada nos atributos
        const config = {
            url: uploadUrl,
            maxFiles: allowMultiple ? null : maxFiles, // null = ilimitado se allowMultiple = true
            acceptedFiles: acceptedFiles,
            addRemoveLinks: true,
            
            // Mensagens dinâmicas baseadas na configuração
            dictDefaultMessage: maxFiles === 1 ? 
                'Arraste uma imagem aqui ou clique para selecionar' : 
                `Arraste até ${maxFiles} arquivos aqui ou clique para selecionar`,
            dictRemoveFile: 'Remover arquivo',
            dictCancelUpload: 'Cancelar upload',
            dictUploadCanceled: 'Upload cancelado',
            dictMaxFilesExceeded: maxFiles === 1 ? 
                'Apenas um arquivo é permitido' : 
                `Máximo de ${maxFiles} arquivos permitidos`,
            
            init: function() {
                const dropzoneInstance = this;
                
                // Para configuração de arquivo único
                if (maxFiles === 1 && !allowMultiple) {
                    this.on("addedfile", function(file) {
                        // Remove arquivo anterior se existir
                        if (this.files.length > 1) {
                            this.removeFile(this.files[0]);
                        }
                    });
                    
                    this.on("maxfilesexceeded", function(file) {
                        this.removeFile(file);
                        alert('Apenas um arquivo é permitido. O arquivo anterior foi substituído.');
                    });
                }
                
                // Para configuração de múltiplos arquivos com limite
                else if (maxFiles > 1 && !allowMultiple) {
                    this.on("maxfilesexceeded", function(file) {
                        this.removeFile(file);
                        alert(`Máximo de ${maxFiles} arquivos permitidos.`);
                    });
                }
                
                // Eventos comuns
                this.on("success", function(file, response) {
                    console.log('Upload realizado com sucesso:', response);
                });
                
                this.on("error", function(file, errorMessage) {
                    console.error('Erro no upload:', errorMessage);
                });
            }
        };
        
        // Cria a instância do Dropzone
        const dropzoneInstance = new Dropzone(element, config);
        
        // Torna a instância disponível globalmente com ID único
        window[`dropzone_${index}`] = dropzoneInstance;
    });
});