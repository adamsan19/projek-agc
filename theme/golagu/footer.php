<div class="fitur noleft">
<ul>
<li>Gratis</li>
<li>Lengkap</li>
<li>Cepat</li>
<li>Mudah</li>
<li>Tanpa Registrasi</li>
</ul>
</div>
</section>
</div>
</div>
</div>
<?php if ( $route['name'] == 'download' ) { ?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/mediaelement@4.2.7/build/mediaelementplayer.min.css">
<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js" type="text/javascript"></script>
<script src="https://cdn.jsdelivr.net/npm/mediaelement@4.2.7/build/mediaelement-and-player.min.js" type="text/javascript"></script>
<script type="text/javascript">
$(document).ready(function(){
$('audio').mediaelementplayer();
})
</script>
<?php } ?>
<footer class="footer">
<div class="copyright container">
<?php echo str_replace( [ '%year%', '%site_name%' ], [ date( 'Y' ), get_option( 'site_name' ) ], get_option( 'footer_copyright' ) ); ?>
</div>
</footer></div>
</body>
</html>