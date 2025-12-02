<?php 
if (!defined('BASE')) { define('BASE', __DIR__); }

require_once 'core/functions/options.php';

$site_title = '404 - Page Not Found';
$meta_description = 'The page you are looking for might have been removed, had its name changed, or is temporarily unavailable.';
$meta_robots = 'noindex,nofollow';
$site_name = get_option('site_name') ?? 'DoodX.us';
$ogimage = '/favicon.ico';

require_once 'header.php';
?>

<div class="flex-1 p-0">
    <div class="rounded-lg border bg-card text-card-foreground shadow-sm w-full mx-auto max-w-4xl m-4">
        <div class="p-4 sm:p-6 space-y-4">
            <div class="text-center">
                <h1 class="text-4xl font-bold text-red-600 mb-4">404 - Page Not Found</h1>
                <p class="text-lg text-muted-foreground mb-6">Sorry, the page you are looking for could not be found. This might be because:</p>
                
                <div class="text-left max-w-2xl mx-auto space-y-4">
                    <ul class="list-disc list-inside space-y-2 text-muted-foreground">
                        <li>The page has been moved or deleted</li>
                        <li>The URL was typed incorrectly</li>
                        <li>The page is temporarily unavailable</li>
                    </ul>
                    
                    <div class="mt-6">
                        <p class="text-muted-foreground mb-4">You can try:</p>
                        <ul class="list-disc list-inside space-y-2 text-muted-foreground">
                            <li>Going back to the <a href="<?php echo site_url(); ?>" class="text-primary hover:underline">homepage</a></li>
                            <li>Using the search box to find what you're looking for</li>
                            <li>Checking the URL for any typos</li>
                        </ul>
                    </div>
                </div>
                
                <div class="mt-8">
                    <div id="countdown-box" class="mb-4">
                        <span id="countdown" class="text-2xl font-bold text-blue-600">5</span> <span class="text-muted-foreground">detik menuju Home...</span>
                    </div>
                    <a id="homeBtn" href="<?php echo site_url(); ?>" style="display:none" class="inline-flex items-center justify-center gap-2 whitespace-nowrap rounded-md text-sm ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50 bg-primary text-primary-foreground hover:bg-primary/90 h-10 px-4 py-2">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4">
                            <path d="m15 18-6-6 6-6"/>
                        </svg>
                        Kembali ke Homepage
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
<script>
// Countdown logic
(function() {
    var seconds = 5;
    var countdownEl = document.getElementById('countdown');
    var homeBtn = document.getElementById('homeBtn');
    var timer = setInterval(function() {
        seconds--;
        if (seconds > 0) {
            countdownEl.textContent = seconds;
        } else {
            clearInterval(timer);
            document.getElementById('countdown-box').style.display = 'none';
            if (homeBtn) homeBtn.style.display = 'inline-flex';
        }
    }, 1000);
})();
</script> 