<?php
/**
 * Plugin Name: WooCommerce Variation Direct URLs
 * Description: Прямые URL для вариативных товаров с атрибутами
 * Version: 2.6.0
 */

if (!defined('ABSPATH')) {
    exit;
}

class WC_Variation_Direct_URLs {

    /**
     * @var int ID текущей вариации
     */
    private $current_variation_id = 0;

    /**
     * @var object Текущая вариация
     */
    private $current_variation = null;

    public function __construct() {
        // Добавляем query vars
        add_filter('query_vars', array($this, 'add_query_vars'));

        // Добавляем правила перезаписи
        add_action('init', array($this, 'add_rewrite_rules'));

        // Перехватываем запрос
        add_action('parse_request', array($this, 'handle_variation_request'), 0);

        // Инициализируем вариацию на wp hook
        add_action('wp', array($this, 'init_variation'), 5);

        // Фильтры для контента
        add_filter('the_title', array($this, 'modify_title'), 10, 2);
        add_filter('document_title_parts', array($this, 'modify_document_title'));

        // ФИЛЬТРЫ ДЛЯ ЦЕНЫ - добавляем фильтр HTML цены
        add_filter('woocommerce_get_price_html', array($this, 'modify_price_html'), 999, 2);
        add_filter('woocommerce_product_get_price', array($this, 'modify_product_price'), 999, 2);
        add_filter('woocommerce_product_get_regular_price', array($this, 'modify_product_price'), 999, 2);
        add_filter('woocommerce_product_get_sale_price', array($this, 'modify_product_sale_price'), 999, 2);

        // Остальные фильтры
        add_filter('woocommerce_product_get_sku', array($this, 'modify_product_sku'), 999, 2);
        add_filter('post_thumbnail_html', array($this, 'modify_product_image'), 999, 5);

        // Метабокс в админке
        add_action('woocommerce_product_after_variable_attributes', array($this, 'variation_url_field'), 10, 3);

        // Sitemap
        add_action('admin_menu', array($this, 'add_sitemap_page'));

        // Добавляем JavaScript для выбора вариации
        add_action('wp_footer', array($this, 'add_variation_selector_script'));
    }

    /**
     * Добавляем query vars
     */
    public function add_query_vars($vars) {
        $vars[] = 'vc_product_slug';
        $vars[] = 'vc_attr1';
        $vars[] = 'vc_attr2';
        $vars[] = 'vc_attr3';
        $vars[] = 'vc_attr4';
        $vars[] = 'vc_attr5';
        $vars[] = 'vc_variation_id';
        $vars[] = 'vc_product_id';
        return $vars;
    }

    /**
     * Добавляем правила перезаписи для URL с разным количеством атрибутов
     */
    public function add_rewrite_rules() {
        // Для 1 атрибута
        add_rewrite_rule(
            '^product-variation/([^/]+)/([^/]+)/?$',
            'index.php?vc_product_slug=$matches[1]&vc_attr1=$matches[2]',
            'top'
        );

        // Для 2 атрибутов
        add_rewrite_rule(
            '^product-variation/([^/]+)/([^/]+)/([^/]+)/?$',
            'index.php?vc_product_slug=$matches[1]&vc_attr1=$matches[2]&vc_attr2=$matches[3]',
            'top'
        );

        // Для 3 атрибутов
        add_rewrite_rule(
            '^product-variation/([^/]+)/([^/]+)/([^/]+)/([^/]+)/?$',
            'index.php?vc_product_slug=$matches[1]&vc_attr1=$matches[2]&vc_attr2=$matches[3]&vc_attr3=$matches[4]',
            'top'
        );
    }

