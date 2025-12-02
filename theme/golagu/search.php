<div class="row">
<div class="col-md-9 new-content">
<div class="nxt-titlepage nxt-icon text-center">
<h1><?php echo str_replace( '%query%', get_search_query(), get_option( 'search_page_title' ) ); ?></h1>
</div>
<div class="go-konten media">Download Mp3 <?php echo get_search_query(); ?> Gratis, Ada <?php echo get_option( 'youtube_search_count' ); ?> daftar lagu <?php echo get_search_query(); ?> yang bisa anda download.</div>
<?php if ( $result ) { ?>
<?php foreach ( $result as $item ) { ?>
<div class="box-post nxt-archive media">
<a href="<?php echo download_permalink( $item['id'] ); ?>" class="pull-left"><img loading="lazy" src="<?php echo $item['image']; ?>?resize=96,96" class="media-object" alt="<?php echo $item['title']; ?>" width="96" height="96"> <span class="duration"><?php echo $item['duration']; ?></span></a>
<div class="media-body">
<h2 class="nxt-titlemini"><a href="<?php echo download_permalink( $item['id'] ); ?>"><?php echo $item['title']; ?>...</a></h2><span class="file-size"><?php echo $item['size']; ?></span> <span class="nxt-date"><?php echo $item['date']; ?></span></div>
</div>
<?php } unset( $item ); ?>
<?php } ?>
</div>
<div class="col-md-3 widget-area">
<?php if ( $playlist ) { ?>
<aside class="widget toplist">
<h4 class="widget-title">Lagu Terpopuler</h4>
<ul>
<?php foreach ( $playlist as $item ) { ?>
<li>
<a href="<?php echo search_permalink( $item['title'] ); ?>"><img loading="lazy" class="nxt-img" src="<?php echo $item['image']; ?>?resize=320,100" alt="<?php echo $item['title']; ?>" /> <span class="nxt-title"><?php echo $item['title']; ?></span> <span class="duration"><?php echo timeline($item['date']); ?></span></a>
</li>
<?php } unset( $item ); ?>
</ul>
</aside>
<?php } ?>
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