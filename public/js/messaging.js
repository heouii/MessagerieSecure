// Variables globales
const API_BASE = '';
let currentView = 'inbox';
let currentEmails = [];
let attachedFiles = []; 
let isReplying = false;
let originalEmailData = null;
let currentInputField = null;
let currentSuggestions = [];
let selectedSuggestionIndex = -1;
let autocompleteTimeout;

// Éléments DOM
const composeBtn = document.getElementById('composeBtn');
const composeModal = document.getElementById('composeModal');
const closeComposeBtn = document.getElementById('closeComposeBtn');
const composeForm = document.getElementById('composeForm');
const emailList = document.getElementById('emailList');
const refreshBtn = document.getElementById('refreshBtn');
const readModal = document.getElementById('readModal');
const closeReadBtn = document.getElementById('closeReadBtn');
const attachmentInput = document.getElementById('attachmentInput');
const attachmentZone = document.getElementById('attachmentZone');
const browseFiles = document.getElementById('browseFiles');
const attachmentList = document.getElementById('attachmentList');
const attachmentPlaceholder = document.getElementById('attachmentPlaceholder');

// Initialisation
document.addEventListener('DOMContentLoaded', function() {
    initializeEventListeners();
    loadEmails();
    setupAutocomplete();
    setInterval(updateCounters, 30000);
});

// Configuration des event listeners
function initializeEventListeners() {
    // Navigation sidebar
    document.querySelectorAll('.sidebar-item').forEach(item => {
        item.addEventListener('click', handleSidebarClick);
    });
    
    // Modal composer
    composeBtn.addEventListener('click', openComposeModal);
    closeComposeBtn.addEventListener('click', closeComposeModal);
    composeModal.addEventListener('click', handleModalBackdropClick);
    
    // Gestion des pièces jointes
    browseFiles.addEventListener('click', (e) => {
        e.preventDefault();
        attachmentInput.click();
    });
    
    attachmentInput.addEventListener('change', handleFileSelect);
    setupDragAndDrop();
    
    // Modal lecture
    closeReadBtn.addEventListener('click', closeReadModal);
    readModal.addEventListener('click', handleReadModalBackdropClick);
    
    // Envoi d'email
    composeForm.addEventListener('submit', handleEmailSend);
    
    // Recherche
    document.getElementById('searchInput').addEventListener('input', handleSearch);
    
    // Rafraîchir
    refreshBtn.addEventListener('click', handleRefresh);
    
    // Boutons modales
    setupModalButtons();
}

// Gestionnaires d'événements
function handleSidebarClick(e) {
    e.preventDefault();
    e.stopPropagation();
    
    document.querySelectorAll('.sidebar-item').forEach(i => i.classList.remove('active'));
    this.classList.add('active');
    currentView = this.dataset.view;
    
    const titles = {
        'inbox': 'Boîte de réception',
        'unverified': 'À vérifier',
        'spam': 'Spam', 
        'sent': 'Envoyés',
        'drafts': 'Brouillons',
        'trash': 'Corbeille'
    };
    
    document.getElementById('viewTitle').textContent = titles[currentView] || currentView;
    loadEmails();
    
    return false;
}

function openComposeModal(e) {
    e.preventDefault();
    e.stopPropagation();
    composeModal.classList.remove('hidden');
    setTimeout(() => {
        document.getElementById('toField').focus();
    }, 100);
    return false;
}

function closeComposeModal(e) {
    e.preventDefault();
    e.stopPropagation();
    composeModal.classList.add('hidden');
    composeForm.reset();
    clearAttachments();
    hideAutocompleteSuggestions();
    hideEmailHistory();
    currentInputField = null;
    return false;
}

function handleModalBackdropClick(e) {
    if (e.target === composeModal) {
        closeComposeModal(e);
    }
}

function closeReadModal(e) {
    e.preventDefault();
    e.stopPropagation();
    readModal.classList.add('hidden');
    return false;
}

function handleReadModalBackdropClick(e) {
    if (e.target === readModal) {
        closeReadModal(e);
    }
}

function handleSearch(e) {
    const query = e.target.value.trim();
    loadEmails(query);
}

function handleRefresh(e) {
    e.preventDefault();
    e.stopPropagation();
    loadEmails();
    return false;
}

// Configuration drag & drop
function setupDragAndDrop() {
    attachmentZone.addEventListener('dragover', (e) => {
        e.preventDefault();
        attachmentZone.style.borderColor = '#BAA8D3';
        attachmentZone.style.backgroundColor = '#f8f9fa';
    });
    
    attachmentZone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        attachmentZone.style.borderColor = '#d1d5db';
        attachmentZone.style.backgroundColor = 'transparent';
    });
    
    attachmentZone.addEventListener('drop', (e) => {
        e.preventDefault();
        attachmentZone.style.borderColor = '#d1d5db';
        attachmentZone.style.backgroundColor = 'transparent';
        
        const files = Array.from(e.dataTransfer.files);
        addFiles(files);
    });
}

