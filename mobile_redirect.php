<?php
/*
 Plugin Name: Mobile Redirection for Pages and Posts
 Description: Redirect the visitor to a specific url when the page is viewed from a mobile device. You can specify on which url you want the visitors to be redirected for particular page.
 Version: 1.0.0
 Author: Review Station
 Author URI: https://reviewstation.in
 */ 

/**
 * Calls the class on the post edit screen.
 */
function mobile_redirectClass() {

    return new mob_redirectionClass();
}

add_action('wp_head', 'mobile_redirection');
function mobile_redirection(){
	global $post;
	$mobred_flag = get_post_meta( $post->ID, '_mr_meta_value_flag', true );
	$mobred_url = get_post_meta( $post->ID, '_mr_meta_value_url', true );
	if($mobred_flag=="checked"){

	?>
	<script>
	if(window.innerWidth<768){
		window.location="<?=$mobred_url?>";
	}
	</script>
	<?php
	}
}
if ( is_admin() ) {
    add_action( 'load-post.php', 'mobile_redirectClass' );
    add_action( 'load-post-new.php', 'mobile_redirectClass'  );
}

/** 
 * The Class.
 */
class mob_redirectionClass {

	/**
	 * Hook into the appropriate actions when the class is constructed.
	 */
	public function __construct() {
		add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
		add_action( 'save_post', array( $this, 'save' ) );
	}

	/**
	 * Adds the meta box container.
	 */
	public function add_meta_box() {
		add_meta_box(
			 'mobile_redirect'
			,__( 'Mobile Redirect', 'mr_textdomain' )
			,array( $this, 'render_meta_box_content' )
			,'post'
			,'advanced'
			,'high'
		);
		 	add_meta_box(
		 	 'mobile_redirect'
		 	,__( 'Mobile Redirect', 'mr_textdomain' )
		 	,array( $this, 'render_meta_box_content' )
		 	,'page'
		 	,'advanced'
		 	,'high'
		);
	}

	/**
	 * Save the meta when the post is saved.
	 *
	 * @param int $post_id The ID of the post being saved.
	 */
	public function save( $post_id ) {
	
		/*
		 * We need to verify this came from the our screen and with proper authorization,
		 * because save_post can be triggered at other times.
		 */

		// Check if our nonce is set.
		if ( ! isset( $_POST['mr_inner_custom_box_nonce'] ) )
			return $post_id;

		$nonce = $_POST['mr_inner_custom_box_nonce'];

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $nonce, 'mr_inner_custom_box' ) )
			return $post_id;

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) 
			return $post_id;

		// Check the user's permissions.
		if ( 'page' == $_POST['post_type'] ) {

			if ( ! current_user_can( 'edit_page', $post_id ) )
				return $post_id;
	
		} else {

			if ( ! current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		}

		/* OK, its safe for us to save the data now. */

		// Sanitize the user input.
		$mydata = sanitize_text_field( $_POST['mr_url'] );
		$myflag = sanitize_text_field( $_POST['mr_flag'] );
		// Update the meta field.
		update_post_meta( $post_id, '_mr_meta_value_flag', $myflag );
		update_post_meta( $post_id, '_mr_meta_value_url', $mydata );

	}


	/**
	 * Render Meta Box content.
	 *
	 * @param WP_Post $post The post object.
	 */
	public function render_meta_box_content( $post ) {
		?>

		<script>
		jQuery(document).ready(function($){
			if($('#mr_flag').is(':checked')){
					$('#md_url_box').css('display', 'block');
			}
			$('#mr_flag').change(function() {
				if($(this).is(':checked')){
					$('#md_url_box').css('display', 'block');
				}else{
					$('#md_url_box').css('display', 'none');
				}
			});
		});
		</script>
		<?php
	
		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'mr_inner_custom_box', 'mr_inner_custom_box_nonce' );

		// Use get_post_meta to retrieve an existing value from the database.
		$mr_flag = get_post_meta( $post->ID, '_mr_meta_value_flag', true );
		$value = get_post_meta( $post->ID, '_mr_meta_value_url', true );
		$checkval=$mr_flag=="checked"?"checked":"";
		// Display the form, using the current value.
		echo '<label for="mr_flag">';
		_e( 'Do you want the mobile redirection on this page?', 'mr_textdomain' );
		echo '</label>&nbsp; ';
		echo '<input type="checkbox" id="mr_flag" name="mr_flag" value="checked" '.$checkval.'/>';
		echo '<br /><div id="md_url_box" style="display:none;"><p><label for="mr_url">';
		_e( 'Add URL', 'mr_textdomain' );
		echo '</label> ';
		echo '<input type="text" id="mr_url" name="mr_url" value="' . esc_attr( $value ) . '" size="45" />(for ex: <i>https://www.google.com</i>)</p></div>';
	}
}