<?php

class Nmax_Report_Errors_Setting extends Nmax_Report_Errors {
	
	public function __construct() {
		add_action('admin_init', array($this, 'register_settings'));
		add_action('admin_menu', array($this, 'admin_menu_options'));
		parent::__construct();
	}

	/**
	 * Add submenu
	 */
	public function admin_menu_options() {
		add_options_page(
			'Nmax Report Errors', 'Nmax Report Errors', 'manage_options', 'nmax_report_errors_settings', array($this, 'print_options_page')
		);
	}

	/**
	 * Options page output
	 */
	public function print_options_page() {
		?>
		<div class="wrap">
			<h2>Nmax Report Error</h2>
			<div>
				<form action="<?=admin_url('options.php')?>" method="post">
				<?php
					settings_fields('nmax_report_error_options');
					do_settings_sections('nmax_report_error_options');
				?>
					<p class="submit">
						<?php submit_button('', 'primary', 'save_nmax_report_error_options', false); ?>
					</p>
				</form>
			</div>
			<div class="clear"></div>
		</div>
		<?php
	}

	/**
	 * Regiseter plugin settings
	 */
	public function register_settings() {
		register_setting('nmax_report_error_options', 'nmax_report_error_options', array($this, 'validate_options'));

		add_settings_section('nmax_report_error_configuration', '', array(), 'nmax_report_error_options');
		add_settings_field('nmax_report_error_post_types', 'Post types', array($this, 'field_post_types'), 'nmax_report_error_options', 'nmax_report_error_configuration');
		add_settings_field('nmax_report_error_custom_caption_text', 'Caption text', array($this, 'field_custom_caption_text'), 'nmax_report_error_options', 'nmax_report_error_configuration');
		
		$this->post_types = $this->get_enabled_post_types();
	}

	/**
	 * Post types to show caption in
	 */
	public function field_post_types() {
		echo '<fieldset>';

		foreach($this->post_types as $value => $label){
			echo '
			<label><input id="nmax_report_error_post_type-' . $value . '" type="checkbox" name="nmax_report_error_options[post_types][' . $value . ']" value="1" '. checked(true, in_array($value, $this->options['post_types']), false) . ' />' . esc_html($label) . '</label><br />';
		}
		
		echo '</fieldset>';
	}

	/**
	 * Caption custom text field
	 */
	public function field_custom_caption_text() {
		echo '
		<fieldset>
			<textarea name="nmax_report_error_options[custom_caption_text]" cols="70" rows="4" />' . esc_textarea($this->options['custom_caption_text']) . '</textarea><br />
		</fieldset>';
	}

	/**
	* Validate options
	*
	* @param $input
	* @return mixed
    */
	public function validate_options($input) {

		if (!current_user_can('manage_options'))
			return $input;

		if(isset($_POST['save_nmax_report_error_options'])){

			// post types
			$input['post_types'] = isset($input['post_types']) && is_array($input['post_types']) && count(array_intersect(array_keys($input['post_types']), array_keys($this->post_types))) === count($input['post_types']) ? array_keys($input['post_types']) : array();

			// caption text mode
			$input['custom_caption_text'] = $input['custom_caption_text'] !== $this->default_caption_text ? wp_kses_post($input['custom_caption_text']) : '';

		}

		return $input;
	}

	/**
	 * Return an array of registered post types with their labels
	 */
	public function get_enabled_post_types() {
		$post_types = get_post_types(
			array(
				'public'  => true,
				'show_ui' => true
			),
			'objects'
		);
		$post_types_list = array();
		foreach($post_types as $id => $post_type) {
			$post_types_list[$id] = $post_type->label;
		}

		return $post_types_list;
	}
}