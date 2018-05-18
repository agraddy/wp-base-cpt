<?php
namespace agraddy\base;
use agraddy\base\Column;

class Type {
	public $args;
	public $cap;
	public $custom_title;
	public $key;
	public $plural;
	public $singular;

	public $full_key;

	public $associates = [];
	public $supports = [];
	public $details = [];

	public $column;
	public $codes = [];
	public $elements = [];
	public $names = [];
	public $titles = [];
	public $values = [];

	function __construct($key, $singular, $plural, $cap, $args) {
		$this->key = $key;
		$this->singular = $singular;
		$this->plural = $plural;
		$this->cap = $cap;
		$this->args = $args;

		$this->full_key = $key . '_' . $this->parseToKey($singular);

		$this->column = new Column($this->full_key);
	}

	function add($type, $title = '', $key = '', $extra = null) {
		if(
			$type == 'editor'
			|| $type == 'title'
			|| $type == 'author'
		) {
			array_push($this->supports, $type);
		} else {
			if(!$key) {
				$key = $this->parseToKey($title);
			}
			$temp = new \stdClass();
			$temp->type = $type;
			$temp->title = $title;
			$temp->key = $key;
			$temp->extra = $extra;
			array_push($this->details, $temp);
		}
		if($type == 'title') {
			$this->custom_title = $title;
		}

	}

	function init() {
		// Create Custom Post Type

		add_action('init', array($this, 'wpInit'));
	}

	function parseToKey($input) {   
		$output = $input;               
		$output = strtolower($output);  
		$output = str_replace(' ', '_', $output); 
		return $output;
	}

	function wpInit() {
		$capabilities =  array(
			'edit_post'          => $this->cap, 
			'read_post'          => $this->cap, 
			'delete_posts'        => $this->cap,
			'edit_posts'         => $this->cap,
			'edit_others_posts'  => $this->cap,
			'publish_posts'      => $this->cap,
			'read_private_posts' => $this->cap,
			'create_posts'       => $this->cap,
			'delete_post'        => $this->cap,
		);  
		$labels = array(
			'name'               => __( $this->plural ),
			'singular_name'      => __( $this->singular ),
			'add_new'            => __( 'Add New' ),
			'add_new_item'       => __( 'Add New ' . $this->singular ),
			'edit_item'          => __( 'Edit ' . $this->singular ),
			'new_item'           => __( 'New ' . $this->singular ),
			'all_items'          => __( $this->plural ),
			'view_item'          => __( 'View ' . $this->singular ),
			'search_items'       => __( 'Search ' . $this->plural ),
			'not_found'          => __( 'No ' . $this->plural . ' found' ),
			'not_found_in_trash' => __( 'No ' . $this->plural . ' found in the Trash' ),
			'parent_item_colon'  => '',
			'menu_name'          => $this->plural
		);

		if(count($this->supports) == 0) {
			$this->supports = false;
		}

		$args = array(
			'labels'        => $labels,
			'description'   => 'A list of ' . $this->plural . '.',
			'supports'      => $this->supports,
			'public' => false,
			'show_ui' => true,
			'show_in_menu' => true,
			'capabilities' => $capabilities,
			'hierarchical' => true,
			'register_meta_box_cb' => array($this, 'wpMetaBox')
		);
		$args = array_merge($args, $this->args);
		register_post_type($this->full_key, $args );

		add_filter('enter_title_here', array($this, 'wpTitleHere'), 10, 2);
		add_action('save_post_' . $this->full_key, array($this, 'wpSave'));
	}

