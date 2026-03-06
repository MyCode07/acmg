<?php
/**
 * Plugin Name: WooCommerce Variation SEO URLs
 * Plugin URI:
 * Description: Создает ЧПУ URL для вариативных товаров с атрибутами, управляет SEO данными и генерирует sitemap
 * Version: 1.0.0
 * Author:
 * License: GPL v2 or later
 */

if (!defined('ABSPATH')) {
    exit; // Запрет прямого доступа
}

/**
 * Класс для управления вариативными товарами WooCommerce
 */
class WC_Variation_SEO_URLS {

    /**
     * Конструктор
     */
    public function __construct() {
        // Инициализация хуков
        $this->init_hooks();
    }

    /**
     * Инициализация хуков WordPress
     */
    private function init_hooks() {
        // Rewrite rules
        add_action('init', array($this, 'add_rewrite_rules'));

        // Обработка запросов
        add_action('template_redirect', array($this, 'handle_variation_request'));

        // Фильтры для подмены данных товара
        add_action('woocommerce_before_single_product', array($this, 'setup_variation_filters'));

        // Метабокс в админке
        add_action('woocommerce_product_after_variable_attributes', array($this, 'add_variation_url_metabox'), 10, 3);

        // SEO фильтры
        add_filter('pre_get_document_title', array($this, 'modify_seo_title'));
        add_filter('wp_title', array($this, 'modify_seo_title'), 99);
        add_filter('wpseo_metadesc', array($this, 'modify_meta_description'));
        add_filter('rank_math/frontend/description', array($this, 'modify_meta_description'));
        add_filter('aioseop_description', array($this, 'modify_meta_description'));
        add_filter('wpseo_canonical', array($this, 'modify_canonical_url'));
        add_filter('rank_math/frontend/canonical', array($this, 'modify_canonical_url'));

        // Open Graph теги
        add_action('wp_head', array($this, 'add_og_tags'));

        // Хлебные крошки
        add_filter('woocommerce_get_breadcrumb', array($this, 'modify_breadcrumbs'), 10, 2);

        // Sitemap
        add_action('admin_menu', array($this, 'add_sitemap_page'));
        add_action('wp', array($this, 'setup_sitemap_schedule'));
        add_action('generate_variation_sitemap_cron', array($this, 'generate_sitemap'));
        add_filter('robots_txt', array($this, 'add_sitemap_to_robots'), 10, 2);

        // Отладка (только для админов)
        if (current_user_can('administrator')) {
            add_action('init', array($this, 'debug_rewrite_rules'));
            add_action('wp', array($this, 'debug_template_loading'));
        }
    }

    /**
     * Добавление правил перезаписи URL
     */
    public function add_rewrite_rules() {
        add_rewrite_rule(
            '^product/([a-z0-9-]+(?:-[0-9]+)+)/?$',
            'index.php?product_variation_path=$matches[1]&post_type=product',
            'top'
        );

        add_rewrite_tag('%product_variation_path%', '([^&]+)');
    }

    /**
     * Обработка запроса вариации
     */
    public function handle_variation_request() {
        $variation_path = get_query_var('product_variation_path');

        if (empty($variation_path)) {
            return;
        }

        // Разбираем URL на части
        $path_parts = explode('-', $variation_path);

        $attribute_values = array();
        $product_slug_parts = array();

        foreach ($path_parts as $part) {
            if (is_numeric($part)) {
                $attribute_values[] = $part;
            } else {
                $product_slug_parts[] = $part;
            }
        }

        $product_slug = implode('-', $product_slug_parts);

        // Находим родительский товар
        $product = get_page_by_path($product_slug, OBJECT, 'product');

        if (!$product) {
            return;
        }

        // Находим вариацию по значениям атрибутов
        $variation_id = $this->find_variation_by_attributes($product->ID, $attribute_values);

        if ($variation_id) {
            // Перенаправляем на стандартный URL с параметром
            $product_url = get_permalink($product->ID);
            $variation_url = add_query_arg('variation_id', $variation_id, $product_url);

            wp_redirect($variation_url);
            exit;
        }
    }

    /**
     * Поиск ID вариации по значениям атрибутов
     */
    private function find_variation_by_attributes($product_id, $attribute_values) {
        $product = wc_get_product($product_id);

        if (!$product || !$product->is_type('variable')) {
            return false;
        }

        $variations = $product->get_available_variations();

        foreach ($variations as $variation) {
            $attributes = $variation['attributes'];
            $matches = true;

            $index = 0;
            foreach ($attributes as $attr_value) {
                if (!empty($attr_value) && isset($attribute_values[$index]) && $attr_value != $attribute_values[$index]) {
                    $matches = false;
                    break;
                }
                $index++;
            }

            if ($matches) {
                return $variation['variation_id'];
            }
        }

        return false;
    }

