<?php
namespace Taiwan_Store_Core\Modules\Social_Login;

defined( 'ABSPATH' ) || exit;

/**
 * 社群登入模組 (Social Login Module)
 * 支援 LINE, Google, Facebook 快速登入並與 WooCommerce 會員對接。
 */
class Module {

	public function boot(): void {
		add_filter( 'woocommerce_get_settings_tw_core', [ $this, 'add_settings_fields' ], 10, 2 );
		add_action( 'taiwan_store_core_settings_before_output_social_login', [ $this, 'output_guide' ] );
		add_action( 'woocommerce_before_customer_login_form', [ $this, 'display_login_buttons' ] );
		add_action( 'woocommerce_before_checkout_form', [ $this, 'display_login_buttons' ], 5 );
		add_action( 'init', [ $this, 'handle_oauth_callback' ] );
	}

	public function output_guide(): void {
		$home_url = home_url( '/' );
		$callbacks = [
			'line'     => add_query_arg( 'taiwan_store_social', 'line', $home_url ),
			'google'   => add_query_arg( 'taiwan_store_social', 'google', $home_url ),
			'facebook' => add_query_arg( 'taiwan_store_social', 'facebook', $home_url ),
		];
		?>
		<div class="wctw-social-guide" style="background:#fff; padding:20px; border:1px solid #ddd; border-radius:8px; margin-top:20px;">
			<h2><?php esc_html_e( '社群登入設定指南', 'taiwan-store-core' ); ?></h2>
			<div class="wctw-callback-box" style="background:#f9f9f9; padding:15px; border-left:4px solid #2271b1;">
				<strong>LINE Callback:</strong> <code><?php echo esc_url( $callbacks['line'] ); ?></code><br>
				<strong>Google Redirect:</strong> <code><?php echo esc_url( $callbacks['google'] ); ?></code><br>
				<strong>Facebook Redirect:</strong> <code><?php echo esc_url( $callbacks['facebook'] ); ?></code>
			</div>
		</div>
		<?php
	}

	public function add_settings_fields( array $settings, string $current_section ): array {
		if ( 'social_login' !== $current_section ) return $settings;
		return [
			[ 'title' => __( 'LINE 登入設定', 'taiwan-store-core' ), 'type' => 'title', 'id' => 'taiwan_store_core_social_line_options' ],
			[ 'title' => __( '啟用 LINE', 'taiwan-store-core' ), 'id' => 'taiwan_store_core_social_line_enabled', 'type' => 'checkbox' ],
			[ 'title' => __( 'Channel ID', 'taiwan-store-core' ), 'id' => 'taiwan_store_core_social_line_client_id', 'type' => 'text' ],
			[ 'title' => __( 'Channel Secret', 'taiwan-store-core' ), 'id' => 'taiwan_store_core_social_line_client_secret', 'type' => 'password' ],
			[ 'type' => 'sectionend', 'id' => 'taiwan_store_core_social_line_options' ],
		];
	}

	public function display_login_buttons(): void {
		if ( is_user_logged_in() ) return;
		$line_enabled = 'yes' === get_option( 'taiwan_store_core_social_line_enabled' );
		if ( ! $line_enabled ) return;
		
		$line_url = add_query_arg( 'taiwan_store_social', 'line', home_url( '/' ) );
		echo '<div class="wc-tw-social-login-wrap" style="margin-bottom:20px;">';
		echo '<a href="' . esc_url( $line_url ) . '" class="wctw-social-btn line-btn" style="background:#00B900; color:#fff; padding:10px 20px; border-radius:4px; text-decoration:none;">LINE 登入</a>';
		echo '</div>';
	}

	public function handle_oauth_callback(): void {
		if ( ! isset( $_GET['taiwan_store_social'] ) ) return;
		$provider = sanitize_key( $_GET['taiwan_store_social'] );

		if ( ! isset( $_GET['code'] ) ) {
			if ( 'line' === $provider ) $this->redirect_to_line_authorize();
			return;
		}

		if ( 'line' === $provider ) {
			if ( ! isset( $_GET['state'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_GET['state'] ) ), 'line_login_state' ) ) {
				wp_die( 'Security check failed.' );
			}
			$this->process_line_login( sanitize_text_field( wp_unslash( $_GET['code'] ) ) );
		}
	}

	private function redirect_to_line_authorize(): void {
		$client_id = get_option( 'taiwan_store_core_social_line_client_id' );
		if ( ! $client_id ) wp_die( 'LINE Channel ID not set.' );
		$redirect_uri = add_query_arg( 'taiwan_store_social', 'line', home_url( '/' ) );
		$state = wp_create_nonce( 'line_login_state' );
		$url = 'https://access.line.me/oauth2/v2.1/authorize?' . http_build_query( [ 'response_type' => 'code', 'client_id' => $client_id, 'redirect_uri' => $redirect_uri, 'state' => $state, 'scope' => 'profile openid email' ] );
		wp_safe_redirect( $url );
		exit;
	}

	private function process_line_login( string $code ): void {
		$client_id     = get_option( 'taiwan_store_core_social_line_client_id' );
		$client_secret = get_option( 'taiwan_store_core_social_line_client_secret' );
		$redirect_uri  = add_query_arg( 'taiwan_store_social', 'line', home_url( '/' ) );

		$response = wp_remote_post( 'https://api.line.me/oauth2/v2.1/token', [ 'body' => [ 'grant_type' => 'authorization_code', 'code' => $code, 'redirect_uri' => $redirect_uri, 'client_id' => $client_id, 'client_secret' => $client_secret ] ] );
		if ( is_wp_error( $response ) ) wp_die( 'Token request failed.' );
		$data = json_decode( wp_remote_retrieve_body( $response ), true );
		if ( empty( $data['access_token'] ) ) wp_die( 'Authorization failed.' );

		$parts   = explode( '.', $data['id_token'] );
		$payload = json_decode( base64_decode( $parts[1] ), true );
		$user_id = $payload['sub'];
		$email   = $payload['email'] ?? '';
		$name    = $payload['name'] ?? '';

		$user = $this->get_or_create_user( $user_id, $email, $name, 'line' );
		if ( $user ) {
			wp_set_auth_cookie( $user->ID, true );
			wp_safe_redirect( wc_get_checkout_url() );
			exit;
		}
	}

	private function get_or_create_user( string $social_id, string $email, string $name, string $provider ): ?\WP_User {
		$users = get_users( [ 'meta_key' => "_taiwan_store_{$provider}_id", 'meta_value' => $social_id, 'number' => 1 ] );
		if ( ! empty( $users ) ) return $users[0];
		if ( $email ) {
			$user = get_user_by( 'email', $email );
			if ( $user ) {
				update_user_meta( $user->ID, "_taiwan_store_{$provider}_id", $social_id );
				return $user;
			}
		}
		$username = $provider . '_' . $social_id;
		$user_id = wp_insert_user( [ 'user_login' => $username, 'user_email' => $email ?: $username . '@example.com', 'user_pass' => wp_generate_password(), 'display_name' => $name, 'role' => 'customer' ] );
		if ( is_wp_error( $user_id ) ) return null;
		update_user_meta( $user_id, "_taiwan_store_{$provider}_id", $social_id );
		return get_user_by( 'id', $user_id );
	}
}
