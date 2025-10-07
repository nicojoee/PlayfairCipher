<?php

/**
 * Generate Playfair cipher matrix from key
 */
function generatePlayfairMatrix($key) {
    $key = strtoupper(preg_replace('/[^A-Z]/i', '', $key));
    $key = str_replace('J', 'I', $key);
    
    $matrix = [];
    $used = [];
    
    // Add key letters to matrix
    for ($i = 0; $i < strlen($key); $i++) {
        $char = $key[$i];
        if (!isset($used[$char])) {
            $matrix[] = $char;
            $used[$char] = true;
        }
    }
    
    // Add remaining letters
    for ($i = 65; $i <= 90; $i++) {
        $char = chr($i);
        if ($char === 'J') continue;
        if (!isset($used[$char])) {
            $matrix[] = $char;
            $used[$char] = true;
        }
    }
    
    // Convert to 5x5 array
    $result = [];
    for ($i = 0; $i < 5; $i++) {
        $result[$i] = array_slice($matrix, $i * 5, 5);
    }
    
    return $result;
}

/**
 * Find position of character in matrix
 */
function findPosition($matrix, $char) {
    for ($i = 0; $i < 5; $i++) {
        for ($j = 0; $j < 5; $j++) {
            if ($matrix[$i][$j] === $char) {
                return ['row' => $i, 'col' => $j];
            }
        }
    }
    return null;
}

/**
 * Prepare text for encryption (split into pairs)
 */
function prepareText($text) {
    $text = strtoupper(preg_replace('/[^A-Z]/i', '', $text));
    $text = str_replace('J', 'I', $text);
    
    $pairs = [];
    $i = 0;
    
    while ($i < strlen($text)) {
        $a = $text[$i];
        $b = ($i + 1 < strlen($text)) ? $text[$i + 1] : 'X';
        
        // If same letter, insert X
        if ($a === $b) {
            $pairs[] = $a . 'X';
            $i++;
        } else {
            $pairs[] = $a . $b;
            $i += 2;
        }
    }
    
    return $pairs;
}

/**
 * Encrypt a pair of letters
 */
function encryptPair($matrix, $a, $b) {
    $posA = findPosition($matrix, $a);
    $posB = findPosition($matrix, $b);
    
    // Same row
    if ($posA['row'] === $posB['row']) {
        $colA = ($posA['col'] + 1) % 5;
        $colB = ($posB['col'] + 1) % 5;
        return $matrix[$posA['row']][$colA] . $matrix[$posB['row']][$colB];
    }
    
    // Same column
    if ($posA['col'] === $posB['col']) {
        $rowA = ($posA['row'] + 1) % 5;
        $rowB = ($posB['row'] + 1) % 5;
        return $matrix[$rowA][$posA['col']] . $matrix[$rowB][$posB['col']];
    }
    
    // Rectangle
    return $matrix[$posA['row']][$posB['col']] . $matrix[$posB['row']][$posA['col']];
}

/**
 * Decrypt a pair of letters
 */
function decryptPair($matrix, $a, $b) {
    $posA = findPosition($matrix, $a);
    $posB = findPosition($matrix, $b);
    
    // Same row
    if ($posA['row'] === $posB['row']) {
        $colA = ($posA['col'] - 1 + 5) % 5;
        $colB = ($posB['col'] - 1 + 5) % 5;
        return $matrix[$posA['row']][$colA] . $matrix[$posB['row']][$colB];
    }
    
    // Same column
    if ($posA['col'] === $posB['col']) {
        $rowA = ($posA['row'] - 1 + 5) % 5;
        $rowB = ($posB['row'] - 1 + 5) % 5;
        return $matrix[$rowA][$posA['col']] . $matrix[$rowB][$posB['col']];
    }
    
    // Rectangle
    return $matrix[$posA['row']][$posB['col']] . $matrix[$posB['row']][$posA['col']];
}

/**
 * Encrypt plaintext using Playfair cipher
 */
function playfairEncrypt($plaintext, $key) {
    if (empty($plaintext) || empty($key)) {
        return ['ciphertext' => '', 'matrix' => [], 'pairs' => []];
    }
    
    $matrix = generatePlayfairMatrix($key);
    $pairs = prepareText($plaintext);
    
    $ciphertext = '';
    foreach ($pairs as $pair) {
        $ciphertext .= encryptPair($matrix, $pair[0], $pair[1]);
    }
    
    return [
        'ciphertext' => $ciphertext,
        'matrix' => $matrix,
        'pairs' => $pairs
    ];
}

/**
 * Decrypt ciphertext using Playfair cipher
 */
function playfairDecrypt($ciphertext, $key) {
    if (empty($ciphertext) || empty($key)) {
        return ['plaintext' => '', 'matrix' => [], 'pairs' => []];
    }
    
    $matrix = generatePlayfairMatrix($key);
    $ciphertext = strtoupper(preg_replace('/[^A-Z]/i', '', $ciphertext));
    
    $pairs = [];
    for ($i = 0; $i < strlen($ciphertext); $i += 2) {
        if ($i + 1 < strlen($ciphertext)) {
            $pairs[] = $ciphertext[$i] . $ciphertext[$i + 1];
        }
    }
    
    $plaintext = '';
    foreach ($pairs as $pair) {
        $plaintext .= decryptPair($matrix, $pair[0], $pair[1]);
    }
    
    return [
        'plaintext' => $plaintext,
        'matrix' => $matrix,
        'pairs' => $pairs
    ];
}

?>