<?php

if (!class_exists('Nmax_WP_List_Table')) {
	require_once __DIR__ . '/nmax-wp-list-table.php';
}

class report_Error_Table extends Nmax_WP_List_Table {

	public $per_page = 15;
	public $base_url;
	public $counts;
	public $report_error;
	public $statuses = array(
		'pending' => 'Новая',
		'closed' => 'Закрытая',
		'trash' => 'Удаленная'
	);
	public $results = array();
	public $last_action;
	public $query_string;
	
	/**
	 * Construct
	 */
	public function __construct() {
		global $status, $page;
		
		$this->query_string = remove_query_arg(array('action'));
		$this->process_bulk_action();
		$this->process_quicklink_action();
		$this->get_counts();
		$this->report_error_data();
		$this->base_url = admin_url('admin.php?page=' . REPORT_ERROR_POST_TYPE);
	}

	/**
	 * Retrieve the view types
	 */
	public function get_views() {
		$current = isset($_GET['status']) ? $_GET['status'] : '';

		$views = array(
			'pending' => sprintf('<a href="%s"%s>%s</a>',
				add_query_arg(array('status' => 'pending', 'paged' => FALSE), $this->query_string),
				$current === 'pending' || $current == '' ? ' class="current"' : '',
				'Открытые' . ' <span class="count">(' . $this->counts['pending'] . ')</span>'
			),
			'closed' => sprintf('<a href="%s"%s>%s</a>',
				add_query_arg(array('status' => 'closed', 'paged' => FALSE), $this->query_string),
				$current === 'closed' ? ' class="current"' : '',
				'Закрытые' . ' <span class="count">(' . $this->counts['closed'] . ')</span>'
			),
			'trash' => sprintf('<a href="%s"%s>%s</a>',
				add_query_arg(array('status' => 'trash', 'paged' => FALSE), $this->query_string),
				$current === 'trash' ? ' class="current"' : '',
				'Удаленные' . ' <span class="count">(' . $this->counts['trash'] . ')</span>'
			)
		);

		return $views;
	}

	/**
	 * Generates content for a single row of the table
	 */
	function single_row($item) {
		static $row_class = '';
		$row_class = $row_class == '' ? 'alternate' : '';

		echo '<tr class="' . esc_attr($item->post_status);
		echo $row_class == '' ? '' : ' ' . $row_class;
		echo '">';
		$this->single_row_columns($item);
		echo '</tr>';
	}

	/**
	 * Retrieve the table columns
	 */
	public function get_columns() {
		$columns = array(
			'cb'        => '<input type="checkbox" />',
			'date'     	=> 'Дата',
			'text'  	=> 'Выделенный текст',
			'full_text' => 'Контекст',
			'author'	=> 'Автор',
			'status'  	=> 'Статус'
		);

		return $columns;
	}

	/**
	 * Retrieve the table's sortable columns
	 */
	public function get_sortable_columns() {
		$columns = array(
			'date' => array('date', true),
		);
		return $columns;
	}

	/**
	 * This function renders most of the columns in the list table.
	 */
	public function column_default($report_error, $column_name){
		switch ($column_name){
			case 'date' :
				$value = $report_error->post_date;
				break;
			case 'text' :
				$value = '<a href=' . get_edit_post_link($report_error->post_parent) . '>' . $report_error->post_title . '</a>';
				break;
			case 'full_text' :
				$value = $report_error->post_content;
				break;
			case 'author' :
				$post_author_id = get_post_field('post_author', $report_error->post_parent);
				$value = get_the_author_meta('first_name', $post_author_id) . ' ' . get_the_author_meta('last_name', $post_author_id);
				break;
			case 'status' :
				$value = $this->statuses[$report_error->post_status];
				break;
			default:
				$value = isset($report_error->$column_name) ? $report_error->$column_name : '';
				break;

		}

		return $value;
	}

	/**
	 * Render the checkbox column
	 */
	public function column_cb($report_error) {
		return sprintf(
			'<input type="checkbox" name="%1$s[]" value="%2$s" />',
			'report_errors',
			$report_error->ID
		);
	}

	/**
	 * Retrieve the bulk actions
	 */
	public function get_bulk_actions() {
		$actions = array(
			'set-status-pending'     => 'Пометить как новая',
			'set-status-closed'      => 'Закрыть',
			'delete'                 => 'Удалить',
		);

		return $actions;
	}

	/**
	 * Process the bulk actions
	 */
	public function process_bulk_action() {
		$ids    = isset($_POST['report_errors']) ? $_POST['report_errors'] : false;
		$action = isset($_POST['action']) ? $_POST['action'] : false;

		if(empty($action) || $action == '-1') {
			return;
		}

		$ids = is_array($ids) ? $ids : array($ids);

		$results = array();
		foreach($ids as $id) {
			switch($action){
				case 'delete':
					$results[$id] = $this->delete_report_error($id);
					break;
				case 'set-status-pending':
					$results[$id] = $this->update_report_error_status($id, 'pending');
					break;
				case 'set-status-closed':
					$results[$id] = $this->update_report_error_status($id, 'closed');
					break;
			}			
		}

		if(count($results)) {
			$this->results = $results;
			$this->last_action = $action;
			$this->admin_notice_bulk_actions();
		}
	}

