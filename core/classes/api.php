<?php

// Autoload Composer dependencies (Guzzle, Symfony Cache)
require 'vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Promise;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

// --- Cache Initialization ---
//$cache = new FilesystemAdapter('api_zcache', 0, __DIR__ . '/cache/symfony');
 
// --- Background Process Constants ---
const FETCH_LOCK_FILE = __DIR__ . '/fetch.lock';
const FETCH_STATUS_FILE = __DIR__ . '/fetch_status.json';

// --- Rate Limiting Constants ---
const RATE_LIMIT_SEARCH = 1000;
const RATE_LIMIT_LIST = 1000;  
const RATE_LIMIT_INFO = 1000;

// --- Performance Monitoring ---
define('SEARCH_PERFORMANCE_LOG', __DIR__ . '/logs/search_performance.log');

// --- New Constants for Paginated Storage ---
const DATA_PER_PAGE = 200;

// --- Helper Functions ---

if (!function_exists('parseDuration')) {
function parseDuration($duration): int {
    if (is_numeric($duration)) {
        return (int)$duration;
    }
    if (is_string($duration)) {
        $parts = array_reverse(explode(':', $duration));
        $seconds = 0;
        foreach ($parts as $idx => $val) {
            $seconds += (int)$val * pow(60, $idx);
        }
        return $seconds;
    }
    return 0;
}
}

if (!function_exists('formatBytes')) {
function formatBytes(int $bytes, int $decimals = 2): string {
    if ($bytes === 0) return "0 Bytes";
    $k = 1024;
    $sizes = ["Bytes", "KB", "MB", "GB", "TB"];
    $i = floor(log($bytes) / log($k));
    return (float)number_format($bytes / pow($k, $i), $decimals) . " " . $sizes[$i];
}
}

if (!function_exists('formatBytesForProgram')) {
function formatBytesForProgram(int $bytes): int {
    return $bytes;
}
}

if (!function_exists('formatDurationForProgram')) {
function formatDurationForProgram(int $seconds): int {
    return $seconds;
}
}

if (!function_exists('isFetchRunning')) {
function isFetchRunning(): bool {
    if (!file_exists(FETCH_LOCK_FILE)) {
        return false;
    }
    
    $lockTime = filemtime(FETCH_LOCK_FILE);
    $timeout = 1800;
    
    if (time() - $lockTime > $timeout) {
        unlink(FETCH_LOCK_FILE);
        return false;
    }
    
    return true;
}
}

if (!function_exists('startBackgroundFetch')) {
function startBackgroundFetch(): bool {
    if (isFetchRunning()) {
        return false;
    }
    
    file_put_contents(FETCH_LOCK_FILE, time());
    
    $status = [
        'status' => 'running',
        'started_at' => date('Y-m-d H:i:s'),
        'message' => 'Fetch process started'
    ];
    file_put_contents(FETCH_STATUS_FILE, json_encode($status));
    
    $scriptPath = __DIR__ . '/fetch_background.php';
    $outputPath = __DIR__ . '/fetch_output.log';
    
    $command = PHP_BINARY . " " . escapeshellarg($scriptPath) . " >> " . escapeshellarg($outputPath) . " 2>&1 &";
    shell_exec($command);
    
    return true;
}
}

if (!function_exists('getFetchStatus')) {
function getFetchStatus(): array {
    $defaultStatus = [
        'status' => 'idle',
        'started_at' => null,
        'completed_at' => null,
        'message' => 'No fetch process running'
    ];
    
    if (!file_exists(FETCH_STATUS_FILE)) {
        return $defaultStatus;
    }
    
    $status = json_decode(file_get_contents(FETCH_STATUS_FILE), true) ?? $defaultStatus;
    
    if ($status['status'] === 'running' && !isFetchRunning()) {
        $status['status'] = 'completed';
        $status['message'] = 'Process completed (lock file expired)';
    }
    
    return $status;
}
}

if (!function_exists('checkRateLimit')) {
function checkRateLimit($identifier, $maxRequests = 100, $timeWindow = 60): bool {
    $rateLimitDir = getcwd() . '/cache/rate_limits';
    if (!is_dir($rateLimitDir)) {
        mkdir($rateLimitDir, 0777, true);
    }
    
    $limitFile = $rateLimitDir . '/' . md5($identifier) . '.json';
    $now = time();
    
    $data = ['count' => 0, 'reset_time' => $now + $timeWindow];
    
    if (file_exists($limitFile)) {
        $existing = json_decode(file_get_contents($limitFile), true) ?? $data;
        
        if ($now > $existing['reset_time']) {
            $data['count'] = 1;
        } else {
            $data['count'] = $existing['count'] + 1;
            $data['reset_time'] = $existing['reset_time'];
        }
    } else {
        $data['count'] = 1;
    }
    
    file_put_contents($limitFile, json_encode($data));
    
    return $data['count'] <= $maxRequests;
}
}