    /**
     * Настройка фильтров для подмены данных вариацией
     */
    public function setup_variation_filters() {
        $variation_id = isset($_GET['variation_id']) ? intval($_GET['variation_id']) : 0;

        if ($variation_id) {
            set_query_var('variation_id', $variation_id);

            // Добавляем фильтры для подмены данных
            add_filter('woocommerce_product_get_sku', array($this, 'override_sku'));
            add_filter('woocommerce_product_get_price', array($this, 'override_price'));
            add_filter('woocommerce_product_get_regular_price', array($this, 'override_price'));
            add_filter('woocommerce_product_get_sale_price', array($this, 'override_sale_price'));
            add_filter('woocommerce_product_get_stock_status', array($this, 'override_stock_status'));
            add_filter('post_thumbnail_html', array($this, 'override_product_image'), 10, 5);
            add_filter('the_title', array($this, 'override_page_title'), 10, 2);
        }
    }

    /**
     * Фильтры для подмены данных
     */
    public function override_sku($sku) {
        $variation_id = get_query_var('variation_id');
        if ($variation_id) {
            $variation = wc_get_product($variation_id);
            return $variation->get_sku();
        }
        return $sku;
    }

    public function override_price($price) {
        $variation_id = get_query_var('variation_id');
        if ($variation_id) {
            $variation = wc_get_product($variation_id);
            return $variation->get_price();
        }
        return $price;
    }

    public function override_sale_price($price) {
        $variation_id = get_query_var('variation_id');
        if ($variation_id) {
            $variation = wc_get_product($variation_id);
            return $variation->get_sale_price();
        }
        return $price;
    }

    public function override_stock_status($status) {
        $variation_id = get_query_var('variation_id');
        if ($variation_id) {
            $variation = wc_get_product($variation_id);
            return $variation->get_stock_status();
        }
        return $status;
    }

    public function override_product_image($html, $post_id, $post_thumbnail_id, $size, $attr) {
        $variation_id = get_query_var('variation_id');
        if ($variation_id) {
            $variation = wc_get_product($variation_id);
            $image_id = $variation->get_image_id();

            if ($image_id) {
                $image_html = wp_get_attachment_image($image_id, $size, false, $attr);
                if ($image_html) {
                    return $image_html;
                }
            }
        }
        return $html;
    }

    public function override_page_title($title, $id) {
        $variation_id = get_query_var('variation_id');

        if ($variation_id && in_the_loop() && $id == get_the_ID()) {
            $variation = wc_get_product($variation_id);
            $parent = wc_get_product($variation->get_parent_id());

            $attributes = $variation->get_attributes();
            $attr_string = implode(' ', $attributes);

            return $parent->get_name() . ' ' . $attr_string;
        }

        return $title;
    }

    /**
     * Получение URL вариации
     */
    public function get_variation_url($product_id, $variation_id) {
        $cache_key = 'variation_url_' . $variation_id;
        $cached_url = wp_cache_get($cache_key, 'variations');

        if ($cached_url !== false) {
            return $cached_url;
        }

        $product = wc_get_product($product_id);
        $variation = wc_get_product($variation_id);

        if (!$product || !$variation) {
            return '';
        }

        $base_slug = $product->get_slug();
        $attributes = $variation->get_attributes();

        $url_parts = array($base_slug);

        foreach ($attributes as $attribute_value) {
            if (!empty($attribute_value)) {
                $url_parts[] = $attribute_value;
            }
        }

        $url = home_url('/product/' . implode('-', $url_parts) . '/');

        // Кэшируем результат
        wp_cache_set($cache_key, $url, 'variations', HOUR_IN_SECONDS);

        return $url;
    }

    /**
     * Метабокс с URL вариации в админке
     */
    public function add_variation_url_metabox($loop, $variation_data, $variation) {
        $variation_id = $variation->ID;
        $product_id = get_the_ID();

        $variation_url = $this->get_variation_url($product_id, $variation_id);

        echo '<div class="variation-url-field">';
        echo '<h4>URL вариации:</h4>';
        echo '<input type="text" readonly value="' . esc_url($variation_url) . '" style="width:100%;" onclick="this.select()">';
        echo '<p class="description">Используйте этот URL для прямой ссылки на вариацию</p>';
        echo '</div>';
    }

