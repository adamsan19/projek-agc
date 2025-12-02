<?php
$title_panel = 'Themes';
require 'includes/header.php';
if (isset($_SESSION['login']) && $_SESSION['login'] == $hash) { ?>
<div class="container-fluid">
<div class="d-sm-flex align-items-center justify-content-between mb-4">
<h1 class="h3 mb-0 text-gray-800">Theme Editor</h1>
<a target="_blank" href="#" class="d-sm-inline-block btn btn-sm btn-success shadow-sm"><i class="fas fa-archive fa-sm text-white-50"></i> Collection</a>
</div>
<?php if(isset($_GET['edit'])){
$tema = $_GET['edit'];
$name = str_replace('_', ' ', $tema);
$name = ucwords($name);
$caronicalku = $_SERVER['REQUEST_URI'];
$folder = '../themes/'.$tema.'';
if(!($buka_folder = opendir($folder)));

$file_array = array(); 
while($baca_folder = readdir($buka_folder))
 {
  if(substr($baca_folder,0,1) != '.')
   {
     $file_array[] =  $baca_folder;
    }
 }
?>
<?php
if(isset($_GET['page'])){
$file = '../themes/'.$_GET['edit'].'/'.$_GET['page'].'';
}else{
$file = '../themes/'.$_GET['edit'].'/home.php';
}
if (isset($_POST['text'])){
file_put_contents($file, $_POST['text']);
?>
<div class="card bg-success text-white shadow mb-2">
<div class="card-body">
Theme <b><?php echo $tema;?></b> successfully updated
</div>
</div>
<?php } ?>
<div class="row">
<div class="col-md-12">
<div class="card">
<h4 class="card-header">
<?php echo $name;?> Theme
</h4>
<div class="card-body">
<div class="card bg-warning text-white shadow mb-2">
<div class="card-body">
If you want to edit or create a theme, please read here for <a href="guide.php"><b>the guide</b></a>.
</div>
</div>
<div class="row">
<div class="col-md-3">
<div class="nav flex-column nav-pills nav-secondary" role="tablist" aria-orientation="vertical">
<?php while(list($index, $nama_file) = each($file_array)){?>
<a class="nav-link <?php if($caronicalku == '/lite/themes.php?edit='.$_GET['edit'].'&page='.$nama_file.'') echo "active"; ?>" href="?edit=<?php echo $_GET['edit'];?>&page=<?php echo $nama_file;?>"><?php echo $nama_file;?></a>
<?php }
closedir($buka_folder);?>
</div></div>
<div class="col-md-9">
<div class="tab-content" id="v-pills-tabContent">
<div class="tab-pane fade show active" id="v-pills-home" role="tabpanel" aria-labelledby="v-pills-home-tab">
<link rel="stylesheet" href="../assets/syntax/lib/codemirror.css">
<script src="../assets/syntax/lib/codemirror.js"></script>
<script src="../assets/syntax/mode/xml/xml.js"></script>
<?php
$text = file_get_contents($file);
echo'<form action="" method="post">
<div style="border:1px solid #ddd;margin:5px 0;">
<textarea id="code" class="form-control" rows="10" name="text">'.htmlspecialchars($text).'</textarea>
</div>
<input type="submit" class="btn btn-primary" value="Update">
</form></div></div></div></div>';
?>
<script>
var editor = CodeMirror.fromTextArea(document.getElementById("code"), {
  mode: "application/xml",
  styleActiveLine: true,
  lineNumbers: true,
  lineWrapping: true
});
</script>
<?php
echo'</div>
<div class="clear"></div>
</div></div>';
}elseif(isset($_GET['add'])){ ?>
<?php
if(isset($_POST["submit"])){
$file_zip = $_FILES['file_zip']['tmp_name'];
$direktori = "../themes/";
$zip = new ZipArchive;
if ($zip->open($file_zip) === TRUE) {
 $zip->extractTo($direktori);
 $zip->close();
?>
<div class="card bg-success text-white shadow mb-2">
<div class="card-body">
Theme successfully added
</div>
</div>
<?php } else { ?>
<div class="card bg-danger text-white shadow mb-2">
<div class="card-body">
Theme failed added
</div>
</div>
<?php }} ?>
<div class="row">
<div class="col-md-12">
<div class="card">
<form enctype="multipart/form-data" method="post">
<div class="card-body">
<div class="card bg-primary text-white shadow mb-4">
<div class="card-body">
Theme extension .ZIP
</div>
</div>
<input id="uploadBtn" name="file_zip" type="file" />
</div>
<div class="card-footer">
<input type="submit" name="submit" class="btn btn-primary" value="Upload" />
<a href="index.php" class="btn btn-danger">Back</a>
</div>
</form>
</div></div></div>
<?php
}else{
if ( file_exists( '../config/' . $_SERVER['HTTP_HOST'] . '/config.json' ) ) {
$cache_file = '../config/' . $_SERVER['HTTP_HOST'] . '/config.json';
}else{
$cache_file = '../config/default/config.json';
}
$cache = json_decode( file_get_contents( $cache_file ), true );
$folder = '../themes';
if(!($buka_folder = opendir($folder)));

$file_array = array(); 
while($baca_folder = readdir($buka_folder))
 {
  if(substr($baca_folder,0,1) != '.')
   {
     $file_array[] =  $baca_folder;
    }
 }
?>
<div class="row">
<?php
while(list($index, $nama_file) = each($file_array))
{?>
<div class="col-md-4 mb-4">
<div class="card">
<?php
echo'<div class="card-body">
<a href="?edit='.$nama_file.'" title="'.ucwords($nama_file).' Theme">
<img src="/themes/'.$nama_file.'/images/thumbnail.png" width="100%" alt="'.ucwords($nama_file).'"/>
</a>
</div>
<div class="card-footer">
<div class="card-title" style="text-align:center"><a href="?edit='.$nama_file.'" title="'.ucwords($nama_file).' Theme">'.ucwords($nama_file).'</a>';
?>
<?php if($cache[ 'theme' ] == ''.$nama_file.'') echo " <span class='badge badge-success'>Used</span>";?></div>
</div>
</div>
</div>
<?php }
closedir($buka_folder);
echo'</div>';
?>
<?php } ?>
</div>
<?php 
require 'includes/footer.php';
}else{
header('Location: ' . lite_url('login.php'));
exit;
}?>