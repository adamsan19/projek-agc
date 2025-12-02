<div class="row">
<div class="col-md-9 new-content">
<div class="nxt-titlepage nxt-icon">
<h1>Download Lagu MP3 2020</h1></div>
<div class="go-konten"><b><?php echo get_option( 'site_name' ); ?></b> adalah media musik digital gratis dengan jutaan link download audio mp3 dan video mp4. Dapatkan semua konten download paling lengkap dan terbaru.</div>
<div class="widget-title text-center">
<h3>Menu</h3></div>
<ul class="menu">
<li><a href="<?php echo playlist_permalink( 'dangdut' ); ?>" class="btn" title="Download Lagu Dangdut">Dangdut</a></li>
<li><a href="<?php echo playlist_permalink( 'lagu-populer' ); ?>" class="btn" title="Download Lagu Populer">Hits</a></li>
</ul>
<div class="widget-title text-center">
<h3>Terpopuler iTunes</h3>
</div>
<?php if ( $top_songs ) { ?>
<?php foreach ( $top_songs as $item ) { ?>
<div class="box-post nxt-archive media">
<a href="<?php echo search_permalink( $item['title'] ); ?>" class="pull-left"><img loading="lazy" src="<?php echo $item['image']; ?>?resize=96,96" class="media-object" alt="<?php echo $item['title']; ?>" width="96" height="96"></a>
<div class="media-body">
<h2 class="nxt-titlemini"><a href="<?php echo search_permalink( $item['title'] ); ?>"><?php echo $item['title']; ?></a></h2>
<span class="nxt-hits"><?php echo $item['artist']; ?></span>
<span class="file-size"><?php echo $item['genre']; ?></span>
<span class="nxt-date"><?php echo timeline($item['date']); ?></span>
</div>
</div>
<?php } unset( $item ); ?>
<?php } ?>
</div>
<div class="col-md-3 widget-area">
<aside class="widget toplist">
<h4 class="widget-title">Lagu Terpopuler</h4>
<ul>
<?php if ( $playlist ) { ?>
<?php foreach ( $playlist as $item ) { ?>
<li>
<a href="<?php echo search_permalink( $item['title'] ); ?>"><img loading="lazy" class="nxt-img" src="<?php echo $item['image']; ?>?resize=320,100" alt="<?php echo $item['title']; ?>" /> <span class="nxt-title"><?php echo $item['title']; ?></span> <span class="duration"><?php echo timeline($item['date']); ?></span></a>
</li>
<?php } unset( $item ); ?>
<?php } ?>
</ul>
</aside>
<?php if ( $searches ) { ?>
<aside class="widget toplist">
<h4 class="widget-title">Lagu Terbaru</h4>
<ul>
<?php foreach( $searches as $item ) { ?>
<li><a href="<?php echo search_permalink( $item['title'] ); ?>" title="<?php echo $item['title']; ?>"><?php echo $item['title']; ?></a></li>
<?php } ?>
</ul>
</aside>
<?php } ?>
</div>
</div>