<?php

// Получаем минимальную и максимальную цену товаров
function get_price_range_for_filter() {
    global $wpdb, $wp_query;

    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'post_status' => 'publish',
    );

    // Если это страница категории - фильтруем по текущей категории
    if (is_tax('product_cat')) {
        $current_cat = get_queried_object();
        $args['tax_query'] = array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'term_id',
                'terms' => $current_cat->term_id,
                'include_children' => true
            )
        );
    }

    $products = new WP_Query($args);
    $prices = array();

    if ($products->have_posts()) {
        while ($products->have_posts()) {
            $products->the_post();
            global $product;

            if ($product->is_type('variable')) {
                // Для вариативных товаров берем минимальную и максимальную цену
                $min_price = $product->get_variation_price('min');
                $max_price = $product->get_variation_price('max');
                $prices[] = $min_price;
                $prices[] = $max_price;
            } else {
                // Для простых товаров
                $prices[] = $product->get_price();
            }
        }
        wp_reset_postdata();
    }

    if (!empty($prices)) {
        $min_price = min($prices);
        $max_price = max($prices);

        // Получаем текущие значения из URL (GET параметры)
        $current_min = isset($_GET['price_min']) && is_numeric($_GET['price_min'])
            ? (int)$_GET['price_min']
            : floor($min_price);

        $current_max = isset($_GET['price_max']) && is_numeric($_GET['price_max'])
            ? (int)$_GET['price_max']
            : ceil($max_price);

        // Валидация - значения не должны выходить за пределы диапазона
        $current_min = max($min_price, min($current_min, $max_price));
        $current_max = min($max_price, max($current_min, $current_max));

        return array(
            'min' => floor($min_price),
            'max' => ceil($max_price),
            'current_min' => $current_min,
            'current_max' => $current_max
        );
    }

    return false;
}

// Получает все используемые атрибуты и термы для товаров в категории.
function get_all_attribute_terms_flat_in_category($category_id)
{
    $product_ids = get_posts(array(
        'post_type'      => 'product',
        'posts_per_page' => -1,
        'fields'         => 'ids',
        'tax_query'      => array(
            array(
                'taxonomy' => 'product_cat',
                'field'    => 'term_id',
                'terms'    => $category_id,
            ),
        ),
    ));

    if (empty($product_ids)) return [];

    $taxonomies = [];

    $attributs = wc_get_attribute_taxonomies();

    foreach ($attributs as $key => $tax) {
        $taxonomy = 'pa_' . $tax->attribute_name;

        $terms = wp_get_object_terms($product_ids, $taxonomy, array(
            'orderby' => 'name',
        ));

        if (!empty($terms)) {
            $taxonomies[$key] = $tax;
        }
    }

    return $taxonomies;
}

// sort by menu order via custom get arg popular and newness
add_filter('woocommerce_get_catalog_ordering_args', 'custom_sorting');
function custom_sorting($args)
{

    if ((isset($_GET['orderby']) && $_GET['orderby'] == 'popular') || !isset($_GET['orderby'])) {
        $args['orderby'] = 'menu_order';
        $args['order']   = 'DESC';
    }

    if ((isset($_GET['orderby']) && $_GET['orderby'] == 'newness')) {
        $args['orderby'] = 'date';
        $args['order']   = 'DESC';
    }

    return $args;
}


// вараиции в каталоге + филтрация
add_action( 'woocommerce_product_query', 'custom_product_filter', 25 );
function custom_product_filter( $query ) {
	$query->set( 'post_type', array( 'product','product_variation' ) );

    $tax_query = $query->get( 'tax_query' );
    $meta_query = $query->get('meta_query');


    // скрываем оригинальный товар
	$tax_query[] = array(
 		'taxonomy' => 'product_type',
		'field'    => 'slug',
		'terms'    => 'variable',
		'operator' => 'NOT IN',
 	);

    // Фильтрация по атрибутам
    foreach ($_GET as $key => $value) {
        $taxonomy = 'pa_' . $key;

        if (taxonomy_exists($taxonomy) && !empty($value)) {
            // Проверяем, является ли значение массивом (из чекбоксов)
            if (is_array($value)) {
                $terms = array_map('sanitize_text_field', $value);
            } else {
                // Если это строка, разбиваем по запятой (для обратной совместимости)
                $terms = explode(',', sanitize_text_field($value));
            }

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
    if (isset($_GET['price_min']) && is_numeric($_GET['price_min'])) {
        $price_min = floatval($_GET['price_min']);
        if ($price_min >= 0) {
            $meta_query[] = [
                'key'     => '_price',
                'value'   => $price_min,
                'type'    => 'NUMERIC',
                'compare' => '>='
            ];
            $has_custom_filter = true;
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
            $has_custom_filter = true;
        }
    }

    // Фильтрация по наличию
    if (isset($_GET['nalichie']) && !empty($_GET['nalichie'])) {
        $nalichie_values = explode(',', $_GET['nalichie']);

        $valid_statuses = [];
        $allowed_statuses = ['vnalichii', 'predzakaz', 'netvnalichii'];
        $status_mapping = [
            'vnalichii' => 'instock',
            'predzakaz' => 'onbackorder',
            'netvnalichii' => 'outofstock'
        ];

        foreach ($nalichie_values as $status) {
            $status = sanitize_text_field($status);
            if (in_array($status, $allowed_statuses)) {
                $valid_statuses[] = $status_mapping[$status];
            }
        }

        if (!empty($valid_statuses)) {
            if (count($valid_statuses) === 1) {
                $meta_query[] = [
                    'key'     => '_stock_status',
                    'value'   => $valid_statuses[0],
                    'compare' => '='
                ];
            } else {
                $meta_query[] = [
                    'key'     => '_stock_status',
                    'value'   => $valid_statuses,
                    'compare' => 'IN'
                ];
            }
            $has_custom_filter = true;
        }
    }

    // Фильтрация по скидке
    if (isset($_GET['sale']) && !empty($_GET['sale'])) {
        $sale_value = sanitize_text_field($_GET['sale']);

        if (in_array($sale_value, ['1', 'yes', 'true', 'on'])) {
            $meta_query[] = [
                'key'     => '_sale_price',
                'value'   => '',
                'compare' => '!='
            ];
            $has_custom_filter = true;
        }
    }

    // Применяем фильтры если они есть
    if ($has_custom_filter) {
        if (count($tax_query) > 1) {
            $tax_query = array_merge(['relation' => 'AND'], $tax_query);
        }

        if (!empty($meta_query)) {
            if (count($meta_query) > 1) {
                $meta_query = array_merge(['relation' => 'AND'], $meta_query);
            }
            $query->set('meta_query', $meta_query);
        }
    }

	$query->set( 'tax_query', $tax_query );
}