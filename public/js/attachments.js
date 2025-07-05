// Gestion des pièces jointes - VERSION CORRIGÉE

// Sélection de fichiers
function handleFileSelect(e) {
    const files = Array.from(e.target.files);
    addFiles(files);
}

// Ajout de fichiers
function addFiles(files) {
    files.forEach(file => {
        if (file.size > 25 * 1024 * 1024) { // 25MB max
            showNotification(`Le fichier ${file.name} est trop volumineux (max 25MB)`, 'error');
            return;
        }
        
        if (!attachedFiles.find(f => f.name === file.name && f.size === file.size)) {
            attachedFiles.push(file);
        }
    });
    
    displayAttachments();
}

// Affichage des pièces jointes dans le composer
function displayAttachments() {
    if (attachedFiles.length === 0) {
        attachmentPlaceholder.classList.remove('hidden');
        attachmentList.classList.add('hidden');
        return;
    }
    
    attachmentPlaceholder.classList.add('hidden');
    attachmentList.classList.remove('hidden');
    
    attachmentList.innerHTML = attachedFiles.map((file, index) => `
        <div class="flex items-center justify-between bg-gray-50 p-2 rounded">
            <div class="flex items-center space-x-2">
                <i class="fas fa-file text-gray-500"></i>
                <span class="text-sm text-gray-700">${file.name}</span>
                <span class="text-xs text-gray-500">(${formatFileSize(file.size)})</span>
            </div>
            <button type="button" onclick="removeAttachment(${index})" class="text-red-500 hover:text-red-700">
                <i class="fas fa-times"></i>
            </button>
        </div>
    `).join('');
}

// Suppression d'une pièce jointe
function removeAttachment(index) {
    attachedFiles.splice(index, 1);
    displayAttachments();
}

// Vider toutes les pièces jointes
function clearAttachments() {
    attachedFiles = [];
    attachmentInput.value = '';
    displayAttachments();
}

// Formatage de la taille de fichier
function formatFileSize(bytes) {
    if (bytes === 0) return '0 B';
    const k = 1024;
    const sizes = ['B', 'KB', 'MB', 'GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
}

// Affichage des pièces jointes dans la lecture d'email - VERSION CORRIGÉE
function displayEmailAttachments(attachments, emailId) {
    const emailAttachmentsDiv = document.getElementById('emailAttachments');
    const attachmentsListDiv = document.getElementById('attachmentsList');

    if (!attachments) {
        emailAttachmentsDiv.classList.add('hidden');
        return;
    }

    let attachmentData = [];
    if (typeof attachments === 'string') {
        try {
            attachmentData = JSON.parse(attachments);
        } catch (e) {
            console.error('Erreur parsing attachments:', e);
            emailAttachmentsDiv.classList.add('hidden');
            return;
        }
    } else if (Array.isArray(attachments)) {
        attachmentData = attachments;
    } else if (typeof attachments === 'object') {
        attachmentData = [attachments];
    }

    if (!Array.isArray(attachmentData) || attachmentData.length === 0) {
        emailAttachmentsDiv.classList.add('hidden');
        return;
    }

    emailAttachmentsDiv.classList.remove('hidden');

    attachmentsListDiv.innerHTML = attachmentData.map((attachment) => {
        const filename = attachment.filename || 'Fichier';
        const size = attachment.size || 0;
        
        // Construire l'URL de téléchargement
        let downloadUrl;
        
        if (attachment.path) {
            // Le chemin contient déjà "attachments/incoming/" ou "attachments/outgoing/"
            downloadUrl = `/storage/${attachment.path}`;
        } else if (attachment.stored_name) {
            // Fallback : utiliser le nom stocké
            downloadUrl = `/storage/attachments/${attachment.stored_name}`;
        } else {
            // Dernière option : utiliser le nom original encodé
            downloadUrl = `/storage/attachments/${encodeURIComponent(filename)}`;
        }

        console.log('🔗 URL générée pour téléchargement:', downloadUrl, attachment);

        return `
            <div class="flex items-center justify-between bg-gray-50 p-3 rounded">
                <div class="flex items-center space-x-3">
                    <i class="fas fa-file text-gray-500"></i>
                    <div>
                        <p class="text-sm font-medium text-gray-700">${filename}</p>
                        <p class="text-xs text-gray-500">${formatFileSize(size)}</p>
                    </div>
                </div>
                <a href="${downloadUrl}" class="text-purple-600 hover:text-purple-800 text-sm" target="_blank">
                    <i class="fas fa-download mr-1"></i>Télécharger
                </a>
            </div>
        `;
    }).join('');
}

// Fonction de debug pour tester les URLs
function debugAttachmentUrl(attachment) {
    console.log('🔍 Debug attachment:', {
        filename: attachment.filename,
        path: attachment.path,
        stored_name: attachment.stored_name,
        url_generated: attachment.path ? `/storage/${attachment.path}` : `/storage/attachments/${attachment.stored_name || attachment.filename}`
    });
}