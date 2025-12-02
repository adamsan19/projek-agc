<?php
session_start();
ob_start();
error_reporting(0);

$username = 'admin';
$password = 'demo123';
$random1 = 'secret_key1';
$random2 = 'secret_key2';
$hash = md5($random1.$password.$random2);

// Helper function to get correct URL for lite directory
function lite_url($path = '') {
    $script_dir = dirname($_SERVER['SCRIPT_NAME']);
    $base_url = rtrim($script_dir, '/') . '/';
    if ($base_url === '//') $base_url = '/lite/';
    return $base_url . ltrim($path, '/');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <?php
  // Get base URL for lite directory
  $script_dir = dirname($_SERVER['SCRIPT_NAME']);
  $base_url = rtrim($script_dir, '/') . '/';
  if ($base_url === '//') $base_url = '/lite/';
  ?>
  <base href="<?php echo htmlspecialchars($base_url, ENT_QUOTES, 'UTF-8'); ?>">

  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <meta name="description" content="">
  <meta name="author" content="">

  <title><?php echo $title_panel;?> | BejoLITE</title>

  <!-- Custom fonts for this template-->
    <link href="includes/vendor/fontawesome-free/css/all.min.css" rel="stylesheet" type="text/css">
    <link
        href="https://fonts.googleapis.com/css?family=Nunito:200,200i,300,300i,400,400i,600,600i,700,700i,800,800i,900,900i"
        rel="stylesheet">

    <!-- Custom styles for this template-->
    <link href="assets/css/sb-admin-2.min.css" rel="stylesheet">
    <link href="assets/css/dark-theme.css" rel="stylesheet">

</head>
<?php if (isset($_SESSION['login']) && $_SESSION['login'] == $hash) { ?>
<body id="page-top">
    <!-- Mobile Overlay -->
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
<div id="wrapper">

    <!-- Sidebar -->
    <ul class="navbar-nav bg-gradient-primary sidebar sidebar-dark accordion toggled" id="accordionSidebar">

      <!-- Sidebar - Brand -->
      <a class="sidebar-brand d-flex align-items-center justify-content-center" href="index.php">
        <div class="sidebar-brand-icon">
          <i class="fas fa-globe"></i>
        </div>
        <div class="sidebar-brand-text mx-3">BejoAGC <sup>LITE</sup></div>
      </a>

      <!-- Divider -->
      <hr class="sidebar-divider my-0">

      <!-- Nav Item - Dashboard -->
      <li class="nav-item">
        <a class="nav-link" href="index.php">
          <i class="fas fa-fw fa-tachometer-alt"></i>
          <span>Dashboard</span></a>
      </li>

      <!-- Nav Item - Pages Collapse Menu -->
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapsePosts" aria-expanded="false" aria-controls="collapsePosts">
          <i class="fas fa-fw fa-pen"></i>
          <span>Posts</span>
        </a>
        <div id="collapsePosts" class="collapse" aria-labelledby="headingPosts" data-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">Custom Posts:</h6>
            <a class="collapse-item" href="pages.php">Pages</a>
            <a class="collapse-item" href="playlist.php">Youtube Playlist</a>
          </div>
        </div>
      </li>

      <!-- Nav Item - Utilities Collapse Menu -->
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseUtilities" aria-expanded="false" aria-controls="collapseUtilities">
          <i class="fas fa-fw fa-wrench"></i>
          <span>Tools</span>
        </a>
        <div id="collapseUtilities" class="collapse" aria-labelledby="headingUtilities" data-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">Custom Tools:</h6>
            <a class="collapse-item" href="filter.php">Filter Search</a>
            <a class="collapse-item" href="keywords.php">Keywords</a>
            <a class="collapse-item" href="dmca.php">DMCA Takedown</a>
            <a class="collapse-item" href="cache.php">Cache Management</a>
            <a class="collapse-item" href="output_manager.php">Output Data Manager</a>
            <a class="collapse-item" href="pingsitemap.php">Ping Sitemap</a>
            <a class="collapse-item" href="convert.php">Converter</a>
            <a class="collapse-item" href="precache.php">Precache Executor</a>
          </div>
        </div>
      </li>

      <!-- Divider -->
      <hr class="sidebar-divider">

      <!-- Heading -->
      <div class="sidebar-heading">
        Interface
      </div>

      <!-- Nav Item - Pages Collapse Menu -->
      <li class="nav-item">
        <a class="nav-link collapsed" href="#" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
          <i class="fas fa-fw fa-laptop"></i>
          <span>Themes</span>
        </a>
        <div id="collapseTwo" class="collapse" aria-labelledby="headingTwo" data-parent="#accordionSidebar">
          <div class="bg-white py-2 collapse-inner rounded">
            <h6 class="collapse-header">Custom Themes:</h6>
            <a class="collapse-item" href="themes.php?add">Add</a>
            <a class="collapse-item" href="themes.php">Theme Editor</a>
            <a class="collapse-item" href="downloader.php">Downloader</a>
          </div>
        </div>
      </li>

      <!-- Divider -->
      <hr class="sidebar-divider">

      <!-- Heading -->
      <div class="sidebar-heading">
        Settings
      </div>

      <!-- Nav Item - Tables -->
      <li class="nav-item">
        <a class="nav-link" href="settings.php">
          <i class="fas fa-fw fa-cog"></i>
          <span>Sites</span></a>
      </li>

      <!-- Nav Item - Tables -->
      <li class="nav-item">
        <a class="nav-link" href="robots.php">
          <i class="fas fa-fw fa-robot"></i>
          <span>Robots.txt</span></a>
      </li>

      <!-- Divider -->
      <hr class="sidebar-divider d-none d-md-block">

      <!-- Sidebar Toggler (Sidebar) -->
      <div class="text-center d-none d-md-inline">
        <button class="rounded-circle border-0" id="sidebarToggle"></button>
      </div>

    </ul>
    <!-- End of Sidebar -->

    <!-- Content Wrapper -->
    <div id="content-wrapper" class="d-flex flex-column">

      <!-- Main Content -->
      <div id="content">

        <!-- Topbar -->
        <nav class="navbar navbar-expand navbar-light bg-white topbar mb-4 static-top shadow">

          <!-- Sidebar Toggle (Topbar) -->
          <button id="sidebarToggleTop" class="btn btn-link d-md-none rounded-circle mr-3">
            <i class="fa fa-bars"></i>
          </button>

          <!-- Topbar Navbar -->
          <ul class="navbar-nav ml-auto">
			<li class="nav-item dropdown no-arrow mx-1">
              <a class="nav-link dropdown-toggle" href="/" target="_blank">
                <i class="fas fa-globe fa-fw"></i>
                <!-- Counter - Alerts -->
                <span class="badge badge-success badge-counter">site</span>
              </a>
			</li>
			<li class="nav-item dropdown no-arrow mx-1">
              <a class="nav-link dropdown-toggle" href="#" data-toggle="modal" data-target="#changelogModal">
                <i class="fas fa-file fa-fw"></i>
                <!-- Counter - Alerts -->
                <span class="badge badge-primary badge-counter">new</span>
              </a>
			</li>
            <div class="topbar-divider d-none d-sm-block"></div>

            <!-- Nav Item - User Information -->
            <li class="nav-item dropdown no-arrow">
              <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <span class="mr-2 d-none d-lg-inline text-gray-600 small">Administrator</span>
                <svg class="img-profile rounded-circle" width="40" height="40" viewBox="0 0 40 40">
  <circle cx="20" cy="20" r="20" fill="#ccc"/> <!-- Background circle -->
  <circle cx="20" cy="15" r="8" fill="#fff"/> <!-- Head -->
  <circle cx="20" cy="40" r="12" fill="#fff"/> <!-- Body base -->
  <rect x="8" y="23" width="24" height="16" fill="#fff"/> <!-- Body -->
</svg>              </a>
              <!-- Dropdown - User Information -->
              <div class="dropdown-menu dropdown-menu-right shadow animated--grow-in" aria-labelledby="userDropdown">
                <a class="dropdown-item" href="#" data-toggle="modal" data-target="#logoutModal">
                  <i class="fas fa-sign-out-alt fa-sm fa-fw mr-2 text-gray-400"></i>
                  Logout
                </a>
              </div>
            </li>

          </ul>

        </nav>
<?php }else{ ?>
<body class="bg-gradient-primary">

  <div class="container">
<?php } ?>