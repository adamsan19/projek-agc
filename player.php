<?php 
if (!defined('BASE')) {
    define('BASE', __DIR__);
}

require 'config/init.php';

require 'libraries/ua.class.php';
require 'libraries/simple_html_dom.php';

require 'core/functions/options.php';
require 'core/functions/cache.php';
require 'core/functions/permalinks.php';
require 'core/functions/common.php';
require 'core/functions/site.php';

require 'core/classes/agc.php';

dmca_redirect();

//delete_cache( get_cache_path() . '/downloads', get_option( 'cache_time' ) );
 
$video_data = agc()->get_video();
$video = !empty($video_data) ? $video_data : null;

if ( $video ) {
} else {
  redirect( site_url() );
} ?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <link rel="shortcut icon" href="/favicon.ico">
    <title><?php echo htmlspecialchars($video['title'], ENT_QUOTES, 'UTF-8'); ?></title>
    <link rel="preload" href="<?php echo htmlspecialchars(photon_cdn($video['single_img'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>" as="image">
    <link rel="preload" href="<?php echo site_url(); ?>/play.svg" as="image" type="image/svg+xml">
    <style>
    html, body {
        margin: 0;
        padding: 0;
        width: 100%;
        height: 100%;
        background-color: #000;
        overflow: hidden;
    }
    .player_block {
        width: 100%;
        height: 100vh;
        position: relative;
        background-color: black;
    }
    .player_block::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: url('<?php echo htmlspecialchars(photon_cdn($video['splash_img'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>') no-repeat center center;
        background-size: contain;
        opacity: 0;
        transition: opacity 0.3s ease-in-out;
    }
    .player_block.loaded::before {
        opacity: 1;
    }
    </style>
</head>

<body>
    <div class="player_block">
        <div class="player" style="width: 100%; height: 100%; position: absolute; top: 0; left: 0; cursor: pointer;">
            <iframe id="videoIframe" class="iframe" frameborder="0" allowfullscreen="" style="width: 100%; height: 100%; display: none;" referrerpolicy="no-referrer" loading="lazy" title="<?php echo htmlspecialchars($video['title'], ENT_QUOTES, 'UTF-8'); ?>" aria-label="">
            </iframe>
            <button id="playButton" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: transparent; border: none; padding: 20px; border-radius: 50%; cursor: pointer;" aria-label="Putar Video">
                <img src="<?php echo site_url(); ?>/play.svg" alt="Tombol Putar" width="100" height="100" style="display: block;" onerror="this.onerror=null; this.style.display='none'; this.parentElement.innerHTML='▶';">
            </button>
        </div>
    </div>

    <script>
    // Lazy load background image
    const playerBlock = document.querySelector('.player_block');
    const bgImage = new Image();
    bgImage.src = '<?php echo htmlspecialchars(photon_cdn($video['single_img'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>';
    bgImage.onload = function() {
        playerBlock.classList.add('loaded');
    };

    function playVideo() {
        const iframe = document.getElementById('videoIframe');
        const embedUrl = '<?php echo htmlspecialchars($video['protected_embed'], ENT_QUOTES, 'UTF-8'); ?>';
        
        // Set the iframe source
        iframe.src = embedUrl;
        
        // Show the iframe and hide the play button
        iframe.style.display = 'block';
        document.getElementById('playButton').style.display = 'none';
        
        // Allow fullscreen and other permissions
        iframe.setAttribute('allowfullscreen', '');
        iframe.setAttribute('allow', 'autoplay; fullscreen');
    }

    // Add click event to both play button and player area
    document.getElementById('playButton').addEventListener('click', playVideo);
    document.querySelector('.player').addEventListener('click', function(e) {
        // Only trigger if the click is not on the play button
        if (e.target !== document.getElementById('playButton') && 
            !document.getElementById('playButton').contains(e.target)) {
            playVideo();
        }
    });
    </script>
</body>
</html> 