if (!function_exists('checkEndpointRateLimit')) {
function checkEndpointRateLimit($endpoint, $maxRequests = 1000, $timeWindow = 60): bool {
    $clientId = getClientIdentifier();
    $identifier = $endpoint . '_' . $clientId;
    return checkRateLimit($identifier, $maxRequests, $timeWindow);
}
}

if (!function_exists('getClientIdentifier')) {
function getClientIdentifier(): string {
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
    return $ip . '_' . md5($userAgent);
}
}

if (!function_exists('getRandomUserAgent')) {
function getRandomUserAgent(): string {
    if (class_exists('UA')) {
        try {
            $uaGen = new UA();
            $ua = $uaGen->get_ua();
            if (is_string($ua) && $ua !== '') {
                return $ua;
            }
        } catch (Throwable $e) {
            // Ignore and fall back to static list
        }
    }

    static $uas = [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Safari/605.1.15',
        'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:124.0) Gecko/20100101 Firefox/124.0',
        'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1',
        'Mozilla/5.0 (Linux; Android 13; Pixel 6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0.0.0 Mobile Safari/537.36',
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36 Edg/124.0.0.0',
    ];
    return $uas[array_rand($uas)];
}
}

if (!function_exists('extractKeywords')) {
function extractKeywords(string $text): array {
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
    $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
    return array_unique($words);
}
}

/**
 
 */
if (!function_exists('savePaginatedDataFiles')) {
function savePaginatedDataFiles(array $videos): array {
    $outputDir = getcwd() . '/output';
    
    if (!is_dir($outputDir)) {
        mkdir($outputDir, 0777, true);
    }
    
    $oldFiles = glob("{$outputDir}/data_page_*.json");
    foreach ($oldFiles as $oldFile) {
        unlink($oldFile);
    }
    
    $chunks = array_chunk($videos, DATA_PER_PAGE);
    $pageFiles = [];
    $filecodeTitleMap = [];
    $filecodePageMap = []; // ✅ Mapping filecode -> page
    
    foreach ($chunks as $page => $chunk) {
        $pageNumber = $page + 1;
        $filename = "data_page_{$pageNumber}.json";
        $filepath = "{$outputDir}/{$filename}";
        
        file_put_contents($filepath, json_encode($chunk, JSON_PRETTY_PRINT));
        $pageFiles[$pageNumber] = $filename;
        
        foreach ($chunk as $video) {
            $filecode = $video['filecode'] ?? $video['file_code'] ?? '';
            $title = $video['title'] ?? '';
            
            if (!empty($filecode) && !empty($title)) {
                $filecodeTitleMap[$filecode] = $title;
                $filecodePageMap[$filecode] = $pageNumber;
            }
        }
        
        error_log("Created page file: {$filename} with " . count($chunk) . " videos");
    }
    
    $metadata = [
        'total_pages' => count($chunks),
        'total_videos' => count($videos),
        'videos_per_page' => DATA_PER_PAGE,
        'pages' => $pageFiles,
        'filecode_title_map' => $filecodeTitleMap,
        'filecode_page_map' => $filecodePageMap,
        'last_updated' => date('Y-m-d H:i:s')
    ];
    
    file_put_contents("{$outputDir}/pages_metadata.json", json_encode($metadata, JSON_PRETTY_PRINT));
    
    error_log("✅ Paginated data created: " . count($chunks) . " pages, " . count($videos) . " total videos");
    error_log("✅ Filecode-title map: " . count($filecodeTitleMap) . " mappings");
    error_log("✅ Filecode-page map: " . count($filecodePageMap) . " mappings");
    
    return $pageFiles;
}
}

