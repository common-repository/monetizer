<?php

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Monetizer
 * @subpackage Monetizer/admin
 */
class Monetizer_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

    /**
     * The instance of settings helper class.
     *
     * @since    1.0.0
     * @access   private
     * @var  Monetizer_Settings  $settings  The instance of the settings class.
     */
	private $settings;

    /**
     * The instance of the API client class used to make calls to Monetizer's API.
     *
     * @since  1.0.0
     * @access  private
     * @var  Monetizer_API_Client $api The instance of the API client class.
     */
    private $api;


    /**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of this plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version, $settings) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
		$this->settings = $settings;
		$this->api = new Monetizer_API_Client($this->settings);
	}

    /**
     * Define hooks for admin area.
     *
     * @since    1.0.0
     */
	public function define_hooks() {
	    add_action('admin_menu', array($this, 'create_menu'));
	    add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('pre_update_option_monetizer_modules_popup', array($this, 'popup_module_status_updated'));
        add_action(Monetizer::ACTION_SCHEDULE_UPDATE_DATA, array($this, 'update_data'));
    }

    /**
     * Create the menu items in the admin menu.
     *
     * @since    1.0.0
     */
    public function create_menu() {
	    add_menu_page(
	        __('Monetizer Settings', 'monetizer'),
            __('Monetizer', 'monetizer'),
            'manage_options',
            $this->plugin_name,
            array($this, 'display_settings_page'),
            plugin_dir_url( __FILE__ ) . 'images/monetizer-icon.png'
        );
    }

    /**
     * Render the content of the settings page.
     *
     * @since    1.0.0
     */
    public function display_settings_page() {
        $this->update_settings_state();
        include_once 'partials/monetizer-admin-settings-page.php';
    }

    private function update_settings_state() {
        $this->settings->add_section('general');
        if (!$this->settings->is_token_valid()) {
            $this->settings->add_message(
                'Please enter your API token to use the Monetizer wordpress plugin. '.
                '<a href="https://app.monetizer.com/profile" target="_blank">You can find your token at your Monetizer profile page</a>. <br>'.
                'If you do not have a Monetizer account yet, you can <a href="https://monetizer.com/" target="_blank">create an account</a> and start using the wordpress plugin.',
                'info'
            );
            $this->settings->disable_modules();
        } else if (empty($this->settings->get_domains())) {
            $this->settings->add_message(
                'You must setup a domain in your Monetizer account before you can start sending traffic and use plugin features. '.
                '<a href="https://app.monetizer.com/domains" target="_blank">Add domain</a>.',
                'info'
            );
            $this->settings->disable_modules();
        } else {
            $this->settings->add_section('modules');

            if ($this->settings->is_module_enabled('push')) {
                $scripts = $this->settings->update_push_scripts();
                $this->settings->add_section('push');
            }

            $url_updated = false;
            if ($this->settings->is_module_enabled('links')) {
                $this->update_link_url();
                $url_updated = true;
                $this->settings->add_section('links');
            }

            if ($this->settings->is_module_enabled('redirect')) {
                if (!$url_updated) {
                    $this->update_link_url();
                    $url_updated = true;
                }
                $this->settings->add_section('redirect');
            }

            if ($this->settings->is_module_enabled('popup')) {
                if (!$url_updated) {
                    $this->update_link_url();
                }
                $this->settings->add_section('popup');
            }
        }
    }

    /**
     * Creates settings fields and register them via Settings API
     *
     * @since    1.0.0
     */
    public function register_settings() {
        $this->register_general_settings();

        if (!empty($this->settings->get_token())) {
            $this->register_modules_settings();

            if ($this->settings->is_module_enabled('push')) {
                $this->register_push_settings();
            }

            if ($this->settings->is_module_enabled('links')) {
                $this->register_links_settings();
            }

            if ($this->settings->is_module_enabled('redirect')) {
                $this->register_redirect_settings();
            }

            if ($this->settings->is_module_enabled('popup')) {
                $this->register_popup_settings();
            }
        }
    }

    /**
     * Register settings fields for general settings section.
     *
     * @since    1.0.0
     */
    private function register_general_settings() {
        $this->settings->register_section(array(
            'id' => 'general',
            'title' => 'General Settings',
            'description' => 'Settings that change the general behaviour of Monetizer plugin.'
        ));

        $this->settings->register_field(array(
            'id' => 'token',
            'section_id' => 'general',
            'title' => 'API Token',
            'type' => 'textfield',
            'description' => 'You can find your API token at the bottom of your <a href="https://app.monetizer.com/profile" target="_blank">profile page</a> in your monetizer account.',
            'data_type' => 'string',
            'sanitize_callback' => 'token_sanitize_callback',
            'default' => '',
        ));
    }

    /**
     * Register settings fields for modules settings section.
     *
     * @since    1.0.0
     */
    private function register_modules_settings() {
        $this->settings->register_section(array(
            'id' => 'modules',
            'title' => 'Modules',
            'description' => 'This section enables you to turn certain features of the Monetizer plugin on or off'
        ));

        $this->settings->register_field(array(
            'id' => 'push',
            'section_id' => 'modules',
            'title' => 'Push Monetization',
            'type' => 'checkbox',
            'label_content' => 'Activate push monetization',
            'tooltip' => 'Allow the Monetizer plugin to collect push subscribers from your website',
            'default' => 0
        ));

        $this->settings->register_field(array(
            'id' => 'links',
            'section_id' => 'modules',
            'title' => 'Links',
            'type' => 'checkbox',
            'label_content' => 'Activate in-text links',
            'tooltip' => 'Allows you to add keywords in order to automatically link them to your monetizer smart link',
            'default' => 0
        ));

        $this->settings->register_field(array(
            'id' => 'redirect',
            'section_id' => 'modules',
            'title' => 'Redirection',
            'type' => 'checkbox',
            'label_content' => 'Activate auto redirection of certain browsers/OSs',
            'default' => 0
        ));

        $this->settings->register_field(array(
            'id' => 'popup',
            'section_id' => 'modules',
            'title' => 'Popup Ads',
            'type' => 'checkbox',
            'label_content' => 'Activate popups',
            'default' => 0
        ));
    }

    /**
     * Register settings fields for push monetization module.
     *
     * @since 1.0.0
     */
    private function register_push_settings() {
       $this->settings->register_section(array(
           'id' => 'push',
           'title' => 'Push Monetization Settings',
           'description' => 'This section allow you to configure the code that collects push subscribers.'
       ));

       $this->settings->register_field(array(
           'id' => 'allow_url',
           'section_id' => 'push',
           'title' => 'Redirect URL on Allow (optional)',
           'description' => 'The URL a user will be redirected to when "Allow" is being clicked. Leave blank for the user to stay on your website.',
           'type' => 'textfield',
           'data_type' => 'string',
           'sanitize_callback' => 'allow_url_sanitize_callback',
           'default' => ''
       ));

        $this->settings->register_field(array(
            'id' => 'deny_url',
            'section_id' => 'push',
            'title' => 'Redirect URL on Deny (optional)',
            'description' => 'The URL a user will be redirected to when "Deny" is being clicked. Leave blank for the user to stay on your website.',
            'type' => 'textfield',
            'data_type' => 'string',
            'sanitize_callback' => 'deny_url_sanitize_callback',
            'default' => ''
        ));

        $this->settings->register_field(array(
            'id' => 'tracking_tag',
            'section_id' => 'push',
            'title' => 'Tracking tag for reporting (optional)',
            'description' => 'Tracking tag for reporting. (letters and numbers only)',
            'type' => 'textfield',
            'data_type' => 'string',
            'sanitize_callback' => 'tracking_tag_sanitize_callback',
            'default' => ''
        ));

        $this->settings->register_field(array(
            'id' => 'enable_prompt',
            'section_id' => 'push',
            'title' => 'Interaction Prompt',
            'type' => 'checkbox',
            'label_content' => 'Enable interaction prompt',
            'default' => 0
        ));

        $this->settings->register_field(array(
            'id' => 'prompt_text',
            'section_id' => 'push',
            'title' => 'Prompt text',
            'description' => 'Custom prompt message (optional)',
            'type' => 'textfield',
            'data_type' => 'string',
            'default' => ''
        ));

        $this->settings->register_field(array(
            'id' => 'prompt_accept_btn_text',
            'section_id' => 'push',
            'title' => '"Accept button" text',
            'description' => 'Custom "Accept button" text (optional)',
            'type' => 'textfield',
            'data_type' => 'string',
            'default' => ''
        ));

        $this->settings->register_field(array(
            'id' => 'prompt_deny_btn_text',
            'section_id' => 'push',
            'title' => '"Deny button" text',
            'description' => 'Custom "Deny button" text (optional)',
            'type' => 'textfield',
            'data_type' => 'string',
            'default' => ''
        ));

        $this->settings->register_field(array(
            'id' => 'prompt_hide_deny_btn',
            'section_id' => 'push',
            'title' => '',
            'type' => 'checkbox',
            'label_content' => 'Hide "Deny" button',
            'default' => 0
        ));
    }

    /**
     * Registers settings for in-text links.
     *
     * @since 1.0.0
     */
    private function register_links_settings()
    {
        $this->settings->register_section(array(
            'id' => 'links',
            'title' => 'Links Settings',
            'description' => 'This section allow you to configure in-text links.'
        ));

        $this->settings->register_field(array(
            'id' => 'keywords',
            'section_id' => 'links',
            'title' => 'Keywords',
            'description' => 'A comma-separated list of keywords that will be associated with selected link.',
            'type' => 'textfield',
            'data_type' => 'string',
            'sanitize_callback' => 'keywords_sanitize_callback',
            'default' => ''
        ));

        $this->settings->register_field(array(
            'id' => 'max_count',
            'section_id' => 'links',
            'title' => 'Number of links',
            'description' => 'How often should the link appear on a page? Choose -1 for unlimited links.',
            'type' => 'textfield',
            'data_type' => 'integer',
            'sanitize_callback' => 'max_count_sanitize_callback',
            'default' => '-1'
        ));
    }

    /**
     * Registers settings for auto redirection.
     *
     * @since 1.0.0
     */
    private function register_redirect_settings()
    {
        $this->settings->register_section(array(
            'id' => 'redirect',
            'title' => 'Redirection Settings',
            'description' => "This section allow you to configure auto redirection of certain browsers or OSs to your Monetizer's smart link."
        ));

        $this->settings->register_field(array(
            'id' => 'browsers',
            'section_id' => 'redirect',
            'title' => 'Browsers',
            'description' => 'Select browsers for auto redirection.',
            'type' => 'selectbox',
            'multiple' => true,
            'options' => Monetizer_Settings::BROWSERS,
            'data_type' => 'array',
            'sanitize_callback' => 'browsers_sanitize_callback',
            'default' => []
        ));

        $this->settings->register_field(array(
            'id' => 'oss',
            'section_id' => 'redirect',
            'title' => 'OSs',
            'description' => 'Select OSs for auto redirection.',
            'type' => 'selectbox',
            'multiple' => true,
            'options' => Monetizer_Settings::OSES,
            'data_type' => 'array',
            'sanitize_callback' => 'oss_sanitize_callback',
            'default' => []
        ));
    }

    /**
     * Registers settings for popup ads.
     *
     * @since 1.0.0
     */
    private function register_popup_settings() {
        $this->settings->register_section(array(
            'id' => 'popup',
            'title' => 'Popup Settings',
            'description' => "This section allow you to configure popup ads settings."
        ));

        $this->settings->register_field(array(
            'id' => 'period',
            'section_id' => 'popup',
            'title' => 'Capping period',
            'description' => 'The capping frequency period in hours.',
            'type' => 'selectbox',
            'options' => Monetizer_Settings::CAPPING_PERIOD_OPTIONS,
            'data_type' => 'array',
            'sanitize_callback' => 'capping_period_sanitize_callback',
            'default' => Monetizer_Settings::CAPPING_PERIOD_DEFAULT
        ));

        $this->settings->register_field(array(
            'id' => 'frequency',
            'section_id' => 'popup',
            'title' => 'Frequency',
            'description' => 'How many times popup will be shown within one capping period.',
            'type' => 'selectbox',
            'options' => Monetizer_Settings::FREQUENCY_CAPPING_OPTIONS,
            'data_type' => 'array',
            'sanitize_callback' => 'frequency_capping_sanitize_callback',
            'default' => Monetizer_Settings::CAPPING_FREQUENCY_DEFAULT
        ));
    }

    /**
     * When popup activated and no settings set then set default settings.
     *
     * @param $value
     * @return int|mixed
     */
    public function popup_module_status_updated($value) {
        if ($value === 1) {
            $period = $this->settings->get_field_value('period', 'popup');
            if (!$period) {
                $this->settings->set_field_value('period', 'popup', Monetizer_Settings::CAPPING_PERIOD_DEFAULT);
            }

            $frequency = $this->settings->get_field_value('frequency', 'popup');
            if (!$frequency) {
                $this->settings->set_field_value('frequency', 'popup', Monetizer_Settings::CAPPING_FREQUENCY_DEFAULT);
            }
        }
        return $value;
    }

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
		    $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'css/monetizer-admin.css',
            array(),
            false
        );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
        wp_enqueue_script($this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'js/monetizer-admin.js',
            array( 'jquery' ),
            false,
            false
        );
	}

    /**
     * Updates service worker and link url if dependent modules are enabled. Should run by WP Cron.
     *
     * @since 1.0.0
     */
    public function update_data() {
        if ($this->settings->is_module_enabled('push')) {
            $scripts = $this->settings->update_push_scripts();
        }

        if (
            $this->settings->is_module_enabled('links') ||
            $this->settings->is_module_enabled('redirect') ||
            $this->settings->is_module_enabled('popup')
        ) {
            $this->update_link_url(false);
        }
    }

    /**
     * Updates the url used in text links.
     *
     * @since 1.0.0
     * @param bool $reportError
     */
    public function update_link_url($reportError = true)
    {
        $current_url = $this->settings->get_field_value('url', 'links');
        $hash = $this->get_url_hash($current_url);
        $host = parse_url(get_site_url(), PHP_URL_HOST);
        $url = '';
        $action = 'update';
        if ($hash) {
            $link = $this->api->get_link($hash);
            if (isset($link['url'])) {
                $url = $link['url'];
            }
        }

        if (empty($url)) {
            $links = $this->api->get_links();
            foreach ($links as $link) {
                if ($link['name'] === 'Link for ' . $host) {
                    $url = $link['url'];
                    break;
                }
            }
        }

        if (empty($url)) {
            $action = 'create';
            $link = $this->api->create_link($host);
            if (isset($link['url'])) {
                $url = $link['url'];
            }
        }

        if (empty($url)) {
            if ($reportError) {
                $this->settings->add_message(
                    "Can't $action url of smart link. Please contact support at ".
                    '<a href="https://app.monetizer.com/" target="_blank">Monetizer</a>.',
                    'error'
                );
            }
        } else if ($url !== $current_url) {
            $this->settings->set_field_value('url', 'links', $url);
        }
    }

    /**
     * Extracts from given url its hash id.
     *
     * @since 1.0.0
     * @param $url
     * @return string|null
     */
    public function get_url_hash($url)
    {
        if (!$url) {
            return null;
        }

        $query = parse_url($url, PHP_URL_QUERY);
        if (!$query) {
            return null;
        }

        parse_str($query, $query_array);
        return $query_array['utm_medium'] ?? null;
    }

}
