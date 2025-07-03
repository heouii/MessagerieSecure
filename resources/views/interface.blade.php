<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Messagerie Sécurisée</title>
    
    <!-- Bootstrap 5 CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .navbar {
            background-color: #BAA8D3;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
        }
        body {
            background-color: #f8f9fa;
            padding-top: 56px; /* Pour compenser la navbar fixe */
        }
        .sidebar {
            min-height: 100vh;
            background-color: #9280A3;
            color: white;
        }
        .sidebar a {
            color: #adb5bd;
            text-decoration: none;
            display: block;
            padding: 15px;
        }
        .sidebar a:hover {
            background-color: #BAA8D3;
            color: white;
        }
        footer {
            background-color: #9280A3;
            color: white;
            padding: 20px;
            position: relative;
            bottom: 0;
            width: 100%;
            text-align: center;
        }
        .sidebar-item:hover {
            background-color: rgba(186, 168, 211, 0.3);
        }
        .sidebar-item.active {
            background-color: rgba(186, 168, 211, 0.4);
            border-right: 3px solid #BAA8D3;
        }
        .sidebar-item.unverified:hover {
            background-color: rgba(249, 115, 22, 0.1);
        }
        .sidebar-item.unverified.active {
            background-color: rgba(249, 115, 22, 0.2);
            border-right: 3px solid #f97316;
        }
        .email-item:hover {
            background-color: #f8fafc;
        }
        .email-item.unread {
            background-color: #fefefe;
            font-weight: 600;
        }
        .email-item.unverified {
            border-left: 4px solid #f97316;
            background-color: #fef7f0;
        }
        .email-item.verified {
            border-left: 4px solid #10b981;
        }
        .compose-editor {
            min-height: 300px;
        }
        .security-badge {
            font-size: 10px;
            padding: 2px 6px;
            border-radius: 9999px;
            font-weight: 600;
            text-transform: uppercase;
        }
        .security-badge.verified {
            background-color: #d1fae5;
            color: #065f46;
        }
        .security-badge.unverified {
            background-color: #fed7aa;
            color: #9a3412;
        }
        .pulse-orange {
            animation: pulse-orange 2s infinite;
        }
        @keyframes pulse-orange {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.8; }
        }
    </style>
