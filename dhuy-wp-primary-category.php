<?php
/*
Plugin Name:  WP Primary Category
Plugin URI:	  https://github.com/dhuyvetter/wp-primary-category
Description:  WordPress pluign for assigning a primary category to a post (or page or other post type).
Version:      0.0.1
Author:       Dimitri Dhuyvetter
Author URI:   https://dhuyvetter.eu/
License:      GPL3
License URI:  https://www.gnu.org/licenses/gpl-3.0.en.html
Text Domain:  dhuy
Domain Path:  /languages
*/

defined( 'ABSPATH' ) or die( 'Thou shall not pass!' );

/**
 * Register meta box
 */
function register_meta_box() {
	add_meta_box( 'primary-category', __( 'Primary Category', 'dhuy' ), 'display_meta_box', 'post' );
}

add_action( 'add_meta_boxes', 'register_meta_box' );

/**
 * Meta box display callback.
 *
 * @param WP_Post $post Current post object.
 */
function display_meta_box( $post ) {
	$categories       = get_categories();
	$primary_category = (int) get_post_meta( $post->ID, 'dhuy_primary_category', true );
	wp_nonce_field( 'dhuy_primary_category_save', 'dhuy_primary_category_save_nonce', false, true );
	?>
    <select name="dhuy_primary_category">
		<?php
		foreach ( $categories as $category ) {
			$selected = ( $primary_category === $category->term_id ) ? ' selected' : ''; ?>
            <option value="<?php echo $category->term_id ?>"<?php echo $selected; ?>><?php echo $category->name ?></option><?php
		}
		?>
    </select>
	<?php
}

/**
 * Save meta box content.
 *
 * @param int $post_id Post ID
 */
function save_meta_box( $post_id ) {
	if ( ! isset( $_POST["dhuy_primary_category"] ) ) {
		return;
	}

	// verify our nonce
	if ( ! wp_verify_nonce( $_POST['dhuy_primary_category_save_nonce'], 'dhuy_primary_category_save' ) ) {
		return;
	}

	$primary_category = (int) $_POST["dhuy_primary_category"];

	if ( ! add_post_meta( $post_id, "dhuy_primary_category", $primary_category, true ) ) {
		update_post_meta( $post_id, "dhuy_primary_category", $primary_category );
	}

	// Append primary category to categories in case it wasn't selected
	wp_set_post_categories( $post_id, [ $primary_category ], true );

	return $post_id;
}

add_action( 'save_post', 'save_meta_box' );

/**
 * Get all posts with given category as primary category
 *
 * @param int $primary_category Category ID
 *
 * @return array $query WordPress Query
 */
function get_posts_by_primary_category( $primary_category ) {
	$primary_category = (int) $primary_category;
	if ( ! $primary_category > 0 ) {
		return;
	}

	$meta_query_args = array(
		array(
			'key'     => 'dhuy_primary_category',
			'value'   => $primary_category,
			'compare' => '='
		)
	);
	$query           = new WP_Meta_Query( $meta_query_args );

	return $query;
}

add_filter( 'dhuy_get_posts_by_primary_category', 'get_posts_by_primary_category' );

/**
 * Add URL rewrite rule for primary-category
 */
function rewrite_rule() {
	add_rewrite_rule( '^primary-category/([0-9]+)/?', 'index.php?primary_category_id=$matches[1]', 'top' );
}

add_action( 'init', 'rewrite_rule' );