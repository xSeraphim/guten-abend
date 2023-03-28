<?php
/**
 * Plugin Name: Gutten Abend Table Generator
 * Author: Aldea Daniel
 * Version: 1.0.0
 * Description: This plugin creates a custom guttenberg table block
 * Text Domain: gutten-abend
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
// Plugin URL.
define( 'GUTTEN_ABEND_URL', untrailingslashit( plugins_url( basename( plugin_dir_path( __FILE__ ) ), basename( __FILE__ ) ) ) );
// Plugin path.
define( 'GUTTEN_ABEND_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );

// Register custom post type
function gutten_create_job_titles_post_type() {
	$labels = array(
		'name'               => __( 'Job Titles', 'gutten-abend' ),
		'singular_name'      => __( 'Job Title', 'gutten-abend' ),
		'add_new'            => __( 'Add New', 'gutten-abend' ),
		'add_new_item'       => __( 'Add New Job Title', 'gutten-abend' ),
		'edit_item'          => __( 'Edit Job Title', 'gutten-abend' ),
		'new_item'           => __( 'New Job Title', 'gutten-abend' ),
		'view_item'          => __( 'View Job Title', 'gutten-abend' ),
		'search_items'       => __( 'Search Job Titles', 'gutten-abend' ),
		'not_found'          => __( 'No Job Titles found', 'gutten-abend' ),
		'not_found_in_trash' => __( 'No Job Titles found in Trash', 'gutten-abend' ),
		'menu_name'          => __( 'Job Titles', 'gutten-abend' ),
	);
	$args   = array(
		'labels'       => $labels,
		'public'       => true,
		'has_archive'  => true,
		'rewrite'      => array( 'slug' => 'job-titles' ),
		'supports'     => array( 'title', 'editor' ),
		'menu_icon'    => 'dashicons-businessman',
		'show_in_rest' => true,
		'taxonomies'   => array( 'skill' ),
	);
	register_post_type( 'job_titles', $args );
}
add_action( 'init', 'gutten_create_job_titles_post_type' );

// Register custom taxonomy for skills
function gutten_create_skill_taxonomy() {
	$labels = array(
		'name'              => __( 'Skills', 'gutten-abend' ),
		'singular_name'     => __( 'Skill', 'gutten-abend' ),
		'search_items'      => __( 'Search Skills', 'gutten-abend' ),
		'all_items'         => __( 'All Skills', 'gutten-abend' ),
		'parent_item'       => __( 'Parent Skill', 'gutten-abend' ),
		'parent_item_colon' => __( 'Parent Skill:', 'gutten-abend' ),
		'edit_item'         => __( 'Edit Skill', 'gutten-abend' ),
		'update_item'       => __( 'Update Skill', 'gutten-abend' ),
		'add_new_item'      => __( 'Add New Skill', 'gutten-abend' ),
		'new_item_name'     => __( 'New Skill Name', 'gutten-abend' ),
		'menu_name'         => __( 'Skills', 'gutten-abend' ),
	);
	$args   = array(
		'labels'            => $labels,
		'hierarchical'      => false,
		'public'            => true,
		'show_ui'           => true,
		'show_admin_column' => true,
		'query_var'         => true,
		// 'show_in_rest'      => true,
		'rewrite'           => array( 'slug' => 'skill' ),
	);
	register_taxonomy( 'skill', array( 'job_titles' ), $args );
}
add_action( 'init', 'gutten_create_skill_taxonomy' );

// Add custom field for Job Titles
function gutten_add_job_title_meta_box() {
	add_meta_box(
		'job_title_skill',
		__( 'Skill', 'gutten-abend' ),
		'gutten_job_title_skill_meta_box_callback',
		'job_titles',
		'normal',
		'default'
	);
}
add_action( 'add_meta_boxes', 'gutten_add_job_title_meta_box' );

// Callback function for custom field
function gutten_job_title_skill_meta_box_callback( $post ) {
	wp_nonce_field( 'job_title_skill_meta_box', 'job_title_skill_meta_box_nonce' );
	$values = get_post_meta( $post->ID, '_job_title_skill', true );
	if ( ! is_array( $values ) ) {
		$values = array();
	}
	$terms = get_terms( 'skill', array( 'hide_empty' => false ) );
	?>
	<label for="job_title_skill">Select the skills required for the Job Title:</label><br>
	<select name="job_title_skill[]" multiple>
		<?php foreach ( $terms as $term ) { ?>
			<option value="<?php echo $term->term_id; ?>" 
									<?php
									if ( in_array( $term->term_id, $values, true ) ) {
											echo 'selected'; }
									?>
			><?php echo $term->name; ?></option>
		<?php } ?>
	</select>
	<?php
}


// Save custom field data
function save_job_title_meta_box_data( $post_id ) {
	if ( ! isset( $_POST['job_title_skill_meta_box_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( $_POST['job_title_skill_meta_box_nonce'], 'job_title_skill_meta_box' ) ) {
		return;
	}
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}
	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}
	if ( ! isset( $_POST['job_title_skill'] ) ) {
		$terms = wp_get_post_terms( $post_id, 'skill', array( 'fields' => 'ids' ) );
		delete_post_meta( $post_id, '_job_title_skill' );
		wp_remove_object_terms( $post_id, $terms, 'skill' );
		return;
	}
	$values = array();
	foreach ( $_POST['job_title_skill'] as $term_id ) {
		$values[] = intval( $term_id );
	}
	update_post_meta( $post_id, '_job_title_skill', $values );
	// Create the term relationships in the wp_term_relationships table so the skills show up when viewing the Job Titles
	wp_set_object_terms( $post_id, $values, 'skill' );
}
add_action( 'save_post', 'save_job_title_meta_box_data' );
