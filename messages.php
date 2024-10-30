<?php
callback_messages();

// Builds and manages the applications table
function callback_messages() {
	
	$callback_edit=$content=$current=$all=$callback_edit=false;
	$selected = array();
	
	$message = get_option('callback_messages');
	if(!is_array($message)) $message = array();
	
	$delete = array_reverse($message);
	
	// Delete callbacks
	for ($i=0; $i<count($delete); $i++) {
		if( isset($_POST['delete'.$i])) {
			unset($delete[$i]);
			$delete = array_values($delete);
			$delete = array_reverse($delete);
			update_option('callback_messages',$delete);
			callback_admin_notice('Callbacks have been updated');
		}
	}

	// Delete all applications
	if( isset( $_POST['callback_reset_message'])) {
		delete_option('callback_messages');
		callback_admin_notice('All callbacks have been deleted.');
	}

	// Send applications as email
	if( isset($_POST['callback_emaillist'])) {
		$fromemail = get_bloginfo('admin_email');
		$title = get_bloginfo('name');
		$message = get_option('callback_messages');
		$content = callback_build_callback_table ($message,false,false,false);
		$sendtoemail = sanitize_email($_POST['sendtoemail']);
		$headers = "From: ".$title." <".$fromemail.">\r\n"."Content-Type: text/html; charset=\"utf-8\"\r\n";	
		wp_mail($sendtoemail, 'Callback List', $content, $headers);
		callback_admin_notice('Callback list has been sent to '.$sendtoemail.'.');
	}
	
	$message = get_option('callback_messages');
	
	$current_user = wp_get_current_user();
	
	if (!isset($sendtoemail)) {
		$sendtoemail = $current_user->user_email;
	}

	if(!is_array($message)) $message = array();
	$dashboard = '<div class="wrap">
	<form method="post" id="callback_download_form" action="">
	<style>th,td{text-align:left;padding: 4px;border-bottom: 1px solid #ccc;}td input[type="text"]{min-height: 20px;padding:1px;margin:0;}</style>
	<h1>Callbacks</h1>';
	$content = callback_build_callback_table ($message,$callback_edit,$selected,true);
	if ($content) {
		$dashboard .= $content;
		
		$dashboard .='<p><input type="submit" name="callback_reset_message" class="button-secondary" value="Delete All Callbacks" onclick="return window.confirm( \'Are you sure you want to delete all the callbacks?\' );"/></p><p>Send callback list to this email address: <input type="text" name="sendtoemail" value="'.$sendtoemail.'">&nbsp;
		<input type="submit" name="callback_emaillist" class="button-primary" value="Email List" /></p>';
		
		$dashboard .= '<p><a href="https://tools.keycdn.com/geo">IP locator</a></p>';
		
		$dashboard .='</form>';
	} else {
		$dashboard .= '<p>There are no callbacks</p>';
	}
	
	$settings = callback_get_stored_settings();

	$dashboard .= '</div>';
	
	$allowed_html = callback_allowed_html();
	
	echo wp_kses($dashboard,$allowed_html);
}

// Build the table of callbacks
function callback_build_callback_table ($message,$callback_edit,$selected,$email) {
	
	$settings = callback_get_stored_settings();
	
	$content='';
	$delete=array();
	$i=0;
	
	$fields = callback_get_stored_fields();
	$arr = array_keys($fields);
	
	if (count($message) == 0) return;

	$dashboard = '<table cellspacing="0">
	<tr>';
	foreach ($arr as $item) {
		$label = $fields[$item]['caption'] ? $fields[$item]['caption'] : $fields[$item]['label'];
		$dashboard .= '<th>'.$label.'</th>';
	}
	$dashboard .= '<th></th></tr>';
	
	$message = array_reverse($message);

	foreach($message as $values) {

		$values['url'] = '<a href="'.get_permalink($values['url']).'" target="_blank">'.get_the_title($values['url']).'</a>';
		$values['ip']	='<a href="https://whatismyipaddress.com/ip/'.$values['ip'].'" target="_blank">'.$values['ip'].'</a>';
		
		$content .= '<tr>';
	
		foreach ($arr as $key) {
			$content .= '<td>'.$values[$key].'</td>';
		}
		
	if ($email) {
		$content .= '<td><input type="submit" name="delete'.$i.'" value="Delete" onclick="return window.confirm( \'Are you sure you want to delete the callback for '.$values['yourname'].'\' );"/></td>';
	}

	$content .= '</tr>';
		
	$i++;
	}

	$dashboard .= $content.'</table>';
	return $dashboard;
}