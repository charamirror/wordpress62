<?php
/**
 * Storefront engine room
 *
 * @package storefront
 */

/**
 * Assign the Storefront version to a var
 */
$theme              = wp_get_theme( 'storefront' );
$storefront_version = $theme['Version'];

/**
 * Set the content width based on the theme's design and stylesheet.
 */
if ( ! isset( $content_width ) ) {
	$content_width = 980; /* pixels */
}

$storefront = (object) array(
	'version'    => $storefront_version,

	/**
	 * Initialize all the things.
	 */
	'main'       => require 'inc/class-storefront.php',
	'customizer' => require 'inc/customizer/class-storefront-customizer.php',
);

require 'inc/storefront-functions.php';
require 'inc/storefront-template-hooks.php';
require 'inc/storefront-template-functions.php';
require 'inc/wordpress-shims.php';


// Add custom search filter
add_filter('pre_get_posts', 'custom_search_filter');

function custom_search_filter($query) {
    // Check if this is the main query and the query is for the search page
    if ($query->is_main_query() && $query->is_search()) {
        // Modify the query to search only in the 'books' post type
        $query->set('post_type', 'books');
    }
}


// Add custom search endpoint
add_action('init', 'register_custom_endpoint');

function register_custom_endpoint() {
    add_rewrite_rule('^search-books/?', 'index.php?custom_search=1', 'top');
    add_rewrite_tag('%custom_search%', '([^&]+)');
}


// Handle custom search endpoint
add_action('parse_request', 'handle_custom_endpoint');

function handle_custom_endpoint($wp) {
    if (isset($wp->query_vars['custom_search'])) {
        // Handle the search query
        $search_query = isset($_GET['q']) ? sanitize_text_field($_GET['q']) : '';

        // Fetch data from the SQL database
        global $wpdb;
        $results = $wpdb->get_results("SELECT * FROM books WHERE title LIKE '%$search_query%' OR author LIKE '%$search_query%'");

        // Render the search results
        if (!empty($results)) {
            echo '<ul>';
            foreach ($results as $result) {
                echo '<li>' . $result->title . ' by ' . $result->author . '</li>';
            }
            echo '</ul>';
        } else {
            echo '<p>No results found.</p>';
        }
        exit;
    }
}

function get_registered_post_types() {
    $post_types = get_post_types();
    foreach ( $post_types as $post_type ) {
        $post_type_obj = get_post_type_object( $post_type );
        echo $post_type . ': ' . $post_type_obj->label . '<br>';
    }
}
add_action( 'wp_footer', 'get_registered_post_types' );



if ( class_exists( 'Jetpack' ) ) {
	$storefront->jetpack = require 'inc/jetpack/class-storefront-jetpack.php';
}

if ( storefront_is_woocommerce_activated() ) {
	$storefront->woocommerce            = require 'inc/woocommerce/class-storefront-woocommerce.php';
	$storefront->woocommerce_customizer = require 'inc/woocommerce/class-storefront-woocommerce-customizer.php';

	require 'inc/woocommerce/class-storefront-woocommerce-adjacent-products.php';

	require 'inc/woocommerce/storefront-woocommerce-template-hooks.php';
	require 'inc/woocommerce/storefront-woocommerce-template-functions.php';
	require 'inc/woocommerce/storefront-woocommerce-functions.php';
}

if ( is_admin() ) {
	$storefront->admin = require 'inc/admin/class-storefront-admin.php';

	require 'inc/admin/class-storefront-plugin-install.php';
}

/**
 * NUX
 * Only load if wp version is 4.7.3 or above because of this issue;
 * https://core.trac.wordpress.org/ticket/39610?cversion=1&cnum_hist=2
 */
if ( version_compare( get_bloginfo( 'version' ), '4.7.3', '>=' ) && ( is_admin() || is_customize_preview() ) ) {
	require 'inc/nux/class-storefront-nux-admin.php';
	require 'inc/nux/class-storefront-nux-guided-tour.php';
	require 'inc/nux/class-storefront-nux-starter-content.php';
}

/**
 * Note: Do not add any custom code here. Please use a custom plugin so that your customizations aren't lost during updates.
 * https://github.com/woocommerce/theme-customisations
 */
