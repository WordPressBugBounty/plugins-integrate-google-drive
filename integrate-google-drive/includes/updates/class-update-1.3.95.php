<?php

namespace IGD;

defined( 'ABSPATH' ) || exit();

class Update_1_3_95 {
	private static $instance;

	public function __construct() {
		$this->add_root_id();
	}

	public function add_root_id() {
		$accounts = Account::instance()->get_accounts();

		if ( ! empty( $accounts ) ) {
			foreach ( $accounts as $account ) {
				$service = App::instance( $account['id'] )->getService();

				$account['root_id'] = $service->files->get( 'root' )->getId();

				$data = Account::instance()->update_account( $account );
			}
		}
	}

	public static function instance() {
		if ( null == self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

}

Update_1_3_95::instance();