const inputItems = [...document.querySelectorAll('input ')].concat([...document.querySelectorAll('textarea ')])
if (inputItems.length) {
    inputItems.forEach(input => {
        if (input.closest('.form__item')) {
            const form__item = input.closest('.form__item')
            input.addEventListener('input', () => {
                if (input.value != '') form__item.classList.add('_active')
                else form__item.classList.remove('_active')
            })
        }
    })
}