<?php
/*
Plugin Name: WPW to Firebase
Description: Sends WooCommerce users and orders data to Firebase
Version: 1.0
Author: Ali Nawaz
*/
if (!defined('ABSPATH')) {
    exit;
}

add_action( 'admin_menu', 'wp_to_firebase_menu' );

function wp_to_firebase_menu() {
    add_menu_page( 'WP to Firebase', 'WP to Firebase', 'manage_options', 'wp-to-firebase', 'wp_to_firebase_options' );
}

function wp_to_firebase_options() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
        <form action="options.php" method="post">
            <?php
            settings_fields( 'wp_to_firebase_options' );
            do_settings_sections( 'wp-to-firebase' );
            submit_button();
            ?>
        </form>
        <?php
        if ( isset( $_GET['settings-updated'] ) ) {
            ?>
            <div class="notice notice-success is-dismissible">
                <p><strong>Changes saved.</strong></p>
            </div>
            <?php
        }
        ?>
    </div>
    <?php
}

add_action( 'admin_init', 'wp_to_firebase_settings' );

function wp_to_firebase_settings() {
    register_setting( 'wp_to_firebase_options', 'wp_to_firebase_database_url' );
    register_setting( 'wp_to_firebase_options', 'wp_to_firebase_api_key' );
    add_settings_section( 'wp_to_firebase_section', 'Firebase Connection Settings', 'wp_to_firebase_section_callback', 'wp-to-firebase' );
    add_settings_field( 'wp_to_firebase_database_url', 'Database URL', 'wp_to_firebase_database_url_callback', 'wp-to-firebase', 'wp_to_firebase_section' );
    add_settings_field( 'wp_to_firebase_api_key', 'API Key', 'wp_to_firebase_api_key_callback', 'wp-to-firebase', 'wp_to_firebase_section' );
}

function wp_to_firebase_section_callback() {
    echo 'Enter your Firebase connection settings:';
}

// Retrieve the saved

function wp_to_firebase_database_url_callback() {
    $database_url = get_option( 'wp_to_firebase_database_url' );
    echo '<input type="text" name="wp_to_firebase_database_url" value="' . esc_attr( $database_url ) . '" size="60" />';
}

function wp_to_firebase_api_key_callback() {
    $api_key = get_option( 'wp_to_firebase_api_key' );
    echo '<input type="text" name="wp_to_firebase_api_key" value="' . esc_attr( $api_key ) . '" size="60" />';
}


// Function to get all WordPress users
function get_all_wp_users() {
    $users = get_users();
    $wp_users = array();

    foreach ($users as $user) {
        $wp_users[] = array(
            "ID" => $user->ID,
            "user_login" => $user->user_login,
            "user_pass" => $user->user_pass,
            "user_nicename" => $user->user_nicename,
            "user_email" => $user->user_email,
            "user_url" => $user->user_url,
            "user_registered" => $user->user_registered,
            "user_activation_key" => $user->user_activation_key,
            "user_status" => $user->user_status,
            "display_name" => $user->display_name,
            "nickname" => $user->nickname,
            "first_name" => $user->first_name,
            "last_name" => $user->last_name,
            "description" => $user->description,
            "rich_editing" => $user->rich_editing,
            "comment_shortcuts" => $user->comment_shortcuts,
            "admin_color" => $user->admin_color,
            "use_ssl" => $user->use_ssl,
            "user_level" => $user->user_level,
            "user_firstname" => $user->user_firstname,
            "user_lastname" => $user->user_lastname,
            "user_description" => $user->user_description,
            "user_avatar" => $user->user_avatar,
        );
    }

    return $wp_users;
}

