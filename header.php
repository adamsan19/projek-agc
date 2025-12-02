<?php
/**
 * Header Template - AGC Video Platform
 * 
 * Required variables (set before including):
 * - $site_title       : Page title
 * - $meta_description : Meta description
 * - $meta_robots      : Robots directive (optional, defaults to "index, follow")
 * - $site_name        : Site name (optional, loaded from config if not set)
 * - $ogimage          : Open Graph image URL (optional)
 * - $additional_head  : Additional HTML to insert in <head> (optional)
 * - $body_class       : Additional body classes (optional)
 */

// Set defaults if not defined
if (!isset($site_name) || empty($site_name)) {
    $site_name = get_option('site_name', 'Video Viral');
}
if (!isset($meta_robots)) {
    $meta_robots = 'index, follow';
}
if (!isset($ogimage) || empty($ogimage)) {
    $ogimage = site_url() . '/assets/img/placeholder.png';
}
if (!isset($body_class)) {
    $body_class = '';
}
?>
<!DOCTYPE html>
<html class="xv-responsive" lang="id-ID" itemscope itemtype="https://schema.org/WebPage">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?php echo htmlspecialchars($site_title ?? '', ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($meta_description ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="canonical" href="<?php echo htmlspecialchars(canonical_url()); ?>">
    <link rel="icon" href="<?php echo site_url(); ?>/favicon.ico">
    <meta name="robots" content="<?php echo htmlspecialchars($meta_robots, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="theme-color" content="#101010">
    <meta name="author" content="<?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="language" content="Indonesian">
    <meta name="revisit-after" content="1 days">
    <meta name="distribution" content="global">
    <meta name="rating" content="general">
    
    <!-- Preload Critical Resources -->
    <link rel="preload" href="<?php echo site_url(); ?>/assets/img/logo.png" as="image" type="image/png">
    <link rel="preload" href="<?php echo site_url(); ?>/assets/img/placeholder.png" as="image" type="image/png">
    <?php 
    // Only preload play.svg on pages that actually use it (download.php, player.php)
    if (isset($preload_play_svg) && $preload_play_svg === true): ?>
    <link rel="preload" href="<?php echo site_url(); ?>/play.svg" as="image" type="image/svg+xml">
    <?php endif; ?>

    <!-- Open Graph / Facebook -->
    <meta property="og:type" content="website">
    <meta property="og:url" content="<?php echo htmlspecialchars(canonical_url()); ?>">
    <meta property="og:title" content="<?php echo htmlspecialchars($site_title ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($meta_description ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars(photon_cdn($ogimage), ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?>">

    <!-- Twitter -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="<?php echo htmlspecialchars(photon_cdn($ogimage), ENT_QUOTES, 'UTF-8'); ?>">
 
    
    <!-- Preload Critical CSS -->
    <link rel="preload" href="<?php echo site_url(); ?>/assets/css/style.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="<?php echo site_url(); ?>/assets/css/style.css"></noscript>
    
    <!-- Critical CSS Inline -->
    <style>
        :root{--tap-highlight-color:transparent;--tap-target-size:44px;--border-radius-sm:6px;--border-radius-md:10px;--border-radius-lg:14px;--border-radius-full:9999px;--font-sans:'Inter',sans-serif;--color-brand:#E50914;--color-text-primary:#e0e0e0;--color-text-secondary:#9e9e9e;--color-bg-primary:#1e1e1e;--color-bg-secondary:#2c2c2c;--color-border:#3a3a3a;--shadow-sm:0 1px 2px 0 rgb(0 0 0 / 0.15);--shadow-md:0 4px 6px -1px rgb(0 0 0 / 0.2),0 2px 4px -2px rgb(0 0 0 / 0.2);--shadow-lg:0 5px 15px rgba(0,0,0,0.3)}html{-webkit-text-size-adjust:100%;text-size-adjust:100%;scroll-behavior:smooth}body{margin:0;overflow-x:hidden;background-color:#101010;color:var(--color-text-primary);font-family:var(--font-sans);font-size:clamp(0.9rem,1.5vw,1rem);box-shadow:var(--shadow-lg);border:1px solid #282828}.page-wrapper{max-width:800px;margin:0 auto;padding:0 8px;background-color:var(--color-bg-primary);border:1px solid #282828;border-radius:var(--border-radius-md);box-shadow:var(--shadow-lg)}.site-header{position:sticky;top:0;z-index:50;background:#fff0;-webkit-backdrop-filter:blur(10px);backdrop-filter:blur(10px);border-bottom:1px solid var(--color-brand);padding-top:max(0px,env(safe-area-inset-top,0px))}.site-header__content{display:flex;align-items:center;justify-content:space-between;padding:8px 0}.site-header__brand{display:flex;align-items:center;gap:0;font-size:1.1rem;font-weight:700;text-decoration:none;color:#fff;position:relative}.site-header__brand .icon{stroke-width:2.5px}.site-header__logo{display:block;width:auto;height:40px;max-width:200px;max-height:40px;object-fit:contain;flex-shrink:0;image-rendering:-webkit-optimize-contrast;image-rendering:crisp-edges;transition:opacity .2s;z-index:2;position:relative}@media(max-width:768px){.site-header__logo{height:36px;max-height:36px;max-width:180px}}@media(max-width:480px){.site-header__logo{height:32px;max-height:32px;max-width:150px}}@media(max-width:360px){.site-header__logo{height:28px;max-height:28px;max-width:120px}}.site-header__brand-text{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;clip:rect(0,0,0,0);white-space:nowrap;opacity:0;pointer-events:none}.site-header__nav{display:none}@media(min-width:768px){.site-header__nav{display:flex;align-items:center;gap:10px}}.site-header__nav-link{display:flex;align-items:center;gap:6px;padding:6px 10px;border-radius:var(--border-radius-sm);text-decoration:none;color:var(--color-text-secondary);font-weight:500;transition:color .2s,background-color .2s}.site-header__nav-link:hover{color:#fff;background-color:rgb(255 255 255 / .1)}.site-header__actions{display:flex;align-items:center;gap:4px}.site-header__icon-btn{display:inline-flex;justify-content:center;align-items:center;width:38px;height:38px;border-radius:var(--border-radius-full);background:0 0;border:none;color:#fff;cursor:pointer;transition:color .2s,background-color .2s}.site-header__icon-btn:hover{background-color:rgb(255 255 255 / .1)}.site-header__mobile-toggle{display:inline-flex}@media(min-width:768px){.site-header__mobile-toggle{display:none}}.main-content{min-height:60vh;padding:3px 0}.site-footer{text-align:center;padding:20px 8px;margin-top:20px;border-top:1px solid var(--color-border);color:var(--color-text-secondary);font-size:.875rem}.site-footer p{margin:0}.video-card-list-item__thumbnail-wrapper{flex:0 0 140px;position:relative;aspect-ratio:16/9;border-radius:var(--border-radius-sm);overflow:hidden;background-color:#3a3a3a}.video-card-list-item__thumbnail{width:100%;height:100%;object-fit:cover}.video-card-list-item__duration{position:absolute;right:5px;bottom:5px;font-size:11px;padding:2px 5px;border-radius:4px;background:rgb(0 0 0 / .75);color:#fff}.drawer-overlay{display:none;position:fixed;top:0;left:0;width:100%;height:100%;background-color:rgb(0 0 0 / .5);z-index:199}.drawer-overlay.is-visible{display:block}.mobile-nav-drawer{position:fixed;top:0;left:0;width:280px;height:100%;background-color:var(--color-bg-primary);z-index:200;transform:translateX(-100%);transition:transform .3s ease-in-out;box-shadow:0 0 20px rgb(0 0 0 / .1)}.mobile-nav-drawer.is-open{transform:translateX(0)}.mobile-nav-drawer__header{display:flex;justify-content:space-between;align-items:center;padding:16px;border-bottom:1px solid var(--color-border)}.mobile-nav-drawer__title{margin:0;font-size:1.1rem;color:var(--color-text-primary)}.mobile-nav-drawer__close-btn{font-size:1.5rem;background:0 0;border:none;color:var(--color-text-secondary);cursor:pointer}.mobile-nav-drawer__content{padding:16px}.mobile-nav-drawer__list{list-style:none;padding:0;margin:0}.mobile-nav-drawer__list a{display:block;padding:12px 0;text-decoration:none;color:var(--color-text-primary);font-weight:500;border-bottom:1px solid var(--color-border)}.modal{display:none;position:fixed;z-index:100;left:0;top:0;width:100%;height:100%;overflow:auto;background-color:rgb(0 0 0 / .6)}.modal__content{background-color:var(--color-bg-primary);margin:10% auto;padding:20px;border:none;width:90%;max-width:500px;border-radius:var(--border-radius-md);position:relative;box-shadow:var(--shadow-md)}.modal__close-btn{position:absolute;top:10px;right:12px;border:none;background:0 0;font-size:1.5rem;cursor:pointer;color:var(--color-text-secondary)}.modal__title{margin-top:0;color:var(--color-text-primary)}.search-form{display:flex;gap:8px}.search-form__input{flex-grow:1;padding:10px 14px;border:1px solid var(--color-border);border-radius:var(--border-radius-sm);font-size:1rem;background-color:var(--color-bg-secondary);color:var(--color-text-primary)}.search-form__button{padding:10px 18px;border:none;background-color:var(--color-brand);color:#fff;border-radius:var(--border-radius-sm);cursor:pointer;font-weight:500}.category-grid{display:grid;grid-template-columns:repeat(2,1fr);gap:6px}@media(max-width:640px){.category-grid{grid-template-columns:1fr}}.category-grid__link{display:block;padding:8px;background-color:var(--color-bg-secondary);border-radius:var(--border-radius-sm);text-decoration:none;color:var(--color-text-primary);text-align:center;transition:background-color .2s;border:1px solid var(--color-border)}img.lazy{opacity:0;transition:opacity .5s}img.lazy.loaded{opacity:1}:is(button,a):focus-visible{outline:2px solid var(--color-brand);outline-offset:2px}.breadcrumb-nav{max-width:1200px;margin:20px auto;padding:0 1rem;position:relative}.breadcrumb-list{display:flex;align-items:center;list-style:none;padding:0;margin:0;flex-wrap:wrap;gap:6px}.breadcrumb-item{display:inline-flex;align-items:center;position:relative}.breadcrumb-item a{color:var(--color-text-secondary);text-decoration:none;transition:all 0.3s ease;font-size:.9rem;display:inline-flex;align-items:center;gap:4px;padding:4px 8px;border-radius:var(--border-radius-sm);line-height:1.4}.breadcrumb-item a:hover{color:var(--color-text-primary);background-color:rgb(255 255 255 / .05)}.breadcrumb-item a .icon{width:16px;height:16px;flex-shrink:0;stroke-width:2;transition:transform 0.2s ease}.breadcrumb-item a:hover .icon{transform:scale(1.1)}.breadcrumb-item.active{color:var(--color-text-primary);font-weight:500;pointer-events:none}.breadcrumb-item.active span{display:-webkit-box;-webkit-line-clamp:1;-webkit-box-orient:vertical;overflow:hidden;max-width:300px;font-size:.9rem;padding:4px 8px;line-height:1.4}.breadcrumb-separator{color:var(--color-text-secondary);opacity:.4;font-size:.75rem;display:inline-flex;align-items:center;justify-content:center;width:16px;height:16px;flex-shrink:0;margin:0 2px}.breadcrumb-separator .icon{width:12px;height:12px;stroke-width:2.5}@media (max-width:768px){.breadcrumb-nav{margin:15px auto;padding:0 1rem}.breadcrumb-item.active span{max-width:200px}.breadcrumb-item a{font-size:.85rem;padding:3px 6px}.breadcrumb-item a .icon{width:14px;height:14px}}@media (max-width:480px){.breadcrumb-nav{margin:12px auto;padding:0 .75rem}.breadcrumb-list{gap:4px}.breadcrumb-item a,.breadcrumb-item.active span{font-size:.8rem;padding:2px 5px}.breadcrumb-item.active span{max-width:150px}.breadcrumb-item a .icon{width:12px;height:12px}.breadcrumb-separator{width:14px;height:14px;margin:0 1px}.breadcrumb-separator .icon{width:10px;height:10px}}
    </style>

    <!-- Default SEO Schema -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "<?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?>",
        "url": "<?php echo htmlspecialchars(site_url(), ENT_QUOTES, 'UTF-8'); ?>",
        "description": "<?php echo htmlspecialchars($meta_description ?? '', ENT_QUOTES, 'UTF-8'); ?>",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "<?php echo htmlspecialchars(site_url(), ENT_QUOTES, 'UTF-8'); ?>/?q={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "<?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?>",
        "url": "<?php echo htmlspecialchars(site_url(), ENT_QUOTES, 'UTF-8'); ?>",
        "logo": "<?php echo htmlspecialchars(site_url(), ENT_QUOTES, 'UTF-8'); ?>/assets/img/logo.png"
    }
    </script>

    <?php 
    // Allow pages to add custom head content (additional schemas, meta tags, etc.)
    if (isset($additional_head)) {
        echo $additional_head;
    }
    ?>
</head>
<body<?php echo !empty($body_class) ? ' class="' . htmlspecialchars($body_class, ENT_QUOTES, 'UTF-8') . '"' : ''; ?>>

<div class="page-wrapper">
    <header class="site-header">
        <div class="site-header__content">
            <a href="<?php echo site_url(); ?>" class="site-header__brand" aria-label="<?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?> - Beranda">
                <img 
                    src="<?php echo site_url(); ?>/assets/img/logo.png" 
                    alt="<?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?>" 
                    class="site-header__logo"
                    width="32" 
                    height="32"
                    loading="eager"
                    fetchpriority="high"
                    decoding="async"
                    onerror="this.onerror=null; this.src='<?php echo site_url(); ?>/assets/img/placeholder.svg'; this.classList.add('lazyerror');"
                >
                <span id="site-name-header" class="site-header__brand-text"><?php echo htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8'); ?></span>
            </a>
            <nav class="site-header__nav">
                <a href="<?php echo site_url(); ?>" class="site-header__nav-link">
                    <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                    <span>Beranda</span>
                </a>
                <a href="#" class="site-header__nav-link" id="category-btn-desktop">
                    <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"/><path d="M3 9h18M3 15h18M9 3v18M15 3v18"/></svg>
                    <span>Kategori</span>
                </a>
            </nav>
            <div class="site-header__actions">
                <button class="site-header__icon-btn" id="search-btn" aria-label="Buka pencarian">
                    <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
                </button>
                <button class="site-header__icon-btn site-header__mobile-toggle" id="mobile-menu-btn" aria-label="Buka menu">
                    <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="4" x2="20" y1="12" y2="12"/><line x1="4" x2="20" y1="6" y2="6"/><line x1="4" x2="20" y1="18" y2="18"/></svg>
                </button>
            </div>
        </div>
    </header>

    <!-- Main Content Start -->
    <main class="main-content">
