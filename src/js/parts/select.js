const selects = document.querySelectorAll('select');
if (selects) {
    selects.forEach(item => {
        let isOpen = false;

        item.addEventListener('change', function () {
            if (this.value === "") {
                this.classList.remove('_active');
                item.closest('div').classList.remove('_open');

            } else {
                this.classList.add('_active');
                item.closest('div').classList.add('_open');
            }
        });


        item.addEventListener('click', function () {
            item.closest('div').classList.toggle('_open');
        });
    })
}


import { lockPadding, unLockPadding } from '../utils/lockPadding.js';

const filter = document.querySelector('.filter');

document.addEventListener('click', function (e) {
    let targetEl = e.target;

    const ativeSelect = document.querySelector('.select-input._active');

    if (targetEl.classList.contains('select-input')) {

        if (ativeSelect && ativeSelect !== targetEl) {
            ativeSelect.classList.remove('_active')
        }
        targetEl.classList.toggle('_active')
    }


    if ((targetEl.closest('.select-body') || targetEl.closest('.select-input')) && targetEl.hasAttribute('data-id')) {
        const select = targetEl.closest('.select-input');
        const label = select.querySelector('label')

        label.textContent = targetEl.textContent
        label.dataset.id = targetEl.dataset.id
        select.classList.remove('_active')
    }

    if (!targetEl.classList.contains('select-input') && !targetEl.closest('.select-input') && document.querySelector('.select-input._active')) {
        document.querySelector('.select-input._active').classList.remove('_active')
    }

    if (targetEl.hasAttribute('data-open-filter')) {
        filter.classList.add('_open')
        lockPadding()
    }

    if (targetEl.hasAttribute('data-close-filter')) {
        filter.classList.remove('_open')
        unLockPadding()
    }

    if (targetEl.classList.contains('filter')) {
        filter.classList.remove('_open')
        unLockPadding()
    }
})