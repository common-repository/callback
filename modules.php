<?php

// Builds modal
function callback_display_form($settings,$fields) {
	
	$callwhen = explode(',',$fields['callwhen']['label']);
	$when=0;
	
	$content = '<div class="modal-content">
		<div class="modal-message">';
			if ($settings['type'] == 'modal') $content .= '<span class="closemodal">&times;</span>';
			$content .= '<div class="thankyoutitle"></div>
			<div class="thankyoublurb"></div>
		</div>
		<div class="modal-form">';
			if ($settings['type'] == 'always') $content .= '<h2>'.$settings['applylabel'].'</h2>';
			$content .= '<p id="instructions">'.$settings['applyinstructions'].'</p>
			<p id="error">'.$settings['errorlabel'].'</p>';
			if ($fields['yourname']['use']) $content .= '<div class="inputlabel">'.$fields['yourname']['label'].'</div><div class="forminput"><input id="yourname" class="required" name="yourname" type="text" value="" /></div>'."\n";
			if ($fields['contact']['use']) $content .= '<div class="inputlabel">'.$fields['contact']['label'].'</div><div class="forminput"><input id="contact" class="required" name="contact" type="text" value="" /></div>'."\n";
			if ($fields['callwhen']['use']) {
				$content .= '<div id="callwhen">';
				foreach ($callwhen as $item) {
					$content .= '<input type="radio" name="callwhen" value="'.$item.'" id="callwhen'.$when.'"><label for="callwhen'.$when.'"><span></span>'.$item.'</label>';
					$when++;
				}
			}
			$content .= '</div>';
			$content .= '<div class="validator">Enter the word YES in the box: <input type="text" style="width:3em" name="validator" value=""></div>';
			$content .= '<div class="buttons"><input type="submit" name="submit" class="submit action-button" value="'.$settings['submit'].'" /></div>
			<div class="buttons_working"><div class="working_loading"></div></div>
		</div>
	</div>';
	
	return $content;
	
}

// Replaces shortcodes
function callback_do_replace($subject, $array) {

	$keys = array_keys($array);
	
	foreach ($keys as $key) {

		$subject = str_replace('['.$key.']', $array[$key], $subject);
	}

	return $subject;
	
}

// Form opened
function callback_ajax_track() {

	$settings	= callback_get_stored_settings();
	
	// Updates tracking
	$track = get_option('callback_track');
	$track['opened']++;
	update_option('callback_track',$track);

	$return['success'] = true;
	
	echo json_encode($return);
	
	die(0);
	
}

// Submits the form
function callback_ajax_submit() {
	
	global $post;
	
	if ($_POST['validator']) die();
	if(!callback_spawnSecure($_POST['anything'])) die();
	
	// Configures the message data
	$_POST['sentdate']		= sanitize_text_field(date_i18n('H:i d M Y'));
	$_POST['url']			= sanitize_text_field(url_to_postid(wp_get_referer()));
	$_POST['ip']			= sanitize_text_field($_SERVER['REMOTE_ADDR']);
	$settings				= callback_get_stored_settings();
	$fields					= callback_get_stored_fields();
	$mailinglist			= callback_mailchimp();
	$callback_messages		= get_option('callback_messages');
	if(!is_array($callback_messages)) $callback_messages = array();

	$return				= ['success' => false,'title' => '', 'message' => ''];
	
	// Updates tracking
	$track = get_option('callback_track');
	$track['completed']++;
	update_option('callback_track',$track);
	
	// Validates and saves data to DB
	$arr					= array_keys($fields);
	foreach ($arr as $key) {
		$log[$key] = sanitize_text_field($_POST[$key]);
	}
	$callback_messages[]	= $log;
	update_option('callback_messages',$callback_messages);
	
	// Mailchimp
	if (str_contains($log['contact'], '@') && $mailinglist['mailchimpkey']) {
		CBF\subscribe($log['contact'],$log['yourname']);
	}

	// Build the message
	$content = callback_build_message ($log);
	
	// Sends the message
	callback_send_notification ($log,$content);
	
	$firstname = explode(' ', $log['yourname']);
	$log['firstname'] = $firstname[0];

	// Updates JS
	$return['success']		= true;
	$return['title']		= callback_do_replace($settings['thankyoutitle'],$log);
	$return['message']		= callback_do_replace($settings['thankyoublurb'],$log);
	
	echo json_encode($return);
	
	die(0);
	
}

