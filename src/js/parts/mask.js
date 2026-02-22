import { maskInputs } from "../static/inputmask.js";

const phones = document.querySelectorAll('.phone-input-wrapper');
phones.forEach(phone => {

    const countrySelect = phone.querySelector('.country-select');
    const selectedCountry = countrySelect.querySelector('.selected-country');
    const countryDropdown = countrySelect.querySelector('.country-dropdown');
    const phoneInput = phone.querySelector('input');
    const submitBtn = phone.closest('form').querySelector('button');

    let currentCountry = {
        code: '+7',
        flag: '🇷🇺',
        mask: '+7 (999) 999-99-99',
        name: 'Россия'
    };

    maskInputs(currentCountry.mask, '.phone-input')

    // Показ/скрытие выпадающего списка стран
    selectedCountry.addEventListener('click', function () {
        countryDropdown.style.display = countryDropdown.style.display === 'block' ? 'none' : 'block';
    });

    // Выбор страны из списка
    countryDropdown.querySelectorAll('.country-option').forEach(option => {
        option.addEventListener('click', function () {
            currentCountry = {
                code: this.getAttribute('data-code'),
                flag: this.getAttribute('data-flag'),
                mask: this.getAttribute('data-mask'),
                name: this.querySelector('.country-name').textContent
            };

            selectedCountry.innerHTML = `
                        <span class="flag">${currentCountry.flag}</span>
                        <span class="code">${currentCountry.code}</span>
                    `;

            phoneInput.placeholder = currentCountry.mask;
            countryDropdown.style.display = 'none';

            // Очищаем поле ввода при смене страны
            phoneInput.value = '';

            maskInputs(this.getAttribute('data-mask'), '.phone-input')
        });
    });

    // Закрытие выпадающего списка при клике вне его
    document.addEventListener('click', function (e) {
        if (!countrySelect.contains(e.target)) {
            countryDropdown.style.display = 'none';
        }
    });



    // Обработка отправки формы
    submitBtn.addEventListener('click', function () {
        if (validatePhone(phoneInput, currentCountry)) {
            const numbers = phoneInput.value.replace(/\D/g, '');
        }
    });

    // Инициализация
    phoneInput.placeholder = currentCountry.mask;
})


// Валидация номера телефона
function validatePhone(phoneInput, currentCountry) {
    const numbers = phoneInput.value.replace(/\D/g, '');
    const expectedLength = currentCountry.mask.match(/9/g).length;

    console.log('expectedLength', expectedLength);

    if (numbers.length === 0) {
        console.log('Введите номер телефона');
        return false;
    } else if (numbers.length < expectedLength) {
        console.log(`Номер слишком короткий. Ожидается ${expectedLength} цифр`);
        return false;
    } else if (numbers.length > expectedLength) {
        console.log(`Номер слишком длинный. Ожидается ${expectedLength} цифр`);
        return false;
    } else {
        console.log('✓ Номер телефона корректен');
        return true;
    }

    // ,,,,
    if (/[_]/.test(input.value) || input.value.length < 18) {
        formAddError(input);
        error++;
    }
}