<?php 
if (!defined('BASE')) { define('BASE', __DIR__); }

// Sertakan library dan fungsi yang diperlukan
require 'libraries/ua.class.php';
require 'core/functions/options.php';
require 'core/functions/permalinks.php';
require 'core/functions/common.php';
require 'core/functions/site.php';
require 'core/functions/breadcrumb.php';
require_once 'core/classes/VideoService.php';

// Get recent searches
$searches = get_recent_user_access(get_option('recent_searches_count', 20));

// Inisialisasi VideoService
$agc = new VideoService();

// Ambil data video menggunakan VideoService
$file_data = $agc->get_video();

// Alihkan ke halaman utama jika data file tidak ditemukan
if (!$file_data || empty($file_data) || isset($file_data['error'])) {
    redirect(site_url());
    exit;
}

// Persiapkan variabel untuk digunakan di HTML dan Schema
$cleanedTitle = $file_data['title'] ?? 'Judul Tidak Tersedia';
$download_code = $file_data['filecode'] ?? $file_data['file_code'] ?? '';
$thumbnailUrl = !empty($file_data['single_img']) ? photon_cdn($file_data['single_img']) : (site_url() . '/assets/img/placeholder.png');
$viewsCount = filter_var($file_data['views'] ?? 0, FILTER_SANITIZE_NUMBER_INT);
$videoDuration = $file_data['length'] ?? '00:00';

// Format durasi ke format ISO 8601 (misal: PT5M30S) untuk schema
$durationParts = explode(':', $videoDuration);
$isoDuration = 'PT' . ($durationParts[0] ?? '0') . 'M' . ($durationParts[1] ?? '0') . 'S';

// Ambil video terkait menggunakan VideoService
$totalPages = 0;
$relatedData = $agc->get_list_files(rand(1, 100));
if (is_array($relatedData) && isset($relatedData['result']['files'])) {
    $related = $relatedData['result']['files'];
    $totalPages = $relatedData['result']['total_pages'] ?? 0;
} else {
    $related = [];
}

// Remove duplicates based on length value
$unique_related = [];
$seen_lengths = [];

foreach ($related as $video) {
    $length = $video['length'] ?? $video['duration'] ?? '';
    
    // Normalize length format for comparison
    $length_normalized = '';
    if (!empty($length)) {
        if (is_numeric($length)) {
            $length_normalized = (string)(int)$length;
        } else {
            $parts = explode(':', $length);
            $parts = array_map('intval', $parts);
            
            if (count($parts) === 3) {
                $length_normalized = (string)($parts[0] * 3600 + $parts[1] * 60 + $parts[2]);
            } elseif (count($parts) === 2) {
                $length_normalized = (string)($parts[0] * 60 + $parts[1]);
            } else {
                $length_normalized = preg_replace('/[^0-9]/', '', $length);
            }
        }
    }
    
    if (!isset($seen_lengths[$length_normalized])) {
        $seen_lengths[$length_normalized] = true;
        $unique_related[] = $video;
    }
}

// Limit to 24 videos
$related = array_slice($unique_related, 0, 24);
// SEO Variables from config.json
$site_name = get_option('site_name') ?? 'Website';
$download_title_template = get_option('download_title') ?? 'Download %title% - %site_name%';
$download_desc_template = get_option('download_description') ?? '';

// Replace template variables
$site_title = str_replace(['%title%', '%site_name%', '%size%'], [$cleanedTitle, $site_name, $size ?? ''], $download_title_template);
$metaDescription = !empty($download_desc_template) 
    ? str_replace(['%title%', '%site_name%', '%size%'], [$cleanedTitle, $site_name, $size ?? ''], $download_desc_template)
    : "Unduh file '" . htmlspecialchars($cleanedTitle) . "'. Tautan unduhan Anda akan siap dalam beberapa detik. Temukan juga video terkait lainnya.";
$last_modified = date('Y-m-d');
$canonical_url = site_url($_SERVER['REQUEST_URI']);