	function wpDetails() {
		global $post;

		$html = '';

		for($i = 0; $i < count($this->details); $i++) {
			$item = $this->details[$i];
			if(!$item->extra) {
				$item->extra = '';
			}
			$value = get_post_meta( $post->ID, $item->key, true );
			if($item->type == 'end_group') {
				$html .= '</p>';
			} elseif($item->type == 'group') {
				$html .= '<div>';
				$html .= '<label>' . esc_html($item->title) . '</label>';
			} elseif($item->type == 'hidden') {
				if(!$value) {
					$value = 0;
				}
				$html .= '<input type="hidden" name="' . esc_attr($item->title) . '" value="' . esc_attr($value) . '">';
			} elseif($item->type == 'radio') {
				$html .= '<div>';
				$html .= '<label><input type="radio" name="' . esc_attr($item->key) . '" value="' . esc_attr($item->extra) . '"';
				if($item->extra == $value) {
					$html .= ' checked';
				}
				$html .= '>' . esc_html($item->title) . '</label>';
				$html .= '</div>';
			} elseif($item->type == 'text') {
				$html .= '<p>';
				$html .= '<label>' . esc_html($item->title) . '</label>';
				$html .= '<input class="widefat" type="text" name="' . esc_attr($item->key) . '" value="' . esc_attr($value) . '" placeholder="' . esc_attr($item->extra) . '">';
				$html .= '</p>';
			} elseif($item->type == 'select_user') {
				$html .= '<div>';
				$html .= '<label>' . esc_html($item->title) . '</label><br>';
				$html .= wp_dropdown_pages(array(
						'show_option_none' => __( 'Please Select...' ),
						'post_type'=> substr($item->type, 7),
						'name' => $item->key,             
						'echo' => 0,                    
						'selected' => $value
					));  
				$html .= '<br>';
				$html .= '<br>';
				$html .= '</div>';
			} elseif(strpos($item->type, 'select_') === 0) {
				$html .= '<div>';
				$html .= '<label>' . esc_html($item->title) . '</label><br>';
				$html .= wp_dropdown_pages(array(
						'show_option_none' => __( 'Please Select...' ),
						'post_type'=> substr($item->type, 7),
						'name' => $item->key,             
						'echo' => 0,                    
						'selected' => $value
					));  
				$html .= '<br>';
				$html .= '<br>';
				$html .= '</div>';
			}
		}

		echo $html;


		/*
		$data = array();

		$data['domain'] = get_post_meta( $post->ID, 'domain', true );
		$data['email'] = get_post_meta( $post->ID, 'email', true );
		$data['phone'] = get_post_meta( $post->ID, 'phone', true );

		$selected = get_post_meta( $post->ID, 'page_id', true );
		$data['pages'] = wp_dropdown_pages(array(
			'show_option_none' => __( 'Please Select...' ),
			'post_type'=> 'page',
			'name' => 'page_id',
			'echo' => 0,
			'selected' => $selected
		));

		echo $this->show('meta_box_domain_details', $data);
		 */
	}

	function wpMetaBox() {
		add_meta_box(
			$this->full_key . '_details',
			'Details',
			array($this, 'wpDetails'),
			$this->full_key,
			'normal',
			'default'
		);
	}

	function wpSave($post_id) {
		global $wpdb;
		if(!empty($_POST)) {
			$found = [];
			for($i = 0; $i < count($this->details); $i++) {
				$item = $this->details[$i];
				if(
					isset($_POST[$item->key]) 
					&& (
						$item->type == 'hidden'
						|| $item->type == 'text'
						|| strpos($item->type, 'select_') === 0
					)
				) {
					update_post_meta($post_id, $item->key, sanitize_text_field( $_POST[$item->key]));
				} elseif(isset($_POST[$item->key]) && $item->type == 'radio' && !in_array($item->key, $found)) {
					update_post_meta($post_id, $item->key, sanitize_text_field( $_POST[$item->key]));
					// Make sure only checks once
					array_push($found, $item->key);
				}
			}

			for($i = 0; $i < count($this->associates); $i++) {
				$item = $this->associates[$i];
				if($item->extra[0] == 'get_the_title') {
					$title = get_the_title(get_post_meta($post_id, $item->extra[1], true));
					$where = array( 'ID' => $post_id );
					$wpdb->update( $wpdb->posts, array( 'post_title' => $title ), $where );
				}
			}
		}
	}

	function wpTitleHere($title , $post){
		if($post->post_type == $this->full_key) {
			$title = $this->custom_title;
		}
		return $title;
	}

}

?>
