<?php

function get_api_token() {
    // The URL to the Amrod API endpoint for getting a token
    $api_url = 'https://identity.amrod.co.za/VendorLogin';

    $credentials = array(
        'UserName' => 'AMROD_USERNAME',
        'Password' => 'AMROD_PASSWORD',
        'CustomerCode' => 'AMROD_CUSTOMER_CODE',
    );

    // Sending a POST request to the Amrod API
    $response = wp_remote_post(
        $api_url,
        array(
            'headers' => array(
                'Content-Type' => 'application/json',
            ),
            'body' => json_encode($credentials),
        )
    );

    // To check if the request was successful
    if (is_wp_error($response)) {
        // Handling error
        error_log('Error fetching API token from Amrod: ' . $response->get_error_message());
        return false;
    } else {
        // Decoding the response body
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Checking if the token key is in the response
        if (isset($data['token'])) {
            return $data['token'];
        } else {
            // Logging an error if the token key is not found in the response
            error_log('Error: API token not found in the response');
            return false;
        }
    }
}

// Adding the action hook to trigger the function on every page load
add_action('wp_loaded', 'trigger_function_on_every_page');

function trigger_function_on_every_page() {
    // Registering the shortcode to display Amrod products
    add_shortcode('amrod_products', 'display_amrod_products_shortcode');
}

function display_amrod_products_shortcode() {
    // Calling the function to get the Amrod API token
    $api_token = get_api_token();

    // Using $api_token to fetch and display products
    if ($api_token) {
        $products = fetch_amrod_products($api_token);

        ob_start();
        if ($products) {
            echo '<h2>Products from Amrod</h2>';
            echo '<ul>';
            foreach ($products as $product) {
                echo '<li>';
                echo '<strong>' . esc_html($product['name']) . '</strong><br>';
                // Add more product information as needed below
                echo '</li>';
            }
            echo '</ul>';
        } else {
            echo 'Error: Unable to fetch products.';
        }
        $output = ob_get_clean();
        return $output;
    } else {
        return 'Error: Unable to retrieve or refresh the API token.';
    }
}

// Function to fetch branding prices
function fetch_amrod_products($api_token) {
    $api_url = 'https://vendorapi.amrod.co.za/api/v1/GetAllProducts';

    $response = wp_remote_get(
        $api_url,
        array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_token,
            ),
        )
    );

    if (is_wp_error($response)) {
        // Handling error
        error_log('Error fetching products from Amrod: ' . $response->get_error_message());
        return false;
    } else {
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if (isset($data['products'])) {
            return $data['products'];
        } else {
            // Logging an error if the products key is not found in the response
            error_log('Error: Amrod products not found in the response');
            return false;
        }
    }
}

// ... (other functions remain unchanged)

function log_api_request($api_token, $method, $endpoint) {
    // Logging the API request details
    $log_message = sprintf(
        'API Request - Method: %s, Endpoint: %s, Token: %s',
        $method,
        $endpoint,
        $api_token
    );

    // Logging to a file or other logging mechanism
    error_log($log_message);
}

function the_content_filter($content) {
    return do_shortcode($content);
}
add_filter('the_content', 'the_content_filter', 1000);
?>
