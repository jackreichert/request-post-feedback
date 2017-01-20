<?php

class Request_Feedback_Public {
	private $request;

	public function __construct() {
		$this->request = false;
	}

	public function init() {
		$this->hooks();
	}

	private function hooks() {
		add_filter( 'init', array( $this, 'maybe_remove_wptexturize' ) );
		add_filter( 'the_posts', array( $this, 'intercept_the_posts' ) );
		add_filter( 'the_content', array( $this, 'return_content_in_editor' ), 20 );
	}

	public function maybe_remove_wptexturize() {
		if ( isset( $_GET['feedback'] ) && isset( $_GET['p'] ) ) {
			$this->request = new Request_Feedback_Request( $_GET['p'], $_GET['feedback'] );
			add_filter( 'run_wptexturize', '__return_false' );
		}
	}

	public function return_content_in_editor( $content ) {
		global $post;
		if ( 'draft' === $post->post_status ) {
			$request = new Request_Feedback_Request( $post->ID, $_GET['feedback'] );
			if ( $request->can_give_feedback() && ! $request->get_status() ) {
				$content = $this->build_editor_form( $content, $request->get_uID(), $post );
			} elseif ( $request->can_give_feedback() ) {
				$content = "Thank you for your feedback.";
			}
		}

		return $content;
	}

	private function build_editor_form( $content, $uID, $post ) {
		ob_start(); ?>
		<form method="post">
			<?php wp_nonce_field( 'give_feedback_nonce_action', 'give_feedback_nonce' ); ?>
			<?php wp_editor( $content, 'request_feedback', array( 'media_buttons' => false ) ); ?>
			<p>
				<input type="hidden" name="uID" value="<?php echo $uID; ?>">
				<input type="hidden" name="pID" value="<?php echo $post->ID; ?>">
				<button type="submit">Submit Feedback</button>
			</p>
		</form>
		<?php
		$content = ob_get_contents();
		ob_end_clean();

		return $content;
	}

	public function intercept_the_posts( $posts ) {
		if ( empty( $posts ) && $this->request ) {
			$post = get_post( intval( $_GET['p'] ) );
			if ( ! is_null( $post ) && 'draft' === $post->post_status && $this->request->can_give_feedback() ) {
				return array( $post );
			}
		}

		return $posts;
	}
}