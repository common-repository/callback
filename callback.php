<?php
/*
Plugin Name: Callback
Plugin URI: http://loanpaymentplugin.com/
Description: Simple callback, newsletter and lead generator form. Shortcode is [callback].
Version: 1.0
Author: aerin
Author URI: http://quick-plugins.com/
License: GPL v2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: callback
Domain Path: /languages

Callback is free software: you can redistribute it and/or modify it under the terms of the GNU General Public License as published by the Free Software Foundation, either version 2 of the License, or any later version.

Callback is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with Callback. If not, see {URI to Plugin License}.
*/

register_activation_hook( __FILE__, 'callback_create_tracking');

require_once( plugin_dir_path( __FILE__ ) . '/options.php' );
require_once( plugin_dir_path( __FILE__ ) . '/modules.php' );
require_once( plugin_dir_path( __FILE__ ) . '/mailchimp/mailchimp.init.php');

if (is_admin()) require_once( plugin_dir_path( __FILE__ ) . '/settings.php' );

add_action( 'wp_enqueue_scripts', 'callback_scripts');
add_action( 'init', 'callback_init' );
add_action( 'wp_ajax_ajax_callback_submit', 'callback_ajax_submit' );
add_action( 'wp_ajax_nopriv_ajax_callback_submit', 'callback_ajax_submit' );
add_action( 'wp_ajax_ajax_track', 'callback_ajax_track' );
add_action( 'wp_ajax_nopriv_ajax_track', 'callback_ajax_track' );
add_action( 'wp_dashboard_setup', 'callback_add_dashboard_widgets' );

add_filter( 'plugin_action_links', 'callback_plugin_action_links', 10, 2 );

add_shortcode('callback', 'callback_display');

// Block
function callback_init() {
	
	load_plugin_textdomain( 'callback', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
	
	if ( !function_exists( 'register_block_type' ) ) {
		return;
	}
	
	// Register our block editor script.
	wp_register_script(
		'callback',
		plugins_url( 'block.js', __FILE__ ),
		array( 'wp-blocks' )
	);
	
	register_block_type(
		'callback/block',
		array(
			'editor_script'		=> 'callback', // The script name we gave in the wp_register_script() call.
			'render_callback'	=> 'callback_display'
		)
	);
}

// Display the form on the page
function callback_display() {
	
	$ajaxurl	= admin_url( 'admin-ajax.php' );
	
	$settings	= callback_get_stored_settings();
	$fields		= callback_get_stored_fields();
	
	$track = get_option('callback_track');
	@$track['visitors']++;
	update_option('callback_track',$track);

	// Set up validation functions
	$functions  = [];
	foreach ($fields as $name => $field) {
		$js = $field['js'] ? $field['js'] : "function(obj){ return false; }";
		$functions[] = '"'.$name.'":{"required":'.(($field['required'] == 'checked')? 'true':'false').',"callback":'.$js.'}';
	}
	$functions  = implode(",\n			",$functions);
	
	// Set up JS arrays
	$output = '<script type="text/javascript">
	var callback_fields = {'.$functions.'};
	var callback_ajax_url = "'.$ajaxurl.'";
	</script>';
	
	// Callback form
	$output .= '<form action="" class="callback_form" method="POST" id="callback">';
	
	// Defines the type of form
	if ($settings['type'] == 'modal') {
		$output .= '<div id="callback_apply"><a class="openmodal" href="#/">'.$settings['applylabel'].'</a></div>
		<div id="callbackform" class="modal">';
		$output .= callback_display_form($settings,$fields);
		$output .= '</div>';
	} else if ($settings['type'] == 'always') {
		$output .= '<div id="callbackform">';
		$output .= callback_display_form($settings,$fields);
		$output .= '</div>';
	} else {
		$output .= '<div id="callback_apply"><a class="opentoggle" href="#/">'.$settings['applylabel'].'</a></div>
		<div id="callbackform" class="toggle">';
		$output .= callback_display_form($settings,$fields);
		$output .= '</div>';
	}
	
	// Closes the form
	$output .= '</form>';
	
	return $output;
}

// Sets up tracking array
function callback_create_tracking() {
	$tracking = get_option('callback_track');
	if (is_array($tracking)) return;
	else $tracking['opened'] = $tracking['applied'] = $tracking['completed'] = 0;
	update_option('callback_track',$tracking);
}

// Enqueue Scripts and Styles
function callback_scripts() {
	
	wp_enqueue_style( 'callback_style',plugins_url('callback.css', __FILE__));
	wp_enqueue_script("jquery-effects-core");
	wp_enqueue_script('callback_script',plugins_url('callback.js', __FILE__ ), array( 'jquery' ), false, true );
}

// Settings linm from plugins page)
function callback_plugin_action_links($links, $file ) {
	if ( $file == plugin_basename( __FILE__ ) ) {
		$callback_links = '<a href="'.get_admin_url().'options-general.php?page=callback/settings.php">'.__('Settings','callback').'</a>';
		array_unshift( $links, $callback_links );
		}
	return $links;
}