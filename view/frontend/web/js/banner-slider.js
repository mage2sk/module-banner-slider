/**
 * Copyright © Panth Infotech. All rights reserved.
 * Banner Slider — Luma RequireJS Component (vanilla JS, no Swiper)
 */
define(['jquery'], function ($) {
    'use strict';

    return function (config, element) {
        var $el = $(element);
        var $slides = $el.find('[data-slide]');
        var $dots = $el.find('[data-action="goto"]');
        var total = $slides.length;
        var current = 0;
        var timer = null;
        var touchStartX = 0;

        var settings = $.extend({
            autoplay: true,
            autoplaySpeed: 5000,
            transitionSpeed: 600,
            loop: true,
            pauseOnHover: true
        }, config);

        if (total <= 1) return;

        function showSlide(index) {
            var speed = settings.transitionSpeed || 600;
            $slides.each(function (i) {
                var $s = $(this);
                if (i === index) {
                    $s.css({
                        opacity: 1,
                        'pointer-events': 'auto',
                        transition: 'opacity ' + speed + 'ms ease'
                    });
                } else {
                    $s.css({
                        opacity: 0,
                        'pointer-events': 'none',
                        transition: 'opacity ' + speed + 'ms ease'
                    });
                }
            });

            $dots.removeClass('panth-banner-dot--active');
            $dots.filter('[data-index="' + index + '"]').addClass('panth-banner-dot--active');

            current = index;
        }

        function next() {
            var idx = settings.loop
                ? (current + 1) % total
                : Math.min(current + 1, total - 1);
            showSlide(idx);
        }

        function prev() {
            var idx = settings.loop
                ? (current - 1 + total) % total
                : Math.max(current - 1, 0);
            showSlide(idx);
        }

        function startAutoplay() {
            stopAutoplay();
            if (settings.autoplay) {
                timer = setInterval(next, settings.autoplaySpeed || 5000);
            }
        }

        function stopAutoplay() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        }

        // Navigation buttons
        $el.on('click', '[data-action="prev"]', function () { prev(); startAutoplay(); });
        $el.on('click', '[data-action="next"]', function () { next(); startAutoplay(); });
        $el.on('click', '[data-action="goto"]', function () {
            showSlide(parseInt($(this).data('index'), 10));
            startAutoplay();
        });

        // Pause on hover
        if (settings.pauseOnHover) {
            $el.on('mouseenter', stopAutoplay);
            $el.on('mouseleave', startAutoplay);
        }

        // Touch/swipe
        $el.find('.panth-banner-track').on('touchstart', function (e) {
            touchStartX = e.originalEvent.changedTouches[0].screenX;
        }).on('touchend', function (e) {
            var diff = touchStartX - e.originalEvent.changedTouches[0].screenX;
            if (Math.abs(diff) > 50) {
                diff > 0 ? next() : prev();
                startAutoplay();
            }
        });

        // Keyboard
        $(document).on('keydown', function (e) {
            if (e.key === 'ArrowLeft') prev();
            if (e.key === 'ArrowRight') next();
        });

        // Start
        startAutoplay();
    };
});