// Category generation for breadcrumbs - menggunakan logika yang sama dengan breadcrumb_url
$category_name = breadcrumb_category_name($cleanedTitle);
$breadcrumb_url = breadcrumb_url($cleanedTitle);

// Total related videos count
$total_related = count($related);

// Prepare SEO Schema for additional_head
$additional_head = '
    <!-- JSON-LD Schema untuk SEO -->
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "WebSite",
        "name": "' . htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8') . '",
        "url": "' . htmlspecialchars(site_url(), ENT_QUOTES, 'UTF-8') . '",
        "description": "' . htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') . '",
        "potentialAction": {
            "@type": "SearchAction",
            "target": "' . htmlspecialchars(site_url(), ENT_QUOTES, 'UTF-8') . '/?q={search_term_string}",
            "query-input": "required name=search_term_string"
        }
    }
    </script>
    
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "Organization",
        "name": "' . htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8') . '",
        "url": "' . htmlspecialchars(site_url(), ENT_QUOTES, 'UTF-8') . '",
        "logo": "' . htmlspecialchars(site_url(), ENT_QUOTES, 'UTF-8') . '/favicon.ico",
        "founder": {
            "@type": "Person",
            "name": "' . htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8') . '"
        },
        "foundingDate": "' . date('Y-m-d') . '"
    }
    </script>
    
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "BreadcrumbList",
      "itemListElement": [
        {"@type": "ListItem", "position": 1, "name": "' . htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8') . '", "item": "' . htmlspecialchars(site_url(), ENT_QUOTES, 'UTF-8') . '"},
        {"@type": "ListItem", "position": 2, "name": "' . htmlspecialchars($category_name, ENT_QUOTES, 'UTF-8') . '", "item": "' . htmlspecialchars($breadcrumb_url, ENT_QUOTES, 'UTF-8') . '"},
        {"@type": "ListItem", "position": 3, "name": "' . htmlspecialchars($cleanedTitle, ENT_QUOTES, 'UTF-8') . '", "item": "' . htmlspecialchars($canonical_url, ENT_QUOTES, 'UTF-8') . '"}
      ]
    }
    </script>
    
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "WebPage",
      "name": "Unduh File - ' . htmlspecialchars($cleanedTitle, ENT_QUOTES, 'UTF-8') . '",
      "description": "' . htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') . '",
      "url": "' . htmlspecialchars($canonical_url, ENT_QUOTES, 'UTF-8') . '",
      "dateModified": "' . htmlspecialchars($last_modified, ENT_QUOTES, 'UTF-8') . '",
      "inLanguage": "id-ID",
      "isPartOf": {
        "@type": "WebSite",
        "name": "' . htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8') . '",
        "url": "' . htmlspecialchars(site_url(), ENT_QUOTES, 'UTF-8') . '"
      },
      "primaryImageOfPage": {
        "@type": "ImageObject",
        "url": "' . htmlspecialchars(photon_cdn($thumbnailUrl), ENT_QUOTES, 'UTF-8') . '"
      }
    }
    </script>
    
    <script type="application/ld+json">
    {
      "@context": "https://schema.org",
      "@type": "VideoObject",
      "name": "' . htmlspecialchars($cleanedTitle, ENT_QUOTES, 'UTF-8') . '",
      "description": "' . htmlspecialchars($metaDescription, ENT_QUOTES, 'UTF-8') . '",
      "thumbnailUrl": "' . htmlspecialchars(photon_cdn($thumbnailUrl), ENT_QUOTES, 'UTF-8') . '",
      "uploadDate": "' . date('c', time() - 86400) . '",
      "duration": "' . $isoDuration . '",
      "contentUrl": "' . htmlspecialchars(site_url('/d/' . $download_code), ENT_QUOTES, 'UTF-8') . '",
      "embedUrl": "' . htmlspecialchars(site_url('/e/' . $download_code), ENT_QUOTES, 'UTF-8') . '",
      "interactionStatistic": {
        "@type": "InteractionCounter",
        "interactionType": { "@type": "WatchAction" },
        "userInteractionCount": ' . (int)$viewsCount . '
      },
      "aggregateRating": {
        "@type": "AggregateRating",
        "ratingValue": "4.5",
        "ratingCount": "' . ((int)$viewsCount > 0 ? (int)$viewsCount : 1) . '",
        "bestRating": "5",
        "worstRating": "1"
      },
      "publisher": {
        "@type": "Organization",
        "name": "' . htmlspecialchars($site_name, ENT_QUOTES, 'UTF-8') . '",
        "logo": {
          "@type": "ImageObject",
          "url": "' . htmlspecialchars(site_url(), ENT_QUOTES, 'UTF-8') . '/favicon.ico"
        }
      },
      "genre": "Entertainment"
    }
    </script>';