    /**
     * Изменение SEO заголовка
     */
    public function modify_seo_title($title) {
        $variation_id = get_query_var('variation_id');

        if ($variation_id) {
            $variation = wc_get_product($variation_id);
            $product = wc_get_product($variation->get_parent_id());

            $attributes = $variation->get_attributes();
            $attr_string = implode(' ', $attributes);

            return $product->get_name() . ' ' . $attr_string . ' | ' . get_bloginfo('name');
        }

        return $title;
    }

    /**
     * Изменение мета-описания
     */
    public function modify_meta_description($description) {
        $variation_id = get_query_var('variation_id');

        if ($variation_id) {
            $variation = wc_get_product($variation_id);
            $product = wc_get_product($variation->get_parent_id());

            $attributes = $variation->get_attributes();
            $attr_string = implode(', ', $attributes);

            return sprintf(
                'Купить %s %s по лучшей цене. Характеристики: %s. Доставка по всей России.',
                $product->get_name(),
                $attr_string,
                $attr_string
            );
        }

        return $description;
    }

    /**
     * Изменение канонического URL
     */
    public function modify_canonical_url($canonical) {
        $variation_id = get_query_var('variation_id');

        if ($variation_id) {
            $product_id = wp_get_post_parent_id($variation_id);
            return $this->get_variation_url($product_id, $variation_id);
        }

        return $canonical;
    }

    /**
     * Добавление Open Graph тегов
     */
    public function add_og_tags() {
        $variation_id = get_query_var('variation_id');

        if ($variation_id) {
            $variation = wc_get_product($variation_id);
            $product = wc_get_product($variation->get_parent_id());

            $attributes = $variation->get_attributes();
            $attr_string = implode(' ', $attributes);

            $image_id = $variation->get_image_id();
            $image_url = wp_get_attachment_image_url($image_id, 'full');

            if (!$image_url) {
                $image_id = $product->get_image_id();
                $image_url = wp_get_attachment_image_url($image_id, 'full');
            }
            ?>

            <meta property="og:title" content="<?php echo esc_attr($product->get_name() . ' ' . $attr_string); ?>" />
            <meta property="og:description" content="<?php echo esc_attr(wp_trim_words($variation->get_description(), 20)); ?>" />
            <meta property="og:url" content="<?php echo esc_url($this->get_variation_url($product->get_id(), $variation_id)); ?>" />
            <?php if ($image_url): ?>
            <meta property="og:image" content="<?php echo esc_url($image_url); ?>" />
            <?php endif; ?>
            <meta property="product:price:amount" content="<?php echo esc_attr($variation->get_price()); ?>" />
            <meta property="product:price:currency" content="<?php echo esc_attr(get_woocommerce_currency()); ?>" />

            <?php
        }
    }

    /**
     * Изменение хлебных крошек
     */
    public function modify_breadcrumbs($crumbs, $breadcrumb) {
        $variation_id = get_query_var('variation_id');

        if ($variation_id) {
            $variation = wc_get_product($variation_id);
            $attributes = $variation->get_attributes();

            $crumbs[] = array(
                implode(' ', $attributes),
                ''
            );
        }

        return $crumbs;
    }

    /**
     * Добавление страницы sitemap в админку
     */
    public function add_sitemap_page() {
        add_management_page(
            'Sitemap вариаций',
            'Sitemap вариаций',
            'manage_options',
            'variation-sitemap',
            array($this, 'render_sitemap_page')
        );
    }

    /**
     * Отображение страницы sitemap
     */
    public function render_sitemap_page() {
        ?>
        <div class="wrap">
            <h1>Генерация Sitemap для вариаций</h1>

            <form method="post" action="">
                <?php wp_nonce_field('generate_variation_sitemap', 'sitemap_nonce'); ?>
                <input type="submit" name="generate_sitemap" class="button button-primary" value="Сгенерировать Sitemap">
            </form>

            <?php
            if (isset($_POST['generate_sitemap']) && check_admin_referer('generate_variation_sitemap', 'sitemap_nonce')) {
                $this->generate_sitemap();
            }

            $upload_dir = wp_upload_dir();
            $sitemap_path = $upload_dir['basedir'] . '/variation-sitemap.xml';

            if (file_exists($sitemap_path)) {
                $sitemap_url = $upload_dir['baseurl'] . '/variation-sitemap.xml';
                echo '<p>Sitemap успешно создан: <a href="' . esc_url($sitemap_url) . '" target="_blank">' . esc_url($sitemap_url) . '</a></p>';
                echo '<p>Дата последней генерации: ' . date('Y-m-d H:i:s', filemtime($sitemap_path)) . '</p>';
                echo '<p>Найдено вариаций: ' . $this->get_sitemap_count() . '</p>';
            }
            ?>
        </div>
        <?php
    }

