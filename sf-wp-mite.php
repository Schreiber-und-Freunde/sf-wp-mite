<?
/*
Plugin Name: S&F Wordpress mite.
Description: mite. API Integration for Wordpress
Version: 0.1
Author: Schreiber & Freunde GmbH
Author URI: http://www.schreiber-freunde.de
*/

class SfWpMite
{
	// singleton instance
	private static $instance;

	private $url;
	private $api_key;
	private $result;
	private $useragent;
	private $is_ready = true;

	public static function instance() {
		if ( isset( self::$instance ) )
			return self::$instance;

		self::$instance = new SfWpMite;
		return self::$instance;
	}

	function __construct() {
		add_action( 'init', array(&$this, 'init'));
		add_action( 'admin_menu', array( &$this, 'add_pages' ), 30 );		
	}

	function init() {

		if( isset($_REQUEST['sfwp_mite_action']) ) {
			if( $_REQUEST['sfwp_mite_action'] == 'save_options' ) {
				$this->save_options();
			}
		}

		$account_name = get_option('mite_account_name');
		$api_key = get_option('mite_api_key');
		
		if( $api_key === false ) {
			$this->is_ready = false;
			add_action('admin_notices', array( &$this, 'admin_notice_missing_account_data'));
			return;
		}

		$this->url = 'https://' . $account_name . '.mite.yo.lk/';
		$this->api_key = $api_key;
		$this->useragent = get_bloginfo( 'name' ) . ' (' . get_bloginfo( 'url' ) . ')';

		if( isset($_REQUEST['sfwp_mite_action']) ) {
			if( $_REQUEST['sfwp_mite_action'] == 'test' ) {
				$this->test();
			}
		}
	}

	function admin_notice_missing_account_data() {
		echo '<div class="error"><p>' . __('WP mite.: Please go to the options page and fill in your account details.', 'sf_wp_mite') . '</p></div>';
	}

	function add_pages() {
		add_options_page( 'mite.', 'mite.', 'manage_options', 'sfwp_mite_options', array( &$this, 'page_options'));
	}

	function save_options() {
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'] , 'sfwp_mite_action_save_options' ) ) {
			wp_die( __('Nonce check failed', 'sf_wp_mite') );
			return;
		}

		if( isset($_REQUEST['mite_api_key']) ) {
			update_option('mite_api_key', trim($_REQUEST['mite_api_key']) );
		}

		if( isset($_REQUEST['mite_account_name']) ) {
			update_option('mite_account_name', trim($_REQUEST['mite_account_name']) );
		}
	}

	function page_options() {
		?>
		<div class="wrap">
			<h2><? _e('Settings', 'sf_wp_mite'); ?> › <? _e('mite.', 'sf_wp_mite') ?></h2>
			<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
				<input type="hidden" name="sfwp_mite_action" value="save_options" />
				<input type="hidden" name="_wpnonce" value="<? echo wp_create_nonce( 'sfwp_mite_action_save_options' ) ?>" />
				<table class="form-table">
					<tr>
						<th><label for="mite_api_key"><? _e('API Key', 'sf_wp_mite') ?></label></th>
						<td><input name="mite_api_key" id="mite_api_key" type="text" value="<? echo get_option('mite_api_key') ?>" /></td>
					</tr>
					<tr>
						<th><label for="mite_account_name"><? _e('Account name', 'sf_wp_mite') ?></label></th>
						<td>
							<input name="mite_account_name" id="mite_account_name" type="text" value="<? echo get_option('mite_account_name') ?>" />
							<p class="description"><? _e('<strong>demo</strong>.mite.yo.lk (only the first part)', 'sf_wp_mite') ?></p>
						</td>
					</tr>
				</table>
				<p class="submit"><input type="submit" value="<? _e('Save Settings', 'sf_wp_mite') ?>" class="button-primary" /></p>
			</form>
			<h3><? _e('Test', 'sf_wp_mite') ?></h3>
			<form action="<?php echo $_SERVER['REQUEST_URI']; ?>" method="post">
				<input type="hidden" name="sfwp_mite_action" value="test" />
				<input type="hidden" name="_wpnonce" value="<? echo wp_create_nonce( 'sfwp_mite_action_test' ) ?>" />
				<p class="submit"><input type="submit" value="<? _e('Test Settings', 'sf_wp_mite') ?>" class="button-primary" /></p>
			</form>
			<? if( isset($this->result) ) : ?>
			<h3><? _e('Test Result', 'sf_wp_mite') ?></h3>
			<? echo '<pre>' . print_r( $this->result, true) . '</pre>'; ?>
			<? endif; ?>
		</div>
		<?
	}

	private function test() {
		if ( ! wp_verify_nonce( $_REQUEST['_wpnonce'] , 'sfwp_mite_action_test' ) ) {
			wp_die( __('Nonce check failed', 'sf_wp_mite') );
			return;
		}

		if( !$this->is_ready ) {
			return false;
		}

		$this->result = $this->do_request('time_entries.json?group_by=year');
	}

	public function do_request($method) {
		if( !$this->is_ready ) {
			return false;
		}
		
		$curl = curl_init();

		if ($data) {
			curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		}

		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
			'User-Agent: ' . $this->useragent,
			'X-MiteApiKey: ' . $this->api_key
		));
		
		curl_setopt( $curl, CURLOPT_URL, $this->url . $method );
		curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );

		return curl_exec($curl);
	}
}
$sf_wp_mite = SfWpMite::instance();
function mite_get_time_entries_by_year() {
	return json_decode( SfWpMite::instance()->do_request( 'time_entries.json?group_by=year' ) );
}
?>
