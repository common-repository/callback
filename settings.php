<?php

add_action('admin_menu', 'callback_admin_pages');
add_action('admin_notices', 'callback_admin_notice' );

function callback_admin_pages() {
	add_options_page('Callback Form', 'Callback Form', 'manage_options', __FILE__, 'callback_settings');
	add_menu_page('Callbacks', 'Callbacks', 'manage_options','callback/messages.php','','dashicons-email-alt');
}

function callback_settings(){
	
	$modal = $always = $toggle = false;
	
	if( isset( $_POST['SubmitFields']) && check_admin_referer("save_callback")) {
		
		$fields = callback_get_stored_fields();
		
		$options = array(
			'yourname',
			'contact',
			'callwhen',
			'url',
			'ip',
			'sentdate',
		);
		
		foreach ($options as $item) {
			$fields[$item]['label']		= sanitize_text_field($_POST[$item.'label']);
			$fields[$item]['caption']	= sanitize_text_field($_POST[$item.'caption']);
			$fields[$item]['use'] 		= isset($_POST[$item.'use']) ? true : false;
		}
		
		update_option( 'callback_fields', $fields);

		callback_admin_notice(__('The form fields have been updated','callback'));
	}
	
	if( isset( $_POST['ResetFields']) && check_admin_referer("save_callback")) {
		delete_option('callback_fields');
		callback_admin_notice(__('The form fields have been reset','callback'));
	}

	if( isset( $_POST['SubmitSettings']) && check_admin_referer("save_callback")) {
		
		$options = array(
			'applylabel',
			'applyinstructions',
			'yournamelabel',
			'contactlabel',
			'sentdatelabel',
			'iplabel',
			'urllabel',
			'submit',
			'thankyoutitle',
			'thankyoublurb',
			'errorlabel',
			'notificationsubject',
			'sendto',
			'type'
		);
		
		foreach ($options as $item) {
			$settings[$item] = sanitize_text_field($_POST[$item]);
		}
		
		update_option( 'callback_settings', $settings);

		callback_admin_notice(__('The settings have been updated','callback'));
	}
	
	if( isset( $_POST['ResetSettings']) && check_admin_referer("save_callback")) {
		delete_option('callback_settings');
		callback_admin_notice(__('The settings have been reset','callback'));
	}
	
	if( isset( $_POST['SubmitMailchimp']) && check_admin_referer("save_callback")) {
		
		$options = array(
			'mailchimpkey',
			'mailchimplistid'
		);
		
		foreach ($options as $item) {
			$mailinglist[$item] = sanitize_text_field($_POST[$item]);
		}
		
		update_option( 'mailinglist_settings', $mailinglist);

		callback_admin_notice(__('The mainchimp settings have been updated','callback'));
	}
	
	if( isset( $_POST['ResetMailchimp']) && check_admin_referer("save_callback")) {
		delete_option('mailinglist_settings');
		callback_admin_notice(__('The mailchip settings have been reset','callback'));
	}
	
	// Reset the tracking
	if( isset( $_POST['resettracking']) && check_admin_referer("save_callback")) {
		delete_option('callback_track');
		callback_admin_notice(__('Tracking has been reset','callback'));
	}
	
	$settings		= callback_get_stored_settings();
	$fields			= callback_get_stored_fields();
	$mailinglist	= callback_mailchimp();

	${$settings['type']} = 'checked="checked"';
	
	if ($fields['yourname']['use'])	$fields['yourname']['use'] = ' checked';
	if ($fields['contact']['use'])	$fields['contact']['use']  = ' checked';
	if ($fields['callwhen']['use'])	$fields['callwhen']['use']  = ' checked';
	
	$content ='<style>.callback-options {margin: 12px 15px;}
.callback-options p {margin: 4px 0;padding: 0;}
.callback-options h2 {color: #005F6B;margin-top:0;}
.callback-options table {width:100%;}
.callback-options th {text-align:left;}
.callback-options td {vertical-align:middle;}
.callback-options input[type=text], .callback-options textarea, .callback-options select, .callback-options #submit {width: 100%;box-sizing: border-box;}
.callback-options .description {font-style:italic;}
</style>';
	
	// Instructions for use
	$content .='<div class="callback-options">
	<form method="post" action="">
	
	<h1>'.__('Callback Form Settings', 'callback').'</h1>
	
	<fieldset style="border: 1px solid #888888;padding:10px;margin-bottom:10px;">

	<h2>'.__('Using the Plugin', 'callback').'</h2>
	<p>'.__('Add the form using the shortcode [callback]', 'callback').' '.__('or use the Callback widget block', 'callback').'.</p>
	<p>'.__('Callbacks are sent to the email address you set below and saved to the database', 'callback').'.</p>
	<p><a href="?page=callback/messages.php">'.__('Manage Callbacks', 'callback').'</a>.</p>
	<p>'.__('You can also click on the <b>Callbacks</b> link in the dashboard menu to access the messages', 'callback').'.</p>
	<p>'.__('To get help send an email to', 'callback').' <a href="mailto:mail@quick-plugins.com">mail@quick-plugins.com</a>.</p>
	
	</fieldset>';
	
	//Form fields
	$content .= '<fieldset style="border: 1px solid #888888;padding:10px;margin-top:10px;">
	
	<h2>'.__('Form Fields','callback').'</h2>
	
	<p class="description">'.__('Only those fields with a label are shown on the form','callback').'. '.__('Unckeck the <strong>Use</strong> box to remove a field from the form','callback').'. '.__('The captions are used in the email message and','callback').' <a href="?page=callback/messages.php">'.__('callbacks list','callback').'</a>.</span></p>
	
	<table>
	<tr><th style="width:15%">Field</th><th style="width:5%">Use</th><th style="width:40%">Form Label</th><th style="width:40%">Email Caption</th></tr>
	
	<tr><td>Name:</td><td><input type="checkbox" name="yournameuse" value="checked" '.$fields['yourname']['use'].'></td><td><input type="text" name="yournamelabel" value ="' . $fields['yourname']['label'] . '" /></td><td><input type="text" name="yournamecaption" value ="' . $fields['yourname']['caption'] . '" /></td></tr>
	
	<tr><td>Contact:</td><td><input type="checkbox" name="contactuse" value="checked" '.$fields['contact']['use'].'></td><td><input type="text" name="contactlabel" value ="' . $fields['contact']['label'] . '" /></td><td><input type="text" name="contactcaption" value ="' . $fields['contact']['caption'] . '" /></td></tr>
	
	<tr><td>When to call selector:</td><td><input type="checkbox" name="callwhenuse" value="checked" '.$fields['callwhen']['use'].'></td><td><input type="text" name="callwhenlabel" value ="' . $fields['callwhen']['label'] . '" /></td><td><input type="text" name="callwhencaption" value ="' . $fields['callwhen']['caption'] . '" /></td></tr>
	<tr><td></td><td></td><td><span class="description">This selector is conditional and only displays if the contact field is a telephone number. Separate options with a comma.</span></td><td></td></tr>
	
	<tr><td>Page:</td><td></td><td>Not shown<input type="hidden" name="urllabel" value ="' . $fields['url']['label'] . '" /></td><td><input type="text" name="urlcaption" value ="' . $fields['url']['caption'] . '" /></td></tr>
	<tr><td>IP Address:</td><td></td><td>Not shown<input type="hidden" name="iplabel" value ="' . $fields['ip']['label'] . '" /></td><td><input type="text" name="ipcaption" value ="' . $fields['ip']['caption'] . '" /></td></tr>
	<tr><td>Sent date:</td><td></td><td>Not shown<input type="hidden" name="sentdatelabel" value ="' . $fields['sentdate']['label'] . '" /></td><td><input type="text" name="sentdatecaption" value ="' . $fields['sentdate']['caption'] . '" /></td></tr>
	</table>
	
	<p><input type="submit" name="SubmitFields" class="button-primary" style="color: #FFF;" value="Update Form Fields" /> <input type="submit" name="ResetFields" class="button-secondary" value="Reset" onclick="return window.confirm( \'Are you sure you want to reset the form fields?\' );"/></p>
	
	</fieldset>';
	
	//Labels
	$content .= '<fieldset style="border: 1px solid #888888;padding:10px;margin-top:10px;">
	
	<h2>'.__('Labels and Values','callback').'</h2>
	
	<table>
	<tr><td>'.__('Display type', 'callback').':</td><td><input type="radio" name="type" value="modal" ' . $modal . ' />'.__('Modal', 'callback').'&nbsp;&nbsp;&nbsp;
	<input type="radio" name="type" value="toggle" ' . $toggle . ' />'.__('Toggle', 'callback').'&nbsp;&nbsp;&nbsp;
	<input type="radio" name="type" value="always" ' . $always . ' />'.__('Aways showing', 'callback').'</td></tr>
	<tr><td style="width:20%">'.__('Button (used on Modal and Toggle forms)','callback').':</td><td><input type="text" name="applylabel" value ="' . $settings['applylabel'] . '" /></td></tr>
	<tr><td>'.__('Completion Instructions','callback').':</td><td><input type="text" name="applyinstructions" value ="' . $settings['applyinstructions'] . '" /></td></tr>
	<tr><td>'.__('Submit button','callback').':</td><td><input type="text" name="submit" value ="' . $settings['submit'] . '" /></td></tr>
	<tr><td>'.__('Thankyou title','callback').':</td><td><input type="text" name="thankyoutitle" value ="' . $settings['thankyoutitle'] . '" /></td></tr>
	<tr><td>'.__('Thankyou message','callback').':</td><td><input type="text" name="thankyoublurb" value ="' . $settings['thankyoublurb'] . '" /></td></tr>
	<tr><td>'.__('Error message','callback').':</td><td><input type="text" name="errorlabel" value ="' . $settings['errorlabel'] . '" /></td></tr>
	<tr><td>'.__('Email Subject','callback').':</td><td><input type="text" name="notificationsubject" value ="' . $settings['notificationsubject'] . '" /></td></tr>
	<tr><td>'.__('Send to','callback').':</td><td><input type="text" name="sendto" value ="' . $settings['sendto'] . '" /></td></tr>
	</table>
	
	<p><input type="submit" name="SubmitSettings" class="button-primary" style="color: #FFF;" value="Update Settings" /> <input type="submit" name="ResetSettings" class="button-secondary" value="Reset" onclick="return window.confirm( \'Are you sure you want to reset the settings?\' );"/></p>
	
	</fieldset>';
	
	// Mailchimp
	$content .= '<fieldset style="border: 1px solid #888888;padding:10px;margin-top:10px;">
	
	<h2>UseMailchimp</h2>

	<p class="description">'.__('Note','callback').': '.__('You need a name and email to add a contact to a mailchimp list','callback').'.</p>
	
	<table>
	<tr><td style="width:20%">Key:</td><td><input type="text" name="mailchimpkey" value ="' . $mailinglist['mailchimpkey'] . '" /></td></tr>
	<tr><td>List ID:</td><td><input type="text" name="mailchimplistid" value ="' . $mailinglist['mailchimplistid'] . '" /></td></tr>
	</table>
	
	<p><input type="submit" name="SubmitMailchimp" class="button-primary" style="color: #FFF;" value="Update Mailchimp Settings" /> <input type="submit" name="ResetMailchimp" class="button-secondary" value="Reset" onclick="return window.confirm( \'Are you sure you want to reset the mailchimp settings?\' );"/></p>
	
	</fieldset>';
	
	// Reset tracking data
	$content .= '<fieldset style="border: 1px solid #888888;padding:10px;margin-top:10px;">
	
	<h2>'.__('Tracking','callback').'</h2>
	
	<p class="description">Tracking is shown on your dashboard homepage and shows the number of views and number of completions.</p>
	
	<p><input type="submit" name="resettracking" class="button-secondary" value="Reset Tracking" onclick="return window.confirm( \'Are you sure you want to reset the tracking?\' );"/></p>';
	
	$content .= wp_nonce_field("save_callback");
	
	$content .= '</form>
	</fieldset>
	</div>';
	
	$allowed_html = callback_allowed_html();
	
	echo wp_kses($content,$allowed_html);
}

// Admin notices
function callback_admin_notice($message) {
	$allowed_html = callback_allowed_html();
	if (!empty( $message)) echo wp_kses('<div class="updated"><p>'.$message.'</p></div>',$allowed_html);
}

function callback_allowed_html() {

	$allowed_tags = array(
		'style' => array(),
		'form' => array(
			'method' => array(),
			'action' => array(),
		),
		'fieldset' => array(
			'style' => array(),
		),
		'input' => array(
			'type'	=> array(),
			'name'	=> array(),
			'value'	=> array(),
			'checked'=> array(),
			'class' => array(),
			'style' => array()
		),
		'table' => array(),
		'tr' => array(),
		'th' => array(
			'style' => array(),
		),
		'td' => array(),
		'a' => array(
			'class' => array(),
			'href'  => array(),
			'rel'   => array(),
			'title' => array(),
		),
		'b' => array(),
		'code' => array(),
		'div' => array(
			'class' => array(),
			'title' => array(),
			'style' => array(),
		),
		'em' => array(),
		'h1' => array(),
		'h2' => array(),
		'h3' => array(),
		'h4' => array(),
		'h5' => array(),
		'h6' => array(),
		'i' => array(),
		'img' => array(
			'alt'    => array(),
			'class'  => array(),
			'height' => array(),
			'src'    => array(),
			'width'  => array(),
		),
		'li' => array(
			'class' => array(),
		),
		'ol' => array(
			'class' => array(),
		),
		'p' => array(
			'class' => array(),
		),
		'span' => array(
			'class' => array(),
			'title' => array(),
			'style' => array(),
		),
		'strong' => array(),
		'ul' => array(
			'class' => array(),
		),
	);
	
	return $allowed_tags;
}