// Check speed of completion
function callback_spawnSecure($var) {
	$spawn = trim(stripslashes($var)); $now = date('Y-m-d H:i:s'); $diff = strtotime($now) - strtotime($spawn);
	if($diff<=1) { return false; } else { return true; }
}

// Send Notification
function callback_send_notification ($values,$content) {
	
	global $post;

	$settings 		= callback_get_stored_settings();
	
	$fromname		= get_bloginfo('name');
	$fromemail		= get_bloginfo('admin_email');
	$callback_email = $settings['sendto'];
	
	$subject		= callback_do_replace($settings['notificationsubject'],$values);
	
	$message .= $content;
	
	$headers = "From: ".$fromname." <".$fromemail.">\r\n"
	. "Content-Type: text/html; charset=\"utf-8\"\r\n";
	$message = '<html>'.$message.'</html>';
	
	//wp_mail($values['contact'],$settings['notificationsubject'],$message, $headers);
	
	wp_mail($callback_email,$subject,$message, $headers);
	
}

// Builds email message
function callback_build_message ($values) {
	
	$fields		= callback_get_stored_fields();
	$arr		= array_keys($fields);
	
	$values['url'] = '<a href="'.get_permalink($values['url']).'" target="_blank">'.get_the_title($values['url']).'</a>';
	$values['ip']	='<a href="https://whatismyipaddress.com/ip/'.$values['ip'].'" target="_blank">'.$values['ip'].'</a>';
	
	$message = '<table>';
	foreach ($arr as $key) {
		$label = $fields[$key]['caption'] ? $fields[$key]['caption'] : $fields[$key]['label'];
		if ($values[$key]) $message .= '<tr><td>'.$label.':</td><td>'.$values[$key].'</td></tr>';
	}
	$message .= '</table>';
	
	return $message;
}

// Tracking widget
function callback_add_dashboard_widgets() {
	
	wp_add_dashboard_widget(
		'callback_dashboard_widget',						  // Widget slug.
		esc_html__( 'Callback Tracking', 'callback' ), // Title.
		'callback_dashboard_widget_render'					// Display function.
	);
}

// Tracking widget content
function callback_dashboard_widget_render() {
	
	$track	= get_option('callback_track');
	
	$allowed_html = callback_allowed_html();

	if ($track) {
		if (!isset($track['completed'])) $track['completed'] = 0;
		if (!isset($track['opened'])) $track['opened'] = 0;
		if (!isset($track['applied'])) $track['applied'] = 0;

		echo wp_kses('<div style="text-align:center;width:33%;float:left"><div>Visitors</div>
		<div style="font-size:30px;text-align:center;">'.$track['visitors'].'</div></div>',$allowed_html);
		
		echo wp_kses('<div style="text-align:center;width:33%;float:left"><div>Opened</div>
		<div style="font-size:30px;text-align:center;">'.$track['opened'].'</div>',$allowed_html);
		if ($track['opened'] > 0) {
			$percent = ($track['opened'] / $track['visitors']) * 100;
			echo round($percent, 1).'%';
		}
		echo wp_kses('</div>',$allowed_html);

		echo wp_kses('<div style="text-align:center;width:33%;float:left"><div>Completed</div>
		<div style="font-size:30px;text-align:center;">'.$track['completed'].'</div>',$allowed_html);
		if ($track['completed'] > 0) {
			$percent = ($track['completed'] / $track['opened']) * 100;
			echo round($percent, 1).'%';
		}
		echo wp_kses('</div>',$allowed_html);

		echo wp_kses('<div style="clear:both"></div>',$allowed_html);
		
	} else {
		echo wp_kses('<p>No tracking data available</p>',$allowed_html);
	}

}