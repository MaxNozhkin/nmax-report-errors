<?php
/*
Plugin Name: Nmax Report Errors
Description: Nmax Report Errors allows users to effortlessly notify site staff about found spelling errors.
Version: 1.0.0
Author: Maksym Nozhkin
License: MIT License
License URI: http://opensource.org/licenses/MIT
Text Domain: nmax-report-errors
Domain Path: /languages
*/

// exit if accessed directly
if(!defined('ABSPATH')) exit;
	
define('REPORT_ERROR_POST_TYPE', 'nmax-report-errors');

$nmax_report_errors = new Nmax_Report_Errors();

// load ajax-related class
if(defined('DOING_AJAX') && DOING_AJAX){
	require_once(__DIR__ . '/ajax.php');
	$nmax_report_errors_ajax = new Nmax_Report_Errors_Ajax();
}
// conditionally load admin-related class
elseif(is_admin()){
	require_once(__DIR__ . '/admin.php');
}
// or frontend class
else {
	require_once(__DIR__ . '/front.php');
	$nmax_report_errors_front = new Nmax_Report_Errors_Front();
}

class Nmax_Report_Errors {
	
	protected $defaults = array(
		'post_types' => array(),
		'custom_caption_text' => ''
	);
	protected $options = array();
	
	function __construct() {
		$this->options = array_merge($this->defaults, get_option('nmax_report_error_options', $this->defaults));
	}
	
}