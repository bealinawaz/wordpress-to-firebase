<?php
/*
Plugin Name: WP to Firebase
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
            "user_email" => $user->user_email,
            "user_registered" => $user->user_registered,
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
        $wc_orders[] = array(
            "ID" => $order->get_id(),
            "status" => $order->get_status(),
            "total" => $order->get_total(),
            "date_created" => $order->get_date_created()->format('Y-m-d H:i:s'),
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

        $products = get_posts($args);
        $wc_products = array();

        foreach ($products as $product) {
            $product = wc_get_product($product->ID);
            $wc_products[] = array(
                "ID" => $product->get_id(),
                "name" => $product->get_name(),
                "price" => $product->get_price(),
                "stock_status" => $product->get_stock_status(),
                "in_stock" => $product->is_in_stock(),
                "description" => $product->get_description(),
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

    // Initialize cURL
    $curl = curl_init();

    // Set the cURL options
    curl_setopt($curl, CURLOPT_URL, $firebase_database_url . ".json?auth=" . $firebase_api_key);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PATCH");
    curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Content-Length: " . strlen($json_data)
    ));
    // Send the POST request to Firebase
    $response = curl_exec($curl);

    // Check if the request was successful
    if ($response === false) {
        echo "Error: " . curl_error($curl);
    } else {
        // echo "Success: Data pushed to Firebase";
    }

    // Close the cURL session
    curl_close($curl);
}

add_action('init', 'push_data_to_firebase');