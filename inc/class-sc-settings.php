<?php
/**
 * Settings class
 *
 * @package  bestwebsite-simple-cache
 */

defined( 'ABSPATH' ) || exit;

/**
 * Class containing settings hooks
 */
class bestwebsite_Settings {

	/**
	 * Setup the plugin
	 *
	 * @since 1.0
	 */
	public function setup() {
		add_action( 'admin_enqueue_scripts', array( $this, 'action_admin_enqueue_scripts_styles' ) );

		add_action( 'load-settings_page_bestwebsite-simple-cache', array( $this, 'update' ) );
		add_action( 'load-settings_page_bestwebsite-simple-cache', array( $this, 'purge_cache' ) );

		if ( bestwebsite_IS_NETWORK ) {
			add_action( 'network_admin_menu', array( $this, 'network_admin_menu' ) );
		} else {
			add_action( 'admin_menu', array( $this, 'action_admin_menu' ) );
			add_action( 'admin_bar_menu', array( $this, 'admin_bar_menu' ) );
		}

	}

	/**
	 * Output network setting menu option
	 *
	 * @since  1.7
	 */
	public function network_admin_menu() {
		add_submenu_page( 'settings.php', esc_html__( 'Simple Cache', 'bestwebsite-simple-cache' ), esc_html__( 'Simple Cache', 'bestwebsite-simple-cache' ), 'manage_options', 'bestwebsite-simple-cache', array( $this, 'screen_options' ) );
	}

