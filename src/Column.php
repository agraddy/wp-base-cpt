<?php
namespace agraddy\base;

class Column {
	public $columns = [];
	public $full_key;

	function __construct($full_key) {
		$this->full_key = $full_key;
		add_action('init', array($this, 'wpInit'));
	}

	function add($key, $title = '') {
		$temp = new \stdClass();

		if($key == 'cb' && $title == '') {
			$temp->key = $key;
			$temp->title = '<input type="checkbox" />';
		} else {
			$temp->key = $key;
			$temp->title = $title;
		}

		array_push($this->columns, $temp);
	}

	function parseToKey($input) {   
		$output = $input;               
		$output = strtolower($output);  
		$output = str_replace(' ', '_', $output); 
		return $output;
	}

        // Remove date filter
        function wpAdminInit() {
                global $typenow;
                if(
                        $typenow == $this->full_key
                ) {
                        add_filter('months_dropdown_results', '__return_empty_array');
                }
        }

	function wpColumns() {
		$columns = [];

		for($i = 0; $i < count($this->columns); $i++) {
			$column = $this->columns[$i];
			$columns[$column->key] = $column->title;
		}

		return $columns;
	}

	function wpColumnContent($column, $post_id) {
		if(
			$column != 'cb'
			&& $column != 'title'
		) {
			echo get_post_meta( $post_id, $column, true );
		}
	}

	function wpInit() {
		add_filter('manage_edit-' . $this->full_key . '_columns', array($this, 'wpColumns')) ;
		add_action('manage_' . $this->full_key . '_posts_custom_column', array($this, 'wpColumnContent'), 10, 2 );

		// Remove hover edits
		// From: https://wordpress.stackexchange.com/a/14982
		add_filter('post_row_actions', array($this, 'wpPostRowActions'), 10, 2);
		add_filter('page_row_actions', array($this, 'wpPostRowActions'), 10, 2);

		// Remove date filters (and other filters) as needed
		add_action('admin_init', array($this, 'wpAdminInit'));

		// Redirect after save
		//add_filter('redirect_post_location', array($this, 'wpRedirectSave'));

		// Remove filter views like All(1) and Published(1)
		add_filter( 'views_edit-' . $this->full_key, '__return_null' );
	}

        // Remove hover edit
        function wpPostRowActions($actions, $post) {
                if(
                        $post->post_type == $this->full_key
                ) {
                        unset($actions['edit']);
                        unset($actions['trash']);
                        $actions['inline hide-if-no-js'] = '';
                } elseif(
                        $post->post_type == $this->full_key
                ) {
                        unset($actions['edit']);
                        unset($actions['trash']);
                        unset($actions['inline hide-if-no-js']);
                }
                return $actions;
        }

        // Inspired by: https://gist.github.com/davejamesmiller/1966595
	/*
        function wpRedirectSave($location) {
                global $post;
                if($post->post_type == $this->full_key) {
                        $url = 'edit.php?post_type=' . $this->full_key;
                        $location = get_admin_url(null, $url);
                        return $location;
                } else {
                        return $location;
                }
        }
	 */
}



?>
