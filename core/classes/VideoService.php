<?php

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * VideoService - Menggunakan pages_metadata.json sebagai sumber data utama
 * 
 * Class ini menggunakan pages_metadata.json untuk:
 * - Mapping filecode ke title (filecode_title_map)
 * - Mapping filecode ke page number (filecode_page_map)
 * - Informasi pagination (total_pages, total_videos, videos_per_page)
 * - Daftar file per page (pages)
 * 
 * Semua data dimuat sekali dan di-cache di memory untuk performa optimal
 */
class VideoService {
    private $cache;
    private $filecodeTitleMap;
    private $filecodePageMap;
    private $cacheVersion;
    private $metadata;
    private $outputDir;
    private $metadataFile;
    private $searchIndex;
    private $titleWordIndex;
    
    // ========== STATIC CACHE UNTUK METADATA ==========
    private static $metadataStaticCache = null;
    private static $metadataStaticCacheKey = null;
    private static $metadataLastModified = null;
    private static $metadataChecksum = null;
    
    public function __construct() {
        $this->cache = new FilesystemAdapter('video_services', 0, __DIR__ . '/cache/symfony');
        $this->filecodeTitleMap = null;
        $this->filecodePageMap = null;
        $this->metadata = null;
        $this->searchIndex = null;
        $this->titleWordIndex = null;
        $this->outputDir = getcwd() . '/output';
        $this->metadataFile = "{$this->outputDir}/pages_metadata.json";
        $this->cacheVersion = $this->getCurrentCacheVersion();
    }
    
    /**
     * Load metadata dari pages_metadata.json dengan static cache
     * - File hanya dibaca sekali per request
     * - Static cache berlaku untuk semua instance VideoService
     * - Cache di-invalidate berdasarkan file modification time dan checksum
     */
    private function loadMetadata(): array {
        // Cek apakah file metadata ada
        if (!file_exists($this->metadataFile)) {
            // Return default metadata jika file tidak ada
            $defaultMetadata = [
                'total_pages' => 0,
                'total_videos' => 0,
                'videos_per_page' => 200,
                'pages' => [],
                'filecode_title_map' => [],
                'filecode_page_map' => []
            ];
            
            // Simpan default ke static cache agar tidak cek file berulang
            self::$metadataStaticCache = $defaultMetadata;
            self::$metadataStaticCacheKey = $this->metadataFile;
            self::$metadataLastModified = null;
            self::$metadataChecksum = null;
            
            return $defaultMetadata;
        }
        
        // Dapatkan informasi file saat ini
        $currentMtime = filemtime($this->metadataFile);
        $currentSize = filesize($this->metadataFile);
        
        // Baca konten file untuk checksum (hanya jika diperlukan)
        $needsReload = true;
        
        // Cek apakah static cache masih valid
        if (self::$metadataStaticCache !== null && 
            self::$metadataStaticCacheKey === $this->metadataFile &&
            self::$metadataLastModified !== null &&
            self::$metadataLastModified === $currentMtime) {
            
            // File belum dimodifikasi, gunakan cache yang ada
            return self::$metadataStaticCache;
        }
        
        // Load dari file (hanya sekali per request jika file berubah)
        try {
            $fileContent = file_get_contents($this->metadataFile);
            if ($fileContent === false) {
                throw new Exception("Failed to read metadata file");
            }
            
            $metadata = json_decode($fileContent, true);
            if ($metadata === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception("Invalid JSON in metadata file: " . json_last_error_msg());
            }
            
            $metadata = $metadata ?? [];
            
            // Ensure all required keys exist dengan nilai default
            $defaultMetadata = [
                'total_pages' => 0,
                'total_videos' => 0,
                'videos_per_page' => 200,
                'pages' => [],
                'filecode_title_map' => [],
                'filecode_page_map' => []
            ];
            
            $metadata = array_merge($defaultMetadata, $metadata);
            
            // Hitung checksum untuk validasi data
            $currentChecksum = md5($fileContent);
            
            // Simpan ke static cache
            self::$metadataStaticCache = $metadata;
            self::$metadataStaticCacheKey = $this->metadataFile;
            self::$metadataLastModified = $currentMtime;
            self::$metadataChecksum = $currentChecksum;
            
            // Log untuk debugging (hanya di development)
            if (defined('ENVIRONMENT') && ENVIRONMENT === 'development') {
                error_log("Metadata loaded from file: {$this->metadataFile}, " .
                         "size: {$currentSize} bytes, " .
                         "checksum: " . substr($currentChecksum, 0, 8));
            }
            
            return $metadata;
            
        } catch (Exception $e) {
            // Log error dan return default metadata
            error_log("Error loading metadata: " . $e->getMessage());
            
            $defaultMetadata = [
                'total_pages' => 0,
                'total_videos' => 0,
                'videos_per_page' => 200,
                'pages' => [],
                'filecode_title_map' => [],
                'filecode_page_map' => []
            ];
            
            // Cache default untuk menghindari error berulang
            self::$metadataStaticCache = $defaultMetadata;
            self::$metadataStaticCacheKey = $this->metadataFile;
            self::$metadataLastModified = $currentMtime;
            self::$metadataChecksum = 'error_' . time();
            
            return $defaultMetadata;
        }
    }
    
    /**
     * Clear static cache - berguna untuk testing atau manual refresh
     */
    public static function clearStaticCache(): void {
        self::$metadataStaticCache = null;
        self::$metadataStaticCacheKey = null;
        self::$metadataLastModified = null;
        self::$metadataChecksum = null;
    }
    
    /**
     * Get current cache version dengan checksum content untuk invalidation yang lebih akurat
     */
    private function getCurrentCacheVersion(): string {
        if (!file_exists($this->metadataFile)) {
            return 'v_default_' . time();
        }
        
        try {
            // Gunakan kombinasi modification time dan checksum konten
            $content = @file_get_contents($this->metadataFile);
            if ($content === false) {
                return 'v_error_' . time();
            }
            
            $checksum = md5($content);
            $mtime = filemtime($this->metadataFile);
            
            // Format: v_YYYYMMDD_checksum8digit
            return 'v_' . date('Ymd', $mtime) . '_' . substr($checksum, 0, 8);
        } catch (Exception $e) {
            // Fallback ke modification time saja
            $mtime = @filemtime($this->metadataFile);
            if ($mtime === false) {
                $mtime = time();
            }
            return 'v_' . date('Ymd_His', $mtime);
        }
    }
    
