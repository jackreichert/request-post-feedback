<?php

class Request_Feedback_Request {
	private $uID;
	private $pID;
	private $hash;
	private $status;
	private $email;

	public function __construct( $pID, $email_or_hash ) {
		if ( $this->is_email( $email_or_hash ) ) {
			$this->email = $email_or_hash;
			$this->hash  = false;
		} else {
			$this->hash  = $this->filter_hash( $email_or_hash );
			$this->email = false;
		}
		$this->pID = intval( $pID );
		$this->load_meta();
	}

	private function is_email( $email_or_hash ) {
		return filter_var( $email_or_hash, FILTER_VALIDATE_EMAIL );
	}

	private function filter_hash( $hash ) {
		return preg_replace( '/[^0-9A-Za-z]/i', '', $hash );
	}

	private function load_meta() {
		$request_meta = false;
		if ( $this->email ) {
			$request_meta = $this->find_request_in_meta_by( 'email' );
			$this->hash   = $request_meta['hash'];
			$this->status = $request_meta['status'];
		} elseif ( $this->hash ) {
			$request_meta = $this->find_request_in_meta_by( 'hash' );
			$this->email  = $request_meta['email'];
			$this->status = $request_meta['status'];
		}

		if ( ! $request_meta ) {
			$this->hash   = $request_user = wp_generate_password( 32, false );
			$this->status = false;
		}

		$user = get_user_by( 'email', $this->email );
		if ( $user ) {
			$this->uID = $user->ID;
		} else {
			$this->uID = $this->insert_new_user( $this->email );
		}

	}

	private function find_request_in_meta_by( $key ) {
		$meta = get_post_meta( $this->pID, 'feedback_requests' );
		foreach ( $meta as $request ) {
			if ( $this->{$key} === $request[ $key ] ) {
				return $request;
			}
		}

		return false;
	}

	private function insert_new_user( $email ) {
		$random_password = wp_generate_password( $length = 32 );
		$userdata        = array(
			'user_login' => $email,
			'user_pass'  => $random_password,
			'user_email' => $email,
			'role'       => 'subscriber'
		);

		return wp_insert_user( $userdata );
	}

	public function can_give_feedback() {
		return $this->find_request_in_meta_by( 'hash' );
	}

	public function update_access() {
		$this->status = true;
		$this->save();
	}

	public function save() {
		if ( $this->hash ) {
			$update_request = array( 'email' => $this->email, 'hash' => $this->hash, 'status' => $this->status );
			if ( $request = $this->find_request_in_meta_by( 'email' ) ) {
				update_post_meta( $this->pID, 'feedback_requests', $update_request, $request );
			} else {
				add_post_meta( $this->pID, 'feedback_requests', $update_request );
			}
		}
	}

	public function get_hash() {
		return $this->hash;
	}

	public function get_uID() {
		return $this->uID;
	}

	public function get_status() {
		return $this->status;
	}

	public function get_email() {
		return $this->email;
	}

	public function get_edit_link() {
		return get_bloginfo( 'url' ) . '?p=' . $this->pID . '&feedback=' . $this->hash;
	}

	public function send_invite( $message = "I'd love your feedback for this post I'm working on." ) {
		$post = get_post( $this->pID );
		wp_mail( $this->email, "Feedback request: " . $post->post_title, stripslashes( $message ) . "\n\n" . $this->get_edit_link() );
	}

	public function get_feedback_link() {
		global $wpdb;
		$query = $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_parent=%d AND post_type='revision' AND post_author=%d ORDER BY post_date DESC LIMIT 1", $this->pID, $this->uID );
		$fID   = $wpdb->get_var( $query );

		if ( is_null( $fID ) ) {
			return false;
		}

		return admin_url( 'revision.php?revision=' . $fID );
	}

}