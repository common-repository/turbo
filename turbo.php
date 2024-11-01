<?php

/**
 * Plugin Name: Turbo
 * Description: WooCommerce integration for Turbo
 * Author: Turbo
 * Author URI: https://turbo-eg.com/
 * Version: 2.0.0
 * Requires at least: 5.0
 * Requires PHP: 7.0
 * Tested up to: 6.4.2
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */


//  ini_set('display_errors','Off');
//  ini_set('error_reporting', E_ALL );
//  define('WP_DEBUG', false);
//  define('WP_DEBUG_DISPLAY', false);

define('TURBO_ECOMMERCE_VERSION', '2.0.0');

if (defined('TURBO_ECOMMERCE_SANDBOX') && TURBO_ECOMMERCE_SANDBOX) {
    define('TURBO_ECOMMERCE_BASE_URL', TURBO_ECOMMERCE_SANDBOX);
} else {
    define('TURBO_ECOMMERCE_BASE_URL', 'https://backoffice.turbo-eg.com');
}


// Add a second phone for Delivery field to WooCommerce checkout
add_filter('woocommerce_billing_fields', 'add_second_phone_field', 10, 1);
add_filter('woocommerce_shipping_fields', 'add_second_phone_field', 10, 1);
function add_second_phone_field($fields)
{
    $field_name = empty($fields['billing_phone']) ? 'shipping_turbo_phone_2' : 'billing_turbo_phone_2';
    $fields[$field_name] = array(
        'label'       => __('Second Phone for Delivery', 'woocommerce'),
        'required'    => false,
        'class'       => array('form-row-wide'),
        'clear'       => true,
        'priority'    => 101,
    );
    return $fields;
}

// Save second phone for Delivery field data to order
add_action('woocommerce_checkout_create_order', 'save_second_phone_field_to_order', 10, 2);
function save_second_phone_field_to_order($order, $data)
{
    $field_name = empty($data['billing_phone']) ? 'shipping_turbo_phone_2' : 'billing_turbo_phone_2';
    if (isset($_POST[$field_name])) {
        $order->update_meta_data('_' . $field_name, sanitize_text_field($_POST[$field_name]));
        // Save changes
        $order->save();
    }
}

// Update second phone for Delivery field when editing order from admin panel
add_action('woocommerce_process_shop_order_meta', 'update_second_phone_field_on_order_edit', 10, 2);
function update_second_phone_field_on_order_edit($order_id, $post)
{
    $order = wc_get_order($order_id);

    if (isset($_POST['_shipping_turbo_phone_2'])) {
        $order->update_meta_data('_shipping_turbo_phone_2', sanitize_text_field($_POST['_shipping_turbo_phone_2']));
        $order->save();
    }
}

function custom_display_order_data_in_admin($order)
{  ?>
    <div class="form-row-wide">
        <div class="address">
            <?php
            echo '<p><strong>' . __('Second Phone for Delivery') . ':</strong>' . $order->get_meta('_shipping_turbo_phone_2', true) . '</p>';
            ?>
        </div>
        <div class="edit_address">
            <?php woocommerce_wp_text_input(array('id' => '_shipping_turbo_phone_2', 'label' => __('Second Phone for Delivery'), 'wrapper_class' => '_billing_company_field')); ?>
        </div>
    </div>
<?php }

add_action('woocommerce_admin_order_data_after_shipping_address', 'custom_display_order_data_in_admin');

/** replace cities and states edites */

// change woocommerce egypt states
add_filter('woocommerce_states', 'turbo_custom_woocommerce_states');
function turbo_custom_woocommerce_states($states)
{
    $url = TURBO_ECOMMERCE_BASE_URL . '/external-api/get-government';
    $response = wp_remote_get($url);
    if (is_wp_error($response) ||  wp_remote_retrieve_response_code($response) !== 200) {
        $states['EG'] = array(
            '0' => 'يتعذر تحميل المحافظات يرجى المحاولة لاحقا'
        );
        return $states;
    } else {
        $turbo_states = json_decode(wp_remote_retrieve_body($response))->feed;

        $states['EG'] = array();
        foreach ($turbo_states as $state) {
            $states['EG'][$state->id] = $state->name;
        }
        return $states;
    }
}

//change city field to select element in admin side
add_filter('woocommerce_admin_billing_fields', 'turbo_admin_billing_edit');
function turbo_admin_billing_edit($fields)
{
    $order = wc_get_order();
    $selected_billing = $order->get_billing_city();
    $selected_billing_city = explode(':', $selected_billing);
    $selected_billing_city_name = $selected_billing_city[1] ?? '';
    $selected_billing_city_value = $selected_billing_city[0] ?? '';

    if (!$selected_billing_city_name || !$selected_billing_city_value) {
        $option_cities = array(

            '0' => 'اختر مدينة'
        );
    } else {
        $option_cities = array(
            $selected_billing_city_value . ':' . $selected_billing_city_name => $selected_billing_city_name,
            '0' => 'جارٍ تحميل بقية المدن'
        );
    }


    // Set billing city field as select dropdown
    $fields['city']['type'] = 'select';
    $fields['city']['options'] = $option_cities;

    return $fields;
}

