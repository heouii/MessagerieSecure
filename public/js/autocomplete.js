// Système d'autocomplétion pour les emails

// Configuration de l'autocomplétion
function setupAutocomplete() {
    const toField = document.getElementById('toField');
    const ccField = document.getElementById('ccField');
    
    if (toField) setupAutocompleteField(toField);
    if (ccField) setupAutocompleteField(ccField);
}
function setupAutocompleteField(field) {
    if (!field.parentNode.classList.contains('autocomplete-container')) {
        const wrapper = document.createElement('div');
        wrapper.className = 'autocomplete-container';
        field.parentNode.insertBefore(wrapper, field);
        wrapper.appendChild(field);
    }
    field.addEventListener('input', handleAutocompleteInput);
    field.addEventListener('focus', handleAutocompleteFocus);
    field.addEventListener('blur', handleAutocompleteBlur);
    field.addEventListener('keydown', handleAutocompleteKeydown);
}

// Gestionnaires d'événements
function handleAutocompleteInput(e) {
    const field = e.target;
    const query = field.value.trim();
    field.classList.add('compose-field-focused');
    clearTimeout(autocompleteTimeout);
    hideAutocompleteSuggestions();
    
    if (query.length < 2) {
        hideEmailHistory();
        return;
    }
    
    autocompleteTimeout = setTimeout(() => {
        fetchEmailSuggestions(field, query);
    }, 300);
}

function handleAutocompleteFocus(e) {
    currentInputField = e.target;
    e.target.classList.add('compose-field-focused');
}

function handleAutocompleteBlur(e) {
    setTimeout(() => {
        e.target.classList.remove('compose-field-focused');
        hideAutocompleteSuggestions();
    }, 200);
}

function handleAutocompleteKeydown(e) {
    const suggestions = document.querySelector('.autocomplete-suggestions.show');
    if (!suggestions) return;
    
    switch (e.key) {
        case 'ArrowDown': 
            e.preventDefault(); 
            navigateSuggestions(1); 
            break;
        case 'ArrowUp': 
            e.preventDefault(); 
            navigateSuggestions(-1); 
            break;
        case 'Enter': 
            e.preventDefault(); 
            if (selectedSuggestionIndex >= 0) selectSuggestion(currentSuggestions[selectedSuggestionIndex]); 
            break;
        case 'Escape': 
            hideAutocompleteSuggestions(); 
            break;
    }
}

