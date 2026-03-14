<?php
defined('ABSPATH') || exit;

global $product;

// Получаем параметры атрибутов из URL
$selected_attributes = array();
foreach ($_GET as $key => $value) {
    if (strpos($key, 'attribute_pa_') === 0) {
        $attribute_name = str_replace('attribute_pa_', 'pa_', $key);
        $selected_attributes[$attribute_name] = sanitize_title($value);
    }
}

// Если есть выбранные атрибуты, ищем соответствующую вариацию
$selected_variation = null;
$variation_data = array();

if (!empty($selected_attributes) && $product->is_type('variable')) {
    // $variations = $product->get_available_variations();
    $variations = get_product_all_variations($product);


    foreach ($variations as $variation) {
        $match = true;

        // Проверяем, совпадают ли все выбранные атрибуты
        foreach ($selected_attributes as $attr_name => $attr_value) {
            $variation_attr_key = 'attribute_' . $attr_name;

            if (!isset($variation['attributes'][$variation_attr_key]) ||
                $variation['attributes'][$variation_attr_key] !== $attr_value) {
                $match = false;
                break;
            }
        }

        if ($match) {
            $selected_variation = $variation;
            break;
        }
    }
}


$product_image_id = $selected_variation ? $selected_variation['image_id'] : $product->get_image_id();
$product_gallery_ids = $product->get_gallery_image_ids();
$product_attributes = $product->get_attributes();

$datetime_created = $product->get_date_created();
$timestamp_created = $datetime_created->getTimestamp();
$datetime_now = new WC_DateTime();
$timestamp_now = $datetime_now->getTimestamp();
$time_delta = $timestamp_now - $timestamp_created;
$days = 5;
$new_check = $days * (24 * 60 * 60);
?>