    /**
     * Get filecode to title mapping dari static cache metadata
     */
    private function getFilecodeTitleMap(): array {
        if ($this->filecodeTitleMap === null) {
            $metadata = $this->loadMetadata();
            $this->filecodeTitleMap = $metadata['filecode_title_map'] ?? [];
        }
        return $this->filecodeTitleMap;
    }
    
    /**
     * Get filecode to page mapping dari static cache metadata
     */
    private function getFilecodePageMap(): array {
        if ($this->filecodePageMap === null) {
            $metadata = $this->loadMetadata();
            $this->filecodePageMap = $metadata['filecode_page_map'] ?? [];
        }
        return $this->filecodePageMap;
    }
    
    /**
     * Get total pages count dari static cache metadata
     */
    private function getTotalPagesCount(): int {
        $metadata = $this->loadMetadata();
        return (int)($metadata['total_pages'] ?? 0);
    }
    
    /**
     * Get total videos count dari static cache metadata
     */
    private function getTotalCountFromIndex(): int {
        $metadata = $this->loadMetadata();
        return (int)($metadata['total_videos'] ?? 0);
    }
    
    /**
     * Load video by filecode menggunakan mapping dari static cache
     */
    private function loadVideoByFilecodeOptimized(string $filecode): ?array {
        $filecodePageMap = $this->getFilecodePageMap();
        
        if (!isset($filecodePageMap[$filecode])) {
            return null;
        }
        
        $page = $filecodePageMap[$filecode];
        $videos = $this->loadVideosFromPage($page);
        
        foreach ($videos as $video) {
            $videoFilecode = $video['filecode'] ?? $video['file_code'] ?? '';
            if ($videoFilecode === $filecode) {
                return $video;
            }
        }
        
        return null;
    }
    
    /**
     * Load videos dari page tertentu
     * File page JSON masih dibaca dari disk, tapi metadata sudah di-cache
     */
    private function loadVideosFromPage(int $page): array {
        $metadata = $this->loadMetadata();
        $pages = $metadata['pages'] ?? [];
        
        if (!isset($pages[(string)$page])) {
            return [];
        }
        
        $filename = $pages[(string)$page];
        $filepath = "{$this->outputDir}/{$filename}";
        
        if (!file_exists($filepath)) {
            return [];
        }
        
        $videos = json_decode(file_get_contents($filepath), true) ?? [];
        return is_array($videos) ? $videos : [];
    }

    /**
     * Build search index for faster searching
     */
    private function buildSearchIndex(): void {
        if ($this->searchIndex !== null) return;
        
        $cacheKey = $this->getCacheKey("search_index");
        $this->searchIndex = $this->cache->get($cacheKey, function (ItemInterface $item) {
            // Cache lebih agresif untuk search index: 6 jam (data jarang berubah)
            $item->expiresAfter(21600);
            
            $metadata = $this->loadMetadata();
            $filecodeTitleMap = $metadata['filecode_title_map'] ?? [];
            
            $index = [
                'filecode_to_title' => $filecodeTitleMap,
                'title_to_filecodes' => [],
                'word_index' => []
            ];
            
            // Build reverse index: title -> filecodes
            foreach ($filecodeTitleMap as $filecode => $title) {
                $normalizedTitle = $this->normalizeText($title);
                $index['title_to_filecodes'][$normalizedTitle] = $filecode;
                
                // Build word index for partial matching
                $words = $this->extractWords($normalizedTitle);
                foreach ($words as $word) {
                    if (strlen($word) > 2) { // Only index words longer than 2 chars
                        if (!isset($index['word_index'][$word])) {
                            $index['word_index'][$word] = [];
                        }
                        $index['word_index'][$word][] = $filecode;
                    }
                }
            }
            
            return $index;
        });
    }
    
