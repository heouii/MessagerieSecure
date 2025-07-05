<div id="composeModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50">
    <div class="flex items-center justify-center h-full p-4">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-screen overflow-y-auto">
            <!-- Header -->
            <div class="flex items-center justify-between p-4 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-800">Nouveau message</h3>
                <button id="closeComposeBtn" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Formulaire -->
            <form id="composeForm" class="flex flex-col h-full">
                <!-- Champs d'en-tête -->
                <div class="p-4 space-y-4 border-b border-gray-200">
                    <div class="flex items-center space-x-2">
                        <label class="text-sm font-medium text-gray-700 w-16">À :</label>
                        <input type="email" id="toField" required
                               class="flex-1 px-3 py-2 border border-gray-300 rounded" 
                               style="--tw-ring-color: #BAA8D3;" 
                               onfocus="this.style.borderColor='#9280A3'" 
                               onblur="this.style.borderColor='#BAA8D3'"
                               placeholder="destinataire@exemple.com">
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <label class="text-sm font-medium text-gray-700 w-16">Cc :</label>
                        <input type="email" id="ccField"
                               class="flex-1 px-3 py-2 border border-gray-300 rounded" 
                               style="--tw-ring-color: #BAA8D3;" 
                               onfocus="this.style.borderColor='#9280A3'" 
                               onblur="this.style.borderColor='#BAA8D3'"
                               placeholder="Copie carbone (optionnel)">
                    </div>
                    
                    <div class="flex items-center space-x-2">
                        <label class="text-sm font-medium text-gray-700 w-16">Objet :</label>
                        <input type="text" id="subjectField" required
                               class="flex-1 px-3 py-2 border border-gray-300 rounded" 
                               style="--tw-ring-color: #BAA8D3;" 
                               onfocus="this.style.borderColor='#9280A3'" 
                               onblur="this.style.borderColor='#BAA8D3'"
                               placeholder="Objet du message">
                    </div>
                </div>
                
                <!-- Zone de composition -->
                <div class="flex-1 p-4">
                    <textarea id="messageField" required
                              class="w-full h-48 px-3 py-2 border border-gray-300 rounded resize-none mb-4" 
                              style="--tw-ring-color: #BAA8D3;" 
                              onfocus="this.style.borderColor='#9280A3'" 
                              onblur="this.style.borderColor='#BAA8D3'"
                              placeholder="Écrivez votre message ici..."></textarea>
                    
                    <!-- Zone pièces jointes -->
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-4 text-center" 
                         id="attachmentZone" style="transition: border-color 0.3s;">
                        <input type="file" id="attachmentInput" multiple class="hidden" accept="*/*">
                        
                        <div id="attachmentPlaceholder">
                            <i class="fas fa-paperclip text-gray-400 text-2xl mb-2"></i>
                            <p class="text-gray-500 mb-2">
                                Glissez vos fichiers ici ou 
                                <button type="button" id="browseFiles" class="text-purple-600 underline">parcourez</button>
                            </p>
                            <p class="text-xs text-gray-400">Maximum 25MB par fichier</p>
                        </div>
                        
                        <div id="attachmentList" class="hidden space-y-2"></div>
                    </div>
                </div>
                
                <!-- Footer avec boutons -->
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
                                class="px-6 py-2 text-white rounded transition-colors" 
                                style="background-color: #BAA8D3;" 
                                onmouseover="this.style.backgroundColor='#9280A3'" 
                                onmouseout="this.style.backgroundColor='#BAA8D3'">
                            <i class="fas fa-paper-plane mr-1"></i>Envoyer
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>