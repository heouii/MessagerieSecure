// Gestionnaires pour les actions sur les emails

// Envoi d'email
async function handleEmailSend(e) {
    e.preventDefault();
    
    const formData = new FormData();
    formData.append('to', document.getElementById('toField').value);
    formData.append('cc', document.getElementById('ccField').value);
    formData.append('subject', document.getElementById('subjectField').value);
    formData.append('message', document.getElementById('messageField').value);
    
    // Conversion correcte des booléens
    formData.append('html_format', document.getElementById('htmlFormat').checked ? 1 : 0);
    formData.append('read_receipt', document.getElementById('readReceipt').checked ? 1 : 0);
    
    // Ajouter les pièces jointes
    attachedFiles.forEach((file, index) => {
        formData.append(`attachments[${index}]`, file);
    });
    
    const sendBtn = document.getElementById('sendBtn');
    const originalText = sendBtn.innerHTML;
    sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Envoi...';
    sendBtn.disabled = true;
    
    try {
        const response = await fetch('/mailgun-send', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Email envoyé avec succès !', 'success');
            composeModal.classList.add('hidden');
            composeForm.reset();
            clearAttachments();
            loadEmails();
        } else {
            const errorMessage = data.error || data.message || 'Erreur lors de l\'envoi';
            console.error('Erreur envoi:', data);
            showNotification(errorMessage, 'error');
        }
        
    } catch (error) {
        console.error('Erreur réseau:', error);
        showNotification('Erreur de connexion', 'error');
    } finally {
        sendBtn.innerHTML = originalText;
        sendBtn.disabled = false;
    }
}

// Ouverture d'un email
function openEmail(emailId) {
    console.log('Ouverture email ID:', emailId);

    document.getElementById('readModal').dataset.currentEmailId = emailId;

    const email = currentEmails.find(e => e.id == emailId);
    if (!email) {
        console.error('Email non trouvé:', emailId);
        showNotification('Email non trouvé', 'error');
        return;
    }

    // Configuration des boutons selon le contexte
    setupEmailButtons(email);
    
    // Remplissage des données
    populateEmailData(email);
    
    // Affichage du modal
    readModal.classList.remove('hidden');

    // Marquer comme lu
    if (!email.read) {
        markAsRead(emailId);
    }
}

function setupEmailButtons(email) {
    const verifyBtn = document.getElementById('verifyEmailBtn');
    const restoreBtn = document.getElementById('restoreEmailBtn');
    const deleteBtn = document.getElementById('deleteEmailBtn');
    
    // Bouton vérifier
    if (currentView === 'unverified') {
        verifyBtn.classList.remove('hidden');
    } else {
        verifyBtn.classList.add('hidden');
    }

    // Bouton restaurer
    if (currentView === 'trash') {
        restoreBtn.classList.remove('hidden');
    } else {
        restoreBtn.classList.add('hidden');
    }

    // Bouton supprimer
    if (currentView === 'trash') {
        deleteBtn.innerHTML = '<i class="fas fa-trash-alt mr-1"></i> Supprimer définitivement';
        deleteBtn.dataset.action = 'permanent';
    } else {
        deleteBtn.innerHTML = '<i class="fas fa-trash mr-1"></i> Supprimer';
        deleteBtn.dataset.action = 'trash';
    }
}

function populateEmailData(email) {
    document.getElementById('readSubject').textContent = email.subject || 'Sans objet';
    document.getElementById('readFrom').textContent = email.from_name || email.from || 'Expéditeur inconnu';
    document.getElementById('readDate').textContent = formatDate(email.date || email.created_at);
    document.getElementById('readTo').textContent = email.to || 'Destinataire inconnu';
    document.getElementById('readContent').innerHTML = email.content || 'Contenu vide';

    displayEmailAttachments(email.attachments, email.id);

    // Badge de sécurité
    const isVerified = email.signature_verified !== false && currentView !== 'unverified';
    const emailSecurityBadge = document.getElementById('emailSecurityBadge');
    emailSecurityBadge.innerHTML = isVerified 
        ? '<span class="security-badge verified"><i class="fas fa-shield-check mr-1"></i>Signature vérifiée</span>'
        : '<span class="security-badge unverified"><i class="fas fa-exclamation-triangle mr-1"></i>Signature non vérifiée</span>';
}

