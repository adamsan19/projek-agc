<?php
if (!defined('IDC')) {
    http_response_code(403);
    exit('Direct access not permitted');
}

/**
 * Generate breadcrumb URL from title
 * Takes a word from the title and creates a URL in format: localhost/f/bokep-(wordslug)
 * Skips word "bokep" to avoid duplicate (localhost/f/bokep-bokep)
 * PRIORITY: 1) Special words at FIRST position (sma, smp, stw, indo, abg)
 *           2) Words >= 4 chars (not special words)
 *           3) Special words at other positions
 *           4) First word from title (skip "bokep")
 * 
 * @param string $title The title to extract the word from
 * @return string The breadcrumb URL
 * 
 * @example
 * breadcrumb_url("Indo Pelajar Smp") returns "localhost/f/bokep-indo"
 * breadcrumb_url("Janda Muda Smp") returns "localhost/f/bokep-janda"
 * breadcrumb_url("Sma Pelajar Terbaru") returns "localhost/f/bokep-sma"
 * breadcrumb_url("Bokep Jepang") returns "localhost/f/bokep-jepang"
 * breadcrumb_url("Ab Cd Ef") returns "localhost/f/bokep-ab"
 */
function breadcrumb_url($title) {
    // Validate input
    if (empty($title) || !is_string($title)) {
        return site_url();
    }
    
    // Trim whitespace
    $title = trim($title);
    if (empty($title)) {
        return site_url();
    }
    
    // Split title into words
    $words = preg_split('/\s+/u', $title);
    
    // Special words
    $specialWords = ['sma', 'smp', 'stw', 'indo', 'abg'];
    
    $selectedWord = '';
    $firstWord = '';
    $firstSpecialWord = '';
    $firstSpecialPosition = -1;
    $firstLongWord = '';
    $firstLongWordPosition = -1;
    
    // First pass: collect all potential words and their positions
    foreach ($words as $index => $word) {
        $word = trim($word);
        if (empty($word)) {
            continue;
        }
        
        $wordLower = mb_strtolower($word, 'UTF-8');
        
        // Skip word "bokep"
        if ($wordLower === 'bokep') {
            continue;
        }
        
        // Track first word (skip "bokep")
        if (empty($firstWord)) {
            $firstWord = $word;
        }
        
        // Track special words
        if (in_array($wordLower, $specialWords)) {
            // First special word
            if ($firstSpecialPosition === -1) {
                $firstSpecialWord = $word;
                $firstSpecialPosition = $index;
            }
        }
        
        // Track first word >= 4 chars (not special word)
        if ($firstLongWordPosition === -1) {
            $wordLength = mb_strlen($word, 'UTF-8');
            if ($wordLength >= 4 && !in_array($wordLower, $specialWords)) {
                $firstLongWord = $word;
                $firstLongWordPosition = $index;
            }
        }
    }
    
    // Apply priorities
    // PRIORITY 1: Special word at FIRST position (index 0)
    if ($firstSpecialPosition === 0) {
        $selectedWord = $firstSpecialWord;
    }
    // PRIORITY 2: First word >= 4 chars (not special word)
    elseif ($firstLongWordPosition !== -1) {
        $selectedWord = $firstLongWord;
    }
    // PRIORITY 3: Special word at other positions (not first)
    elseif ($firstSpecialPosition !== -1) {
        $selectedWord = $firstSpecialWord;
    }
    // PRIORITY 4: First word from title (skip "bokep")
    elseif (!empty($firstWord)) {
        $selectedWord = $firstWord;
    }
    
    // DEFAULT fallback: use "viral" if no valid word found
    if (empty($selectedWord)) {
        $selectedWord = 'viral';
    }
    
    // Convert word to lowercase
    $wordLower = mb_strtolower($selectedWord, 'UTF-8');
    
    // Use existing permalink function from common.php
    $slug = permalink($wordLower);
    
    // Additional safety check for non-ASCII characters
    // If permalink() returns characters outside ASCII range, clean them
    if (preg_match('/[^\x20-\x7E]/', $slug)) {
        $slug = preg_replace('/[^a-z0-9\-]/', '', $slug);
    }
    
    // If slug is empty after processing, use default "viral"
    if (empty($slug)) {
        $slug = 'viral';
    }
    
    // Construct breadcrumb URL: localhost/f/bokep-(slug)
    return site_url() . '/f/bokep-' . $slug;
}

/**
 * Get category name from title (same logic as breadcrumb_url)
 * Returns the word that will be used in breadcrumb URL
 * 
 * @param string $title The title to extract the word from
 * @return string The category name
 * 
 * @example
 * breadcrumb_category_name("Indo Pelajar Smp") returns "indo"
 * breadcrumb_category_name("Janda Muda Smp") returns "janda"
 * breadcrumb_category_name("Sma Pelajar Terbaru") returns "sma"
 * breadcrumb_category_name("Bokep Jepang") returns "jepang"
 * breadcrumb_category_name("Ab Cd Ef") returns "ab"
 */
function breadcrumb_category_name($title) {
    // Validate input
    if (empty($title) || !is_string($title)) {
        return 'viral';
    }
    
    // Trim whitespace
    $title = trim($title);
    if (empty($title)) {
        return 'viral';
    }
    
    // Split title into words
    $words = preg_split('/\s+/u', $title);
    
    // Special words
    $specialWords = ['sma', 'smp', 'stw', 'indo', 'abg'];
    
    $selectedWord = '';
    $firstWord = '';
    $firstSpecialWord = '';
    $firstSpecialPosition = -1;
    $firstLongWord = '';
    $firstLongWordPosition = -1;
    
    // First pass: collect all potential words and their positions
    foreach ($words as $index => $word) {
        $word = trim($word);
        if (empty($word)) {
            continue;
        }
        
        $wordLower = mb_strtolower($word, 'UTF-8');
        
        // Skip word "bokep"
        if ($wordLower === 'bokep') {
            continue;
        }
        
        // Track first word (skip "bokep")
        if (empty($firstWord)) {
            $firstWord = $word;
        }
        
        // Track special words
        if (in_array($wordLower, $specialWords)) {
            // First special word
            if ($firstSpecialPosition === -1) {
                $firstSpecialWord = $word;
                $firstSpecialPosition = $index;
            }
        }
        
        // Track first word >= 4 chars (not special word)
        if ($firstLongWordPosition === -1) {
            $wordLength = mb_strlen($word, 'UTF-8');
            if ($wordLength >= 4 && !in_array($wordLower, $specialWords)) {
                $firstLongWord = $word;
                $firstLongWordPosition = $index;
            }
        }
    }
    
    // Apply priorities
    // PRIORITY 1: Special word at FIRST position (index 0)
    if ($firstSpecialPosition === 0) {
        $selectedWord = $firstSpecialWord;
    }
    // PRIORITY 2: First word >= 4 chars (not special word)
    elseif ($firstLongWordPosition !== -1) {
        $selectedWord = $firstLongWord;
    }
    // PRIORITY 3: Special word at other positions (not first)
    elseif ($firstSpecialPosition !== -1) {
        $selectedWord = $firstSpecialWord;
    }
    // PRIORITY 4: First word from title (skip "bokep")
    elseif (!empty($firstWord)) {
        $selectedWord = $firstWord;
    }
    
    // DEFAULT fallback: use "viral" if no valid word found
    if (empty($selectedWord)) {
        $selectedWord = 'viral';
    }
    
    return $selectedWord;
}