    /**
     * Обрабатываем запрос вариации
     */
    public function handle_variation_request(&$wp) {
        // Проверяем наличие слага товара
        if (empty($wp->query_vars['vc_product_slug'])) {
            return;
        }

        $product_slug = $wp->query_vars['vc_product_slug'];

        // Собираем все значения атрибутов из query vars
        $attr_values = array();
        for ($i = 1; $i <= 5; $i++) {
            if (!empty($wp->query_vars['vc_attr' . $i])) {
                $attr_values[] = $wp->query_vars['vc_attr' . $i];
            }
        }

        if (empty($attr_values)) {
            return;
        }

        // Получаем товар по слагу
        $product = get_page_by_path($product_slug, OBJECT, 'product');

        if (!$product) {
            return;
        }

        // Ищем вариацию по значениям атрибутов
        $variation_id = $this->find_variation_by_attr_values($product->ID, $attr_values);

        if ($variation_id) {
            // Сохраняем ID вариации в классе
            $this->current_variation_id = $variation_id;
            $this->current_variation = wc_get_product($variation_id);

            // Сохраняем в куки для надежности
            setcookie('vc_variation_id', $variation_id, time() + 3600, '/');

            // Перенаправляем на страницу товара с параметром
            $redirect_url = add_query_arg('vc_variation', $variation_id, get_permalink($product->ID));
            wp_redirect($redirect_url);
            exit;
        }
    }

    /**
     * Инициализация вариации на странице товара
     */
    public function init_variation() {
        // Проверяем GET параметр
        if (isset($_GET['vc_variation']) && is_numeric($_GET['vc_variation'])) {
            $this->current_variation_id = intval($_GET['vc_variation']);
            $this->current_variation = wc_get_product($this->current_variation_id);
        }

        // Проверяем куки
        if (!$this->current_variation_id && isset($_COOKIE['vc_variation_id'])) {
            $this->current_variation_id = intval($_COOKIE['vc_variation_id']);
            $this->current_variation = wc_get_product($this->current_variation_id);
        }
    }

    /**
     * Поиск вариации по значениям атрибутов
     */
    private function find_variation_by_attr_values($product_id, $search_attr_values) {
        $product = wc_get_product($product_id);

        if (!$product || !$product->is_type('variable')) {
            return false;
        }

        $variations = $product->get_available_variations();

        foreach ($variations as $variation) {
            // Получаем значения атрибутов вариации (очищаем от пустых)
            $variation_attrs = array();
            foreach ($variation['attributes'] as $attr_value) {
                if (!empty($attr_value)) {
                    $variation_attrs[] = $attr_value;
                }
            }

            // Проверяем соответствие количества атрибутов
            if (count($variation_attrs) != count($search_attr_values)) {
                continue;
            }

            // Проверяем каждое значение
            $match = true;
            foreach ($search_attr_values as $index => $search_value) {
                if (!isset($variation_attrs[$index]) || $variation_attrs[$index] != $search_value) {
                    $match = false;
                    break;
                }
            }

            if ($match) {
                return $variation['variation_id'];
            }
        }

        return false;
    }

    /**
     * Получаем текущую вариацию
     */
    private function get_current_variation() {
        if ($this->current_variation) {
            return $this->current_variation;
        }

        if ($this->current_variation_id) {
            $this->current_variation = wc_get_product($this->current_variation_id);
            return $this->current_variation;
        }

        return null;
    }

    /**
     * Модифицируем заголовок
     */
    public function modify_title($title, $id) {
        if (!is_product() || $id != get_the_ID()) {
            return $title;
        }

        $variation = $this->get_current_variation();

        if ($variation) {
            $parent = wc_get_product($variation->get_parent_id());
            $attrs = $variation->get_attributes();
            $attr_text = implode(' ', array_filter($attrs));

            return $parent->get_name() . ($attr_text ? ' ' . $attr_text : '');
        }

        return $title;
    }

    /**
     * Модифицируем части заголовка для SEO
     */
    public function modify_document_title($parts) {
        if (!is_product()) {
            return $parts;
        }

        $variation = $this->get_current_variation();

        if ($variation && isset($parts['title'])) {
            $parent = wc_get_product($variation->get_parent_id());
            $attrs = $variation->get_attributes();
            $attr_text = implode(' ', array_filter($attrs));

            $parts['title'] = $parent->get_name() . ($attr_text ? ' ' . $attr_text : '');
        }

        return $parts;
    }