// Récupération des suggestions
async function fetchEmailSuggestions(field, query) {
    try {
        showAutocompleteLoading(field);
        
        console.log('🔍 Recherche suggestions pour:', query);
        
        const response = await fetch(`/mailgun-email-suggestions?query=${encodeURIComponent(query)}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            }
        });
        
        console.log('📡 Réponse statut:', response.status);
        
        if (!response.ok) {
            const errorText = await response.text();
            console.error('❌ Erreur HTTP:', response.status, errorText);
            throw new Error(`HTTP ${response.status}: ${errorText}`);
        }
        
        const data = await response.json();
        console.log('📝 Données reçues:', data);
        
        if (data.success && data.suggestions && data.suggestions.length > 0) {
            currentSuggestions = data.suggestions;
            showAutocompleteSuggestions(field, data.suggestions);
            console.log('✅ Suggestions affichées:', data.suggestions.length);
        } else {
            console.log('ℹ️ Aucune suggestion trouvée');
            showNoResults(field);
        }
        
    } catch (error) {
        console.error('💥 Erreur autocomplétion détaillée:', error);
        showAutocompleteError(field, error.message);
    }
}

// Affichage des suggestions
function showAutocompleteLoading(field) {
    const container = field.parentNode;
    let suggestions = container.querySelector('.autocomplete-suggestions');
    if (!suggestions) {
        suggestions = document.createElement('div');
        suggestions.className = 'autocomplete-suggestions';
        container.appendChild(suggestions);
    }
    suggestions.innerHTML = `<div class="autocomplete-loading"><i class="fas fa-spinner fa-spin mr-2"></i>Recherche...</div>`;
    suggestions.classList.add('show');
}

function showAutocompleteSuggestions(field, suggestions) {
    const container = field.parentNode;
    let suggestionsDiv = container.querySelector('.autocomplete-suggestions');
    if (!suggestionsDiv) {
        suggestionsDiv = document.createElement('div');
        suggestionsDiv.className = 'autocomplete-suggestions';
        container.appendChild(suggestionsDiv);
    }
    
    suggestionsDiv.innerHTML = suggestions.map((suggestion, index) => `
        <div class="suggestion-item" data-index="${index}" onclick="selectSuggestion(currentSuggestions[${index}])">
            <div class="suggestion-main">
                <div class="suggestion-email">${suggestion.email}</div>
                ${suggestion.name ? `<div class="suggestion-name">${suggestion.name}</div>` : ''}
                <div class="suggestion-context">${getSuggestionContext(suggestion)}</div>
            </div>
            <div class="suggestion-badge ${suggestion.type === 'sent_to' ? 'sent' : 'received'}">
                ${suggestion.type === 'sent_to' ? 'Envoyé' : 'Reçu'}
            </div>
        </div>
    `).join('');
    
    suggestionsDiv.classList.add('show');
    selectedSuggestionIndex = -1;
}

function showNoResults(field) {
    const container = field.parentNode;
    let suggestions = container.querySelector('.autocomplete-suggestions');
    if (!suggestions) {
        suggestions = document.createElement('div');
        suggestions.className = 'autocomplete-suggestions';
        container.appendChild(suggestions);
    }
    suggestions.innerHTML = `<div class="autocomplete-no-results"><i class="fas fa-search mr-2"></i>Aucune adresse trouvée</div>`;
    suggestions.classList.add('show');
}

function showAutocompleteError(field, errorMessage) {
    const container = field.parentNode;
    let suggestions = container.querySelector('.autocomplete-suggestions');
    
    if (!suggestions) {
        suggestions = document.createElement('div');
        suggestions.className = 'autocomplete-suggestions';
        container.appendChild(suggestions);
    }
    
    suggestions.innerHTML = `
        <div class="autocomplete-no-results">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            Erreur de recherche: ${errorMessage}
        </div>
    `;
    suggestions.classList.add('show');
}

// Navigation et sélection
function navigateSuggestions(direction) {
    const suggestionItems = document.querySelectorAll('.suggestion-item');
    if (suggestionItems.length === 0) return;
    
    if (selectedSuggestionIndex >= 0) {
        suggestionItems[selectedSuggestionIndex].classList.remove('highlighted');
    }
    
    selectedSuggestionIndex += direction;
    if (selectedSuggestionIndex < 0) {
        selectedSuggestionIndex = suggestionItems.length - 1;
    } else if (selectedSuggestionIndex >= suggestionItems.length) {
        selectedSuggestionIndex = 0;
    }
    
    suggestionItems[selectedSuggestionIndex].classList.add('highlighted');
    suggestionItems[selectedSuggestionIndex].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function selectSuggestion(suggestion) {
    if (!currentInputField || !suggestion) return;
    
    currentInputField.value = suggestion.email;
    hideAutocompleteSuggestions();
    loadEmailHistory(suggestion.email);
    
    // Navigation automatique vers le champ suivant
    if (currentInputField.id === 'toField') {
        const ccField = document.getElementById('ccField');
        if (ccField.value.trim() === '') {
            ccField.focus();
        } else {
            document.getElementById('subjectField').focus();
        }
    } else if (currentInputField.id === 'ccField') {
        document.getElementById('subjectField').focus();
    }
    
    showNotification(`Adresse sélectionnée : ${suggestion.email}`, 'success');
}

function hideAutocompleteSuggestions() {
    const suggestions = document.querySelectorAll('.autocomplete-suggestions');
    suggestions.forEach(s => {
        s.classList.remove('show');
        setTimeout(() => {
            if (!s.classList.contains('show')) {
                s.remove();
            }
        }, 200);
    });
    currentSuggestions = [];
    selectedSuggestionIndex = -1;
}

// Fonctions utilitaires
function getSuggestionContext(suggestion) {
    if (suggestion.type === 'sent_to') {
        return 'Vous avez déjà envoyé des emails à cette adresse';
    } else {
        return 'Vous avez reçu des emails de cette adresse';
    }
}

// Historique des emails
async function loadEmailHistory(email) {
    try {
        const response = await fetch(`/mailgun-email-history?email=${encodeURIComponent(email)}`, {
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        if (!response.ok) throw new Error(`HTTP ${response.status}`);
        const data = await response.json();
        if (data.success && data.history.length > 0) {
            showEmailHistory(data.history, email);
        } else {
            hideEmailHistory();
        }
    } catch (error) {
        console.error('Erreur chargement historique:', error);
    }
}

function showEmailHistory(history, email) {
    hideEmailHistory();
    const historyPanel = document.createElement('div');
    historyPanel.id = 'email-history-panel';
    historyPanel.className = 'email-history-panel show';
    historyPanel.innerHTML = `
        <div class="history-header">
            <i class="fas fa-history"></i>
            Historique avec ${email} (${history.length} message${history.length > 1 ? 's' : ''})
        </div>
        <div class="history-items">
            ${history.map(email => `
                <div class="history-item" onclick="insertEmailReference('${email.subject}', '${email.date}')">
                    <div class="history-subject">${email.subject}</div>
                    <div class="history-meta">
                        <span class="history-date">${email.date}</span>
                        <span class="history-folder ${email.folder}">${email.folder}</span>
                    </div>
                    <div class="history-preview">${email.preview}</div>
                </div>
            `).join('')}
        </div>
    `;
    const ccField = document.getElementById('ccField').parentNode.parentNode;
    ccField.parentNode.insertBefore(historyPanel, ccField.nextSibling);
}

function insertEmailReference(subject, date) {
    const messageField = document.getElementById('messageField');
    const currentValue = messageField.value;
    const reference = `\n\n--- En référence à "${subject}" du ${date} ---\n\n`;
    if (currentValue.trim() === '') {
        messageField.value = reference.trim() + '\n\n';
    } else {
        messageField.value = currentValue + reference;
    }
    messageField.focus();
    showNotification('Référence ajoutée au message', 'success');
}

function hideEmailHistory() {
    const existingPanel = document.getElementById('email-history-panel');
    if (existingPanel) {
        existingPanel.remove();
    }
}