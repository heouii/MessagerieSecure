// Gestion des réponses aux emails

// Fonction pour gérer le clic sur répondre
async function handleReplyClick() {
    const readModal = document.getElementById('readModal');
    const emailId = readModal.dataset.currentEmailId;
    
    if (!emailId) {
        showNotification('Erreur: Email non identifié', 'error');
        return;
    }
    
    try {
        const response = await fetch(`/mailgun-emails/${emailId}/reply`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Fermer le modal de lecture
            readModal.classList.add('hidden');
            
            // Ouvrir le modal de composition avec les données de réponse
            openReplyComposer(data.reply_data);
        } else {
            showNotification('Erreur lors de la préparation de la réponse', 'error');
        }
        
    } catch (error) {
        console.error('Erreur réponse:', error);
        showNotification('Erreur de connexion', 'error');
    }
}

// Fonction pour ouvrir le compositeur en mode réponse
function openReplyComposer(replyData) {
    isReplying = true;
    originalEmailData = replyData.original_email;
    
    // Ouvrir le modal
    document.getElementById('composeModal').classList.remove('hidden');
    
    // Pré-remplir les champs
    document.getElementById('toField').value = replyData.to;
    document.getElementById('subjectField').value = replyData.subject;
    
    // Ajouter le message original quoté
    const originalMessage = `


--- Message original ---
De: ${originalEmailData.from_name || originalEmailData.from} <${originalEmailData.from}>
Date: ${originalEmailData.date}
Objet: ${originalEmailData.subject}

${originalEmailData.content}`;
    
    document.getElementById('messageField').value = originalMessage;
    
    // Positionner le curseur au début
    const messageField = document.getElementById('messageField');
    messageField.focus();
    messageField.setSelectionRange(0, 0);
}

// Fonction pour charger un brouillon (pour édition)
async function loadDraft(draftId) {
    try {
        const response = await fetch(`/emails/draft/${draftId}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            const draft = data.draft;
            
            // Ouvrir le modal de composition
            document.getElementById('composeModal').classList.remove('hidden');
            
            // Remplir les champs
            document.getElementById('toField').value = draft.to || '';
            document.getElementById('ccField').value = draft.cc || '';
            document.getElementById('subjectField').value = draft.subject || '';
            document.getElementById('messageField').value = draft.content || '';
            
            // Focus sur le premier champ vide
            if (!draft.to) {
                document.getElementById('toField').focus();
            } else if (!draft.subject) {
                document.getElementById('subjectField').focus();
            } else {
                document.getElementById('messageField').focus();
            }
            
        } else {
            showNotification('Erreur lors du chargement du brouillon', 'error');
        }
        
    } catch (error) {
        console.error('Erreur chargement brouillon:', error);
        showNotification('Erreur de connexion', 'error');
    }
}

// Fonction pour supprimer un brouillon
async function deleteDraft(draftId) {
    if (!confirm('Voulez-vous vraiment supprimer ce brouillon ?')) {
        return;
    }
    
    try {
        const response = await fetch(`/emails/draft/${draftId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('Brouillon supprimé', 'success');
            loadEmails(); // Recharger la liste
        } else {
            showNotification('Erreur lors de la suppression du brouillon', 'error');
        }
        
    } catch (error) {
        console.error('Erreur suppression brouillon:', error);
        showNotification('Erreur de connexion', 'error');
    }
}