    /**
     * Normalize text for search
     */
    private function normalizeText(string $text): string {
        $text = mb_strtolower($text, 'UTF-8');
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text); // Remove punctuation
        $text = preg_replace('/\s+/', ' ', $text); // Normalize whitespace
        return trim($text);
    }
    
    /**
     * Extract words from text
     */
    private function extractWords(string $text): array {
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        return array_filter($words, function($word) {
            return strlen($word) > 1; // Only words longer than 1 character
        });
    }
    
    /**
     * Find matching filecodes using search index
     */
    private function findMatchingFilecodes(array $queryWords, string $fullQuery): array {
        $exactMatches = [];
        $caseInsensitiveExactMatches = [];
        $partialMatches = [];
        $wordMatches = [];
        $seenFilecodes = [];
        
        // Exact match check (case-sensitive)
        if (isset($this->searchIndex['title_to_filecodes'][$fullQuery])) {
            $filecode = $this->searchIndex['title_to_filecodes'][$fullQuery];
            $exactMatches[] = $filecode;
            $seenFilecodes[$filecode] = true;
        }
        
        // Case-insensitive exact match check
        $normalizedQuery = mb_strtolower($fullQuery, 'UTF-8');
        foreach ($this->searchIndex['title_to_filecodes'] as $title => $filecode) {
            if (!isset($seenFilecodes[$filecode])) {
                $normalizedTitle = mb_strtolower($title, 'UTF-8');
                if ($normalizedTitle === $normalizedQuery) {
                    $caseInsensitiveExactMatches[] = $filecode;
                    $seenFilecodes[$filecode] = true;
                }
            }
        }
        
        // Partial matches (title contains query) - case-insensitive
        foreach ($this->searchIndex['title_to_filecodes'] as $title => $filecode) {
            if (!isset($seenFilecodes[$filecode])) {
                $normalizedTitle = mb_strtolower($title, 'UTF-8');
                if (stripos($normalizedTitle, $normalizedQuery) !== false) {
                    $partialMatches[] = $filecode;
                    $seenFilecodes[$filecode] = true;
                }
            }
        }
        
        // Word-based matches
        foreach ($queryWords as $word) {
            if (strlen($word) > 2 && isset($this->searchIndex['word_index'][$word])) {
                $wordFilecodes = $this->searchIndex['word_index'][$word];
                foreach ($wordFilecodes as $filecode) {
                    if (!isset($seenFilecodes[$filecode])) {
                        $wordMatches[] = $filecode;
                        $seenFilecodes[$filecode] = true;
                    }
                }
            }
        }
        
        // Merge by priority: exact matches first, then case-insensitive exact, then partial, then word matches
        $allMatches = array_merge($exactMatches, $caseInsensitiveExactMatches, $partialMatches, $wordMatches);
        
        return array_values($allMatches);
    }
    
    /**
     * Sort matches by relevance
     */
    private function sortByRelevance(array $filecodes, array $queryWords, string $fullQuery): array {
        $scoredMatches = [];
        
        foreach ($filecodes as $filecode) {
            $title = $this->searchIndex['filecode_to_title'][$filecode] ?? '';
            $normalizedTitle = $this->normalizeText($title);
            
            $score = $this->calculateRelevanceScore($normalizedTitle, $queryWords, $fullQuery);
            $scoredMatches[$filecode] = $score;
        }
        
        // Sort by score descending
        arsort($scoredMatches);
        
        return array_keys($scoredMatches);
    }
    
    /**
     * Calculate relevance score for search results
     * Prioritas tertinggi untuk judul yang cocok persis dengan kata kunci
     */
    private function calculateRelevanceScore(string $title, array $queryWords, string $fullQuery): int {
        $score = 0;
        
        // Exact match bonus - prioritas tertinggi (10000 poin)
        if ($title === $fullQuery) {
            $score += 10000;
            return $score; // Return immediately untuk exact match
        }
        
        // Case-insensitive exact match - prioritas sangat tinggi (8000 poin)
        if (strcasecmp($title, $fullQuery) === 0) {
            $score += 8000;
            return $score;
        }
        
        // Title contains exact query phrase - prioritas tinggi (5000 poin)
        if (stripos($title, $fullQuery) !== false) {
            $score += 5000;
            
            // Bonus jika query di awal judul
            if (stripos($title, $fullQuery) === 0) {
                $score += 2000;
            }
        }
        
        // Contains all words in order - prioritas sedang-tinggi (3000 poin)
        $allWordsMatch = true;
        $matchedWordCount = 0;
        $wordsInOrder = true;
        $lastPos = -1;
        
        foreach ($queryWords as $word) {
            $pos = stripos($title, $word);
            if ($pos !== false) {
                $matchedWordCount++;
                if ($pos < $lastPos) {
                    $wordsInOrder = false;
                }
                $lastPos = $pos;
            } else {
                $allWordsMatch = false;
                $wordsInOrder = false;
            }
        }
        
        if ($allWordsMatch) {
            $score += 3000;
            if ($wordsInOrder) {
                $score += 1000; // Bonus jika kata-kata dalam urutan yang benar
            }
        }
        
        // Partial word matches - prioritas rendah (50 poin per kata)
        $score += ($matchedWordCount * 50);
        
        // Length penalty (shorter titles are often more relevant)
        $length = strlen($title);
        if ($length > 100) {
            // FIX: Explicitly cast to int to avoid float to int conversion warning
            $score -= (int)(($length - 100) / 10);
        }
        
        return $score;
    }
    
    /**
     * Batch load multiple videos efficiently
     */
    private function batchLoadVideos(array $filecodes): array {
        if (empty($filecodes)) {
            return [];
        }
        
        // Group filecodes by page for efficient loading
        $filecodePageMap = $this->getFilecodePageMap();
        $pageGroups = [];
        
        foreach ($filecodes as $filecode) {
            $page = $filecodePageMap[$filecode] ?? null;
            if ($page) {
                if (!isset($pageGroups[$page])) {
                    $pageGroups[$page] = [];
                }
                $pageGroups[$page][] = $filecode;
            }
        }
        
        // Load videos in batches by page
        $results = [];
        foreach ($pageGroups as $page => $pageFilecodes) {
            $pageVideos = $this->loadVideosFromPage($page);
            $videoMap = [];
            
            // Create map for quick lookup
            foreach ($pageVideos as $video) {
                $videoFilecode = $video['filecode'] ?? $video['file_code'] ?? '';
                $videoMap[$videoFilecode] = $video;
            }
            
            // Add matching videos to results
            foreach ($pageFilecodes as $filecode) {
                if (isset($videoMap[$filecode])) {
                    $video = $this->applyTitleTruncationToVideo($videoMap[$filecode]);
                    $results[] = $video;
                }
            }
        }
        
        return $results;
    }
    
    private function getCacheKey(string $baseKey): string {
        return $this->cacheVersion . '_' . $baseKey;
    }
    
    /**
     * Optimized search dengan index
     */
    public function get_search_optimized($q = '', $page = 1): array {
        $query = trim($q);
        if (empty($query)) {
            return [];
        }
        
        $page = max(1, (int)$page);
        $per_page = 50;
        // Tidak ada batasan maksimal hasil - tampilkan semua hasil yang relevan
        
        $cacheKey = $this->getCacheKey("service_search_opts_" . md5($query . '_' . $page));
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($query, $page, $per_page) {
            $this->buildSearchIndex();
            
            $normalizedQuery = $this->normalizeText($query);
            $queryWords = $this->extractWords($normalizedQuery);
            
            $matchedFilecodes = $this->findMatchingFilecodes($queryWords, $normalizedQuery);
            
            if (empty($matchedFilecodes)) {
                // Cache lebih agresif untuk empty results (2 jam)
                $item->expiresAfter(7200);
                return [
                    'videos' => [],
                    'total_results' => 0,
                    'total_pages' => 0,
                    'cache_version' => $this->cacheVersion
                ];
            }
            
            // Sort by relevance - prioritas tinggi untuk judul yang cocok persis
            $matchedFilecodes = $this->sortByRelevance($matchedFilecodes, $queryWords, $normalizedQuery);
            
            // Pagination
            $startIndex = ($page - 1) * $per_page;
            $pagedFilecodes = array_slice($matchedFilecodes, $startIndex, $per_page);
            
            // Batch load videos
            $pagedResults = $this->batchLoadVideos($pagedFilecodes);
            
            // Apply title truncation
            $pagedResults = $this->applyTitleTruncation($pagedResults);
            
            $result = [
                'videos' => $pagedResults,
                'total_results' => count($matchedFilecodes),
                'total_pages' => (int)ceil(count($matchedFilecodes) / $per_page), // FIX: Explicit cast to int
                'cache_version' => $this->cacheVersion,
                'search_method' => 'optimized_index'
            ];

            // Cache lebih agresif: 4 jam untuk hasil yang bagus, 2 jam untuk hasil sedikit
            if (count($pagedResults) >= 10) {
                $item->expiresAfter(14400); // 4 hours untuk hasil yang banyak
            } elseif (count($pagedResults) > 0) {
                $item->expiresAfter(7200); // 2 hours untuk hasil sedikit
            } else {
                $item->expiresAfter(3600); // 1 hour untuk hasil kosong di halaman tertentu
            }
            
            return $result;
        });
    }
    
    /**
     * Batch operations untuk multiple videos
     */
    public function batchGetVideos(array $filecodes): array {
        if (empty($filecodes)) {
            return [];
        }
        
        $cacheKey = $this->getCacheKey("batch_videoss_" . md5(implode(',', $filecodes)));
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($filecodes) {
            // Cache lebih agresif: 4 jam
            $item->expiresAfter(14400);
            
            return $this->batchLoadVideos($filecodes);
        });
    }
    
    /**
     * Get videos by multiple filecodes dengan fallback
     */
    public function getVideosByFilecodes(array $filecodes, int $batchSize = 50): array {
        if (empty($filecodes)) {
            return [];
        }
        
        // Process in batches to avoid memory issues
        $allResults = [];
        $batches = array_chunk($filecodes, $batchSize);
        
        foreach ($batches as $batch) {
            $batchResults = $this->batchGetVideos($batch);
            $allResults = array_merge($allResults, $batchResults);
        }
        
        return $allResults;
    }
    
    /**
     * Preload multiple pages for better performance
     */
    public function preloadPages(array $pages): void {
        $filecodePageMap = $this->getFilecodePageMap();
        $pageFiles = [];
        
        // Group filecodes by page
        foreach ($filecodePageMap as $filecode => $page) {
            if (in_array($page, $pages)) {
                if (!isset($pageFiles[$page])) {
                    $pageFiles[$page] = [];
                }
                $pageFiles[$page][] = $filecode;
            }
        }
        
        // Preload each page
        foreach ($pageFiles as $page => $filecodes) {
            $this->loadVideosFromPage($page);
        }
    }
    
    /**
     * Get statistics about the video database
     */
    public function getDatabaseStats(): array {
        $metadata = $this->loadMetadata();
        
        return [
            'total_videos' => $metadata['total_videos'] ?? 0,
            'total_pages' => $metadata['total_pages'] ?? 0,
            'videos_per_page' => $metadata['videos_per_page'] ?? 0,
            'unique_titles' => count($metadata['filecode_title_map'] ?? []),
            'cache_version' => $this->cacheVersion,
            'metadata_file_size' => file_exists($this->metadataFile) ? filesize($this->metadataFile) : 0,
            'last_updated' => file_exists($this->metadataFile) ? date('Y-m-d H:i:s', filemtime($this->metadataFile)) : null,
            'static_cache_active' => self::$metadataStaticCache !== null,
            'static_cache_key' => self::$metadataStaticCacheKey,
            'static_cache_last_modified' => self::$metadataLastModified,
            'static_cache_checksum' => self::$metadataChecksum ? substr(self::$metadataChecksum, 0, 8) : null
        ];
    }
    
    /**
     * Debug method untuk melihat informasi cache
     */
    public function debugCacheInfo(): array {
        return [
            'static_cache_loaded' => self::$metadataStaticCache !== null,
            'static_cache_key' => self::$metadataStaticCacheKey,
            'static_cache_last_modified' => self::$metadataLastModified,
            'static_cache_checksum' => self::$metadataChecksum ? substr(self::$metadataChecksum, 0, 8) : null,
            'metadata_file_exists' => file_exists($this->metadataFile),
            'metadata_file_mtime' => file_exists($this->metadataFile) ? filemtime($this->metadataFile) : null,
            'metadata_file_size' => file_exists($this->metadataFile) ? filesize($this->metadataFile) : null,
            'cache_version' => $this->cacheVersion,
            'filecode_title_map_count' => count($this->getFilecodeTitleMap()),
            'filecode_page_map_count' => count($this->getFilecodePageMap()),
            'total_pages' => $this->getTotalPagesCount(),
            'total_videos' => $this->getTotalCountFromIndex()
        ];
    }
    
    /**
     * Search dengan filter tambahan
     */
    public function advancedSearch(array $criteria): array {
        $defaultCriteria = [
            'query' => '',
            'page' => 1,
            'per_page' => 50,
            'min_duration' => null,
            'max_duration' => null,
            'sort_by' => 'relevance', // relevance, newest, oldest, title
            'sort_order' => 'desc'
        ];
        
        $criteria = array_merge($defaultCriteria, $criteria);
        
        // First get search results
        $searchResults = $this->get_search_optimized($criteria['query'], $criteria['page']);
        
        if (empty($searchResults['videos'])) {
            return $searchResults;
        }
        
        // Apply filters
        $filteredVideos = $this->applySearchFilters($searchResults['videos'], $criteria);
        
        // Apply sorting
        $sortedVideos = $this->applySearchSorting($filteredVideos, $criteria);
        
        // Paginate
        $startIndex = ($criteria['page'] - 1) * $criteria['per_page'];
        $pagedVideos = array_slice($sortedVideos, $startIndex, $criteria['per_page']);
        
        // Apply title truncation
        $pagedVideos = $this->applyTitleTruncation($pagedVideos);
        
        return [
            'videos' => $pagedVideos,
            'total_results' => count($filteredVideos),
            'total_pages' => (int)ceil(count($filteredVideos) / $criteria['per_page']), // FIX: Explicit cast to int
            'cache_version' => $this->cacheVersion,
            'search_method' => 'advanced'
        ];
    }
    
    private function applySearchFilters(array $videos, array $criteria): array {
        return array_filter($videos, function($video) use ($criteria) {
            // Duration filter
            if ($criteria['min_duration'] !== null || $criteria['max_duration'] !== null) {
                $duration = $this->parseDuration($video['length'] ?? $video['duration'] ?? '');
                if ($duration === null) return false;
                
                if ($criteria['min_duration'] !== null && $duration < $criteria['min_duration']) {
                    return false;
                }
                if ($criteria['max_duration'] !== null && $duration > $criteria['max_duration']) {
                    return false;
                }
            }
            
            return true;
        });
    }
    
    private function applySearchSorting(array $videos, array $criteria): array {
        usort($videos, function($a, $b) use ($criteria) {
            switch ($criteria['sort_by']) {
                case 'newest':
                    $timeA = strtotime($a['uploaded'] ?? $a['date'] ?? '');
                    $timeB = strtotime($b['uploaded'] ?? $b['date'] ?? '');
                    return $criteria['sort_order'] === 'desc' ? $timeB - $timeA : $timeA - $timeB;
                    
                case 'title':
                    $titleA = $a['title'] ?? '';
                    $titleB = $b['title'] ?? '';
                    return $criteria['sort_order'] === 'desc' 
                        ? strcmp($titleB, $titleA) 
                        : strcmp($titleA, $titleB);
                        
                case 'relevance':
                default:
                    // For relevance, maintain original order (already sorted by relevance)
                    return 0;
            }
        });
        
        return $videos;
    }
    
    private function parseDuration(string $duration): ?int {
        // Convert "HH:MM:SS" or "MM:SS" to seconds
        $parts = explode(':', $duration);
        $parts = array_map('intval', $parts);
        
        if (count($parts) === 3) {
            return $parts[0] * 3600 + $parts[1] * 60 + $parts[2];
        } elseif (count($parts) === 2) {
            return $parts[0] * 60 + $parts[1];
        }
        
        return null;
    }
    
    // ========== EXISTING METHODS (dengan optimisasi) ==========
    
    public function generatePrecache(array $videos = [], int $maxPages = 15): void {
        error_log("?? Starting OPTIMIZED precache generation for version: " . $this->cacheVersion);
        
        $startTime = microtime(true);
        
        try {
            $totalVideos = count($videos) ?: $this->getTotalCountFromIndex();
            $perPage = 30;
            // FIX: Explicit cast to int untuk menghindari float to int conversion
            $totalPages = (int)ceil($totalVideos / $perPage);
            $pagesToCache = min($totalPages, $maxPages);
            
            $importantPages = min($pagesToCache, 8);
            for ($page = 1; $page <= $importantPages; $page++) {
                $this->get_list_files($page);
                error_log("? Precached page: {$page}/{$importantPages}");
            }
            
            $this->get_list_random(30);
            $this->get_list_random(20);
            
            // Prebuild search index
            $this->buildSearchIndex();
            
            $endTime = microtime(true);
            $duration = round($endTime - $startTime, 2);
            
            error_log("? Optimized precaching completed in {$duration}s - {$importantPages} pages, 2 random sets, search index");
            
        } catch (Exception $e) {
            error_log("? Precaching failed: " . $e->getMessage());
        }
    }
    
    public function refreshCache(array $videos = []): void {
        try {
            error_log("?? Starting cache refresh...");
            $this->clearCache();
            $this->cacheVersion = $this->getCurrentCacheVersion();
            $this->generatePrecache($videos);
            error_log("? Cache refresh completed successfully");
        } catch (Exception $e) {
            error_log("? Cache refresh failed: " . $e->getMessage());
        }
    }
    
    public function clearCache(): bool {
        try {
            $result = $this->cache->clear();
            $this->searchIndex = null; // Reset search index
            $this->titleWordIndex = null;
            self::clearStaticCache(); // Clear static cache juga
            error_log("?? Cache cleared: " . ($result ? 'success' : 'failed'));
            return $result;
        } catch (Exception $e) {
            error_log("? Cache clear error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get list of files from specific page using pages_metadata.json
     */
    public function get_list_files($halaman = null) {
        global $route;
        
        $page = $halaman ?? $route['vars'][0] ?? 1;
        $page = max(1, (int)$page);
        $per_page = 30;
        
        $cacheKey = $this->getCacheKey("service_list_filesss_{$page}");
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($page, $per_page) {
            try {
                $metadata = $this->loadMetadata();
                $totalVideos = (int)($metadata['total_videos'] ?? 0);
                $videosPerPage = (int)($metadata['videos_per_page'] ?? 100);
                $totalPages = (int)($metadata['total_pages'] ?? 0);
                
                // ? OPTIMIZED: Langsung load dari page file menggunakan pages_metadata.json
                $videos = $this->loadVideosFromPage($page);
                
                // Jika per_page berbeda dengan videos_per_page di metadata, ambil slice yang sesuai
                if ($videosPerPage !== $per_page && !empty($videos)) {
                    $startIndex = (($page - 1) * $per_page) % $videosPerPage;
                    $videos = array_slice($videos, $startIndex, $per_page);
                }
                
                // Apply title truncation
                $videos = $this->applyTitleTruncation($videos);
                
                // Hitung total pages berdasarkan per_page yang diminta
                // FIX: Explicit cast to int untuk menghindari float to int conversion
                $calculatedTotalPages = $totalVideos > 0 ? (int)ceil($totalVideos / $per_page) : 0;
                
                $response = [
                    "server_time" => date('Y-m-d H:i:s'),
                    "status" => 200,
                    "msg" => "OK",
                    "result" => [
                        "per_page_limit" => $per_page,
                        "total_pages" => $calculatedTotalPages,
                        "current_page" => $page,
                        "results" => count($videos),
                        "results_total" => (string)$totalVideos,
                        "files" => $videos
                    ],
                    "api_source" => "video_service",
                    "cache_version" => $this->cacheVersion
                ];

                // Cache lebih agresif: 4 jam untuk halaman list
                $item->expiresAfter(14400);
                
                return $response;
                
            } catch (Exception $e) {
                // Cache error lebih pendek: 1 jam
                $item->expiresAfter(3600);
                return [
                    "server_time" => date('Y-m-d H:i:s'),
                    "status" => 500,
                    "msg" => "Error: " . $e->getMessage(),
                    "result" => [
                        "per_page_limit" => $per_page,
                        "total_pages" => 1,
                        "current_page" => $page,
                        "results" => 0,
                        "results_total" => "0",
                        "files" => []
                    ]
                ];
            }
        });
    }
 
    public function get_video() {
        global $route;
        
        if (!isset($route['vars'][0])) {
            return [
                'error' => "Video ID not found.",
                'source' => 'none'
            ];
        }
        
        $id = $route['vars'][0];
 
        
        $cacheKey = $this->getCacheKey("servicse_get_videos_" . md5($id));
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($id) {
            try {
                $map = $this->getFilecodeTitleMap();
                if (!isset($map[$id])) {
                    // Cache lebih agresif untuk video tidak ditemukan: 2 jam
                    $item->expiresAfter(7200);
                    return [];
                }
                
                $videoData = $this->loadVideoByFilecodeOptimized($id);
                
                if ($videoData) {
                    $videoData = $this->applyTitleTruncationToVideo($videoData);
                    $videoData['cache_version'] = $this->cacheVersion;
                    // Cache lebih agresif untuk video yang ditemukan: 6 jam
                    $item->expiresAfter(21600);
                    return $videoData;
                }
                
                // Video tidak ditemukan setelah cek map: 2 jam
                $item->expiresAfter(7200);
                return [];
                
            } catch (Exception $e) {
                // Cache error: 1 jam
                $item->expiresAfter(3600);
                return [
                    'error' => "Failed to get video: " . $e->getMessage(),
                    'source' => 'error'
                ];
            }
        });
    }
    
    /**
     * Update existing search method to use optimized version
     */
    public function get_search($q = '', $page = 1) {
        // Use optimized search by default
        return $this->get_search_optimized($q, $page);
    }
    
    public function get_related($title = null, $mainFileCode = null, $limit = 40) {
        // Get current video data if title or filecode not provided
        if ($title === null || $mainFileCode === null) {
            global $route;
            if (!isset($route['vars'][0])) return [];
            
            $videoData = $this->get_video();
            if (empty($videoData) || !isset($videoData['title']) || isset($videoData['error'])) return [];
            
            if ($title === null) {
                $title = $videoData['title'];
            }
            if ($mainFileCode === null) {
                $mainFileCode = $videoData['filecode'] ?? $videoData['file_code'] ?? '';
            }
        }
        
        // Ensure mainFileCode is a string for comparison
        $mainFileCode = (string)($mainFileCode ?? '');
        
        $titleWords = preg_split('/\s+/', trim($title), -1, PREG_SPLIT_NO_EMPTY);
        // Use maximum 4 words from title for search query
        $searchQuery = implode(' ', array_slice($titleWords, 0, 5));
        
        // TAMBAHKAN CACHE UNTUK RELATED VIDEOS
        $cacheKey = $this->getCacheKey("service_related_searches_" . md5($searchQuery . '_' . $mainFileCode . '_' . $limit));
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($searchQuery, $mainFileCode, $limit) {
            $searchResult = $this->get_search($searchQuery, 1);
            
            if (empty($searchResult) || !isset($searchResult['videos']) || !is_array($searchResult['videos'])) {
                // Cache lebih agresif untuk empty results: 2 jam
                $item->expiresAfter(7200);
                return [];
            }
            
            $videos = $searchResult['videos'];
            
            // Always filter out current video to prevent it from appearing in related results
            // Normalize mainFileCode untuk perbandingan yang lebih akurat
            $mainFileCodeNormalized = trim(strtolower($mainFileCode));
            
            $videos = array_filter($videos, function($item) use ($mainFileCode, $mainFileCodeNormalized) {
                // Cek semua kemungkinan field filecode
                $itemFilecode1 = (string)($item['file_code'] ?? '');
                $itemFilecode2 = (string)($item['filecode'] ?? '');
                
                // Normalize untuk perbandingan case-insensitive
                $itemFilecode1Normalized = trim(strtolower($itemFilecode1));
                $itemFilecode2Normalized = trim(strtolower($itemFilecode2));
                
                // Exclude jika filecode sama dengan video yang sedang diputar
                $isCurrentVideo = (
                    (!empty($mainFileCode) && (
                        $itemFilecode1 === $mainFileCode ||
                        $itemFilecode2 === $mainFileCode ||
                        $itemFilecode1Normalized === $mainFileCodeNormalized ||
                        $itemFilecode2Normalized === $mainFileCodeNormalized
                    ))
                );
                
                // Hanya return video yang bukan video saat ini dan memiliki filecode
                return !$isCurrentVideo && (!empty($itemFilecode1) || !empty($itemFilecode2));
            });
            
            // Reset array keys setelah filter
            $videos = array_values($videos);
            
            $videos = $this->remove_duplicate_by_length($videos);
            
            // Apply title truncation
            $videos = $this->applyTitleTruncation($videos);
            
            // Sort by relevance first, then by uploaded date (newest first)
            $searchQueryLower = mb_strtolower($searchQuery, 'UTF-8');
            $searchWords = explode(' ', $searchQueryLower);
            $searchWords = array_filter($searchWords, function($word) {
                return strlen(trim($word)) > 0;
            });
            
            usort($videos, function($a, $b) use ($searchQueryLower, $searchWords) {
                // Calculate relevance score for video A
                $titleA = mb_strtolower($a['title'] ?? '', 'UTF-8');
                $relevanceA = 0;
                
                // Exact match gets highest score
                if ($titleA === $searchQueryLower) {
                    $relevanceA = 1000;
                }
                // Starts with query gets high score
                elseif (strpos($titleA, $searchQueryLower) === 0) {
                    $relevanceA = 500;
                }
                // Contains all words gets medium-high score
                elseif (!empty($searchWords)) {
                    $allWordsMatch = true;
                    foreach ($searchWords as $word) {
                        if (strpos($titleA, $word) === false) {
                            $allWordsMatch = false;
                            break;
                        }
                    }
                    if ($allWordsMatch) {
                        $relevanceA = 300;
                    }
                    // Contains some words gets lower score
                    else {
                        $matchedWords = 0;
                        foreach ($searchWords as $word) {
                            if (strpos($titleA, $word) !== false) {
                                $matchedWords++;
                            }
                        }
                        $relevanceA = 100 + ($matchedWords * 10);
                    }
                }
                // Contains query as substring gets low score
                elseif (strpos($titleA, $searchQueryLower) !== false) {
                    $relevanceA = 50;
                }
                
                // Calculate relevance score for video B
                $titleB = mb_strtolower($b['title'] ?? '', 'UTF-8');
                $relevanceB = 0;
                
                if ($titleB === $searchQueryLower) {
                    $relevanceB = 1000;
                }
                elseif (strpos($titleB, $searchQueryLower) === 0) {
                    $relevanceB = 500;
                }
                elseif (!empty($searchWords)) {
                    $allWordsMatch = true;
                    foreach ($searchWords as $word) {
                        if (strpos($titleB, $word) === false) {
                            $allWordsMatch = false;
                            break;
                        }
                    }
                    if ($allWordsMatch) {
                        $relevanceB = 300;
                    }
                    else {
                        $matchedWords = 0;
                        foreach ($searchWords as $word) {
                            if (strpos($titleB, $word) !== false) {
                                $matchedWords++;
                            }
                        }
                        $relevanceB = 100 + ($matchedWords * 10);
                    }
                }
                elseif (strpos($titleB, $searchQueryLower) !== false) {
                    $relevanceB = 50;
                }
                
                // First sort by relevance (higher is better)
                if ($relevanceA !== $relevanceB) {
                    return $relevanceB <=> $relevanceA; // Descending: higher relevance first
                }
                
                // If relevance is equal, sort by uploaded date (newest first)
                $uploadedA = $a['uploaded'] ?? $a['date'] ?? '';
                $uploadedB = $b['uploaded'] ?? $b['date'] ?? '';
                
                $timestampA = 0;
                $timestampB = 0;
                
                if (!empty($uploadedA)) {
                    $timestampA = strtotime($uploadedA);
                    if ($timestampA === false) {
                        $timestampA = 0;
                    }
                }
                
                if (!empty($uploadedB)) {
                    $timestampB = strtotime($uploadedB);
                    if ($timestampB === false) {
                        $timestampB = 0;
                    }
                }
                
                // Sort descending (newest first)
                return $timestampB <=> $timestampA;
            });
            
            // Filter sekali lagi setelah sorting untuk memastikan video yang sedang diputar tidak muncul
            $videos = array_filter($videos, function($item) use ($mainFileCode, $mainFileCodeNormalized) {
                $itemFilecode1 = (string)($item['file_code'] ?? '');
                $itemFilecode2 = (string)($item['filecode'] ?? '');
                $itemFilecode1Normalized = trim(strtolower($itemFilecode1));
                $itemFilecode2Normalized = trim(strtolower($itemFilecode2));
                
                $isCurrentVideo = (
                    (!empty($mainFileCode) && (
                        $itemFilecode1 === $mainFileCode ||
                        $itemFilecode2 === $mainFileCode ||
                        $itemFilecode1Normalized === $mainFileCodeNormalized ||
                        $itemFilecode2Normalized === $mainFileCodeNormalized
                    ))
                );
                
                return !$isCurrentVideo;
            });
            
            $result = array_slice(array_values($videos), 0, $limit);
            
            // Cache lebih agresif: 4 jam untuk hasil yang bagus, 2 jam untuk hasil sedikit
            if (count($result) >= 10) {
                $item->expiresAfter(14400); // 4 jam
            } elseif (count($result) >= 5) {
                $item->expiresAfter(7200); // 2 jam
            } else {
                $item->expiresAfter(3600); // 1 jam
            }
            
            return $result;
        });
    }
    
    // ? FIXED: get_list_random - Tidak memory intensive lagi
    public function get_list_random($jumlah = 30) {
        $cacheKey = $this->getCacheKey("service_randoms_" . $jumlah);
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($jumlah) {
            try {
                // ? OPTIMIZED: Gunakan filecode map saja (ringan)
                $filecodeMap = $this->getFilecodeTitleMap();
                $allFilecodes = array_keys($filecodeMap);
                
                if (empty($allFilecodes)) {
                    // Cache lebih agresif untuk empty: 2 jam
                    $item->expiresAfter(7200);
                    return [];
                }
                
                // ? Shuffle hanya array filecodes (bukan semua data video)
                shuffle($allFilecodes);
                $randomFilecodes = array_slice($allFilecodes, 0, $jumlah);
                
                $results = [];
                foreach ($randomFilecodes as $filecode) {
                    $videoData = $this->loadVideoByFilecodeOptimized($filecode);
                    if ($videoData) {
                        $videoData = $this->applyTitleTruncationToVideo($videoData);
                        $results[] = $videoData;
                    }
                }
                
                // Cache lebih agresif: 3 jam untuk random videos
                $item->expiresAfter(10800);
                
                return $results;
                
            } catch (Exception $e) {
                error_log("Random videos error: " . $e->getMessage());
                // Cache error: 1 jam
                $item->expiresAfter(3600);
                return [];
            }
        });
    }
    
    // ? NEW: Optimized method untuk random videos dengan batasan memory
    public function get_list_random_optimized($jumlah = 30, $maxPagesToScan = 5) {
        $cacheKey = $this->getCacheKey("service_random_opt_" . $jumlah . '_' . $maxPagesToScan);
        
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($jumlah, $maxPagesToScan) {
            try {
                $totalPages = $this->getTotalPagesCount();
                if ($totalPages === 0) {
                    // Cache lebih agresif untuk empty: 2 jam
                    $item->expiresAfter(7200);
                    return [];
                }
                
                // ? Pilih random pages untuk diversitas
                $randomPages = [];
                $pagesToPick = min($maxPagesToScan, $totalPages);
                
                for ($i = 0; $i < $pagesToPick; $i++) {
                    $randomPages[] = rand(1, $totalPages);
                }
                $randomPages = array_unique($randomPages);
                
                // ? Collect videos dari random pages
                $allCandidates = [];
                foreach ($randomPages as $page) {
                    $videos = $this->loadVideosFromPage($page);
                    $allCandidates = array_merge($allCandidates, $videos);
                }
                
                // ? Shuffle dan ambil jumlah yang diminta
                shuffle($allCandidates);
                $results = array_slice($allCandidates, 0, $jumlah);
                
                // Apply title truncation
                $results = $this->applyTitleTruncation($results);
                
                // Cache lebih agresif: 3 jam untuk random videos optimized
                $item->expiresAfter(10800);
                
                return $results;
                
            } catch (Exception $e) {
                error_log("Optimized random videos error: " . $e->getMessage());
                // Cache error: 1 jam
                $item->expiresAfter(3600);
                return $this->get_list_random($jumlah); // Fallback ke method original
            }
        });
    }

    public function remove_duplicate_by_length($items) {
        $unique_items = [];
        $seen_lengths = [];
        
        foreach ($items as $item) {
            $length = $item['length'] ?? $item['duration'] ?? '';
            $filecode = $item['file_code'] ?? $item['filecode'] ?? '';
            $identifier = $filecode . '_' . $length; // Kombinasi filecode + length
            
            if (!isset($seen_lengths[$identifier])) {
                $seen_lengths[$identifier] = true;
                $unique_items[] = $item;
            }
        }
        
        return $unique_items;
    }

    public function remove_duplicate_titles($items) {
        $unique_items = [];
        $seen_identifiers = [];
        
        foreach ($items as $item) {
            $filecode = $item['file_code'] ?? $item['filecode'] ?? '';
            if (!empty($filecode) && !isset($seen_identifiers[$filecode])) {
                $seen_identifiers[$filecode] = true;
                $unique_items[] = $item;
            }
        }
        
        return $unique_items;
    }
    
    public function getRandomWords($count = 10) {
        if (class_exists('DataNormalizer')) {
            return (new DataNormalizer())->getRandomWords($count);
        }
        return [];
    }
    
    /**
     * Truncate title to maximum 60 characters
     * - Tidak memotong di tengah kata
     * - Menjaga bagian penting judul tetap utuh (awal judul lebih penting)
     * - Tetap readable meski dipotong
     * 
     * @param string $title Judul yang akan dipotong
     * @param int $maxLength Maksimal panjang karakter (default: 60)
     * @return string Judul yang sudah dipotong tanpa ellipsis
     */
    public function truncateTitle(string $title, int $maxLength = 60): string {
        // Normalize input
        $title = trim($title);
        if (empty($title)) {
            return $title;
        }
        
        $titleLength = mb_strlen($title, 'UTF-8');
        
        // Jika judul sudah pendek, kembalikan as-is
        if ($titleLength <= $maxLength) {
            return $title;
        }
        
        // Potong ke maxLength
        $truncated = mb_substr($title, 0, $maxLength, 'UTF-8');
        
        // Cari spasi terakhir untuk menghindari memotong di tengah kata
        $lastSpace = mb_strrpos($truncated, ' ', 0, 'UTF-8');
        
        // Jika ada spasi dan posisinya tidak terlalu dekat dengan awal (minimal 40% dari maxLength)
        // Ini memastikan bagian penting judul (awal) tetap utuh
        $minLength = (int)($maxLength * 0.4);
        if ($lastSpace !== false && $lastSpace >= $minLength) {
            // Potong di spasi terakhir untuk menghindari memotong di tengah kata
            $truncated = mb_substr($truncated, 0, $lastSpace, 'UTF-8');
        }
        // Jika tidak ada spasi (satu kata panjang), tetap potong di maxLength
        
        // Hapus spasi di akhir
        $truncated = rtrim($truncated);
        
        // Pastikan tidak kosong setelah trim
        if (empty($truncated)) {
            // Fallback: ambil minimal 10 karakter pertama
            $truncated = mb_substr($title, 0, min(10, $titleLength), 'UTF-8');
        }
        
        // Kembalikan judul yang sudah dipotong tanpa ellipsis
        return $truncated;
    }
    
    /**
     * Apply title truncation to a single video item
     * 
     * @param array $video Video data array
     * @return array Video data with truncated title
     */
    private function applyTitleTruncationToVideo(array $video): array {
        if (isset($video['title']) && is_string($video['title'])) {
            $video['title'] = $this->truncateTitle($video['title']);
        }
        return $video;
    }
    
    /**
     * Apply title truncation to multiple video items
     * 
     * @param array $videos Array of video data
     * @return array Array of videos with truncated titles
     */
    private function applyTitleTruncation(array $videos): array {
        return array_map([$this, 'applyTitleTruncationToVideo'], $videos);
    }
}

function agc() {
    static $instance = null;
    
    if ($instance === null) {
        $instance = new VideoService();
    }
    
    return $instance;
}