<?php

class Request_Feedback_Save {
	public function __construct() {
	}

	public function init() {
		$this->hooks();
	}

	private function hooks() {
		add_action( 'init', array( $this, 'process_feedback' ) );
	}

	public function process_feedback() {
		$nonce_name   = isset( $_POST['give_feedback_nonce'] ) ? $_POST['give_feedback_nonce'] : '';
		$nonce_action = 'give_feedback_nonce_action';
		if ( ! wp_verify_nonce( $nonce_name, $nonce_action ) ) {
			return;
		}

		if ( isset( $_POST['request_feedback'] ) ) {
			list( $user, $post ) = $this->filter_posted_data();
			if ( $user && ! is_null( $post ) ) {
				$this->save_feedback( $_POST['request_feedback'], $user, $post );
				$request = new Request_Feedback_Request( $post->ID, $user->user_email );
				$request->update_access();
			}

		}
	}

	/**
	 * @return false|WP_User
	 */
	private function filter_posted_data() {
		$user    = get_user_by( 'ID', intval( $_POST['uID'] ) );
		$post_id = get_post( intval( $_POST['pID'] ) );

		return array( $user, $post_id );
	}

	/**
	 * @param $content
	 * @param $user
	 * @param $post_id
	 */
	private function save_feedback( $content, $user, $post ) {
		$new_revision = array(
			'post_type'    => 'revision',
			'post_status'  => 'inherit',
			'post_name'    => $post->ID . '-revision-v1',
			'post_content' => $content,
			'post_title'   => $post->post_title,
			'post_parent'  => $post->ID,
			'post_author'  => $user->ID
		);
		wp_insert_post( $new_revision );
	}
}