</head>
<body>
    <!-- Navbar top (only when authenticated) -->
    @auth
    <nav class="navbar navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ route('dashboard') }}">
                <i class="fas fa-envelope"></i> Missive
            </a>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn btn-outline-light">
                    <i class="fas fa-sign-out-alt"></i> Déconnexion
                </button>
            </form>
        </div>
    </nav>
    @else
    <!-- Navbar for guests -->
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="{{ url('/') }}">
            <h1 class="h3 mb-0">Missive</h1>
            </a>
            <div class="d-flex">
                <a href="{{ route('login') }}" class="btn btn-outline-light me-2">Se connecter</a>
                <a href="{{ route('register') }}" class="btn btn-light">S'inscrire</a>
            </div>
        </div>
    </nav>
    @endauth
    <div class="flex h-screen"> <!-- Supprimé le padding -->
        
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg border-r border-gray-200">
            <!-- Supprimé complètement le bloc titre MessagerieSecure -->
            
            <!-- Bouton Composer -->
            <div class="p-4">
                <button id="composeBtn" class="w-full text-white font-medium py-3 px-4 rounded-lg transition-colors" style="background-color: #BAA8D3;" onmouseover="this.style.backgroundColor='#9280A3'" onmouseout="this.style.backgroundColor='#BAA8D3'">
                    <i class="fas fa-pen mr-2"></i>Nouveau message
                </button>
            </div>
            
            <!-- Menu Navigation -->
            <nav class="flex-1">
                <div class="px-2">
                    <div class="sidebar-item active flex items-center px-4 py-3 text-gray-700 cursor-pointer" data-view="inbox">
                        <i class="fas fa-inbox mr-3" style="color: #BAA8D3;"></i>
                        <span>Boîte de réception</span>
                        <span id="inboxCount" class="ml-auto text-white text-xs px-2 py-1 rounded-full" style="background-color: #BAA8D3;">0</span>
                    </div>
                    
                    <!-- NOUVEAU : Dossier À vérifier -->
                    <div class="sidebar-item unverified flex items-center px-4 py-3 text-gray-700 cursor-pointer" data-view="unverified">
                        <i class="fas fa-exclamation-triangle mr-3 text-orange-500 pulse-orange"></i>
                        <span>À vérifier</span>
                        <span id="unverifiedCount" class="ml-auto bg-orange-500 text-white text-xs px-2 py-1 rounded-full">0</span>
                    </div>
                    
                    <div class="sidebar-item flex items-center px-4 py-3 text-gray-700 cursor-pointer" data-view="sent">
                        <i class="fas fa-paper-plane mr-3" style="color: #9280A3;"></i>
                        <span>Envoyés</span>
                    </div>
                    <div class="sidebar-item flex items-center px-4 py-3 text-gray-700 cursor-pointer" data-view="drafts">
                        <i class="fas fa-edit mr-3" style="color: #9280A3;"></i>
                        <span>Brouillons</span>
                    </div>
                    <div class="sidebar-item flex items-center px-4 py-3 text-gray-700 cursor-pointer" data-view="trash">
                        <i class="fas fa-trash mr-3" style="color: #9280A3;"></i>
                        <span>Corbeille</span>
                    </div>
                </div>
            </nav>
            
            <!-- User Info -->
            <div class="p-4 border-t border-gray-200">
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-medium" style="background-color: #BAA8D3;">
                        {{ substr(Auth::user()->prenom, 0, 1) }}{{ substr(Auth::user()->nom, 0, 1) }}
                    </div>
                    <div class="ml-3">
                        <p class="text-sm font-medium text-gray-700">{{ Auth::user()->prenom }} {{ Auth::user()->nom }}</p>
                        <p class="text-xs text-gray-500">{{ Auth::user()->email }}</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Zone principale -->
        <div class="flex-1 flex flex-col">
            
            <!-- Header -->
            <div class="bg-white border-b border-gray-200 px-6 py-4">
                <div class="flex items-center justify-between">
                    <div class="flex items-center space-x-4">
                        <h2 id="viewTitle" class="text-xl font-semibold text-gray-800">Boîte de réception</h2>
                        <button id="refreshBtn" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-sync-alt"></i>
                        </button>
                    </div>
                    <div class="flex items-center space-x-2">
                        <div class="relative">
                            <input type="text" id="searchInput" placeholder="Rechercher..." 
                                   class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:border-2" style="--tw-ring-color: #BAA8D3; border-color: #BAA8D3;" onfocus="this.style.borderColor='#9280A3'" onblur="this.style.borderColor='#BAA8D3'">
                            <i class="fas fa-search absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400"></i>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Liste des emails -->
            <div class="flex-1 overflow-y-auto">
                <div id="emailList" class="divide-y divide-gray-200">
                    <!-- Les emails seront chargés ici dynamiquement -->
                    <div class="p-8 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-4"></i>
                        <p>Chargement des messages...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Modal Composer -->
    <div id="composeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center h-full p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
                <div class="flex items-center justify-between p-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-800">Nouveau message</h3>
                    <button id="closeComposeBtn" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                
                <form id="composeForm" class="flex flex-col h-full">
                    <div class="p-4 space-y-4 border-b border-gray-200">
                        <div class="flex items-center space-x-2">
                            <label class="text-sm font-medium text-gray-700 w-16">À :</label>
                            <input type="email" id="toField" required
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded" style="--tw-ring-color: #BAA8D3;" onfocus="this.style.borderColor='#9280A3'" onblur="this.style.borderColor='#BAA8D3'"
                                   placeholder="destinataire@exemple.com">
                        </div>
                        <div class="flex items-center space-x-2">
                            <label class="text-sm font-medium text-gray-700 w-16">Cc :</label>
                            <input type="email" id="ccField"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded" style="--tw-ring-color: #BAA8D3;" onfocus="this.style.borderColor='#9280A3'" onblur="this.style.borderColor='#BAA8D3'"
                                   placeholder="Copie carbone (optionnel)">
                        </div>
                        <div class="flex items-center space-x-2">
                            <label class="text-sm font-medium text-gray-700 w-16">Objet :</label>
                            <input type="text" id="subjectField" required
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded" style="--tw-ring-color: #BAA8D3;" onfocus="this.style.borderColor='#9280A3'" onblur="this.style.borderColor='#BAA8D3'"
                                   placeholder="Objet du message">
                        </div>
                    </div>
                    
                    <div class="flex-1 p-4">
                        <textarea id="messageField" required
                                  class="w-full h-48 px-3 py-2 border border-gray-300 rounded resize-none mb-4" style="--tw-ring-color: #BAA8D3;" onfocus="this.style.borderColor='#9280A3'" onblur="this.style.borderColor='#BAA8D3'"
                                  placeholder="Écrivez votre message ici..."></textarea>
                        
                        <!-- Pièces jointes -->
                        <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center" id="attachmentZone" style="transition: border-color 0.3s;">
                            <input type="file" id="attachmentInput" multiple class="hidden" accept="*/*">
                            <div id="attachmentPlaceholder">
                                <i class="fas fa-paperclip text-gray-400 text-2xl mb-2"></i>
                                <p class="text-gray-500 mb-2">Glissez vos fichiers ici ou <button type="button" id="browseFiles" class="text-purple-600 underline">parcourez</button></p>
                                <p class="text-xs text-gray-400">Maximum 25MB par fichier</p>
                            </div>
                            <div id="attachmentList" class="hidden space-y-2"></div>
                        </div>
                    </div>
                    
                    <div class="p-4 border-t border-gray-200 flex items-center justify-between">
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center">
                                <input type="checkbox" id="htmlFormat" class="mr-2">
                                <span class="text-sm text-gray-600">Format HTML</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" id="readReceipt" class="mr-2">
                                <span class="text-sm text-gray-600">Accusé de réception</span>
                            </label>
                        </div>
                        <div class="flex space-x-2">
                            <button type="button" id="saveDraftBtn" 
                                    class="px-4 py-2 border border-gray-300 rounded text-gray-700 hover:bg-gray-50">
                                <i class="fas fa-save mr-1"></i>Brouillon
                            </button>
                            <button type="submit" id="sendBtn"
                                    class="px-6 py-2 text-white rounded transition-colors" style="background-color: #BAA8D3;" onmouseover="this.style.backgroundColor='#9280A3'" onmouseout="this.style.backgroundColor='#BAA8D3'">
                                <i class="fas fa-paper-plane mr-1"></i>Envoyer
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Modal Lecture -->
    <div id="readModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
        <div class="flex items-center justify-center h-full p-4">
            <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
                <div class="flex items-center justify-between p-4 border-b border-gray-200">
                    <div class="flex items-center space-x-3">
                        <h3 id="readSubject" class="text-lg font-semibold text-gray-800"></h3>
                        <div id="emailSecurityBadge"></div>
                    </div>
                    <div class="flex space-x-2">
                        <button id="replyBtn" style="color: #BAA8D3;" onmouseover="this.style.color='#9280A3'" onmouseout="this.style.color='#BAA8D3'">
                            <i class="fas fa-reply mr-1"></i>Répondre
                        </button>
                        <button id="deleteEmailBtn" class="text-red-600 hover:text-red-700">
                            <i class="fas fa-trash mr-1"></i>Supprimer
                        </button>
                        <button id="closeReadBtn" class="text-gray-500 hover:text-gray-700">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
                
                <div class="p-4 border-b border-gray-200 bg-gray-50">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium text-gray-800" id="readFrom"></p>
                            <p class="text-sm text-gray-600" id="readDate"></p>
                        </div>
                        <div class="text-right">
                            <p class="text-sm text-gray-600">À : <span id="readTo"></span></p>
                        </div>
                    </div>
                </div>
                
                <div class="p-6 flex-1 overflow-y-auto max-h-96">
                    <div id="readContent" class="prose max-w-none"></div>
                    
                    <!-- Pièces jointes reçues -->
                    <div id="emailAttachments" class="mt-4 hidden">
                        <h4 class="text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-paperclip mr-1"></i>Pièces jointes
                        </h4>
                        <div id="attachmentsList" class="space-y-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Notifications -->
    <div id="notifications" class="fixed top-4 right-4 space-y-2 z-50"></div>

    <script>
        // Configuration
        const API_BASE = '';
        let currentView = 'inbox';
        let currentEmails = [];
        let attachedFiles = []; // Nouveau : stockage des fichiers
        
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
        
        // Navigation sidebar
        document.querySelectorAll('.sidebar-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault(); // Empêche le rechargement
                e.stopPropagation();
                
                document.querySelectorAll('.sidebar-item').forEach(i => i.classList.remove('active'));
                item.classList.add('active');
                currentView = item.dataset.view;
                
                // Mettre à jour le titre
                const titles = {
                    'inbox': 'Boîte de réception',
                    'unverified': 'À vérifier',
                    'sent': 'Envoyés',
                    'drafts': 'Brouillons',
                    'trash': 'Corbeille'
                };
                
                document.getElementById('viewTitle').textContent = titles[currentView] || currentView;
                loadEmails();
                
                return false;
            });
        });
        
        // Modal composer
        composeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            composeModal.classList.remove('hidden');
            return false;
        });
        
        closeComposeBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            composeModal.classList.add('hidden');
            composeForm.reset();
            clearAttachments(); // Nouveau : vider les pièces jointes
            return false;
        });
        
        // Gestion des pièces jointes
        browseFiles.addEventListener('click', (e) => {
            e.preventDefault();
            attachmentInput.click();
        });
        
        attachmentInput.addEventListener('change', handleFileSelect);
        
        // Drag & Drop
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
        
        // Fermer modal en cliquant à côté
        composeModal.addEventListener('click', (e) => {
            if (e.target === composeModal) {
                composeModal.classList.add('hidden');
                composeForm.reset();
            }
        });
        
        // Modal lecture
        closeReadBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            readModal.classList.add('hidden');
            return false;
        });
        
        readModal.addEventListener('click', (e) => {
            if (e.target === readModal) {
                readModal.classList.add('hidden');
            }
        });
        
        // Envoi d'email
        composeForm.addEventListener('submit', async (e) => {
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
                        // Pas de Content-Type pour FormData (boundary automatique)
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
                    // Améliorer l'affichage des erreurs
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
        });
        
        // Charger les emails
        async function loadEmails() {
            emailList.innerHTML = `
                <div class="p-8 text-center text-gray-500">
                    <i class="fas fa-spinner fa-spin text-2xl mb-4"></i>
                    <p>Chargement des messages...</p>
                </div>
            `;
            
            try {
                const response = await fetch(`/mailgun-emails/${currentView}`, {
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                
                const data = await response.json();
                currentEmails = data.emails || [];
                
                if (currentEmails.length === 0) {
                    const emptyMessages = {
                        'inbox': 'Aucun message dans la boîte de réception',
                        'unverified': 'Aucun message à vérifier',
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
                } else {
                    displayEmails(currentEmails);
                }
                
                // Mettre à jour les compteurs
                updateCounters();
                
            } catch (error) {
                emailList.innerHTML = `
                    <div class="p-8 text-center text-red-500">
                        <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                        <p>Erreur lors du chargement des messages</p>
                    </div>
                `;
            }
        }
        
        // Mettre à jour les compteurs
        async function updateCounters() {
            try {
                // Charger tous les dossiers pour les compteurs
                const folders = ['inbox', 'unverified'];
                
                for (const folder of folders) {
                    const response = await fetch(`/mailgun-emails/${folder}`, {
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
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
            } catch (error) {
                console.error('Erreur mise à jour compteurs:', error);
            }
        }
        
        // Afficher les emails avec badges de sécurité
        function displayEmails(emails) {
            emailList.innerHTML = emails.map(email => {
                const isVerified = email.signature_verified !== false && currentView !== 'unverified';
                const securityClass = isVerified ? 'verified' : 'unverified';
                const securityBadge = isVerified 
                    ? '<span class="security-badge verified"><i class="fas fa-shield-check mr-1"></i>Vérifié</span>'
                    : '<span class="security-badge unverified"><i class="fas fa-exclamation-triangle mr-1"></i>Non vérifié</span>';
                
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
                                </div>
                            </div>
                            <div class="flex items-center space-x-2">
                                ${!email.read ? '<div class="w-2 h-2 rounded-full" style="background-color: #BAA8D3;"></div>' : ''}
                                <i class="fas fa-chevron-right text-gray-400"></i>
                            </div>
                        </div>
                    </div>
                `;
            }).join('');
            
            // Ajouter les event listeners pour les clics
            document.querySelectorAll('.email-item').forEach(item => {
                item.addEventListener('click', function(e) {
                    e.preventDefault(); // Empêche le comportement par défaut
                    e.stopPropagation(); // Empêche la propagation
                    
                    const emailId = this.getAttribute('data-email-id');
                    console.log('Clic sur email ID:', emailId); // Debug
                    openEmail(emailId);
                    
                    return false; // Assurance supplémentaire
                });
            });
        }
        
        // Ouvrir un email
        function openEmail(emailId) {
            console.log('Ouverture email ID:', emailId);
            
            const email = currentEmails.find(e => e.id == emailId);
            if (!email) {
                console.error('Email non trouvé:', emailId);
                showNotification('Email non trouvé', 'error');
                return;
            }
            
            document.getElementById('readSubject').textContent = email.subject || 'Sans objet';
            document.getElementById('readFrom').textContent = email.from_name || email.from || 'Expéditeur inconnu';
            document.getElementById('readDate').textContent = formatDate(email.date || email.created_at);
            document.getElementById('readTo').textContent = email.to || 'Destinataire inconnu';
            document.getElementById('readContent').innerHTML = email.content || 'Contenu vide';
            
            // Afficher les pièces jointes si présentes
            displayEmailAttachments(email.attachments);
            
            // Badge de sécurité dans le modal
            const isVerified = email.signature_verified !== false && currentView !== 'unverified';
            const emailSecurityBadge = document.getElementById('emailSecurityBadge');
            emailSecurityBadge.innerHTML = isVerified 
                ? '<span class="security-badge verified"><i class="fas fa-shield-check mr-1"></i>Signature vérifiée</span>'
                : '<span class="security-badge unverified"><i class="fas fa-exclamation-triangle mr-1"></i>Signature non vérifiée</span>';
            
            readModal.classList.remove('hidden');
            
            // Marquer comme lu
            if (!email.read) {
                markAsRead(emailId);
            }
        }
        
        // Fonctions pour les pièces jointes
        function handleFileSelect(e) {
            const files = Array.from(e.target.files);
            addFiles(files);
        }
        
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
        
        function removeAttachment(index) {
            attachedFiles.splice(index, 1);
            displayAttachments();
        }
        
        function clearAttachments() {
            attachedFiles = [];
            attachmentInput.value = '';
            displayAttachments();
        }
        
        function formatFileSize(bytes) {
            if (bytes === 0) return '0 B';
            const k = 1024;
            const sizes = ['B', 'KB', 'MB', 'GB'];
            const i = Math.floor(Math.log(bytes) / Math.log(k));
            return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
        }
        
        function displayEmailAttachments(attachments) {
            const emailAttachmentsDiv = document.getElementById('emailAttachments');
            const attachmentsListDiv = document.getElementById('attachmentsList');
            
            // Debug pour voir ce qu'on reçoit
            console.log('Attachments reçus:', attachments, typeof attachments);
            
            if (!attachments) {
                emailAttachmentsDiv.classList.add('hidden');
                return;
            }
            
            let attachmentData = [];
            
            // Gérer différents formats d'attachments
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
            
            attachmentsListDiv.innerHTML = attachmentData.map(attachment => {
                // S'assurer que attachment est un objet valide
                const filename = attachment.filename || attachment.name || 'Fichier sans nom';
                const size = attachment.size || 0;
                const attachmentId = attachment.id || attachment.path || '#';
                
                return `
                    <div class="flex items-center justify-between bg-gray-50 p-3 rounded">
                        <div class="flex items-center space-x-3">
                            <i class="fas fa-file text-gray-500"></i>
                            <div>
                                <p class="text-sm font-medium text-gray-700">${filename}</p>
                                <p class="text-xs text-gray-500">${formatFileSize(size)}</p>
                            </div>
                        </div>
                        <a href="/download-attachment/${attachmentId}" 
                           class="text-purple-600 hover:text-purple-800 text-sm">
                            <i class="fas fa-download mr-1"></i>Télécharger
                        </a>
                    </div>
                `;
            }).join('');
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
        
        // Rafraîchir
        refreshBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();
            loadEmails();
            return false;
        });
        
        // Format date
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
        
        // Notifications avec gestion d'erreur améliorée
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
            
            notification.className = `${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;
            notification.innerHTML = `
                <div class="flex items-center">
                    <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'} mr-2"></i>
                    ${displayMessage}
                </div>
            `;
            
            document.getElementById('notifications').appendChild(notification);
            
            setTimeout(() => {
                notification.classList.remove('translate-x-full');
            }, 100);
            
            setTimeout(() => {
                notification.classList.add('translate-x-full');
                setTimeout(() => notification.remove(), 300);
            }, 5000);
        }
        
        // Initialisation
        loadEmails();
        
        // Mise à jour automatique des compteurs toutes les 30 secondes
        setInterval(updateCounters, 30000);
    </script>

    <!-- Footer -->
    <footer>
        <p>&copy; 2025 Missive - Messagerie Sécurisée. Tous droits réservés.</p>
    </footer>
</body>
</html>