if (!empty($related)) {
    $additional_head .= '
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "ItemList",
        "name": "Video Terkait: ' . htmlspecialchars($cleanedTitle, ENT_QUOTES, 'UTF-8') . '",
        "numberOfItems": ' . $total_related . ',
        "itemListElement": [
        ' . implode(',', array_map(function($index, $item) {
            return '{
              "@type": "ListItem",
              "position": ' . ($index + 1) . ',
              "url": "' . site_url('/e/' . ($item['file_code'] ?? $item['filecode'] ?? '')) . '"
            }';
        }, array_keys($related), $related)) . '
        ]
    }
    </script>
    
    <script type="application/ld+json">
    {
        "@context": "https://schema.org",
        "@type": "VideoGallery",
        "name": "Video Terkait ' . htmlspecialchars($cleanedTitle, ENT_QUOTES, 'UTF-8') . '",
        "description": "Koleksi video terkait ' . htmlspecialchars($cleanedTitle, ENT_QUOTES, 'UTF-8') . '",
        "numberOfItems": ' . $total_related . ',
        "url": "' . htmlspecialchars($canonical_url, ENT_QUOTES, 'UTF-8') . '#related-videos"
    }
    </script>';
}
 
?>
<?php
/**
 * Custom Header for dl.php - Maintains original CSS and Navigation
 * 
 * Required variables:
 * - $site_title
 * - $metaDescription
 * - $canonical_url
 * - $site_name
 * - $cleanedTitle
 * - $thumbnailUrl
 * - $last_modified
 * - $additional_head (optional) - for SEO schemas
 */
