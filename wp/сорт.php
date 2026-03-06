<div class="catalog-sort select-input">
    <label data-id="0">
        <?php
        // Логика отображения текущего выбранного значения в label
        if (isset($_GET['orderby'])) {
            switch ($_GET['orderby']) {
                case 'price':
                    echo 'Сначала дешевые';
                    break;
                case 'price-desc':
                    echo 'Сначала дорогие';
                    break;
                case 'popular':
                    echo 'Сначала популярные';
                    break;
                // --- НОВЫЕ ОПЦИИ ---
                case 'newness':
                    echo 'По новизне';
                    break;
                default:
                    echo 'Сначала популярные';
                    break;
            }
        } else {
            echo 'Сначала популярные'; // Значение при первой загрузке страницы
        }
        ?>
    </label>
    <svg width="12" height="7" viewBox="0 0 12 7" fill="none">
        <use xlink:href='<?php bloginfo('template_url') ?>/assets/img/svg/icons.svg#arrow' />
    </svg>
    <div class="select">
        <div class="select-body">
            <span data-id="1" <?php if (isset($_GET['orderby']) && 'price' == $_GET['orderby']) : ?> class="_active" <?php endif; ?>>
                <a href="?orderby=price" data-sort="price">Сначала дешевые</a>
            </span>
            <span data-id="2" <?php if (isset($_GET['orderby']) && 'price-desc' == $_GET['orderby']) : ?> class="_active" <?php endif; ?>>
                <a href="?orderby=price-desc" data-sort="price-desc">Сначала дорогие</a>
            </span>
            <span data-id="3" <?php if (isset($_GET['orderby']) && 'popular' == $_GET['orderby']) : ?> class="_active" <?php endif; ?>>
                <a href="?orderby=popular" data-sort="popular">Сначала популярные</a>
            </span>
            <span data-id="4" <?php if (isset($_GET['orderby']) && 'newness' == $_GET['orderby']) : ?> class="_active" <?php endif; ?>>
                <a href="?orderby=newness" data-sort="newness">По новизне</a>
            </span>
        </div>
    </div>
</div>