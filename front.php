<?php

class Nmax_Report_Errors_Front extends Nmax_Report_Errors {
	
	function __construct() {
		parent::__construct();
		add_action('wp_enqueue_scripts', array($this, 'front_load_scripts_styles'));
		add_filter('the_content', array($this, 'append_caption_to_content'));
	}

	/**
	 * Load scripts and styles - frontend
	 */
	public function front_load_scripts_styles() {
		if (!$this->is_appropriate_post())
			return false;

		wp_enqueue_script('nmax-report-errors-front', plugins_url('front.js', __FILE__), array('jquery'));
		
		$nonce = wp_create_nonce('nmax_report_errors');
		wp_localize_script(
			'nmax-report-errors-front', 'nmaxReportErrorsArgs', array(
				'ajaxurl' => admin_url('admin-ajax.php'),
				'nonce' => $nonce,
			)
		);
	}

	/**
	 * Add Nmax Report Error caption to post content
	 *
	 * @param $content
	 * @return string
	 */
	public function append_caption_to_content($content) {
		if (!$this->is_appropriate_post())
			return $content;
		
		return $content . $this->options['custom_caption_text'];
	}
	
	/**
	 * Check post type of the page
	 *
	 * @return bool
	 */
	public function is_appropriate_post() {
		if((is_single() && in_array(get_post_type(), $this->options['post_types']))
		    || (is_page() && in_array('page', $this->options['post_types'])))
		{
			return true;
		}

		return false;
	}
}