add_filter('woocommerce_admin_shipping_fields', 'turbo_admin_shipping_edit');
function turbo_admin_shipping_edit($fields)
{
    $order = wc_get_order();
    $selected_shipping = $order->get_shipping_city();
    $selected_shipping_city = explode(':', $selected_shipping);
    $selected_shipping_city_name = $selected_shipping_city[1] ?? '';
    $selected_shipping_city_value = $selected_shipping_city[0] ?? '';


    if (!$selected_shipping_city_name || !$selected_shipping_city_value) {
        $option_cities = array(

            '0' => 'اختر مدينة'
        );
    } else {

        $option_cities = array(

            $selected_shipping_city_value . ':' . $selected_shipping_city_name => $selected_shipping_city_name,
            '0' => 'جارٍ تحميل بقية المدن'
        );
    }

    // Set billing city field as select dropdown
    $fields['city']['type'] = 'select';
    $fields['city']['options'] = $option_cities;

    return $fields;
}


//change city field to select element in client side

add_filter('woocommerce_billing_fields', 'turbo_client_billing_edit');
function turbo_client_billing_edit($fields)
{
    $option_cities = array(

        "0" => "حدد خياراً"
    );
    // Set billing city field as select dropdown
    $fields['billing_city']['type'] = 'select';
    $fields['billing_city']['options'] =  $option_cities;
    return $fields;
}

add_filter('woocommerce_shipping_fields', 'turbo_client_shipping_edit');
function turbo_client_shipping_edit($fields)
{
    $option_cities = array(

        "0" => "حدد خياراً"
    );
    // Set billing city field as select dropdown
    $fields['shipping_city']['type'] = 'select';
    $fields['shipping_city']['options'] = $option_cities;

    return $fields;
}


// add cities to select element based on data from turbo in admin side 
add_action('admin_enqueue_scripts', 'turbo_admin_side_script');
function turbo_admin_side_script()
{
    if (is_callable('wc_get_order') && $order = wc_get_order()) {
        //cities values
        $selected_billing_city_arr = explode(':', $order->get_billing_city());
        $selected_billing_city_name = $selected_billing_city_arr[1] ?? '';
        $selected_billing_city_value = $selected_billing_city_arr[0] ?? '';
        $selected_shipping_city_arr = explode(':', $order->get_shipping_city());
        $selected_shipping_city_name = $selected_shipping_city_arr[1] ?? '';
        $selected_shipping_city_value = $selected_shipping_city_arr[0] ?? '';
        //states values
        $selected_billing_state_value = $order->get_billing_state();
        $selected_shipping_state_value = $order->get_shipping_state();

        wp_enqueue_script('turbo-admin-side-script', plugin_dir_url(__FILE__) . '/js/admin-side.js', array('jquery'), '1.0', true);
        wp_localize_script('turbo-admin-side-script', 'turbo_admin_side_script_vars', array(
            //pass values to the script file
            'site_url' => get_site_url(),
            'selected_billing_city_name' => $selected_billing_city_name,
            'selected_billing_city_value' => $selected_billing_city_value,
            'selected_shipping_city_name' => $selected_shipping_city_name,
            'selected_shipping_city_value' => $selected_shipping_city_value,
            'selected_billing_state_value' => $selected_billing_state_value,
            'selected_shipping_state_value' => $selected_shipping_state_value,
        ));
    }
}


//add cities to select element based on data from turbo in client side

add_action('wp_enqueue_scripts', 'turbo_custom_client_js_script');
function turbo_custom_client_js_script()
{
    $current_url = $_SERVER['REQUEST_URI'];
    if ((is_checkout() && !is_wc_endpoint_url()) || strpos($current_url, '/billing') !== false || strpos($current_url, '/shipping') !== false) {
        $woo = WC(); //woocommerce object
        $selected_billing_city = $woo->customer->get_billing_city();
        $selected_shipping_city = $woo->customer->get_shipping_city();

        wp_enqueue_script('custom-client-script', plugin_dir_url(__FILE__) . '/js/client-side.js', array('jquery'), '1.0', true);

        wp_localize_script('custom-client-script', 'custom_client_script_vars', array(
            //pass values to the script file
            'site_url' => get_site_url(),
            'selected_billing_city' =>  $selected_billing_city,
            'selected_shipping_city' => $selected_shipping_city,

        ));
    }
}


//endpoint for get areas
add_action('rest_api_init', function () {
    register_rest_route('turbo', '/getareas', array(
        'methods' => 'GET',
        'callback' => 'turbo_get_areas',
        'permission_callback' => '__return_true'
    ));
});

function turbo_get_areas()
{
    $url = TURBO_ECOMMERCE_BASE_URL . '/external-api/get-area/' . $_GET['id'];
    $response = wp_remote_get($url);
    if (is_wp_error($response) ||  wp_remote_retrieve_response_code($response) !== 200) {
        return;
    } else {
        $areas = json_decode(wp_remote_retrieve_body($response));
        return $areas;
    }
}

