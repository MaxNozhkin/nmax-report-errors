<?php
require_once(__DIR__ . '/admin-page.php');
require_once(__DIR__ . '/admin-settings.php');

$nmax_report_errors_settings = new Nmax_Report_Errors_Setting;

/**
* Init
*/
add_action('init', 'load_cpts_report_error');
add_action('init', 'set_report_error_statuses');
add_action('admin_menu', 'add_report_error_menu_page');

/**
 * Initialize custom post types
 */
function load_cpts_report_error() {
	$args = array(
		'labels' => array(
			'name'               => 'Report errors',                  
			'singular_name'      => 'Report error',                   
			'menu_name'          => 'Report errors',                  
			'name_admin_bar'     => 'Report errors',                  
			'add_new'            => 'Add New',                 	 
			'add_new_item'       => 'Add New Report error',           
			'edit_item'          => 'Edit Report error',              
			'new_item'           => 'New Report error',               
			'view_item'          => 'View Report error',              
			'search_items'       => 'Search Report error',           
			'not_found'          => 'No eeport errors found',
			'not_found_in_deleted' => 'No report errors found in deleted',
			'all_items'          => 'All Report errors',              
		),
		'menu_icon' => 'dashicons-calendar',
		'public' => false,
		'supports' => array(
			'title',
			'revisions'
		)
	);

	register_post_type(REPORT_ERROR_POST_TYPE, $args);
}

/**
 * Set custom statuses for report error
 */
function set_report_error_statuses() {
	$statuses['pending'] = array(
		'label'						=> 'Pending',
		'default'					=> true,
		'user_selectable'			=> true,
	);

	$statuses['closed'] = array(
		'label'                     => 'Closed',
		'default'					=> false,
		'user_selectable'			=> true,
		'public'                    => false,
		'exclude_from_search'       => true,
		'show_in_admin_all_list'    => true,
		'show_in_admin_status_list' => true,
	);

	foreach($statuses as $status => $args){
		if($args['default'] === false){
			register_post_status($status, $args);
		}
	}
}

/**
 * Add menu page
 */		
function add_report_error_menu_page() {
	global $menu; 
	$position = 46;
	
	add_menu_page(
		'Report errors',
		'Ошибки',
		'manage_options',
		'nmax_report_errors',
		'show_admin_report_error_page',
		'dashicons-calendar',
		$position
	);
	 
    $newitem = wp_count_posts(REPORT_ERROR_POST_TYPE)->pending;
    $menu[$position][0] .= $newitem ? "<span class='update-plugins count-1'><span class='update-count'>$newitem </span></span>" : '';
}
  
/**
 * Show menu page
 */
function show_admin_report_error_page() {
	$table = new report_Error_Table();
	$table->prepare_items();
	?>
	<div class="wrap">
		<h2>Ошибки</h2>
		<form id="nmax_report_errors-table" method="POST" action="">
			<input type="hidden" name="post_type" value="<?=REPORT_ERROR_POST_TYPE?>" />
			<input type="hidden" name="page" value="nmax_report_errors">
			<?php $table->views(); ?>
			<?php $table->advanced_filters(); ?>
			<?php $table->display(); ?>
		</form>
	</div>
	<?php
}