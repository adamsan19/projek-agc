<?php
// Define paths FIRST - use dirname for reliable path resolution
$apiDir = dirname(__DIR__) . '/core/classes';
$outputDir = dirname(__DIR__) . '/output';

define('API_DIR', $apiDir);
define('OUTPUT_DIR', $outputDir);
define('FETCH_LOCK_FILE', API_DIR . '/core/classes/fetch.lock');
define('FETCH_STATUS_FILE', API_DIR . '/core/classes/fetch_status.json');
define('FETCH_LOG_FILE', API_DIR . '/core/classes/fetch_output.log');

// Helper functions (local implementation to avoid api.php dependency issues)
function isFetchRunningLocal(): bool {
    if (!file_exists(FETCH_LOCK_FILE)) {
        return false;
    }
    
    $lockTime = filemtime(FETCH_LOCK_FILE);
    $timeout = 1800; // 30 minutes
    
    if (time() - $lockTime > $timeout) {
        @unlink(FETCH_LOCK_FILE);
        return false;
    }
    
    // Additional check: if status file says completed, process is not running
    if (file_exists(FETCH_STATUS_FILE)) {
        $status = json_decode(file_get_contents(FETCH_STATUS_FILE), true) ?? [];
        if (($status['status'] ?? '') === 'completed' || ($status['status'] ?? '') === 'error') {
            // Process completed, remove stale lock file
            @unlink(FETCH_LOCK_FILE);
            return false;
        }
    }
    
    return true;
}

function getFetchStatusLocal(): array {
    $defaultStatus = [
        'status' => 'idle',
        'started_at' => null,
        'completed_at' => null,
        'message' => 'No fetch process running',
        'progress' => 0,
        'total_videos' => 0
    ];
    
    if (!file_exists(FETCH_STATUS_FILE)) {
        return $defaultStatus;
    }
    
    $status = json_decode(file_get_contents(FETCH_STATUS_FILE), true) ?? $defaultStatus;
    
    // Check if process is actually running
    $isRunning = isFetchRunningLocal();
    
    // If status says running but lock file doesn't exist or expired, mark as completed
    if (($status['status'] ?? '') === 'running' && !$isRunning) {
        // Check if status was recently updated (within last 5 minutes)
        $statusFileTime = filemtime(FETCH_STATUS_FILE);
        $timeSinceUpdate = time() - $statusFileTime;
        
        // Also check if progress is stuck at 5% for more than 10 minutes (process likely failed to start)
        $currentProgress = $status['progress'] ?? 0;
        $isStuckAtStart = ($currentProgress <= 5 && $timeSinceUpdate > 600); // 10 minutes
        
        if ($timeSinceUpdate > 300 || $isStuckAtStart) { // 5 minutes or stuck at start
            // Process likely completed but status wasn't updated properly, or failed to start
            $status['status'] = $isStuckAtStart ? 'error' : 'completed';
            if (empty($status['completed_at'])) {
                $status['completed_at'] = date('Y-m-d H:i:s', $statusFileTime);
            }
            if ($isStuckAtStart) {
                $status['message'] = 'Process failed to start or crashed (stuck at initialization)';
                $status['progress'] = 0;
            } else {
                if (empty($status['message']) || strpos($status['message'], 'completed') === false) {
                    $status['message'] = 'Process completed (auto-detected)';
                }
                if (($status['progress'] ?? 0) < 100) {
                    $status['progress'] = 100;
                }
            }
            // Save the corrected status
            file_put_contents(FETCH_STATUS_FILE, json_encode($status, JSON_PRETTY_PRINT));
        }
    }
    
    // If status is completed but lock file still exists, remove it
    if (($status['status'] ?? '') === 'completed' && $isRunning) {
        @unlink(FETCH_LOCK_FILE);
    }
    
    return $status;
}

function getMetadataLocal(): array {
    $metadataFile = OUTPUT_DIR . '/pages_metadata.json';
    
    if (!file_exists($metadataFile)) {
        return [
            'total_videos' => 0,
            'total_pages' => 0,
            'last_updated' => 'Never'
        ];
    }
    
    return json_decode(file_get_contents($metadataFile), true) ?? [];
}

