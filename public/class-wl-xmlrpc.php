<?php
/**
 * WL_CrossPoster.
 *
 * @package   WL_CrossPoster
 * @author    Eugene F <eugen@figursky.com>
 * @license   GPL-2.0+
 * @link      http://wildlabs.com
 * @copyright 2014 Eugene F / WildLabs
 */

/**
 * Plugin class. This class should ideally be used to work with the
 * public-facing side of the WordPress site.
 *
 * @package WL_CrossPoster
 * @author  Eugene F <eugen@figursky.com>
 */
if (class_exists('WL_XMLRPC')) {
	return;
}

class WL_XMLRPC {

	/**
	 * Instance of this class.
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected static $instance = null;

	/**
	 * Plugin instance
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected $plugin = null;

	/**
	 * Plugin slug
	 *
	 * @since    1.0.0
	 *
	 * @var      object
	 */
	protected $plugin_slug = null;

	protected $url;

	protected $username;

	protected $password;

	protected $meta = '_xmlrpc';

	protected $current_request;

	protected $rpc;

	protected $is_ajax;

	/**
	 * Initialize the plugin by loading admin scripts & styles and adding a
	 * settings page and menu.
	 *
	 * @since     1.0.0
	 */
	private function __construct($meta = null, $is_ajax = false) {
		/*
		 * Call $plugin_slug from public plugin class.
		 */
		$this->plugin      = WL_CrossPoster::get_instance();
		$this->plugin_slug = $this->plugin->get_plugin_slug();

		$this->username    = $this->plugin->get_option('username');
		$this->password    = $this->plugin->get_option('password');
		$this->url         = $this->plugin->get_option('xmlrpc_url');

		$this->rpc         = new WP_HTTP_IXR_Client( $this->url );

		// $this->rpc->debug = true;

		if (null !== $meta) {
			$this->meta = $meta;
		}

		$this->is_ajax = (bool) $is_ajax;

	}

