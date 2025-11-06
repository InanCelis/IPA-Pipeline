<?php
/**
 * Dashboard Mobile Header Component
 * Unified mobile header with hamburger menu for all dashboard pages
 */
$dashboard_logo = houzez_option('dashboard_logo', false, 'url');
$current_user = wp_get_current_user();
$user_avatar = get_avatar_url($current_user->ID);
$dash_profile_link = houzez_get_template_link_2('template/user_dashboard_profile.php');
?>

<!-- Mobile Header Bar (Sticky) -->
<div class="dashboard-mobile-header">
    <button class="mobile-sidebar-toggle" id="mobileSidebarToggle" aria-label="Toggle Menu">
        <i class="houzez-icon icon-navigation-menu"></i>
    </button>

    <a href="<?php echo esc_url(home_url('/')); ?>" class="mobile-header-logo">
        <img src="<?php echo esc_url($dashboard_logo); ?>" alt="<?php bloginfo('name'); ?>">
    </a>

    <a href="<?php echo esc_url($dash_profile_link); ?>" class="mobile-header-profile">
        <img src="<?php echo esc_url($user_avatar); ?>" alt="Profile" class="mobile-avatar">
    </a>
</div>

<!-- Sidebar Overlay -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<style>
    /* Mobile Header Bar - Hidden by default on desktop */
    #wpadminbar {
        display: none !important;
    }

    .dashboard-mobile-header {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        width: 100%;
        height: 60px;
        background: #004274;
        z-index: 10000;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        align-items: center;
        justify-content: space-between;
        padding: 0 15px;
    }

    /* Hamburger Menu Button */
    .mobile-sidebar-toggle {
        background: transparent;
        border: none;
        color: white;
        cursor: pointer;
        padding: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .mobile-sidebar-toggle:hover {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 4px;
    }

    .mobile-sidebar-toggle i {
        font-size: 24px;
        margin: 0;
    }

    /* Logo */
    .mobile-header-logo {
        flex: 1;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0 10px;
    }

    .mobile-header-logo img {
        max-height: 40px;
        width: auto;
        object-fit: contain;
    }

    /* Profile Icon */
    .mobile-header-profile {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 5px;
        transition: all 0.3s ease;
    }

    .mobile-header-profile:hover {
        background: rgba(255, 255, 255, 0.1);
        border-radius: 50%;
    }

    .mobile-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        border: 2px solid white;
        object-fit: cover;
    }

    /* Mobile sidebar overlay */
    .sidebar-overlay {
        display: none;
        position: fixed;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background: rgba(0,0,0,0.6);
        z-index: 9999;
        opacity: 0;
        transition: opacity 0.3s ease;
    }

    .sidebar-overlay.active {
        opacity: 1;
    }

    /* Mobile responsive styles - Only show on mobile/tablet */
    @media (max-width: 991px) {
        /* Show mobile header bar */
        .dashboard-mobile-header {
            display: flex !important;
        }

        /* Add padding to body content to account for fixed header */
        body {
            padding-top: 60px !important;
        }

        /* Hide ALL desktop dashboard headers on mobile */
        #header-mobile {
            display: none !important;
            visibility: hidden !important;
            height: 0 !important;
            overflow: hidden !important;
        }

        /* Sidebar styling for mobile */
        .dashboard-side-wrap {
            display: block !important;
            transform: translateX(-100%);
            transition: transform 0.3s ease;
            z-index: 10001;
            box-shadow: 2px 0 15px rgba(0,0,0,0.2);
            padding-top: 60px; /* Account for fixed mobile header */
        }

        .dashboard-side-wrap.mobile-active {
            transform: translateX(0);
        }

        /* Adjust dashboard content for mobile */
        .dashboard-content-wrap {
            padding-top: 20px;
        }
    }

    @media (max-width: 768px) {
        .dashboard-header-wrap {
            padding: 20px 20px 20px;
        }
        .dashboard-mobile-header {
            height: 60px;
        }

        body {
            padding-top: 55px;
        }

        .dashboard-side-wrap {
            padding-top: 55px;
        }

        .mobile-header-logo img {
            max-height: 35px;
        }

        .mobile-avatar {
            width: 32px;
            height: 32px;
        }

        .mobile-sidebar-toggle i {
            font-size: 22px;
        }
    }

    @media (max-width: 480px) {
        .dashboard-mobile-header {
            height: 50px;
            padding: 0 10px;
        }

        body {
            padding-top: 50px;
        }

        .dashboard-side-wrap {
            padding-top: 50px;
        }

        .mobile-header-logo img {
            max-height: 30px;
        }

        .mobile-avatar {
            width: 30px;
            height: 30px;
        }

        .mobile-sidebar-toggle {
            padding: 8px;
        }

        .mobile-sidebar-toggle i {
            font-size: 20px;
        }
    }

    /* Desktop view - ensure mobile header is completely hidden */
    @media (min-width: 992px) {
        .dashboard-mobile-header {
            display: none !important;
            visibility: hidden !important;
        }

        .sidebar-overlay {
            display: none !important;
            visibility: hidden !important;
        }

        .dashboard-side-wrap {
            transform: translateX(0) !important;
        }

        body {
            padding-top: 0 !important;
        }
    }
</style>

<script>
jQuery(document).ready(function($) {
    // Mobile sidebar toggle functionality
    const sidebarToggle = $('#mobileSidebarToggle');
    const sidebar = $('.dashboard-side-wrap');
    const overlay = $('#sidebarOverlay');
    const body = $('body');

    // Toggle sidebar
    function toggleSidebar() {
        sidebar.toggleClass('mobile-active');
        overlay.toggleClass('active');

        if (sidebar.hasClass('mobile-active')) {
            overlay.fadeIn(300);
            body.css('overflow', 'hidden'); // Prevent body scroll when sidebar is open
        } else {
            overlay.fadeOut(300);
            body.css('overflow', '');
        }
    }

    // Close sidebar
    function closeSidebar() {
        sidebar.removeClass('mobile-active');
        overlay.removeClass('active').fadeOut(300);
        body.css('overflow', '');
    }

    // Toggle button click
    sidebarToggle.on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        toggleSidebar();
    });

    // Overlay click to close
    overlay.on('click', function() {
        closeSidebar();
    });

    // Close sidebar when clicking menu items (optional, improves UX)
    sidebar.find('.side-menu a').on('click', function() {
        if ($(window).width() <= 991) {
            closeSidebar();
        }
    });

    // Close sidebar on window resize if screen becomes larger
    $(window).on('resize', function() {
        if ($(window).width() > 991) {
            closeSidebar();
        }
    });

    // Prevent sidebar clicks from closing it
    sidebar.on('click', function(e) {
        e.stopPropagation();
    });
});
</script>
