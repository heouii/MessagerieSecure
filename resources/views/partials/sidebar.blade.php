<div class="w-64 bg-white shadow-lg border-r border-gray-200">
    <!-- Bouton Composer -->
    <div class="p-4">
        <button id="composeBtn" class="w-full text-white font-medium py-3 px-4 rounded-lg transition-colors" 
                style="background-color: #BAA8D3;" 
                onmouseover="this.style.backgroundColor='#9280A3'" 
                onmouseout="this.style.backgroundColor='#BAA8D3'">
            <i class="fas fa-pen mr-2"></i>Nouveau message
        </button>
    </div>

    <!-- Navigation -->
    <nav class="flex-1">
        <div class="px-2">
            <div class="sidebar-item active flex items-center px-4 py-3 text-gray-700 cursor-pointer" data-view="inbox">
                <i class="fas fa-inbox mr-3" style="color: #BAA8D3;"></i>
                <span>Boîte de réception</span>
                <span id="inboxCount" class="ml-auto text-white text-xs px-2 py-1 rounded-full" style="background-color: #BAA8D3;">0</span>
            </div>

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
            
            <div class="sidebar-item flex items-center px-4 py-3 text-gray-700 cursor-pointer" data-view="spam">
                <i class="fas fa-ban mr-3 text-red-500"></i>
                <span>Spam</span>
                <span id="spamCount" class="ml-auto bg-red-500 text-white text-xs px-2 py-1 rounded-full">0</span>
            </div>
            <div class="sidebar-item flex items-center px-4 py-3 text-gray-700 cursor-pointer" data-view="virus">
                <i class="fas fa-biohazard mr-3 text-red-600"></i>
                <span>Virus</span>
                <span id="virusCount" class="ml-auto bg-red-600 text-white text-xs px-2 py-1 rounded-full">0</span>
            </div>
        </div>
    </nav>

    <!-- Profil utilisateur -->
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
    
    <!-- Bouton retour dashboard -->
    <a href="{{ route('dashboard') }}"
        class="block w-full text-center text-white font-medium py-2 px-4 rounded transition-colors"
        style="background-color: #BAA8D3;"
        onmouseover="this.style.backgroundColor='#9280A3';"
        onmouseout="this.style.backgroundColor='#BAA8D3';">
            <i class="fas fa-arrow-left mr-1"></i> Retour au tableau de bord
    </a>
</div>