import { lockPadding, unLockPadding } from '../utils/lockPadding.js';

const burger = document.querySelector('.header__burger');
const catalogMenu = document.querySelector('.catalog-menu');
const menu = document.querySelector('.menu');
const filter = document.querySelector('.filter');

document.addEventListener('click', function (e) {
    let targetEl = e.target;

    if (targetEl.classList.contains('header__burger')) {
        burger.classList.toggle('_active');
        menu.classList.toggle('_open');

        if (menu.classList.contains('_open')) {
            lockPadding();

        } else {
            unLockPadding();
        }
    }

    if (targetEl.classList.contains('header__catalog')) {
        catalogMenu.classList.toggle('_open');
        targetEl.classList.toggle('_active');

        if (catalogMenu.classList.contains('_open')) {
            lockPadding();

        } else {
            unLockPadding();
        }
    }

    if (targetEl.classList.contains('menu__close')) {
        burger.classList.remove('_active');
        menu.classList.remove('_open');
        unLockPadding();
    }
    if (targetEl.tagName == 'A' && targetEl.closest('.menu') && document.querySelector('.menu._open')) {
        burger.classList.remove('_active');
        menu.classList.remove('_open');
        unLockPadding();
    }
    if (targetEl.classList.contains('open-filter')) {
        targetEl.classList.toggle('_active');
        filter.classList.toggle('_open');
        let top = document.querySelector('.products-top').getBoundingClientRect().bottom
        filter.style.top = top + 'px';
        filter.style.height = `calc(100vh - ${top + 20}px)`;

        if (filter.classList.contains('_open')) {
            lockPadding();

        } else {
            unLockPadding();
        }
    }
    if (targetEl.classList.contains('filter-close')) {
        filter.classList.remove('_open');
        unLockPadding();
    }
})

function clickOutsideElement(elemSelector = '', excludedSelectors = [], activeClass = '_open') {
    const elem = document.querySelector(elemSelector);
    if (!elem) return;

    document.addEventListener('click', (e) => {

        const clickedExcluded = excludedSelectors.some(selector =>
            e.target.closest(selector)
        );

        if (!clickedExcluded && !e.target.closest(elemSelector)) {
            elem.classList.remove(activeClass);
            unLockPadding();
        }
    });
}

clickOutsideElement('.filter', ['.open-filter']);

// menu arrow buttom
const arrow = `<div class="arrow"><svg width="12" height="7" viewBox="0 0 12 7"><path d="M6.46824 6.80994L11.8083 1.50758C11.9319 1.38495 12 1.22124 12 1.04668C12 0.872118 11.9319 0.708408 11.8083 0.585771L11.4151 0.195289C11.1589 -0.0587999 10.7425 -0.0587999 10.4867 0.195289L6.00249 4.64781L1.51326 0.190349C1.38965 0.0677115 1.22487 -4.70996e-07 1.04916 -4.78676e-07C0.873261 -4.86365e-07 0.708482 0.0677114 0.584775 0.190348L0.191706 0.58083C0.0680971 0.703564 -3.79055e-08 0.867177 -4.55358e-08 1.04174C-5.3166e-08 1.2163 0.0680971 1.38001 0.191706 1.50264L5.53664 6.80994C5.66064 6.93287 5.8262 7.00039 6.00219 7C6.17888 7.00039 6.34434 6.93287 6.46824 6.80994Z"/></svg></div>`;

// add menu summenu opener button
const submenuList = [...document.querySelectorAll('.filter ul li'), ...document.querySelectorAll('.filter-item')];
if (submenuList.length) {
    submenuList.forEach(li => {
        let submenu = li.querySelector('ul');
        let link = li.querySelector('a');

        if (li.classList.contains('filter-item')) {
            submenu = li.querySelector('label')
            link = li.querySelector('p span');
        }

        if (submenu && link) {
            link.insertAdjacentHTML('afterend', arrow);
            let btn = li.querySelector('div');

            if (btn) {
                if ((btn.closest('.menu') || btn.closest('.header')) && isMobile.any()) {
                    btn.addEventListener('click', function () {
                        toggleMenu(li)
                    })
                }
                else {
                    btn.addEventListener('click', function () {
                        toggleMenu(li)
                    })
                }
            }

            const btnArrow = li.querySelector('.menu-arrow');
            if (btnArrow && isMobile.any()) {
                btnArrow.addEventListener('click', function () {
                    toggleMenu(li)
                })
            }
        }
    })

    function toggleMenu(item) {
        if (!item.hasAttribute('data-open')) {
            item.setAttribute('data-open', 'open')
        }
        else {
            item.removeAttribute('data-open')
        }
    }
}

// replace catalg menu

const replaceCatalogmenu = () => {
    let lockPosition = true;
    const replaceElem = document.querySelector('.catalog-menu');
    if (!replaceElem) return

    const width = 1024;
    const newPosition = document.querySelector('.menu .header__catalog');
    const oldPosition = document.querySelector('.menu');

    function replace() {
        if (window.innerWidth <= width) {
            if (lockPosition == true)
                newPosition.insertAdjacentElement('afterend', replaceElem)
            lockPosition = false
        }
        else {
            if (lockPosition == false)
                oldPosition.insertAdjacentElement('beforebegin', replaceElem)
            lockPosition = true
        }

    }

    replace()
    window.addEventListener('resize', replace)
}

replaceCatalogmenu();