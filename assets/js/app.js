// Playfair Cipher Dashboard - Main JavaScript

// State Management
const state = {
    mode: 'encrypt',
    currentKey: '',
    currentMatrix: [],
    currentPairs: []
};

// DOM Elements
const elements = {
    keyInput: document.getElementById('keyInput'),
    inputText: document.getElementById('inputText'),
    outputText: document.getElementById('outputText'),
    processBtn: document.getElementById('processBtn'),
    processBtnText: document.getElementById('processBtnText'),
    encryptModeBtn: document.getElementById('encryptModeBtn'),
    decryptModeBtn: document.getElementById('decryptModeBtn'),
    inputLabel: document.getElementById('inputLabel'),
    outputLabel: document.getElementById('outputLabel'),
    resultCard: document.getElementById('resultCard'),
    matrixContainer: document.getElementById('matrixContainer'),
    pairsCard: document.getElementById('pairsCard'),
    pairsContainer: document.getElementById('pairsContainer'),
    copyBtn: document.getElementById('copyBtn'),
    historyBtn: document.getElementById('historyBtn'),
    historyModal: document.getElementById('historyModal'),
    closeHistoryBtn: document.getElementById('closeHistoryBtn'),
    clearHistoryBtn: document.getElementById('clearHistoryBtn'),
    historyContent: document.getElementById('historyContent')
};

// Initialize App
function init() {
    setupEventListeners();
    loadHistory();
}

// Setup Event Listeners
function setupEventListeners() {
    // Mode switching
    elements.encryptModeBtn.addEventListener('click', () => setMode('encrypt'));
    elements.decryptModeBtn.addEventListener('click', () => setMode('decrypt'));
    
    // Process button
    elements.processBtn.addEventListener('click', processText);
    
    // Copy button
    elements.copyBtn.addEventListener('click', copyResult);
    
    // History modal
    elements.historyBtn.addEventListener('click', openHistoryModal);
    elements.closeHistoryBtn.addEventListener('click', closeHistoryModal);
    elements.clearHistoryBtn.addEventListener('click', clearHistory);
    
    // Close modal on outside click
    elements.historyModal.addEventListener('click', (e) => {
        if (e.target === elements.historyModal) {
            closeHistoryModal();
        }
    });
    
    // Enter key to process
    elements.inputText.addEventListener('keydown', (e) => {
        if (e.ctrlKey && e.key === 'Enter') {
            processText();
        }
    });
}

// Set Mode (Encrypt/Decrypt)
function setMode(mode) {
    state.mode = mode;
    
    if (mode === 'encrypt') {
        elements.encryptModeBtn.classList.add('active');
        elements.decryptModeBtn.classList.remove('active');
        elements.inputLabel.textContent = 'Plaintext Input';
        elements.outputLabel.textContent = 'Encrypted Result';
        elements.processBtnText.textContent = 'Encrypt Message';
        elements.inputText.placeholder = 'Enter text to encrypt...';
    } else {
        elements.decryptModeBtn.classList.add('active');
        elements.encryptModeBtn.classList.remove('active');
        elements.inputLabel.textContent = 'Ciphertext Input';
        elements.outputLabel.textContent = 'Decrypted Result';
        elements.processBtnText.textContent = 'Decrypt Message';
        elements.inputText.placeholder = 'Enter text to decrypt...';
    }
    
    // Clear results
    elements.resultCard.classList.add('hidden');
    elements.pairsCard.classList.add('hidden');
}