if (!function_exists('loadDataFromPaginatedFiles')) {
function loadDataFromPaginatedFiles(): array {
    $outputDir = getcwd() . '/output';
    $metadataFile = "{$outputDir}/pages_metadata.json";
    
    if (!file_exists($metadataFile)) {
        return ['videos' => [], 'total_pages' => 0, 'total_videos' => 0, 'filecode_title_map' => []];
    }
    
    $metadata = json_decode(file_get_contents($metadataFile), true) ?? [];
    return [
        'videos' => [],
        'total_pages' => $metadata['total_pages'] ?? 0,
        'total_videos' => $metadata['total_videos'] ?? 0,
        'filecode_title_map' => $metadata['filecode_title_map'] ?? [],
        'filecode_page_map' => $metadata['filecode_page_map'] ?? [],
        'pages_metadata' => $metadata
    ];
}
}

if (!function_exists('loadVideosFromPage')) {
function loadVideosFromPage(int $page): array {
    $outputDir = getcwd() . '/output';
    $filename = "data_page_{$page}.json";
    $filepath = "{$outputDir}/{$filename}";
    
    if (file_exists($filepath)) {
        return json_decode(file_get_contents($filepath), true) ?? [];
    }
    
    return [];
}
}

if (!function_exists('loadAllVideosFromPaginatedFiles')) {
function loadAllVideosFromPaginatedFiles(): array {
    $outputDir = getcwd() . '/output';
    $metadataFile = "{$outputDir}/pages_metadata.json";
    
    if (!file_exists($metadataFile)) {
        return [];
    }
    
    $metadata = json_decode(file_get_contents($metadataFile), true) ?? [];
    $totalPages = $metadata['total_pages'] ?? 0;
    
    if ($totalPages === 0) {
        return [];
    }
    
    $allVideos = [];
    for ($page = 1; $page <= $totalPages; $page++) {
        $videos = loadVideosFromPage($page);
        $allVideos = array_merge($allVideos, $videos);
    }
    
    return $allVideos;
}
}

if (!function_exists('getFilecodeTitleMap')) {
function getFilecodeTitleMap(): array {
    $outputDir = getcwd() . '/output';
    $metadataFile = "{$outputDir}/pages_metadata.json";
    
    if (!file_exists($metadataFile)) {
        return [];
    }
    
    $metadata = json_decode(file_get_contents($metadataFile), true) ?? [];
    return $metadata['filecode_title_map'] ?? [];
}
}

if (!function_exists('getFilecodePageMap')) {
function getFilecodePageMap(): array {
    $outputDir = getcwd() . '/output';
    $metadataFile = "{$outputDir}/pages_metadata.json";
    
    if (!file_exists($metadataFile)) {
        return [];
    }
    
    $metadata = json_decode(file_get_contents($metadataFile), true) ?? [];
    return $metadata['filecode_page_map'] ?? [];
}
}

if (!function_exists('getTitleByFilecode')) {
function getTitleByFilecode(string $filecode): string {
    $map = getFilecodeTitleMap();
    return $map[$filecode] ?? '';
}
}

/**
 * ✅ OPTIMIZED: Cari file_code berdasarkan keyword di title (tanpa search index)
 */