function startBackgroundFetchLocal(): bool {
    if (isFetchRunningLocal()) {
        return false;
    }
    
    // Create lock file
    file_put_contents(FETCH_LOCK_FILE, time());
    
    // Update status - let fetch_background.php set the initial status
    // Just mark as running so UI knows process is starting
    $status = [
        'status' => 'running',
        'started_at' => date('Y-m-d H:i:s'),
        'message' => 'Starting fetch process...',
        'progress' => 0,
        'total_videos' => 0
    ];
    file_put_contents(FETCH_STATUS_FILE, json_encode($status, JSON_PRETTY_PRINT));
    
    // Start background process
    $scriptPath = API_DIR . '/core/classes/fetch_background.php';
    $outputPath = FETCH_LOG_FILE;
    
    // Find PHP executable
    $phpBinary = PHP_BINARY;
    
    // Log the start attempt
    $logMsg = "[" . date('Y-m-d H:i:s') . "] Starting fetch process...\n";
    $logMsg .= "PHP Binary: {$phpBinary}\n";
    $logMsg .= "Script Path: {$scriptPath}\n";
    $logMsg .= "Script Exists: " . (file_exists($scriptPath) ? 'YES' : 'NO') . "\n";
    file_put_contents($outputPath, $logMsg, FILE_APPEND);
    
    // Check OS for proper command
    if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        // Windows: Use cmd /c start /B for background execution
        $command = 'start /B "" "' . $phpBinary . '" "' . $scriptPath . '" >> "' . $outputPath . '" 2>&1';
        pclose(popen($command, 'r'));
    } else {
        // Linux/Unix command
        $command = escapeshellarg($phpBinary) . ' ' . escapeshellarg($scriptPath) . ' >> ' . escapeshellarg($outputPath) . ' 2>&1 &';
        shell_exec($command);
    }
    
    return true;
}

function testConfigurationLocal(): array {
    $tests = [];
    
    // Test PHP Binary
    $phpBinary = PHP_BINARY;
    $tests['php_binary'] = [
        'path' => $phpBinary,
        'exists' => file_exists($phpBinary),
        'version' => PHP_VERSION
    ];
    
    // Test Script Path
    $scriptPath = API_DIR . '/core/classes/fetch_background.php';
    $tests['script'] = [
        'path' => $scriptPath,
        'exists' => file_exists($scriptPath),
        'readable' => is_readable($scriptPath)
    ];
    
    // Test API Directory
    $tests['api_dir'] = [
        'path' => API_DIR,
        'exists' => is_dir(API_DIR),
        'writable' => is_writable(API_DIR)
    ];
    
    // Test Output Directory
    $tests['output_dir'] = [
        'path' => OUTPUT_DIR,
        'exists' => is_dir(OUTPUT_DIR),
        'writable' => is_writable(OUTPUT_DIR)
    ];
    
    // Test Status File
    $tests['status_file'] = [
        'path' => FETCH_STATUS_FILE,
        'exists' => file_exists(FETCH_STATUS_FILE),
        'writable' => is_writable(dirname(FETCH_STATUS_FILE))
    ];
    
    // Test Lock File
    $tests['lock_file'] = [
        'path' => FETCH_LOCK_FILE,
        'exists' => file_exists(FETCH_LOCK_FILE)
    ];
    
    // Test OS
    $tests['os'] = [
        'php_os' => PHP_OS,
        'is_windows' => strtoupper(substr(PHP_OS, 0, 3)) === 'WIN'
    ];
    
    return $tests;
}

function formatSizeUnits($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        return $bytes . ' bytes';
    } elseif ($bytes == 1) {
        return $bytes . ' byte';
    } else {
        return '0 bytes';
    }
}

