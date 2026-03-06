<?php
defined('ABSPATH') || exit;

global $product;

// Ensure visibility.
if (empty($product) || !$product->is_visible()) {
    return;
}

$datetime_created = $product->get_date_created();
$timestamp_created = $datetime_created->getTimestamp();
$datetime_now = new WC_DateTime();
$timestamp_now = $datetime_now->getTimestamp();
$time_delta = $timestamp_now - $timestamp_created;
$days = 5;
$new_check = $days * (24 * 60 * 60);

$is_in_cart = false;
$cart_item_key = '';

// Проверяем, есть ли товар в корзине (для простых и вариативных)
$product_id = $product->get_id();
foreach (WC()->cart->get_cart() as $item_key => $cart_item) {
    // Для простых товаров проверяем по product_id
    if ($cart_item['product_id'] == $product_id) {
        $is_in_cart = true;
        $cart_item_key = $item_key;
        break;
    }
    // Для вариативных также проверяем по product_id (родительский товар)
    if (isset($cart_item['variation_id']) && $cart_item['variation_id'] > 0) {
        $variation_product = wc_get_product($cart_item['variation_id']);
        if ($variation_product && $variation_product->get_parent_id() == $product_id) {
            $is_in_cart = true;
            $cart_item_key = $item_key;
            break;
        }
    }
}

?>
<div class="product-item <?php echo $product->is_type('variable') ? 'product-item__variable' : '' ?>">
    <a href="<?php echo $product->get_permalink() ?>" class="product-item__img">
        <?php echo $product->get_image('full') ?>
    </a>
    <div class="product-item__actions">
        <?php woocommerce_list_button('wishlist'); ?>
        <?php woocommerce_list_button('compare'); ?>
    </div>
    <div>
        <h4><a href="<?php echo $product->get_permalink() ?>"><?php echo $product->get_title() ?></a></h4>
        <p>Электрический штабелер с раздвижными вилами</p>
        <div class="product-item__data">
            <span><img src="<?php bloginfo('template_url') ?>/assets/img/icons/weight.svg" alt="">1000 кг</span>
            <span><img src="<?php bloginfo('template_url') ?>/assets/img/icons/up-fill.svg" alt="">1.6 м</span>
        </div>
    </div>
    <div>
        <div class="product-item__price">
            <?php echo $product->get_price_html() ?>
        </div>

        <?php if ($product->is_type('simple')) : ?>
            <?php do_action('woocommerce_after_shop_loop_item') ?>
        <?php else : ?>
            <?php if ($is_in_cart) : ?>
                <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="_btn _btn-accent-dark">
                    <img src="<?php bloginfo('template_url') ?>/assets/img/icons/cart-white.svg" alt="">
                    <span>В корзине</span>
                </a>
            <?php else : ?>
                <a href="<?php echo esc_url(get_permalink($product->get_id())); ?>" class="_btn _btn-accent-dark">Выбрать параметры</a>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</div>