if (!function_exists('searchFilecodesByTitleOptimized')) {
    function searchFilecodesByTitleOptimized(string $query): array {
        $map = getFilecodeTitleMap();
        
        if (empty($query)) {
            return [];
        }
        
        // A. PREPROCESSING QUERY
        $originalQuery = trim($query);
        $searchTerms = array_map('strtolower', array_filter(explode(' ', $originalQuery)));
        
        if (empty($searchTerms)) {
            return [];
        }
        
        // B. GENERATE MULTIPLE QUERIES
        $queries = generateSearchQueries($searchTerms);
        
        // C. SEARCH WITH RELEVANCE SCORING
        $results = [];
        
        foreach ($map as $filecode => $title) {
            $lowerTitle = strtolower($title);
            $relevanceScore = calculateRelevanceScore($lowerTitle, $queries, $searchTerms);
            
            if ($relevanceScore > 0) {
                $results[] = [
                    'filecode' => $filecode,
                    'title' => $title,
                    'relevance' => $relevanceScore,
                    'matched_terms' => getMatchedTerms($lowerTitle, $searchTerms)
                ];
            }
        }
        
        // D. SORT BY RELEVANCE
        usort($results, function($a, $b) {
            // 1. Priority: Relevance score (descending)
            $relevanceComparison = $b['relevance'] - $a['relevance'];
            if ($relevanceComparison !== 0) return $relevanceComparison;
            
            // 2. Secondary: Title length (shorter titles first - usually more relevant)
            return strlen($a['title']) - strlen($b['title']);
        });
        
        return $results;
    }
    }
    
    if (!function_exists('generateSearchQueries')) {
    function generateSearchQueries(array $searchTerms): array {
        $queries = [];
        
        // Add individual words
        foreach ($searchTerms as $word) {
            $queries[] = $word;
        }
        
        // Add word combinations (progressive)
        $termCount = count($searchTerms);
        for ($i = 2; $i <= min(4, $termCount); $i++) {
            $combined = implode(' ', array_slice($searchTerms, 0, $i));
            $queries[] = $combined;
        }
        
        // Add all terms combined
        if ($termCount > 1) {
            $queries[] = implode(' ', $searchTerms);
        }
        
        return array_unique($queries);
    }
    }
    
    if (!function_exists('calculateRelevanceScore')) {
    function calculateRelevanceScore(string $lowerTitle, array $queries, array $individualTerms): int {
        $relevance = 0;
        
        foreach ($queries as $query) {
            if (strpos($lowerTitle, $query) !== false) {
                // Calculate weight based on query length (more words = higher weight)
                $wordCount = substr_count($query, ' ') + 1;
                $weight = $wordCount * 2; // Base weight
                
                // Bonus for exact matches at beginning of title
                if (strpos($lowerTitle, $query) === 0) {
                    $weight += 3;
                }
                
                // Bonus for complete phrase match
                if ($wordCount > 1) {
                    $weight += 2;
                }
                
                $relevance += $weight;
            }
        }
        
        // Additional scoring for individual term density
        $individualMatches = 0;
        foreach ($individualTerms as $term) {
            if (strpos($lowerTitle, $term) !== false) {
                $individualMatches++;
            }
        }
        
        // Bonus for matching all individual terms
        if ($individualMatches === count($individualTerms)) {
            $relevance += 5;
        }
        
        // Bonus for high term density
        $termDensity = $individualMatches / count($individualTerms);
        if ($termDensity >= 0.8) {
            $relevance += 3;
        }
        
        return $relevance;
    }
    }
    
    if (!function_exists('getMatchedTerms')) {
    function getMatchedTerms(string $lowerTitle, array $searchTerms): array {
        $matched = [];
        foreach ($searchTerms as $term) {
            if (strpos($lowerTitle, $term) !== false) {
                $matched[] = $term;
            }
        }
        return $matched;
    }
    }
    
    if (!function_exists('searchFilecodesByTitle')) {
    function searchFilecodesByTitle(string $keyword): array {
        // Use optimized version with backward compatibility
        $optimizedResults = searchFilecodesByTitleOptimized($keyword);
        
        // Convert to old format for compatibility
        $legacyResults = [];
        foreach ($optimizedResults as $result) {
            $legacyResults[$result['filecode']] = $result['title'];
        }
        
        return $legacyResults;
    }
    }

if (!function_exists('preserveExistingTitles')) {
function preserveExistingTitles(array $newVideos, array $existingVideos): array {
    $existingTitlesMap = [];
    
    // Buat mapping filecode -> title dari data existing
    foreach ($existingVideos as $video) {
        $filecode = $video['filecode'] ?? $video['file_code'] ?? '';
        $title = $video['title'] ?? '';
        if (!empty($filecode) && !empty($title)) {
            $existingTitlesMap[$filecode] = $title;
        }
    }
    
    // Preservasi title untuk video yang sudah ada
    foreach ($newVideos as &$video) {
        $filecode = $video['filecode'] ?? $video['file_code'] ?? '';
        if (!empty($filecode) && isset($existingTitlesMap[$filecode])) {
            $video['title'] = $existingTitlesMap[$filecode];
            $video['title_preserved'] = true; // Flag untuk tracking
        } else {
            $video['title_preserved'] = false;
        }
    }
    
    return $newVideos;
}
}

if (!function_exists('parseUploadedTimestamp')) {
function parseUploadedTimestamp($uploaded): int {
    if (empty($uploaded)) {
        return 0;
    }
    
    // Jika sudah berupa timestamp (numeric)
    if (is_numeric($uploaded)) {
        return (int)$uploaded;
    }
    
    // Jika berupa string date, convert ke timestamp
    $timestamp = strtotime($uploaded);
    return $timestamp !== false ? $timestamp : 0;
}
}

