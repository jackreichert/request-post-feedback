<?php
/*
Plugin Name: Request Post Feeback
Plugin URI: https://www.jackreichert.com/
Description: Allows writers to get feedback from peers, levergaing the built-in revisions feature, before they publish.
Version: 0.1
Author: jackreichert
Author URI: http://www.jackreichert.com
Text Domain: request-feedback
*/

$RequestFeedback = new Request_Feedback();

class Request_Feedback {
	public function __construct() {
		$this->include_dependencies();
		$this->instantiate_components();
	}

	private function include_dependencies() {
		require_once 'inc/Admin.php';
		require_once 'inc/Public.php';
		require_once 'inc/Save.php';
		require_once 'inc/Request.php';
	}

	/**
	 *
	 */
	private function instantiate_components() {
		$Admin = new Request_Feedback_Admin();
		$Admin->init();

		$Public = new Request_Feedback_Public();
		$Public->init();

		$Save = new Request_Feedback_Save();
		$Save->init();
	}
}