<?php

defined('ABSPATH') || exit;

global $product;

$product_image_id = $product->get_image_id();
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

    <?php get_template_part('template-parts/breadcrumbs') ?>

    <!-- Добавляем данные вариаций -->
    <?php if ($product->is_type('variable')) : ?>
        <div id="variation-data" style="display: none;"
            data-variations='<?php echo htmlspecialchars(json_encode($product->get_available_variations()), ENT_QUOTES, 'UTF-8'); ?>'>
        </div>
    <?php endif; ?>

    <!-- Передаем URL иконок и AJAX -->
    <script>
        window.stockIconUrl = '<?php echo get_template_directory_uri(); ?>/assets/img/icons/stock.svg';
        window.notInStockIconUrl = '<?php echo get_template_directory_uri(); ?>/assets/img/icons/not-in-stock.svg';
        window.wc_add_to_cart_params = {
            ajax_url: '<?php echo admin_url('admin-ajax.php'); ?>'
        };
    </script>

    <section class="section single-product" data-replace-width="768">
        <div class="section__container _container">
            <div class="section__body" data-replace-new-position="afterbegin">
                <div class="single-product__img">
                    <div class="labels">
                        <?php
                        if ($product->is_on_sale()) :
                            // $sale_label = get_product_sale_percentages($product);
                        ?>
                            <label class="sale">Акция</label>
                        <?php endif; ?>
                        <?php if ($time_delta < $new_check) : ?>
                        <?php endif; ?>
                        <label class="hit">Хит</label>
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
                </div>
                <div class="single-product__text" data-replace-old-position="afterbegin">
                    <div class="single-product__title" data-replace-element>
                        <h1><?php the_title() ?></h1>
                        <div class="descr"><?php echo $product->get_short_description() ?></div>
                    </div>

                    <?php if ($product->is_type('variable')) : ?>
                        <form class="variations-form" method="post" enctype="multipart/form-data" data-product_id="<?php echo $product->get_id(); ?>">
                            <div class="grid grid-2 variations">
                                <?php $variations = $product->get_available_variations(); ?>
                                <?php $attributes = $product->get_variation_attributes(); ?>

                                <?php foreach ($attributes as $attribute_name => $options) : ?>
                                    <?php
                                    $attribute_label = wc_attribute_label($attribute_name);
                                    $attribute_id = sanitize_title($attribute_name);
                                    ?>
                                    <div class="var-item">
                                        <p><?php echo $attribute_label; ?></p>
                                        <div class="select-input variation-select" data-attribute="<?php echo esc_attr($attribute_id); ?>">
                                            <label data-id="0"><?php echo esc_html($options[0]); ?></label>
                                            <svg width="12" height="7" viewBox="0 0 12 7">
                                                <use xlink:href='<?php bloginfo('template_url') ?>/assets/img/svg/icons.svg#arrow' />
                                            </svg>
                                            <div class="select">
                                                <div class="select-body">
                                                    <?php foreach ($options as $option) : ?>
                                                        <span data-id="<?php echo esc_attr(sanitize_title($option)); ?>"
                                                            data-value="<?php echo esc_attr($option); ?>">
                                                            <?php echo esc_html($option); ?>
                                                        </span>
                                                    <?php endforeach; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <input type="hidden" name="variation_id" class="variation_id" value="">
                            <input type="hidden" name="product_id" value="<?php echo $product->get_id(); ?>">
                        </form>
                    <?php else : ?>
                    <?php endif; ?>

                    <ol class="chars">
                        <li>
                            <span>
                                <img src="<?php bloginfo('template_url') ?>/assets/img/icons/center.svg" alt="">
                                Центр тяжести:
                            </span>
                            <span>600 мм</span>
                        </li>
                        <li>
                            <span>
                                <img src="<?php bloginfo('template_url') ?>/assets/img/icons/gruz.svg" alt="">
                                Грузоподъемность:
                            </span>
                            <span>1500 кг</span>
                        </li>
                        <li>
                            <span>
                                <img src="<?php bloginfo('template_url') ?>/assets/img/icons/vysota.svg" alt="">
                                Высота подъема:
                            </span>
                            <span>3/10 %</span>
                        </li>
                        <li>
                            <span>
                                <img src="<?php bloginfo('template_url') ?>/assets/img/icons/dlina.svg" alt="">
                                Общая длина:
                            </span>
                            <span>1200 мм</span>
                        </li>
                        <li>
                            <span>
                                <img src="<?php bloginfo('template_url') ?>/assets/img/icons/shirina.svg" alt="">
                                Ширина:
                            </span>
                            <span>800 мм</span>
                        </li>
                        <li>
                            <span>
                                <img src="<?php bloginfo('template_url') ?>/assets/img/icons/weight.svg" alt="">
                                Вес:
                            </span>
                            <span>700 кг</span>
                        </li>
                    </ol>

                    <a href="#chars" class="_btn all-chars">
                        Все характетистики
                        <img src="<?php bloginfo('template_url') ?>/assets/img/icons/down.svg" alt="">
                    </a>
                </div>
                <div class="single-product__addtocart width-397">
                    <div class="single-product__actions">
                        <div class="single-product__stock">
                            <div class="stock">
                                <!-- for simple product -->
                                <?php if ($product->is_in_stock()): ?>
                                    <span>
                                        <img src="<?php bloginfo('template_url') ?>/assets/img/icons/stock.svg" alt="">
                                    </span>
                                    <label>В наличии</label>
                                <?php else: ?>
                                    <span class="not-in-stock">
                                        <img src="<?php bloginfo('template_url') ?>/assets/img/icons/not-in-stock.svg" alt="">
                                    </span>
                                    <label>нет в наличии</label>
                                <?php endif; ?>
                            </div>
                            <div class="product-item__actions">
                                <?php woocommerce_list_button('wishlist'); ?>
                                <?php woocommerce_list_button('compare'); ?>
                            </div>
                        </div>
                        <div class="product-item__price">
                            <?php echo $product->get_price_html() ?>
                        </div>

                        <?php if ($product->is_type('variable')) : ?>
                            <?php
                            do_action('woocommerce_before_add_to_cart_button');
                            ?>
                            <div class="single_variation_wrap">
                                <?php
                                do_action('woocommerce_before_single_variation');
                                do_action('woocommerce_single_variation');
                                do_action('woocommerce_after_single_variation');
                                ?>
                            </div>
                            <?php do_action('woocommerce_after_add_to_cart_button'); ?>
                        <?php else : ?>
                            <?php woocommerce_template_single_add_to_cart(); ?>
                        <?php endif; ?>
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
                                <ol class="chars">
                                    <?php foreach ($product_attributes as  $attr) : ?>
                                        <li><span><?php echo wc_attribute_label($attr->get_name()) ?></span><span><?php echo $product->get_attribute($attr->get_name()) ?></span></li>
                                    <?php endforeach; ?>
                                    <?php
                                    foreach (get_field('chars') as $item) :
                                        if ($item['value']) :
                                    ?>
                                            <li><span><?php echo $item['name'] ?></span><span><?php echo $item['value'] ?></span></li>
                                    <?php
                                        endif;
                                    endforeach;
                                    ?>
                                </ol>
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
                                <a href="" download>
                                    <label>Каталог.pdf</label>
                                    <span>
                                        <svg width="14" height="14" viewBox="0 0 14 14">
                                            <use xlink:href='<?php bloginfo('template_url') ?>/assets/img/svg/icons.svg#download' />
                                        </svg>
                                    </span>
                                </a>
                                <a href="" download>
                                    <label>Сертификат качества.pdf</label>
                                    <span>
                                        <svg width="14" height="14" viewBox="0 0 14 14">
                                            <use xlink:href='<?php bloginfo('template_url') ?>/assets/img/svg/icons.svg#download' />
                                        </svg>
                                    </span>
                                </a>
                                <a href="" download>
                                    <label>Инструкция по эксплуатации.pdf</label>
                                    <span>
                                        <svg width="14" height="14" viewBox="0 0 14 14">
                                            <use xlink:href='<?php bloginfo('template_url') ?>/assets/img/svg/icons.svg#download' />
                                        </svg>
                                    </span>
                                </a>
                                <a href="" download>
                                    <label>Гарантия.pdf</label>
                                    <span>
                                        <svg width="14" height="14" viewBox="0 0 14 14">
                                            <use xlink:href='<?php bloginfo('template_url') ?>/assets/img/svg/icons.svg#download' />
                                        </svg>
                                    </span>
                                </a>
                            </div>
                        </div>
                    </div>
                    <?php get_template_part('template-parts/ask-form') ?>
                </div>
            </div>
        </div>
    </section>

    <?php get_template_part('template-parts/sections/adv') ?>

    <?php get_template_part('template-parts/sections/related') ?>

    <?php get_template_part('template-parts/sections/request') ?>

</main>