if (!function_exists('mergeVideoData')) {
function mergeVideoData(array $newVideos, array $existingVideos): array {
    $mergedVideos = [];
    $existingMap = [];
    
    // Buat mapping filecode -> video dari data existing
    foreach ($existingVideos as $video) {
        $filecode = $video['filecode'] ?? $video['file_code'] ?? '';
        if (!empty($filecode)) {
            $existingMap[$filecode] = $video;
        }
    }
    
    // Proses merge
    foreach ($newVideos as $newVideo) {
        $filecode = $newVideo['filecode'] ?? $newVideo['file_code'] ?? '';
        
        if (!empty($filecode) && isset($existingMap[$filecode])) {
            // Video sudah ada - preservasi title dan metadata penting
            $existingVideo = $existingMap[$filecode];
            
            $mergedVideo = $newVideo; // Data baru sebagai base
            
            // Preservasi field-field penting dari data existing
            $mergedVideo['title'] = $existingVideo['title'] ?? $newVideo['title'];
            $mergedVideo['deskripsi'] = $existingVideo['deskripsi'] ?? $newVideo['deskripsi'];
            $mergedVideo['tag'] = $existingVideo['tag'] ?? $newVideo['tag'];
            $mergedVideo['custom_fields'] = $existingVideo['custom_fields'] ?? $newVideo['custom_fields'] ?? [];
            $mergedVideo['title_preserved'] = true;
            
            $mergedVideos[] = $mergedVideo;
            
            // Hapus dari existing map agar tidak duplikat
            unset($existingMap[$filecode]);
        } else {
            // Video baru - tambahkan dengan flag
            $newVideo['title_preserved'] = false;
            $mergedVideos[] = $newVideo;
        }
    }
    
    // Tambahkan video existing yang tidak ada di data baru (jika ingin mempertahankan semua data lama)
    foreach ($existingMap as $existingVideo) {
        $existingVideo['title_preserved'] = true;
        $existingVideo['data_preserved'] = true; // Flag bahwa data ini dipertahankan
        $mergedVideos[] = $existingVideo;
    }
    
    return $mergedVideos;
}
}

if (!function_exists('loadLatestData')) {
function loadLatestData(): array {
    return loadDataFromPaginatedFiles();
}
}

if (!function_exists('getAllVideosFromIndex')) {
function getAllVideosFromIndex(): array {
    $map = getFilecodeTitleMap();
    $allVideos = [];
    foreach ($map as $filecode => $title) {
        $allVideos[] = ['filecode' => $filecode, 'title' => $title];
    }
    return $allVideos;
}
}

/**
 * ✅ OPTIMIZED: Load video by filecode DENGAN mapping page (SANGAT CEPAT)
 */
if (!function_exists('loadVideoByFilecodeOptimized')) {
function loadVideoByFilecodeOptimized(string $filecode): ?array {
    static $filecodePageMap = null;
    
    if ($filecodePageMap === null) {
        $filecodePageMap = getFilecodePageMap();
    }
    
    if (isset($filecodePageMap[$filecode])) {
        $page = $filecodePageMap[$filecode];
        $videos = loadVideosFromPage($page);
        
        foreach ($videos as $video) {
            if (($video['filecode'] ?? '') === $filecode || ($video['file_code'] ?? '') === $filecode) {
                return $video;
            }
        }
    }
    
    return null;
}
}
class DataNormalizer {
    public function cleanAndFormatTitle(string $title): string {
        if (empty($title)) return '';
    
        // Tambahkan spasi sebelum huruf kapital yang mengikuti huruf kecil (contoh: VideoTerbaru -> Video Terbaru)
        $cleanTitle = preg_replace('/([a-z])([A-Z])/', '$1 $2', $title);
        
        // First, replace special characters within words with spaces
        $cleanTitle = preg_replace('/([a-zA-Z])[^a-zA-Z\s]+([a-zA-Z])/', '$1 $2', $cleanTitle);
        
        // Then remove all remaining special characters (not letters or spaces)
        $cleanTitle = preg_replace('/[^a-zA-Z\s]/', '', $cleanTitle);
        
        // Replace multiple spaces with single space and trim
        $cleanTitle = trim(preg_replace('/\s+/', ' ', $cleanTitle));
        
        // Convert to proper case
        $cleanTitle = ucwords(strtolower($cleanTitle));
    
        $words = explode(' ', $cleanTitle);
        if (count($words) < 12) {
            $additionalWords = $this->getRandomWords(5);
            $cleanTitle .= ' ' . implode(' ', $additionalWords);
        }
    
        $words = explode(' ', $cleanTitle);
        $uniqueWords = array_unique($words);
        $limitedWords = array_slice($uniqueWords, 0, 12);
    
        return trim(implode(' ', $limitedWords));
    }

