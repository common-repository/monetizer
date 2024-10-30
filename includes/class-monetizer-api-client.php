<?php

/**
 * This class used to make requests to the Monetizer API.
 *
 * @since      1.0.0
 * @package    Monetizer
 * @subpackage Monetizer/includes
 */
class Monetizer_API_Client {
    /**
     * The instance of settings helper class.
     *
     * @since    1.0.0
     * @access   private
     * @var  Monetizer_Settings  $settings  The instance of the settings class.
     */
    private $settings;

    public function __construct($settings) {
        $this->settings = $settings;
    }

    /**
     * Checks whether the given token is valid.
     *
     * @since 1.0.0
     * @param string $token
     * @return bool|null
     */
    public function check_token($token) {
        $args = array('headers' => $this->get_headers($token), 'timeout' => 10);
        return $this->get('checkToken', [], $args);
    }

    /**
     * Get the list of active domains registered at Monetizer.
     *
     * @since 1.0.0
     * @return array
     */
    public function get_domains() {
        $domains = $this->get('domains');
        return $domains === null ? [] : $domains;
    }

    /**
     * Get the list of active links registered at Monetizer.
     *
     * @since 1.0.0
     * @return array
     */
    public function get_links() {
        $links = $this->get('links');
        return $links === null ? [] : $links;
    }

    /**
     * Get the link with specified hash ID.
     *
     * @since 1.0.0
     * @return mixed
     */
    public function get_link($hash) {
        return $this->get('links', ['hash' => $hash]);
    }

    /**
     * Create the link to use on this site.
     *
     * @since 1.0.0
     * @return mixed
     */
    public function create_link($hostname) {
        return $this->post('links', ['hostname' => $hostname]);
    }

    /**
     * Get the code of javascript scripts for activation of push monetization.
     *
     * @return array|null
     */
    public function get_push_monetization_scripts() {
        $params = array();
        $fields = array(
            'allow_url' => 'allowURL',
            'deny_url' => 'denyURL',
            'tracking_tag' => 'trackingTag',
            'enable_prompt' => 'enablePrompt',
            'prompt_text' => 'promptText',
            'prompt_accept_btn_text' => 'promptAcceptBtnText',
            'prompt_deny_btn_text' => 'promptDenyBtnText',
            'prompt_hide_deny_btn' => 'hideDenyBtn'
        );
        foreach ($fields as $field_name => $param_name) {
            $field_value = $this->settings->get_field_value($field_name, 'push');
            if (isset($field_value)) {
                $params[$param_name] = $field_value;
            }
        }
        return $this->get('pushMonetizationScripts', $params);
    }

    private function get($endpoint, $params = [], $args = []) {
        $url = $this->get_base_url() . $endpoint . '.php';
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        if (empty($args)) {
            $token = $this->settings->get_token();
            $args = array('headers' => $this->get_headers($token), 'timeout' => 10);
        }

        return $this->parse_response(wp_remote_get($url, $args));
    }

    private function post($endpoint, $body = []) {
        $url = $this->get_base_url() . $endpoint . '.php';
        $token = $this->settings->get_token();
        $args = array(
            'headers' => $this->get_headers($token),
            'body' => $body,
            'timeout' => 10
        );
        return $this->parse_response(wp_remote_post($url, $args));
    }

    private function get_base_url() {
        return 'https://api.monetizer.co/wp/';
    }

    private function get_headers($token) {
        return array(
            'Accept' => 'application/json',
            'X_AFFLOW_WP_PLUGIN_API_TOKEN' => $token
        );
    }

    private function parse_response($response) {
        $body = wp_remote_retrieve_body($response);
        $data = !is_wp_error($response) ? json_decode($body, true) : null;

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code >= 400) {
            if (isset($data['message'])) {
                $this->settings->add_message($data['message'], 'error');
            }

            if ($response_code == 403) {
                $this->settings->disable_modules();
            }

            return null;
        }


        return isset($data['result']) ? $data['result'] : null;
    }

}