// Configuration des boutons modaux
function setupModalButtons() {
    document.addEventListener('click', function(e) {
        if (e.target.id === 'replyBtn' || e.target.closest('#replyBtn')) {
            e.preventDefault();
            handleReplyClick();
        }
    });

    document.getElementById('verifyEmailBtn').addEventListener('click', handleVerifyEmail);
    document.getElementById('saveDraftBtn').addEventListener('click', handleSaveDraft);
    document.getElementById('deleteEmailBtn').addEventListener('click', handleDeleteEmail);
    document.getElementById('restoreEmailBtn').addEventListener('click', handleRestoreEmail);
}

// Chargement des emails
async function loadEmails(search = '') {
    emailList.innerHTML = `
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-spinner fa-spin text-2xl mb-4"></i>
            <p>Chargement des messages...</p>
        </div>
    `;
    
    try {
        const endpoint = `/mailgun-emails/${currentView}?search=${encodeURIComponent(search)}`;

        const response = await fetch(endpoint, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            }
        });
        
        const data = await response.json();
        currentEmails = data.emails || [];
        
        if (currentEmails.length === 0) {
            displayEmptyState();
        } else {
            displayEmails(currentEmails);
        }
        
        updateCounters();
        
    } catch (error) {
        displayErrorState();
    }
}


function displayEmptyState() {
    const emptyMessages = {
        'inbox': 'Aucun message dans la boîte de réception',
        'unverified': 'Aucun message à vérifier',
        'spam': 'Aucun spam détecté',
        'sent': 'Aucun message envoyé',
        'drafts': 'Aucun brouillon',
        'trash': 'Corbeille vide'
    };
    
    emailList.innerHTML = `
        <div class="p-8 text-center text-gray-500">
            <i class="fas fa-inbox text-4xl mb-4"></i>
            <p>${emptyMessages[currentView] || 'Aucun message'}</p>
        </div>
    `;
}

function displayErrorState() {
    emailList.innerHTML = `
        <div class="p-8 text-center text-red-500">
            <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
            <p>Erreur lors du chargement des messages</p>
        </div>
    `;
}

// Affichage des emails
function displayEmails(emails) {
    emailList.innerHTML = emails.map(email => {
        const isVerified = email.signature_verified !== false && currentView !== 'unverified';
        const securityClass = isVerified ? 'verified' : 'unverified';
        const securityBadge = isVerified 
            ? '<span class="security-badge verified"><i class="fas fa-shield-check mr-1"></i>Vérifié</span>'
            : '<span class="security-badge unverified"><i class="fas fa-exclamation-triangle mr-1"></i>Non vérifié</span>';
        
        return createEmailItemHTML(email, securityClass, securityBadge);
    }).join('');
    
    // Ajouter les event listeners pour les clics
    document.querySelectorAll('.email-item').forEach(item => {
        item.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const emailId = this.getAttribute('data-email-id');
            openEmail(emailId);
            
            return false;
        });
    });
}

function createEmailItemHTML(email, securityClass, securityBadge) {
    return `
        <div class="email-item ${!email.read ? 'unread' : ''} ${securityClass} p-4 cursor-pointer border-b border-gray-100 hover:bg-gray-50" 
            data-email-id="${email.id}" style="transition: background-color 0.2s;">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-4 flex-1">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center text-white font-medium" style="background: linear-gradient(to right, #BAA8D3, #9280A3);">
                        ${email.from_name ? email.from_name.charAt(0).toUpperCase() : email.from.charAt(0).toUpperCase()}
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center space-x-2">
                                <p class="font-medium text-gray-900">${email.from_name || email.from}</p>
                                ${securityBadge}
                            </div>
                            <p class="text-sm text-gray-500">${formatDate(email.date)}</p>
                        </div>
                        <p class="text-sm font-medium text-gray-800 truncate">${email.subject}</p>
                        <p class="text-sm text-gray-600 truncate">${email.preview}</p>
                        ${email.attachments && email.attachments.length > 0 ? 
                            `<p class="text-xs text-gray-500 mt-1">
                                <i class="fas fa-paperclip mr-1"></i>${Array.isArray(email.attachments) ? email.attachments.length : '1'} pièce(s) jointe(s)
                            </p>` : ''}

                        ${currentView === 'drafts' ? createDraftButtons(email.id) : ''}
                    </div>
                </div>
                <div class="flex items-center space-x-2">
                    ${!email.read ? '<div class="w-2 h-2 rounded-full" style="background-color: #BAA8D3;"></div>' : ''}
                    <i class="fas fa-chevron-right text-gray-400"></i>
                </div>
            </div>
        </div>
    `;
}

function createDraftButtons(emailId) {
    return `
        <div class="flex items-center space-x-3 mt-2">
            <button onclick="event.stopPropagation(); loadDraft('${emailId}');" 
                class="text-sm text-purple-600 hover:underline flex items-center">
                <i class="fas fa-edit mr-1"></i> Éditer
            </button>
            <button onclick="event.stopPropagation(); deleteDraft('${emailId}');" 
                class="text-sm text-red-600 hover:underline flex items-center">
                <i class="fas fa-trash mr-1"></i> Supprimer
            </button>
        </div>
    `;
}