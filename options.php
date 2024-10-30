<?php

function callback_get_stored_settings () {
	
	$settings = get_option('callback_settings');
	if(!is_array($settings)) $settings = array();

	$default = array(
		'applylabel'				=> 'Request a Callback',
		'applyinstructions'			=> 'Please enter your details below.',
		'submit'					=> 'Submit',
		'thankyoutitle'				=> 'Thankyou [firstname]',
		'thankyoublurb'				=> 'Your callback request has been received. We will be in contact soon.',
		'errorlabel'				=> 'Please complete all marked fields',
		'notificationsubject'		=> 'New callback request for [yourname] on [sentdate]',
		'sendto'					=> get_bloginfo('admin_email'),
		'type'						=> 'modal'
	);
	
	$settings = array_merge($default, $settings);
	
	return $settings;
}

function callback_mailchimp () {
	
	$settings = get_option('mailinglist_settings');
	if(is_array($settings)) return $settings;
	
	$list = array(
		'mailchimpkey'				=> false,
		'mailchimplistid'			=> false,
	);
	return $list;
}

function callback_get_stored_fields() {
	
	$fields = get_option('callback_fields');
	if(is_array($fields)) return $fields;
	
	$default = array(
		'yourname'		=> array(
			'label'		=> 'Your Name',
			'caption'	=> 'Name',
			'use'		=> true,
			'type'		=> 'text',
			'required'	=> true,
			'js'		=> "function(obj){ return ((obj.value)? true: false); }"),
		'contact'		=> array(
			'label'		=> 'Email/Phone number',
			'caption'	=> 'Details',
			'use'		=> true,
			'type'		=> 'text',
			'required'	=> true,
			'js'		=> "function(obj){ return ((obj.value)? true: false); }"),
		'callwhen'			=> array(
			'label'		=> 'Call me ASAP,Later is fine',
			'caption'	=> 'Call',
			'use'		=> true,
			'type'		=> 'hidden',
			'required'	=> false,
			'js'		=> ""),
		'url'			=> array(
			'label'		=> 'Sent from',
			'caption'	=> 'Page',
			'use'		=> false,
			'type'		=> 'hidden',
			'required'	=> false,
			'js'		=> ""),
		'ip'			=> array(
			'label'		=> 'IP Address',
			'caption'	=> 'IP Address',
			'use'		=> false,
			'type'		=> 'hidden',
			'required'	=> false,
			'js'		=> ""),
		'sentdate'		=> array(
			'label'		=> 'Time/Date',
			'caption'	=> 'Sent at',
			'use'		=> false,
			'type'		=> 'hidden',
			'required'	=> false,
			'js'		=> ""),
	);
	
	return $default;
}