/**end replace cities and states edites */

//Plugin Settings page
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'turbo_settings_page');

function turbo_settings_page($links)
{
    $links[] = '<a href="' . esc_url(admin_url('admin.php?page=turbo')) . '">' . esc_html__('Settings', 'turbo') . '</a>';

    return $links;
}

//Turbo in side menu
add_action('admin_menu', 'turbo_menu');
function turbo_menu()
{
    add_menu_page(
        'Turbo', // page title
        'Turbo', // menu title
        'manage_options', // permisions
        'turbo', // slug
        'turbo_options_page', // page function
        plugin_dir_url(__FILE__) . '/img/favicon.png', // logo
        56 // menu position
    );
}

//main style sheet
add_action('admin_print_styles', 'turbo_stylesheet');

function turbo_stylesheet()
{
    wp_enqueue_style('turbo_style', plugins_url('/css/main.css', __FILE__));
}

// adding new columns to order details
// For old page without HPOS
add_filter('manage_edit-shop_order_columns', 'turbo_custom_shop_order_column', 20);
// For HPOS
add_action('manage_woocommerce_page_wc-orders_columns', 'turbo_custom_shop_order_column', 20, 2);
function turbo_custom_shop_order_column($columns)
{
    $reordered_columns = array();

    foreach ((array) $columns as $key => $column) {
        $reordered_columns[$key] = $column;
        if ($key ==  'order_status') {
            // Inserting after "Status" column
            $reordered_columns['turbo_order_code'] = __('Turbo Order code', 'theme_domain');
            $reordered_columns['turbo_status'] = __('Turbo Status', 'theme_domain');
            $reordered_columns['turbo_return_reason'] = __('Return reason', 'theme_domain');
            $reordered_columns['turbo_delay_reason'] = __('Delay reason', 'theme_domain');
            $reordered_columns['turbo_date'] = __('turbo date', 'theme_domain');
        }
    }
    return $reordered_columns;
}

// For old page without HPOS
add_action('manage_shop_order_posts_custom_column', 'turbo_custom_orders_list_column_content', 20, 2);
// For HPOS
add_action('manage_woocommerce_page_wc-orders_custom_column', 'turbo_custom_orders_list_column_content', 20, 2);
function turbo_custom_orders_list_column_content($column, $post_id)
{

    $client_statuses = [
        1 => 'محفوظة قبل الشحن',
        2 => 'مرسلة للشحن',
        3 => 'قيد التنفيذ',
        4 => 'مع الكابتن',
        5 => 'مرتجعة مع الشركة',
        6 => 'مرتجعة',
        7 => 'تم التسليم',
        8 => 'تم التوريد',
        9 => 'محذوفة',
        10 => 'مرتجعة معاد إرسالها',
        11 => 'مرتجع مفقود',
        12 => 'مرتجع معدوم',
        13 => 'مؤجلة',
    ];

    if (substr($column, 0, 6) != "turbo_") {
        return;
    }

    $order = wc_get_order($post_id);

    switch ($column) {
        case 'turbo_status':
            $turbo_status = $order->get_meta('turbo_status', true);
            $turbo_status = !empty($turbo_status) ? $turbo_status : '<small>--</small>';
            $turbo_status = isset($client_statuses[$turbo_status]) ? $client_statuses[$turbo_status] : '';
            echo '<span class="turbo_span">' . $turbo_status . '</span>';
            break;
        case 'turbo_order_code':
            $turbo_order_code = $order->get_meta('turbo_order_code', true);
            $turbo_order_code = !empty($turbo_order_code) ? $turbo_order_code : '<small>--</small>';
            echo '<span class="turbo_span">' . $turbo_order_code . '</span>';
            break;
        case 'turbo_return_reason':
            $turbo_return_reason = $order->get_meta('turbo_return_reason', true);
            $turbo_return_reason = !empty($turbo_return_reason) ? $turbo_return_reason : '<small>--</small>';
            echo '<span class="turbo_span">' . $turbo_return_reason . '</span>';
            break;
        case 'turbo_delay_reason':
            $turbo_delay_reason = $order->get_meta('turbo_delay_reason', true);
            $turbo_delay_reason = !empty($turbo_delay_reason) ? $turbo_delay_reason : '<small>--</small>';
            echo '<span class="turbo_span">' . $turbo_delay_reason . '</span>';
            break;
        case 'turbo_date':
            $turbo_date = $order->get_meta('turbo_date', true);
            $turbo_date = !empty($turbo_date) ? $turbo_date : '<small>--</small>';
            echo '<span class="turbo_span">' . $turbo_date . '</span>';
            break;

        default:
            break;
    }
}

// For old page without HPOS
add_filter('bulk_actions-edit-shop_order', 'sync_turbo', 20, 1);
// For HPOS
add_filter('bulk_actions-woocommerce_page_wc-orders', 'sync_turbo', 20, 1);
function sync_turbo($actions)
{
    $actions['sync_to_turbo'] = __('Send To Turbo', 'woocommerce');
    return $actions;
}