// Handle AJAX requests BEFORE any HTML output
if (isset($_GET['ajax'])) {
    header('Content-Type: application/json');
    
    switch ($_GET['ajax']) {
        case 'status':
            $status = getFetchStatusLocal();
            $metadata = getMetadataLocal();
            
            echo json_encode([
                'success' => true,
                'fetch_status' => $status,
                'is_running' => isFetchRunningLocal(),
                'total_videos' => $metadata['total_videos'] ?? 0,
                'total_pages' => $metadata['total_pages'] ?? 0,
                'last_updated' => $metadata['last_updated'] ?? 'Never'
            ]);
            exit;
            
        case 'start':
            $result = startBackgroundFetchLocal();
            echo json_encode([
                'success' => $result,
                'message' => $result ? 'Fetch process started successfully' : 'Fetch process is already running'
            ]);
            exit;
            
        case 'stop':
            // Force stop by removing lock file
            if (file_exists(FETCH_LOCK_FILE)) {
                @unlink(FETCH_LOCK_FILE);
            }
            
            // Update status
            if (file_exists(FETCH_STATUS_FILE)) {
                $status = json_decode(file_get_contents(FETCH_STATUS_FILE), true) ?? [];
                $status['status'] = 'stopped';
                $status['message'] = 'Fetch process was manually stopped';
                $status['stopped_at'] = date('Y-m-d H:i:s');
                file_put_contents(FETCH_STATUS_FILE, json_encode($status, JSON_PRETTY_PRINT));
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'Fetch process stopped'
            ]);
            exit;
            
        case 'logs':
            $logs = '';
            if (file_exists(FETCH_LOG_FILE)) {
                $logs = file_get_contents(FETCH_LOG_FILE);
                // Get last 100 lines
                $lines = explode("\n", $logs);
                $logs = implode("\n", array_slice($lines, -100));
            }
            echo json_encode([
                'success' => true,
                'logs' => $logs ?: 'No logs available'
            ]);
            exit;
            
        case 'clear_logs':
            if (file_exists(FETCH_LOG_FILE)) {
                file_put_contents(FETCH_LOG_FILE, '');
            }
            echo json_encode([
                'success' => true,
                'message' => 'Logs cleared'
            ]);
            exit;
            
        case 'test':
            $tests = testConfigurationLocal();
            echo json_encode([
                'success' => true,
                'tests' => $tests
            ]);
            exit;
            
        case 'run_sync':
            // Run fetch synchronously for testing (will block!)
            $scriptPath = API_DIR . '/core/classes/fetch_background.php';
            $output = [];
            $returnCode = 0;
            
            // Create lock file
            file_put_contents(FETCH_LOCK_FILE, time());
            
            // Update status
            $status = [
                'status' => 'running',
                'started_at' => date('Y-m-d H:i:s'),
                'message' => 'Running fetch (sync mode)...',
                'progress' => 5,
                'total_videos' => 0
            ];
            file_put_contents(FETCH_STATUS_FILE, json_encode($status, JSON_PRETTY_PRINT));
            
            // Execute synchronously
            exec('"' . PHP_BINARY . '" "' . $scriptPath . '" 2>&1', $output, $returnCode);
            
            echo json_encode([
                'success' => $returnCode === 0,
                'return_code' => $returnCode,
                'output' => implode("\n", array_slice($output, -50)) // Last 50 lines
            ]);
            exit;
            
        default:
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
            exit;
    }
}

// Now include header for non-AJAX requests (this outputs HTML)
$title_panel = 'Output Data Manager';
require 'includes/header.php';

// Get initial data for page display
$fetchStatus = getFetchStatusLocal();
$isRunning = isFetchRunningLocal();
$metadata = getMetadataLocal();

