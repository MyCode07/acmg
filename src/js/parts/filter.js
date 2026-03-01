class PriceRangeSlider {
    constructor() {
        this.elements = {
            minInput: document.getElementById('min-price'),
            maxInput: document.getElementById('max-price'),
            minThumb: document.getElementById('min-thumb'),
            maxThumb: document.getElementById('max-thumb'),
            sliderRange: document.getElementById('slider-range'),
            minValue: document.getElementById('min-value'),
            maxValue: document.getElementById('max-value')
        };

        // Получаем ВСЕ значения из input-полей
        this.minPrice = parseInt(this.elements.minInput.getAttribute('data-min')) || 0;
        this.maxPrice = parseInt(this.elements.maxInput.getAttribute('data-max')) || 1000;
        this.currentMin = parseInt(this.elements.minInput.value) || this.minPrice;
        this.currentMax = parseInt(this.elements.maxInput.value) || this.maxPrice;

        this.isTyping = false;
        this.typingTimeout = null;
        this.isDragging = false;

        this.init();
    }

    init() {
        // Валидация значений
        this.validateValues();

        this.updateDisplayValues();
        this.updateSlider();
        this.addEventListeners();
    }

    validateValues() {
        // Проверяем, чтобы min был не меньше минимального предела
        this.currentMin = Math.max(this.minPrice, this.currentMin);
        // Проверяем, чтобы max был не больше максимального предела
        this.currentMax = Math.min(this.maxPrice, this.currentMax);
        // Проверяем, чтобы min был меньше max
        if (this.currentMin >= this.currentMax) {
            this.currentMin = this.currentMax - 1;
        }
        if (this.currentMax <= this.currentMin) {
            this.currentMax = this.currentMin + 1;
        }
    }

    enableFilterBtn() {
        const btn = document.querySelector('.price-filter-btn');
        if (!btn) return

        btn.classList.add('_active');
    }

    addEventListeners() {
        // События для полей ввода - с задержкой
        this.elements.minInput.addEventListener('input', (e) => {
            let value = parseInt(e.target.value);
            if (isNaN(value)) return;

            this.isTyping = true;

            if (this.typingTimeout) {
                clearTimeout(this.typingTimeout);
            }

            this.typingTimeout = setTimeout(() => {
                value = Math.max(this.minPrice, Math.min(value, this.maxPrice));

                if (value >= this.currentMax) {
                    value = this.currentMax - 1;
                }

                this.currentMin = value;
                this.validateValues();
                this.updateSlider();
                this.isTyping = false;
            }, 500);

            this.enableFilterBtn()
        });

        this.elements.maxInput.addEventListener('input', (e) => {
            let value = parseInt(e.target.value);
            if (isNaN(value)) return;

            this.isTyping = true;

            if (this.typingTimeout) {
                clearTimeout(this.typingTimeout);
            }

            this.typingTimeout = setTimeout(() => {
                value = Math.max(this.minPrice, Math.min(value, this.maxPrice));

                if (value <= this.currentMin) {
                    value = this.currentMin + 1;
                }

                this.currentMax = value;
                this.validateValues();
                this.updateSlider();
                this.isTyping = false;
            }, 500);

            this.enableFilterBtn()
        });

        // События для потери фокуса
        this.elements.minInput.addEventListener('blur', (e) => {
            if (this.typingTimeout) {
                clearTimeout(this.typingTimeout);
            }

            let value = parseInt(e.target.value);
            if (isNaN(value)) {
                value = this.currentMin;
            }

            value = Math.max(this.minPrice, Math.min(value, this.maxPrice));

            if (value >= this.currentMax) {
                value = this.currentMax - 1;
            }

            this.currentMin = value;
            this.validateValues();
            this.updateSlider();
            this.isTyping = false;
        });

        this.elements.maxInput.addEventListener('blur', (e) => {
            if (this.typingTimeout) {
                clearTimeout(this.typingTimeout);
            }

            let value = parseInt(e.target.value);
            if (isNaN(value)) {
                value = this.currentMax;
            }

            value = Math.max(this.minPrice, Math.min(value, this.maxPrice));

            if (value <= this.currentMin) {
                value = this.currentMin + 1;
            }

            this.currentMax = value;
            this.validateValues();
            this.updateSlider();
            this.isTyping = false;
        });

        // События для клавиши Enter
        this.elements.minInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                if (this.typingTimeout) {
                    clearTimeout(this.typingTimeout);
                }
                e.target.blur();
            }
        });

        this.elements.maxInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                if (this.typingTimeout) {
                    clearTimeout(this.typingTimeout);
                }
                e.target.blur();
            }
        });

        // События для ползунков - Desktop и Mobile
        this.addSliderEvents(this.elements.minThumb, 'min');
        this.addSliderEvents(this.elements.maxThumb, 'max');
    }

    addSliderEvents(thumb, type) {
        // Desktop events
        thumb.addEventListener('mousedown', (e) => {
            if (this.isTyping) return;
            e.preventDefault();
            this.startDragging(type, e, 'mouse');
        });

        // Mobile events
        thumb.addEventListener('touchstart', (e) => {
            if (this.isTyping) return;
            e.preventDefault();
            this.startDragging(type, e, 'touch');
        });

        // Предотвращаем выделение текста при перетаскивании
        thumb.addEventListener('dragstart', (e) => {
            e.preventDefault();
        });
    }

    startDragging(type, e, inputType) {
        if (this.isDragging) return;

        this.isDragging = true;
        const sliderRect = this.elements.minThumb.parentElement.getBoundingClientRect();
        const sliderWidth = sliderRect.width;

        const moveEvent = inputType === 'touch' ? 'touchmove' : 'mousemove';
        const endEvent = inputType === 'touch' ? 'touchend' : 'mouseup';

        const moveHandler = (moveEvent) => {
            const clientX = inputType === 'touch' ?
                moveEvent.touches[0].clientX : moveEvent.clientX;

            const x = clientX - sliderRect.left;
            let percentage = (x / sliderWidth) * 100;
            percentage = Math.max(0, Math.min(100, percentage));

            const value = Math.round(this.minPrice + (percentage / 100) * (this.maxPrice - this.minPrice));

            if (type === 'min') {
                this.currentMin = Math.min(value, this.currentMax - 1);
            } else {
                this.currentMax = Math.max(value, this.currentMin + 1);
            }

            this.updateSlider();
        };

        const endHandler = () => {
            document.removeEventListener(moveEvent, moveHandler);
            document.removeEventListener(endEvent, endHandler);
            document.removeEventListener('mouseup', endHandler); // Для безопасности
            document.removeEventListener('touchend', endHandler); // Для безопасности
            this.isDragging = false;
        };

        document.addEventListener(moveEvent, moveHandler);
        document.addEventListener(endEvent, endHandler);

        // Добавляем дополнительные обработчики для безопасности
        document.addEventListener('mouseup', endHandler);
        document.addEventListener('touchend', endHandler);

        // Предотвращаем скролл на мобильных при перетаскивании
        if (inputType === 'touch') {
            document.body.style.overflow = 'hidden';
            document.body.style.userSelect = 'none';

            // Восстанавливаем после завершения
            const restoreStyles = () => {
                document.body.style.overflow = '';
                document.body.style.userSelect = '';
            };
            document.addEventListener(endEvent, restoreStyles, { once: true });
        }
        this.enableFilterBtn()
    }

    updateSlider() {
        // Не обновляем поля ввода если пользователь печатает
        if (!this.isTyping) {
            this.elements.minInput.value = this.currentMin;
            this.elements.maxInput.value = this.currentMax;
        }

        const minPercentage = ((this.currentMin - this.minPrice) / (this.maxPrice - this.minPrice)) * 100;
        const maxPercentage = ((this.currentMax - this.minPrice) / (this.maxPrice - this.minPrice)) * 100;

        // Обновление позиции ползунков
        this.elements.minThumb.style.left = `${minPercentage}%`;
        this.elements.maxThumb.style.left = `${maxPercentage}%`;

        // Обновление заполненной области
        this.elements.sliderRange.style.left = `${minPercentage}%`;
        this.elements.sliderRange.style.width = `${maxPercentage - minPercentage}%`;

        this.updateDisplayValues();
    }

    updateDisplayValues() {
        return;
        // Обновление отображаемых значений под слайдером
        this.elements.minValue.textContent = this.minPrice;
        this.elements.maxValue.textContent = this.maxPrice;
    }

    getValues() {
        return {
            min: this.currentMin,
            max: this.currentMax
        };
    }
}
document.addEventListener('DOMContentLoaded', () => {
    if (document.querySelector('.filter-price')) {
        const priceSlider = new PriceRangeSlider();
    }
});