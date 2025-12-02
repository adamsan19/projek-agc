<footer><div class="footer"><a href="#">About</a><a href="#">Privacy Policy</a></div><?php echo str_replace( [ '%year%', '%site_name%' ], [ date( 'Y' ), get_option( 'site_name' ) ], get_option( 'footer_copyright' ) ); ?></footer><?php
if ( file_exists( BASE . '/config/' . $_SERVER['HTTP_HOST'] . '/_footer.php' ) ) {
include BASE . '/config/' . $_SERVER['HTTP_HOST'] . '/_footer.php';
}?></body></html>