if (isset($_SESSION['login']) && $_SESSION['login'] == $hash) { 
?>

<style>
.status-indicator {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    display: inline-block;
    margin-right: 8px;
}
.status-idle { background-color: #6c757d; }
.status-running { background-color: #28a745; animation: pulse 1.5s infinite; }
.status-completed { background-color: #17a2b8; }
.status-error { background-color: #dc3545; }
.status-stopped { background-color: #ffc107; }

@keyframes pulse {
    0% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.6; transform: scale(1.1); }
    100% { opacity: 1; transform: scale(1); }
}

.progress-wrapper {
    background-color: #e9ecef;
    border-radius: 8px;
    overflow: hidden;
    height: 24px;
}
.progress-bar-animated {
    transition: width 0.3s ease-out;
}
.log-container {
    background-color: #1a1a2e;
    color: #0f0;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    padding: 15px;
    border-radius: 8px;
    height: 300px;
    overflow-y: auto;
    white-space: pre-wrap;
    word-wrap: break-word;
}
.stat-card {
    transition: transform 0.2s;
}
.stat-card:hover {
    transform: translateY(-2px);
}
.btn-fetch {
    min-width: 150px;
}
.refresh-spin {
    animation: spin 1s linear infinite;
}
@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>

<div class="container-fluid">
    <!-- Page Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-database mr-2"></i>Output Data Manager
        </h1>
        <div>
            <button onclick="refreshStatus()" class="btn btn-sm btn-outline-secondary shadow-sm mr-2" id="refreshBtn">
                <i class="fas fa-sync-alt fa-sm" id="refreshIcon"></i> Refresh
            </button>
            <a href="index.php" class="btn btn-sm btn-secondary shadow-sm">
                <i class="fas fa-arrow-left fa-sm text-white-50"></i> Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Status Alert -->
    <div id="alertContainer"></div>

    <!-- Status Overview Cards -->
    <div class="row">
        <!-- Fetch Status Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Fetch Status</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <span class="status-indicator status-<?php echo htmlspecialchars($fetchStatus['status'] ?? 'idle'); ?>" id="statusIndicator"></span>
                                <span id="statusText"><?php echo ucfirst(htmlspecialchars($fetchStatus['status'] ?? 'Idle')); ?></span>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-clock fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Videos Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Videos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalVideos">
                                <?php echo number_format($metadata['total_videos'] ?? 0); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-video fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Total Pages Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Data Pages</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="totalPages">
                                <?php echo number_format($metadata['total_pages'] ?? 0); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-file-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Last Updated Card -->
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2 stat-card">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Last Updated</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800" id="lastUpdated">
                                <?php echo htmlspecialchars($metadata['last_updated'] ?? 'Never'); ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-calendar-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Row -->
    <div class="row">
        <!-- Fetch Control Panel -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-cogs mr-2"></i>Fetch Control Panel
                    </h6>
                </div>
                <div class="card-body">
                    <!-- Progress Bar -->
                    <div class="mb-4">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="font-weight-bold">Progress</span>
                            <span id="progressPercent"><?php echo $fetchStatus['progress'] ?? 0; ?>%</span>
                        </div>
                        <div class="progress-wrapper">
                            <div class="progress-bar progress-bar-striped progress-bar-animated bg-primary" 
                                 role="progressbar" 
                                 id="progressBar"
                                 style="width: <?php echo $fetchStatus['progress'] ?? 0; ?>%; height: 100%;">
                            </div>
                        </div>
                    </div>

                    <!-- Status Message -->
                    <div class="alert alert-secondary mb-4" id="statusMessage">
                        <i class="fas fa-info-circle mr-2"></i>
                        <span id="messageText"><?php echo htmlspecialchars($fetchStatus['message'] ?? 'Ready to fetch data'); ?></span>
                    </div>

                    <!-- Control Buttons -->
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <button type="button" 
                                    class="btn btn-success btn-block btn-fetch" 
                                    id="startBtn"
                                    onclick="startFetch()"
                                    <?php echo $isRunning ? 'disabled' : ''; ?>>
                                <i class="fas fa-play mr-2"></i>Start Fetch
                            </button>
                        </div>
                        <div class="col-md-6 mb-3">
                            <button type="button" 
                                    class="btn btn-danger btn-block btn-fetch" 
                                    id="stopBtn"
                                    onclick="stopFetch()"
                                    <?php echo !$isRunning ? 'disabled' : ''; ?>>
                                <i class="fas fa-stop mr-2"></i>Stop Fetch
                            </button>
                        </div>
                    </div>

                    <!-- Additional Info -->
                    <hr>
                    <div class="row text-center">
                        <div class="col-4">
                            <small class="text-muted">Started</small>
                            <p class="mb-0 font-weight-bold" id="startedAt">
                                <?php echo htmlspecialchars($fetchStatus['started_at'] ?? '-'); ?>
                            </p>
                        </div>
                        <div class="col-4">
                            <small class="text-muted">Completed</small>
                            <p class="mb-0 font-weight-bold" id="completedAt">
                                <?php echo htmlspecialchars($fetchStatus['completed_at'] ?? '-'); ?>
                            </p>
                        </div>
                        <div class="col-4">
                            <small class="text-muted">Videos Found</small>
                            <p class="mb-0 font-weight-bold" id="videosFound">
                                <?php echo number_format($fetchStatus['total_videos'] ?? 0); ?>
                            </p>
                        </div>
                    </div>

                    <!-- Preservation Stats -->
                    <hr>
                    <div class="row text-center">
                        <div class="col-6">
                            <span class="badge badge-success py-2 px-3">
                                <i class="fas fa-check mr-1"></i>
                                Preserved: <span id="preservedTitles"><?php echo number_format($fetchStatus['preserved_titles'] ?? 0); ?></span>
                            </span>
                        </div>
                        <div class="col-6">
                            <span class="badge badge-info py-2 px-3">
                                <i class="fas fa-plus mr-1"></i>
                                New: <span id="newVideos"><?php echo number_format($fetchStatus['new_videos'] ?? 0); ?></span>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- API Sources Info -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-server mr-2"></i>API Sources
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>Source</th>
                                    <th>API URL</th>
                                    <th>Per Page</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td><strong>Doodstream</strong></td>
                                    <td><small>doodapi.com/api/file/list</small></td>
                                    <td>200</td>
                                    <td><span class="badge badge-success">Active</span></td>
                                </tr>
                                <tr>
                                    <td><strong>Lulustream</strong></td>
                                    <td><small>api.lulustream.com/api/file/list</small></td>
                                    <td>500</td>
                                    <td><span class="badge badge-success">Active</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <hr>

                    <!-- Quick Actions -->
                    <h6 class="font-weight-bold mb-3">Quick Actions</h6>
                    <div class="btn-group-vertical btn-block">
                        <a href="../output/" target="_blank" class="btn btn-outline-primary btn-sm mb-2">
                            <i class="fas fa-folder-open mr-2"></i>Browse Output Files
                        </a>
                        <button onclick="viewLogs()" class="btn btn-outline-info btn-sm mb-2" data-toggle="modal" data-target="#logsModal">
                            <i class="fas fa-file-alt mr-2"></i>View Fetch Logs
                        </button>
                        <button onclick="clearLogs()" class="btn btn-outline-warning btn-sm mb-2">
                            <i class="fas fa-eraser mr-2"></i>Clear Logs
                        </button>
                        <button onclick="testConfig()" class="btn btn-outline-secondary btn-sm mb-2" data-toggle="modal" data-target="#testModal">
                            <i class="fas fa-stethoscope mr-2"></i>Test Configuration
                        </button>
                        <button onclick="runSync()" class="btn btn-outline-danger btn-sm" id="syncBtn">
                            <i class="fas fa-bolt mr-2"></i>Run Sync (Debug)
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Data Files Overview -->
    <div class="row">
        <div class="col-lg-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-database mr-2"></i>Data Files Overview
                    </h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover" id="dataFilesTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Filename</th>
                                    <th>Videos</th>
                                    <th>Size</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $totalPages = $metadata['total_pages'] ?? 0;
                                $videosPerPage = $metadata['videos_per_page'] ?? 200;
                                $totalVideos = $metadata['total_videos'] ?? 0;
                                
                                if ($totalPages > 0 && OUTPUT_DIR) {
                                    for ($i = 1; $i <= min($totalPages, 10); $i++) {
                                        $pageFile = OUTPUT_DIR . "/data_page_{$i}.json";
                                        $size = file_exists($pageFile) ? formatSizeUnits(filesize($pageFile)) : '0 KB';
                                        $videosInPage = min($videosPerPage, $totalVideos - (($i - 1) * $videosPerPage));
                                        if ($videosInPage < 0) $videosInPage = 0;
                                        echo "<tr>
                                            <td>{$i}</td>
                                            <td><code>data_page_{$i}.json</code></td>
                                            <td>{$videosInPage}</td>
                                            <td>{$size}</td>
                                            <td>
                                                <a href='../output/data_page_{$i}.json' target='_blank' class='btn btn-sm btn-outline-primary'>
                                                    <i class='fas fa-eye'></i>
                                                </a>
                                            </td>
                                        </tr>";
                                    }
                                    
                                    if ($totalPages > 10) {
                                        echo "<tr>
                                            <td colspan='5' class='text-center text-muted'>
                                                ... and " . ($totalPages - 10) . " more files
                                            </td>
                                        </tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='5' class='text-center text-muted'>No data files found. Click 'Start Fetch' to begin.</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Logs Modal -->
<div class="modal fade" id="logsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-file-alt mr-2"></i>Fetch Logs
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="log-container" id="logContent">
                    Loading logs...
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-warning" onclick="clearLogs()">
                    <i class="fas fa-eraser mr-1"></i>Clear Logs
                </button>
                <button type="button" class="btn btn-info" onclick="viewLogs()">
                    <i class="fas fa-sync-alt mr-1"></i>Refresh
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Test Configuration Modal -->
<div class="modal fade" id="testModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-stethoscope mr-2"></i>Configuration Test
                </h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="testResults">
                    <p class="text-center"><i class="fas fa-spinner fa-spin"></i> Running tests...</p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-info" onclick="testConfig()">
                    <i class="fas fa-sync-alt mr-1"></i>Re-run Tests
                </button>
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
let autoRefreshInterval = null;
let isRunning = <?php echo $isRunning ? 'true' : 'false'; ?>;
let lastProgress = 0;
let refreshInterval = 1000; // Start with 1 second for real-time updates

// Wait for jQuery to be loaded
function waitForJQuery(callback) {
    if (typeof jQuery !== 'undefined' && typeof $ !== 'undefined') {
        callback();
    } else {
        setTimeout(function() {
            waitForJQuery(callback);
        }, 50);
    }
}

// Initialize after jQuery is loaded
waitForJQuery(function() {
    $(document).ready(function() {
        if (isRunning) {
            startAutoRefresh();
        }
        // Also start auto-refresh on page load to keep status updated
        refreshStatus();
    });
});

function startAutoRefresh() {
    if (autoRefreshInterval) clearInterval(autoRefreshInterval);
    // Use dynamic interval: faster when running, slower when idle
    refreshInterval = isRunning ? 1000 : 5000; // 1 second when running, 5 seconds when idle
    autoRefreshInterval = setInterval(refreshStatus, refreshInterval);
}

function stopAutoRefresh() {
    if (autoRefreshInterval) {
        clearInterval(autoRefreshInterval);
        autoRefreshInterval = null;
    }
}

function refreshStatus() {
    if (typeof $ === 'undefined') return; // Safety check
    
    $('#refreshIcon').addClass('refresh-spin');
    
    $.ajax({
        url: 'output_manager.php?ajax=status',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                updateUI(data);
            }
        },
        error: function(xhr, status, error) {
            console.error('Refresh error:', error);
        },
        complete: function() {
            setTimeout(function() {
                $('#refreshIcon').removeClass('refresh-spin');
            }, 500);
        }
    });
}

function updateUI(data) {
    if (typeof $ === 'undefined') return; // Safety check
    
    const status = data.fetch_status;
    const wasRunning = isRunning;
    isRunning = data.is_running;
    
    // Update status indicator
    $('#statusIndicator').removeClass('status-idle status-running status-completed status-error status-stopped')
                         .addClass('status-' + (status.status || 'idle'));
    $('#statusText').text(capitalizeFirst(status.status || 'Idle'));
    
    // Update progress with smooth animation
    const progress = status.progress || 0;
    const progressChanged = Math.abs(progress - lastProgress) > 0.1; // Detect significant change
    
    // If status is completed or error, ensure isRunning is false
    if (status.status === 'completed' || status.status === 'error') {
        isRunning = false;
    }
    
    // Smooth progress bar update
    $('#progressBar').css({
        'width': progress + '%',
        'transition': 'width 0.3s ease-out'
    });
    $('#progressPercent').text(Math.round(progress) + '%');
    
    // If progress is actively changing, refresh more frequently
    if (progressChanged && isRunning && refreshInterval > 500) {
        lastProgress = progress;
        // Restart with faster interval if progress is changing
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            refreshInterval = 500; // 500ms when progress is actively changing
            autoRefreshInterval = setInterval(refreshStatus, refreshInterval);
        }
    } else if (!progressChanged && isRunning && refreshInterval < 1000) {
        // Slow down if progress hasn't changed
        lastProgress = progress;
        if (autoRefreshInterval) {
            clearInterval(autoRefreshInterval);
            refreshInterval = 1000; // Back to 1 second
            autoRefreshInterval = setInterval(refreshStatus, refreshInterval);
        }
    } else {
        lastProgress = progress;
    }
    
    // Update message
    $('#messageText').text(status.message || 'Ready to fetch data');
    
    // Update stats
    $('#totalVideos').text(numberFormat(data.total_videos || 0));
    $('#totalPages').text(numberFormat(data.total_pages || 0));
    $('#lastUpdated').text(data.last_updated || 'Never');
    
    // Update additional info
    $('#startedAt').text(status.started_at || '-');
    $('#completedAt').text(status.completed_at || '-');
    $('#videosFound').text(numberFormat(status.total_videos || 0));
    
    // Update preservation stats
    if (status.preserved_titles !== undefined) {
        $('#preservedTitles').text(numberFormat(status.preserved_titles));
    }
    if (status.new_videos !== undefined) {
        $('#newVideos').text(numberFormat(status.new_videos));
    }
    
    // Update buttons
    $('#startBtn').prop('disabled', isRunning);
    $('#stopBtn').prop('disabled', !isRunning);
    
    // Auto refresh handling - restart if status changed
    // Stop auto-refresh if status is completed or error
    if (status.status === 'completed' || status.status === 'error') {
        isRunning = false;
        stopAutoRefresh();
    } else if (isRunning && !autoRefreshInterval) {
        startAutoRefresh();
    } else if (!isRunning && autoRefreshInterval) {
        stopAutoRefresh();
    } else if (wasRunning !== isRunning) {
        // Status changed, restart with appropriate interval
        if (isRunning) {
            startAutoRefresh();
        } else {
            stopAutoRefresh();
        }
    }
}

function startFetch() {
    if (typeof $ === 'undefined') {
        alert('jQuery is not loaded yet. Please wait a moment and try again.');
        return;
    }
    
    $('#startBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Starting...');
    
    $.ajax({
        url: 'output_manager.php?ajax=start',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                showAlert(data.message, 'success');
                isRunning = true;
                lastProgress = 0; // Reset progress tracking
                startAutoRefresh();
                // Start immediate refresh and continue with real-time updates
                setTimeout(refreshStatus, 500);
            } else {
                showAlert(data.message, 'warning');
            }
        },
        error: function(xhr, status, error) {
            showAlert('Failed to start fetch process: ' + error, 'danger');
        },
        complete: function() {
            $('#startBtn').html('<i class="fas fa-play mr-2"></i>Start Fetch');
            refreshStatus();
        }
    });
}

function stopFetch() {
    if (typeof $ === 'undefined') {
        alert('jQuery is not loaded yet. Please wait a moment and try again.');
        return;
    }
    
    if (!confirm('Are you sure you want to stop the fetch process?')) return;
    
    $('#stopBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Stopping...');
    
    $.ajax({
        url: 'output_manager.php?ajax=stop',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                showAlert(data.message, 'info');
                isRunning = false;
                stopAutoRefresh();
            }
        },
        error: function(xhr, status, error) {
            showAlert('Failed to stop fetch process: ' + error, 'danger');
        },
        complete: function() {
            $('#stopBtn').html('<i class="fas fa-stop mr-2"></i>Stop Fetch');
            refreshStatus();
        }
    });
}