    public function getRandomWords(int $count): array {
        $words = [
            'Sotwe', 'Bokep Dood', 'Twitter', 'Bokepsatset', 'Simontok', 'Video Viral',
            'Video Viral', 'Bokep Video', 'Simintok', 'Xpanas', 'Full Album', 'Doodstream',
            'Bokepsin', 'Simontok', 'Bokep', 'Asupan', 'Bokepsin', 'Bebasindo',
            'Pekoblive', 'Terabox', 'Streaming', 'Viral', 'Indo', 'Tiktok', 'Telegram',
            'Doods Pro', 'Telegram', 'Ful Album', 'Viral', 'Videos',
            'Poophd', 'Bochiel', 'Link Web', 'Folder',
            'Cilbo', 'Live', 'Tele', 'Terupdate', 'Links', 'Lokal', 'Dodstream',
            'Pemersatu', 'Update', 'Dood', 'Doostream', 'Website',
            'Downloader', 'Lulustream', 'Doodsflix', 'Yakwad', 'Doodflix',
            'Tobrut', 'Lagi Viral', 'Doodstreem', 'Jilbab',
            'Asupan Viral',
          
            'Pejuang Lendir', 'Popstream', 'Staklam', 'Bokepind', 'Video Bokep',
            'Bokep31', 'Video Indo', 'Video Colmek', 'Toketbagus',  
            'Video Sma', 'Doods Pro',  'Ngentot',  
            'Indonesia', 'Bokepin', 'Dood Tele', 'Cantik Tobrut', 'Memeksiana'
        ];
        shuffle($words);
        return array_slice($words, 0, $count);
    }
    public function convertDurationToSize($duration, int $bitrate = 1): float {
        $seconds = parseDuration($duration);
        return $seconds * $bitrate * 1024 * 1024;
    }

    public function generateLongSeoDescription(string $rawTitle, array $titleTags): string {
        $cleanTitle = $this->cleanAndFormatTitle($rawTitle);
        $descWords = array_unique(array_merge($titleTags, $this->getRandomWords(5)));
        shuffle($descWords);
        $descWords = array_slice($descWords, 0, 12);
        return ucfirst(implode(' ', $descWords)) . ". {$cleanTitle} - Streaming dan download video terbaru.";
    }
    
    public function updateExistingData(array $existingData, array $newData): array {
        $preservedTitle = $existingData['title'];
        $updatedData = $newData;
        $updatedData['title'] = $preservedTitle;

        if (!empty($existingData['deskripsi'])) $updatedData['deskripsi'] = $existingData['deskripsi'];
        if (!empty($existingData['tag'])) $updatedData['tag'] = $existingData['tag'];
        
        return $updatedData;
    }

