<?php
$title_panel = 'Dashboard';
require 'includes/header.php';
if (isset($_SESSION['login']) && $_SESSION['login'] == $hash) { ?>
<div class="container-fluid">
<div class="d-sm-flex align-items-center justify-content-between mb-4">
<h1 class="h3 mb-0 text-gray-800">Dashboard</h1>
<a target="_blank" href="https://api.whatsapp.com/send?phone=6285273078112&text&source&data&app_absent" class="d-sm-inline-block btn btn-sm btn-danger shadow-sm"><i class="fab fa-whatsapp fa-sm text-white-50"></i> Report Bug</a>
</div>
<div class="row">
<div class="col-xl-3 col-md-6 mb-4">
<div class="card border-left-danger shadow h-100 py-2">
<div class="card-body">
<div class="row no-gutters align-items-center">
<div class="col mr-2">
<div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total DMCA</div>
<div class="h5 mb-0 font-weight-bold text-gray-800">
<?php
if ( file_exists( '../store/' . $_SERVER['HTTP_HOST'] . '/dmca.txt' ) ) {
echo count(file('../store/' . $_SERVER['HTTP_HOST'] . '/dmca.txt'));
}else{
echo count(file('../store/default/dmca.txt'));
}
?>
</div>
</div>
<div class="col-auto">
<i class="fas fa-ban fa-2x text-gray-300"></i>
</div>
</div>
</div>
</div>
</div>
<div class="col-xl-3 col-md-6 mb-4">
<div class="card border-left-warning shadow h-100 py-2">
<div class="card-body">
<div class="row no-gutters align-items-center">
<div class="col mr-2">
<div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Total Filter</div>
<div class="h5 mb-0 font-weight-bold text-gray-800">
<?php 
if ( file_exists( '../store/' . $_SERVER['HTTP_HOST'] . '/badwords.txt' ) ) {
$data = '../store/' . $_SERVER['HTTP_HOST'] . '/badwords.txt';
}else{
$data = '../store/default/badwords.txt';
}
$div = ',';
$fp = fopen($data, 'r');
$count = fgets($fp);
fclose($fp);
$dataq = explode($div, $count);
$cek = count($dataq);
echo $cek;
?>
</div>
</div>
<div class="col-auto">
<i class="fas fa-filter fa-2x text-gray-300"></i>
</div>
</div>
</div>
</div>
</div>
<div class="col-xl-3 col-md-6 mb-4">
<div class="card border-left-success shadow h-100 py-2">
<div class="card-body">
<div class="row no-gutters align-items-center">
<div class="col mr-2">
<div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Themes</div>
<div class="h5 mb-0 font-weight-bold text-gray-800">
<?php echo count(glob('../themes/*', GLOB_ONLYDIR)); ?>
</div>
</div>
<div class="col-auto">
<i class="fas fa-laptop fa-2x text-gray-300"></i>
</div>
</div>
</div>
</div>
</div>
<div class="col-xl-3 col-md-6 mb-4">
<div class="card border-left-info shadow h-100 py-2">
<div class="card-body">
<div class="row no-gutters align-items-center">
<div class="col mr-2">
<div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Keywords</div>
<div class="h5 mb-0 font-weight-bold text-gray-800">
<?php
if ( file_exists( '../store/' . $_SERVER['HTTP_HOST'] . '/keywords/1.txt' ) ) {
echo count(file('../store/' . $_SERVER['HTTP_HOST'] . '/keywords/1.txt'));
}else{
echo count(file('../store/default/keywords/1.txt'));
}
?>
</div>
</div>
<div class="col-auto">
<i class="fas fa-key fa-2x text-gray-300"></i>
</div>
</div>
</div>
</div>
</div>
</div>
<h1 style="text-align:center;padding:20px 0;">What is BejoLITE?</h1>
</div>
<?php 
require 'includes/footer.php';
}else{
header('Location: ' . lite_url('login.php'));
exit;
}?>