function viewLogs() {
    if (typeof $ === 'undefined') {
        alert('jQuery is not loaded yet. Please wait a moment and try again.');
        return;
    }
    
    $('#logContent').html('<i class="fas fa-spinner fa-spin"></i> Loading logs...');
    
    $.ajax({
        url: 'output_manager.php?ajax=logs',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                const logs = data.logs || 'No logs available';
                $('#logContent').text(logs);
                // Scroll to bottom
                const logContainer = document.getElementById('logContent');
                logContainer.scrollTop = logContainer.scrollHeight;
            }
        },
        error: function(xhr, status, error) {
            $('#logContent').text('Failed to load logs: ' + error);
        }
    });
}

function clearLogs() {
    if (typeof $ === 'undefined') {
        alert('jQuery is not loaded yet. Please wait a moment and try again.');
        return;
    }
    
    if (!confirm('Are you sure you want to clear all logs?')) return;
    
    $.ajax({
        url: 'output_manager.php?ajax=clear_logs',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                showAlert(data.message, 'success');
                $('#logContent').text('Logs cleared');
            }
        },
        error: function(xhr, status, error) {
            showAlert('Failed to clear logs: ' + error, 'danger');
        }
    });
}

function showAlert(message, type) {
    if (typeof $ === 'undefined') {
        alert(message); // Fallback to native alert
        return;
    }
    
    const alertHtml = `
        <div class="alert alert-${type} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="close" data-dismiss="alert">
                <span>&times;</span>
            </button>
        </div>
    `;
    $('#alertContainer').html(alertHtml);
    
    // Auto dismiss after 5 seconds
    setTimeout(function() {
        if (typeof $ !== 'undefined') {
            $('#alertContainer .alert').alert('close');
        }
    }, 5000);
}

