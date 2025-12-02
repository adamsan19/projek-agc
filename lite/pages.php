<?php
define('IDC', dirname(__FILE__));
$title_panel = 'Output Data Manager';
require 'includes/header.php';

if (isset($_SESSION['login']) && $_SESSION['login'] == $hash) {
    $output_dir = '../output/';
    $metadata_file = $output_dir . 'pages_metadata.json';
    $message = '';
    $message_type = '';

    // Load metadata
    $metadata = [];
    if (file_exists($metadata_file)) {
        $metadata = json_decode(file_get_contents($metadata_file), true);
    }

    // Function to update metadata
    function updateMetadata($output_dir, $metadata_file, $page, $filecode, $title, $action = 'update') {
        $metadata = [];
        if (file_exists($metadata_file)) {
            $metadata = json_decode(file_get_contents($metadata_file), true);
        }
        
        // Preserve existing structure
        if (!isset($metadata['total_pages'])) {
            $metadata['total_pages'] = 140;
        }
        if (!isset($metadata['videos_per_page'])) {
            $metadata['videos_per_page'] = 200;
        }
        if (!isset($metadata['pages'])) {
            $metadata['pages'] = [];
            for ($i = 1; $i <= $metadata['total_pages']; $i++) {
                $metadata['pages'][$i] = "data_page_{$i}.json";
            }
        }
        
        // Initialize maps if not exists
        if (!isset($metadata['filecode_title_map'])) {
            $metadata['filecode_title_map'] = [];
        }
        if (!isset($metadata['filecode_page_map'])) {
            $metadata['filecode_page_map'] = [];
        }
        
        if ($action === 'update' || $action === 'add') {
            // Update maps
            $metadata['filecode_title_map'][$filecode] = $title;
            $metadata['filecode_page_map'][$filecode] = $page;
        } elseif ($action === 'delete') {
            // Remove from maps
            unset($metadata['filecode_title_map'][$filecode]);
            unset($metadata['filecode_page_map'][$filecode]);
        }
        
        // Update last_updated timestamp
        $metadata['last_updated'] = date('Y-m-d H:i:s');
        
        // Recalculate total_videos by counting all pages
        $total_videos = 0;
        $total_pages = $metadata['total_pages'] ?? 140;
        for ($i = 1; $i <= $total_pages; $i++) {
            $page_file = $output_dir . "data_page_{$i}.json";
            if (file_exists($page_file)) {
                $page_data = json_decode(file_get_contents($page_file), true);
                if (is_array($page_data)) {
                    $total_videos += count($page_data);
                }
            }
        }
        $metadata['total_videos'] = $total_videos;
        
        // Save metadata to output folder
        file_put_contents($metadata_file, json_encode($metadata, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        
        return $metadata;
    }

    // Get current page and search query (needed for redirects)
    $current_page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $search_query = isset($_GET['search']) ? trim($_GET['search']) : '';

    // Handle actions
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'save':
                if (isset($_POST['page']) && isset($_POST['original_filecode']) && isset($_POST['data'])) {
                    $page = (int)$_POST['page'];
                    $original_filecode = $_POST['original_filecode'];
                    $data = json_decode($_POST['data'], true);
                    
                    // Validate JSON data
                    if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                        $message = "Error: Data JSON tidak valid - " . json_last_error_msg();
                        $message_type = 'danger';
                    } else {
                        // Ensure file_code matches filecode
                        $data['file_code'] = $data['filecode'] ?? $data['file_code'] ?? '';
                        
                        $page_file = $output_dir . "data_page_{$page}.json";
                        if (file_exists($page_file)) {
                            $page_data = json_decode(file_get_contents($page_file), true);
                            if ($page_data === null) {
                                $message = "Error: File data_page_{$page}.json tidak valid atau corrupt!";
                                $message_type = 'danger';
                            } else {
                                $found = false;
                                // Find item by original filecode
                                foreach ($page_data as $idx => $item) {
                                    if (($item['filecode'] ?? '') === $original_filecode) {
                                        $found = true;
                                        $old_filecode = $item['filecode'] ?? '';
                                        $old_title = $item['title'] ?? '';
                                        
                                        // Update the data
                                        $page_data[$idx] = $data;
                                        
                                        // Save to output folder
                                        $json_content = json_encode($page_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                                        $save_result = @file_put_contents($page_file, $json_content);
                                        
                                        if ($save_result === false) {
                                            $message = "Error: Gagal menyimpan file! Periksa permission folder output.";
                                            $message_type = 'danger';
                                        } else {
                                            // Update metadata
                                            $new_filecode = $data['filecode'] ?? '';
                                            $new_title = $data['title'] ?? '';
                                            
                                            // If filecode changed, remove old entry from metadata
                                            if ($old_filecode !== $new_filecode && !empty($old_filecode)) {
                                                updateMetadata($output_dir, $metadata_file, $page, $old_filecode, $old_title, 'delete');
                                            }
                                            
                                            // Update metadata with new data
                                            if (!empty($new_filecode) && !empty($new_title)) {
                                                updateMetadata($output_dir, $metadata_file, $page, $new_filecode, $new_title, 'update');
                                            }
                                            
                                            // Reload metadata
                                            $metadata = json_decode(file_get_contents($metadata_file), true);
                                            
                                            $message = "Data berhasil disimpan ke folder output!";
                                            $message_type = 'success';
                                            
                                            // Redirect to prevent resubmission (use POST page, fallback to GET)
                                            $redirect_page = isset($_POST['page']) ? (int)$_POST['page'] : $current_page;
                                            $redirect_query = isset($_GET['search']) ? trim($_GET['search']) : '';
                                            $redirect_url = "pages.php?page={$redirect_page}";
                                            if (!empty($redirect_query)) {
                                                $redirect_url .= "&search=" . urlencode($redirect_query);
                                            }
                                            header("Location: " . $redirect_url);
                                            exit;
                                        }
                                        break;
                                    }
                                }
                                
                                if (!$found) {
                                    $message = "Error: Data dengan filecode '{$original_filecode}' tidak ditemukan di halaman {$page}!";
                                    $message_type = 'danger';
                                }
                            }
                        } else {
                            $message = "Error: File data_page_{$page}.json tidak ditemukan!";
                            $message_type = 'danger';
                        }
                    }
                } else {
                    $message = "Error: Data tidak lengkap! Pastikan semua field terisi.";
                    $message_type = 'danger';
                }
                break;

            case 'add':
                if (isset($_POST['page']) && isset($_POST['data'])) {
                    $page = (int)$_POST['page'];
                    $data = json_decode($_POST['data'], true);
                    
                    // Ensure file_code matches filecode
                    $data['file_code'] = $data['filecode'] ?? $data['file_code'] ?? '';
                    
                    $page_file = $output_dir . "data_page_{$page}.json";
                    if (file_exists($page_file)) {
                        $page_data = json_decode(file_get_contents($page_file), true);
                        if (!is_array($page_data)) {
                            $page_data = [];
                        }
                        $page_data[] = $data;
                        
                        // Save to output folder
                        file_put_contents($page_file, json_encode($page_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                        
                        // Update metadata
                        $filecode = $data['filecode'] ?? '';
                        $title = $data['title'] ?? '';
                        if (!empty($filecode) && !empty($title)) {
                            updateMetadata($output_dir, $metadata_file, $page, $filecode, $title, 'add');
                        }
                        
                        // Reload metadata
                        $metadata = json_decode(file_get_contents($metadata_file), true);
                        
                        $message = "Data baru berhasil ditambahkan ke folder output!";
                        $message_type = 'success';
                        
                        // Redirect to prevent resubmission (use POST page, fallback to GET)
                        $redirect_page = isset($_POST['page']) ? (int)$_POST['page'] : $current_page;
                        $redirect_query = isset($_GET['search']) ? trim($_GET['search']) : '';
                        $redirect_url = "pages.php?page={$redirect_page}";
                        if (!empty($redirect_query)) {
                            $redirect_url .= "&search=" . urlencode($redirect_query);
                        }
                        header("Location: " . $redirect_url);
                        exit;
                    } else {
                        $message = "Error: File data_page_{$page}.json tidak ditemukan!";
                        $message_type = 'danger';
                    }
                } else {
                    $message = "Error: Data tidak lengkap!";
                    $message_type = 'danger';
                }
                break;

            case 'delete':
                if (isset($_POST['page']) && isset($_POST['filecode'])) {
                    $page = (int)$_POST['page'];
                    $filecode = $_POST['filecode'];
                    
                    $page_file = $output_dir . "data_page_{$page}.json";
                    if (file_exists($page_file)) {
                        $page_data = json_decode(file_get_contents($page_file), true);
                        // Find and remove item by filecode
                        foreach ($page_data as $idx => $item) {
                            if (($item['filecode'] ?? '') === $filecode) {
                                $title = $item['title'] ?? '';
                                
                                unset($page_data[$idx]);
                                $page_data = array_values($page_data); // Re-index array
                                
                                // Save to output folder
                                file_put_contents($page_file, json_encode($page_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
                                
                                // Update metadata - remove from maps
                                if (!empty($filecode)) {
                                    updateMetadata($output_dir, $metadata_file, $page, $filecode, $title, 'delete');
                                }
                                
                                // Reload metadata
                                $metadata = json_decode(file_get_contents($metadata_file), true);
                                
                                $message = "Data berhasil dihapus dari folder output!";
                                $message_type = 'success';
                                
                                // Redirect to prevent resubmission (use POST page, fallback to GET)
                                $redirect_page = isset($_POST['page']) ? (int)$_POST['page'] : $current_page;
                                $redirect_query = isset($_GET['search']) ? trim($_GET['search']) : '';
                                $redirect_url = "pages.php?page={$redirect_page}";
                                if (!empty($redirect_query)) {
                                    $redirect_url .= "&search=" . urlencode($redirect_query);
                                }
                                header("Location: " . $redirect_url);
                                exit;
                            }
                        }
                        
                        if (!isset($message)) {
                            $message = "Error: Data dengan filecode '{$filecode}' tidak ditemukan!";
                            $message_type = 'danger';
                        }
                    } else {
                        $message = "Error: File data_page_{$page}.json tidak ditemukan!";
                        $message_type = 'danger';
                    }
                } else {
                    $message = "Error: Data tidak lengkap!";
                    $message_type = 'danger';
                }
                break;
        }
    }

    // Get edit filecode first to determine correct page
    $edit_filecode = isset($_GET['edit']) ? $_GET['edit'] : null;
    $add_new = isset($_GET['add']) ? true : false;
    
    // If editing, find the correct page first
    if ($edit_filecode !== null) {
        // Use metadata to find the page quickly
        if (isset($metadata['filecode_page_map'][$edit_filecode])) {
            $edit_page = $metadata['filecode_page_map'][$edit_filecode];
            $current_page = $edit_page; // Update current page to the correct page
        }
    }
    
    // Load page data
    $page_file = $output_dir . "data_page_{$current_page}.json";
    $page_data = [];
    if (file_exists($page_file)) {
        $page_data = json_decode(file_get_contents($page_file), true);
        if (!is_array($page_data)) {
            $page_data = [];
        }
    }

    // Search functionality - search across all pages if search query exists
    $filtered_data = [];
    $search_results = []; // Will contain items with page info
    
    if (!empty($search_query)) {
        // Use metadata to optimize search
        $filecode_page_map = $metadata['filecode_page_map'] ?? [];
        $filecode_title_map = $metadata['filecode_title_map'] ?? [];
        
        // First, try to find by filecode in metadata (fastest)
        $search_lower = strtolower($search_query);
        $found_pages = [];
        
        // Check if search query matches a filecode
        if (isset($filecode_page_map[$search_query])) {
            $found_pages[] = $filecode_page_map[$search_query];
        }
        
        // Also search in filecode_title_map for partial matches (optimized)
        // Only search if query is specific enough (at least 3 characters)
        if (strlen($search_query) >= 3) {
            $search_count = 0;
            $max_metadata_search = 500; // Limit metadata search for performance
            foreach ($filecode_title_map as $filecode => $title) {
                if ($search_count++ > $max_metadata_search) break;
                if (stripos($filecode, $search_query) !== false || stripos($title, $search_query) !== false) {
                    if (isset($filecode_page_map[$filecode])) {
                        $page_num = $filecode_page_map[$filecode];
                        if (!in_array($page_num, $found_pages)) {
                            $found_pages[] = $page_num;
                        }
                    }
                }
            }
        }
        
        // If not found in metadata maps or search is too broad, search all pages
        if (empty($found_pages) || count($found_pages) > 50) {
            $total_pages = $metadata['total_pages'] ?? 140;
            $found_pages = range(1, min($total_pages, 140));
        }
        
        // Search in found pages
        foreach ($found_pages as $page_num) {
            $search_page_file = $output_dir . "data_page_{$page_num}.json";
            if (file_exists($search_page_file)) {
                $search_page_data = json_decode(file_get_contents($search_page_file), true);
                if (is_array($search_page_data)) {
                    foreach ($search_page_data as $item) {
                        $match = false;
                        
                        // Search in title
                        if (stripos($item['title'] ?? '', $search_query) !== false) {
                            $match = true;
                        }
                        // Search in filecode
                        if (!$match && stripos($item['filecode'] ?? '', $search_query) !== false) {
                            $match = true;
                        }
                        // Search in deskripsi
                        if (!$match && stripos($item['deskripsi'] ?? '', $search_query) !== false) {
                            $match = true;
                        }
                        // Search in tags
                        if (!$match && is_array($item['tag'] ?? [])) {
                            foreach ($item['tag'] as $tag) {
                                if (stripos(strtolower($tag), $search_lower) !== false) {
                                    $match = true;
                                    break;
                                }
                            }
                        }
                        
                        if ($match) {
                            // Add page number to item for display
                            $item['_page_number'] = $page_num;
                            $search_results[] = $item;
                        }
                    }
                }
            }
        }
        
        $filtered_data = $search_results;
    } else {
        // No search query, show current page data
        $filtered_data = $page_data;
        // Add page number to items
        foreach ($filtered_data as &$item) {
            $item['_page_number'] = $current_page;
        }
        unset($item);
    }

    // Get item to edit - search in all pages if needed
    $edit_item = null;
    $edit_page_number = $current_page; // Track which page the edit item is on
    if ($edit_filecode !== null) {
        // First try current page
        foreach ($page_data as $item) {
            if (($item['filecode'] ?? '') === $edit_filecode) {
                $edit_item = $item;
                $edit_page_number = $current_page;
                break;
            }
        }
        
        // If not found, use metadata to find the page
        if ($edit_item === null && isset($metadata['filecode_page_map'][$edit_filecode])) {
            $edit_page = $metadata['filecode_page_map'][$edit_filecode];
            $edit_page_file = $output_dir . "data_page_{$edit_page}.json";
            if (file_exists($edit_page_file)) {
                $edit_page_data = json_decode(file_get_contents($edit_page_file), true);
                if (is_array($edit_page_data)) {
                    foreach ($edit_page_data as $item) {
                        if (($item['filecode'] ?? '') === $edit_filecode) {
                            $edit_item = $item;
                            $edit_page_number = $edit_page;
                            // Update current_page to the correct page for form submission
                            $current_page = $edit_page;
                            // Reload page_data from the correct page
                            $page_data = $edit_page_data;
                            // Reload filtered_data if no search query
                            if (empty($search_query)) {
                                $filtered_data = $page_data;
                                foreach ($filtered_data as &$item) {
                                    $item['_page_number'] = $current_page;
                                }
                                unset($item);
                            }
                            break;
                        }
                    }
                }
            }
        }
        
        // If still not found, search all pages (fallback)
        if ($edit_item === null) {
            $total_pages = $metadata['total_pages'] ?? 140;
            for ($i = 1; $i <= min($total_pages, 140); $i++) {
                $search_page_file = $output_dir . "data_page_{$i}.json";
                if (file_exists($search_page_file)) {
                    $search_page_data = json_decode(file_get_contents($search_page_file), true);
                    if (is_array($search_page_data)) {
                        foreach ($search_page_data as $item) {
                            if (($item['filecode'] ?? '') === $edit_filecode) {
                                $edit_item = $item;
                                $edit_page_number = $i;
                                $current_page = $i;
                                $page_data = $search_page_data;
                                // Reload filtered_data if no search query
                                if (empty($search_query)) {
                                    $filtered_data = $page_data;
                                    foreach ($filtered_data as &$item) {
                                        $item['_page_number'] = $current_page;
                                    }
                                    unset($item);
                                }
                                break 2; // Break both loops
                            }
                        }
                    }
                }
            }
        }
    }

    // Default new item structure
    $new_item = [
        'protected_embed' => '',
        'size' => '',
        'length' => '',
        'protected_dl' => '',
        'views' => '0',
        'single_img' => '',
        'title' => '',
        'status' => '200',
        'uploaded' => date('Y-m-d H:i:s'),
        'last_view' => '',
        'splash_img' => '',
        'filecode' => '',
        'file_code' => '',
        'canplay' => 1,
        'api_source' => 'doodstream',
        'tag' => [],
        'deskripsi' => '',
        'title_preserved' => true,
        'custom_fields' => []
    ];
?>

<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Output Data Manager</h1>
        <a href="pages.php?page=<?php echo $current_page; ?>&add=1" class="d-sm-inline-block btn btn-sm btn-primary shadow-sm">
            <i class="fas fa-plus fa-sm text-white-50"></i> Tambah Data Baru
        </a>
    </div>

    <?php if ($message): ?>
    <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
        <?php echo htmlspecialchars($message); ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
    <?php endif; ?>

    <!-- Search Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Pencarian Data</h6>
        </div>
        <div class="card-body">
            <form method="get" class="form-inline">
                <?php if (empty($search_query)): ?>
                <input type="hidden" name="page" value="<?php echo $current_page; ?>">
                <?php endif; ?>
                <div class="form-group mr-2">
                    <input type="text" name="search" class="form-control" placeholder="Cari di semua halaman berdasarkan title, filecode, deskripsi, atau tag..." value="<?php echo htmlspecialchars($search_query); ?>" style="min-width: 500px;">
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-search"></i> Cari di Semua Halaman
                </button>
                <?php if ($search_query): ?>
                <a href="pages.php?page=<?php echo $current_page; ?>" class="btn btn-secondary ml-2">
                    <i class="fas fa-times"></i> Reset
                </a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Page Navigation -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <?php if (empty($search_query)): ?>
                    <form method="get" class="form-inline">
                        <label class="mr-2">Halaman:</label>
                        <select name="page" class="form-control mr-2" onchange="this.form.submit()">
                            <?php
                            $total_pages = $metadata['total_pages'] ?? 140;
                            for ($i = 1; $i <= min($total_pages, 140); $i++):
                            ?>
                            <option value="<?php echo $i; ?>" <?php echo $current_page == $i ? 'selected' : ''; ?>>
                                Halaman <?php echo $i; ?>
                            </option>
                            <?php endfor; ?>
                        </select>
                    </form>
                    <?php else: ?>
                    <p class="mb-0"><strong>Mode Pencarian:</strong> Mencari di semua halaman</p>
                    <?php endif; ?>
                </div>
                <div class="col-md-6 text-right">
                    <p class="mb-0">
                        <?php if (!empty($search_query)): ?>
                        <strong>Hasil Pencarian:</strong> <span class="badge badge-primary"><?php echo count($filtered_data); ?></span> data ditemukan
                        <?php else: ?>
                        <strong>Total Data di Halaman <?php echo $current_page; ?>:</strong> <span class="badge badge-info"><?php echo count($filtered_data); ?></span>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <?php if ($add_new || $edit_item): ?>
    <!-- Add/Edit Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <?php echo $edit_item ? 'Edit Data' : 'Tambah Data Baru'; ?>
            </h6>
        </div>
        <div class="card-body">
            <form method="post" id="dataForm">
                <input type="hidden" name="action" value="<?php echo $edit_item ? 'save' : 'add'; ?>">
                <input type="hidden" name="page" value="<?php echo $current_page; ?>">
                <?php if ($edit_item): ?>
                <input type="hidden" name="original_filecode" id="original_filecode" value="<?php echo htmlspecialchars($edit_item['filecode'] ?? ''); ?>">
                <?php endif; ?>
                <input type="hidden" name="data" id="formData">

                <?php $item = $edit_item ?: $new_item; ?>
                
                <div class="form-group row">
                    <label class="col-md-2 col-form-label">Title</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control" id="title" value="<?php echo htmlspecialchars($item['title']); ?>" required>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-2 col-form-label">Filecode</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control" id="filecode" value="<?php echo htmlspecialchars($item['filecode']); ?>" required>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-2 col-form-label">Protected Embed URL</label>
                    <div class="col-md-10">
                        <input type="url" class="form-control" id="protected_embed" value="<?php echo htmlspecialchars($item['protected_embed']); ?>" required>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-2 col-form-label">Protected Download URL</label>
                    <div class="col-md-10">
                        <input type="url" class="form-control" id="protected_dl" value="<?php echo htmlspecialchars($item['protected_dl']); ?>">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-2 col-form-label">Size</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control" id="size" value="<?php echo htmlspecialchars($item['size']); ?>" placeholder="e.g., 670 MB">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-2 col-form-label">Length</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control" id="length" value="<?php echo htmlspecialchars($item['length']); ?>" placeholder="e.g., 00:11:10">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-2 col-form-label">Views</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control" id="views" value="<?php echo htmlspecialchars($item['views']); ?>">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-2 col-form-label">Single Image URL</label>
                    <div class="col-md-10">
                        <input type="url" class="form-control" id="single_img" value="<?php echo htmlspecialchars($item['single_img']); ?>">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-2 col-form-label">Splash Image URL</label>
                    <div class="col-md-10">
                        <input type="url" class="form-control" id="splash_img" value="<?php echo htmlspecialchars($item['splash_img']); ?>">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-2 col-form-label">Deskripsi</label>
                    <div class="col-md-10">
                        <textarea class="form-control" id="deskripsi" rows="3"><?php echo htmlspecialchars($item['deskripsi']); ?></textarea>
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-2 col-form-label">Tags (pisahkan dengan koma)</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control" id="tags" value="<?php echo htmlspecialchars(implode(', ', $item['tag'] ?? [])); ?>" placeholder="Tag1, Tag2, Tag3">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-2 col-form-label">Status</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control" id="status" value="<?php echo htmlspecialchars($item['status']); ?>" placeholder="200">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-2 col-form-label">API Source</label>
                    <div class="col-md-10">
                        <input type="text" class="form-control" id="api_source" value="<?php echo htmlspecialchars($item['api_source']); ?>" placeholder="doodstream">
                    </div>
                </div>

                <div class="form-group row">
                    <label class="col-md-2 col-form-label">Can Play</label>
                    <div class="col-md-10">
                        <select class="form-control" id="canplay">
                            <option value="1" <?php echo ($item['canplay'] ?? 1) == 1 ? 'selected' : ''; ?>>Yes</option>
                            <option value="0" <?php echo ($item['canplay'] ?? 1) == 0 ? 'selected' : ''; ?>>No</option>
                        </select>
                    </div>
                </div>

                <div class="form-group row">
                    <div class="col-md-10 offset-md-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save"></i> Simpan
                        </button>
                        <a href="pages.php?page=<?php echo $current_page; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Batal
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

    <!-- Data List -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <?php if (!empty($search_query)): ?>
                    Hasil Pencarian: "<?php echo htmlspecialchars($search_query); ?>" (<?php echo count($filtered_data); ?> hasil)
                <?php else: ?>
                    Data List - Halaman <?php echo $current_page; ?>
                <?php endif; ?>
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Title</th>
                            <th>Filecode</th>
                            <th>Size</th>
                            <th>Views</th>
                            <th>Page</th>
                            <th>Uploaded</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($filtered_data)): ?>
                        <tr>
                            <td colspan="8" class="text-center">Tidak ada data ditemukan</td>
                        </tr>
                        <?php else: ?>
                        <?php foreach ($filtered_data as $idx => $item): 
                            $item_page = $item['_page_number'] ?? $current_page;
                        ?>
                        <tr>
                            <td><?php echo $idx + 1; ?></td>
                            <td><?php echo htmlspecialchars(substr($item['title'] ?? '', 0, 50)); ?><?php echo strlen($item['title'] ?? '') > 50 ? '...' : ''; ?></td>
                            <td><?php echo htmlspecialchars($item['filecode'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($item['size'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($item['views'] ?? '0'); ?></td>
                            <td>
                                <a href="pages.php?page=<?php echo $item_page; ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>" class="badge badge-info">
                                    Page <?php echo $item_page; ?>
                                </a>
                            </td>
                            <td><?php echo htmlspecialchars($item['uploaded'] ?? ''); ?></td>
                            <td>
                                <a href="pages.php?page=<?php echo $item_page; ?>&edit=<?php echo urlencode($item['filecode'] ?? ''); ?><?php echo $search_query ? '&search=' . urlencode($search_query) : ''; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                                <button type="button" class="btn btn-sm btn-danger" onclick="deleteItem(<?php echo $item_page; ?>, '<?php echo htmlspecialchars($item['filecode'] ?? '', ENT_QUOTES); ?>')">
                                    <i class="fas fa-trash"></i> Hapus
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
document.getElementById('dataForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    // Get form values
    var tags = document.getElementById('tags').value;
    var tagArray = tags ? tags.split(',').map(function(tag) { return tag.trim(); }).filter(function(tag) { return tag.length > 0; }) : [];
    
    var data = {
        protected_embed: document.getElementById('protected_embed').value,
        size: document.getElementById('size').value,
        length: document.getElementById('length').value,
        protected_dl: document.getElementById('protected_dl').value,
        views: document.getElementById('views').value || '0',
        single_img: document.getElementById('single_img').value,
        title: document.getElementById('title').value,
        status: document.getElementById('status').value || '200',
        uploaded: document.getElementById('uploaded') ? document.getElementById('uploaded').value : '<?php echo date('Y-m-d H:i:s'); ?>',
        last_view: '',
        splash_img: document.getElementById('splash_img').value,
        filecode: document.getElementById('filecode').value,
        file_code: document.getElementById('filecode').value,
        canplay: parseInt(document.getElementById('canplay').value),
        api_source: document.getElementById('api_source').value || 'doodstream',
        tag: tagArray,
        deskripsi: document.getElementById('deskripsi').value,
        title_preserved: true,
        custom_fields: []
    };
    
    document.getElementById('formData').value = JSON.stringify(data);
    this.submit();
});

function deleteItem(page, filecode) {
    if (confirm('Apakah Anda yakin ingin menghapus data ini?')) {
        var form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="delete">' +
                        '<input type="hidden" name="page" value="' + page + '">' +
                        '<input type="hidden" name="filecode" value="' + filecode + '">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<?php 
require 'includes/footer.php';
} else {
    header('Location: ' . lite_url('login.php'));
exit;
}
?>