// Marquer comme lu
async function markAsRead(emailId) {
    try {
        await fetch(`/mailgun-read/${emailId}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        loadEmails();
    } catch (error) {
        console.error('Erreur marquage lu:', error);
    }
}

// Vérification d'email
async function handleVerifyEmail(e) {
    e.preventDefault();
    const emailId = document.getElementById('readModal').dataset.currentEmailId;
    if (!emailId) {
        showNotification('Erreur : email introuvable', 'error');
        return;
    }

    try {
        const response = await fetch(`/emails/${emailId}/verify`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        const data = await response.json();

        if (data.success) {
            showNotification(data.message, 'success');
            document.getElementById('readModal').classList.add('hidden');
            loadEmails();
        } else if (data.need_confirmation) {
            if (confirm(`${data.message}\n\nVoulez-vous vraiment valider cet email ?`)) {
                await confirmVerifyEmail(emailId);
            }
        } else {
            showNotification(data.error || 'Erreur lors de la vérification.', 'error');
        }

    } catch (error) {
        console.error('Erreur vérification:', error);
        showNotification('Erreur réseau lors de la vérification.', 'error');
    }
}

async function confirmVerifyEmail(emailId) {
    try {
        const confirmResponse = await fetch(`/emails/${emailId}/verify`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ force: 1 })
        });
        const confirmData = await confirmResponse.json();
        if (confirmData.success) {
            showNotification(confirmData.message, 'success');
            document.getElementById('readModal').classList.add('hidden');
            loadEmails();
        } else {
            showNotification(confirmData.error || 'Erreur lors de la confirmation.', 'error');
        }
    } catch (error) {
        console.error('Erreur confirmation:', error);
        showNotification('Erreur réseau lors de la confirmation.', 'error');
    }
}

// Sauvegarde de brouillon
async function handleSaveDraft(e) {
    e.preventDefault();

    const to = document.getElementById('toField').value;
    const cc = document.getElementById('ccField').value;
    const subject = document.getElementById('subjectField').value;
    const content = document.getElementById('messageField').value;

    try {
        const response = await fetch('/emails/draft', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                to: to,
                cc: cc,
                subject: subject,
                content: content
            })
        });
        const data = await response.json();

        if (data.success) {
            showNotification('Brouillon enregistré', 'success');
            document.getElementById('composeModal').classList.add('hidden');
            loadEmails();
        } else {
            showNotification(data.error || 'Erreur lors de l\'enregistrement du brouillon', 'error');
        }
    } catch (error) {
        console.error('Erreur sauvegarde brouillon:', error);
        showNotification('Erreur réseau lors de la sauvegarde', 'error');
    }
}

// Suppression d'email
async function handleDeleteEmail(e) {
    e.preventDefault();

    const emailId = document.getElementById('readModal').dataset.currentEmailId;
    if (!emailId) {
        showNotification('Erreur : email introuvable', 'error');
        return;
    }

    const action = this.dataset.action;

    if (action === 'permanent') {
        if (!confirm('Voulez-vous vraiment supprimer définitivement cet email ? Cette action est irréversible.')) {
            return;
        }

        try {
            const response = await fetch(`/emails/${emailId}/permanent-delete`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();

            if (data.success) {
                showNotification(data.message, 'success');
                document.getElementById('readModal').classList.add('hidden');
                loadEmails();
            } else {
                showNotification(data.error || 'Erreur lors de la suppression', 'error');
            }
        } catch (error) {
            console.error('Erreur suppression:', error);
            showNotification('Erreur réseau lors de la suppression', 'error');
        }
    } else {
        if (!confirm('Voulez-vous déplacer cet email dans la corbeille ?')) {
            return;
        }

        try {
            const response = await fetch(`/emails/${emailId}/delete`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });
            const data = await response.json();

            if (data.success) {
                showNotification(data.message, 'success');
                document.getElementById('readModal').classList.add('hidden');
                loadEmails();
            } else {
                showNotification(data.error || 'Erreur lors de la suppression', 'error');
            }
        } catch (error) {
            console.error('Erreur suppression:', error);
            showNotification('Erreur réseau lors de la suppression', 'error');
        }
    }
}

// Restauration d'email
async function handleRestoreEmail(e) {
    e.preventDefault();
    const emailId = document.getElementById('readModal').dataset.currentEmailId;
    if (!emailId) {
        showNotification('Erreur : email introuvable', 'error');
        return;
    }

    try {
        const response = await fetch(`/emails/${emailId}/restore`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        const data = await response.json();

        if (data.success) {
            showNotification(data.message, 'success');
            document.getElementById('readModal').classList.add('hidden');
            loadEmails();
        } else {
            showNotification(data.error || 'Erreur lors de la restauration', 'error');
        }
    } catch (error) {
        console.error('Erreur restauration:', error);
        showNotification('Erreur réseau lors de la restauration', 'error');
    }
}