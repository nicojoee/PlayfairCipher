<?php
session_start();
require_once 'includes/functions.php';

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    $response = ['success' => false];
    
    switch ($action) {
        case 'encrypt':
            $plaintext = $_POST['plaintext'] ?? '';
            $key = $_POST['key'] ?? '';
            if ($plaintext && $key) {
                $result = playfairEncrypt($plaintext, $key);
                $response = [
                    'success' => true,
                    'result' => $result['ciphertext'],
                    'matrix' => $result['matrix'],
                    'pairs' => $result['pairs']
                ];
            }
            break;
            
        case 'decrypt':
            $ciphertext = $_POST['ciphertext'] ?? '';
            $key = $_POST['key'] ?? '';
            if ($ciphertext && $key) {
                $result = playfairDecrypt($ciphertext, $key);
                $response = [
                    'success' => true,
                    'result' => $result['plaintext'],
                    'matrix' => $result['matrix'],
                    'pairs' => $result['pairs']
                ];
            }
            break;
            
        case 'save_history':
            if (!isset($_SESSION['history'])) {
                $_SESSION['history'] = [];
            }
            $_SESSION['history'][] = [
                'type' => $_POST['type'] ?? '',
                'input' => $_POST['input'] ?? '',
                'output' => $_POST['output'] ?? '',
                'key' => $_POST['key'] ?? '',
                'timestamp' => date('Y-m-d H:i:s')
            ];
            // Keep only last 10 entries
            $_SESSION['history'] = array_slice($_SESSION['history'], -10);
            $response = ['success' => true];
            break;
            
        case 'get_history':
            $response = [
                'success' => true,
                'history' => array_reverse($_SESSION['history'] ?? [])
            ];
            break;
            
        case 'clear_history':
            $_SESSION['history'] = [];
            $response = ['success' => true];
            break;
    }
    
    echo json_encode($response);
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" href="/assets/img/playfair.png" type="image/x-icon">
    <title>Playfair Cipher - Cryptography Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-gradient-to-br from-slate-900 via-slate-800 to-slate-900 min-h-screen text-white">
    
    <!-- Header -->
    <header class="border-b border-slate-700/50 backdrop-blur-sm bg-slate-900/50 sticky top-0 z-50">
        <div class="container mx-auto px-6 py-4">
            <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <<img src="/assets/img/logo.jpg" alt="Playfair Cipher Logo" class="w-10 h-10 rounded-lg object-cover">
                    <div>
                        <h1 class="text-xl font-bold">Playfair Cipher</h1>
                        <p class="text-xs text-slate-400">Cryptography Dashboard</p>
                    </div>
                </div>
                <button id="historyBtn" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 rounded-lg transition-all duration-300 text-sm flex items-center space-x-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span>History</span>
                </button>
            </div>
        </div>
    </header>

 <!-- Main Container -->
    <main class="container mx-auto px-6 py-8">
        
        <!-- Dashboard Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
            
            <!-- Left Column - Input Section -->
            <div class="lg:col-span-2 space-y-4 sm:space-y-6">
                
                <!-- Identity Card -->
                <div class="card">
                    <div class="p-6">
                        <h3 class="text-center text-lg font-semibold mb-4 text-cyan-400">Made by</h3>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <div class="text-center">
                                <p class="text-white font-medium">Nicholas Joe Sumantri</p>
                                <p class="text-slate-400 text-sm mt-1">NRP: 5002221003</p>
                            </div>
                            <div class="text-center">
                                <p class="text-white font-medium">Candra Wardani</p>
                                <p class="text-slate-400 text-sm mt-1">NRP: 5002221045</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                
                <!-- Key Input Card -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="text-lg font-semibold flex items-center space-x-2">
                            <svg class="w-5 h-5 text-cyan-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                            </svg>
                            <span>Encryption Key</span>
                        </h2>
                    </div>
                    <div class="p-6">
                        <input type="text" id="keyInput" placeholder="Enter your secret key..." 
                               class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-lg focus:outline-none focus:border-cyan-400 transition-all duration-300 text-white placeholder-slate-500">
                        <p class="mt-2 text-xs text-slate-400">Key will be used to generate the 5×5 cipher matrix</p>
                    </div>
                </div>

                <!-- Mode Selector -->
                <div class="flex space-x-4">
                    <button id="encryptModeBtn" class="mode-btn active flex-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                        <span>Encrypt</span>
                    </button>
                    <button id="decryptModeBtn" class="mode-btn flex-1">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"/>
                        </svg>
                        <span>Decrypt</span>
                    </button>
                </div>

                <!-- Input/Output Card -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="text-lg font-semibold" id="inputLabel">Plaintext Input</h2>
                    </div>
                    <div class="p-6 space-y-4">
                        <textarea id="inputText" rows="4" placeholder="Enter text here..." 
                                class="w-full px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-lg focus:outline-none focus:border-cyan-400 transition-all duration-300 text-white placeholder-slate-500 resize-none"></textarea>
                        
                        <button id="processBtn" class="btn-primary w-full">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                            </svg>
                            <span id="processBtnText">Encrypt Message</span>
                        </button>
                    </div>
                </div>

                <!-- Result Card -->
                <div id="resultCard" class="card hidden">
                    <div class="card-header flex justify-between items-center">
                        <h2 class="text-lg font-semibold" id="outputLabel">Encrypted Result</h2>
                        <button id="copyBtn" class="text-cyan-400 hover:text-cyan-300 transition-colors text-sm flex items-center space-x-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                            </svg>
                            <span>Copy</span>
                        </button>
                    </div>
                    <div class="p-6">
                        <div id="outputText" class="px-4 py-3 bg-slate-800/50 border border-slate-700 rounded-lg text-cyan-400 font-mono break-all"></div>
                    </div>
                </div>

            </div>

            <!-- Right Column - Matrix & Info -->
            <div class="space-y-6">
                
                <!-- Cipher Matrix Card -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="text-lg font-semibold">Cipher Matrix</h2>
                    </div>
                    <div class="p-6">
                        <div id="matrixContainer" class="grid grid-cols-5 gap-2">
                            <!-- Matrix will be generated here -->
                            <div class="aspect-square bg-slate-800/50 border border-slate-700 rounded flex items-center justify-center text-slate-600">?</div>
                            <div class="aspect-square bg-slate-800/50 border border-slate-700 rounded flex items-center justify-center text-slate-600">?</div>
                            <div class="aspect-square bg-slate-800/50 border border-slate-700 rounded flex items-center justify-center text-slate-600">?</div>
                            <div class="aspect-square bg-slate-800/50 border border-slate-700 rounded flex items-center justify-center text-slate-600">?</div>
                            <div class="aspect-square bg-slate-800/50 border border-slate-700 rounded flex items-center justify-center text-slate-600">?</div>
                            <div class="aspect-square bg-slate-800/50 border border-slate-700 rounded flex items-center justify-center text-slate-600">?</div>
                            <div class="aspect-square bg-slate-800/50 border border-slate-700 rounded flex items-center justify-center text-slate-600">?</div>
                            <div class="aspect-square bg-slate-800/50 border border-slate-700 rounded flex items-center justify-center text-slate-600">?</div>
                            <div class="aspect-square bg-slate-800/50 border border-slate-700 rounded flex items-center justify-center text-slate-600">?</div>
                            <div class="aspect-square bg-slate-800/50 border border-slate-700 rounded flex items-center justify-center text-slate-600">?</div>
                            <div class="aspect-square bg-slate-800/50 border border-slate-700 rounded flex items-center justify-center text-slate-600">?</div>
                            <div class="aspect-square bg-slate-800/50 border border-slate-700 rounded flex items-center justify-center text-slate-600">?</div>
                            <div class="aspect-square bg-slate-800/50 border border-slate-700 rounded flex items-center justify-center text-slate-600">?</div>
                            <div class="aspect-square bg-slate-800/50 border border-slate-700 rounded flex items-center justify-center text-slate-600">?</div>
                            <div class="aspect-square bg-slate-800/50 border border-slate-700 rounded flex items-center justify-center text-slate-600">?</div>
                            <div class="aspect-square bg-slate-800/50 border border-slate-700 rounded flex items-center justify-center text-slate-600">?</div>
                            <div class="aspect-square bg-slate-800/50 border border-slate-700 rounded flex items-center justify-center text-slate-600">?</div>
                            <div class="aspect-square bg-slate-800/50 border border-slate-700 rounded flex items-center justify-center text-slate-600">?</div>
                            <div class="aspect-square bg-slate-800/50 border border-slate-700 rounded flex items-center justify-center text-slate-600">?</div>
                            <div class="aspect-square bg-slate-800/50 border border-slate-700 rounded flex items-center justify-center text-slate-600">?</div>
                            <div class="aspect-square bg-slate-800/50 border border-slate-700 rounded flex items-center justify-center text-slate-600">?</div>
                            <div class="aspect-square bg-slate-800/50 border border-slate-700 rounded flex items-center justify-center text-slate-600">?</div>
                            <div class="aspect-square bg-slate-800/50 border border-slate-700 rounded flex items-center justify-center text-slate-600">?</div>
                            <div class="aspect-square bg-slate-800/50 border border-slate-700 rounded flex items-center justify-center text-slate-600">?</div>
                            <div class="aspect-square bg-slate-800/50 border border-slate-700 rounded flex items-center justify-center text-slate-600">?</div>
                        </div>
                    </div>
                </div>

                <!-- Pairs Display -->
                <div id="pairsCard" class="card hidden">
                    <div class="card-header">
                        <h2 class="text-lg font-semibold">Letter Pairs</h2>
                    </div>
                    <div class="p-6">
                        <div id="pairsContainer" class="flex flex-wrap gap-2"></div>
                    </div>
                </div>

                <!-- Info Card -->
                <div class="card">
                    <div class="card-header">
                        <h2 class="text-lg font-semibold">About Playfair</h2>
                    </div>
                    <div class="p-6 space-y-3 text-sm text-slate-400">
                        <p>The Playfair cipher uses a 5×5 matrix to encrypt pairs of letters.</p>
                        <ul class="space-y-2 list-disc list-inside">
                            <li>I and J are treated as one letter</li>
                            <li>Text is split into pairs (digraphs)</li>
                            <li>Each pair is encrypted using matrix rules</li>
                        </ul>
                    </div>
                </div>

            </div>

        </div>

    </main>

    <!-- History Modal -->
    <div id="historyModal" class="fixed inset-0 bg-black/50 backdrop-blur-sm z-50 hidden flex items-center justify-center p-6">
        <div class="bg-slate-900 border border-slate-700 rounded-2xl max-w-4xl w-full max-h-[80vh] overflow-hidden flex flex-col">
            <div class="p-6 border-b border-slate-700 flex justify-between items-center">
                <h2 class="text-xl font-bold">Encryption History</h2>
                <div class="flex space-x-2">
                    <button id="clearHistoryBtn" class="px-4 py-2 bg-red-500/10 hover:bg-red-500/20 text-red-400 rounded-lg transition-all duration-300 text-sm">
                        Clear All
                    </button>
                    <button id="closeHistoryBtn" class="px-4 py-2 bg-slate-800 hover:bg-slate-700 rounded-lg transition-all duration-300">
                        Close
                    </button>
                </div>
            </div>
            <div id="historyContent" class="p-6 overflow-y-auto flex-1">
                <!-- History items will be loaded here -->
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>