// For old page without HPOS
add_filter('handle_bulk_actions-edit-shop_order', 'sync_turbo_handle', 10, 3);
// For HPOS
add_filter('handle_bulk_actions-woocommerce_page_wc-orders', 'sync_turbo_handle', 10, 3);
function sync_turbo_handle($redirect_to, $action, $order_ids)
{
    if ($action != 'sync_to_turbo') {
        return;
    }

    $turboApiKey = get_option('turbo_api_key');
    $turboClientCode = get_option('turbo_client_code');

    if (empty($turboApiKey) || empty($turboClientCode)) {
        $redirect_url = admin_url('admin.php?') . 'page=turbo&error=empty_fields';
        wp_redirect($redirect_url);
        die;
    }

    $args = array(
        'limit' => -1,
        'post__in' => $order_ids,
    );

    $allOrders   = wc_get_orders($args);

    $error = '';
    $message = '';

    foreach ($allOrders as $order) {
        if (empty($order->get_meta('turbo_order_code', true))) {
            $items = $order->get_items();
            $items_quantity = $order->get_item_count();
            $orderSummary  = '';

            foreach ($items as $item_id => $item_data) {
                $product = $item_data->get_product();
                $product_name = $product->get_name();
                $item_quantity = $item_data->get_quantity();
                $orderSummary .= '[' . $item_quantity . ' ' . $product_name . ($items_quantity > 1 ? '-' : '') . '] ';
            }

            // Trim the order summary if it exceeds 400 characters
            //  $summary_length = strlen($orderSummary);
            //     if ($summary_length > 400) {
            //     $orderSummary = substr($orderSummary, 0, 600) . '...';
            //     }

            //get customer name
            $receiver_first_name = $order->get_billing_first_name();
            $receiver_last_name  = $order->get_billing_last_name();
            // Check if shipping customer name exists, use it as priority
            if (!empty($order->get_shipping_first_name()) || !empty($order->get_shipping_last_name())) {
                $receiver_first_name = $order->get_shipping_first_name();
                $receiver_last_name = $order->get_shipping_last_name();
            }
            // get state name
            $states =  turbo_custom_woocommerce_states([]);
            $state = isset($states['EG'][$order->get_billing_state()]) ? $states['EG'][$order->get_billing_state()] : '';
            // Check if shipping state exists, use it as priority
            if (!empty($order->get_shipping_state())) {
                $state = isset($states['EG'][$order->get_shipping_state()]) ? $states['EG'][$order->get_shipping_state()] : '';
            }
            // get city name
            $city_name_arr = explode(":", $order->get_billing_city());
            // Check if shipping city exists, use it as priority
            if (!empty($order->get_shipping_city())) {
                $city_name_arr = explode(":", $order->get_shipping_city());
            }

            //get phone
            $phone1 = $order->get_billing_phone();
            // Check if shipping phone exists, use it as priority
            if (!empty($order->get_shipping_phone())) {
                $phone1 = $order->get_shipping_phone();
            }
            //get address
            $address_1 = $order->get_billing_address_1();
            $address_2 = $order->get_billing_address_2();
            // Check if shipping address exists, use it as priority
            if (!empty($order->get_shipping_address_1()) || !empty($order->get_shipping_address_2())) {
                $address_1 = $order->get_shipping_address_1();
                $address_2 = $order->get_shipping_address_2();
            }

            //new order array
            $newOrder = array();
            $newOrder['remote_order_id'] = $order->get_id();
            $newOrder['quantity'] = $order->get_item_count();
            $newOrder['receiver_email'] = $order->get_billing_email();
            $newOrder['authentication_key'] = $turboApiKey;
            $newOrder['main_client_code']   = $turboClientCode;
            $newOrder['second_client']      = '';
            $newOrder['receiver']   = $receiver_first_name . ' ' . $receiver_last_name;
            $newOrder['phone1'] = $phone1;
            $newOrder['phone2'] = $order->get_meta('_shipping_turbo_phone_2', true);
            $newOrder['api_followup_phone'] = get_option('turbo_api_followup_phone');
            $newOrder['government'] = $state;
            $newOrder['address'] = $address_1 . '\ ' . $address_2;
            $newOrder['area'] = $city_name_arr[1]; // $city after explode;
            $newOrder['notes'] = $order->get_customer_note();
            $newOrder['invoice_number'] = '';
            $newOrder['order_summary'] = $orderSummary;
            $newOrder['delivery_date'] = null;
            $newOrder['delivery_time'] = null;
            $newOrder['amount_to_be_collected'] = $order->get_total();

            $return_amount = $order->get_meta('turbo_return_amount', true);
            if (!strlen($return_amount)) {
                $return_amount = get_option('return_amount_default') ? $order->get_shipping_total() : 0;
            }

            $newOrder['return_amount'] = intval($return_amount);
            $newOrder['is_order'] = 0;
            $newOrder['can_open'] =  get_option('can_open');
            if ($newOrder['can_open'] === false)  $newOrder['can_open'] = "1";
            //send to turbo 
            $response = wp_remote_post(TURBO_ECOMMERCE_BASE_URL . '/external-api/add-order', array(
                'timeout' => 30,
                'method' => 'POST',
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'authorization' => $turboApiKey,
                    'X-Requested-By' => 'WooCommerce',
                ),
                'body' => json_encode($newOrder),
            ));


            $result = json_decode($response['body'], true);

            if ($result['success']) {
                $code = $result['result']['code'];
                $order->update_meta_data('turbo_status', 1);
                $order->update_meta_data('turbo_order_code', $code);
                // Save changes
                $order->save();
                $message = 'sent';
            } else {
                if ($result['error_msg'] == 'Sorry, You do not have permission') {
                    $error = 'error_auth';
                } elseif ($result['error_msg'] == 'main client not found') {
                    $error = 'error_client_code';
                } else {
                    $error = 'error_general';
                }
                $message = $result['error_msg'];
            }
        }
    }

    if (Automattic\WooCommerce\Utilities\OrderUtil::custom_orders_table_usage_is_enabled()) {
        $redirect_url = admin_url('admin.php?') . 'page=wc-orders&paged=1&error=' . $error . '&message=' . $message;
    } else {
        $redirect_url = admin_url('edit.php?') . 'post_type=shop_order&paged=1&error=' . $error . '&message=' . $message;
    }
    wp_redirect($redirect_url);
    die;
}

