/**
 * Copyright (c) Panth Infotech. All rights reserved.
 * Banner Slider - Luma RequireJS Component (vanilla JS, no Swiper)
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
        var focused = false;
        var reducedMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

        var settings = $.extend({
            autoplay: true,
            autoplaySpeed: 5000,
            transitionSpeed: 600,
            effect: 'fade',
            loop: true,
            pauseOnHover: true
        }, config);

        if (total <= 1) return;

        function updateDots(index) {
            $dots.removeClass('panth-banner-dot--active');
            $dots.filter('[data-index="' + index + '"]').addClass('panth-banner-dot--active');
        }

        function fadeTo(index, speed) {
            $slides.each(function (i) {
                var $s = $(this);
                if (i === index) {
                    $s.css({
                        opacity: 1,
                        transform: '',
                        'pointer-events': 'auto',
                        transition: 'opacity ' + speed + 'ms ease'
                    });
                } else {
                    $s.css({
                        opacity: 0,
                        transform: '',
                        'pointer-events': 'none',
                        transition: 'opacity ' + speed + 'ms ease'
                    });
                }
            });
        }

        function slideTo(index, direction, speed) {
            var $in = $slides.eq(index);
            var $out = $slides.eq(current);

            $slides.each(function (i) {
                if (i !== index && i !== current) {
                    $(this).css({
                        opacity: 0,
                        transform: '',
                        'pointer-events': 'none',
                        transition: 'none'
                    });
                }
            });

            $in.css({
                transition: 'none',
                transform: 'translateX(' + (direction * 100) + '%)',
                opacity: 1,
                'pointer-events': 'auto'
            });
            void $in[0].offsetWidth;
            $in.css({
                transition: 'transform ' + speed + 'ms ease',
                transform: 'translateX(0)'
            });
            $out.css({
                transition: 'transform ' + speed + 'ms ease',
                transform: 'translateX(' + (-direction * 100) + '%)',
                'pointer-events': 'none'
            });
        }

        function showSlide(index, direction) {
            var speed = settings.transitionSpeed || 600;

            if (index === current) {
                updateDots(index);
                return;
            }

            if (settings.effect === 'slide') {
                slideTo(index, direction || (index > current ? 1 : -1), speed);
            } else {
                fadeTo(index, speed);
            }

            updateDots(index);
            current = index;
        }

        function next() {
            var idx = settings.loop
                ? (current + 1) % total
                : Math.min(current + 1, total - 1);
            showSlide(idx, 1);
        }

        function prev() {
            var idx = settings.loop
                ? (current - 1 + total) % total
                : Math.max(current - 1, 0);
            showSlide(idx, -1);
        }

        function startAutoplay() {
            stopAutoplay();
            if (settings.autoplay && !focused && !reducedMotion) {
                timer = setInterval(next, settings.autoplaySpeed || 5000);
            }
        }

        function stopAutoplay() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
        }

        function isEditable(target) {
            var tag = target && target.tagName ? target.tagName.toLowerCase() : '';
            return tag === 'input' || tag === 'textarea' || tag === 'select' || !!(target && target.isContentEditable);
        }

        $el.on('click', '[data-action="prev"]', function () { prev(); startAutoplay(); });
        $el.on('click', '[data-action="next"]', function () { next(); startAutoplay(); });
        $el.on('click', '[data-action="goto"]', function () {
            showSlide(parseInt($(this).data('index'), 10));
            startAutoplay();
        });

        $el.on('focusin', function () {
            focused = true;
            stopAutoplay();
        });
        $el.on('focusout', function (e) {
            if (e.relatedTarget && $el[0].contains(e.relatedTarget)) {
                return;
            }
            focused = false;
            startAutoplay();
        });

        if (settings.pauseOnHover) {
            $el.on('mouseenter', stopAutoplay);
            $el.on('mouseleave', startAutoplay);
        }

        $el.find('.panth-banner-track').on('touchstart', function (e) {
            touchStartX = e.originalEvent.changedTouches[0].screenX;
        }).on('touchend', function (e) {
            var diff = touchStartX - e.originalEvent.changedTouches[0].screenX;
            if (Math.abs(diff) > 50) {
                diff > 0 ? next() : prev();
                startAutoplay();
            }
        });

        if (!$el.is('[tabindex]')) {
            $el.attr('tabindex', '0');
        }
        $el.on('keydown', function (e) {
            if (isEditable(e.target)) {
                return;
            }
            if (e.key === 'ArrowLeft') {
                e.preventDefault();
                prev();
                startAutoplay();
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                next();
                startAutoplay();
            }
        });

        startAutoplay();
    };
});