    /**
     * Модифицируем HTML цену
     */
    public function modify_price_html($price_html, $product) {
        if (!is_product() || $product->get_id() != get_the_ID()) {
            return $price_html;
        }

        $variation = $this->get_current_variation();

        if ($variation) {
            // Получаем цену вариации
            $variation_price = $variation->get_price_html();

            // Форматируем цену
            if ($variation_price) {
                return $variation_price;
            }
        }

        return $price_html;
    }

    /**
     * Модифицируем цену
     */
    public function modify_product_price($price, $product) {
        if (!is_product() || $product->get_id() != get_the_ID()) {
            return $price;
        }

        $variation = $this->get_current_variation();

        if ($variation) {
            return $variation->get_price();
        }

        return $price;
    }

    /**
     * Модифицируем цену со скидкой
     */
    public function modify_product_sale_price($price, $product) {
        if (!is_product() || $product->get_id() != get_the_ID()) {
            return $price;
        }

        $variation = $this->get_current_variation();

        if ($variation) {
            return $variation->get_sale_price();
        }

        return $price;
    }

    /**
     * Модифицируем SKU
     */
    public function modify_product_sku($sku, $product) {
        if (!is_product() || $product->get_id() != get_the_ID()) {
            return $sku;
        }

        $variation = $this->get_current_variation();

        if ($variation) {
            return $variation->get_sku();
        }

        return $sku;
    }