	/**
	 * Return an instance of this class.
	 *
	 * @since     1.0.0
	 *
	 * @return    object    A single instance of this class.
	 */
	public static function get_instance() {

		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function post($post_id) {

		$post = get_post($post_id);

		if (!$post || !$post_id || $post->post_type != 'post') {
			return false;
		}

		$to_post_id = get_post_meta( $post_id , $this->meta, true );

		# update post
		if ($to_post_id) {

			# send all the attachments
			$guids = $this->send_attachments($post_id);

			# update the post
			$remote_id = $this->update_post($post_id, $to_post_id, $guids);

		# create new post
		} else {

			# upload the post data
			$success = $this->create_post($post_id);

			# add meta with remote id
			if ($success) {
				$to_post_id = $this->current_request;
				update_post_meta($post_id, $this->meta, $to_post_id);
			}

			# send all the attachments
			$guids = $this->send_attachments($post_id);

			// update content after changing attachment url
			if ( $guids ) {
				// create attachments link to parent in remote
				$success = $this->update_post( $post_id, $to_post_id, $guids );
			}
		}

		return $to_post_id;
	}

	public function create_post($post_id)
	{
		$original_post = get_post($post_id);

		$post = array(
			'post_title'    => $original_post->post_title,
			'post_type'     => 'post',
			'post_content'  => $original_post->post_content,
			'post_name'     => $original_post->post_name,
			'post_status'	=> 'publish',
			'custom_fields' => array(
				'settings'      => array(
					'related' => 'yes',
					'meta'    => 'yes',
					'sharing' => 'yes',
				),
				'layout'        => array(
					'type'    => 'full',
					'sidebar' => '',
				),
			),
			'terms_names'	=> $this->get_terms($original_post),
		);

		# send new post
		$status = $this->query('wp.newPost', $post);

		// echo 'ADDED POST<br>';

		return $status;
	}

	public function update_post($post_id, $to_post_id, $guids = array(), $search_replace = false)
	{
		if (!$to_post_id) {
			return false;
		}

		$original_post = get_post($post_id);

		$post_content = $original_post->post_content;

		$post_thumbnail_id = false;
		if ( $guids ) {

			foreach ( $guids as $guid ) {

				$post_content = str_replace ( $guid['local'], $guid['remote'], $post_content );

				if ($guid['is_thumb']) {
					$post_thumbnail_id = $guid['remote_id'];
				}
			}
		}

		if ( $search_replace &&  isset( $search_replace['search']) ) {
			$post_content = str_replace($search_replace ['search'], $search_replace['replace'], $post_content);
		}

		$post = array(
			'post_title'   => $original_post->post_title,
			'post_content' => $post_content,
			'post_name'    => $original_post->post_name,
			'terms_names'  => $this->get_terms($original_post),
		);

		if ($post_thumbnail_id) {
			$post['post_thumbnail'] = $post_thumbnail_id;
		}

		$status = $this->query('wp.editPost', $to_post_id, $post);

		// echo 'UPDATED POST<br>';
		// echo '<pre>';
		// var_dump($status);
		// var_dump($this->current_request);
		// exit;

		// $status = $this->rpc->query(
		// 	'wp.editPost',
		// 	$post
		// );

		return $status;
	}

	protected function get_terms($post, $taxonomy = 'category')
	{
		return array(
			'category' => wp_get_post_terms($post->ID, $taxonomy, array('fields' => 'names')),
		);

		// $terms = array();

		// foreach ($post_terms as $term) {
		// 	$this->query('wp.getTerms', $taxonomy, array(
		// 		'search' => $term,
		// 	));

		// 	$_terms = $this->current_request;

		// 	if (count($_terms)) {
		// 		$terms[] = $_terms[0];
		// 	} else{
		// 		$success = $this->query('wp.newTerm', array(
		// 			'name'     => $term,
		// 			'taxonomy' => $taxonomy,
		// 		));

		// 		$term_id = $this->current_request;

		// 		$this->query('wp.getTerm', $taxonomy, $term_id);


		// 		$terms[] = $this->current_request;
		// 	}
		// }

		// return $terms;
	}

	protected function send_attachments($post_id)
	{
		// $attachments = get_children(array(
		// 	'post_parent' => $post_id,
  //   		'post_type'   => 'attachment',
		// ));
		$attachments = array();

		$post_thumbnail_id = get_post_thumbnail_id($post_id);

		$post_thumbnail = get_post($post_thumbnail_id);

		if ($post_thumbnail) {
			$attachments[$post_thumbnail_id] = $post_thumbnail;
		}

		# check if the post thumbnail attached to the post
		// $post_thumbnail_attached = false;
		// if ($post_thumbnail_id) {
		// 	foreach ($attachments as $attachment) {
		// 		if ($attachment->ID == $post_thumbnail_id) {
		// 			$post_thumbnail_attached = true;
		// 		}
		// 	}
		// 	if (!$post_thumbnail_attached) {
		// 		$attachment = get_post($post_thumbnail_id);

		// 		$attachments[$post_thumbnail_id] = $attachment;
		// 	}
		// }

		if (is_array($attachments) && count($attachments)) {
			$to_post_id = (int) get_post_meta($post_id , $this->meta, true);
			$guids = array();
			foreach ( $attachments as $attachment_id => $attachment ) {

				$to_attachment_id = (int) get_post_meta( $attachment_id , $this->meta, true );

				# upload the new image to the server
				if (!$to_attachment_id) {
					$file = get_attached_file( $attachment_id );

					$post = array(
						'name' => basename($file),
						'type'      => $attachment->post_mime_type,
						'bits'      => new IXR_Base64(file_get_contents($file)),
						'overwrite' => true,
						'post_id'   => $to_post_id,
					);

					# upload file
					$status = $this->query('wp.uploadFile', $post);

					# check for uploaded file
					if ($status) {

						// echo 'ADDED ATTACHMENT<br>';

						$remote_url = $this->current_request['url'];
						$remote_id  = $this->current_request['id'];

						$updated = update_post_meta($attachment_id, $this->meta, $remote_id);
					} else {
						$this->raise_error();
					}
				} else {

					$remote_id = $to_attachment_id;

					# request data
					$post = array(
						'attachment_id' => $remote_id,
					);

					# make a call
					$status = $this->query('wp.getMediaItem', $remote_id);

					// echo 'GRABBED ATTACHMENT<br>';
					// echo '<pre>';
					// var_dump($this->current_request);

					# grab the url to change
					$remote_url = $this->current_request['link'];

					# we will need this remote_id to add to the array
					$remote_id  = $to_attachment_id;
				}

				// $this->current_request example:
				// array(4) {
				//   ["id"]=>
				//   string(4) "3434"
				//   ["file"]=>
				//   string(6) "ww.png"
				//   ["url"]=>
				//   string(55) "http://wedpro.gr/new/wp-content/uploads/2014/04/ww5.png"
				//   ["type"]=>
				//   string(9) "image/png"
				// }
				if ($status) {
					$guids[] = array(
						'local_id'  => $attachment_id,
						'local'     => $attachment->guid,
						'remote'    => isset($remote_url) && $remote_url ? $remote_url : $attachment->guid,
						'remote_id' => $remote_id,
						'is_thumb'  => $post_thumbnail_id == $attachment->ID,
					);
				} else {
					$this->raise_error();
				}
			}

			return $guids;
		}
	}

	protected function query($request, $param1, $param2 = 0)
	{
		$status = $this->rpc->query(
			$request,
			array(
				0,
				$this->username,
				$this->password,
				$param1,
				$param2
			)
		);

		$this->current_request = $this->rpc->getResponse();

		return $status;
	}

	protected function raise_error()
	{
		$msg = 'Error [' . $this->rpc->getErrorCode() . ']: ' . $this->rpc->getErrorMessage();
		if ($this->is_ajax) {
			return array(
				'error' => 1,
				'message' => $msg,
			);
		} else {
			error_log($msg);
		}
	}
}
