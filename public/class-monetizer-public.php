<?php

use html_changer\HtmlChanger;

/**
 * The public-facing functionality of the plugin.
 *
 * @package    Monetizer
 * @subpackage Monetizer/public
 */
class Monetizer_Public {

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
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param      string    $plugin_name       The name of the plugin.
	 * @param      string    $version    The version of this plugin.
	 */
	public function __construct($plugin_name, $version, $settings) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
        $this->settings = $settings;
	}

    /**
     * Define hooks public-facing functionality
     *
     * @since    1.0.0
     */
    public function define_hooks() {
        add_action('template_redirect', array($this, 'handle_redirect'));
        add_action('parse_request', array($this, 'handle_parse_request'));
        add_action('wp_head', array($this, 'head_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_styles'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('the_content', array($this, 'insert_links'), 90);
    }

    /**
     * Determines whether auto redirection should be performed and if yes redirect.
     *
     * @since 1.0.0
     */
    public function handle_redirect()
    {
        if (is_admin()) {
            return;
        }

        if ($this->settings->is_module_enabled('redirect')) {
            $redirect_browsers = $this->settings->get_field_value('browsers', 'redirect');
            $browser = $this->determine_browser();

            if (!empty($redirect_browsers) && in_array($browser, $redirect_browsers)) {
                $this->redirect();
            }

            $redirect_oss = $this->settings->get_field_value('oss', 'redirect');
            $os = $this->determine_os();

            if (!empty($redirect_oss) && in_array($os, $redirect_oss)) {
                $this->redirect();
            }
        }
    }

    /**
     * Redirect to smart link.
     *
     * @since 1.0.0
     */
    private function redirect() {
        $url = $this->settings->get_field_value('url', 'links');
        if (!empty($url)) {
            wp_redirect(wp_sanitize_redirect($url));
            exit();
        }
    }

    public function handle_parse_request($wp) {
        if (isset($wp->query_vars['pagename']) && $wp->query_vars['pagename'] === 'monetizer-sw.js') {
            if (!$this->settings->is_module_enabled('push')) {
                return;
            }

            $sw_code = $this->settings->get_field_value('service_worker_code', 'push');
            if (empty($sw_code)) {
                return;
            }

            header("Service-Worker-Allowed: /");
            header("X-Robots-Tag: none");
            header("Content-Type: application/javascript; charset=utf-8");
            echo wp_kses($sw_code, []);
            exit();
        }
    }

    public function head_scripts()
    {
        if ($this->settings->is_module_enabled('push')) {
            $script_code = $this->settings->get_field_value('page_head_script_code', 'push');
            if (!empty($script_code)) {
                echo wp_kses($script_code, ['script' => ['type' => array(), 'src' => array(), 'async' => array()]]) . "\n\n";
            }
        }
    }

	/**
	 * Register the stylesheets for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style(
		    $this->plugin_name,
            plugin_dir_url( __FILE__ ) . 'css/monetizer-public.css',
            array(), $this->version,
            'all'
        );
	}

	/**
	 * Register the JavaScript for the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		if ($this->settings->is_module_enabled('popup')) {
            $period = $this->settings->get_field_value('period', 'popup', Monetizer_Settings::CAPPING_PERIOD_DEFAULT);
            $frequency = $this->settings->get_field_value('frequency', 'popup', Monetizer_Settings::CAPPING_FREQUENCY_DEFAULT);
		    $url = sanitize_url($this->settings->get_field_value('url', 'links'));
		    if (empty($url) || empty($period) || empty($frequency)) {
		        return;
            }

            wp_enqueue_script(
                $this->plugin_name,
                plugin_dir_url( __FILE__ ) . 'js/monetizer-public.js',
                array( 'jquery' ),
                $this->version,
                false
            );

            wp_localize_script(
                $this->plugin_name,
                'monetizerPublicSettings',
                [
                    'pops' => [
                        'enabled' => true,
                        'period' => (int) $period,
                        'frequency' => (int) $frequency,
                        'delay' => 30000, //ms
                        'url' => $url
                    ]
                ]
            );
        }
	}

    /**
     * Modifies the content of posts and pages by inserting links.
     *
     * @param $content
     * @return mixed
     */
	public function insert_links($content) {
	    if (!$this->settings->is_module_enabled('links')) {
	        return $content;
        }

	    $url = sanitize_url($this->settings->get_field_value('url', 'links'));
	    $keywords = $this->settings->get_field_value('keywords', 'links');
	    $max_count = $this->settings->get_field_value('max_count', 'links', -1);

	    if (empty($url) || empty($keywords)) {
	        return $content;
        }

	    $search = [];
	    $keywords = explode(',', $keywords);
	    foreach ($keywords as $keyword) {
	        $keyword = trim($keyword);
	        if (empty($keyword)) {
	            continue;
            }

	        $search[$keyword] = [
                'value' => $url,
                'caseInsensitive' => false,
                'wordBoundary' => true,
                'group' => 'links',
                'maxCount' => intval($max_count), // default -1, means no restriction
            ];
        }

        $htmlChanger = new HtmlChanger($content, [
            'search' => $search,
            'ignore' => [
                'h1',
                'h2',
                'a',
            ]
        ]);

        $htmlChanger->replace(function ($text, $value) {
            return '<a href="'. $value .'" rel="nofollow">' . $text . '</a>';
        });

        return $htmlChanger->html();
    }

    /**
     * Determines current user browser.
     *
     * @since 1.0.0
     * @return string
     */
    private function determine_browser() {
        global $is_chrome, $is_safari, $is_edge, $is_gecko, $is_IE, $is_opera;

        if ($is_chrome) {
            return 'Chrome';
        } else if ($is_safari) {
            return 'Safari';
        } else if ($is_edge) {
            return 'Microsoft Edge';
        } else if ($is_gecko) {
            return 'Firefox';
        } else if ($is_IE) {
            return 'Internet Explorer';
        } else if ($is_opera) {
            return 'Opera';
        }

        return 'Other';
    }

    /**
     * Determine current user OS.
     *
     * @since 1.0.0
     * @return string
     */
    private function determine_os() {
        if (isset($_SERVER['HTTP_USER_AGENT'])) {
            $http_agent = strtolower(sanitize_text_field(wp_unslash($_SERVER['HTTP_USER_AGENT'])));

            if (strstr($http_agent, 'android')) {
                return 'Android';
            } else if (strstr($http_agent, 'windows')) {
                return 'Windows';
            } else if (strstr($http_agent, 'iphone') || strstr($http_agent, 'ipad') || strstr($http_agent, 'ipod')) {
                return 'IOS';
            } else if (strstr($http_agent, 'macintosh') || strstr($http_agent, 'mac os x') || strstr($http_agent, 'mac_powerpc')) {
                return 'OS X (Mac OS)';
            } else if (strstr($http_agent, 'linux')) {
                return 'Linux';
            } else if (strstr($http_agent, 'kindle')) {
                return 'Kindle';
            } else if (strstr($http_agent, 'blackberry')) {
                return 'BlackBerry';
            }
        }

        return 'Other';
    }
}