// Function to get all WooCommerce orders
function get_all_wc_orders() {
    $args = array(
        'post_type' => 'shop_order',
        'post_status' => 'wc-completed',
        'posts_per_page' => -1,
    );

    $orders = get_posts($args);
    $wc_orders = array();

    foreach ($orders as $order) {
        $order = wc_get_order($order->ID);
        $uploaded_video_url = get_post_meta( $order->get_id(), '_video_file', true );

        $shipping_items = array();
        foreach ($order->get_shipping_methods() as $shipping_item) {
            $shipping_items[] = array(
                "name" => $shipping_item->get_name(),
                "method_id" => $shipping_item->get_method_id(),
                "total" => $shipping_item->get_total(),
                "taxes" => $shipping_item->get_taxes(),
                "meta_data" => $shipping_item->get_meta_data(),
                "instance_id" => $shipping_item->get_instance_id(),
                "status" => $shipping_item->get_status(),
            );
        }

        $wc_orders[] = array(
            "ID" => $order->get_id(),
            "status" => $order->get_status(),
            "total" => $order->get_total(),
            "date_created" => $order->get_date_created()->format('Y-m-d H:i:s'),
            "billing" => array(
                "first_name" => $order->get_billing_first_name(),
                "last_name" => $order->get_billing_last_name(),
                "company" => $order->get_billing_company(),
                "address_1" => $order->get_billing_address_1(),
                "address_2" => $order->get_billing_address_2(),
                "city" => $order->get_billing_city(),
                "state" => $order->get_billing_state(),
                "postcode" => $order->get_billing_postcode(),
                "country" => $order->get_billing_country(),
                "email" => $order->get_billing_email(),
                "phone" => $order->get_billing_phone(),
            ),
            "shipping" => array(
                "first_name" => $order->get_shipping_first_name(),
                "last_name" => $order->get_shipping_last_name(),
                "company" => $order->get_shipping_company(),
                "address_1" => $order->get_shipping_address_1(),
                "address_2" => $order->get_shipping_address_2(),
                "city" => $order->get_shipping_city(),
                "state" => $order->get_shipping_state(),
                "postcode" => $order->get_shipping_postcode(),
                "country" => $order->get_shipping_country(),
                "method" => $shipping_items,
            ),
            "payment_method" => $order->get_payment_method_title(),
            "payment_method_id" => $order->get_payment_method(),
            "customer_note" => $order->get_customer_note(),
            "uploaded_video_url" => $uploaded_video_url
        );
    }

    return $wc_orders;
}