    /**
     * Модифицируем изображение
     */
    public function modify_product_image($html, $post_id, $post_thumbnail_id, $size, $attr) {
        if (!is_product() || $post_id != get_the_ID()) {
            return $html;
        }

        $variation = $this->get_current_variation();

        if ($variation) {
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

    /**
     * Добавляем JavaScript для выбора вариации
     */
    public function add_variation_selector_script() {
        if (!is_product()) {
            return;
        }

        $variation = $this->get_current_variation();

        if (!$variation) {
            return;
        }
        ?>
        <script type="text/javascript">
        jQuery(document).ready(function($) {
            var target_variation_id = <?php echo $variation->get_id(); ?>;

            console.log('Looking for variation: ' + target_variation_id);

            // Функция для выбора вариации
            function selectVariation() {
                var $form = $('.variations_form');

                if (!$form.length) {
                    setTimeout(selectVariation, 500);
                    return;
                }

                var variations = $form.data('product_variations');

                if (!variations) {
                    setTimeout(selectVariation, 500);
                    return;
                }

                // Ищем нужную вариацию
                $.each(variations, function(index, variation) {
                    if (variation.variation_id == target_variation_id) {
                        console.log('Found variation, setting attributes...');

                        // Устанавливаем атрибуты
                        $.each(variation.attributes, function(attr_name, attr_value) {
                            var $select = $form.find('select[name="' + attr_name + '"]');
                            if ($select.length) {
                                $select.val(attr_value).trigger('change');
                                console.log('Set ' + attr_name + ' to ' + attr_value);
                            }
                        });

                        // Обновляем цену и кнопку
                        $form.trigger('check_variations');
                        $('.single_add_to_cart_button').removeAttr('disabled');

                        // Принудительно обновляем цену
                        $('.price').html(variation.price_html);

                        return false;
                    }
                });
            }

            // Запускаем выбор вариации несколько раз для надежности
            selectVariation();
            setTimeout(selectVariation, 500);
            setTimeout(selectVariation, 1000);
        });
        </script>
        <?php
    }

    /**
     * Поле с URL вариации в админке
     */
    public function variation_url_field($loop, $variation_data, $variation) {
        $variation_id = $variation->ID;
        $product_id = $variation->post_parent;

        $product = wc_get_product($product_id);
        $variation_obj = wc_get_product($variation_id);

        if ($product && $variation_obj) {
            $slug = $product->get_slug();
            $attrs = $variation_obj->get_attributes();

            // Формируем URL с атрибутами как отдельные сегменты
            $url = home_url('/product-variation/' . $slug);
            foreach ($attrs as $attr) {
                if (!empty($attr)) {
                    $url .= '/' . $attr;
                }
            }
            $url .= '/';

            echo '<div class="variation-url-field">';
            echo '<h4>Прямая ссылка на вариацию:</h4>';
            echo '<input type="text" readonly value="' . esc_url($url) . '" style="width:100%; background:#f0f0f0;" onclick="this.select()">';
            echo '<p class="description">Слаг товара: ' . $product->get_slug() . '<br>';
            echo 'Значения атрибутов: ' . implode(', ', array_filter($attrs)) . '</p>';
            echo '</div>';
        }
    }

    /**
     * Страница sitemap
     */
    public function add_sitemap_page() {
        add_management_page(
            'Sitemap вариаций',
            'Sitemap вариаций',
            'manage_options',
            'variation-sitemap',
            array($this, 'render_sitemap')
        );
    }

    /**
     * Генерация sitemap
     */
    public function render_sitemap() {
        ?>
        <div class="wrap">
            <h1>Sitemap вариаций</h1>

            <?php
            if (isset($_POST['generate'])) {
                check_admin_referer('variation_sitemap');
                $count = $this->generate_sitemap();
                echo '<div class="notice notice-success"><p>Sitemap создан! Добавлено ' . $count . ' вариаций.</p></div>';
            }

            $upload_dir = wp_upload_dir();
            $sitemap_file = $upload_dir['basedir'] . '/variation-sitemap.xml';
            $sitemap_url = $upload_dir['baseurl'] . '/variation-sitemap.xml';

            if (file_exists($sitemap_file)) {
                echo '<p>Sitemap доступен по адресу: <br><a href="' . esc_url($sitemap_url) . '" target="_blank">' . esc_url($sitemap_url) . '</a></p>';
                echo '<p>Дата создания: ' . date('Y-m-d H:i:s', filemtime($sitemap_file)) . '</p>';
                echo '<p>Размер: ' . size_format(filesize($sitemap_file)) . '</p>';
            }
            ?>

            <form method="post" style="margin-top:20px;">
                <?php wp_nonce_field('variation_sitemap'); ?>
                <p>
                    <input type="submit" name="generate" class="button button-primary" value="Сгенерировать Sitemap">
                </p>
            </form>
        </div>
        <?php
    }

    /**
     * Генерация XML sitemap
     */
    private function generate_sitemap() {
        $args = array(
            'post_type' => 'product',
            'posts_per_page' => -1,
            'post_status' => 'publish'
        );

        $products = get_posts($args);
        $urls = array();

        foreach ($products as $product_post) {
            $product = wc_get_product($product_post->ID);

            if ($product && $product->is_type('variable')) {
                $variations = $product->get_available_variations();

                foreach ($variations as $variation) {
                    $variation_obj = wc_get_product($variation['variation_id']);

                    if ($variation_obj && $variation_obj->is_in_stock()) {
                        $slug = $product->get_slug();
                        $attrs = $variation_obj->get_attributes();

                        // Формируем URL
                        $url = home_url('/product-variation/' . $slug);
                        foreach ($attrs as $attr) {
                            if (!empty($attr)) {
                                $url .= '/' . $attr;
                            }
                        }
                        $url .= '/';

                        $urls[] = array(
                            'loc' => $url,
                            'lastmod' => get_the_modified_date('c', $variation['variation_id']),
                            'priority' => '0.8'
                        );
                    }
                }
            }
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            $xml .= "\t<url>\n";
            $xml .= "\t\t<loc>" . esc_url($url['loc']) . "</loc>\n";
            $xml .= "\t\t<lastmod>" . $url['lastmod'] . "</lastmod>\n";
            $xml .= "\t\t<priority>" . $url['priority'] . "</priority>\n";
            $xml .= "\t</url>\n";
        }

        $xml .= '</urlset>';

        $upload_dir = wp_upload_dir();
        file_put_contents($upload_dir['basedir'] . '/variation-sitemap.xml', $xml);

        return count($urls);
    }
}

// Инициализация плагина
new WC_Variation_Direct_URLs();

// Активация
register_activation_hook(__FILE__, function() {
    flush_rewrite_rules();
});

// Деактивация
register_deactivation_hook(__FILE__, function() {
    flush_rewrite_rules();
});