    /**
     * Получение количества вариаций в sitemap
     */
    private function get_sitemap_count() {
        $upload_dir = wp_upload_dir();
        $sitemap_path = $upload_dir['basedir'] . '/variation-sitemap.xml';

        if (file_exists($sitemap_path)) {
            $xml = simplexml_load_file($sitemap_path);
            return count($xml->url);
        }

        return 0;
    }

    /**
     * Генерация sitemap
     */
    public function generate_sitemap() {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        );

        $products = get_posts($args);

        $sitemap_items = array();

        foreach ($products as $product) {
            $product_obj = wc_get_product($product->ID);

            if ($product_obj && $product_obj->is_type('variable')) {
                $variations = $product_obj->get_available_variations();

                foreach ($variations as $variation) {
                    $variation_obj = wc_get_product($variation['variation_id']);

                    if ($variation_obj && $variation_obj->is_purchasable() && $variation_obj->is_in_stock()) {
                        $sitemap_items[] = array(
                            'url' => $this->get_variation_url($product->ID, $variation['variation_id']),
                            'lastmod' => get_the_modified_date('c', $variation['variation_id']),
                            'changefreq' => 'weekly',
                            'priority' => '0.8'
                        );
                    }
                }
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($sitemap_items as $item) {
            $xml .= "\t<url>\n";
            $xml .= "\t\t<loc>" . esc_url($item['url']) . "</loc>\n";
            $xml .= "\t\t<lastmod>" . $item['lastmod'] . "</lastmod>\n";
            $xml .= "\t\t<changefreq>" . $item['changefreq'] . "</changefreq>\n";
            $xml .= "\t\t<priority>" . $item['priority'] . "</priority>\n";
            $xml .= "\t</url>\n";
        }

        $xml .= '</urlset>';

        $upload_dir = wp_upload_dir();
        file_put_contents($upload_dir['basedir'] . '/variation-sitemap.xml', $xml);
    }

    /**
     * Настройка планировщика для sitemap
     */
    public function setup_sitemap_schedule() {
        if (!wp_next_scheduled('generate_variation_sitemap_cron')) {
            wp_schedule_event(time(), 'daily', 'generate_variation_sitemap_cron');
        }
    }

    /**
     * Добавление sitemap в robots.txt
     */
    public function add_sitemap_to_robots($output, $public) {
        if ($public) {
            $upload_dir = wp_upload_dir();
            $sitemap_url = $upload_dir['baseurl'] . '/variation-sitemap.xml';

            $output .= "\nSitemap: " . esc_url($sitemap_url) . "\n";
        }

        return $output;
    }

    /**
     * Отладка rewrite rules
     */
    public function debug_rewrite_rules() {
        $variation_path = get_query_var('product_variation_path');
        if ($variation_path) {
            error_log('product_variation_path: ' . $variation_path);
        }
    }

    /**
     * Отладка загрузки шаблона
     */
    public function debug_template_loading() {
        if (get_query_var('product_variation_path')) {
            global $template;
            error_log('Current template: ' . $template);
            error_log('Is product: ' . (is_product() ? 'yes' : 'no'));
            error_log('Is singular: ' . (is_singular() ? 'yes' : 'no'));
            error_log('Is archive: ' . (is_archive() ? 'yes' : 'no'));
        }
    }
}

/**
 * Инициализация плагина
 */
function init_wc_variation_seo_urls() {
    // Проверяем наличие WooCommerce
    if (class_exists('WooCommerce')) {
        new WC_Variation_SEO_URLS();
    } else {
        add_action('admin_notices', function() {
            ?>
            <div class="notice notice-error">
                <p><?php _e('Для работы плагина WC Variation SEO URLs требуется установленный и активированный WooCommerce.', 'wc-variation-seo-urls'); ?></p>
            </div>
            <?php
        });
    }
}
add_action('plugins_loaded', 'init_wc_variation_seo_urls');

/**
 * Активация плагина
 */
function wc_variation_seo_urls_activate() {
    // Добавляем правила перезаписи
    if (class_exists('WC_Variation_SEO_URLS')) {
        $plugin = new WC_Variation_SEO_URLS();
        $plugin->add_rewrite_rules();
    }

    // Сбрасываем правила перезаписи
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'wc_variation_seo_urls_activate');

/**
 * Деактивация плагина
 */
function wc_variation_seo_urls_deactivate() {
    // Удаляем запланированные задачи
    wp_clear_scheduled_hook('generate_variation_sitemap_cron');

    // Сбрасываем правила перезаписи
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'wc_variation_seo_urls_deactivate');

?>