add_action('woocommerce_after_order_object_save', 'turbo_order_changed', 10, 1);

function turbo_order_changed($order_id)
{
    $order = wc_get_order($order_id);
    $code  = $order->get_meta('turbo_order_code', true);

    if (!empty($code)) {
        $turboApiKey = get_option('turbo_api_key');
        $turboClientCode = get_option('turbo_client_code');

        if (empty($turboApiKey) || empty($turboClientCode)) {
            $redirect_url = admin_url('admin.php?') . 'page=turbo&error=empty_fields';
            wp_redirect($redirect_url);
            die;
        }

        $items = $order->get_items();
        $items_quantity = $order->get_item_count();
        $orderSummary  = '';

        foreach ($items as $item_id => $item_data) {
            $product = $item_data->get_product();
            $product_name = $product->get_name();
            $item_quantity = $item_data->get_quantity();
            $orderSummary .= '[' . $item_quantity . ' ' . $product_name . ($items_quantity > 1 ? '-' : '') . '] ';
        }

        // Trim the order summary if it exceeds 400 characters
        // $summary_length = strlen($orderSummary);
        // if ($summary_length > 400) {
        //     $orderSummary = substr($orderSummary, 0, 600) . '...';
        // }

        //get customer name 
        $receiver_first_name = $_POST['_billing_first_name'] ?? $order->get_shipping_first_name();
        $receiver_last_name  = $_POST['_billing_last_name'] ?? $order->get_shipping_last_name();
        // Check if shipping customer name exists, use it as priority
        if (!empty($_POST['_shipping_first_name']) || !empty($_POST['_shipping_last_name'])) {
            $receiver_first_name = $_POST['_shipping_first_name'];
            $receiver_last_name  = $_POST['_shipping_last_name'];
        }
        //get states
        $states =  turbo_custom_woocommerce_states([]);
        $state = isset($states['EG'][$_POST['_billing_state'] ?? $order->get_billing_state()]) ? $states['EG'][$_POST['_billing_state'] ?? $order->get_billing_state()] : '';
        // Check if shipping state exists, use it as priority
        if (!empty($_POST['_shipping_state'])) {
            $state = isset($states['EG'][$_POST['_shipping_state']]) ? $states['EG'][$_POST['_shipping_state']] : '';
        }
        //split value and get city name
        $city_name_arr = explode(":", $_POST['_billing_city'] ?? $order->get_shipping_city());
        // Check if shipping city exists, use it as priority
        if (!empty($_POST['_shipping_city'])) {
            $city_name_arr = explode(":", $_POST['_shipping_city'] ?? $order->get_shipping_city());
        }
        // get phone
        $phone1 = $_POST['_billing_phone'] ?? $order->get_shipping_phone();
        // Check if shipping phone exists, use it as priority
        if (!empty($_POST['_shipping_phone'])) {
            $phone1 = $_POST['_shipping_phone'] ?: $order->get_shipping_phone();
        }
        //get address 
        $address_1 = $_POST['_billing_address_1'] ?? $order->get_shipping_address_1();
        $address_2 = $_POST['_billing_address_2'] ?? $order->get_shipping_address_2();
        // Check if shipping address exists, use it as priority
        if (!empty($_POST['_shipping_address_1']) || !empty($_POST['_shipping_address_2'])) {
            $address_1 = $_POST['_shipping_address_1'] ?? $order->get_shipping_address_1();
            $address_2 = $_POST['_shipping_address_2'] ?? $order->get_shipping_address_2();
        }
        //new order array
        $newOrder = array();
        $newOrder['code'] = $code;
        $newOrder['remote_order_id'] = $order->get_id();
        $newOrder['quantity'] = $order->get_item_count();
        $newOrder['receiver_email'] = $_POST['_billing_email'] ?? $order->get_billing_email();
        $newOrder['authentication_key'] = $turboApiKey;
        $newOrder['main_client_code']   = $turboClientCode;
        $newOrder['second_client']      = '';
        $newOrder['receiver']   = $receiver_first_name . ' ' . $receiver_last_name;

        $newOrder['phone1'] = $phone1;
        $newOrder['phone2'] = $order->get_meta('_shipping_turbo_phone_2', true);
        $newOrder['api_followup_phone'] = get_option('turbo_api_followup_phone');
        $newOrder['government'] = $state;
        $newOrder['address'] = $address_1 . ' / ' . $address_2;
        $newOrder['area'] = $city_name_arr[1]; //city after expload
        $newOrder['notes'] = $_POST['customer_note'] ?? $order->get_customer_note();
        $newOrder['invoice_number'] = '';
        $newOrder['order_summary'] = $orderSummary;
        $newOrder['delivery_date'] = date('Y-m-d');
        $newOrder['delivery_time'] = '';
        $newOrder['amount_to_be_collected'] = $order->get_total();

        $return_amount = $order->get_meta('turbo_return_amount', true);
        if (!strlen($return_amount)) {
            $return_amount = get_option('return_amount_default') ? $order->get_shipping_total() : 0;
        }

        $newOrder['return_amount'] = intval($return_amount);
        $newOrder['is_order'] = 0;
        $newOrder['can_open'] = get_option('can_open');
        if ($newOrder['can_open'] === false)  $newOrder['can_open'] = "1";
        //send updates to turbo
        $response = wp_remote_post(TURBO_ECOMMERCE_BASE_URL . '/external-api/edit-order', array(
            'timeout' => 30,
            'method' => 'POST',
            'headers' => array(
                'Content-Type' => 'application/json',
                'authorization' => $turboApiKey,
                'X-Requested-By' => 'WooCommerce',
            ),
            'body' => json_encode($newOrder),
        ));

        $result = json_decode($response['body'], true);

        $message = '';
        if ($result['success']) {
            $message = 'sent';
        } else {
            if ($result['error_msg'] == 'Sorry, You do not have permission') {
                $error = 'error_auth';
            } elseif ($result['error_msg'] == 'main client not found') {
                $error = 'error_client_code';
            } else {
                $error = 'error_general';
            }
            $message = $result['error_msg'];
        }
    }
}


