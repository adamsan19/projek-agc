<?php
define('IDC', dirname(__FILE__));
$title_panel = 'Robots.txt';
require 'includes/header.php';
if (isset($_SESSION['login']) && $_SESSION['login'] == $hash) { ?>
<div class="container-fluid">
<div class="d-sm-flex align-items-center justify-content-between mb-4">
<h1 class="h3 mb-0 text-gray-800">Ping Sitemap</h1>
</div>
<div class="row">
<div class="col-md-12">
<div class="card">
<div class="card-body">
<h3>Bagaimana cara ping sitemap?</h3>
Silahkan akses link berikut ini untuk PING secara manual: <br><br>
<a href="//<?php echo $_SERVER['HTTP_HOST'];?>/pingsitemap.php" target="_blank">
<b><?php echo $_SERVER['HTTP_HOST'];?>/pingsitemap.php</b></a><br><br>
Atau kamu juga dapat menjadwalkan ping secara otomatis melalui cronjob berikut: <br><br>
<textarea class="form-control" rows="1">lynx -source https://<?php echo $_SERVER['HTTP_HOST'];?>/pingsitemap.php > /dev/null</textarea>
</div>
</div></div></div>
<?php 
require 'includes/footer.php';
}else{
header('Location: ' . lite_url('login.php'));
exit;
}?>