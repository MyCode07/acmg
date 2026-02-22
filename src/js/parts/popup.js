import { isMobile } from "../utils/isMobile.js";
import { lockPadding, unLockPadding } from "../utils/lockPadding.js";


document.addEventListener('click', function (e) {
    let targetEl = e.target;

    if (targetEl.hasAttribute('data-open-popup')) {
        e.preventDefault();
        const id = targetEl.getAttribute('data-open-popup');
        const popup = document.querySelector(`.popup#${id}`);

        if (popup) {
            popup.classList.add('_open')
            lockPadding();
        }
    }

    if (targetEl.classList.contains('popup')) {
        targetEl.classList.remove('_open')
        closePopup(targetEl)
    }

    if (targetEl.classList.contains('popup-close') || targetEl.hasAttribute('data-close-popup')) {
        const popup = targetEl.closest('.popup');
        closePopup(popup)
    }
})


function closePopup(popup) {
    popup.classList.remove('_open')
    unLockPadding();
}