<main>

    <?php
    get_template_part('template-parts/breadcrumbs');
    ?>

    <section class="section single-product" data-replace-width="768">
        <div class="section__container _container">
            <div class="section__body" data-replace-new-position="afterbegin">
                <div class="single-product__img">
                    <div class="labels">
                        <?php
                        if (($selected_variation && $selected_variation['is_on_sale']) || $product->is_on_sale()) :
                        ?>
                            <label class="sale">Акция</label>
                        <?php endif; ?>
                    </div>
                    <div class="swiper slider-main">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide">
                                <a href="<?php echo wp_get_attachment_image_url($product_image_id, 'full') ?>" data-fancybox="product">
                                    <?php echo wp_get_attachment_image($product_image_id, 'full') ?>
                                </a>
                            </div>
                            <?php if ($product_gallery_ids) : ?>
                                <?php foreach ($product_gallery_ids as $product_gallery_id) : ?>
                                    <div class="swiper-slide">
                                        <a href="<?php echo wp_get_attachment_image_url($product_gallery_id, 'full') ?>" data-fancybox="product">
                                            <?php echo wp_get_attachment_image($product_gallery_id, 'full') ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="swiper slider-thumbs">
                        <div class="swiper-wrapper">
                            <div class="swiper-slide">
                                <?php echo wp_get_attachment_image($product_image_id, 'full') ?>
                            </div>
                            <?php if ($product_gallery_ids) : ?>
                                <?php foreach ($product_gallery_ids as $product_gallery_id) : ?>
                                    <div class="swiper-slide">
                                        <?php echo wp_get_attachment_image($product_gallery_id, 'full') ?>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="single-product__text" data-replace-old-position="afterbegin">
                    <div class="single-product__title" data-replace-element>
                        <?php
                            // Формируем заголовок
                            $title = get_the_title();
                            // Если выбрана вариация, добавляем значения атрибутов к заголовку
                            if ($selected_variation && !empty($selected_variation['attributes'])) {
                                $attribute_values = array();
                                foreach ($selected_variation['attributes'] as $attr_name => $attr_value) {
                                    if (!empty($attr_value)) {
                                        // Получаем название атрибута для красивого отображения
                                        $attr_name_clean = str_replace('attribute_pa_', '', $attr_name);
                                        $attr_name_clean = str_replace('attribute_', '', $attr_name_clean);

                                        // Пытаемся получить термин для красивого названия значения
                                        $term = get_term_by('slug', $attr_value, $attr_name_clean);
                                        if ($term && !is_wp_error($term)) {
                                            $attribute_values[] = $term->name;
                                        } else {
                                            $attribute_values[] = $attr_value;
                                        }
                                    }
                                }

                                if (!empty($attribute_values)) {
                                    $title .= ' ' . implode(' ', $attribute_values);
                                }
                            }
                        ?>
                        <h1><?php echo esc_html($title); ?></h1>
                        <div class="descr">
                            <?php
                            // Выводим описание вариации если есть, иначе описание товара
                            if ($selected_variation && !empty($selected_variation['variation_description'])) {
                                echo $selected_variation['variation_description'];
                            } else {
                                echo $product->get_short_description();
                            }
                            ?>
                        </div>
                    </div>

                    <?php if ($product->is_type('variable')) : ?>
                        <div class="grid grid-2 variations">
                            <?php
                            $attributes = $product->get_variation_attributes();
                            // Создаем массив существующих комбинаций для быстрого поиска
                            $existing_combinations = array();
                            $variation_data = array();
                            $attribute_options_availability = array();

                            foreach ($variations as $variation) {
                                $combination_key = '';
                                $attr_values = array();

                                foreach ($variation['attributes'] as $attr_name => $attr_value) {
                                    $attr_name_clean = str_replace('attribute_', '', $attr_name);
                                    $attr_values[$attr_name_clean] = $attr_value;
                                    $combination_key .= $attr_value . '|';
                                }

                                $combination_key = rtrim($combination_key, '|');
                                $existing_combinations[$combination_key] = true;

                                // Собираем информацию о доступных опциях для каждого атрибута
                                foreach ($variation['attributes'] as $attr_name => $attr_value) {
                                    $attr_name_clean = str_replace('attribute_', '', $attr_name);
                                    if (!isset($attribute_options_availability[$attr_name_clean])) {
                                        $attribute_options_availability[$attr_name_clean] = array();
                                    }
                                    $attribute_options_availability[$attr_name_clean][$attr_value] = true;
                                }

                                // Определяем класс статуса наличия
                                $stock_class = $variation['is_in_stock'] ? 'in-stock' : 'out-of-stock';
                                $stock_text = $variation['is_in_stock'] ? 'В наличии' : 'Нет в наличии';

                                // Сохраняем данные вариации
                                $variation_data[$combination_key] = array(
                                    'variation_id' => $variation['variation_id'],
                                    'price' => $variation['price_html'],
                                    'sku' => $variation['sku'],
                                    'stock' => $variation['is_in_stock'],
                                    'stock_class' => $stock_class,
                                    'stock_text' => $stock_text,
                                    'attributes' => $attr_values,
                                    'weight' => $variation['weight'],
                                    'dimensions' => $variation['dimensions']
                                );
                            }

                            // Получаем первый атрибут для группировки
                            $attribute_keys = array_keys($attributes);
                            $first_attribute = !empty($attribute_keys) ? $attribute_keys[0] : '';
                            $second_attribute = !empty($attribute_keys[1]) ? $attribute_keys[1] : '';
                            ?>
                            <?php if (!empty($attributes)) : ?>
                                <?php foreach ($attributes as $attribute_name => $options) : ?>
                                    <?php
                                    $attribute_label = wc_attribute_label($attribute_name);
                                    $attribute_id = sanitize_title($attribute_name);

                                    // Проверяем, выбран ли этот атрибут в URL
                                    $selected_value = '';
                                    $selected_option_label = 'Выберите'; // Значение по умолчанию

                                    if (isset($selected_attributes[$attribute_name])) {
                                        $selected_value = $selected_attributes[$attribute_name];
                                        // Получаем название выбранной опции
                                        $term = get_term_by('slug', $selected_value, $attribute_name);
                                        if ($term && !is_wp_error($term)) {
                                            $selected_option_label = $term->name;
                                        } else {
                                            $selected_option_label = $selected_value;
                                        }
                                    }

                                    // Если есть выбранная вариация, проверяем её атрибуты
                                    if ($selected_variation && isset($selected_variation['attributes']['attribute_' . $attribute_name])) {
                                        $variation_attr_value = $selected_variation['attributes']['attribute_' . $attribute_name];
                                        if (!empty($variation_attr_value)) {
                                            $selected_value = $variation_attr_value;
                                            // Получаем название опции из вариации
                                            $term = get_term_by('slug', $variation_attr_value, $attribute_name);
                                            if ($term && !is_wp_error($term)) {
                                                $selected_option_label = $term->name;
                                            } else {
                                                $selected_option_label = $variation_attr_value;
                                            }
                                        }
                                    }
                                    ?>
                                    <div class="var-item">
                                        <p><?php echo $attribute_label; ?></p>
                                        <div class="select-input variation-select"
                                            data-attribute="<?php echo esc_attr($attribute_id); ?>"
                                            data-attribute-name="<?php echo esc_attr($attribute_name); ?>">
                                            <label data-id="0"><?php echo esc_html($selected_option_label); ?></label>
                                            <svg width="12" height="7" viewBox="0 0 12 7">
                                                <use xlink:href='<?php bloginfo('template_url') ?>/assets/img/svg/icons.svg#arrow' />
                                            </svg>

                                            <div class="select">
                                                <div class="select-body">
                                                    <?php foreach ($options as $option) : ?>
                                                        <?php
                                                        // Проверяем, существует ли вариация с этим атрибутом
                                                        $has_variation = false;
                                                        $variation_url = '';
                                                        $is_in_stock = false;

                                                        // Проверяем, выбрана ли эта опция
                                                        $is_selected = ($selected_value === sanitize_title($option));

                                                        // Если опция соответствует значению из вариации, отмечаем её как выбранную
                                                        if ($selected_variation && !$is_selected) {
                                                            $variation_attr_key = 'attribute_' . $attribute_name;
                                                            if (isset($selected_variation['attributes'][$variation_attr_key]) &&
                                                                $selected_variation['attributes'][$variation_attr_key] === sanitize_title($option)) {
                                                                $is_selected = true;
                                                            }
                                                        }

                                                        foreach ($variations as $variation) {
                                                            $attrs = $variation['attributes'];
                                                            $attr_key = 'attribute_' . $attribute_name;

                                                            if (isset($attrs[$attr_key]) && $attrs[$attr_key] === sanitize_title($option)) {
                                                                $has_variation = true;
                                                                $is_in_stock = $variation['is_in_stock'];

                                                                // Получаем URL вариации
                                                                $variation_obj = wc_get_product($variation['variation_id']);
                                                                if ($variation_obj) {
                                                                    $variation_url = $variation_obj->get_permalink();
                                                                }

                                                                // Если это второй атрибут, проверяем комбинацию с выбранным первым
                                                                if ($attribute_name === $second_attribute && isset($_GET['attribute_pa_color'])) {
                                                                    $selected_first = sanitize_title($_GET['attribute_pa_color']);
                                                                    if (isset($attrs['attribute_' . $first_attribute]) &&
                                                                        $attrs['attribute_' . $first_attribute] === $selected_first) {
                                                                        break;
                                                                    }
                                                                } else {
                                                                    // Для первого атрибута или если это единственный атрибут
                                                                    break;
                                                                }
                                                            }
                                                        }

                                                        $disabled_class = !$has_variation ? 'disabled' : '';
                                                        $stock_class = !$is_in_stock ? 'out-of-stock-option' : '';
                                                        $selected_class = $is_selected ? 'selected' : '';
                                                        ?>
                                                        <?php if ($has_variation && !empty($variation_url)) : ?>
                                                            <span class="variation-option <?php echo $disabled_class; ?> <?php echo $stock_class; ?> <?php echo $selected_class; ?>">
                                                                <a href="<?php echo esc_url($variation_url); ?>" class="variation-option-link">
                                                                    <?php echo esc_html($option); ?>
                                                                </a>
                                                            </span>
                                                        <?php else : ?>
                                                            <span class="variation-option <?php echo $disabled_class; ?> <?php echo $stock_class; ?> <?php echo $selected_class; ?>">
                                                                <?php echo esc_html($option); ?>
                                                            </span>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    <?php else : ?>
                    <?php endif; ?>

                    <ol class="chars">
                        <?php
                            $allowed_attrs = get_field('atributy_tovara_s_ikonkami', 'option');
                            $allowed_icons = get_field('ikonki_atributov', 'option');

                            $icons_arr = [
                                'ids' => [],
                                'icons' => [],
                            ];
                            foreach($allowed_icons as $item){
                                $icons_arr['ids'][] = $item['atribut'][0];
                                $icons_arr['icons'][] = $item['ikonka'];
                            }
                            foreach ($product_attributes as $attr):
                                $attr_name = wc_attribute_label($attr->get_name());
                                $attr_value = $product->get_attribute($attr->get_name());

                                if ($selected_variation && isset($selected_variation['attributes']['attribute_' . $attr->get_name()])) {
                                    $attr_value = $selected_variation['attributes']['attribute_' . $attr->get_name()];
                                }
                                if(in_array($attr->get_id(), $allowed_attrs)):
                        ?>
                            <li>
                                <span>
                                    <?php
                                        $icon_pos = array_search($attr->get_id(), $icons_arr['ids']);
                                        if(is_numeric($icon_pos)):
                                    ?>
                                        <img src="<?php echo $icons_arr['icons'][$icon_pos] ?>" alt="">
                                    <?php endif; ?>
                                    <?php echo $attr_name ?>
                                </span>
                                <span><?php echo $attr_value ?></span>
                            </li>
                        <?php endif; endforeach; ?>
                    </ol>
                    <a href="#chars" class="_btn all-chars">
                        Все характетистики
                        <img src="<?php bloginfo('template_url') ?>/assets/img/icons/down.svg" alt="">
                    </a>
                </div>
                <div class="single-product__addtocart width-397">
                    <div class="single-product__actions">
                        <div class="single-product__stock">
                            <?php
                            $var = wc_get_product($selected_variation['variation_id']);
                            if ($selected_variation) {
                                $variable_status = $var->get_stock_status();
                                get_product_status( $variable_status);
                            }else{
                                get_product_status( $product->get_stock_status());
                            }

                            ?>
                            <div class="product-item__actions">
                                <?php
                                    if($selected_variation){
                                        woocommerce_list_button('wishlist',$selected_variation['variation_id']);
                                        woocommerce_list_button('compare',$selected_variation['variation_id']);
                                    }else{
                                        woocommerce_list_button('wishlist');
                                        woocommerce_list_button('compare');
                                    }
                                ?>
                            </div>
                        </div>
                        <div class="product-item__price">
                            <?php
                            if ($selected_variation) {
                                echo $selected_variation['price_html'];
                            } else {
                                echo $product->get_price_html();
                            }
                            ?>
                        </div>

                        <?php
                        if ($selected_variation) {
                            $is_in_cart = is_product_in_cart($selected_variation['variation_id']);
                        } else {
                            $is_in_cart = is_product_in_cart($product->get_id());
                        }

                        if($is_in_cart){
                            echo  '<a href="'.esc_url(wc_get_cart_url()).'" class="_btn _btn-accent-dark">
                                    <img src="'.get_template_directory_uri().'/assets/img/icons/cart-white.svg" alt="">
                                    <span>В корзине</span>
                                </a>';
                        }else{
                            if ($selected_variation){
                                if($variable_status !== 'outofstock'){
                                    woocommerce_template_single_add_to_cart();
                                }
                            }else{
                               woocommerce_template_single_add_to_cart();
                            }
                        }
                        ?>
                    </div>

                    <ol>
                        <li>
                            <span>
                                <img src="<?php bloginfo('template_url') ?>/assets/img/icons/samovyvoz.svg" alt="">
                                Самовывоз:
                            </span>
                            <span>бесплатно</span>
                        </li>
                        <li>
                            <span>
                                <img src="<?php bloginfo('template_url') ?>/assets/img/icons/dostavka-sp.svg" alt="">
                                <a href="/dostavka-i-oplata/" target="_blank">Доставка:</a>
                            </span>
                            <span>по запросу</span>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </section>

    <section class="section single-product__content" data-tabs-area>
        <div class="section__container _container">
            <div class="section__body">
                <div class="tabs">
                    <button data-tab="0" class="_active">Технические характеристики</button>
                    <button data-tab="1">Описание</button>
                    <button data-tab="2">Документы</button>
                </div>
                <div class="flex">
                    <div class="tabs-content">
                        <div data-tab-content="0" class="_active" id="chars">
                            <h2>Технические характеристики</h2>
                            <div class="tabs-content__chars">
                               <?php display_grouped_product_attributes($product, $selected_variation); ?>
                            </div>
                        </div>
                        <div data-tab-content="1">
                            <h2>Описание</h2>
                            <div class="text-box">
                                <?php the_content() ?>
                            </div>
                        </div>
                        <div data-tab-content="2">
                            <h2>Документы</h2>
                            <div class="tabs-content__docs">
                                <?php display_product_documents($product->get_id()) ?>
                            </div>
                        </div>
                    </div>
                    <?php get_template_part('template-parts/ask-form') ?>
                </div>
            </div>
        </div>
    </section>

    <?php get_template_part('template-parts/sections/adv') ?>

    <?php
        $variation_id = $selected_variation ? $selected_variation['variation_id'] : null ;
        get_template_part('template-parts/sections/related', null, ['variation_id' => $variation_id]);
    ?>

    <?php get_template_part('template-parts/sections/request') ?>

</main>