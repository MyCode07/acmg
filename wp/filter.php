<?php

add_action('pre_get_posts', 'filter_woocommerce_products', 20);
function filter_woocommerce_products($query)
{
    if (is_admin() || !$query->is_main_query()) return;
    if (!is_shop() && !is_product_category() && !is_product_tag()) return;

    $tax_query = $query->get('tax_query');
    if (!is_array($tax_query)) $tax_query = [];

    $has_custom_filter = false;
    $meta_query = $query->get('meta_query');
    if (!is_array($meta_query)) $meta_query = [];

    // Сохраняем оригинальные фильтры
    $original_tax_query = $tax_query;
    $original_meta_query = $meta_query;

    // Фильтрация по атрибутам
    foreach ($_GET as $key => $value) {
        $taxonomy = 'pa_' . $key;

        if (taxonomy_exists($taxonomy) && !empty($value)) {
            $terms = explode(',', sanitize_text_field($value));
            $valid_terms = [];

            foreach ($terms as $term_slug) {
                $term_slug = sanitize_title($term_slug);
                if (get_term_by('slug', $term_slug, $taxonomy)) {
                    $valid_terms[] = $term_slug;
                }
            }

            if (!empty($valid_terms)) {
                $tax_query[] = [
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => $valid_terms,
                    'operator' => 'IN',
                ];
                $has_custom_filter = true;
            }
        }
    }

    // Фильтрация по цене
    $price_filter_added = false;

    if (isset($_GET['price_min']) && is_numeric($_GET['price_min'])) {
        $price_min = floatval($_GET['price_min']);
        if ($price_min >= 0) {
            $meta_query[] = [
                'key'     => '_price',
                'value'   => $price_min,
                'type'    => 'NUMERIC',
                'compare' => '>='
            ];
            $price_filter_added = true;
        }
    }

    if (isset($_GET['price_max']) && is_numeric($_GET['price_max'])) {
        $price_max = floatval($_GET['price_max']);
        if ($price_max > 0) {
            $meta_query[] = [
                'key'     => '_price',
                'value'   => $price_max,
                'type'    => 'NUMERIC',
                'compare' => '<='
            ];
            $price_filter_added = true;
        }
    }

    if ($price_filter_added) {
        $has_custom_filter = true;
    }

    // Фильтрация по наличию (с русскими статусами)
    if (isset($_GET['nalichie']) && !empty($_GET['nalichie'])) {

        // Получаем массив значений (может быть строкой или массивом)
        $nalichie_values = $_GET['nalichie'];

        // Преобразуем в массив, если это строка
        if (!is_array($nalichie_values)) {
            $nalichie_values = array($nalichie_values);
        }

        // Очищаем и фильтруем значения
        $valid_statuses = [];
        $allowed_statuses = ['vnalichii', 'predzakaz', 'netvnalichii'];

        // Маппинг русских статусов на английские для WooCommerce
        $status_mapping = [
            'vnalichii' => 'instock',
            'predzakaz' => 'onbackorder',
            'netvnalichii' => 'outofstock'
        ];

        foreach ($nalichie_values as $status) {
            $status = sanitize_text_field($status);
            if (in_array($status, $allowed_statuses)) {
                // Используем маппинг для преобразования в английский статус
                $valid_statuses[] = $status_mapping[$status];
            }
        }

        // Добавляем фильтр если есть валидные значения
        if (!empty($valid_statuses)) {
            if (count($valid_statuses) === 1) {
                // Если выбран один статус
                $meta_query[] = [
                    'key'     => '_stock_status',
                    'value'   => $valid_statuses[0],
                    'compare' => '='
                ];
            } else {
                // Если выбрано несколько статусов
                $meta_query[] = [
                    'key'     => '_stock_status',
                    'value'   => $valid_statuses,
                    'compare' => 'IN'
                ];
            }
            $has_custom_filter = true;
        }
    }

    // Применяем фильтры если они есть
    if ($has_custom_filter) {
        // Объединяем tax_query
        if (!empty($tax_query)) {
            $tax_query = array_merge(
                ['relation' => 'AND'],
                $original_tax_query,
                array_diff_key($tax_query, $original_tax_query)
            );
            $query->set('tax_query', $tax_query);
        }

        // Объединяем meta_query (для фильтрации по цене и наличию)
        if (!empty($meta_query)) {
            // Если есть несколько условий в meta_query, добавляем relation
            if (count($meta_query) > 1) {
                // Проверяем, есть ли уже relation в meta_query
                $has_relation = false;
                foreach ($meta_query as $key => $item) {
                    if ($key === 'relation') {
                        $has_relation = true;
                        break;
                    }
                }

                if (!$has_relation) {
                    $meta_query = array_merge(
                        ['relation' => 'AND'],
                        $meta_query
                    );
                }
            }
            $query->set('meta_query', $meta_query);
        }
    }
}?>

