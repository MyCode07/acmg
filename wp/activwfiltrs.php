<?php
$show_reset_btn = false;

// Проверяем есть ли поисковый запрос
$has_search_query = isset($_GET['search']) && !empty($_GET['search']);

// Функция для проверки наличия фильтров в URL
function has_filters_in_url() {
    return isset($_GET['price_min']) || isset($_GET['max_price']) || isset($_GET['nalichie']);
}

if (check_attributes_in_query() || $has_search_query || has_filters_in_url()): ?>
    <div class="active-filters">
        <?php
        // Фильтры по атрибутам
        foreach ($_GET as $key => $value) {
            if (taxonomy_exists('pa_' . $key)) {
                $terms = explode(',', $value);
                $attribute_name = wc_attribute_label('pa_' . $key);
                foreach ($terms as $term_slug) {
                    $term_slug = sanitize_title($term_slug);
                    $term = get_term_by('slug', $term_slug, 'pa_' . $key);

                    if ($term) {
                        $show_reset_btn = true;

                        // Создаем URL для удаления этого фильтра
                        $new_terms = array_diff($terms, [$term_slug]);
                        $new_url = add_query_arg(
                            $key,
                            implode(',', $new_terms),
                            remove_query_arg($key)
                        );

                        echo '<a href="' . esc_url($new_url) . '" class="active-filters__item">' . $attribute_name . ': ' . $term->name . '
                                <svg width="12" height="12" viewBox="0 0 12 12"><use xlink:href="' . get_template_directory_uri() . '/assets/img/svg/icons.svg#close" /></svg>
                            </a>';
                    }
                }
            }
        }

        // Фильтр по наличию
        if (isset($_GET['nalichie']) && !empty($_GET['nalichie'])) {
            $nalichie_values = explode(',', $_GET['nalichie']);

            $nalichie_labels = [
                'vnalichii' => 'В наличии',
                'predzakaz' => 'Предзаказ',
                'netvnalichii' => 'Нет в наличии'
            ];

            foreach ($nalichie_values as $status) {
                if (isset($nalichie_labels[$status])) {
                    $show_reset_btn = true;

                    // Создаем URL для удаления этого статуса наличия
                    $new_statuses = array_diff($nalichie_values, [$status]);
                    $new_url = remove_query_arg('nalichie');
                    if (!empty($new_statuses)) {
                        foreach ($new_statuses as $s) {
                            $new_url = add_query_arg('nalichie[]', $s, $new_url);
                        }
                    }

                    echo '<a href="' . esc_url($new_url) . '" class="active-filters__item">' . $nalichie_labels[$status] . '
                            <svg width="12" height="12" viewBox="0 0 12 12"><use xlink:href="' . get_template_directory_uri() . '/assets/img/svg/icons.svg#close" /></svg>
                        </a>';
                }
            }
        }

        // Фильтр по минимальной цене
        if (isset($_GET['price_min']) && is_numeric($_GET['price_min']) && $_GET['price_min'] > 0) {
            $price_min = floatval($_GET['price_min']);
            $show_reset_btn = true;

            $new_url = remove_query_arg('price_min');

            echo '<a href="' . esc_url($new_url) . '" class="active-filters__item">От ' . wc_price($price_min) . '
                    <svg width="12" height="12" viewBox="0 0 12 12"><use xlink:href="' . get_template_directory_uri() . '/assets/img/svg/icons.svg#close" /></svg>
                </a>';
        }

        // Фильтр по максимальной цене
        if (isset($_GET['max_price']) && is_numeric($_GET['max_price']) && $_GET['max_price'] > 0) {
            $max_price = floatval($_GET['max_price']);
            $show_reset_btn = true;

            $new_url = remove_query_arg('max_price');

            echo '<a href="' . esc_url($new_url) . '" class="active-filters__item">До ' . wc_price($max_price) . '
                    <svg width="12" height="12" viewBox="0 0 12 12"><use xlink:href="' . get_template_directory_uri() . '/assets/img/svg/icons.svg#close" /></svg>
                </a>';
        }

        // Фильтр по диапазону цен (если указаны и min и max)
        if (isset($_GET['price_min']) && isset($_GET['max_price']) &&
            is_numeric($_GET['price_min']) && is_numeric($_GET['max_price']) &&
            $_GET['price_min'] > 0 && $_GET['max_price'] > 0) {

            $price_min = floatval($_GET['price_min']);
            $max_price = floatval($_GET['max_price']);
            $show_reset_btn = true;

            $new_url = remove_query_arg(['price_min', 'max_price']);

            // Показываем отдельный элемент для диапазона (можно закомментировать, если не нужно)
            // echo '<a href="' . esc_url($new_url) . '" class="active-filters__item">Цена: ' . wc_price($price_min) . ' - ' . wc_price($max_price) . '
            //         <svg width="12" height="12" viewBox="0 0 12 12"><use xlink:href="' . get_template_directory_uri() . '/assets/img/svg/icons.svg#close" /></svg>
            //     </a>';
        }

        // распродажа
        if (isset($_GET['sale']) && in_array($_GET['sale'], ['1', 'yes', 'true', 'on'])){

            echo '<a href="' . esc_url(remove_query_arg('sale')) . '" class="active-filters__item">
                    На распродаже
                    <svg width="12" height="12" viewBox="0 0 12 12">
                        <use xlink:href="' . esc_url(get_template_directory_uri()) . '/assets/img/svg/icons.svg#close" />
                    </svg>
                </a>';
        }

        // Поисковый запрос
        if ($has_search_query) {
            $show_reset_btn = true;
            $search_query = sanitize_text_field($_GET['search']);

            echo '<a href="' . esc_url(remove_query_arg('search')) . '" class="active-filters__item">
                    Поиск: "' . esc_html($search_query) . '"
                    <svg width="12" height="12" viewBox="0 0 12 12">
                        <use xlink:href="' . esc_url(get_template_directory_uri()) . '/assets/img/svg/icons.svg#close" />
                    </svg>
                </a>';
        }
        ?>

        <!-- Кнопка сброса всех фильтров -->
        <?php if ($show_reset_btn): ?>
            <a href="<?php echo esc_url(remove_query_arg(array_keys($_GET))) ?>" class="reset-filters">
                Сбросить фильтры
                <svg width="12" height="12" viewBox="0 0 12 12">
                    <use xlink:href="<?php bloginfo('template_url') ?>/assets/img/svg/icons.svg#close" />
                </svg>
            </a>
        <?php endif; ?>

    </div>
<?php endif; ?>