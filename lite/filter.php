<?php
define('IDC', dirname(__FILE__));
$title_panel = 'Filter Search';
require 'includes/header.php';
if (isset($_SESSION['login']) && $_SESSION['login'] == $hash) { ?>
<div class="container-fluid">
<div class="d-sm-flex align-items-center justify-content-between mb-4">
<h1 class="h3 mb-0 text-gray-800">Filter Search</h1>
</div>
<?php if ( file_exists( '../store/' . $_SERVER['HTTP_HOST'] . '/badwords.txt' ) ) {
$file = '../store/' . $_SERVER['HTTP_HOST'] . '/badwords.txt';
}else{
$file = '../store/default/badwords.txt';
}
if (isset($_POST['text'])){
file_put_contents($file, $_POST['text']);
?>
<div class="card bg-success text-white shadow mb-2">
<div class="card-body">
<b>Filter Search</b> successfully updated
</div>
</div>
<?php }
$text = file_get_contents($file);
?>
<div class="row">
<div class="col-md-12">
<div class="card">
<form action="" method="post">
<div class="card-body">
<div class="card bg-primary text-white shadow mb-2">
<div class="card-body">
You can add filter search, separate keyword with commas.<br>
<b>Example:</b> sex, drug, naked
</div>
</div>
<textarea class="form-control" rows="15" name="text"><?php echo htmlspecialchars($text) ?></textarea>
</div><div class="card-footer">
<input class="btn btn-primary" value="Update" type="submit" />
<a class="btn btn-danger" href="index.php">Back</a>
</div>
</form>
</div></div></div>
</div>
<?php 
require 'includes/footer.php';
}else{
header('Location: ' . lite_url('login.php'));
exit;
}?>