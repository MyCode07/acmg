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

$load_capacity = $product->get_attribute('pa_gruzopodemnost-kg');
$lift_height = $product->get_attribute('pa_vysota-podema-m');

$is_in_cart = is_product_in_cart($product->get_id());
$product_url = $product->get_permalink();
$product_title = $product->get_title();

?>
<div class="product-item" data-id="<?php echo $product->get_id() ?>">
    <a href="<?php echo $product_url; ?>" class="product-item__img">
        <?php
            echo $product->get_image('full');
        ?>
    </a>
    <div class="product-item__actions">
        <?php woocommerce_list_button('wishlist'); ?>
        <?php woocommerce_list_button('compare'); ?>
    </div>
    <div>
        <h4><a href="<?php echo $product_url; ?>"><?php echo $product_title; ?></a></h4>
        <p><?php echo $product->get_short_description() ?></p>
        <div class="product-item__data">
            <?php if($load_capacity): ?>
                <span><img src="<?php bloginfo('template_url') ?>/assets/img/icons/weight.svg" alt=""><?php echo $load_capacity; ?> кг</span>
            <?php endif; ?>
            <?php if($lift_height): ?>
                <span><img src="<?php bloginfo('template_url') ?>/assets/img/icons/up-fill.svg" alt=""><?php echo $lift_height; ?> м</span>
            <?php endif; ?>
        </div>
    </div>
    <div>
        <div class="product-item__price">
            <?php echo $product->get_price_html(); ?>
        </div>

        <?php
        $product_type = $product->get_type();
        echo $product_type;
        if ($product->is_type('simple') || $is_variation){
            echo 'yes is simple';
        }
        ?>

        <?php if ($is_in_cart) : ?>
            <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="_btn _btn-accent-dark">
                <img src="<?php bloginfo('template_url') ?>/assets/img/icons/cart-white.svg" alt="">
                <span>В корзине</span>
            </a>
        <?php else : ?>
            <?php
                if ($product->is_type('simple')) :
                    do_action('woocommerce_after_shop_loop_item');
                else:
            ?>
                <a href="?add-to-cart=<?php echo $product->get_id(); ?>" class="product_type_simple add_to_cart_button ajax_add_to_cart _btn _btn-accent-dark" data-product_id="<?php echo $product->get_id(); ?>" data-quantity="1">
                    <img src="<?php bloginfo('template_url') ?>/assets/img/icons/cart-white.svg" alt="">
                    <span class="_desktop">Добавить в корзину</span>
                    <span class="_mobile">В корзину</span>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <?php
            if(is_page( 60 )) {
                get_product_compare_data($product);
            }
        ?>
    </div>
</div>