function capitalizeFirst(string) {
    return string.charAt(0).toUpperCase() + string.slice(1);
}

function numberFormat(num) {
    return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

function testConfig() {
    if (typeof $ === 'undefined') {
        alert('jQuery is not loaded yet. Please wait a moment and try again.');
        return;
    }
    
    $('#testResults').html('<p class="text-center"><i class="fas fa-spinner fa-spin"></i> Running tests...</p>');
    
    $.ajax({
        url: 'output_manager.php?ajax=test',
        type: 'GET',
        dataType: 'json',
        success: function(data) {
            if (data.success) {
                let html = '<table class="table table-sm table-bordered">';
                html += '<thead><tr><th>Test</th><th>Path/Value</th><th>Status</th></tr></thead><tbody>';
                
                for (const [key, value] of Object.entries(data.tests)) {
                    const name = key.replace(/_/g, ' ').toUpperCase();
                    let status = '';
                    let path = '';
                    
                    if (typeof value === 'object') {
                        path = value.path || value.php_os || JSON.stringify(value);
                        
                        if (value.exists !== undefined) {
                            status = value.exists ? 
                                '<span class="badge badge-success">EXISTS</span>' : 
                                '<span class="badge badge-danger">NOT FOUND</span>';
                        }
                        if (value.writable !== undefined) {
                            status += ' ' + (value.writable ? 
                                '<span class="badge badge-success">WRITABLE</span>' : 
                                '<span class="badge badge-warning">NOT WRITABLE</span>');
                        }
                        if (value.readable !== undefined) {
                            status += ' ' + (value.readable ? 
                                '<span class="badge badge-success">READABLE</span>' : 
                                '<span class="badge badge-danger">NOT READABLE</span>');
                        }
                        if (value.version !== undefined) {
                            status += ' <span class="badge badge-info">v' + value.version + '</span>';
                        }
                        if (value.is_windows !== undefined) {
                            status = value.is_windows ? 
                                '<span class="badge badge-primary">Windows</span>' : 
                                '<span class="badge badge-secondary">Linux/Unix</span>';
                        }
                    }
                    
                    html += '<tr><td><strong>' + name + '</strong></td>';
                    html += '<td><small style="word-break: break-all;">' + path + '</small></td>';
                    html += '<td>' + status + '</td></tr>';
                }
                
                html += '</tbody></table>';
                $('#testResults').html(html);
            }
        },
        error: function(xhr, status, error) {
            $('#testResults').html('<div class="alert alert-danger">Test failed: ' + error + '</div>');
        }
    });
}

function runSync() {
    if (typeof $ === 'undefined') {
        alert('jQuery is not loaded yet. Please wait a moment and try again.');
        return;
    }
    
    if (!confirm('This will run the fetch process SYNCHRONOUSLY and may take several minutes. The page will be unresponsive until it completes. Continue?')) {
        return;
    }
    
    $('#syncBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-2"></i>Running...');
    showAlert('Fetch process running synchronously. Please wait...', 'info');
    
    $.ajax({
        url: 'output_manager.php?ajax=run_sync',
        type: 'GET',
        dataType: 'json',
        timeout: 600000, // 10 minutes timeout
        success: function(data) {
            if (data.success) {
                showAlert('Sync fetch completed successfully!', 'success');
            } else {
                showAlert('Sync fetch failed. Return code: ' + data.return_code, 'danger');
            }
            
            // Show output in logs modal
            if (data.output) {
                $('#logContent').text(data.output);
                $('#logsModal').modal('show');
            }
            
            refreshStatus();
        },
        error: function(xhr, status, error) {
            showAlert('Sync fetch error: ' + error, 'danger');
        },
        complete: function() {
            $('#syncBtn').prop('disabled', false).html('<i class="fas fa-bolt mr-2"></i>Run Sync (Debug)');
        }
    });
}
</script>

<?php 
require 'includes/footer.php';
} else {
    header('Location: ' . lite_url('login.php'));
    exit;
}
?>