    public function normalizeData(array $apiData, string $apiSource = 'doodstream', array $existingVideos = []): array {
        $normalizedItems = [];
        if (!isset($apiData['result']['files']) || !is_array($apiData['result']['files'])) {
            return $normalizedItems;
        }
        
        $existingVideosMap = [];
        foreach($existingVideos as $video) {
            if(!empty($video['filecode'])) {
                $existingVideosMap[$video['filecode']] = $video;
            }
        }

        foreach ($apiData['result']['files'] as $item) {
            $filecode = $item['file_code'] ?? $item['filecode'] ?? '';
            $rawTitle = $item['title'] ?? $item['file_title'] ?? '';
            $cleanTitle = $this->cleanAndFormatTitle($rawTitle);
            $titleWords = preg_split('/\s+/', preg_replace('/[^a-zA-Z0-9\s]/', '', $cleanTitle), -1, PREG_SPLIT_NO_EMPTY);
            $titleTags = array_values(array_unique($titleWords));
            $description = $this->generateLongSeoDescription($rawTitle, $titleTags);

            $normalizedItem = [];
            if ($apiSource === 'doodstream') {
                $duration = parseDuration($item['length'] ?? 0);
                $normalizedItem = [
                    'protected_embed' => $item['protected_embed'] ?? "https://dodl.pages.dev/{$filecode}",
                    'size' => isset($item['size']) ? formatBytes($item['size']) : formatBytes($this->convertDurationToSize($duration)),
                    'length' => gmdate("H:i:s", $duration),
                    'protected_dl' => $item['download_url'] ?? "https://doodstream.com/d/{$filecode}",
                    'views' => $item['views'] ?? 0,
                    'single_img' => $item['single_img'] ?? $item['splash_img'] ?? '',
                    'title' => $cleanTitle,
                    'status' => $item['status'] ?? '200',
                    'uploaded' => $item['uploaded'] ?? '',
                    'last_view' => $item['last_view'] ?? '',
                    'splash_img' => $item['splash_img'] ?? $item['single_img'] ?? '',
                    'filecode' => $filecode,
                    'file_code' => $filecode,
                    'canplay' => $item['canplay'] ?? true,
                    'api_source' => $item['api_source'] ?? 'doodstream',
                    'tag' => $titleTags,
                    'deskripsi' => $description
                ];
            } else {
                $duration = parseDuration($item['length'] ?? $item['file_length'] ?? 0);
                $normalizedItem = [
                    'protected_embed' => $item['protected_embed'] ?? "https://luvluv.pages.dev/{$filecode}",
                    'size' => isset($item['file_size']) ? formatBytes($item['file_size']) : formatBytes($this->convertDurationToSize($duration)),
                    'length' => gmdate("H:i:s", $duration),
                    'protected_dl' => $item['download_url'] ?? "https://lulustream.com/d/{$filecode}",
                    'views' => $item['views'] ?? $item['file_views'] ?? 0,
                    'single_img' => $item['player_img'] ?? "https://img.lulucdn.com/{$filecode}_t.jpg",
                    'splash_img' => $item['player_img'] ?? "https://img.lulucdn.com/{$filecode}_xt.jpg",
                    'title' => $cleanTitle,
                    'status' => $item['status'] ?? '200',
                    'uploaded' => $item['uploaded'] ?? '',
                    'last_view' => $item['last_view'] ?? '',
                    'filecode' => $filecode,
                    'file_code' => $filecode,
                    'canplay' => $item['canplay'] ?? true,
                    'api_source' => $item['api_source'] ?? 'lulustream',
                    'tag' => $titleTags,
                    'deskripsi' => $description
                ];
            }
            
            if (isset($existingVideosMap[$filecode])) {
                $normalizedItems[] = $this->updateExistingData($existingVideosMap[$filecode], $normalizedItem);
            } else {
                $normalizedItems[] = $normalizedItem;
            }
        }
        return $normalizedItems;
    }
}

if (!function_exists('updateFetchStatus')) {
function updateFetchStatus(array $status): void {
    file_put_contents(FETCH_STATUS_FILE, json_encode($status, JSON_PRETTY_PRINT));
}
}

// --- API Fetching Logic ---
if (!defined('MAX_REQUESTS_PER_SECOND')) {
    define('MAX_REQUESTS_PER_SECOND', 10);
}
if (!defined('REQUEST_INTERVAL_MS')) {
    define('REQUEST_INTERVAL_MS', 1000);
}

if (!function_exists('fetchAllPages')) {
function fetchAllPages(array $config, string $apiSource, array $existingVideos = []): array {
    $allData = [];
    $page = 1;
    $client = new Client();
    $normalizer = new DataNormalizer();

    while (true) {
        $promises = [];
        for ($i = 0; $i < MAX_REQUESTS_PER_SECOND; $i++) {
            $currentPage = $page + $i;
            $url = "{$config['api_url']}?key={$config['api_key']}&per_page={$config['per_page']}&page={$currentPage}";
            $userAgent = getRandomUserAgent();
            $promises[$currentPage] = $client->getAsync($url, [
                'headers' => ['User-Agent' => $userAgent],
                'timeout' => 15
            ]);
        }

        $results = Promise\Utils::settle($promises)->wait();
        $hasDataInBatch = false;
        
        foreach ($results as $currentPage => $result) {
            if ($result['state'] === 'fulfilled') {
                $response = $result['value'];
                $pageDataRaw = json_decode($response->getBody()->getContents(), true);
                
                if ($pageDataRaw && ($pageDataRaw['status'] ?? 0) === 200) {
                    $normalizedData = $normalizer->normalizeData($pageDataRaw, $apiSource, $existingVideos);
                    if (!empty($normalizedData)) {
                        $allData = array_merge($allData, $normalizedData);
                        $hasDataInBatch = true;
                    }
                }
            }
        }

        if (!$hasDataInBatch) {
            break;
        }

        $page += MAX_REQUESTS_PER_SECOND;
        usleep(REQUEST_INTERVAL_MS * 1000);
    }
    
    // ✅ APPLY TITLE PRESERVATION sebelum return
    return preserveExistingTitles($allData, $existingVideos);
}
}

