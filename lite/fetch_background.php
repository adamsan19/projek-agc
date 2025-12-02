<?php

// Set working directory ke root project
$rootDir = dirname(__DIR__);
chdir($rootDir);

// Autoload Composer dependencies (Guzzle, Symfony Cache)
require $rootDir . '/vendor/autoload.php';

// Import semua fungsi dari api.php
require $rootDir . '/core/classes/api.php';

use GuzzleHttp\Client;
use GuzzleHttp\Promise;

// --- Constants (hanya yang khusus fetch_background) ---
const MAX_REQUESTS_PER_SECOND = 10;
const REQUEST_INTERVAL_MS = 1000;
 

// --- Main Execution ---

// Simpan waktu mulai
$startTime = date('Y-m-d H:i:s');

// Update status awal
updateFetchStatus([
    'status' => 'running',
    'started_at' => $startTime,
    'message' => 'Fetch process started - Initializing...',
    'progress' => 5,
    'total_videos' => 0
]);

error_log("=== FETCH BACKGROUND STARTED at {$startTime} ===");

try {
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

    // Update: Loading existing data
    updateFetchStatus([
        'status' => 'running',
        'started_at' => $startTime,
        'message' => 'Loading existing video data...',
        'progress' => 10,
        'total_videos' => 0
    ]);

    // Load existing data untuk preservasi title
    $existingVideos = loadAllVideosFromPaginatedFiles();
    error_log("Loaded " . count($existingVideos) . " existing videos for title preservation");

    $allVideos = [];
    $totalSources = count($configs);
    $currentSource = 0;

    // Progress allocation: 10-80% for fetching (35% per source)
    $fetchProgressStart = 10;
    $fetchProgressEnd = 80;
    $progressPerSource = ($fetchProgressEnd - $fetchProgressStart) / $totalSources;

    foreach ($configs as $sourceName => $config) {
        $currentSource++;
        $sourceProgressStart = $fetchProgressStart + (($currentSource - 1) * $progressPerSource);
        $sourceProgressEnd = $fetchProgressStart + ($currentSource * $progressPerSource);
        
        updateFetchStatus([
            'status' => 'running',
            'started_at' => $startTime,
            'message' => "Connecting to {$sourceName} API...",
            'progress' => round($sourceProgressStart),
            'total_videos' => count($allVideos)
        ]);

        error_log("Fetching data from {$sourceName}...");
        
        // Update mid-fetch
        updateFetchStatus([
            'status' => 'running',
            'started_at' => $startTime,
            'message' => "Fetching videos from {$sourceName}...",
            'progress' => round($sourceProgressStart + ($progressPerSource * 0.5)),
            'total_videos' => count($allVideos)
        ]);
        
        $sourceVideos = fetchAllPages($config, $sourceName, $existingVideos);
        $allVideos = array_merge($allVideos, $sourceVideos);
        
        error_log("Fetched " . count($sourceVideos) . " videos from {$sourceName}");
        
        // Update after source complete
        updateFetchStatus([
            'status' => 'running',
            'started_at' => $startTime,
            'message' => "Completed {$sourceName}: " . count($sourceVideos) . " videos",
            'progress' => round($sourceProgressEnd),
            'total_videos' => count($allVideos)
        ]);
    }

    // Update: Merging data
    updateFetchStatus([
        'status' => 'running',
        'started_at' => $startTime,
        'message' => 'Merging video data with existing titles...',
        'progress' => 82,
        'total_videos' => count($allVideos)
    ]);

    // ✅ GUNAKAN MERGE FUNCTION YANG BARU
    $mergedVideos = mergeVideoData($allVideos, $existingVideos);
    
    // Hitung statistik preservasi
    $preservedCount = 0;
    $newCount = 0;
    foreach ($mergedVideos as $video) {
        if ($video['title_preserved'] ?? false) {
            $preservedCount++;
        } else {
            $newCount++;
        }
    }
    
    error_log("Merge result: {$preservedCount} preserved titles, {$newCount} new titles");

    // Update: Removing duplicates
    updateFetchStatus([
        'status' => 'running',
        'started_at' => $startTime,
        'message' => 'Removing duplicate videos...',
        'progress' => 85,
        'total_videos' => count($mergedVideos)
    ]);

    // Remove duplicates based on filecode (safety measure)
    $uniqueVideos = [];
    $seenFilecodes = [];
    foreach ($mergedVideos as $video) {
        $filecode = $video['filecode'] ?? $video['file_code'] ?? '';
        if ($filecode && !isset($seenFilecodes[$filecode])) {
            $uniqueVideos[] = $video;
            $seenFilecodes[$filecode] = true;
        }
    }

    error_log("Total unique videos: " . count($uniqueVideos));

    // Update: Sorting
    updateFetchStatus([
        'status' => 'running',
        'started_at' => $startTime,
        'message' => 'Sorting videos by upload date...',
        'progress' => 88,
        'total_videos' => count($uniqueVideos)
    ]);

    // ✅ SORT BY UPLOADED DATE (newest first)
    usort($uniqueVideos, function($a, $b) {
        $timestampA = parseUploadedTimestamp($a['uploaded'] ?? '');
        $timestampB = parseUploadedTimestamp($b['uploaded'] ?? '');
        
        // Sort descending (newest first)
        return $timestampB <=> $timestampA;
    });
    
    error_log("✅ Videos sorted by uploaded date (newest first)");

    // ✅ SIMPAN DATA DALAM FORMAT PAGINATED
    updateFetchStatus([
        'status' => 'running',
        'started_at' => $startTime,
        'message' => "Saving paginated data files... (Preserved: {$preservedCount}, New: {$newCount})",
        'progress' => 92,
        'total_videos' => count($uniqueVideos)
    ]);

    $pageFiles = savePaginatedDataFiles($uniqueVideos);
    
    // ✅ SIMPAN INDIVIDUAL VIDEO FILES (untuk pencarian)
    if (function_exists('saveIndividualVideoFiles')) {
        updateFetchStatus([
            'status' => 'running',
            'started_at' => $startTime,
            'message' => 'Saving individual video files...',
            'progress' => 96,
            'total_videos' => count($uniqueVideos)
        ]);

        saveIndividualVideoFiles($uniqueVideos);
    }

    $endTime = date('Y-m-d H:i:s');

    // Update status akhir dengan statistik preservasi
    updateFetchStatus([
        'status' => 'completed',
        'started_at' => $startTime,
        'completed_at' => $endTime,
        'message' => "✅ Fetch completed! Preserved: {$preservedCount}, New: {$newCount} videos",
        'progress' => 100,
        'total_videos' => count($uniqueVideos),
        'preserved_titles' => $preservedCount,
        'new_videos' => $newCount,
        'pages_created' => count($pageFiles)
    ]);

    error_log("=== FETCH BACKGROUND COMPLETED at {$endTime} ===");
    error_log("Total videos: " . count($uniqueVideos));
    error_log("Title preservation: {$preservedCount} preserved, {$newCount} new");

    // Cleanup lock file
    if (file_exists(FETCH_LOCK_FILE)) {
        unlink(FETCH_LOCK_FILE);
    }

} catch (Throwable $e) {
    $errorTime = date('Y-m-d H:i:s');
    error_log("=== FETCH BACKGROUND ERROR at {$errorTime} ===");
    error_log("Error: " . $e->getMessage());
    error_log("File: " . $e->getFile() . " Line: " . $e->getLine());
    
    updateFetchStatus([
        'status' => 'error',
        'started_at' => $startTime ?? $errorTime,
        'completed_at' => $errorTime,
        'message' => '❌ Fetch failed: ' . $e->getMessage(),
        'progress' => 0,
        'total_videos' => 0
    ]);

    // Cleanup lock file on error
    if (file_exists(FETCH_LOCK_FILE)) {
        unlink(FETCH_LOCK_FILE);
    }
}

?>