//Receive updates from turbo
function turbo_rest_init()
{
    register_rest_route('turbo/v1', '/update-order', array(
        'methods' => 'POST',
        'callback' => 'rest_update_order',
        'permission_callback' => '__return_true'
    ));
}

add_action('rest_api_init', 'turbo_rest_init');
function rest_update_order($data)
{


    $BearerToken = $data->get_header('authorization');
    $token = str_replace('Bearer ', '', $BearerToken);

    if ($token == get_option('your_api_token')) {
        $order_id = $data->get_param('remote_order_id');
        $status = $data->get_param('status');

        if (!empty($order_id)) {
            $order = new WC_Order($order_id);

            $order->update_meta_data('turbo_status', $status);
            $order->update_meta_data('turbo_return_reason', $data->get_param('return_reason'));
            $order->update_meta_data('turbo_delay_reason', $data->get_param('delay_reason'));
            $order->update_meta_data('turbo_date', date('Y-m-d H:i A'));

            // Save changes
            $order->save();

            $order->update_status('wc-turbo_status_' . $status);
        }
    }
}


add_action('init', 'register_turbo_statuses_order_status');
function register_turbo_statuses_order_status()
{
    register_post_status('wc-turbo_status_1', array(
        'label'                     => 'Saved',
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop('Saved (%s)', 'Saved (%s)')
    ));
    register_post_status('wc-turbo_status_2', array(
        'label'                     => 'Sent',
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop('Sent (%s)', 'Sent (%s)')
    ));
    register_post_status('wc-turbo_status_3', array(
        'label'                     => 'Underway',
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop('Underway (%s)', 'Underway (%s)')
    ));
    register_post_status('wc-turbo_status_4', array(
        'label'                     => 'With Captain',
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop('With Captain (%s)', 'With Captain (%s)')
    ));
    register_post_status('wc-turbo_status_5', array(
        'label'                     => 'Returned With Company',
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop('Returned With Company (%s)', 'Returned With Company (%s)')
    ));
    register_post_status('wc-turbo_status_6', array(
        'label'                     => 'Returned',
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop('Returned (%s)', 'Returned (%s)')
    ));
    register_post_status('wc-turbo_status_7', array(
        'label'                     => 'Delivered',
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop('Delivered (%s)', 'Delivered (%s)'),
        'class'                     => 'wc-completed'
    ));
    register_post_status('wc-turbo_status_8', array(
        'label'                     => 'Supplied',
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop('Supplied (%s)', 'Supplied (%s)')
    ));
    register_post_status('wc-turbo_status_9', array(
        'label'                     => 'Archived',
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop('Archived (%s)', 'Archived (%s)')
    ));
    register_post_status('wc-turbo_status_10', array(
        'label'                     => 'Returned',
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop('Returned (%s)', 'Returned (%s)')
    ));
    register_post_status('wc-turbo_status_11', array(
        'label'                     => 'Lost',
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop('Lost (%s)', 'Lost (%s)')
    ));
    register_post_status('wc-turbo_status_12', array(
        'label'                     => 'Absent Returned',
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop('Absent Returned (%s)', 'Absent Returned (%s)')
    ));
    register_post_status('wc-turbo_status_13', array(
        'label'                     => 'Delayed',
        'public'                    => true,
        'show_in_admin_status_list' => true,
        'show_in_admin_all_list'    => true,
        'exclude_from_search'       => false,
        'label_count'               => _n_noop('Delayed (%s)', 'Delayed (%s)')
    ));
}


