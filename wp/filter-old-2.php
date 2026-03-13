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

            // Пропускаем если продукт не существует
            if (!$product) {
                continue;
            }

            if ($product->is_type('variable')) {
                // Для вариативных товаров берем минимальную и максимальную цену
                $min_price = $product->get_variation_price('min');
                $max_price = $product->get_variation_price('max');

                // Проверяем что цены существуют и являются числами
                if (is_numeric($min_price) && $min_price > 0) {
                    $prices[] = floatval($min_price);
                }

                if (is_numeric($max_price) && $max_price > 0) {
                    $prices[] = floatval($max_price);
                }
            } else {
                // Для простых товаров
                $price = $product->get_price();

                // Проверяем что цена существует, не пустая и является числом
                if ($price !== '' && $price !== null && is_numeric($price) && $price > 0) {
                    $prices[] = floatval($price);
                }
            }
        }
        wp_reset_postdata();
    }

    // Удаляем дубликаты и пустые значения
    $prices = array_filter(array_unique($prices));

    // Сортируем цены по возрастанию
    sort($prices, SORT_NUMERIC);

    if (!empty($prices)) {
        $min_price = min($prices);
        $max_price = max($prices);

        // Получаем текущие значения из URL (GET параметры)
        $current_min = isset($_GET['price_min']) && is_numeric($_GET['price_min'])
            ? floatval($_GET['price_min'])
            : floor($min_price);

        $current_max = isset($_GET['price_max']) && is_numeric($_GET['price_max'])
            ? floatval($_GET['price_max'])
            : ceil($max_price);

        // Валидация - значения не должны выходить за пределы диапазона
        $current_min = max($min_price, min($current_min, $max_price));
        $current_max = min($max_price, max($current_min, $current_max));

        return array(
            'min' => floor($min_price),
            'max' => ceil($max_price),
            'current_min' => floor($current_min),
            'current_max' => ceil($current_max),
            'all_prices' => $prices // Добавил для отладки, можно удалить
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

    $query->set('tax_query', $tax_query);

    $simple_ids = get_simple_products_by_attributes();
    $variation_ids = get_variations_by_attributes();

    $all_ids = array_merge( $simple_ids, $variation_ids );
    $all_ids = array_unique( $all_ids );

    if ( !empty( $all_ids ) ) {
        $query->set( 'post__in', $all_ids );
        // $query->set( 'tax_query', array() );
        // $query->set( 'meta_query', array() );
    }
}

// Получаем простые товары по атрибутам
function get_simple_products_by_attributes() {
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'post_status' => 'publish',
        'tax_query' => array(
            'relation' => 'AND',
            array(
                'taxonomy' => 'product_type',
                'field'    => 'slug',
                'terms'    => array( 'simple', 'variable' ),
                'operator' => 'IN'
            )
        ),
        'meta_query' => array()
    );

    // Добавляем фильтры по атрибутам
    foreach ( $_GET as $key => $value ) {
        $taxonomy = 'pa_' . $key;

        if ( taxonomy_exists( $taxonomy ) && ! empty( $value ) ) {
            if ( is_array( $value ) ) {
                $terms = array_map( 'sanitize_text_field', $value );
            } else {
                $terms = explode( ',', sanitize_text_field( $value ) );
            }

            $valid_terms = array();

            foreach ( $terms as $term_slug ) {
                $term_slug = sanitize_title( $term_slug );
                if ( get_term_by( 'slug', $term_slug, $taxonomy ) ) {
                    $valid_terms[] = $term_slug;
                }
            }

            if ( ! empty( $valid_terms ) ) {
                $args['tax_query'][] = array(
                    'taxonomy' => $taxonomy,
                    'field'    => 'slug',
                    'terms'    => $valid_terms,
                    'operator' => 'IN'
                );
            }
        }
    }

    // Добавляем фильтр по цене
    if ( isset( $_GET['price_min'] ) && is_numeric( $_GET['price_min'] ) ) {
        $args['meta_query'][] = array(
            'key'     => '_price',
            'value'   => floatval( $_GET['price_min'] ),
            'type'    => 'NUMERIC',
            'compare' => '>='
        );
    }

    if ( isset( $_GET['price_max'] ) && is_numeric( $_GET['price_max'] ) ) {
        $args['meta_query'][] = array(
            'key'     => '_price',
            'value'   => floatval( $_GET['price_max'] ),
            'type'    => 'NUMERIC',
            'compare' => '<='
        );
    }

    // Добавляем фильтр по наличию
    if ( isset( $_GET['nalichie'] ) && ! empty( $_GET['nalichie'] ) ) {
        $nalichie_values = explode( ',', $_GET['nalichie'] );

        $valid_statuses = array();
        $status_mapping = array(
            'vnalichii' => 'instock',
            'predzakaz' => 'onbackorder',
            'netvnalichii' => 'outofstock'
        );

        foreach ( $nalichie_values as $status ) {
            $status = sanitize_text_field( $status );
            if ( isset( $status_mapping[$status] ) ) {
                $valid_statuses[] = $status_mapping[$status];
            }
        }

        if ( ! empty( $valid_statuses ) ) {
            if ( count( $valid_statuses ) === 1 ) {
                $args['meta_query'][] = array(
                    'key'     => '_stock_status',
                    'value'   => $valid_statuses[0],
                    'compare' => '='
                );
            } else {
                $args['meta_query'][] = array(
                    'key'     => '_stock_status',
                    'value'   => $valid_statuses,
                    'compare' => 'IN'
                );
            }
        }
    }

    // Добавляем фильтр по скидке
    if ( isset( $_GET['sale'] ) && ! empty( $_GET['sale'] ) ) {
        $sale_value = sanitize_text_field( $_GET['sale'] );

        if ( in_array( $sale_value, array( '1', 'yes', 'true', 'on' ) ) ) {
            $args['meta_query'][] = array(
                'key'     => '_sale_price',
                'value'   => '',
                'compare' => '!='
            );
        }
    }

    // Добавляем relation для meta_query если нужно
    if ( count( $args['meta_query'] ) > 1 ) {
        $args['meta_query']['relation'] = 'AND';
    }

    $simple_query = new WP_Query( $args );
    return $simple_query->posts;
}
// Получаем вариации по атрибутам
function get_variations_by_attributes() {
    $args = array(
        'post_type' => 'product_variation',
        'posts_per_page' => -1,
        'fields' => 'ids',
        'post_status' => array('publish'),
        'meta_query' => array()
    );

    // Добавляем фильтры по атрибутам
    foreach ( $_GET as $key => $value ) {
        $taxonomy = 'pa_' . $key;

        if ( taxonomy_exists( $taxonomy ) && ! empty( $value ) ) {
            if ( is_array( $value ) ) {
                $terms = array_map( 'sanitize_text_field', $value );
            } else {
                $terms = explode( ',', sanitize_text_field( $value ) );
            }

            $term_conditions = array( 'relation' => 'OR' );

            foreach ( $terms as $term_slug ) {
                $term_slug = sanitize_title( $term_slug );
                if ( get_term_by( 'slug', $term_slug, $taxonomy ) ) {
                    $term_conditions[] = array(
                        'key'   => 'attribute_' . $taxonomy,
                        'value' => $term_slug,
                        'compare' => '='
                    );
                }
            }

            if ( count( $term_conditions ) > 1 ) {
                $args['meta_query'][] = $term_conditions;
            }
        }
    }

    // Добавляем фильтр по цене
    if ( isset( $_GET['price_min'] ) && is_numeric( $_GET['price_min'] ) ) {
        $args['meta_query'][] = array(
            'key'     => '_price',
            'value'   => floatval( $_GET['price_min'] ),
            'type'    => 'NUMERIC',
            'compare' => '>='
        );
    }

    if ( isset( $_GET['price_max'] ) && is_numeric( $_GET['price_max'] ) ) {
        $args['meta_query'][] = array(
            'key'     => '_price',
            'value'   => floatval( $_GET['price_max'] ),
            'type'    => 'NUMERIC',
            'compare' => '<='
        );
    }

    // Добавляем фильтр по наличию
    if ( isset( $_GET['nalichie'] ) && ! empty( $_GET['nalichie'] ) ) {
        $nalichie_values = explode( ',', $_GET['nalichie'] );

        $valid_statuses = array();
        $status_mapping = array(
            'vnalichii' => 'instock',
            'predzakaz' => 'onbackorder',
            'netvnalichii' => 'outofstock'
        );

        foreach ( $nalichie_values as $status ) {
            $status = sanitize_text_field( $status );
            if ( isset( $status_mapping[$status] ) ) {
                $valid_statuses[] = $status_mapping[$status];
            }
        }

        if ( ! empty( $valid_statuses ) ) {
            if ( count( $valid_statuses ) === 1 ) {
                $args['meta_query'][] = array(
                    'key'     => '_stock_status',
                    'value'   => $valid_statuses[0],
                    'compare' => '='
                );
            } else {
                $args['meta_query'][] = array(
                    'key'     => '_stock_status',
                    'value'   => $valid_statuses,
                    'compare' => 'IN'
                );
            }
        }
    }

    // Добавляем фильтр по скидке
    if ( isset( $_GET['sale'] ) && ! empty( $_GET['sale'] ) ) {
        $sale_value = sanitize_text_field( $_GET['sale'] );

        if ( in_array( $sale_value, array( '1', 'yes', 'true', 'on' ) ) ) {
            $args['meta_query'][] = array(
                'key'     => '_sale_price',
                'value'   => '',
                'compare' => '!='
            );
        }
    }

    // Добавляем relation для meta_query если нужно
    if ( count( $args['meta_query'] ) > 1 ) {
        $args['meta_query']['relation'] = 'AND';
    }

    $variation_query = new WP_Query( $args );
    return $variation_query->posts;
}