	/**
	 * Display an admin notice when a bulk action is completed
	 */
	public function admin_notice_bulk_actions() {
		$success = 0;
		$failure = 0;

		foreach($this->results as $id => $result) {
			if ($result === true || $result === null || $result == 1) {
				$success++;
			} else {
				$failure++;
			}
		}

		if ($success > 0) : ?>
		<div id="rtb-admin-notice-bulk-<?=esc_attr($this->last_action)?>" class="updated">
			<?php if($this->last_action == 'delete') : ?>
			<p><?='Успешно удалено. Количество: ' . $success?></p>
			<?php elseif($this->last_action == 'set-status-pending') : ?>
			<p><?='Статус успешно изменен на "Новый". Количество: ' . $success?></p>
			<?php elseif($this->last_action == 'set-status-closed') : ?>
			<p><?='Статус успешно изменен на "Закрыт". Количество: ' . $success?></p>
			<?php endif; ?>
		</div>
		<?php endif;

		if ($failure > 0) : ?>
		<div class="error">
			<p><?='Произошла ошибка. Количество: ' . $failure?></p>
		</div>
		<?php endif;
	}

	/**
	 * Retrieve the counts of report errors
	 */
	public function get_counts() {
		global $wpdb;

		$query = "SELECT p.post_status,count(*) AS num_posts
			FROM $wpdb->posts p
			WHERE p.post_type = '" . REPORT_ERROR_POST_TYPE . "'
			GROUP BY p.post_status
		";

		$count = $wpdb->get_results($query, ARRAY_A);

		$this->counts = array();
		foreach(get_post_stati() as $state) {
			$this->counts[$state] = 0;
		}

		$this->counts['total'] = 0;
		foreach((array)$count as $row) {
			$this->counts[$row['post_status']] = $row['num_posts'];
			if($row['post_status'] !== 'trash') $this->counts['total'] += $row['num_posts'];
		}

	}

	/**
	 * Retrieve all the data for all the report errors
	 */
	public function report_error_data() {
		$args = array(
			'post_type'			=> REPORT_ERROR_POST_TYPE,
			'posts_per_page'	=> $this->per_page,
			'paged'				=> isset($_GET['paged']) ? $_GET['paged'] : 1,
			'post_status'		=> isset($_GET['status']) ? $_GET['status'] : array('pending'),
		);
		
		if (isset($_GET['order'])) {
			$args['order'] = isset($_GET['order']);
		}
		
		if (isset($_GET['orderby'])) {
			$args['orderby'] = $_GET['orderby'];
		} else {
			$args['orderby'] = 'date';
			$args['order'] = 'DESC';
		}		

		$query = new WP_Query($args);

		if($query->have_posts()) {
			while($query->have_posts()) {
				$query->the_post();
				$this->report_error[] = $query->post;
			}
		}
	}

	/**
	 * Setup the final data for the table
	 */
	public function prepare_items() {
		$columns  = $this->get_columns();
		$hidden   = array();
		$sortable = $this->get_sortable_columns();

		$this->_column_headers = array( $columns, $hidden, $sortable );

		$this->items = $this->report_error;

		$total_items = empty($_GET['status']) ? $this->counts['total'] : $this->counts[$_GET['status']];

		$this->set_pagination_args(array(
				'total_items' => $total_items,
				'per_page'    => $this->per_page,
				'total_pages' => ceil($total_items / $this->per_page)
			)
		);
	}
	
	
	/**
	 * Delete a report error request
	 *
	 */
	public function delete_report_error($id) {
		$screen = get_current_screen();
		$result = wp_trash_post($id);

		if($result === false) {
			return false;
		} else {
			return true;
		}
	}

	/**
	 * Update a report error status.
	 */
	function update_report_error_status($id, $status) {
		
		if (!$this->is_valid_status($status)) {
			return false;
		}

		$report_error = get_post($id);

		if (is_wp_error($report_error) || !is_object($report_error)) {
			return false;
		}

		if ($report_error->post_status === $status ) {
			return null;
		}

		$result = wp_update_post(
			array(
				'ID'			=> $id,
				'post_status'	=> $status,
				'edit_date'		=> current_time('Y-m-d H:i:s'),
			)
		);
		
		return $result ? true : false;
	}

	/**
	 * Check if status is valid
	 */
	public function is_valid_status( $status ) {
		return array_key_exists($status, $this->statuses) ? true : false;
	}
}