function get_all_wc_products() {
    $args = array(
        'post_type' => 'product',
        'post_status' => 'publish',
        'posts_per_page' => -1,
    );

    $products = get_posts( $args );
    $wc_products = array();

    foreach ( $products as $product ) {
        $product = wc_get_product( $product->ID );
        $wc_products[] = array(
            'ID' => $product->get_id(),
            'name' => $product->get_name(),
            'slug' => $product->get_slug(),
            'date_created' => $product->get_date_created()->getTimestamp(),
            'date_modified' => $product->get_date_modified()->getTimestamp(),
            'status' => $product->get_status(),
            'featured' => $product->is_featured(),
            'catalog_visibility' => $product->get_catalog_visibility(),
            'description' => $product->get_description(),
            'short_description' => $product->get_short_description(),
            'sku' => $product->get_sku(),
            'price' => $product->get_price(),
            'regular_price' => $product->get_regular_price(),
            'sale_price' => $product->get_sale_price(),
            'date_on_sale_from' => $product->get_date_on_sale_from() ? $product->get_date_on_sale_from()->getTimestamp() : '',
            'date_on_sale_to' => $product->get_date_on_sale_to() ? $product->get_date_on_sale_to()->getTimestamp() : '',
            'total_sales' => $product->get_total_sales(),
            'tax_status' => $product->get_tax_status(),
            'tax_class' => $product->get_tax_class(),
            'manage_stock' => $product->managing_stock(),
            'stock_quantity' => $product->get_stock_quantity(),
            'in_stock' => $product->is_in_stock(),
            'backorders_allowed' => $product->backorders_allowed(),
            'backordered' => $product->is_on_backorder(),
            'sold_individually' => $product->is_sold_individually(),
            'weight' => $product->get_weight(),
            'length' => $product->get_length(),
            'width' => $product->get_width(),
            'height' => $product->get_height(),
            'reviews_allowed' => $product->get_reviews_allowed(),
            'average_rating' => $product->get_average_rating(),
            'rating_count' => $product->get_rating_count(),
            'related_ids' => $product->get_related(),
            'upsell_ids' => $product->get_upsells(),
            'cross_sell_ids' => $product->get_cross_sells(),
            'parent_id' => $product->get_parent_id(),
            'categories' => wp_get_post_terms( $product->get_id(), 'product_cat', array( 'fields' => 'names' ) ),
            'tags' => wp_get_post_terms( $product->get_id(), 'product_tag', array( 'fields' => 'names' ) ),
            'images' => array_map( function( $image ) {
                $image_arr = array(
                    'id' => $image->get_id(),
                    'src' => $image->get_src(),
                    'alt' => $image->get_alt(),
                    'title' => $image->get_title(),
                );
                if ( $image instanceof WC_Product_Variation ) {
                    $image_arr['variation_id'] = $image->get_variation_id();
                }
                return $image_arr;
            }, $product->get_gallery_image_ids()
        ),
        'attributes' => array_map( function( $attribute ) {
                $attribute_arr = array(
                    'name' => $attribute['name'],
                    'value' => $attribute['value'],
                    'position' => $attribute['position'],
                    'is_visible' => $attribute['is_visible'] ? true : false,
                    'is_variation' => $attribute['is_variation'] ? true : false,
                );
                return $attribute_arr;
            }, $product->get_attributes()
        ),
        'default_attributes' => array_map( function( $attribute ) {
                $attribute_arr = array(
                    'name' => $attribute['name'],
                    'value' => $attribute['value'],
                );
                return $attribute_arr;
            }, $product->get_default_attributes()
        ),
        'related_products' => $product->get_related(),
        'upsell_products' => $product->get_upsell_ids(),
        'cross_sell_products' => $product->get_cross_sell_ids(),
    );
    }
    return $wc_products;
}
// Define the function to push data to Firebase
function push_data_to_firebase() {
    // Define Firebase database URL and API key
    $firebase_database_url = get_option( 'wp_to_firebase_database_url' );
    $firebase_api_key = get_option( 'wp_to_firebase_api_key' );

    // Get the WordPress and WooCommerce data
    $wp_users = get_all_wp_users();
    $wc_orders = get_all_wc_orders();
    $wc_products = get_all_wc_products();

    // Define the data you want to push to Firebase
    $data = array(
        "wp_users" => $wp_users,
        "wc_orders" => $wc_orders,
        "wc_products" => $wc_products,
    );

    // Convert the data to JSON format
    $json_data = json_encode($data);

    // Set up the arguments for the API request
    $args = array(
        'body' => $json_data,
        'headers' => array(
            'Content-Type' => 'application/json',
            'Authorization' => 'Basic ' . base64_encode( 'ignored:' . $firebase_api_key ),
        ),
        'timeout' => '5',
    );

    // Check if the data already exists in Firebase
    $response = wp_remote_get($firebase_database_url . ".json?auth=" . $firebase_api_key);
    $existing_data = json_decode(wp_remote_retrieve_body($response), true);
    if (isset($existing_data) && is_array($existing_data)) {
        $existing_data = array_merge_recursive($existing_data, $data);
        $json_data = json_encode($existing_data);
        $args['method'] = 'PUT';
    }

    // Send the data to Firebase using the wp_remote_post() function
    $response = wp_remote_post($firebase_database_url . ".json?auth=" . $firebase_api_key, $args);

    // Check for errors and log the response
    if ( is_wp_error( $response ) ) {
        error_log( 'Error sending data to Firebase: ' . $response->get_error_message() );
    } else {
        error_log( 'Firebase response: ' . wp_json_encode( $response ) );
    }
}




add_action('init', 'push_data_to_firebase');