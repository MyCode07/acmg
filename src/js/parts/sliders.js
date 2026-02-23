import Swiper from 'swiper';
import { Navigation, Autoplay, FreeMode, Thumbs, Pagination } from 'swiper/modules';

const sliders = document.querySelectorAll('.swiper');
if (sliders.length) {
    sliders.forEach(slider => {
        const section = slider.closest('section');
        const prev = section.querySelector('.prev');
        const next = section.querySelector('.next');
        const pagination = section.querySelector('.pagination');

        if (slider.closest('.hero')) {
            new Swiper(slider, {
                modules: [
                    Navigation, Pagination
                ],
                navigation: {
                    prevEl: prev,
                    nextEl: next,
                },
                pagination: {
                    el: pagination,
                    clickable: true,
                },
                slidesPerView: 1,
                spaceBetween: 10,
            });
        }

        if (slider.closest('.about')) {
            new Swiper(slider, {
                modules: [
                    Pagination
                ],
                pagination: {
                    el: pagination,
                    clickable: true,
                },
                slidesPerView: 1,
                spaceBetween: 10,
            });
        }

        if (slider.closest('.single-product')) {
            const thumbs = new Swiper('.slider-thumbs', {
                modules: [
                    FreeMode
                ],
                freeMode: true,
                watchSlidesProgress: true,
                slidesPerView: 'auto',
                spaceBetween: 18,
                breakpoints: {
                    300: {
                        direction: 'horizontal',
                        spaceBetween: 5,
                    },
                    769: {
                        direction: 'vertical',
                        spaceBetween: 18,
                    },
                }
            });

            new Swiper('.slider-main', {
                modules: [
                    Thumbs, Navigation
                ],
                navigation: {
                    prevEl: prev,
                    nextEl: next,
                },
                slidesPerView: 1,
                spaceBetween: 10,
                thumbs: {
                    swiper: thumbs,
                },
            });
        }

        if (slider.closest('.related')) {
            new Swiper(slider, {
                modules: [
                    Pagination, Navigation
                ],
                pagination: {
                    el: '.related .progressbar',
                    type: 'progressbar',
                },
                navigation: {
                    prevEl: prev,
                    nextEl: next,
                },
                spaceBetween: 24,
                breakpoints: {
                    300: {
                        slidesPerView: 1,
                    },
                    541: {
                        slidesPerView: 2,
                    },
                    469: {
                        slidesPerView: 3,
                    },
                    1025: {
                        slidesPerView: 4,
                    },
                }
            });
        }

        if (slider.closest('.page-compare')) {
            new Swiper(slider, {
                slidesPerView: 'auto',
                modules: [
                    FreeMode, Pagination
                ],

                pagination: {
                    el: '.page-compare .progressbar',
                    type: 'progressbar',
                },
                breakpoints: {
                    300: {
                        spaceBetween: 8,
                    },
                    1025: {
                        spaceBetween: 24,
                    },
                },
                freeMode: {
                    enabled: true,
                    momentum: true,
                    momentumBounce: false,
                    sticky: false
                },
                speed: 0,
                grabCursor: true,
                resistance: false, // Disable resistance at edges
                resistanceRatio: 0,

                // Disable all snapping and effects
                effect: 'slide',
                fadeEffect: {
                    crossFade: false
                },
                cubeEffect: {
                    shadow: false,
                    slideShadows: false
                },
                flipEffect: {
                    slideShadows: false,
                    limitRotation: false
                },
                coverflowEffect: {
                    rotate: 0,
                    stretch: 0,
                    depth: 0,
                    modifier: 0,
                    slideShadows: false
                }
            });
        }
    })
}
