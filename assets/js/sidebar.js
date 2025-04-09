$(document).ready(function() {
    const $menuToggle = $('#menu-toggle');
    const $sidebar = $('.sidebar');
    const $overlay = $('.sidebar-overlay');
    const $body = $('body');
    const $sidebarToggle = $('.sidebar-toggle');

    function toggleMenu() {
        $menuToggle.toggleClass('active');
        $sidebar.toggleClass('active');
        $overlay.toggleClass('active');
        $body.toggleClass('menu-open');
    }

    $menuToggle.on('click', function(e) {
        e.preventDefault();
        toggleMenu();
    });

    $overlay.on('click', toggleMenu);

    $('.sidebar a').on('click', function() {
        if (window.innerWidth <= 768) {
            toggleMenu();
        }
    });

    $(window).on('resize', function() {
        if (window.innerWidth > 768) {
            $menuToggle.removeClass('active');
            $sidebar.removeClass('active');
            $overlay.removeClass('active');
            $body.removeClass('menu-open');
        }
    });

    $sidebarToggle.on('click', function() {
        $sidebar.toggleClass('collapsed');
        
        localStorage.setItem('sidebarCollapsed', $sidebar.hasClass('collapsed'));
    });

    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        $sidebar.addClass('collapsed');
    }
}); 