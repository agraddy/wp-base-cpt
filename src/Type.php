<?php
namespace agraddy\base;
use agraddy\base\Column;

class Type {
	public $args;
	public $cap;
	public $config = [];
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

	public $title_key;
	public $title_fn;

	function __construct($key, $singular, $plural, $cap, $args) {
		$this->key = $key;
		$this->singular = $singular;
		$this->plural = $plural;
		$this->cap = $cap;
		$this->args = $args;

		$this->full_key = $key . '_' . $this->parseToKey($singular);

		$this->column = new Column($this, $this->full_key);

		$this->config('show_bulk_actions', true);
		$this->config('show_filter_date', false);
		$this->config('show_filters', false);
		$this->config('show_search', false);
		$this->config('show_simple_save', true);
		$this->config('auto_save', false);
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

	function config($key, $value) {
		$this->config[$key] = $value;
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

	function title($input, $fn) {   
		$this->title_key = $this->parseToKey($input);
		$this->title_fn = $fn;
	}

	function wpAdminEnqueueScripts($hook_suffix) {
		global $post_type;

		if($post_type == $this->full_key) {
			if(!$this->config['auto_save']) {
				wp_deregister_script('autosave');
			}

			$css = "";
			$css .= "body { background: red; }\n";
			wp_add_inline_style($this->full_key . '_inline', $css);
		}
	}

	function wpAdminPrintScripts() {
		global $post_type;

		if($post_type == $this->full_key) {
			$css = "";
			$css .= "<style>\n";
			if(!$this->config['show_bulk_actions']) {
				$css .= "#posts-filter .bulkactions { display: none; } \n";
			}
			if(!$this->config['show_search']) {
				$css .= "#posts-filter .search-box { display: none; } \n";
			}
			$css .= "</style>\n";
			echo $css;
		}
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
			'register_meta_box_cb' => array($this, 'wpMetaBoxes')
		);

		$args = array_merge($args, $this->args);
		register_post_type($this->full_key, $args );

		add_filter('enter_title_here', array($this, 'wpTitleHere'), 10, 2);
		add_action('save_post_' . $this->full_key, array($this, 'wpSave'));

		add_action('admin_enqueue_scripts', array($this, 'wpAdminEnqueueScripts'));
		add_action('admin_print_scripts', array($this, 'wpAdminPrintScripts'));
	}

	function wpMetaBoxes() {
		if(count($this->details)) {
			add_meta_box(
				$this->full_key . '_details',
				'Details',
				array($this, 'wpMetaDetails'),
				$this->full_key,
				'normal',
				'default'
			);
		}
		if($this->config['show_simple_save']) {
			remove_meta_box( 'submitdiv', $this->full_key, 'side' );
			add_meta_box(
				$this->full_key . '_save',
				'Save',
				array($this, 'wpMetaSave'),       
				$this->full_key,      
				'side',
				'high'
			);
		}
	}

	function wpMetaDetails() {
		global $post;

		$html = '';

		for($i = 0; $i < count($this->details); $i++) {
			$item = $this->details[$i];
			if(!$item->extra) {
				$item->extra = '';
			}
			$value = get_post_meta( $post->ID, $item->key, true );
			//echo $item->key;
			//echo '-<br>';
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
			} elseif($item->type == 'select') {
				$html .= '<div>';
				$html .= '<label>' . esc_html($item->title) . '</label><br>';
				$html .= '<select name="' . esc_attr($item->key) . '">';
				for($i = 0; $i < count($item->extra); $i++) {
					if(is_array($item->extra[$i])) {
						if($item->extra[$i][1] == $value) {
							$html .= '<option value="' . esc_attr($item->extra[$i][1]) . '" selected>' . esc_html($item->extra[$i][0]) . '</option>';
						} else {
							$html .= '<option value="' . esc_attr($item->extra[$i][1]) . '">' . esc_html($item->extra[$i][0]) . '</option>';
						}
					} else {
						if($item->extra[$i] == $value) {
							$html .= '<option value="' . esc_attr($item->extra[$i]) . '" selected>' . esc_html($item->extra[$i]) . '</option>';
						} else {
							$html .= '<option value="' . esc_attr($item->extra[$i]) . '">' . esc_html($item->extra[$i]) . '</option>';
						}
					}
				}
				$html .= '</select>';
				$html .= '<br>';
				$html .= '<br>';
				$html .= '</div>';
			} elseif($item->type == 'select_user') {
				$html .= '<div>';
				$html .= '<label>' . esc_html($item->title) . '</label><br>';
				$html .= wp_dropdown_users(array(
						'show_option_none' => __( 'Please Select...' ),
						'name' => $item->key,             
						'echo' => 0,                    
						'selected' => $value
					));  
				$html .= '<br>';
				$html .= '<br>';
				$html .= '</div>';
			} elseif(strpos($item->type, 'select_custom') === 0) {
				$fn = $item->extra;
				$html .= '<div>';
				$html .= '<label>' . esc_html($item->title) . '</label><br>';
				$html .= $fn($item->title, $item->key, $value);
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
	}

	function wpMetaSave() {
                global $post;

                $data = array();
                // From: https://gist.github.com/NiloySarker/2d1954eef3b0003d718d#file-replace-wp_submit-php-L93
                if (!in_array( $post->post_status, array('publish', 'future', 'private') ) || 0 == $post->ID ) {
                        $initial_save = true;
                } else {
                        $initial_save = false;
                }    

		$redirect_info = [];

		// Modified from: https://gist.github.com/NiloySarker/2d1954eef3b0003d718d
?>
<div class="submitbox" id="submitpost">
         <div id="major-publishing-actions" style="background: transparent; border: 0;">
                <?php do_action( 'post_submitbox_start' ); ?>
                 <div id="publishing-action">    
                         <span class="spinner"></span>   
                        <input name="post_status" type="hidden" id="post_status" value="publish" />
                        <input name="original_publish" type="hidden" id="original_publish" value="Update" />
                        <input name="save" type="submit" class="button button-primary button-large" id="publish" accesskey="p" value="Save" />
                        <?php if($initial_save): ?>
                        <input type="hidden" name="initial_save" value="yes" />
                        <?php endif; ?>                 
      
                        <?php foreach($redirect_info as  $key => $val): ?>
                        <input type="hidden" name="redirect_<?php echo $key; ?>" value="<?php echo $val; ?>" />
                        <?php endforeach; ?>            
                 </div>
                 <div class="clear"></div>       
         </div>
 </div>
<?php

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
						|| $item->type == 'select'
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

			if(isset($this->title_key) && isset($this->title_fn) && isset($_POST[$this->title_key])) {
				$fn = $this->title_fn;
				$title = $fn($_POST[$this->title_key]);
				$where = array( 'ID' => $post_id );
				$wpdb->update( $wpdb->posts, array( 'post_title' => $title ), $where );
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