	/**
	 * Add purge cache button to admin bar
	 *
	 * @since 1,3
	 */
	public function admin_bar_menu() {
		global $wp_admin_bar;

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$wp_admin_bar->add_menu(
			array(
				'id'     => 'sc-purge-cache',
				'parent' => 'top-secondary',
				'href'   => esc_url( admin_url( 'options-general.php?page=bestwebsite-simple-cache&amp;wp_http_referer=' . esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ) . '&amp;action=bestwebsite_purge_cache&amp;bestwebsite_cache_nonce=' . wp_create_nonce( 'bestwebsite_purge_cache' ) ) ),
				'title'  => esc_html__( 'Purge Cache', 'bestwebsite-simple-cache' ),
			)
		);
	}

	/**
	 * Enqueue settings screen js/css
	 *
	 * @since 1.0
	 */
	public function action_admin_enqueue_scripts_styles() {

		global $pagenow;

		if ( ( 'options-general.php' === $pagenow || 'settings.php' === $pagenow ) && ! empty( $_GET['page'] ) && 'bestwebsite-simple-cache' === $_GET['page'] ) {
			wp_enqueue_script( 'sc-settings', plugins_url( '/dist/js/settings.js', dirname( __FILE__ ) ), array( 'jquery' ), bestwebsite_VERSION, true );
			wp_enqueue_style( 'sc-settings', plugins_url( '/dist/css/settings-styles.css', dirname( __FILE__ ) ), array(), bestwebsite_VERSION );
		}
	}

	/**
	 * Add options page
	 *
	 * @since 1.0
	 */
	public function action_admin_menu() {
		add_submenu_page( 'options-general.php', esc_html__( 'Simple Cache', 'bestwebsite-simple-cache' ), esc_html__( 'Simple Cache', 'bestwebsite-simple-cache' ), 'manage_options', 'bestwebsite-simple-cache', array( $this, 'screen_options' ) );
	}

	/**
	 * Purge cache manually
	 *
	 * @since 1.0
	 */
	public function purge_cache() {

		if ( ! empty( $_REQUEST['action'] ) && 'bestwebsite_purge_cache' === $_REQUEST['action'] ) {
			if ( ! current_user_can( 'manage_options' ) || empty( $_REQUEST['bestwebsite_cache_nonce'] ) || ! wp_verify_nonce( $_REQUEST['bestwebsite_cache_nonce'], 'bestwebsite_purge_cache' ) ) {
				wp_die( esc_html__( 'You need a higher level of permission.', 'bestwebsite-simple-cache' ) );
			}

			if ( bestwebsite_IS_NETWORK ) {
				bestwebsite_cache_flush( true );
			} else {
				bestwebsite_cache_flush();
			}

			if ( ! empty( $_REQUEST['wp_http_referer'] ) ) {
				wp_safe_redirect( $_REQUEST['wp_http_referer'] );
				exit;
			}
		}
	}

	/**
	 * Handle setting changes
	 *
	 * @since 1.0
	 */
	public function update() {

		if ( ! empty( $_REQUEST['action'] ) && 'bestwebsite_update' === $_REQUEST['action'] ) {

			if ( ! current_user_can( 'manage_options' ) || empty( $_REQUEST['bestwebsite_settings_nonce'] ) || ! wp_verify_nonce( $_REQUEST['bestwebsite_settings_nonce'], 'bestwebsite_update_settings' ) ) {
				wp_die( esc_html__( 'You need a higher level of permission.', 'bestwebsite-simple-cache' ) );
			}

			$verify_file_access = bestwebsite_verify_file_access();

			if ( is_array( $verify_file_access ) ) {
				if ( bestwebsite_IS_NETWORK ) {
					update_site_option( 'bestwebsite_cant_write', array_map( 'sanitize_text_field', $verify_file_access ) );
				} else {
					update_option( 'bestwebsite_cant_write', array_map( 'sanitize_text_field', $verify_file_access ) );
				}

				if ( in_array( 'cache', $verify_file_access, true ) ) {
					wp_safe_redirect( $_REQUEST['wp_http_referer'] );
					exit;
				}
			} else {
				if ( bestwebsite_IS_NETWORK ) {
					delete_site_option( 'bestwebsite_cant_write' );
				} else {
					delete_option( 'bestwebsite_cant_write' );
				}
			}

			$defaults       = bestwebsite_Config::factory()->defaults;
			$current_config = bestwebsite_Config::factory()->get();

			foreach ( $defaults as $key => $default ) {
				$clean_config[ $key ] = $current_config[ $key ];

				if ( isset( $_REQUEST['bestwebsite_simple_cache'][ $key ] ) ) {
					$clean_config[ $key ] = call_user_func( $default['sanitizer'], $_REQUEST['bestwebsite_simple_cache'][ $key ] );
				}
			}

			// Back up configration in options.
			if ( bestwebsite_IS_NETWORK ) {
				update_site_option( 'bestwebsite_simple_cache', $clean_config );
			} else {
				update_option( 'bestwebsite_simple_cache', $clean_config );
			}

			bestwebsite_Config::factory()->write( $clean_config );

			if ( ! apply_filters( 'bestwebsite_disable_auto_edits', false ) ) {
				bestwebsite_Advanced_Cache::factory()->write();
				bestwebsite_Object_Cache::factory()->write();

				if ( $clean_config['enable_page_caching'] ) {
					bestwebsite_Advanced_Cache::factory()->toggle_caching( true );
				} else {
					bestwebsite_Advanced_Cache::factory()->toggle_caching( false );
				}
			}

			// Reschedule cron events.
			bestwebsite_Cron::factory()->unschedule_events();
			bestwebsite_Cron::factory()->schedule_events();

			if ( ! empty( $_REQUEST['wp_http_referer'] ) ) {
				wp_safe_redirect( $_REQUEST['wp_http_referer'] );
				exit;
			}
		}
	}

	/**
	 * Output settings
	 *
	 * @since 1.0
	 */
	public function screen_options() {

		$config = bestwebsite_Config::factory()->get();

		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Simple Cache Settings', 'bestwebsite-simple-cache' ); ?></h1>

			<form action="" method="post">
				<?php wp_nonce_field( 'bestwebsite_update_settings', 'bestwebsite_settings_nonce' ); ?>
				<input type="hidden" name="action" value="bestwebsite_update">
				<input type="hidden" name="wp_http_referer" value="<?php echo esc_attr( wp_unslash( $_SERVER['REQUEST_URI'] ) ); ?>'" />

				<div class="advanced-mode-wrapper">
					<label for="bestwebsite_advanced_mode"><?php esc_html_e( 'Enable Advanced Mode', 'bestwebsite-simple-cache' ); ?></label>
					<select name="bestwebsite_simple_cache[advanced_mode]" id="bestwebsite_advanced_mode">
						<option value="0"><?php esc_html_e( 'No', 'bestwebsite-simple-cache' ); ?></option>
						<option <?php selected( $config['advanced_mode'], true ); ?> value="1"><?php esc_html_e( 'Yes', 'bestwebsite-simple-cache' ); ?></option>
					</select>
				</div>

				<table class="form-table sc-simple-mode-table <?php if ( empty( $config['advanced_mode'] ) ) : ?>show<?php endif; ?>">
					<tbody>
						<tr>
							<th scope="row"><label for="bestwebsite_enable_page_caching_simple"><span class="setting-highlight">*</span><?php esc_html_e( 'Enable Caching', 'bestwebsite-simple-cache' ); ?></label></th>
							<td>
								<select <?php if ( ! empty( $config['advanced_mode'] ) ) : ?>disabled<?php endif; ?> name="bestwebsite_simple_cache[enable_page_caching]" id="bestwebsite_enable_page_caching_simple">
									<option value="0"><?php esc_html_e( 'No', 'bestwebsite-simple-cache' ); ?></option>
									<option <?php selected( $config['enable_page_caching'], true ); ?> value="1"><?php esc_html_e( 'Yes', 'bestwebsite-simple-cache' ); ?></option>
								</select>

								<p class="description"><?php esc_html_e( 'Turn this on to get started. This setting turns on caching and is really all you need.', 'bestwebsite-simple-cache' ); ?></p>
							</td>
						</tr>
						<tr>
							<th scope="row"><label for="bestwebsite_page_cache_length_simple"><?php esc_html_e( 'Expire the cache after', 'bestwebsite-simple-cache' ); ?></label></th>
							<td>
								<input <?php if ( ! empty( $config['advanced_mode'] ) ) : ?>disabled<?php endif; ?> size="5" id="bestwebsite_page_cache_length_simple" type="text" value="<?php echo (float) $config['page_cache_length']; ?>" name="bestwebsite_simple_cache[page_cache_length]">
								<select <?php if ( ! empty( $config['advanced_mode'] ) ) : ?>disabled<?php endif; ?> name="bestwebsite_simple_cache[page_cache_length_unit]" id="bestwebsite_page_cache_length_unit_simple">
									<option <?php selected( $config['page_cache_length_unit'], 'minutes' ); ?> value="minutes"><?php esc_html_e( 'minutes', 'bestwebsite-simple-cache' ); ?></option>
									<option <?php selected( $config['page_cache_length_unit'], 'hours' ); ?> value="hours"><?php esc_html_e( 'hours', 'bestwebsite-simple-cache' ); ?></option>
									<option <?php selected( $config['page_cache_length_unit'], 'days' ); ?> value="days"><?php esc_html_e( 'days', 'bestwebsite-simple-cache' ); ?></option>
									<option <?php selected( $config['page_cache_length_unit'], 'weeks' ); ?> value="weeks"><?php esc_html_e( 'weeks', 'bestwebsite-simple-cache' ); ?></option>
								</select>
							</td>
						</tr>

						<?php if ( function_exists( 'gzencode' ) ) : ?>
							<tr>
								<th scope="row"><label for="bestwebsite_enable_gzip_compression_simple"><?php esc_html_e( 'Enable Compression', 'bestwebsite-simple-cache' ); ?></label></th>
								<td>
									<select <?php if ( ! empty( $config['advanced_mode'] ) ) : ?>disabled<?php endif; ?> name="bestwebsite_simple_cache[enable_gzip_compression]" id="bestwebsite_enable_gzip_compression_simple">
										<option value="0"><?php esc_html_e( 'No', 'bestwebsite-simple-cache' ); ?></option>
										<option <?php selected( $config['enable_gzip_compression'], true ); ?> value="1"><?php esc_html_e( 'Yes', 'bestwebsite-simple-cache' ); ?></option>
									</select>

									<p class="description"><?php esc_html_e( 'When enabled, pages will be compressed. This is a good thing! This should always be enabled unless it causes issues.', 'bestwebsite-simple-cache' ); ?></p>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>

				<table class="form-table sc-advanced-mode-table <?php if ( ! empty( $config['advanced_mode'] ) ) : ?>show<?php endif; ?>">
					<tbody>
						<tr>
							<th scope="row" colspan="2">
								<h2 class="cache-title"><?php esc_html_e( 'Page Cache', 'bestwebsite-simple-cache' ); ?></h2>
							</th>
						</tr>

						<tr>
							<th scope="row"><label for="bestwebsite_enable_page_caching_advanced"><?php esc_html_e( 'Enable Page Caching', 'bestwebsite-simple-cache' ); ?></label></th>
							<td>
								<select <?php if ( empty( $config['advanced_mode'] ) ) : ?>disabled<?php endif; ?> name="bestwebsite_simple_cache[enable_page_caching]" id="bestwebsite_enable_page_caching_advanced">
									<option value="0"><?php esc_html_e( 'No', 'bestwebsite-simple-cache' ); ?></option>
									<option <?php selected( $config['enable_page_caching'], true ); ?> value="1"><?php esc_html_e( 'Yes', 'bestwebsite-simple-cache' ); ?></option>
								</select>

								<p class="description"><?php esc_html_e( 'When enabled, entire front end pages will be cached.', 'bestwebsite-simple-cache' ); ?></p>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="bestwebsite_cache_exception_urls"><?php esc_html_e( 'Exception URL(s)', 'bestwebsite-simple-cache' ); ?></label></th>
							<td>
								<textarea name="bestwebsite_simple_cache[cache_exception_urls]" class="widefat" id="bestwebsite_cache_exception_urls"><?php echo ebestwebsite_html( $config['cache_exception_urls'] ); ?></textarea>

								<p class="description"><?php esc_html_e( 'Allows you to add URL(s) to be exempt from page caching. One URL per line. URL(s) can be full URLs (http://google.com) or absolute paths (/my/url/). You can also use wildcards like so /url/* (matches any url starting with /url/).', 'bestwebsite-simple-cache' ); ?></p>

								<p>
									<select name="bestwebsite_simple_cache[enable_url_exemption_regex]" id="bestwebsite_enable_url_exemption_regex">
										<option value="0"><?php esc_html_e( 'No', 'bestwebsite-simple-cache' ); ?></option>
										<option <?php selected( $config['enable_url_exemption_regex'], true ); ?> value="1"><?php esc_html_e( 'Yes', 'bestwebsite-simple-cache' ); ?></option>
									</select>
									<?php esc_html_e( 'Enable Regex', 'bestwebsite-simple-cache' ); ?>
								</p>
							</td>
						</tr>

						<tr>
							<th scope="row"><label for="bestwebsite_page_cache_length_advanced"><?php esc_html_e( 'Expire page cache after', 'bestwebsite-simple-cache' ); ?></label></th>
							<td>
								<input <?php if ( empty( $config['advanced_mode'] ) ) : ?>disabled<?php endif; ?> size="5" id="bestwebsite_page_cache_length_advanced" type="text" value="<?php echo (float) $config['page_cache_length']; ?>" name="bestwebsite_simple_cache[page_cache_length]">
								<select
								<?php if ( empty( $config['advanced_mode'] ) ) : ?>disabled<?php endif; ?> name="bestwebsite_simple_cache[page_cache_length_unit]" id="bestwebsite_page_cache_length_unit_advanced">
									<option <?php selected( $config['page_cache_length_unit'], 'minutes' ); ?> value="minutes"><?php esc_html_e( 'minutes', 'bestwebsite-simple-cache' ); ?></option>
									<option <?php selected( $config['page_cache_length_unit'], 'hours' ); ?> value="hours"><?php esc_html_e( 'hours', 'bestwebsite-simple-cache' ); ?></option>
									<option <?php selected( $config['page_cache_length_unit'], 'days' ); ?> value="days"><?php esc_html_e( 'days', 'bestwebsite-simple-cache' ); ?></option>
									<option <?php selected( $config['page_cache_length_unit'], 'weeks' ); ?> value="weeks"><?php esc_html_e( 'weeks', 'bestwebsite-simple-cache' ); ?></option>
								</select>
							</td>
						</tr>
						<tr>
							<th scope="row" colspan="2">
								<h2 class="cache-title"><?php esc_html_e( 'Object Cache (Redis or Memcached)', 'bestwebsite-simple-cache' ); ?></h2>
							</th>
						</tr>

						<?php if ( class_exists( 'Memcache' ) || class_exists( 'Memcached' ) || class_exists( 'Redis' ) ) : ?>
							<tr>
								<th scope="row"><label for="bestwebsite_enable_in_memory_object_caching"><?php esc_html_e( 'Enable In-Memory Object Caching', 'bestwebsite-simple-cache' ); ?></label></th>
								<td>
									<select name="bestwebsite_simple_cache[enable_in_memory_object_caching]" id="bestwebsite_enable_in_memory_object_caching">
										<option value="0"><?php esc_html_e( 'No', 'bestwebsite-simple-cache' ); ?></option>
										<option <?php selected( $config['enable_in_memory_object_caching'], true ); ?> value="1"><?php esc_html_e( 'Yes', 'bestwebsite-simple-cache' ); ?></option>
									</select>

									<p class="description"><?php echo wp_kses_post( __( "When enabled, things like database query results will be stored in memory. Memcached and Redis are suppported. Note that if the proper <a href='http://pecl.php.net/package/memcached'>Memcached</a>, <a href='http://pecl.php.net/package/memcache'>Memcache</a>, or <a href='https://pecl.php.net/package/redis'>Redis</a> PHP extensions aren't loaded, they won't show as options below.", 'bestwebsite-simple-cache' ) ); ?></p>
								</td>
							</tr>
							<tr>
								<th class="in-memory-cache <?php if ( ! empty( $config['enable_in_memory_object_caching'] ) ) : ?>show<?php endif; ?>" scope="row"><label for="bestwebsite_in_memory_cache"><?php esc_html_e( 'In Memory Cache', 'bestwebsite-simple-cache' ); ?></label></th>
								<td class="in-memory-cache <?php if ( ! empty( $config['enable_in_memory_object_caching'] ) ) : ?>show<?php endif; ?>">
									<select name="bestwebsite_simple_cache[in_memory_cache]" id="bestwebsite_in_memory_cache">
										<?php if ( class_exists( 'Redis' ) ) : ?>
											<option <?php selected( $config['in_memory_cache'], 'redis' ); ?> value="redis">Redis</option>
										<?php endif; ?>
										<?php if ( class_exists( 'Memcached' ) ) : ?>
											<option <?php selected( $config['in_memory_cache'], 'memcachedd' ); ?> value="memcachedd">Memcached</option>
										<?php endif; ?>
										<?php if ( class_exists( 'Memcache' ) ) : ?>
											<option <?php selected( $config['in_memory_cache'], 'memcached' ); ?> value="memcached">Memcache (Legacy)</option>
										<?php endif; ?>
									</select>
								</td>
							</tr>
						<?php else : ?>
							<tr>
								<td colspan="2">
									<?php echo wp_kses_post( __( 'Neither <a href="https://pecl.php.net/package/memcached">Memcached</a>, <a href="https://pecl.php.net/package/memcache">Memcache</a>, nor <a href="https://pecl.php.net/package/redis">Redis</a> PHP extensions are set up on your server.', 'bestwebsite-simple-cache' ) ); ?>
								</td>
							</tr>
						<?php endif; ?>

						<tr>
							<th scope="row" colspan="2">
								<h2 class="cache-title"><?php esc_html_e( 'Compression', 'bestwebsite-simple-cache' ); ?></h2>
							</th>
						</tr>

						<?php if ( function_exists( 'gzencode' ) ) : ?>
							<tr>
								<th scope="row"><label for="bestwebsite_enable_gzip_compression_advanced"><?php esc_html_e( 'Enable gzip Compression', 'bestwebsite-simple-cache' ); ?></label></th>
								<td>
									<select <?php if ( empty( $config['advanced_mode'] ) ) : ?>disabled<?php endif; ?> name="bestwebsite_simple_cache[enable_gzip_compression]" id="bestwebsite_enable_gzip_compression_advanced">
										<option value="0"><?php esc_html_e( 'No', 'bestwebsite-simple-cache' ); ?></option>
										<option <?php selected( $config['enable_gzip_compression'], true ); ?> value="1"><?php esc_html_e( 'Yes', 'bestwebsite-simple-cache' ); ?></option>
									</select>

									<p class="description"><?php esc_html_e( 'When enabled pages will be gzip compressed at the PHP level. Note many hosts set up gzip compression in Apache or nginx.', 'bestwebsite-simple-cache' ); ?></p>
								</td>
							</tr>
						<?php endif; ?>
					</tbody>
				</table>

				<p class="submit">
					<input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_html_e( 'Save Changes', 'bestwebsite-simple-cache' ); ?>">
					<a class="button" style="margin-left: 10px;" href="?page=bestwebsite-simple-cache&amp;wp_http_referer=<?php echo esc_url( wp_unslash( $_SERVER['REQUEST_URI'] ) ); ?>&amp;action=bestwebsite_purge_cache&amp;bestwebsite_cache_nonce=<?php echo esc_attr( wp_create_nonce( 'bestwebsite_purge_cache' ) ); ?>"><?php esc_html_e( 'Purge Cache', 'bestwebsite-simple-cache' ); ?></a>
				</p>
			</form>
		</div>
		<?php
	}

	/**
	 * Return an instance of the current class, create one if it doesn't exist
	 *
	 * @since  1.0
	 * @return object
	 */
	public static function factory() {

		static $instance;

		if ( ! $instance ) {
			$instance = new self();
			$instance->setup();
		}

		return $instance;
	}
}
