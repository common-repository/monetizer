<?php

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Monetizer
 * @subpackage Monetizer/includes
 */
class Monetizer {
    const ACTION_SCHEDULE_UPDATE_DATA = 'monetizer_schedule_update_sw_link';

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

    /**
     * The instance of settings helper class.
     *
     * @since    1.0.0
     * @access   private
     * @var  Monetizer_Settings  $settings  The instance of the settings class.
     */
    private $settings;

	/**
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'MONETIZER_VERSION' ) ) {
			$this->version = MONETIZER_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'monetizer';
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {
		require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-monetizer-i18n.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-monetizer-settings.php';
        require_once plugin_dir_path(dirname(__FILE__)) . 'includes/class-monetizer-api-client.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'admin/class-monetizer-admin.php';
		require_once plugin_dir_path(dirname(__FILE__)) . 'public/class-monetizer-public.php';
	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Monetizer_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale() {
		$plugin_i18n = new Monetizer_i18n();

        add_action('plugins_loaded', array($plugin_i18n, 'load_plugin_textdomain'));
	}

    /**
     * Initialize settings class
     *
     * @since 1.0.0
     * @access private
     */
	private function init_settings() {
        $this->settings = new Monetizer_Settings($this->plugin_name);
    }

	/**
	 * Register all the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {
		$plugin_admin = new Monetizer_Admin( $this->get_plugin_name(), $this->get_version(), $this->settings );
		$plugin_admin->define_hooks();
	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		$plugin_public = new Monetizer_Public( $this->get_plugin_name(), $this->get_version(), $this->settings );
		$plugin_public->define_hooks();
	}

	/**
     * Load the dependencies, define the locale, and set the hooks for the admin area and
     * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function run() {
        $this->load_dependencies();
        $this->set_locale();
        $this->init_settings();
        $this->define_admin_hooks();
        $this->define_public_hooks();
    }

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
