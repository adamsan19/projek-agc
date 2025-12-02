<?php if (isset($_SESSION['login']) && $_SESSION['login'] == $hash) { ?>
  </div>
  <!-- End of Page Wrapper -->

  <!-- Scroll to Top Button-->
  <a class="scroll-to-top rounded" href="#page-top">
    <i class="fas fa-angle-up"></i>
  </a>

  <!-- Logout Modal-->
  <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">Ready to Leave?</h5>
          <button class="close" type="button" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">×</span>
          </button>
        </div>
        <div class="modal-body">Select "Logout" below if you are ready to end your current session.</div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-dismiss="modal">Cancel</button>
          <a class="btn btn-primary" href="<?php echo lite_url('login.php?logout=true'); ?>">Logout</a>
        </div>
      </div>
    </div>
  </div>

  <!-- Changelog Modal-->
  <div class="modal fade" id="changelogModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="exampleModalLabel">BejoLite Changelogs</h5>
        </div>
        <div class="modal-body"><b>Changelog version 1.0</b><br/>
		- Release </div>
        <div class="modal-footer">
          <button class="btn btn-primary" type="button" data-dismiss="modal">OK</button>
        </div>
      </div>
    </div>
  </div>
    <!-- Bootstrap core JavaScript-->
    <script src="includes/vendor/jquery/jquery.min.js"></script>
    <script src="includes/vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    <!-- Core plugin JavaScript-->
    <script src="includes/vendor/jquery-easing/jquery.easing.min.js"></script>

    <!-- Custom scripts for all pages-->
    <script src="includes/js/sb-admin-2.min.js"></script>

 
  <!-- Custom scripts for all pages-->
  <script src="assets/js/sb-admin-2.min.js"></script>
  
  <!-- Mobile Navigation Scripts -->
  <script>
    // Wait for all scripts to load, then initialize collapse
    $(window).on('load', function() {
      // Ensure all collapse menus are closed (not shown) on first page load
      function ensureCollapseClosed() {
        $('.sidebar .collapse').each(function() {
          var $collapse = $(this);
          $collapse.removeClass('show');
          var collapseId = '#' + $collapse.attr('id');
          $('.sidebar .nav-link[data-target="' + collapseId + '"]').addClass('collapsed').attr('aria-expanded', 'false');
        });
      }

      // Run immediately
      ensureCollapseClosed();

      // Ensure Bootstrap collapse is initialized for all collapse elements
      if (typeof $.fn.collapse !== 'undefined') {
        $('[data-toggle="collapse"]').each(function() {
          var $this = $(this);
          var target = $this.attr('data-target');
          if (target && $(target).length) {
            var $target = $(target);
            // Initialize collapse if not already initialized
            if (!$target.data('bs.collapse')) {
              $target.collapse({
                toggle: false
              });
            }
            // Force hide and update aria attributes
            $target.removeClass('show');
            $this.addClass('collapsed').attr('aria-expanded', 'false');
          }
        });

        // Listen for collapse events to update aria-expanded dynamically
        $('.sidebar .collapse').on('show.bs.collapse', function() {
          var collapseId = '#' + $(this).attr('id');
          $('.sidebar .nav-link[data-target="' + collapseId + '"]').removeClass('collapsed').attr('aria-expanded', 'true');
        }).on('hide.bs.collapse', function() {
          var collapseId = '#' + $(this).attr('id');
          $('.sidebar .nav-link[data-target="' + collapseId + '"]').addClass('collapsed').attr('aria-expanded', 'false');
        });
      }
    });

    // Also run on document ready as fallback
    $(document).ready(function() {
      // Ensure all collapse menus are closed
      $('.sidebar .collapse').removeClass('show');
      $('.sidebar .nav-link[data-toggle="collapse"]').addClass('collapsed').attr('aria-expanded', 'false');
      
      // Ensure sidebar is hidden on mobile by default
      if ($(window).width() <= 768) {
        $('#accordionSidebar').removeClass('show');
        $('body').removeClass('sidebar-toggled');
        $('.sidebar-overlay').removeClass('show');
      }

      // Sidebar toggle for mobile
      $('#sidebarToggleTop').on('click touchstart', function(e) {
        e.preventDefault();
        e.stopPropagation();
        var $body = $('body');
        var $sidebar = $('#accordionSidebar');
        var $overlay = $('.sidebar-overlay');
        
        // Toggle classes
        $body.toggleClass('sidebar-toggled');
        $sidebar.toggleClass('show');
        $overlay.toggleClass('show');
        
        // Force show sidebar if it has show class
        if ($sidebar.hasClass('show')) {
          $sidebar.css({
            'transform': 'translateX(0)',
            'display': 'block',
            'visibility': 'visible',
            'opacity': '1',
            'width': '70vw',
            'max-width': '280px',
            'min-width': '240px'
          });
        } else {
          $sidebar.css({
            'transform': 'translateX(-100%)',
            'display': 'block',
            'visibility': 'visible',
            'opacity': '1'
          });
        }
        
        // Prevent body scroll when sidebar is open
        if ($overlay.hasClass('show')) {
          $('body').css('overflow', 'hidden');
        } else {
          $('body').css('overflow', '');
        }
      });
      // Close sidebar on overlay click
      $('.sidebar-overlay').on('click touchstart', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('body').removeClass('sidebar-toggled').css('overflow', '');
        $('#accordionSidebar').removeClass('show');
        $('.sidebar-overlay').removeClass('show');
      });
      // Close sidebar on menu click (optional) - but not for collapse toggles
      $('.sidebar .nav-link').not('[data-toggle="collapse"]').on('click', function() {
        if ($(window).width() <= 768) {
          $('body').removeClass('sidebar-toggled');
          $('#accordionSidebar').removeClass('show');
          $('.sidebar-overlay').removeClass('show');
        }
      });
    });
  </script>
<?php } ?>
</body>
</html>