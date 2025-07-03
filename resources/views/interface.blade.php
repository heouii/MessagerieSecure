@extends('layouts.app')
@section('content')
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
<style>
    .sidebar-item:hover {
        background-color: rgba(186, 168, 211, 0.1); /* Violet clair au hover */
    }
    .sidebar-item.active {
        background-color: rgba(186, 168, 211, 0.2); /* Violet actif */
        border-right: 3px solid #927ca6; /* Bordure violet foncé */
    }
    .email-item:hover {
        background-color: #f4f2f8; /* Violet très clair */
    }
    .email-item.unread {
        background-color: #fbf9ff;
        font-weight: 600;
    }
    .compose-editor {
        min-height: 300px;
    }
</style>
    <div class="flex h-screen">
        
        <!-- Sidebar -->
        <div class="w-64 bg-white shadow-lg border-r border-gray-200">
            
            <!-- Bouton Composer -->
            <div class="p-4">
                <button id="composeBtn" class="w-full bg-[#927ca6] hover:bg-[#7c6993] text-white font-medium py-3 px-4 rounded-lg transition-colors">
                    <i class="fas fa-pen mr-2"></i>Nouveau message
                </button>
            </div>
            
            <!-- Menu Navigation -->
            <nav class="flex-1">
                <div class="px-2">
                    <div class="sidebar-item active flex items-center px-4 py-3 text-gray-700 cursor-pointer" data-view="inbox">
                        <i class="fas fa-inbox mr-3"></i>
                        <span>Boîte de réception</span>
                        <span id="inboxCount" class="ml-auto bg-[#927ca6] text-white text-xs px-2 py-1 rounded-full">0</span>
                    </div>
                    <div class="sidebar-item flex items-center px-4 py-3 text-gray-700 cursor-pointer" data-view="sent">
                        <i class="fas fa-paper-plane mr-3"></i>
                        <span>Envoyés</span>
                    </div>
                    <div class="sidebar-item flex items-center px-4 py-3 text-gray-700 cursor-pointer" data-view="drafts">
                        <i class="fas fa-edit mr-3"></i>
                        <span>Brouillons</span>
                    </div>
                    <div class="sidebar-item flex items-center px-4 py-3 text-gray-700 cursor-pointer" data-view="trash">
                        <i class="fas fa-trash mr-3"></i>
                        <span>Corbeille</span>
                    </div>
                </div>
                <div class="p-4">
                <a href="{{ route('dashboard') }}" class="w-full inline-flex items-center justify-center px-4 py-3 bg-[#927ca6] text-white font-medium rounded-lg hover:bg-[#7c6993] transition-colors">
                    <i class="fas fa-arrow-left mr-2"></i> Retour au Dashboard
                </a>
            </div>
            </nav>
            
            <!-- User Info -->
            <div class="p-4 border-t border-gray-200">
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-gradient-to-r from-[#BAA8D3] to-[#927ca6] rounded-full flex items-center justify-center text-white text-sm font-medium">
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
                                   class="pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
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
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="destinataire@exemple.com">
                        </div>
                        <div class="flex items-center space-x-2">
                            <label class="text-sm font-medium text-gray-700 w-16">Cc :</label>
                            <input type="email" id="ccField"
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Copie carbone (optionnel)">
                        </div>
                        <div class="flex items-center space-x-2">
                            <label class="text-sm font-medium text-gray-700 w-16">Objet :</label>
                            <input type="text" id="subjectField" required
                                   class="flex-1 px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Objet du message">
                        </div>
                    </div>
                    
                    <div class="flex-1 p-4">
                        <textarea id="messageField" required
                                  class="w-full h-64 px-3 py-2 border border-gray-300 rounded resize-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                  placeholder="Écrivez votre message ici..."></textarea>
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
                            <button type="submit" id="sendBtn"class="px-6 py-2 bg-[#927ca6] text-white rounded hover:bg-[#7c6993] transition-colors flex items-center">
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
                    <h3 id="readSubject" class="text-lg font-semibold text-gray-800"></h3>
                    <div class="flex space-x-2">
                        <button id="replyBtn" class="text-blue-600 hover:text-blue-700">
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
        
        // Éléments DOM
        const composeBtn = document.getElementById('composeBtn');
        const composeModal = document.getElementById('composeModal');
        const closeComposeBtn = document.getElementById('closeComposeBtn');
        const composeForm = document.getElementById('composeForm');
        const emailList = document.getElementById('emailList');
        const refreshBtn = document.getElementById('refreshBtn');
        const readModal = document.getElementById('readModal');
        const closeReadBtn = document.getElementById('closeReadBtn');
        
        // Navigation sidebar
        document.querySelectorAll('.sidebar-item').forEach(item => {
            item.addEventListener('click', () => {
                document.querySelectorAll('.sidebar-item').forEach(i => i.classList.remove('active'));
                item.classList.add('active');
                currentView = item.dataset.view;
                document.getElementById('viewTitle').textContent = item.textContent.trim();
                loadEmails();
            });
        });
        
        // Modal composer
        composeBtn.addEventListener('click', () => {
            composeModal.classList.remove('hidden');
        });
        
        closeComposeBtn.addEventListener('click', () => {
            composeModal.classList.add('hidden');
            composeForm.reset();
        });
        
        // Fermer modal en cliquant à côté
        composeModal.addEventListener('click', (e) => {
            if (e.target === composeModal) {
                composeModal.classList.add('hidden');
                composeForm.reset();
            }
        });
        
        // Modal lecture
        closeReadBtn.addEventListener('click', () => {
            readModal.classList.add('hidden');
        });
        
        readModal.addEventListener('click', (e) => {
            if (e.target === readModal) {
                readModal.classList.add('hidden');
            }
        });
        
        // Envoi d'email
        composeForm.addEventListener('submit', async (e) => {
            e.preventDefault();
            
            const formData = {
                to: document.getElementById('toField').value,
                cc: document.getElementById('ccField').value,
                subject: document.getElementById('subjectField').value,
                message: document.getElementById('messageField').value,
                html_format: document.getElementById('htmlFormat').checked,
                read_receipt: document.getElementById('readReceipt').checked
            };
            
            const sendBtn = document.getElementById('sendBtn');
            const originalText = sendBtn.innerHTML;
            sendBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>Envoi...';
            sendBtn.disabled = true;
            
            try {
                const response = await fetch('/mailgun-send', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    },
                    body: JSON.stringify(formData)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification('Email envoyé avec succès !', 'success');
                    composeModal.classList.add('hidden');
                    composeForm.reset();
                    loadEmails();
                } else {
                    showNotification(data.error || 'Erreur lors de l\'envoi', 'error');
                }
                
            } catch (error) {
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
                    emailList.innerHTML = `
                        <div class="p-8 text-center text-gray-500">
                            <i class="fas fa-inbox text-4xl mb-4"></i>
                            <p>Aucun message dans ${currentView === 'inbox' ? 'la boîte de réception' : currentView}</p>
                        </div>
                    `;
                } else {
                    displayEmails(currentEmails);
                }
                
                // Mettre à jour le compteur
                if (currentView === 'inbox') {
                    document.getElementById('inboxCount').textContent = currentEmails.filter(e => !e.read).length;
                }
                
            } catch (error) {
                emailList.innerHTML = `
                    <div class="p-8 text-center text-red-500">
                        <i class="fas fa-exclamation-triangle text-4xl mb-4"></i>
                        <p>Erreur lors du chargement des messages</p>
                    </div>
                `;
            }
        }
        
        // Afficher les emails
        function displayEmails(emails) {
            emailList.innerHTML = emails.map(email => `
                <div class="email-item ${!email.read ? 'unread' : ''} p-4 cursor-pointer border-b border-gray-100" 
                     onclick="openEmail('${email.id}')">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-4 flex-1">
                            <div class="w-10 h-10 bg-gradient-to-r from-[#BAA8D3] to-[#927ca6] rounded-full flex items-center justify-center text-white font-medium">
                                ${email.from_name ? email.from_name.charAt(0).toUpperCase() : email.from.charAt(0).toUpperCase()}
                            </div>
                            <div class="flex-1">
                                <div class="flex items-center justify-between">
                                    <p class="font-medium text-gray-900">${email.from_name || email.from}</p>
                                    <p class="text-sm text-gray-500">${formatDate(email.date)}</p>
                                </div>
                                <p class="text-sm font-medium text-gray-800 truncate">${email.subject}</p>
                                <p class="text-sm text-gray-600 truncate">${email.preview}</p>
                            </div>
                        </div>
                        <div class="flex items-center space-x-2">
                            ${!email.read ? '<div class="w-2 h-2 bg-blue-600 rounded-full"></div>' : ''}
                            <i class="fas fa-chevron-right text-gray-400"></i>
                        </div>
                    </div>
                </div>
            `).join('');
        }
        
        // Ouvrir un email
        function openEmail(emailId) {
            const email = currentEmails.find(e => e.id === emailId);
            if (!email) return;
            
            document.getElementById('readSubject').textContent = email.subject;
            document.getElementById('readFrom').textContent = email.from_name || email.from;
            document.getElementById('readDate').textContent = formatDate(email.date);
            document.getElementById('readTo').textContent = email.to;
            document.getElementById('readContent').innerHTML = email.content;
            
            readModal.classList.remove('hidden');
            
            // Marquer comme lu
            if (!email.read) {
                markAsRead(emailId);
            }
        }
        
        // Marquer comme lu
        async function markAsRead(emailId) {
            try {
                await fetch(`${API_BASE}/emails/${emailId}/read`, {
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
        refreshBtn.addEventListener('click', loadEmails);
        
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
        
        // Notifications
        function showNotification(message, type = 'info') {
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
                    ${message}
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
    </script>
@endsection