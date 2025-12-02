<?php
define('IDC', dirname(__FILE__));
$title_panel = 'Downloader';
require 'includes/header.php';
if (isset($_SESSION['login']) && $_SESSION['login'] == $hash) { ?>
<link rel="stylesheet" href="../assets/syntax/lib/codemirror.css">
<script src="../assets/syntax/lib/codemirror.js"></script>
<script src="../assets/syntax/mode/xml/xml.js"></script>
<div class="container-fluid">
<div class="d-sm-flex align-items-center justify-content-between mb-4">
<h1 class="h3 mb-0 text-gray-800">Downloader</h1>
</div>
<?php
$file = '../downloader.php';
if (isset($_POST['text'])){
file_put_contents($file, $_POST['text']);
?>
<div class="card bg-success text-white shadow mb-2">
<div class="card-body">
<b>Downloader</b> successfully updated
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
<textarea id="code" class="form-control" rows="15" name="text"><?php echo htmlspecialchars($text) ?></textarea>
</div><div class="card-footer">
<input class="btn btn-primary" value="Update" type="submit" />
<a class="btn btn-danger" href="index.php">Back</a>
</div>
</form>
</div></div></div>
</div>
<script>
var editor = CodeMirror.fromTextArea(document.getElementById("code"), {
  mode: "application/xml",
  styleActiveLine: true,
  lineNumbers: true,
  lineWrapping: true
});
</script>
<?php 
require 'includes/footer.php';
}else{
header('Location: ' . lite_url('login.php'));
exit;
}?>