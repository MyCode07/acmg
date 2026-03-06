// Функция для отображения карточки товара
function display_product_card($product, $is_variation = false, $variation = null) {
    $current_product = $is_variation ? $variation : $product;
    $parent_product = $is_variation ? $product : null;

    // Получаем значения атрибутов
    $load_capacity = $product->get_attribute('pa_gruzopodemnost-kg');
    $lift_height = $product->get_attribute('pa_vysota-podema-m');

    $is_in_cart = is_product_in_cart($current_product->get_id());
    $product_url = $current_product->get_permalink();
    $product_title = $current_product->get_title();

    // Для вариаций добавляем атрибуты к названию

    if ($is_variation && $variation) {
        $attributes = $variation->get_attributes();
        foreach ($attributes as $attribute_name => $attribute_value) {
            if ($attribute_value) {
                $product_title .= ' - ' . $attribute_value;
            }
        }
    }

    $additional_classes = $product->is_type('variable') ? 'product-item__variable' : '';
    $additional_classes .= $is_variation ? ' variation-item' : '';
?>
<div class="product-item <?php echo $additional_classes; ?>" <?php echo $is_in_cart ? 'data-in-cart' : 'data-no-in-cart' ?> <?php echo $is_variation ? 'data-variation-id="' . $variation->get_id() . '"' : ''; ?>>
    <a href="<?php echo $product_url; ?>" class="product-item__img">
        <?php
        if ($is_variation && $variation) {
            echo $variation->get_image('full') ? $variation->get_image('full') : $product->get_image('full');
        } else {
            echo $product->get_image('full');
        }
        ?>
    </a>
    <div class="product-item__actions">
        <?php woocommerce_list_button('wishlist',$current_product->get_id()); ?>
        <?php woocommerce_list_button('compare',$current_product->get_id()); ?>
    </div>
    <div>
        <h4><a href="<?php echo $product_url; ?>"><?php echo $product_title; ?></a></h4>
        <p><?php echo $product->get_short_description() ?></p>

        <?php if ($is_variation && $variation): ?>
            <div class="product-item__data">
                <?php foreach ($variation->get_attributes() as $attribute_name => $attribute_value): ?>
                    <?php if ($attribute_value): ?>
                        <span class="attribute-badge">
                            <?php if($attribute_name == 'pa_gruzopodemnost-kg'): ?>
                                <img src="<?php bloginfo('template_url') ?>/assets/img/icons/weight.svg" alt="">
                            <?php endif; ?>
                            <?php if($attribute_name == 'pa_vysota-podema-m'): ?>
                                <img src="<?php bloginfo('template_url') ?>/assets/img/icons/up-fill.svg" alt="">
                            <?php endif; ?>
                            <?php
                            echo $attribute_value;
                            if($attribute_name == 'pa_gruzopodemnost-kg') echo ' кг';
                            if($attribute_name == 'pa_vysota-podema-m') echo ' м';
                            ?>
                        </span>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="product-item__data">
                <?php if($load_capacity): ?>
                    <span><img src="<?php bloginfo('template_url') ?>/assets/img/icons/weight.svg" alt=""><?php echo $load_capacity; ?> кг</span>
                <?php endif; ?>
                <?php if($lift_height): ?>
                    <span><img src="<?php bloginfo('template_url') ?>/assets/img/icons/up-fill.svg" alt=""><?php echo $lift_height; ?> м</span>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
    <div>
        <div class="product-item__price">
            <?php echo $current_product->get_price_html(); ?>
        </div>

        <?php if ($is_in_cart) : ?>
            <a href="<?php echo esc_url(wc_get_cart_url()); ?>" class="_btn _btn-accent-dark">
                <img src="<?php bloginfo('template_url') ?>/assets/img/icons/cart-white.svg" alt="">
                <span>В корзине</span>
            </a>
        <?php else : ?>
            <?php
                if ($product->is_type('simple') && !$is_variation) {
                    do_action('woocommerce_after_shop_loop_item');
                }
                if ($product->is_type('variable') || $is_variation) :
            ?>
                <a href="?add-to-cart=<?php echo $variation->get_id(); ?>" class="product_type_simple add_to_cart_button ajax_add_to_cart _btn _btn-accent-dark" data-product_id="<?php echo $variation->get_id(); ?>" data-quantity="1">
                    <img src="<?php bloginfo('template_url') ?>/assets/img/icons/cart-white.svg" alt="">
                    <span class="_desktop">Добавить в корзину</span>
                    <span class="_mobile">В корзину</span>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <?php
            if(is_page( 60 )) {
                get_product_compare_data($current_product);
            }
        ?>
    </div>
</div>
<?php }