$configs = [
    'doodstream' => [
        'api_url' => 'https://doodapi.com/api/file/list',
        'api_key' => '112623ifbcbltzajwjrpjx',
        'per_page' => 200,
        'request_delay' => 1,
        'max_retries' => 3
    ],
    'lulustream' => [
        'api_url' => 'https://api.lulustream.com/api/file/list',
        'api_key' => '37943j35tc5i1bg3gje5y',
        'per_page' => 500,
        'request_delay' => 1,
        'max_retries' => 3
    ]
];

// --- API Endpoints dengan Rate Limiting ---
if (isset($_GET['action']) && $_GET['action'] === 'get_title') {
    if (!checkEndpointRateLimit('get_title', 500)) {
        http_response_code(429);
        echo json_encode(['error' => 'Rate limit exceeded']);
        exit;
    }
    
    $filecode = $_GET['filecode'] ?? '';
    if (!empty($filecode)) {
        $title = getTitleByFilecode($filecode);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => !empty($title),
            'filecode' => $filecode,
            'title' => $title
        ]);
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'search_titles') {
    if (!checkEndpointRateLimit('search_titles', 300)) {
        http_response_code(429);
        echo json_encode(['error' => 'Rate limit exceeded']);
        exit;
    }
    
    $keyword = $_GET['keyword'] ?? '';
    if (!empty($keyword)) {
        $results = searchFilecodesByTitle($keyword);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'keyword' => $keyword,
            'results' => $results,
            'total' => count($results)
        ]);
        exit;
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'get_preservation_stats') {
    if (!checkEndpointRateLimit('get_preservation_stats', 100)) {
        http_response_code(429);
        echo json_encode(['error' => 'Rate limit exceeded']);
        exit;
    }
    
    $videos = loadAllVideosFromPaginatedFiles();
    
    $preservedCount = 0;
    $newCount = 0;
    
    foreach ($videos as $video) {
        if ($video['title_preserved'] ?? false) {
            $preservedCount++;
        } else {
            $newCount++;
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'total_videos' => count($videos),
        'preserved_titles' => $preservedCount,
        'new_videos' => $newCount,
        'preservation_rate' => count($videos) > 0 ? round(($preservedCount / count($videos)) * 100, 2) : 0
    ]);
    exit;
}

if (!function_exists('generateVideoServicePrecache')) {
function generateVideoServicePrecache(array $videos = []): void {
    if (!class_exists('VideoService')) {
        require_once 'VideoService.php';
    }
    
    try {
        $videoService = new VideoService();
        $videoService->refreshCache($videos);
        error_log("✅ VideoService precache completed successfully");
    } catch (Exception $e) {
        error_log("❌ VideoService precache failed: " . $e->getMessage());
    }
}
}

if (!function_exists('getDataVersion')) {
function getDataVersion(): string {
    $metadataFile = getcwd() . '/output/pages_metadata.json';
    if (file_exists($metadataFile)) {
        $mtime = filemtime($metadataFile);
        return 'v_' . date('Ymd_His', $mtime);
    }
    return 'v_default';
}
}

if (!function_exists('cleanupExpiredCache')) {
function cleanupExpiredCache(): void {
    $cacheDir = getcwd() . '/cache/symfony/video_services';
    if (!is_dir($cacheDir)) {
        return;
    }
    
	// Helper: recursively delete directories/files
	if (!function_exists('deletePathRecursive')) {
		function deletePathRecursive(string $path): void {
			if (is_dir($path)) {
				$items = scandir($path);
				if ($items !== false) {
					foreach ($items as $item) {
						if ($item === '.' || $item === '..') {
							continue;
						}
						$child = $path . DIRECTORY_SEPARATOR . $item;
						deletePathRecursive($child);
					}
				}
				@rmdir($path);
			} else {
				@unlink($path);
			}
		}
	}
	
    $files = glob($cacheDir . '/*');
    $currentVersion = getDataVersion();
    
    foreach ($files as $file) {
        $filename = basename($file);
        if (strpos($filename, $currentVersion) === false) {
			if (is_dir($file)) {
				deletePathRecursive($file);
			} else {
				@unlink($file);
			}
            error_log("🧹 Cleaned expired cache: " . $filename);
        }
    }
}
}

// Initial data load
$loadedData = loadLatestData();
$allVideos = $loadedData['videos'];