<?php
$attribute_taxonomies = wc_get_attribute_taxonomies();
$category_id = get_queried_object_id();

if (is_product_category()) {
    $category_id = get_queried_object_id();
    $attribute_taxonomies = get_all_attribute_terms_flat_in_category($category_id);
}
$allowed_attrs = get_field('atributy_filtra', 'option');;
?>
<div class="filter">
    <div class="filter-top">
        <button class="filter-top__title">Фильтры</button>
        <button class="filter-close">
            <img src="<?php bloginfo('template_url') ?>/assets/img/icons/close.svg" alt="">
        </button>
    </div>
    <form action="">
        <div class="filter-item filter-cats">
            <?php
            $categories = get_terms(array(
                'taxonomy'   => 'product_cat',
                'orderby'    => 'name',
                'order'      => 'ASC',
                'hide_empty' => true,
            ));

            // Проверяем, есть ли категории
            if (!empty($categories) && !is_wp_error($categories)) {
                echo '<ul>';
                foreach ($categories as $category) {
                    $category_link = get_term_link($category);
                    $category_name = $category->name;
                    $product_count = $category->count;
                    echo '<li><a href="' . esc_url($category_link) . '">' . esc_html($category_name) . ' (' . $product_count . ')</a></li>';
                }
                echo '</ul>';
            }
            ?>
        </div>

        <?php
            $price_range = get_price_range_for_filter();
            if ($price_range) :
                $min = $price_range['min'];
                $max = $price_range['max'];
                $current_min = $price_range['current_min'];
                $current_max = $price_range['current_max'];
                // $current_url = remove_query_arg(array('price_min', 'price_max'));
        ?>
            <div class="filter-item filter-price">
                <p>Цена, руб.</p>
                <div class="price-inputs">
                    <span>
                        <i>от</i>
                        <input type="number"
                            name="price_min"
                            class="price-input"
                            id="min-price"
                            value="<?php echo esc_attr($current_min); ?>"
                            data-min="<?php echo esc_attr($min); ?>"
                            data-max="<?php echo esc_attr($max); ?>"
                            min="<?php echo esc_attr($min); ?>"
                            max="<?php echo esc_attr($max); ?>">
                    </span>
                    <span>
                        <i>до</i>
                        <input type="number"
                            name="price_max"
                            class="price-input"
                            id="max-price"
                            value="<?php echo esc_attr($current_max); ?>"
                            data-min="<?php echo esc_attr($min); ?>"
                            data-max="<?php echo esc_attr($max); ?>"
                            min="<?php echo esc_attr($min); ?>"
                            max="<?php echo esc_attr($max); ?>">
                    </span>
                </div>
                <div class="slider-container"
                    data-min="<?php echo esc_attr($min); ?>"
                    data-max="<?php echo esc_attr($max); ?>"
                    data-current-min="<?php echo esc_attr($current_min); ?>"
                    data-current-max="<?php echo esc_attr($current_max); ?>">
                    <div class="slider-track"></div>
                    <div class="slider-range" id="slider-range"></div>
                    <div class="slider-thumb min" id="min-thumb"></div>
                    <div class="slider-thumb max" id="max-thumb"></div>
                </div>
            </div>
        <?php endif; ?>

        <div class="filter-item filter-sale">
            <label for="sale">
                <span class="checkbox-custom"></span>
                На распродаже
                <input type="checkbox"
                    id="sale"
                    name="sale"
                    value="1"
                    <?php echo in_array('sale', $_GET) ? 'checked' : ''; ?>
                    >
            </label>
        </div>

        <?php
        $selected_nalichie = isset($_GET['nalichie']) ? $_GET['nalichie'] : array();
        if (!is_array($selected_nalichie)) {
            $selected_nalichie = array($selected_nalichie);
        }
        $selected_nalichie = array_filter($selected_nalichie);
        ?>
        <div class="filter-item filter-nalichie">
            <p><span>Наличие</span></p>
            <div class="filter-item__values">
                <label for="vnalichii">
                    <span class="checkbox-custom"></span>
                    В наличии
                    <input type="checkbox"
                        id="vnalichii"
                        name="nalichie[]"
                        value="vnalichii"
                        <?php echo in_array('vnalichii', $selected_nalichie) ? 'checked' : ''; ?>
                        >
                </label>
                <label for="predzakaz">
                    <span class="checkbox-custom"></span>
                    Предзаказ
                    <input type="checkbox"
                        id="predzakaz"
                        name="nalichie[]"
                        value="predzakaz"
                        <?php echo in_array('predzakaz', $selected_nalichie) ? 'checked' : ''; ?>
                        >
                </label>
                <label for="netvnalichii">
                    <span class="checkbox-custom"></span>
                    Нет в наличии
                    <input type="checkbox"
                        id="netvnalichii"
                        name="nalichie[]"
                        value="netvnalichii"
                        <?php echo in_array('netvnalichii', $selected_nalichie) ? 'checked' : ''; ?>
                       >
                </label>
            </div>
        </div>

        <?php
            if (!empty($attribute_taxonomies)):
                foreach ($attribute_taxonomies as $tax):
                    $show_count = 0;
                    $terms = get_terms(array(
                        'taxonomy' => 'pa_' . $tax->attribute_name,
                        'hide_empty' => true,
                        'orderby' => 'name',
                        'order' => 'DESC'
                    ));
                    if($terms && in_array($tax->attribute_id, $allowed_attrs)):
        ?>
            <div class="filter-item">
                <p><span><?php echo $tax->attribute_label ?></span></p>
                <div class="filter-item__values">
                    <?php


                    // ортировку цвета нужно сделать как на стинержи - своим списком
                    usort($terms, function ($a, $b) {
                        // Извлекаем числовое значение из имени терма
                        $a_num = (int) filter_var($a->name, FILTER_SANITIZE_NUMBER_INT);
                        $b_num = (int) filter_var($b->name, FILTER_SANITIZE_NUMBER_INT);

                        // Сортировка по убыванию (DESC)
                        return $b_num <=> $a_num;
                    });


                    foreach ($terms as $key => $term) :
                        $show_count++;
                    ?>
                        <label for="<?php echo "pa_$tax->attribute_name-$term->slug" ?>">
                            <span></span>
                            <?php echo $term->name ?>
                            <input type="checkbox" id="<?php echo "pa_$tax->attribute_name-$term->slug" ?>" name="<?php echo $tax->attribute_name ?>" value="<?php echo $term->slug ?>" <?php echo is_attribute_checked($tax->attribute_name, $term->slug); ?>>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endif; endforeach; ?>
        <?php endif; ?>
        <div class="filter-main-actions">
            <a href="<?php echo esc_url(remove_query_arg(array_keys($_GET))) ?>" class="_btn _btn-border" data-apply-filter>
               Очистить
            </a>
            <!-- <button class="_btn _btn-border" >Очистить</button> -->
            <button class="_btn _btn-black" data-apply-filter>Применить</button>
        </div>
    </form>
</div>
 