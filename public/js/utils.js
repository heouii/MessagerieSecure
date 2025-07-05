// Fonctions utilitaires

// Mise à jour des compteurs
async function updateCounters() {
    try {
        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const csrfToken = csrfMeta ? csrfMeta.content : null;

        if (!csrfToken) {
            console.warn("Le meta csrf-token est introuvable, les compteurs ne seront pas mis à jour.");
            return;
        }

        const folders = ['inbox', 'unverified', 'spam'];
        
        for (const folder of folders) {
            if (folder === 'spam') {
                // Pour le spam, endpoint différent
                const spamResponse = await fetch(`/emails/folder/spam`, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                const spamData = await spamResponse.json();
                const spamEmails = spamData.emails || [];
                const spamCount = spamEmails.filter(e => !e.read).length;
                document.getElementById('spamCount').textContent = spamCount;
            } else {
                const response = await fetch(`/mailgun-emails/${folder}`, {
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    }
                });
                const data = await response.json();
                const emails = data.emails || [];
                
                if (folder === 'inbox') {
                    const unreadCount = emails.filter(e => !e.read).length;
                    document.getElementById('inboxCount').textContent = unreadCount;
                } else if (folder === 'unverified') {
                    const unverifiedCount = emails.filter(e => !e.read).length;
                    document.getElementById('unverifiedCount').textContent = unverifiedCount;
                }
            }
        }
    } catch (error) {
        console.error('Erreur mise à jour compteurs:', error);
    }
}

// Formatage des dates
function formatDate(dateString) {
    const date = new Date(dateString);
    const now = new Date();
    const diffTime = Math.abs(now - date);
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    
    if (diffDays === 1) return 'Aujourd\'hui';
    if (diffDays === 2) return 'Hier';
    if (diffDays <= 7) return `Il y a ${diffDays} jours`;
    
    return date.toLocaleDateString('fr-FR');
}

// Système de notifications
function showNotification(message, type = 'info') {
    // S'assurer que message est une string
    let displayMessage = message;
    if (typeof message === 'object') {
        displayMessage = JSON.stringify(message);
    }
    if (!displayMessage || displayMessage === '[object Object]') {
        displayMessage = 'Une erreur est survenue';
    }
    
    const notification = document.createElement('div');
    const colors = {
        success: 'bg-green-500',
        error: 'bg-red-500',
        info: 'bg-blue-500'
    };
    
    const icons = {
        success: 'check',
        error: 'times',
        info: 'info'
    };
    
    notification.className = `${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;
    notification.innerHTML = `
        <div class="flex items-center">
            <i class="fas fa-${icons[type]} mr-2"></i>
            ${displayMessage}
        </div>
    `;
    
    const notificationsContainer = document.getElementById('notifications');
    notificationsContainer.appendChild(notification);
    
    // Animation d'entrée
    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);
    
    // Animation de sortie et suppression
    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 300);
    }, 5000);
}

// Debounce pour optimiser les appels
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Validation d'email
function isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return emailRegex.test(email);
}

// Nettoyage des handlers d'événements
function cleanup() {
    // Nettoyer les timeouts
    if (autocompleteTimeout) {
        clearTimeout(autocompleteTimeout);
    }
    
    // Masquer les suggestions
    hideAutocompleteSuggestions();
    hideEmailHistory();
    
    // Reset des variables globales
    currentInputField = null;
    currentSuggestions = [];
    selectedSuggestionIndex = -1;
}

// Gestion des erreurs globales
window.addEventListener('error', function(e) {
    console.error('Erreur JavaScript:', e.error);
    showNotification('Une erreur inattendue s\'est produite', 'error');
});

// Gestion des erreurs de requêtes
window.addEventListener('unhandledrejection', function(e) {
    console.error('Promise rejetée:', e.reason);
    showNotification('Erreur de connexion au serveur', 'error');
});

// Fonction pour logger les actions utilisateur (debugging)
function logUserAction(action, data = {}) {
    if (window.console && console.log) {
        console.log(`[Action utilisateur] ${action}:`, data);
    }
}

// Export des fonctions utilitaires pour les autres modules
window.MessagingUtils = {
    updateCounters,
    formatDate,
    showNotification,
    debounce,
    isValidEmail,
    cleanup,
    logUserAction
};
