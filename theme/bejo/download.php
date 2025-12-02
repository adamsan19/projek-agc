<div class="wrapper">




    <div class="menu top">

    <div class="player_block" style="width: 100%; height: 123vh; position: relative; background: url('<?= htmlspecialchars($result['splash_img']) ?>') no-repeat center center; background-size: contain; background-color: black;">
    <div class="player" style="width: 100%; height: 100%; position: absolute; top: 0; left: 0;">
        <iframe id="videoIframe" 
                class="iframe" 
                frameborder="0" 
                allowfullscreen 
                style="width: 100%; height: 100%; display: none;" 
                referrerpolicy="no-referrer" 
                loading="lazy" 
                title="<?= htmlspecialchars($data['title']) ?>" 
                aria-label="Video player for <?= htmlspecialchars($result['title']) ?>">
        </iframe>
        <button id="playButton" 
                style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background-color: transparent; border: none; padding: 20px; border-radius: 50%;" 
                aria-label="Play <?= htmlspecialchars($result['title']) ?>">
            <img src="/play.svg" 
                 alt="Play button for <?= htmlspecialchars($result['title']) ?>" 
                 width="100" 
                 height="150"
                 loading="eager"
                 title="Click to play video">
        </button>
    </div></div>


    <script>
    document.addEventListener("DOMContentLoaded", function() {
        document.getElementById('playButton').addEventListener('click', function() {
            console.log('Play button clicked');
            const videoUrl = '<?= htmlspecialchars($result['protected_embed']) ?>';
            const iframe = document.getElementById('videoIframe');
            
            console.log('Setting iframe source to:', videoUrl);
            iframe.src = videoUrl;
            iframe.style.display = 'block';
            this.style.display = 'none';
        });
    });
</script>
        <div class="img-flex" style="margin: 0 10px 10px 0;"><img src="<?php echo $result['image']; ?>?resize=80,80" /></div>
        <h1><?php echo str_replace( '%title%', $result['title'], get_option( 'download_page_title' ) ); ?></h1>
        <div class="small">
            <i>
                Upload on
                <?php echo timeline($result['date']); ?>
            </i>
        </div>
    </div>
</div>
<div class="wrapper">
    <div class="kiri">
        <div class="menu">
            <h3 class="title-class">File Info</h3>
            <p>
                Channel: <b><?php echo $result['channel']; ?></b>
            </p>
            <p>
                Title: <b><?php echo $result['title']; ?></b>
            </p>
            <p>Album: <b>-</b></p>
            <p>Genre: <b>-</b></p>
            <p>
                Released: <b><?php echo $result['date']; ?></b>
            </p>
            <p>
                Size: <b><?php echo $result['size']; ?></b>
            </p>
            <p>
                Duration: <b><?php echo $result['duration']; ?></b>
            </p>
            <br />
            <div id="playing" align="center">
                <p style="padding: 0 0 10px;">
                    <a class="download" href="/dl/?g=<?php echo $result['id']; ?>&t=<?php echo $result['title']; ?>&d=<?php echo get_option( 'site_name' ); ?>" rel="nofollow noopener noreferrer">Download MP3</a><br />
                </p>
            </div>
        </div>
        <div class="menu">
            <h3 class="title-class">
                Lirik lagu
                <?php echo $result['title']; ?>
            </h3>
            <?php echo $genius['lyric']; ?>
        </div>
    </div>
    <div class="kanan">
        <div class="menu">
            <?php if ( $related ) { ?>
            <h3 class="title-class">Related</h3>
            <?php foreach ( $related as $item ) { ?>
            <div class="track">
                <div class="img-flex"></div>
                <div class="item-flex">
                    <h3 class="title">
                        <a href="<?php echo download_permalink( $item['id'] ); ?>" title="<?php echo htmlentities( $item['title'], ENT_QUOTES ); ?> mp3 lyrics"><?php echo $item['title']; ?></a>
                    </h3>
                    <div class="small">
                        <span><?php echo timeline($item['date']); ?></span>
                    </div>
                </div>
            </div>
            <?php } unset( $item ); ?><?php } ?>
        </div>
    </div>
    <div class="clear"></div>
</div>
