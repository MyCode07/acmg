<?php

add_action('pre_get_posts', 'filter_woocommerce_products', 20);
function filter_woocommerce_products($query)
{
    if (is_admin() || !$query->is_main_query()) return;

    if (!is_shop() && !is_product_category() && !is_product_tag()) return;

    $meta_query = $query->get('meta_query');

    if (!is_array($meta_query)) $meta_query = [];

    $has_custom_filter = false;
    $attribute_condition = ['relation' => 'OR'];

    foreach ($_GET as $key => $value) {
        if (strpos($key, 'pa_') === 0 || taxonomy_exists('pa_' . $key)) {
            $taxonomy = (strpos($key, 'pa_') === 0) ? $key : 'pa_' . $key;

            if (!empty($value)) {
                $has_custom_filter = true;

                if (is_array($value)) {
                    $terms = array_map('sanitize_text_field', $value);
                } else {
                    $terms = explode(',', sanitize_text_field($value));
                }

                foreach ($terms as $term_slug) {
                    $term_slug = sanitize_title($term_slug);
                    if (get_term_by('slug', $term_slug, $taxonomy)) {

                        $attribute_condition[] = [
                            'key' => 'attribute_' . $taxonomy,
                            'value' => $term_slug,
                            'compare' => '='
                        ];
                    }
                }

                $meta_query[] = $attribute_condition;
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
        if (!empty($meta_query)) {
            if (count($meta_query) > 1) {
                $meta_query = array_merge(['relation' => 'AND'], $meta_query);
            }
            $query->set('meta_query', $meta_query);
        }
    }

    pre( $query->get('meta_query'));

    // Убеждаемся что тип поста правильный
    $query->set('post_type', ['product', 'product_variation']);
}