add_filter('wc_order_statuses', 'add_turbo_statuses_to_order_statuses');
function add_turbo_statuses_to_order_statuses($order_statuses)
{
    $new_order_statuses = array();
    foreach ($order_statuses as $key => $status) {
        $new_order_statuses[$key] = $status;
        if ('wc-on-hold' === $key) {
            $new_order_statuses['wc-turbo_status_1'] = 'Saved';
            $new_order_statuses['wc-turbo_status_2'] = 'Sent';
            $new_order_statuses['wc-turbo_status_3'] = 'Underway';
            $new_order_statuses['wc-turbo_status_4'] = 'With Captain';
            $new_order_statuses['wc-turbo_status_5'] = 'Returned With Company';
            $new_order_statuses['wc-turbo_status_6'] = 'Returned';
            $new_order_statuses['wc-turbo_status_7'] = 'Delivered';
            $new_order_statuses['wc-turbo_status_8'] = 'Supplied';
            $new_order_statuses['wc-turbo_status_9'] = 'Archived';
            $new_order_statuses['wc-turbo_status_10'] = 'Returned';
            $new_order_statuses['wc-turbo_status_11'] = 'Lost';
            $new_order_statuses['wc-turbo_status_12'] = 'Absent Returned';
            $new_order_statuses['wc-turbo_status_13'] = 'Delayed';
        }
    }
    return $new_order_statuses;
}



function wpb_admin_notice_success()
{
    echo '<div class="updated notice">
    <p>Your orders sent to turbo successfully.</p>
    </div>';
}


if (isset($_GET['error']) && $_GET['error'] == 'empty_fields') {
    add_action('admin_notices', 'wpb_admin_notice_error_empty_fields', 10, 3);
}

function wpb_admin_notice_error_empty_fields()
{
    echo '<div class="updated notice error">
    <p>راجع الاعدادات الخاصة بتربو!</p>
    </div>';
}

if (isset($_GET['error']) && $_GET['error'] == 'error_auth') {
    add_action('admin_notices', 'wpb_admin_notice_error_error_auth', 10, 3);
}

function wpb_admin_notice_error_error_auth()
{
    echo '<div class="updated notice error">
    <p>لم يتم ارسال الطرد إلي تربو لأن (المفتاح الرئيسى) خطأ</p>
    </div>';
}

if (isset($_GET['error']) && $_GET['error'] == 'error_client_code') {
    add_action('admin_notices', 'wpb_admin_notice_error_client_code', 10, 3);
}

function wpb_admin_notice_error_client_code()
{
    echo '<div class="updated notice error">
    <p>لم يتم ارسال الطرد إلي تربو لأن (كود الراسل الرئيسى) خطأ</p>
    </div>';
}

if (isset($_GET['error']) && $_GET['error'] == 'error_general') {
    add_action('admin_notices', 'wpb_admin_notice_error_general', 10, 3);
}

function wpb_admin_notice_error_general()
{
    echo '<div class="updated notice error">
    <p>يوجد خطأ ما!</p>
    <p>' . htmlspecialchars($_GET['message']) . '</p>
    </div>';
}

if (isset($_GET['message']) && $_GET['message'] == 'sent') {
    add_action('admin_notices', 'wpb_admin_notice_message_sent', 10, 3);
}

function wpb_admin_notice_message_sent()
{
    echo '<div class="updated notice">
    <p>تم الارسال بنجاح!</p>
    </div>';
}

add_action('admin_init', 'turbo_register_settings');
function turbo_register_settings()
{
    register_setting('turbo_options_group', 'turbo_api_key');
    register_setting('turbo_options_group', 'turbo_client_code');
    register_setting('turbo_options_group', 'turbo_api_followup_phone');
    register_setting('turbo_options_group', 'your_api_token');
    register_setting('turbo_options_group', 'can_open');
    register_setting('turbo_options_group', 'return_amount_default');
}



