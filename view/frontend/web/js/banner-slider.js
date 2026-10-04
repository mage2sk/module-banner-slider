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
        var $toggle = $el.find('[data-action="toggle"]');
        var $track = $el.find('.panth-banner-track');
        var total = $slides.length;
        var current = 0;
        var timer = null;
        var touchStartX = 0;
        var focused = false;
        var hovered = false;
        var reducedMotion = !!(window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches);

        var settings = $.extend({
            autoplay: true,
            autoplaySpeed: 5000,
            transitionSpeed: 600,
            effect: 'fade',
            loop: true,
            pauseOnHover: true,
            labelPause: 'Pause slideshow',
            labelPlay: 'Play slideshow'
        }, config);

        var userPaused = !!settings.autoplay && reducedMotion;

        if (total <= 1) {
            return;
        }

        function speed() {
            return reducedMotion ? 0 : (parseInt(settings.transitionSpeed, 10) || 0);
        }

        function updateDots(index) {
            $dots.removeClass('panth-banner-dot--active').attr('aria-current', 'false');
            $dots.filter('[data-index="' + index + '"]').addClass('panth-banner-dot--active').attr('aria-current', 'true');
        }

        function setActive(index) {
            $slides.each(function (i) {
                var $s = $(this);
                if (i === index) {
                    $s.addClass('panth-banner-slide--active').removeAttr('aria-hidden').prop('inert', false);
                } else {
                    $s.removeClass('panth-banner-slide--active').attr('aria-hidden', 'true').prop('inert', true);
                }
            });
        }

        function fadeTo(index, ms) {
            $slides.each(function (i) {
                $(this).css({
                    opacity: i === index ? 1 : 0,
                    transform: '',
                    'pointer-events': i === index ? 'auto' : 'none',
                    transition: 'opacity ' + ms + 'ms ease'
                });
            });
        }

        function slideTo(index, direction, ms) {
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
                transition: 'transform ' + ms + 'ms ease',
                transform: 'translateX(0)'
            });
            $out.css({
                transition: 'transform ' + ms + 'ms ease',
                transform: 'translateX(' + (-direction * 100) + '%)',
                'pointer-events': 'none'
            });
        }

        function showSlide(index, direction) {
            if (index === current) {
                updateDots(index);
                return;
            }

            if (settings.effect === 'slide' && !reducedMotion) {
                slideTo(index, direction || (index > current ? 1 : -1), speed());
            } else {
                fadeTo(index, speed());
            }

            setActive(index);
            updateDots(index);
            current = index;
        }

        function isRunning() {
            return timer !== null;
        }

        function stopAutoplay() {
            if (timer) {
                clearInterval(timer);
                timer = null;
            }
            $track.attr('aria-live', 'polite');
        }

        function startAutoplay() {
            stopAutoplay();
            if (settings.autoplay && !userPaused && !focused && !hovered) {
                timer = setInterval(function () {
                    next(false);
                }, settings.autoplaySpeed || 5000);
                $track.attr('aria-live', 'off');
            }
        }

        function restart() {
            if (isRunning()) {
                startAutoplay();
            }
        }

        function next(user) {
            if (settings.loop || current < total - 1) {
                showSlide((current + 1) % total, 1);
            } else if (!user) {
                stopAutoplay();
            }
            if (user) {
                restart();
            }
        }

        function prev(user) {
            if (settings.loop || current > 0) {
                showSlide((current - 1 + total) % total, -1);
            }
            if (user) {
                restart();
            }
        }

        function renderToggle() {
            $toggle.attr('aria-label', userPaused ? settings.labelPlay : settings.labelPause)
                .toggleClass('panth-banner-pause--paused', userPaused);
        }

        function isEditable(target) {
            var tag = target && target.tagName ? target.tagName.toLowerCase() : '';
            return tag === 'input' || tag === 'textarea' || tag === 'select' || !!(target && target.isContentEditable);
        }

        $el.on('click', '[data-action="prev"]', function () { prev(true); });
        $el.on('click', '[data-action="next"]', function () { next(true); });
        $el.on('click', '[data-action="goto"]', function () {
            showSlide(parseInt($(this).data('index'), 10));
            restart();
        });
        $el.on('click', '[data-action="toggle"]', function () {
            userPaused = !userPaused;
            renderToggle();
            if (userPaused) {
                stopAutoplay();
            } else {
                focused = false;
                startAutoplay();
            }
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
            $el.on('mouseenter', function () {
                hovered = true;
                stopAutoplay();
            });
            $el.on('mouseleave', function () {
                hovered = false;
                startAutoplay();
            });
        }

        $track.on('touchstart', function (e) {
            touchStartX = e.originalEvent.changedTouches[0].screenX;
        }).on('touchend', function (e) {
            var diff = touchStartX - e.originalEvent.changedTouches[0].screenX;
            if (Math.abs(diff) > 50) {
                if (diff > 0) {
                    next(true);
                } else {
                    prev(true);
                }
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
                prev(true);
            } else if (e.key === 'ArrowRight') {
                e.preventDefault();
                next(true);
            }
        });

        setActive(0);
        renderToggle();
        startAutoplay();
    };
});
