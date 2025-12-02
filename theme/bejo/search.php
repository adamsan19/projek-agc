<div class="wrapper">
    <div class="menu top">
        <h1 class="title-class"><?php echo str_replace( '%query%', get_search_query(), get_option( 'search_page_title' ) ); ?></h1>
        <div class="img-flex" style="margin: 10px 10px 10px 0;"><img src="<?php echo $result[0]['splash_img']; ?>?resize=80,80" /></div>
        Download lagu <strong><?php echo get_search_query(); ?></strong> MP3 dapat kamu download secara gratis di
        <?php echo get_option( 'site_name' ); ?>. Untuk melihat detail lagu
        <?php echo get_search_query(); ?>
        klik salah satu judul yang cocok, kemudian untuk link download
        <?php echo get_search_query(); ?>
        ada di halaman berikutnya.
    </div>
</div>
<div class="wrapper">
    <div class="kiri">
        <div class="menu">
            <?php if ( $result ) { ?>
            <h3 class="title-class">Hasil Lagu MP3</h3>
            <?php foreach ( $result as $item ) { ?>
            <div class="track">
            <div class="img-flex"><img src="<?php echo $item['image']; ?>?resize=80,80" /></div>
                <div class="item-flex">
                    <h3 class="title">
                        <a href="<?php echo download_permalink( $item['id'] ); ?>" title="<?php echo htmlentities( $item['title'], ENT_QUOTES ); ?> mp3 lyrics"><?php echo $item['title']; ?></a>
                    </h3>
                    <div class="small">
                        <span>
                            Size:
                            <?php echo $item['views']; ?>
                        </span>
                    </div>
                </div>
            </div>
            <?php } unset( $item ); ?><?php } ?>
        </div>
    </div>
    <div class="kanan">
        <?php if ( $searches ) { ?>
        <div class="menu">
            <h3 class="title-class">Recent Download</h3>
            <?php foreach( $searches as $item ) { ?>
            <a class="recent" href="<?php echo search_permalink( $item['title'] ); ?>"><?php echo $item['title']; ?></a>
            <?php } ?>
        </div>
        <?php } ?>
    </div>
    <div class="clear"></div>
</div>