// Process Text (Encrypt/Decrypt)
async function processText() {
    const key = elements.keyInput.value.trim();
    const text = elements.inputText.value.trim();
    
    // Validation
    if (!key) {
        showNotification('Please enter an encryption key', 'error');
        elements.keyInput.focus();
        return;
    }
    
    if (!text) {
        showNotification('Please enter text to process', 'error');
        elements.inputText.focus();
        return;
    }
    
    // Show loading state
    elements.processBtn.disabled = true;
    elements.processBtnText.innerHTML = '<div class="spinner"></div> Processing...';
    
    try {
        // Prepare form data
        const formData = new FormData();
        formData.append('action', state.mode);
        formData.append('key', key);
        
        if (state.mode === 'encrypt') {
            formData.append('plaintext', text);
        } else {
            formData.append('ciphertext', text);
        }
        
        // Send request
        const response = await fetch('index.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Display result
            displayResult(data);
            
            // Save to history
            saveToHistory(text, data.result, key);
            
            showNotification('Process completed successfully!', 'success');
        } else {
            showNotification('Processing failed. Please try again.', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('An error occurred. Please try again.', 'error');
    } finally {
        // Reset button state
        elements.processBtn.disabled = false;
        elements.processBtnText.textContent = state.mode === 'encrypt' ? 'Encrypt Message' : 'Decrypt Message';
    }
}

// Display Result
function displayResult(data) {
    // Show result card
    elements.resultCard.classList.remove('hidden');
    elements.resultCard.classList.add('slide-up');
    
    // Display output
    elements.outputText.textContent = data.result;
    
    // Display matrix
    if (data.matrix) {
        displayMatrix(data.matrix);
    }
    
    // Display pairs
    if (data.pairs && data.pairs.length > 0) {
        displayPairs(data.pairs);
    }
    
    // Store in state
    state.currentMatrix = data.matrix;
    state.currentPairs = data.pairs;
}

// Display Matrix
function displayMatrix(matrix) {
    elements.matrixContainer.innerHTML = '';
    
    let delay = 0;
    matrix.forEach(row => {
        row.forEach(cell => {
            const cellDiv = document.createElement('div');
            cellDiv.className = 'matrix-cell';
            cellDiv.textContent = cell;
            cellDiv.style.animationDelay = `${delay}s`;
            elements.matrixContainer.appendChild(cellDiv);
            delay += 0.02;
        });
    });
}

// Display Pairs
function displayPairs(pairs) {
    elements.pairsCard.classList.remove('hidden');
    elements.pairsCard.classList.add('slide-up');
    elements.pairsContainer.innerHTML = '';
    
    let delay = 0;
    pairs.forEach(pair => {
        const badge = document.createElement('div');
        badge.className = 'pair-badge';
        badge.textContent = pair;
        badge.style.animationDelay = `${delay}s`;
        elements.pairsContainer.appendChild(badge);
        delay += 0.05;
    });
}

// Copy Result to Clipboard
function copyResult() {
    const text = elements.outputText.textContent;
    
    navigator.clipboard.writeText(text).then(() => {
        showNotification('Copied to clipboard!', 'success');
        
        // Visual feedback
        elements.copyBtn.innerHTML = `
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            <span>Copied!</span>
        `;
        
        setTimeout(() => {
            elements.copyBtn.innerHTML = `
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                </svg>
                <span>Copy</span>
            `;
        }, 2000);
    }).catch(err => {
        console.error('Failed to copy:', err);
        showNotification('Failed to copy', 'error');
    });
}

// Save to History
async function saveToHistory(input, output, key) {
    const formData = new FormData();
    formData.append('action', 'save_history');
    formData.append('type', state.mode);
    formData.append('input', input);
    formData.append('output', output);
    formData.append('key', key);
    
    try {
        await fetch('index.php', {
            method: 'POST',
            body: formData
        });
    } catch (error) {
        console.error('Error saving history:', error);
    }
}

// Load History
async function loadHistory() {
    const formData = new FormData();
    formData.append('action', 'get_history');
    
    try {
        const response = await fetch('index.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success && data.history) {
            displayHistory(data.history);
        }
    } catch (error) {
        console.error('Error loading history:', error);
    }
}

// Display History
function displayHistory(history) {
    if (!history || history.length === 0) {
        elements.historyContent.innerHTML = `
            <div class="text-center text-slate-500 py-12">
                <svg class="w-16 h-16 mx-auto mb-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p>No history yet</p>
                <p class="text-sm mt-2">Your encryption/decryption history will appear here</p>
            </div>
        `;
        return;
    }
    
    elements.historyContent.innerHTML = history.map((item, index) => `
        <div class="history-item mb-4" style="animation-delay: ${index * 0.05}s">
            <div class="flex justify-between items-start mb-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold ${
                    item.type === 'encrypt' 
                        ? 'bg-cyan-500/20 text-cyan-400' 
                        : 'bg-purple-500/20 text-purple-400'
                }">
                    ${item.type === 'encrypt' ? '🔒 Encrypt' : '🔓 Decrypt'}
                </span>
                <span class="text-xs text-slate-500">${item.timestamp}</span>
            </div>
            <div class="space-y-2 text-sm">
                <div>
                    <span class="text-slate-400">Key:</span>
                    <span class="ml-2 text-white font-mono">${escapeHtml(item.key)}</span>
                </div>
                <div>
                    <span class="text-slate-400">Input:</span>
                    <span class="ml-2 text-white">${escapeHtml(item.input.substring(0, 50))}${item.input.length > 50 ? '...' : ''}</span>
                </div>
                <div>
                    <span class="text-slate-400">Output:</span>
                    <span class="ml-2 text-cyan-400 font-mono">${escapeHtml(item.output.substring(0, 50))}${item.output.length > 50 ? '...' : ''}</span>
                </div>
            </div>
        </div>
    `).join('');
}

// Open History Modal
function openHistoryModal() {
    loadHistory();
    elements.historyModal.classList.remove('hidden');
    elements.historyModal.classList.add('modal-enter');
}

// Close History Modal
function closeHistoryModal() {
    elements.historyModal.classList.add('hidden');
    elements.historyModal.classList.remove('modal-enter');
}

// Clear History
async function clearHistory() {
    if (!confirm('Are you sure you want to clear all history?')) {
        return;
    }
    
    const formData = new FormData();
    formData.append('action', 'clear_history');
    
    try {
        const response = await fetch('index.php', {
            method: 'POST',
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            loadHistory();
            showNotification('History cleared successfully', 'success');
        }
    } catch (error) {
        console.error('Error clearing history:', error);
        showNotification('Failed to clear history', 'error');
    }
}

// Show Notification
function showNotification(message, type = 'success') {
    const notification = document.createElement('div');
    notification.className = 'notification';
    
    const bgColor = type === 'success' ? 'rgba(6, 182, 212, 0.9)' : 'rgba(239, 68, 68, 0.9)';
    notification.style.background = bgColor;
    
    notification.innerHTML = `
        <div class="flex items-center space-x-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                ${type === 'success' 
                    ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>'
                    : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>'
                }
            </svg>
            <span>${message}</span>
        </div>
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'notificationSlide 0.3s ease reverse';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

// Escape HTML
function escapeHtml(text) {
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, m => map[m]);
}

// Initialize on DOM load
document.addEventListener('DOMContentLoaded', init);