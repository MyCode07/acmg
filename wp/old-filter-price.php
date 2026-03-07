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