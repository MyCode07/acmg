document.addEventListener('DOMContentLoaded', function () {
    const form = document.querySelector('.variations_form');
    if (!form) return;

    // Получаем все вариации из data-атрибута
    let variations = [];
    try {
        variations = JSON.parse(form.dataset.product_variations);
        console.log('All variations loaded:', variations);
    } catch (e) {
        console.error('Error parsing variations:', e);
        return;
    }

    const variationSelects = document.querySelectorAll('.variation-select');

    // Создаем кнопку очистки (изначально скрытую)
    const resetButton = createResetButton();

    // Инициализация: устанавливаем значения из скрытых селектов
    initializeFromHiddenSelects();

    // Проверяем, есть ли уже выбранные значения при загрузке
    checkIfAnySelected();

    // Обработчик выбора опции
    variationSelects.forEach(select => {
        const selectBody = select.querySelector('.select-body');

        selectBody.addEventListener('click', function (e) {
            const option = e.target.closest('.variation-option');
            if (!option) return;

            e.stopPropagation();

            const attribute = select.dataset.attribute;
            const value = option.dataset.value;
            const label = select.querySelector('label');

            console.log('Selected:', attribute, '=', value);

            // Обновляем отображаемое значение
            label.textContent = option.textContent;
            label.dataset.id = value;

            // Обновляем скрытый селект WooCommerce
            updateHiddenSelect(attribute, value);

            // Обновляем доступность опций на основе всех выбранных значений
            updateAllOptionsAvailability();

            // Находим и отображаем вариацию
            findAndDisplayVariation();

            // Показываем кнопку очистки
            showResetButton();
        });
    });

    // Функция создания кнопки очистки (скрытой)
    function createResetButton() {
        // Проверяем, есть ли уже кнопка
        if (document.querySelector('.custom-reset-variations')) {
            return document.querySelector('.custom-reset-variations');
        }

        // Создаем кнопку
        const resetButton = document.createElement('button');
        resetButton.className = 'custom-reset-variations button';
        resetButton.textContent = 'Очистить выбор';
        resetButton.type = 'button';

        // Добавляем стили для скрытой кнопки
        resetButton.style.marginTop = '15px';
        resetButton.style.padding = '8px 15px';
        resetButton.style.cursor = 'pointer';
        resetButton.style.display = 'none'; // Изначально скрыта

        // Добавляем обработчик
        resetButton.addEventListener('click', function () {
            resetAllSelections();
        });

        // Ищем место для вставки (после вариаций)
        const variationsGrid = document.querySelector('.grid.grid-2.variations');
        if (variationsGrid) {
            variationsGrid.parentNode.insertBefore(resetButton, variationsGrid.nextSibling);
        } else {
            // Если не нашли, добавляем в форму
            form.appendChild(resetButton);
        }

        return resetButton;
    }

    // Функция показа кнопки очистки
    function showResetButton() {
        if (resetButton) {
            resetButton.style.display = 'inline-block';
        }
    }

    // Функция скрытия кнопки очистки
    function hideResetButton() {
        if (resetButton) {
            resetButton.style.display = 'none';
        }
    }

    // Функция проверки, есть ли выбранные значения
    function checkIfAnySelected() {
        let anySelected = false;

        variationSelects.forEach(select => {
            const label = select.querySelector('label');
            const firstOption = select.querySelector('.variation-option');

            // Проверяем, отличается ли текущее значение от первого
            if (firstOption && label && label.dataset.id !== firstOption.dataset.value) {
                anySelected = true;
            }
        });

        if (anySelected) {
            showResetButton();
        } else {
            hideResetButton();
        }
    }

    // Функция сброса всех выборов
    function resetAllSelections() {
        console.log('Resetting all selections');

        // 1. Сбрасываем все кастомные селекты на первые значения
        variationSelects.forEach(select => {
            const label = select.querySelector('label');
            const firstOption = select.querySelector('.variation-option');

            if (label && firstOption) {
                // Устанавливаем первое значение как стандартное
                label.textContent = firstOption.textContent;
                label.dataset.id = firstOption.dataset.value;
            }
        });

        // 2. Убираем все классы unavailable
        document.querySelectorAll('.variation-option').forEach(option => {
            option.classList.remove('unavailable');
        });

        // 3. Сбрасываем скрытые селекты WooCommerce
        variationSelects.forEach(select => {
            const attribute = select.dataset.attribute;
            const hiddenSelect = document.querySelector(`.variations select[data-attribute_name="${attribute}"]`);
            const label = select.querySelector('label');

            if (hiddenSelect && label) {
                // Сбрасываем на первую опцию
                const firstOption = hiddenSelect.querySelector('option:not([value=""])');
                if (firstOption) {
                    hiddenSelect.value = firstOption.value;
                    label.dataset.id = firstOption.value;
                    label.textContent = firstOption.textContent;
                } else {
                    hiddenSelect.value = '';
                    label.dataset.id = '';
                }

                hiddenSelect.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });

        // 4. Триггерим стандартную кнопку очистки WooCommerce
        const standardResetButton = document.querySelector('.reset_variations');
        if (standardResetButton) {
            console.log('Triggering standard reset button');
            standardResetButton.click();
        }

        // 5. Обновляем доступность опций
        updateAllOptionsAvailability();

        // 6. Сбрасываем отображение
        resetDisplay();

        // 7. Скрываем кнопку очистки
        hideResetButton();
    }

    // Функция обновления скрытого селекта
    function updateHiddenSelect(attribute, value) {
        const hiddenSelect = document.querySelector(`.variations select[data-attribute_name="${attribute}"]`);
        if (hiddenSelect) {
            hiddenSelect.value = value;
            hiddenSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    // Функция получения всех выбранных атрибутов
    function getSelectedAttributes() {
        const selected = {};
        variationSelects.forEach(select => {
            const attribute = select.dataset.attribute;
            const label = select.querySelector('label');
            const firstOption = select.querySelector('.variation-option');

            // Считаем выбранным, если значение отличается от первого
            if (firstOption && label && label.dataset.id && label.dataset.id !== firstOption.dataset.value) {
                selected[attribute] = label.dataset.id;
            }
        });
        return selected;
    }

    // Функция обновления доступности всех опций
    function updateAllOptionsAvailability() {
        const selectedAttributes = getSelectedAttributes();
        console.log('Selected attributes for availability:', selectedAttributes);

        variationSelects.forEach(select => {
            const attribute = select.dataset.attribute;
            const options = select.querySelectorAll('.variation-option');

            options.forEach(option => {
                const optionValue = option.dataset.value;

                // Создаем комбинацию с этой опцией
                const testAttributes = { ...selectedAttributes };
                testAttributes[attribute] = optionValue;

                // Проверяем, существует ли такая вариация
                const exists = variationExists(testAttributes);

                if (exists) {
                    option.classList.remove('unavailable');
                } else {
                    option.classList.add('unavailable');
                }
            });
        });
    }

    // Функция проверки существования вариации
    function variationExists(attributes) {
        return variations.some(variation => {
            // Проверяем, все ли атрибуты совпадают
            return Object.keys(attributes).every(key => {
                const variationAttr = variation.attributes[`attribute_${key}`];
                return variationAttr === attributes[key] || variationAttr === '';
            });
        });
    }

    // Функция поиска и отображения вариации
    function findAndDisplayVariation() {
        const selectedAttributes = getSelectedAttributes();
        console.log('Looking for variation with:', selectedAttributes);

        // Ищем точное совпадение
        const variation = findExactVariation(selectedAttributes);

        if (variation) {
            console.log('Found variation:', variation);
            displayVariation(variation);
        } else {
            console.log('No exact variation found');
            // Если нет точного совпадения, сбрасываем отображение
            resetDisplay();
        }
    }

    // Функция поиска точной вариации
    function findExactVariation(selectedAttributes) {
        return variations.find(variation => {
            // Проверяем, что все выбранные атрибуты совпадают
            const allMatch = Object.keys(selectedAttributes).every(key => {
                const variationAttr = variation.attributes[`attribute_${key}`];
                return variationAttr === selectedAttributes[key];
            });

            // Проверяем, что у вариации нет других обязательных атрибутов
            const hasAllRequired = Object.keys(variation.attributes).every(attrKey => {
                if (!variation.attributes[attrKey]) return true; // Пустой атрибут - не обязательный

                const attrName = attrKey.replace('attribute_', '');
                return selectedAttributes[attrName] === variation.attributes[attrKey];
            });

            return allMatch && hasAllRequired;
        });
    }

    // Функция отображения вариации
    function displayVariation(variation) {
        console.log('Displaying variation:', variation);

        // Обновляем цену
        const priceElement = document.querySelector('.price');
        if (priceElement && variation.price_html) {
            priceElement.innerHTML = variation.price_html;
        }

        // Обновляем SKU
        const skuElement = document.querySelector('.sku');
        if (skuElement) {
            skuElement.textContent = variation.sku || 'N/A';
        }

        // Обновляем изображение
        if (variation.image && variation.image.src) {
            const mainImage = document.querySelector('.woocommerce-product-gallery__image img');
            if (mainImage) {
                mainImage.src = variation.image.src;
                if (variation.image.srcset) {
                    mainImage.srcset = variation.image.srcset;
                }
            }
        }

        // Обновляем ID вариации
        const variationIdInput = document.querySelector('input[name="variation_id"]');
        if (variationIdInput) {
            variationIdInput.value = variation.variation_id;
        }

        // Обновляем наличие
        const stockElement = document.querySelector('.stock');
        if (stockElement && variation.availability_html) {
            stockElement.outerHTML = variation.availability_html;
        } else if (variation.availability_html) {
            const priceElement = document.querySelector('.price');
            if (priceElement) {
                priceElement.insertAdjacentHTML('afterend', variation.availability_html);
            }
        }

        // Триггерим событие
        document.body.dispatchEvent(new CustomEvent('wc_variation_selected', {
            detail: { variation: variation }
        }));
    }

    // Функция сброса отображения
    function resetDisplay() {
        // Сбрасываем цену на диапазон
        const priceElement = document.querySelector('.price');
        if (priceElement) {
            // Показываем диапазон цен из всех вариаций
            const prices = variations.map(v => parseFloat(v.display_price)).filter(p => !isNaN(p));
            if (prices.length > 0) {
                const minPrice = Math.min(...prices);
                const maxPrice = Math.max(...prices);
                const currencySymbol = document.querySelector('.woocommerce-Price-currencySymbol')?.textContent || '₽';

                if (minPrice === maxPrice) {
                    priceElement.innerHTML = `<span class="woocommerce-Price-amount amount">${minPrice} <span class="woocommerce-Price-currencySymbol">${currencySymbol}</span></span>`;
                } else {
                    priceElement.innerHTML = `
                        <span class="woocommerce-Price-amount amount">${minPrice} <span class="woocommerce-Price-currencySymbol">${currencySymbol}</span></span> –
                        <span class="woocommerce-Price-amount amount">${maxPrice} <span class="woocommerce-Price-currencySymbol">${currencySymbol}</span></span>
                    `;
                }
            }
        }

        // Сбрасываем SKU
        const skuElement = document.querySelector('.sku');
        if (skuElement) {
            skuElement.textContent = 'N/A';
        }

        // Сбрасываем ID вариации
        const variationIdInput = document.querySelector('input[name="variation_id"]');
        if (variationIdInput) {
            variationIdInput.value = '';
        }
    }

    // Инициализация из скрытых селектов
    function initializeFromHiddenSelects() {
        variationSelects.forEach(select => {
            const attribute = select.dataset.attribute;
            const hiddenSelect = document.querySelector(`.variations select[data-attribute_name="${attribute}"]`);

            if (hiddenSelect && hiddenSelect.value) {
                const selectedValue = hiddenSelect.value;
                const label = select.querySelector('label');
                const matchingOption = select.querySelector(`.variation-option[data-value="${selectedValue}"]`);

                if (matchingOption && label) {
                    label.textContent = matchingOption.textContent;
                    label.dataset.id = selectedValue;
                }
            }
        });

        // После инициализации обновляем доступность
        updateAllOptionsAvailability();

        // Пробуем найти вариацию
        findAndDisplayVariation();
    }
});