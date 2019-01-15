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
		return null;
	}

	// verify our nonce
	if ( ! wp_verify_nonce( $_POST['dhuy_primary_category_save_nonce'], 'dhuy_primary_category_save' ) ) {
		return null;
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
 * Primary Category archive shortcode
 * Get all posts with given category as primary category
 *
 * @param $atts
 *
 * @return string $result List of posts
 */
function get_posts_by_primary_category( $atts ) {
	$cat_id = null;
	if ( isset( $atts['id'] ) ) {
		$cat_id = (int) $atts['id'];
		if ( ! $cat_id > 0 ) {
			return __( 'Invalid id in shortcode', 'dhuy' );
		}
	} elseif ( isset( $atts['slug'] ) ) {
		$result = wp_cache_get( $atts['slug'], 'dhuy_posts_by_primary_category' );
		if ( false !== $result ) {
			return $result;
		}
		$category = get_category_by_slug( $atts['slug'] );
		if ( ! $category ) {
			return __( 'Invalid slug in shortcode', 'dhuy' );
		}
		$cat_id = $category->term_id;
	}

	$result = wp_cache_get( $cat_id, 'dhuy_posts_by_primary_category' );
	if ( false !== $result ) {
		return $result;
	}
	$args  = array(
		'post_type'  => 'any',
		'meta_key'   => 'dhuy_primary_category',
		'meta_query' => array(
			'key'   => 'dhuy_primary_category',
			'value' => $cat_id,
		)
	);
	$query = new WP_Query( $args );

	// The Loop
	if ( $query->have_posts() ) {
		$result = '<ul>';
		while ( $query->have_posts() ) {
			$query->the_post();
			$result .= '<li>' . get_the_title() . '</li>';
		}
		$result .= '</ul>';
		/* Restore original Post Data */
		wp_reset_postdata();
	} else {
		$result = __( 'No posts with this primary category', 'dhuy' );
	}

	wp_cache_set( $cat_id, 'dhuy_posts_by_primary_category', $result );
	if ( isset( $atts['slug'] ) ) {
		wp_cache_set( $atts['slug'], 'dhuy_posts_by_primary_category', $result );
	}

	return $result;
}

add_shortcode( 'dhuy_primary_category_archive', 'get_posts_by_primary_category' );
