document.addEventListener('click', function (e) {
    let targetEl = e.target;

    if (targetEl.classList.contains('show-more')) {
        const productData = targetEl.closest('.product-item').querySelector('.chars')
        productData.classList.toggle('_open')

        if (productData.classList.contains('_open')) {
            targetEl.textContent = targetEl.dataset.textHide
        } else {
            targetEl.textContent = targetEl.dataset.textShow
        }
    }
})