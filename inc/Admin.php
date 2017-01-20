<?php

class Request_Feedback_Admin {
	/**
	 * Request_Feedback_Admin constructor.
	 */
	public function __construct() {
	}

	/**
	 *
	 */
	public function init() {
		$this->hooks();
	}

	private function hooks() {
		if ( is_admin() ) {
			add_action( 'add_meta_boxes', array( $this, 'request_feedback_meta_box' ) );
			add_action( 'save_post', array( $this, 'request_feedback_add' ) );
		}
	}

	/**
	 *
	 */
	public function request_feedback_meta_box() {
		add_meta_box( 'request-feedback', __( 'Request Feedback', 'request-feedback' ), array(
			$this,
			'request_feedback_meta_box_callback'
		), null, 'normal', 'high' );
	}

	/**
	 *
	 */
	public function request_feedback_meta_box_callback() {
		global $post;
		if ( in_array( $post->post_status, array( 'draft', 'pending' ) ) ) {
			wp_nonce_field( 'reqeust_feedback_nonce_action', 'reqeust_feedback_nonce' ); ?>
            <p><label>Generate feedback link for email:
                    <input class="request-input" type="email" name="request_feedback_email"
                           placeholder="yourEditor@example.com"></label>
                Send them the link too? <input type="checkbox" name="request_feedback_send_email" value="yes"
                                               onclick="jQuery('#request_feedback_email_content').slideToggle()"/><br>
            <div id="request_feedback_email_content" style="display: none;">
                <textarea style="width: 100%; height: 5em;" name="request_feedback_email_content">I'd love your feedback for this post I'm working on.</textarea>
            </div>
            </p>
            <p>
                <button id="send" class="button button-primary button-large">Update Requests</button>
            </p>
			<?php $feedback_requests = get_post_meta( $post->ID, 'feedback_requests' );
			if ( 0 < count( $feedback_requests ) ) : ?>
                <table>
                    <thead>
                    <tr>
                        <th>Revoke</th>
                        <th>Email address</th>
                        <th>Feedback Link (to send)</th>
                        <th>Feedback</th>
                    </tr>
                    </thead>
					<?php foreach ( $feedback_requests as $fr ) : ?>
                        <tr>
							<?php
							$request       = new Request_Feedback_Request( $post->ID, $fr['email'] );
							$feedback_link = $request->get_feedback_link();
							if ( $request->get_status() && $feedback_link ) {
								$status = "<a href='$feedback_link'>review feedback</a>";
							} else {
								$status = '<i>none</i>';
							} ?>
                            <td style="padding: 0.5em 1em;"><input type="checkbox" name="reqeust_feedback_revoke[]"
                                                                   value="<?php echo $request->get_email(); ?>"/></td>
                            <td style="padding: 0.5em 1em;"><?php echo $request->get_email(); ?></td>
                            <td style="padding: 0.5em 1em;"><input type="text" readonly="readonly"
                                                                   onClick="this.select();"
                                                                   value="<?php echo $request->get_edit_link(); ?>"/>
                            </td>
                            <td style="padding: 0.5em 1em;"><?php echo $status; ?></td>
                        </tr>
					<?php endforeach; ?>
                </table>
			<?php endif;
		} else {
			echo 'This feature is only available to unpublished posts.';
		}
	}

	/**
	 * @param $post_id
	 */
	public function request_feedback_add( $post_id ) {
		// Check if nonce is valid.
		$nonce_name   = isset( $_POST['reqeust_feedback_nonce'] ) ? $_POST['reqeust_feedback_nonce'] : '';
		$nonce_action = 'reqeust_feedback_nonce_action';
		if ( ! wp_verify_nonce( $nonce_name, $nonce_action ) ) {
			return;
		}

		if ( isset( $_POST['reqeust_feedback_revoke'] ) ) {
			$feedback_requests = get_post_meta( $post_id, 'feedback_requests' );
			foreach ( $feedback_requests as $ind => $request ) {
				if ( in_array( $request['email'], $_POST['reqeust_feedback_revoke'] ) ) {
					delete_post_meta( $post_id, 'feedback_requests', $request );
				}
			}
		}

		if ( filter_var( $_POST['request_feedback_email'], FILTER_VALIDATE_EMAIL ) ) {
			$request = new Request_Feedback_Request( $post_id, $_POST['request_feedback_email'] );
			$request->save();
			if ( isset( $_POST['request_feedback_send_email'] ) ) {
				$request->send_invite( wp_kses_post( $_POST['request_feedback_email_content'] ) );
			}
		}
	}

}