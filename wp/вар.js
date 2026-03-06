// document.addEventListener('DOMContentLoaded', function () {
//     const form = document.querySelector('.variations_form');
//     if (!form) return;

//     // Получаем все вариации из data-атрибута
//     let variations = [];
//     try {
//         variations = JSON.parse(form.dataset.product_variations);
//     } catch (e) {
//         console.error('Error parsing variations:', e);
//         return;
//     }

//     const variationSelects = document.querySelectorAll('.variation-select');

//     // Создаем кнопку очистки (изначально скрытую)
//     const resetButton = createResetButton();

//     // Инициализация: устанавливаем значения из скрытых селектов
//     initializeFromHiddenSelects();

//     // Проверяем, есть ли уже выбранные значения при загрузке
//     checkIfAnySelected();

//     // Обновляем доступность всех опций при загрузке
//     updateAllOptionsAvailability();

//     // Слушаем событие show_variation от WooCommerce
//     jQuery('.single_variation_wrap').on('show_variation', function (event, variation) {
//         // Обновляем цену в нашем диве
//         const priceDiv = document.querySelector('.product-item__price');
//         if (priceDiv && variation.price_html) {
//             priceDiv.innerHTML = variation.price_html;
//         }
//     });

//     // Также слушаем событие hide_variation (когда вариация не найдена)
//     jQuery('.single_variation_wrap').on('hide_variation', function () {
//         // Возвращаем диапазон цен
//         const priceDiv = document.querySelector('.product-item__price');
//         if (priceDiv) {
//             resetPriceDisplay(priceDiv);
//         }
//     });

//     // Функция сброса отображения цены
//     function resetPriceDisplay(priceDiv) {
//         // Показываем диапазон цен из всех вариаций
//         const prices = variations.map(v => parseFloat(v.display_price)).filter(p => !isNaN(p));
//         if (prices.length > 0) {
//             const minPrice = Math.min(...prices);
//             const maxPrice = Math.max(...prices);
//             const currencySymbol = document.querySelector('.woocommerce-Price-currencySymbol')?.textContent || '₽';

//             if (minPrice === maxPrice) {
//                 priceDiv.innerHTML = `<span class="woocommerce-Price-amount amount">${minPrice} <span class="woocommerce-Price-currencySymbol">${currencySymbol}</span></span>`;
//             } else {
//                 priceDiv.innerHTML = `
//                     <span class="woocommerce-Price-amount amount">${minPrice} <span class="woocommerce-Price-currencySymbol">${currencySymbol}</span></span> –
//                     <span class="woocommerce-Price-amount amount">${maxPrice} <span class="woocommerce-Price-currencySymbol">${currencySymbol}</span></span>
//                 `;
//             }
//         }
//     }

//     // Обработчик выбора опции в кастомном селекте
//     variationSelects.forEach(select => {
//         const selectBody = select.querySelector('.select-body');

//         selectBody.addEventListener('click', function (e) {
//             const option = e.target.closest('.variation-option');
//             if (!option) return;

//             // Проверяем, доступна ли опция
//             if (option.classList.contains('unavailable')) {
//                 console.log('Option is unavailable');
//                 return; // Не даем выбрать недоступную опцию
//             }

//             const attribute = select.dataset.attribute;
//             const value = option.dataset.value;
//             const label = select.querySelector('label');

//             // Обновляем отображаемое значение в кастомном селекте
//             label.textContent = option.textContent;
//             label.dataset.id = value;

//             // Обновляем скрытый селект WooCommerce
//             updateHiddenSelect(attribute, value);

//             // Обновляем доступность опций во всех селектах
//             updateAllOptionsAvailability();

//             // Показываем кнопку очистки
//             showResetButton();
//         });
//     });

//     // Слушаем события WooCommerce для скрытия кнопки при сбросе
//     form.addEventListener('click', function (e) {
//         if (e.target.classList.contains('reset_variations')) {
//             setTimeout(function () {
//                 resetAllSelections(); // Используем нашу функцию сброса
//             }, 100);
//         }
//     });

//     // Функция создания кнопки очистки
//     function createResetButton() {
//         if (document.querySelector('.custom-reset-variations')) {
//             return document.querySelector('.custom-reset-variations');
//         }

//         const resetButton = document.createElement('button');
//         resetButton.className = 'custom-reset-variations button';
//         resetButton.textContent = 'Очистить выбор';
//         resetButton.type = 'button';
//         resetButton.style.display = 'none';

//         resetButton.addEventListener('click', function () {
//             resetAllSelections();
//         });

//         const variationsGrid = document.querySelector('.grid.grid-2.variations');
//         if (variationsGrid) {
//             variationsGrid.parentNode.insertBefore(resetButton, variationsGrid.nextSibling);
//         } else {
//             form.appendChild(resetButton);
//         }

//         return resetButton;
//     }

//     // Функция показа кнопки
//     function showResetButton() {
//         if (resetButton) resetButton.style.display = 'flex';
//     }

//     // Функция скрытия кнопки
//     function hideResetButton() {
//         if (resetButton) resetButton.style.display = 'none';
//     }

