<?php
/**
 * Footer Template - AGC Video Platform
 * 
 * Optional variables:
 * - $embed_url : Video embed URL (for video pages)
 * - $hide_ads  : Set to true to hide ads on this page
 * - $additional_footer_script : Additional JavaScript to insert before closing body tag
 */

// Get keywords for navigation
$footer_keywords = [];
$keywords_file = BASE . '/key.txt';
if (file_exists($keywords_file)) {
    $footer_keywords = array_unique(file($keywords_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
}

// Get footer copyright
$footer_copyright = get_option('footer_copyright', '%year% %site_name%');
$footer_site_name = get_option('site_name', '');
$footer_year = date('Y');
$footer_copyright = str_replace('%year%', $footer_year, $footer_copyright);
$footer_copyright = str_replace('%site_name%', $footer_site_name, $footer_copyright);
?>

    </main>
    <!-- Main Content End -->

    <footer class="site-footer">
        <p>&copy; <?php echo htmlspecialchars($footer_copyright, ENT_QUOTES, 'UTF-8'); ?>. All Rights Reserved.</p>
    </footer>
</div>

<!-- Mobile Navigation Drawer -->
<div class="drawer-overlay" id="drawer-overlay"></div>
<aside class="mobile-nav-drawer" id="mobile-nav-drawer">
    <div class="mobile-nav-drawer__header">
        <h2 class="mobile-nav-drawer__title">Menu</h2>
        <button class="site-header__icon-btn mobile-nav-drawer__close-btn" id="mobile-nav-close-btn" aria-label="Tutup menu">&times;</button>
    </div>
    <div class="mobile-nav-drawer__content">
        <ul class="mobile-nav-drawer__list">
            <li><a href="<?php echo site_url(); ?>">Beranda</a></li>
            <li><a href="#" id="category-btn-mobile">Kategori</a></li>
            <?php foreach (array_slice($footer_keywords, 0, 15) as $keyword): ?>
            <li>
                <a href="<?php echo search_permalink($keyword); ?>" title="<?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>">
                    <?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</aside>

<!-- Search Modal -->
<div id="search-modal" class="modal">
    <div class="modal__content">
        <button class="modal__close-btn" id="close-search-modal">&times;</button>
        <h3 class="modal__title">Cari Video</h3>
        <form action="<?php echo site_url(); ?>" method="get" class="search-form">
            <input type="text" name="q" placeholder="Ketik judul video..." class="search-form__input" required>
            <button type="submit" class="search-form__button">Cari</button>
        </form>
    </div>
</div>

<!-- Category Modal -->
<div id="category-modal" class="modal">
    <div class="modal__content">
        <button class="modal__close-btn" id="close-category-modal">&times;</button>
        <h3 class="modal__title">Kategori Populer</h3>
        <div class="category-grid" id="category-grid-container">
            <?php foreach (array_slice($footer_keywords, 0, 20) as $keyword): ?>
            <a href="<?php echo search_permalink(permalink($keyword)); ?>" class="category-grid__link">
                <?php echo htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8'); ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<?php if (empty($hide_ads)): ?>
<!-- Ad Container -->
<div id="ad-container" class="ad-container" style="display:none;">
    <button class="close-btn" onclick="closeAd()">×</button>
    <div data-adcode="<?php echo htmlspecialchars(get_option('ad_code_sticky', ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
</div>

<!-- Custom Ad -->
<div data-adcode="<?php echo htmlspecialchars(get_option('ad_code_custom', ''), ENT_QUOTES, 'UTF-8'); ?>"></div>
<?php endif; ?>

<!-- JavaScript -->
<script src="<?php echo site_url(); ?>/assets/js/lazy.js"></script>
<script src="<?php echo site_url(); ?>/assets/js/main.js" defer></script>
<script src="<?php echo site_url(); ?>/assets/js/ads.js" defer></script>

<?php 
// Custom video player script (if provided, use it instead of default)
if (!empty($additional_footer_script)) {
    echo $additional_footer_script;
} elseif (!empty($embed_url)) {
    // Default video player initialization
?>
<!-- Video Player Initialization -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const iframe = document.getElementById('video-iframe');
    if (iframe) iframe.dataset.src = <?php echo json_encode($embed_url); ?>;
});
</script>
<?php } ?>

<!-- Analytics - Lazy Loaded -->
<script>
(function() {
    function loadHistats() {
        if (window.histatsLoaded) return;
        window.histatsLoaded = true;
        var _Hasync = window._Hasync || [];
        _Hasync.push(['Histats.start', '1,4270801,4,0,0,0,00010000']);
        _Hasync.push(['Histats.fasi', '1']);
        _Hasync.push(['Histats.track_hits', '']);
        var hs = document.createElement('script');
        hs.async = true;
        hs.defer = true;
        hs.src = '//s10.histats.com/js15_as.js';
        document.body.appendChild(hs);
    }
    // Load after page is interactive
    if (document.readyState === 'complete') {
        setTimeout(loadHistats, 3000);
    } else {
        window.addEventListener('load', function() {
            setTimeout(loadHistats, 3000);
        }, { once: true });
    }
    // Also load on user interaction (backup)
    ['scroll', 'click', 'mousemove', 'touchstart'].forEach(function(e) {
        window.addEventListener(e, loadHistats, { once: true, passive: true });
    });
})();
</script>
<noscript><img src="//sstatic1.histats.com/0.gif?4270801&101" alt="" border="0"></noscript>

</body>
</html>