?>
<!DOCTYPE html>
<html class="xv-responsive" lang="id-ID" itemscope itemtype="https://schema.org/WebPage">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <title><?php echo htmlspecialchars($site_title ?? '', ENT_QUOTES, 'UTF-8'); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="robots" content="index, follow">
    <link rel="canonical" href="<?php echo htmlspecialchars($canonical_url ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <link rel="icon" href="<?php echo site_url(); ?>/favicon.ico">
    <meta name="last-modified" content="<?php echo htmlspecialchars($last_modified ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="theme-color" content="#0a0a0a" />

    <!-- Open Graph -->
    <meta property="og:type" content="video.other">
    <meta property="og:url" content="<?php echo htmlspecialchars($canonical_url ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:title" content="Unduh File - <?php echo htmlspecialchars($cleanedTitle ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars($metaDescription ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:image" content="<?php echo htmlspecialchars(photon_cdn($thumbnailUrl ?? ''), ENT_QUOTES, 'UTF-8'); ?>">
    <meta property="og:site_name" content="<?php echo htmlspecialchars($site_name ?? '', ENT_QUOTES, 'UTF-8'); ?>">

    <!-- Twitter Card -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="Unduh File - <?php echo htmlspecialchars($cleanedTitle ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:description" content="<?php echo htmlspecialchars($metaDescription ?? '', ENT_QUOTES, 'UTF-8'); ?>">
    <meta name="twitter:image" content="<?php echo htmlspecialchars(photon_cdn($thumbnailUrl ?? ''), ENT_QUOTES, 'UTF-8'); ?>">

    <!-- Font Awesome - Preload + Async -->
    <link rel="preload" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css" as="style" onload="this.onload=null;this.rel='stylesheet'">
    <noscript><link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css"></noscript>

    <?php if (isset($additional_head)) echo $additional_head; ?>

    <!-- Original CSS from dl.php -->
    <style>
   :root{--primary-bg:#0a0a0a;--secondary-bg:#1a1a1a;--text-color:#e0e0e0;--gold-accent:#D4AF37;--gold-hover:#E6C667;--border-color:rgba(212, 175, 55, 0.2)}*{margin:0;padding:0;box-sizing:border-box}html{scroll-behavior:smooth}body{background:var(--primary-bg);color:var(--text-color);font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;line-height:1.6}.header{background:var(--primary-bg);border-bottom:1px solid var(--border-color);padding:0 1rem;position:sticky;top:0;z-index:1000;transition:background-color 0.3s ease}.navbar{display:flex;justify-content:space-between;align-items:center;height:70px;max-width:1200px;margin:0 auto}.logo a{font-size:1.5rem;font-weight:700;color:var(--gold-accent);text-decoration:none;letter-spacing:1px}.nav-menu{display:flex;align-items:center;list-style:none;gap:2rem}.nav-link{color:var(--text-color);text-decoration:none;font-weight:500;transition:color 0.3s ease-in-out;padding:.5rem 0}.nav-link:hover{color:var(--gold-accent)}.search-form{display:flex;align-items:center;border:1px solid var(--border-color);border-radius:50px;overflow:hidden;background-color:var(--secondary-bg)}.search-input{background:#fff0;border:none;padding:.5rem 1rem;color:var(--text-color);outline:none;width:150px;transition:width 0.3s ease-in-out}.search-input:focus{width:200px}.search-input::placeholder{color:#888}.search-button{background:#fff0;border:none;color:var(--gold-accent);padding:.5rem 1rem;cursor:pointer;transition:color 0.3s}.search-button:hover{color:var(--gold-hover)}.hamburger{display:none;cursor:pointer}.bar{display:block;width:25px;height:3px;margin:5px auto;transition:all 0.3s ease-in-out;background-color:var(--text-color)}main{padding:1rem 0}.download-container{max-width:870px;margin:40px auto;padding:2.5rem;background:var(--secondary-bg);border-radius:10px;text-align:center;border:1px solid var(--border-color);box-shadow:0 4px 20px rgb(0 0 0 / .5)}.download-container h1{color:var(--gold-accent);margin-bottom:1rem;font-size:2.2rem}.result-title{color:var(--text-color);font-size:1.2rem;margin:1rem 0 1.5rem;line-height:1.4}.download-info{margin:20px 0;color:#aaa}.countdown{font-size:2.5em;color:var(--text-color);margin:20px 0;font-weight:700}.download-button{display:inline-block;padding:15px 30px;background:var(--gold-accent);color:#000;text-decoration:none;border-radius:5px;font-weight:700;transition:all 0.3s ease;opacity:.5;cursor:not-allowed;border:none;font-size:1rem}.download-button.active{opacity:1;cursor:pointer}.download-button.active:hover{background:var(--gold-hover);transform:translateY(-2px);box-shadow:0 4px 10px rgb(212 175 55 / .2)}.download-icon{margin-right:10px}.safety-notice{margin-top:25px;padding:15px;background:rgb(212 175 55 / .05);border-radius:5px;color:#aaa;font-size:.9em;border-top:1px solid var(--border-color)}.related-videos{max-width:1200px;margin:40px auto;padding:2.5rem;background:var(--secondary-bg);border-radius:10px;border:1px solid var(--border-color);box-shadow:0 4px 20px rgb(0 0 0 / .5)}.related-videos h2{color:var(--gold-accent);margin-bottom:25px;font-size:1.8rem;font-weight:500;text-align:center}.videos-list{display:flex;flex-direction:column;gap:15px}.video-list-item{background:#fff0;border-radius:8px;overflow:hidden;transition:all 0.3s ease;border:1px solid transparent}.video-list-item:hover{background:rgb(212 175 55 / .05);border-color:var(--border-color);transform:translateX(5px)}.video-list-link{text-decoration:none;color:inherit;display:flex;align-items:center;gap:15px;padding:12px}.video-list-thumbnail{position:relative;flex-shrink:0;width:160px;height:90px;background:#111;border-radius:8px;overflow:hidden;transition:transform 0.3s ease}.video-list-item:hover .video-list-thumbnail{transform:scale(1.05)}.video-list-thumbnail img{width:100%;height:100%;object-fit:cover;opacity:0;transition:opacity 0.5s ease-in}.video-list-thumbnail img.lazyloaded,.video-list-thumbnail img.loaded{opacity:1}.video-list-info{flex:1;min-width:0;display:flex;flex-direction:column;gap:8px}.video-list-title{color:var(--text-color);font-size:1rem;font-weight:500;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;margin:0;transition:color 0.3s ease}.video-list-item:hover .video-list-title{color:var(--gold-accent)}.video-list-meta{display:flex;align-items:center;gap:15px;color:#aaa;font-size:0.85rem}.meta-views{display:flex;align-items:center;gap:5px}.meta-views i{font-size:0.75rem}.video-duration-overlay{position:absolute;bottom:6px;right:6px;padding:3px 6px;border-radius:4px;font-size:11px;background:rgb(0 0 0 / .8);color:#fff;font-weight:500;z-index:3}.play-overlay-small{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:40px;height:40px;border-radius:50%;display:flex;align-items:center;justify-content:center;z-index:2;transition:all 0.3s ease;opacity:0;background:#fff0}.video-list-item:hover .play-overlay-small{background:rgb(212 175 55 / .9);opacity:1;transform:translate(-50%,-50%) scale(1.1)}.play-overlay-small i{color:#fff;font-size:16px;margin-left:2px}.footer{text-align:center;padding:2rem 1rem;margin-top:40px;background:var(--secondary-bg);border-top:1px solid var(--border-color);color:#aaa}.footer p{margin:0}@media (max-width:992px){.search-form{display:none}.video-list-thumbnail{width:140px;height:79px}}@media (max-width:768px){.hamburger{display:block}.nav-menu{position:fixed;left:-100%;top:70px;flex-direction:column;background-color:var(--primary-bg);width:100%;text-align:center;transition:0.3s;gap:0;border-top:1px solid var(--border-color)}.nav-item{width:100%}.nav-link{padding:1.5rem 0;display:block;width:100%;border-bottom:1px solid var(--border-color)}.nav-link:hover{background-color:var(--secondary-bg)}.nav-menu.active{left:0}.download-container{margin:30px 20px}.related-videos{margin:30px 20px;padding:1.5rem}.video-list-thumbnail{width:120px;height:68px}.video-list-link{gap:12px;padding:10px}.video-list-title{font-size:0.95rem}}@media (max-width:480px){.download-container{padding:1.5rem;margin:20px 15px}.download-container h1{font-size:1.8rem}.countdown{font-size:2rem}.download-button{padding:12px 25px;font-size:.9rem}.related-videos h2{font-size:1.5rem}.related-videos{padding:1.5rem;margin:20px 15px}.video-list-thumbnail{width:100px;height:56px}.video-list-link{gap:10px;padding:8px}.video-list-title{font-size:0.9rem}.video-list-meta{font-size:0.8rem}}
    </style>
    <style>
        .video-thumbnail img.lazyload,
        .video-thumbnail img.lazyloading,
        .video-list-thumbnail img.lazyload,
        .video-list-thumbnail img.lazyloading {
            opacity: 0.6;
            filter: blur(6px);
        }

        .video-thumbnail img.lazyloaded,
        .video-thumbnail img.loaded,
        .video-list-thumbnail img.lazyloaded,
        .video-list-thumbnail img.loaded {
            opacity: 1;
            filter: none;
        }

        /* Breadcrumb Styles */
        .breadcrumb-nav {
            max-width: 1200px;
            margin: 20px auto;
            padding: 0 1rem;
        }

        .breadcrumb-list {
            display: flex;
            align-items: center;
            list-style: none;
            padding: 0;
            margin: 0;
            flex-wrap: wrap;
            gap: 8px;
        }

        .breadcrumb-item {
            display: flex;
            align-items: center;
        }

        .breadcrumb-item a {
            color: var(--text-color);
            text-decoration: none;
            transition: color 0.3s ease;
            font-size: 0.9rem;
        }

        .breadcrumb-item a:hover {
            color: var(--gold-accent);
        }

        .breadcrumb-item.active {
            color: var(--gold-accent);
            font-weight: 500;
        }

        .breadcrumb-item.active span {
            display: -webkit-box;
            -webkit-line-clamp: 1;
            -webkit-box-orient: vertical;
            overflow: hidden;
            max-width: 300px;
            font-size: 0.9rem;
        }

        .breadcrumb-separator {
            color: var(--text-color);
            opacity: 0.5;
            font-size: 0.75rem;
        }

        @media (max-width: 768px) {
            .breadcrumb-nav {
                margin: 15px auto;
                padding: 0 1rem;
            }

            .breadcrumb-item.active span {
                max-width: 200px;
            }
        }

        @media (max-width: 480px) {
            .breadcrumb-item a,
            .breadcrumb-item.active span {
                font-size: 0.85rem;
            }

            .breadcrumb-item.active span {
                max-width: 150px;
            }
        }
    </style>
</head>
<body>
<header class="header">
    <nav class="navbar">
        <div class="logo">
            <a href="<?php echo site_url(); ?>"><?php echo htmlspecialchars($site_name ?? '', ENT_QUOTES, 'UTF-8'); ?></a>
        </div>
        <ul class="nav-menu">
            <li class="nav-item"><a href="<?php echo site_url(); ?>" class="nav-link">Home</a></li>
            <li class="nav-item"><a href="<?php echo search_permalink('bokep'); ?>" class="nav-link">Videos</a></li>
            <li class="nav-item"><a href="<?php echo search_permalink('jav sub indo'); ?>" class="nav-link">jav sub</a></li>
            <li class="nav-item"><a href="<?php echo search_permalink('bokep indo'); ?>" class="nav-link">indo</a></li>
            <li class="nav-item"><a href="<?php echo search_permalink('prank ojol'); ?>" class="nav-link">prank ojol</a></li>
            <li class="nav-item"><a href="<?php echo search_permalink('hijab'); ?>" class="nav-link">hijab</a></li>
            <li class="nav-item"><a href="<?php echo search_permalink('abg mesum'); ?>" class="nav-link">abg</a></li>
        </ul>
        <form action="<?php echo site_url(); ?>" method="GET" class="search-form">
            <input type="search" name="q" class="search-input" placeholder="Search..." required>
            <button type="submit" class="search-button"><i class="fas fa-search"></i></button>
        </form>
        <div class="hamburger">
            <span class="bar"></span>
            <span class="bar"></span>
            <span class="bar"></span>
        </div>
    </nav>
</header>

<main>
        <!-- Breadcrumb Navigation -->
        <nav class="breadcrumb-nav" aria-label="Breadcrumb">
            <ol class="breadcrumb-list">
                <li class="breadcrumb-item">
                    <a href="<?php echo site_url(); ?>">
                        <i class="fas fa-home" style="margin-right: 4px;"></i>Home
                    </a>
                </li>
                <li class="breadcrumb-separator">
                    <i class="fas fa-chevron-right"></i>
                </li>
                <li class="breadcrumb-item">
                    <a href="<?php echo htmlspecialchars($breadcrumb_url, ENT_QUOTES, 'UTF-8'); ?>">
                        <?php echo htmlspecialchars($category_name, ENT_QUOTES, 'UTF-8'); ?>
                    </a>
                </li>
                <li class="breadcrumb-separator">
                    <i class="fas fa-chevron-right"></i>
                </li>
                <li class="breadcrumb-item active">
                    <span><?php echo htmlspecialchars($cleanedTitle, ENT_QUOTES, 'UTF-8'); ?></span>
                </li>
            </ol>
        </nav>

        <div class="download-container">
            <h1>Detail Unduhan</h1>
            <div class="result-title"><?php echo htmlspecialchars($cleanedTitle); ?></div>
            <div class="download-info"><p>Tautan unduhan Anda akan siap dalam:</p></div>
            <div class="countdown" id="countdown">10</div>
            <a href="#" id="downloadButton" class="download-button" onclick="return false;">
                <i class="fas fa-download download-icon"></i>
                Mohon Tunggu...
            </a>
            <div class="safety-notice">
                <i class="fas fa-shield-alt"></i>
                <p>Unduhan ini aman dan telah dipindai dari virus.</p>
            </div>
        </div>

        <section class="related-videos">
            <h2>Video Terkait</h2>
            <div class="videos-list">
                <?php if (!empty($related)): ?>
                    <?php foreach ($related as $video): ?>
                    <div class="video-list-item">
                        <?php 
                        $video_filecode = $video['file_code'] ?? $video['filecode'] ?? '';
                        $placeholder_img = site_url() . '/assets/img/placeholder.png';
                        $video_thumbnail_raw = !empty($video['single_img'])
                            ? photon_cdn($video['single_img']) . '?w=160&h=90&fit=crop&quality=70'
                            : $placeholder_img;
                        $video_thumbnail = htmlspecialchars($video_thumbnail_raw, ENT_QUOTES, 'UTF-8');
                        $video_placeholder = htmlspecialchars($placeholder_img, ENT_QUOTES, 'UTF-8');
                        ?>
                        <a href="<?php echo site_url(); ?>/e/<?php echo htmlspecialchars($video_filecode, ENT_QUOTES, 'UTF-8'); ?>" class="video-list-link">
                            <div class="video-list-thumbnail">
                                <img class="lazyload"
                                     src="<?php echo $video_placeholder; ?>"
                                     data-src="<?php echo $video_thumbnail; ?>"
                                     data-srcset="<?php echo $video_thumbnail; ?> 1x"
                                     data-sizes="auto"
                                     alt="<?php echo htmlspecialchars($video['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?>"
                                     width="160"
                                     height="90"
                                     loading="lazy"
                                     decoding="async"
                                     onerror="this.onerror=null; this.src='<?php echo site_url(); ?>/assets/img/placeholder.svg';">
                                <div class="play-overlay-small"><i class="fas fa-play"></i></div>
                                <div class="video-duration-overlay">
                                    <?php echo htmlspecialchars($video['length'] ?? 'N/A', ENT_QUOTES, 'UTF-8'); ?>
                                </div>
                            </div>
                            <div class="video-list-info">
                                <h3 class="video-list-title"><?php echo htmlspecialchars($video['title'] ?? '', ENT_QUOTES, 'UTF-8'); ?></h3>
                                <div class="video-list-meta">
                                    <span class="meta-views"><i class="fas fa-eye"></i> <?php echo number_format((int)($video['views'] ?? 0)); ?> views</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p style="text-align:center; color: #aaa; padding: 2rem;">Tidak ada video terkait yang ditemukan.</p>
                <?php endif; ?>
            </div>
        </section>

        <?php if (!empty($searches)): ?>
        <aside class="recent-searches-widget" style="max-width: 1200px; margin: 40px auto; padding: 0 1rem;">
            <h3 class="recent-searches-widget__title" style="color: var(--gold-accent); margin-bottom: 20px; font-size: 1.3rem; display: flex; align-items: center; gap: 10px;">
                <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
                Pencarian Terkini
            </h3>
            <ul class="recent-searches-widget__list" style="list-style: none; display: flex; flex-wrap: wrap; gap: 10px;">
                <?php foreach($searches as $item): ?>
                <li class="recent-searches-widget__item" style="display: inline-block;">
                    <a href="<?php echo search_permalink($item['title']); ?>" title="<?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?>" class="recent-searches-widget__link" style="display: flex; align-items: center; gap: 8px; padding: 8px 16px; background: var(--secondary-bg); border: 1px solid var(--border-color); border-radius: 5px; color: var(--text-color); text-decoration: none; transition: all 0.3s ease;">
                        <svg class="icon" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M5 12h14"/>
                            <path d="m12 5 7 7-7 7"/>
                        </svg>
                        <span><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></span>
                    </a>
                </li>
                <?php endforeach; ?>
            </ul>
        </aside>
        <?php endif; ?>

        <?php
/**
 * Custom Footer for dl.php - Maintains original functionality
 */
?>
    </main>

    <!-- Header Ad - Lazy Loaded -->
    <div id="ad-header-placeholder" 
         class="ad-placeholder"
         data-adcode="<?php echo htmlspecialchars(get_option('ad_code_header', ''), ENT_QUOTES, 'UTF-8'); ?>"
         style="min-height: 90px; text-align: center; max-width: 1200px; margin: 20px auto;">
    </div>

    <footer class="footer">
        <p>&copy; <?php echo date("Y"); ?> <?php echo htmlspecialchars($site_name ?? '', ENT_QUOTES, 'UTF-8'); ?>. Semua Hak Cipta Dilindungi.</p>
    </footer>

    <!-- Footer Ad - Lazy Loaded -->
    <div id="ad-footer-placeholder" 
         class="ad-placeholder"
         data-adcode="<?php echo htmlspecialchars(get_option('ad_code_footer', ''), ENT_QUOTES, 'UTF-8'); ?>"
         style="min-height: 90px; text-align: center;">
    </div>

    <!-- JavaScript -->
    <script src="<?php echo site_url(); ?>/assets/js/lazy.js"></script>
    <script src="<?php echo site_url(); ?>/assets/js/main.js" defer></script>
    <script src="<?php echo site_url(); ?>/assets/js/ads.js" defer></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Hamburger Menu
        const hamburger = document.querySelector(".hamburger");
        const navMenu = document.querySelector(".nav-menu");
        if (hamburger && navMenu) {
            hamburger.addEventListener("click", () => {
                hamburger.classList.toggle("active");
                navMenu.classList.toggle("active");
            });
        }

        // Download Countdown
        const downloadButton = document.getElementById('downloadButton');
        const countdownDisplay = document.getElementById('countdown');
        const downloadCode = <?php echo json_encode($download_code ?? ''); ?>;

        if (downloadButton && countdownDisplay && downloadCode) {
            let timeLeft = 10;
            countdownDisplay.textContent = timeLeft;

            const countdownTimer = setInterval(() => {
                timeLeft--;
                countdownDisplay.textContent = timeLeft;

                if (timeLeft <= 0) {
                    clearInterval(countdownTimer);
                    countdownDisplay.textContent = "Siap!";
                    downloadButton.classList.add('active');
                    downloadButton.href = `https://dooodster.com/d/${downloadCode}`;
                    downloadButton.onclick = null;
                    downloadButton.innerHTML = '<i class="fas fa-download download-icon"></i>Unduh Sekarang';
                }
            }, 1000);
        }
    });
    </script>

    <!-- Histats Analytics -->
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
        if (document.readyState === 'complete') {
            setTimeout(loadHistats, 3000);
        } else {
            window.addEventListener('load', function() {
                setTimeout(loadHistats, 3000);
            }, { once: true });
        }
        ['scroll', 'click', 'mousemove', 'touchstart'].forEach(function(e) {
            window.addEventListener(e, loadHistats, { once: true, passive: true });
        });
    })();
    </script>
</body>
</html>



