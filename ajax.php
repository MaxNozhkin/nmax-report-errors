<?php

class Nmax_Report_Errors_Ajax {
	
	public $url;
	public $post_id;
	public $context;
	public $reported_text;
	protected $nonce;
	
	/**
	 * Constructor
	 */
	public function __construct() {		
		add_action('wp_ajax_nmax_report_errors', array($this, 'ajax_handlers'));
		add_action('wp_ajax_nopriv_nmax_report_errors', array($this, 'ajax_handlers'));		
	}


	/**
	 * Handle AJAX reports
	 */
	public function ajax_handlers() {
		$result = false;
		
		$this->context = isset($_POST['context']) ? $_POST['context'] : null;
		$this->reported_text = isset($_POST['reported_text']) ? $_POST['reported_text'] : null;
		$this->nonce = isset($_POST['nonce']) ? $_POST['nonce'] : null;
				
		if ($this->nonce &&
		    $this->reported_text &&
		    $this->context &&
		    wp_verify_nonce($this->nonce, 'nmax_report_errors')
		) {
			// check transients for repeated reports from IP
			$trans_name_short = 'report_error_short_ip_' . $_SERVER['REMOTE_ADDR'];
			$trans_name_long = 'report_error_long_ip_' . $_SERVER['REMOTE_ADDR'];
			$trans_5min = get_transient($trans_name_short);
			$trans_30min = get_transient($trans_name_long);
			$trans_5min = is_numeric($trans_5min) ? (int)$trans_5min : 0;
			$trans_30min = is_numeric($trans_30min) ? (int)$trans_30min : 0;
			
			if ($trans_5min < 5 && $trans_30min < 30) {
				$trans_5min++;
				$trans_30min++;
			
				set_transient($trans_name_short, $trans_5min, 300);
				set_transient($trans_name_long,  $trans_30min, 1800);
				
				if(strstr($this->context, $this->reported_text) !== false){
					$this->context = str_replace($this->reported_text, '<strong style="color: red;">' . $this->reported_text . '</strong>', $this->context);
				}
		
				$this->url = wp_get_referer();
				$this->post_id = url_to_postid($this->url);
							
				$args = array(
					'post_type'		=> REPORT_ERROR_POST_TYPE,
					'post_title'	=> $this->reported_text,
					'post_content'	=> $this->context,
					'post_date'		=> current_time('Y-m-d H:i:s'),
					'post_status'	=> 'pending',
					'post_parent'	=> $this->post_id
				);
			
				$id = wp_insert_post($args);
							
				// send email
				$mail = $this->send_mail();
				
				if($mail && !is_wp_error($id) && $id !== false){
					$result = true;
				}			
			}
		}

		$response = json_encode($result);

		die($response);
	}
	
	public function send_mail(){
		$post_author_id = get_post_field('post_author', $this->post_id);
		$to = get_the_author_meta('user_email', $post_author_id);
		$edit_post_url = get_edit_post_link($this->post_id);
		$subject = 'Сообщение об ошибке';

		$message = '<p>Отправлено со страницы: ';
		$message .= !empty($this->url) ? '<a href="' . $this->url . '">' . urldecode($this->url) . '</a>' : 'Неизвестно';
		$message .= "</p>\n";
		$message .= !empty($this->url) ? '<p>Редактировать пост: <a href="' . $edit_post_url . '">' . $edit_post_url . '</a></p>' . "\n" : '';
		$message .= "<h3>Выделенный текст:</h3>\n";
		$message .= '<code>' . $this->reported_text . "</code>\n";
		$message .= "<h3>Контекст:</h3>\n";
		$message .= '<code>' . $this->context . "</code>\n";

		$headers = array('Content-Type: text/html; charset=UTF-8');
		
		return @wp_mail($to, $subject, $message, $headers);
	}
	
}