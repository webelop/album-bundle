import 'bootstrap/dist/css/bootstrap.min.css'
require('@fortawesome/fontawesome-free/css/all.min.css')
require('../css/main.css')

import $ from 'jquery'
import ZingTouch from 'zingtouch'

/**
 * todo: use image.loaded parameter to activate / deactivate tags
 * todo: change hash in slideshow and allow to restart with photo opened
 */
(function (App, $, ZingTouch) {
    var $thumbnails = $('a.img-thumbnail'),
        current = 1,
        total = $thumbnails.length,
        autoPlaySpeed = $(document.body).data('autoPlaySpeed') || 5000 ;

    App.Main = {
        init: function () {
            this.slideshow();
        },
        slideshow: function () {
            var $slideshow = $('div.slideshow'),
                $mainImage = $slideshow.find('div.picture img'),
                $mainVideo = $slideshow.find('div.picture video'),
                $prevLoader = $slideshow.find('img.prevloader'),
                $nextLoader = $slideshow.find('img.nextloader'),
                $icon = $('a.fullscreen i'),
                $tags = $('a.tag'),
                shown = false,
                autoplay = false,
                self = this;

            function showImage(thumbnail) {
                $slideshow
                    .removeClass('hidden').fadeIn();
                shown = true;
                var href = $(thumbnail).attr('href');
                if (href.indexOf('/stream/') > 0) {
                    // VIDEO
                    $mainVideo.removeClass('hidden').attr('src', href);
                    $mainImage.addClass('hidden');

                    // Stop autoplay when watching videos
                    if (autoplay && autoplay !== 'pending') {
                        window.clearInterval(autoplay);
                        autoplay = 'pending'
                    }
                    if (document.fullscreenElement && document.fullscreenElement.tagName === 'DIV') {
                        $mainVideo[0].requestFullscreen()
                    }
                } else {
                    // PHOTO
                    $mainImage.removeClass('hidden').attr('src', href).show();
                    pauseVideo();
                    $mainVideo.addClass('hidden').attr('src', null);

                    if (autoplay === 'pending') {
                        startAutoplay(false)
                    }
                }

                $('a.download').attr('href', $('a.download').data('href').replace('abcdef', $(thumbnail).data('picture')));

                current = $(thumbnail).data('cursor');
                document.location.hash = `#${current}-of-${total}`

                // Scroll to current thumbnail in background and highlight current image
                thumbnail.scrollIntoView()
                $thumbnails.not(thumbnail).filter('.active').removeClass('active')
                $(thumbnail).addClass('active')

                //Cache previous and next
                href = self.getThumbnail(-1).href;
                if (href && href.indexOf('/stream/') === -1) {
                    $prevLoader.attr('src', href);
                }

                href = self.getThumbnail(+1).href;
                if (href && href.indexOf('/stream/') === -1) {
                    $nextLoader.attr('src', href);
                }
                $tags.each(function(k, el) {
                    var $tag = $(el),
                        tagRegex = new RegExp('\\b'+$tag.data('hash')+',?');

                    if(tagRegex.test($(thumbnail).data('tags'))) {
                        $tag.addClass('btn-success').removeClass('btn-light');
                    } else {
                        $tag.addClass('btn-light').removeClass('btn-success');
                    }
                });
            }

            function showNextImage() {
                showImage(self.getThumbnail(+1));
            }

            function showPreviousImage() {
                showImage(self.getThumbnail(-1));
            }

            function showImageAtCursor(cursor) {
                showImage($thumbnails[(total + cursor - 1) % total])
            }

            function toggleAutoplay() {
                if (autoplay) {
                    stopAutoplay()
                } else {
                    startAutoplay(true)
                }
            }

            function startAutoplay(showNext = false) {
                $('a.play i').removeClass('fa-pause').addClass('fa-play')

                if (autoplay && autoplay !== 'pending') {
                    window.clearInterval(autoplay)
                } else {

                }

                showNext && showNextImage()
                autoplay = window.setInterval(showNextImage, autoPlaySpeed);
            }

            function stopAutoplay() {
                $('a.play i').removeClass('fa-play').addClass('fa-pause')

                window.clearInterval(autoplay);
                autoplay = false;
            }

            function postponeAutoplay() {
                if (autoplay && autoplay !== 'pending') {
                    window.clearInterval(autoplay)
                    autoplay = window.setInterval(showNextImage, autoPlaySpeed);
                }
            }

            function exitFullscreen() {
                if (document.fullscreenElement) {
                    document.exitFullscreen().then(_ => {
                        $icon.addClass('fa-expand-arrows-alt').removeClass('fa-compress-arrows-alt')
                    })
                } else {
                    return Promise.resolve()
                }
            }

            function requestFullscreen() {
                return $slideshow[0].requestFullscreen()
                    .then ( _ => $icon.addClass('fa-compress-arrows-alt').removeClass('fa-expand-arrows-alt'))
            }

            function toggleFullscreen() {
                if (document.fullscreenElement) {
                    return exitFullscreen();
                } else {
                    return requestFullscreen();
                }
            }

            function pauseVideo() {
                if ($mainVideo.attr('src') && !$mainVideo.attr('ended')) {
                    $mainVideo[0].pause();
                }
            }

            $thumbnails.each(function(k, el){
                $(el).data('cursor', k+1);
            });

            $thumbnails.click(function (e) {
                e.preventDefault();
                showImage(e.currentTarget);
            });

            function closeSlideshowAndExitFullscreen() {
                autoplay && stopAutoplay();
                pauseVideo();
                $mainVideo.addClass('hidden').attr('src', null);
                $slideshow.fadeOut();
                shown = false;
                document.location.hash = ''

                return exitFullscreen()
                    .then(_ => {
                        self.getThumbnail().scrollIntoView()
                    })
            }

            $slideshow
                .on('click', 'a.prev', function(e) {
                    e.preventDefault();

                    showPreviousImage();
                    autoplay && autoplay !== 'pending' && postponeAutoplay();

                    return false;
                })
                .on('click', 'a.next', function(e) {
                    e.preventDefault();

                    showNextImage();
                    autoplay && autoplay !== 'pending' && postponeAutoplay();

                    return false;
                })
                .on('click', 'a.ss-close', function(e){
                    e.preventDefault();

                    closeSlideshowAndExitFullscreen();
                })
                .on('click', 'a.play', function(e) {
                    e.preventDefault();

                    toggleAutoplay()
                })
                .on('click', 'a.fullscreen', function(e) {
                    e.preventDefault();

                    toggleFullscreen()
                })
            ;

            $mainVideo
                .on('ended', function() {
                    // restart autoplay when the video finishes
                    if (autoplay === 'pending') {
                        window.setTimeout(showNextImage, 1000)
                    }
                })

            document.addEventListener('fullscreenchange', (event) => {
                if (document.fullscreenElement) {
                    $icon.addClass('fa-compress-arrows-alt').removeClass('fa-expand-arrows-alt')
                } else {
                    $icon.addClass('fa-expand-arrows-alt').removeClass('fa-compress-arrows-alt')
                }
            });

            $(document).on('keyup', function(e){
                if (e.target.tagName === 'INPUT') {
                    return;
                }

                if (shown && e.keyCode == 39) {
                    showNextImage()
                } else if (shown && e.keyCode == 37) {
                    //Space: Play - Pause
                    showPreviousImage();
                } else if (shown && e.keyCode == 32) {
                    //Space: Play - Pause
                    toggleAutoplay();
                } else if (e.key === 'f') {
                    // F in view mode opens the active photo
                    if (shown) {
                        if (document.fullscreenElement) {
                            closeSlideshowAndExitFullscreen();
                        } else {
                            requestFullscreen()
                        }
                    } else {
                        showImageAtCursor($thumbnails.filter('.active').data('cursor') || 1)
                        requestFullscreen()
                    }
                } else if (e.keyCode === 27) {
                    if (shown) {
                        closeSlideshowAndExitFullscreen()
                        e.preventDefault()
                    }
                }
            });

            // Pre-select a photo
            const hashPosition = document.location.hash.match(/(\d+)-of-\d+/);
            if (!shown && hashPosition && $thumbnails.get(hashPosition[1])) {
                showImageAtCursor(hashPosition[1])
            }

            const zing = ZingTouch.Region($mainImage.get(0).parentElement)
            zing.bind($mainImage.get(0), "swipe", function(event) {
                if (event.currentDirection > 120 || event.currentDirection < 240) {
                    showPreviousImage()
                } else if (event.currentDirection > 300 || event.currentDirection < 60) {
                    showNextImage()
                }
            })
        },
        getThumbnail: function(step = 0) {
            return $thumbnails.get((current + total + step -1) % total);
        }
    }

    App.Main.init();
})(window.App = window.App || {}, $, ZingTouch);