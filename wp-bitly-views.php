<?php

add_action( 'admin_menu', 'wpbitly_add_pages' );
add_action( 'admin_menu', 'wpbitly_statistics_metabox' );


function wpbitly_add_pages() {

	$hook = add_options_page( 'WP Bit.ly Options', 'WP Bit.ly Options', 8, 'wpbitly', 'wpbitly_display' );
		add_action( 'admin_print_styles-' . $hook, 'wpbitly_print_styles' ); 
		add_action( 'admin_print_scripts-' . $hook, 'wpbitly_print_scripts' ); 

}


function wpbitly_print_styles() {
	wp_enqueue_style( 'dashboard' );
	wp_enqueue_style( 'wpbitly', plugins_url( '', __FILE__ ) . '/assets/wpbitly.css', FALSE, WPBITLY_VERSION, 'screen' );
}


function wpbitly_print_scripts() {
	wp_enqueue_script( 'dashboard' );
	wp_enqueue_script( 'jquery-validate', 'http://dev.jquery.com/view/trunk/plugins/validate/jquery.validate.js', 'jQuery', FALSE, TRUE );
}


function wpbitly_statistics_metabox() {
	global $post;

	if ( is_object( $post ) && get_post_meta( $post->ID, '_wpbitly', TRUE ) ) {
		add_meta_box( 'wpbitly_stats', 'WP Bit.ly Statistics', 'wpbitly_build_statistics_metabox', 'page', 'side' );
		add_meta_box( 'wpbitly_stats', 'WP Bit.ly Statistics', 'wpbitly_build_statistics_metabox', 'post', 'side' );
	}

}


function wpbitly_build_statistics_metabox() {
	global $wpbitly, $post;

	$wpbitly_link = get_post_meta( $post->ID, '_wpbitly', TRUE );

	if ( ! $wpbitly_link )
		return;

	$url = sprintf( $wpbitly->url['clicks'], $wpbitly_link, $wpbitly->options['bitly_username'], $wpbitly->options['bitly_api_key'] );
	$bitly_response = wpbitly_curl( $url );

	if ( is_array( $bitly_response ) && $bitly_response['status_code'] == 200 ) {
		echo '<p>Statistics for the short link associated with this article;</p>';
		echo "<p>Global Clicks: <strong>{$bitly_response['data']['clicks'][0]['global_clicks']}</strong><br/>";
		echo "<p>User Clicks: <strong>{$bitly_response['data']['clicks'][0]['user_clicks']}</strong></p>";
	}
	else {
		echo '<p>There was a problem retrieving statistics from Bit.ly.</p>';
	}

}


function wpbitly_display() {
	global $wpbitly;

	echo '<div class="wrap">';
	screen_icon();
	echo '<h2 style="margin-bottom: 1em;">' . __( 'WP Bit.ly Options', 'wpbitly' ) . '</h2>';

?>
	<div class="postbox-container" style="width: 70%;">
	<div class="metabox-holder">	
	<div class="meta-box-sortables">
		<form action="options.php" id="wpbitly" method="post">
		<?php
        	settings_fields( 'wpbitly_admin_options' );
			wpbitly_postbox_options();
		?>
		</form>
	</div></div>
	</div> <!-- .postbox-container -->

	<div class="postbox-container" style="width: 24%;">
	<div class="metabox-holder">	
	<div class="meta-box-sortables">
	<?php
		wpbitly_postbox_support();
		if ( ! empty( $wpbitly->options['bitly_username'] ) && ! empty( $wpbitly->options['bitly_api_key'] ) && ! get_option( 'wpbitly_invalid' ) ) wpbitly_postbox_generate();
	?>
	</div></div>
	</div> <!-- .postbox-container -->

	</div> <!-- .wrap -->
<?php

}


function wpbitly_postbox_options() {
	global $wpbitly;

	$options = array();

	$options[] = array( 'id'    => 'bitly_username',
					    'name'  => __( 'Bit.ly Username:', 'wpbitly' ),
						'desc'  => __( 'The username you use to log in to your Bit.ly account.', 'wpbitly' ),
						'input' => '<input name="wpbitly_options[bitly_username]" type="text" value="' . $wpbitly->options['bitly_username'] . '" />'
					   );

	$options[] = array( 'id'    => 'bitly_api_key',
					    'name'  => __( 'Bit.ly API Key:', 'wpbitly' ),
						'desc'  => sprintf( __( 'Your API key can be found on your %1$s', 'wpbitly' ), '<a href="http://bit.ly/account/" target="_blank">' . __( 'Bit.ly account page', 'wpbitly' ) . '</a>' ),
						'input' => '<input name="wpbitly_options[bitly_api_key]" type="text" value="' . $wpbitly->options['bitly_api_key'] . '" />'
					   );

	$options[] = array( 'id'    => 'post_types',
					    'name'  => __( 'Post Types:', 'wpbitly' ),
						'desc'  => __( 'What kind of posts should short links be generated for?', 'wpbitly' ),
						'input' => '<input name="wpbitly_options[post_types]" type="radio" value="post" ' . checked( 'post', $wpbitly->options['post_types'], FALSE ) . ' /><span>' . __( 'Posts', 'wpbitly' ) . '</span>' .
								   '<input name="wpbitly_options[post_types]" type="radio" value="page" ' . checked( 'page', $wpbitly->options['post_types'], FALSE ) . ' /><span>' . __( 'Pages', 'wpbitly' ) . '</span>' .
								   '<input name="wpbitly_options[post_types]" type="radio" value="any" ' . checked( 'any', $wpbitly->options['post_types'], FALSE ) . ' /><span>' . __( 'All', 'wpbitly' ) . '</span>'
					   );

	$output = '<div class="intro">';
	$output .= '<p>' . __( 'Use the following options to configure your Bit.ly API access and determine the general operation of the WP Bit.ly plugin.', 'wpbitly' ) . '</p>';
	$output .= '</div>';

	$output .= wpbitly_build_form( $options );

	wpbitly_build_postbox( 'wp_bitly_options', __( 'General Settings', 'wpbitly' ), $output );
}


