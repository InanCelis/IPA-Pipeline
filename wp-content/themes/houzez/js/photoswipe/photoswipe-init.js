jQuery(document).ready(function ($) {
    var initPropertyPhotoSwipe = function (gallerySelector) {
        var lightbox = new PhotoSwipeLightbox({
            pswpModule: PhotoSwipe,
            gallery: gallerySelector,
            children:
                'a[data-gallery-item]:not(.slick-cloned a[data-gallery-item])',
            imageClickAction: 'next',
            tapAction: 'next',
            wheelToZoom: true,
            preloaderDelay: 0,
            preload: [1, 4],
            mainClass: 'pswp-with-perma-preloader',
            showFullscreenButton: true,
        });
        lightbox.init();

        // Handle clicks on cloned items
        $(gallerySelector + ' .slick-cloned a[data-gallery-item]').on(
            'click',
            function (e) {
                e.preventDefault();
                var index = $(this).closest('.slick-slide').data('slick-index');
                // Adjust index for cloned items to point to original
                if (index < 0) {
                    index =
                        $(
                            gallerySelector +
                                ' a[data-gallery-item]:not(.slick-cloned a[data-gallery-item])'
                        ).length + index;
                } else {
                    index =
                        index %
                        $(
                            gallerySelector +
                                ' a[data-gallery-item]:not(.slick-cloned a[data-gallery-item])'
                        ).length;
                }
                lightbox.loadAndOpen(index);
            }
        );
    };

    // Initialize PhotoSwipe for all galleries with houzez-photoswipe class
    $('.houzez-photoswipe').each(function (index) {
        var galleryId = $(this).attr('id');
        if (!galleryId) {
            galleryId = 'houzez-photoswipe-gallery-' + index;
            $(this).attr('id', galleryId);
        }
        initPropertyPhotoSwipe('#' + galleryId);
    });
});
