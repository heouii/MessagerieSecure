<div id="readModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center h-full p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-4xl max-h-[90vh] overflow-hidden">
            <!-- Header avec actions -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <h3 id="readSubject" class="text-lg font-semibold text-gray-800"></h3>
                    <div id="emailSecurityBadge"></div>
                </div>
                
                <div class="flex space-x-2">
                    <button id="replyBtn" 
                            style="color: #BAA8D3;" 
                            onmouseover="this.style.color='#9280A3'" 
                            onmouseout="this.style.color='#BAA8D3'">
                        <i class="fas fa-reply mr-1"></i>Répondre
                    </button>
                    
                    <button id="deleteEmailBtn" class="text-red-600 hover:text-red-700">
                        <i class="fas fa-trash mr-1"></i>Supprimer
                    </button>
                    
                    <button id="restoreEmailBtn" class="text-green-600 hover:text-green-700 hidden">
                        <i class="fas fa-undo mr-1"></i>Restaurer
                    </button>
                    
                    <button id="verifyEmailBtn" class="text-green-600 hover:text-green-700 hidden">
                        <i class="fas fa-check-circle mr-1"></i> Valider
                    </button>
                    
                    <button id="closeReadBtn" class="text-gray-500 hover:text-gray-700">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
            
            <!-- Métadonnées email -->
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
            
            <!-- Contenu email et pièces jointes -->
            <div class="p-6 flex-1 overflow-y-auto max-h-96">
                <div id="readContent" class="prose max-w-none"></div>

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