function wpbitly_postbox_support() {

	$output  = '<p>' . __( 'If you require support, or would like to contribute to the further development of this plugin, please choose one of the following;', 'wpbitly' ) . '</p>';
	$output .= '<ul class="links">';
	$output .= '<li><a href="http://mark.watero.us/">' . __( 'Author Homepage', 'wpbitly' ) . '</a></li>';
	$output .= '<li><a href="http://mark.watero.us/wordpress-plugins/wp-bitly/">' . __( 'Plugin Homepage', 'wpbitly' ) . '</a></li>';
	$output .= '<li><a href="http://wordpress.org/extend/plugins/wp-bitly/">' . __( 'Rate This Plugin', 'wpbitly' ) . '</a></li>';
	$output .= '<li><a href="http://mark.watero.us/wordpress-plugins/oops/">' . __( 'Bug Reports', 'wpbitly' ) . '</a></li>';
//	$output .= '<li><a href="http://mark.watero.us/">' . __( 'Feature Requests', 'wpbitly' ) . '</a></li>';
	$output .= '<li><a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=9847234">' . __( 'Donate To The Cause', 'wpbitly' ) . '</a></li>';
	$output .= '</ul>';

	$output .= '<div class="logo"><a href="http://mark.watero.us/" target="_blank" title="Visit the Author">http://mark.watero.us/</a></div>';

	wpbitly_build_postbox( 'support', 'WP Bit.ly', $output );
}


function wpbitly_postbox_generate() {
	global $wpbitly;

	$output = '';

	if ( isset( $_POST['wpbitly_generate'] ) ) {

		$generate = $wpbitly->options['post_types'];
		$status = '<strong>' . __( 'Generate Short Links:', 'wpbitly' ) . '</strong> ';

		if ( empty( $wpbitly->options['bitly_username'] ) || empty( $wpbitly->options['bitly_api_key'] ) || get_option( 'wpbitly_invalid' ) ) {
			$output .= '<div class="error"><p>' . $status . __( 'You must configure your username and API key first!', 'wpbitly' ) . '</p></div>';
		}
		else if ( in_array( $generate, array( 'post', 'page', 'any' ) ) ) {

			$posts = get_posts( "numberposts=-1&post_type={$generate}" );

			foreach ( $posts as $the )
				if ( ! get_post_meta( $the->ID, '_wpbitly', TRUE ) )
					wpbitly_generate_shortlink( $the->ID );

			$output .= '<div class="updated fade"><p>' . $status . __( 'Short links have been generated for the selected post type!', 'wpbitly' ) . '</p></div>';

		} // if ( empty )

	} // if ( isset )

	$output .= '<form action="" method="post">';
	$output .= '<p class="wpbitly utility"><input type="submit" name="wpbitly_generate" class="button-primary" value="' . __( 'Generate Links', 'wpbitly' ) . '" /></p>';
	$output .= '</form>';

	wpbitly_build_postbox( 'wpbitly_generate', __( 'Generate Short Links', 'wpbitly' ), $output );

}


function wpbitly_build_postbox( $id, $title, $content, $echo = TRUE ) {

	$output  = '<div id="wpbitly_' . $id . '" class="postbox">';
	$output .= '<div class="handlediv" title="Click to toggle"><br /></div>';
	$output .= '<h3 class="hndle"><span>' . $title . '</span></h3>';
	$output .= '<div class="inside">';
	$output .= $content;
	$output .= '</div></div>';

	if ( $echo === TRUE )
		echo $output;

	return $output;

}


function wpbitly_build_form( $options, $button = 'secondary' ) {

	$output = '<fieldset>';

	foreach ( $options as $option ) {

		$output .= '<dl' . ( isset( $option['class'] ) ? ' class="' . $option['class'] . '"' : '' ) . '>';
		$output .= '<dt><label for="wpbitly_options[' . $option['id'] . '">' . $option['name'] . '</label>';

		if ( isset( $option['desc'] ) )
			$output .= '<p>' . $option['desc'] . '</p>';

		$output .= '</dt>';
		$output .= '<dd>' . $option['input'] . '</dd>';
		$output .= '</dl>';

	}

	$output .= '<div style="clear: both;"></div>';
	$output .= '<p class="wpbitly_submit"><input type="submit" class="button-' . $button . '" value="' . __( 'Save Changes', 'wpbitly' ) . '" /></p>';
	$output .= '</fieldset>';

	return $output;

}