//     // Функция проверки выбранных значений
//     function checkIfAnySelected() {
//         let anySelected = false;

//         variationSelects.forEach(select => {
//             const label = select.querySelector('label');
//             if (label && label.dataset.id && label.dataset.id !== '0') {
//                 anySelected = true;
//             }
//         });

//         if (anySelected) {
//             showResetButton();
//         } else {
//             hideResetButton();
//         }
//     }

//     // Функция получения всех выбранных атрибутов
//     function getSelectedAttributes() {
//         const selected = {};
//         variationSelects.forEach(select => {
//             const attribute = select.dataset.attribute;
//             const label = select.querySelector('label');
//             if (label && label.dataset.id && label.dataset.id !== '0') {
//                 selected[attribute] = label.dataset.id;
//             }
//         });
//         return selected;
//     }

//     // Функция проверки существования вариации с данными атрибутами
//     function variationExists(attributes) {
//         // Если атрибутов нет, считаем что все вариации существуют
//         if (Object.keys(attributes).length === 0) return true;

//         return variations.some(variation => {
//             // Проверяем, все ли атрибуты совпадают
//             return Object.keys(attributes).every(key => {
//                 const variationAttr = variation.attributes[`attribute_${key}`];
//                 // Если атрибут пустой в вариации, он подходит под любое значение
//                 return variationAttr === attributes[key] || variationAttr === '';
//             });
//         });
//     }

//     // Функция обновления доступности всех опций
//     function updateAllOptionsAvailability() {
//         const selectedAttributes = getSelectedAttributes();

//         variationSelects.forEach(select => {
//             const currentAttribute = select.dataset.attribute;
//             const options = select.querySelectorAll('.variation-option');
//             const currentLabel = select.querySelector('label');
//             const currentValue = currentLabel?.dataset.id;

//             options.forEach(option => {
//                 const optionValue = option.dataset.value;

//                 // Создаем комбинацию атрибутов с этой опцией
//                 const testAttributes = { ...selectedAttributes };

//                 // Если для этого атрибута уже есть выбранное значение, заменяем его тестируемым
//                 testAttributes[currentAttribute] = optionValue;

//                 // Проверяем, существует ли такая комбинация
//                 const exists = variationExists(testAttributes);

//                 if (exists) {
//                     option.classList.remove('unavailable');
//                 } else {
//                     option.classList.add('unavailable');
//                 }

//                 // Если это текущее выбранное значение, оно должно быть доступно
//                 if (optionValue === currentValue && currentValue !== '0') {
//                     option.classList.remove('unavailable');
//                 }
//             });
//         });
//     }

//     // Функция сброса всех выборов
//     function resetAllSelections() {
//         // Сбрасываем кастомные селекты на значение "Выберите"
//         variationSelects.forEach(select => {
//             const label = select.querySelector('label');
//             if (label) {
//                 label.textContent = 'Выберите';
//                 label.dataset.id = '0';
//             }
//         });

//         // Сбрасываем скрытые селекты WooCommerce
//         variationSelects.forEach(select => {
//             const attribute = select.dataset.attribute;
//             const hiddenSelect = document.querySelector(`.variations select[data-attribute_name="attribute_${attribute}"]`);

//             if (hiddenSelect) {
//                 hiddenSelect.value = '';

//                 // Триггерим события
//                 hiddenSelect.dispatchEvent(new Event('change', { bubbles: true }));
//             }
//         });

//         // Обновляем доступность опций (после сброса все опции должны стать доступными)
//         setTimeout(() => {
//             updateAllOptionsAvailability();
//         }, 50);

//         // Скрываем кнопку
//         hideResetButton();

//         // Принудительно сбрасываем цену
//         const priceDiv = document.querySelector('.product-item__price');
//         if (priceDiv) {
//             resetPriceDisplay(priceDiv);
//         }
//     }

//     // Функция обновления скрытого селекта
//     function updateHiddenSelect(attribute, value) {
//         const hiddenSelect = document.querySelector(`.variations select[data-attribute_name="attribute_${attribute}"]`);

//         if (hiddenSelect) {
//             hiddenSelect.value = value;

//             // Триггерим события
//             hiddenSelect.dispatchEvent(new Event('change', { bubbles: true }));
//         }
//     }

//     // Функция инициализации из скрытых селектов
//     function initializeFromHiddenSelects() {
//         variationSelects.forEach(select => {
//             const attribute = select.dataset.attribute;
//             const hiddenSelect = document.querySelector(`.variations select[data-attribute_name="attribute_${attribute}"]`);

//             if (hiddenSelect && hiddenSelect.value) {
//                 const selectedValue = hiddenSelect.value;
//                 const label = select.querySelector('label');
//                 const matchingOption = select.querySelector(`.variation-option[data-value="${selectedValue}"]`);

//                 if (matchingOption && label) {
//                     label.textContent = matchingOption.textContent;
//                     label.dataset.id = selectedValue;
//                 }
//             }
//         });
//     }
// });
