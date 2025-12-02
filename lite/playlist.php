<?php
define('IDC', dirname(__FILE__));
$title_panel = 'Playlist';
require 'includes/header.php';

require '../config/init.php';

require '../core/functions/general.php';
require '../core/uri.php';
require '../core/router.php';
require '../core/functions/options.php';
require '../core/functions/cache.php';
require '../core/functions/permalinks.php';
require '../core/functions/common.php';

if (isset($_SESSION['login']) && $_SESSION['login'] == $hash) { ?>
<div class="container-fluid">
<div class="d-sm-flex align-items-center justify-content-between mb-4">
<h1 class="h3 mb-0 text-gray-800">Playlist</h1>
</div>
<?php
require_once '../core/classes/agc.php';

// Ambil data playlist dari get_list_files dengan struktur pengecekan seperti home.php
$listData = $agc->get_list_files(1);
$files = [];
if (isset($listData['result']['files']) && is_array($listData['result']['files'])) {
    $files = array_map(function($item) {
        if (!isset($item['file_code'])) {
            $item['file_code'] = '';
        }
        return $item;
    }, $listData['result']['files']);
}
// Ambil data pagination jika ingin digunakan
$total_items = isset($listData['result']['results_total']) ? $listData['result']['results_total'] : 0;
$total_pages = isset($listData['result']['total_pages']) ? $listData['result']['total_pages'] : 1;
$current_page = isset($listData['result']['current_page']) ? $listData['result']['current_page'] : 1;
$per_page = isset($listData['result']['per_page_limit']) ? $listData['result']['per_page_limit'] : 30;
?>
<div class="row">
<div class="col-md-12">
<div class="card">
<div class="card-header">
<!-- Playlist List -->
</div>
<div class="card-body">
<div class="table-responsive">
<table class="table">
<thead>
<tr>
<th width="5%">No</th>
<th width="45%">Title</th>
<th width="30%">File Code</th>
<th width="15%">Uploaded</th>
<th width="10%">Action</th>
</tr>
</thead>
<tbody>
<?php
if (empty($files)) {
    echo '<tr><td colspan="5" class="text-center">No data found or API error.</td></tr>';
}
$i = 0;
foreach($files as $file){
    $i++;
    $title = $file['title'] ?? '';
    $file_code = $file['file_code'] ?? ($file['filecode'] ?? '');
    $uploaded = $file['uploaded'] ?? '';
?>
<tr>
<td><?php echo $i; ?></td>
<td><?php echo htmlspecialchars($title); ?></td>
<td><?php echo htmlspecialchars($file_code); ?></td>
<td><?php echo htmlspecialchars($uploaded); ?></td>
<td><center>
    <a href="video.php?id=<?php echo urlencode($file_code); ?>" class="btn btn-sm btn-info" data-toggle="tooltip" title="View"><i class="fas fa-eye"></i></a>
</center></td>
</tr>
<?php } ?>
</tbody>
</table>
</div>
</div>
</div>
</div>
</div>
</div>
<?php 
require 'includes/footer.php';
}else{
header('Location: ' . lite_url('login.php'));
exit;
}?>