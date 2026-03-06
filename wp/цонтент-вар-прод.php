<?php
defined('ABSPATH') || exit;

global $product;

// Ensure visibility.
if (empty($product) || !$product->is_visible()) {
    return;
}

if ($product->is_type('variable')) :
    $variations = get_product_all_variations($product);
    foreach ($variations as $variation_data) :
        $variation = wc_get_product($variation_data['variation_id']);
        if ($variation && $variation->is_purchasable()) :
            display_product_card($product, true, $variation);
        endif;
    endforeach;
else :
    // Для простых товаров отображаем одну карточку
    // страницы wishlist и comapre
    if(is_page(58) || is_page(60)){
        display_product_card($product, true, $variation);
    }else{
        display_product_card($product);
    }
endif;