add_action('woocommerce_thankyou', 'woocommerce_order_custom_fields', 10, 2);
function woocommerce_order_custom_fields($order_id)
{
    update_post_meta($order_id, 'turbo_return_amount', '');
}


function turbo_options_page()
{
    if (!is_plugin_active('woocommerce/woocommerce.php')) {
        // WooCommerce is not installed or not active
        echo '<div class="notice notice-error">
            <p>WooCommerce is not installed or not active.!</p>
            <p>Please install WooCommerce and activate first.</p>
        </div>';
    } elseif (!checkIfCheckoutUsingShortCode()) {
        echo '<div class="notice notice-error">
                <p>Checkout page content is not equal to [woocommerce_checkout] or not exist!</p>
                <p>You may have some problems with Turbo Cities.</p>
            </div>';
    }


?>
    <div class="wrap">
        <h2>Turbo Settings</h2>
        <form method="post" action="options.php">
            <?php
            ob_start();
            settings_fields('turbo_options_group');
            $output = ob_get_clean();
            $output = str_replace('error=empty_fields', '', $output);
            echo $output;
            ?>
            <?php do_settings_sections('turbo_options_group'); ?>

            <table class="form-table">
                <tr>
                    <th><label for="turbo_api_key">Turbo api key:</label></th>
                    <td>
                        <input type='text' class="regular-text" id="turbo_api_key" name="turbo_api_key" value="<?php echo get_option('turbo_api_key'); ?>" style="<?php echo empty(get_option('turbo_api_key')) ? 'border: 1px solid red' : ''; ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="turbo_client_code">Turbo client code:</label></th>
                    <td>
                        <input type='number' class="regular-text" id="turbo_client_code" name="turbo_client_code" value="<?php echo get_option('turbo_client_code'); ?>" style="<?php echo empty(get_option('turbo_client_code')) ? 'border: 1px solid red' : ''; ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="turbo_api_followup_phone">Turbo api followup phone:</label></th>
                    <td>
                        <input type='number' class="regular-text" id="turbo_api_followup_phone" name="turbo_api_followup_phone" value="<?php echo get_option('turbo_api_followup_phone'); ?>">
                    </td>
                </tr>
                <tr>
                    <th><label for="your_api_token">Your api token(optional):</label></th>
                    <td>
                        <input type='text' class="regular-text" id="your_api_token" name="your_api_token" value="<?php echo get_option('your_api_token'); ?>">
                        <br>
                        <small class="alert-danger">You can use any random string, but the same string should be entered on Turbo portal.</small>
                        <br>
                        <small class="alert-danger">This field is working as a password for your API to protect your API calls.</small>
                        <br>
                        <button type="button" class="button wp-generate-pw hide-if-no-js" id="generate_token">Generate Random String</button>
                        <button type="button" class="button wp-generate-pw hide-if-no-js" id="clear_token">Clear Token</button>
                    </td>
                </tr>
                <tr>
                    <th><label for="your_api_url">Your api url:</label></th>
                    <td>
                        <input type='text' class="regular-text" id="your_api_url" readonly value="<?php echo site_url(); ?>/wp-json/turbo/v1/update-order">
                    </td>
                </tr>
                <tr>
                    <th><label for="can_open">Can Open the shipment?</label></th>
                    <td>
                        <select class="regular-text" name="can_open" id="can_open">
                            <option value="1" <?php selected(get_option('can_open'), 1); ?>>Yes</option>
                            <option value="0" <?php selected(get_option('can_open'), 0); ?>>No</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <th><label for="return_amount_default">Return Amount Should be as Shipping value if not defined?(قيمة الارتجاع)</label></th>
                    <td>
                        <select class="regular-text" name="return_amount_default" id="return_amount_default">
                            <option value="0" <?php selected(get_option('return_amount_default'), 0); ?>>No</option>
                            <option value="1" <?php selected(get_option('return_amount_default'), 1); ?>>Yes</option>
                        </select>
                    </td>
                </tr>
            </table>

            <?php submit_button(); ?>

            <script>
                document.addEventListener("DOMContentLoaded", function() {
                    document.getElementById("generate_token").addEventListener("click", function() {
                        var randomToken = Math.random().toString(36).substr(2);
                        document.getElementById("your_api_token").value = randomToken;
                    });

                    document.getElementById("clear_token").addEventListener("click", function() {
                        document.getElementById("your_api_token").value = '';
                    });
                });
            </script>

    </div>
<?php }

function checkIfCheckoutUsingShortCode()
{
    // Get the checkout page ID from WooCommerce settings
    $checkout_page_id = get_option('woocommerce_checkout_page_id');

    // Check if the checkout page ID is set
    if ($checkout_page_id) {
        // Get the content of the checkout page
        $checkout_page = get_post($checkout_page_id);

        // Check if the page is found
        if ($checkout_page) {
            // Get the content of the checkout page
            $checkout_content =  $checkout_page->post_content;

            if (strpos($checkout_content, '[woocommerce_checkout]') !== false) {
                // admin alert
                return true;
            }
        }
    }

    return false;
}
