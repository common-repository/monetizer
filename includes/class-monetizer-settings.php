<?php

/**
 * This class provides helper functions for registering, managing and rendering settings.
 *
 * @since      1.0.0
 * @package    Monetizer
 * @subpackage Monetizer/includes
 */
class Monetizer_Settings {
    /**
     * List of allowed browser options.
     *
     * @since 1.0.0
     */
    const BROWSERS = [
        'Chrome',
        'Safari',
        'Microsoft Edge',
        'Firefox',
        'Internet Explorer',
        'Opera',
        'Other'
    ];

    /**
     * List of allowed OSs options.
     *
     * @since 1.0.0
     */
    const OSES = [
        'Android',
        'Windows',
        'IOS',
        'OS X (Mac OS)',
        'Linux',
        'Kindle',
        'BlackBerry',
        'Other'
    ];

    /**
     * List of allowed capping period options
     *
     * @since 1.0.0
     */
    const CAPPING_PERIOD_OPTIONS = [1, 2, 3, 4, 6, 8, 12, 24];

    /**
     * Capping period default value.
     *
     * @since 1.0.0
     */
    const CAPPING_PERIOD_DEFAULT = 24;

    /**
     * List of allowed frequency capping options
     *
     * @since 1.0.0
     */
    const FREQUENCY_CAPPING_OPTIONS = [1, 2, 3, 4, 5];

    /**
     * Frequency capping default value.
     *
     * @since 1.0.0
     */
    const CAPPING_FREQUENCY_DEFAULT = 1;

    /**
     * The slug-name of the settings page.
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $settings_page   The slug-name of the settings page.
     */
    private $settings_page;

    /**
     * Unique options prefix for plugin
     *
     * @since    1.0.0
     * @access   private
     * @var      string    $settings_prefix Unique options prefix for plugin
     */
    private $settings_prefix;

    /**
     * The instance of the API client class used to make calls to Monetizer's API.
     *
     * @since  1.0.0
     * @access  private
     * @var  Monetizer_API_Client $api The instance of the API client class.
     */
    private $api;

    /**
     * List of active domains that may be used to access the Monetizer.
     *
     * @since 1.0.0
     * @access private
     * @var array $domains
     */
    private $domains;

    /**
     * List of active links from Monetizer account that may be used to create in-text links in WordPress.
     *
     * @since 1.0.0
     * @access private
     * @var array $links
     */
    private $links;

    /**
     * The API token value;
     *
     * @since 1.0.0
     * @access private
     * @var string $token
     */
    private $token;

    /**
     * List of settings and fields configs.
     *
     * @var array $configs
     */
    private $configs;

    /**
     * Initialize the class and sets its properties.
     *
     * @since    1.0.0
     * @param string $settings_page  Settings page slug.
     */
    public function __construct($settings_page)
    {
        $this->settings_page = $settings_page;
        $this->settings_prefix = str_replace('-', '_', $this->settings_page);
        $this->api = new Monetizer_API_Client($this);
        $this->configs = [];
    }

    /**
     * Get the list of active domains registered at Monetizer.
     *
     * @since 1.0.0
     * @return array
     */
    public function get_domains()
    {
        if (!isset($this->domains)) {
            $this->domains = $this->api->get_domains();
        }
        return $this->domains;
    }

    /**
     * Get the list of links registered at Monetizer.
     *
     * @since 1.0.0
     * @return array
     */
    public function get_links()
    {
        if (!isset($this->links)) {
            $this->links = $this->api->get_links();
        }
        return $this->links;
    }

    /**
     * Register the section config.
     *
     * @since 1.0.0
     * @param array $config
     */
    public function register_section($config) {
        $this->configs['section'][$config['id']] = $config;
        $this->configs[$config['id']] = [];
    }

    /**
     * Add a new section and all its fields to a plugin's settings page.
     *
     * @since 1.0.0
     * @param string $section_id  ID of the section.
     */
    public function add_section($section_id) {
        if (!isset($this->configs['section'][$section_id])) {
            return;
        }

        $config = $this->configs['section'][$section_id];

        add_settings_section(
            $this->get_section_id($config['id']),
            __($config['title'], 'monetizer'),
            $this->get_section_callback($config['description']),
            $this->settings_page
        );

        foreach ($this->configs[$section_id] as $config) {
            $this->add_field($config);
        }
    }

    /**
     * Get full ID of the plugin settings section.
     *
     * @param string $id Short ID of the settings section.
     * @return string    Full ID of the settings section.
     */
    private function get_section_id($id) {
        return sprintf('%s_%s', $this->settings_prefix, $id);
    }

    /**
     * Create callback for add_settings_section function.
     *
     * @since 1.0.0
     * @param $description
     * @return Closure Callback function that echoes out content at the top of the section.
     */
    private function get_section_callback($description) {
        return function () use ($description) {
            if (!empty($description)) {
                echo '<p>'. wp_kses($description, ['a' => ['href' => true, 'target' => true]]) .'</p>';
            } else {
                echo '';
            }
        };
    }

    /**
     * Register a new field.
     *
     * @since  1.0.0
     * @param array $config  The configuration of the field.
     */
    public function register_field($config) {
        $this->configs[$config['section_id']][$config['id']] = $config;

        $field_id = $this->get_field_id($config['id'], $config['section_id']);

        $register_args = array();
        if (isset($config['data_type'])) {
            $register_args['type'] = $config['data_type'];
        }

        if (isset($config['description'])) {
            $register_args['description'] = $config['description'];
        }

        if (isset($config['sanitize_callback']) && is_callable(array($this, $config['sanitize_callback']))) {
            $register_args['sanitize_callback'] = array($this, $config['sanitize_callback']);
        } else {
            $register_args['sanitize_callback'] = $this->get_sanitize_callback($config['type']);
        }

        if (isset($config['show_in_rest'])) {
            $register_args['show_in_rest'] = $config['show_in_rest'];
        }

        if (isset($config['default'])) {
            $register_args['default'] = $config['default'];
        }

        register_setting(
            $this->settings_page,
            $field_id,
            $register_args
        );
    }

    /**
     * Adds a new field to the settings page.
     *
     * @since 1.0.0
     * @param $id
     * @param $section_id
     */
    public function add_field($config) {
        $field_id = $this->get_field_id($config['id'], $config['section_id']);
        $args = array_merge($config, array(
            'id' => $field_id,
            'label_for' => $field_id,
            'value' => $this->get_field_value($config['id'], $config['section_id'], $config['default'] ?? false)
        ));

        add_settings_field(
            $field_id,
            $config['title'],
            $this->get_field_render_function($config),
            $this->settings_page,
            $this->get_section_id($config['section_id']),
            $args
        );
    }

    /**
     * Get the full ID of the settings field.
     *
     * @since 1.0.0
     * @param string $id   The ID of the field.
     * @param string $section_id  The ID of the section.
     * @return string
     */
    public function get_field_id($id, $section_id) {
        return sprintf('%s_%s_%s', $this->settings_prefix, $section_id, $id);
    }

    /**
     * Get the current value of the settings field.
     *
     * @since 1.0.0
     * @param string $id The ID of the field.
     * @param string $section_id The ID of he section.
     * @return false|mixed|void
     */
    public function get_field_value($id, $section_id, $default = false) {
        return get_option($this->get_field_id($id, $section_id), $default);
    }

    /**
     * Sets value for the specified option
     *
     * @param $id
     * @param $section_id
     * @param $value
     */
    public function set_field_value($id, $section_id, $value) {
        update_option($this->get_field_id($id, $section_id), $value);
    }

    public function delete_field($id, $section_id) {
        delete_option($this->get_field_id($id, $section_id));
    }

    /**
     * Get the function that outputs the code of the field element.
     *
     * @since 1.0.0
     * @param array $config  The config of the field element.
     * @return array
     */
    private function get_field_render_function($config) {
        return array($this, 'render_' . $config['type']);
    }

    /**
     * Get the default sanitize callback for the given field type.
     *
     * @since 1.0.0
     * @param string $type The field type.
     * @return string
     */
    private function get_sanitize_callback($type)
    {
        if ($type === 'checkbox') {
            return 'intval';
        } else if ($type === 'textfield') {
            return 'sanitize_text_field';
        }

        return '';
    }

    /**
     * Sanitize value of API token field.
     *
     * @since 1.0.0
     * @param string $value The input token value.
     * @return string The output token value.
     */
    public function token_sanitize_callback($value) {
        if (!empty($value) && preg_match('/^[A-Za-z0-9]{60}$/', $value) && $this->is_token_valid($value) ) {
            return $value;
        } else {
            $this->add_message('API Token invalid', 'error');
            return '';
        }
    }

    /**
     * Sanitize value of Allow URL field.
     *
     * @since 1.0.0.
     * @param string $value
     */
    public function allow_url_sanitize_callback($value) {
        return $this->url_sanitize_callback($value, 'Redirect URL on Allow');
    }

    /**
     * Sanitize value of Deny URL field.
     *
     * @since 1.0.0.
     * @param string $value
     */
    public function deny_url_sanitize_callback($value) {
        return $this->url_sanitize_callback($value, 'Redirect URL on Deny');
    }

    /**
     * Sanitize value of URL field.
     *
     * @since 1.0.0.
     * @param string $value
     */
    public function url_sanitize_callback($value, $field_title, $optional = true) {
        if ($optional && empty($value)) {
            return '';
        }

        $value = esc_url_raw($value, ['http', 'https']);

        if (empty($value) || filter_var($value, FILTER_VALIDATE_URL) === false) {
            $this->add_message("$field_title is invalid", 'error');
            return '';
        }

        return $value;
    }

    /**
     * Sanitize keywords value.
     *
     * @since 1.0.0
     * @param $value
     * @return string
     */
    public function keywords_sanitize_callback($value) {
        $result = [];
        if (!empty($value)) {
            $keywords = explode(',', $value);
            foreach ($keywords as $keyword) {
                $result[] = sanitize_text_field(trim($keyword));
            }
        }
        return implode(',', $result);
    }

    /**
     * Sanitize max_count value.
     *
     * @since 1.0.0
     * @param $value
     * @return int
     */
    public function max_count_sanitize_callback($value) {
        $result = intval($value);
        if ($result <= 0) {
            $result = -1;
        }
        return $result;
    }

    /**
     * Sanitize value of tracking tag field.
     *
     * @since 1.0.0.
     * @param string $value
     */
    public function tracking_tag_sanitize_callback($value) {
       if (empty($value)) {
          return '';
       }

       if (ctype_alnum($value)) {
           return $value;
       } else {
           $this->add_message('Tracking tag is invalid', 'error');
           return '';
       }
    }

    /**
     * Sanitize value of browser select field.
     *
     * @since 1.0.0
     * @param $value
     * @return mixed
     */
    public function browsers_sanitize_callback($value) {
        $result = [];
        foreach ($value as $v) {
            if (in_array($v, self::BROWSERS)) {
                $result[] = $v;
            }
        }
        return $result;
    }

    /**
     * Sanitize value of OS select field.
     *
     * @since 1.0.0
     * @param $value
     * @return mixed
     */
    public function oss_sanitize_callback($value) {
        $result = [];
        foreach ($value as $v) {
            if (in_array($v, self::OSES)) {
                $result[] = $v;
            }
        }
        return $result;
    }

    /**
     * Sanitize value of capping period field.
     *
     * @since 1.0.0
     * @param $value
     * @return int
     */
    public function capping_period_sanitize_callback($value) {
        if (!in_array($value, self::CAPPING_PERIOD_OPTIONS)) {
            return self::CAPPING_PERIOD_DEFAULT;
        }

        return (int) $value;
    }

    /**
     * Sanitize value of frequency capping field.
     *
     * @since 1.0.0
     * @param $value
     * @return int
     */
    public function frequency_capping_sanitize_callback($value) {
        if (!in_array($value, self::FREQUENCY_CAPPING_OPTIONS)) {
            return self::CAPPING_FREQUENCY_DEFAULT;
        }

        return (int) $value;
    }

    /**
     * Outputs the html code the checkbox input field.
     *
     * @since 1.0.0.
     * @param array $args The configuration parameters of checkbox input field.
     */
    public function render_checkbox($args) {
        $id = $args['id'];
        $name = $id;
        $class = !empty($args['class']) ? $args['class'] : '';
        $label_content = $args['label_content'] ?? '';
        $tooltip = $args['tooltip'] ?? '';
        ?>
        <input
            type="checkbox"
            id="<?php echo esc_attr($id) ?>"
            name="<?php echo esc_attr($name) ?>"
            class="<?php echo esc_attr($class) ?>"
            value="1"
            <?php checked(esc_attr($args['value']), 1) ?>
        >
        <label for="<?php echo esc_attr($id) ?>" title="<?php echo esc_attr($tooltip) ?>">
            <?php echo wp_kses_post($label_content) ?>
        </label>
        <?php if (isset($args['description'])): ?>
        <p class="description"><?php echo wp_kses_post($args['description']) ?></p>
        <?php endif ?>
        <?php
    }

    /**
     * Outputs the html code of the text input field.
     *
     * @since 1.0.0
     * @param array $args The configuration parameters of text input field.
     */
    public function render_textfield($args) {
        $id = $args['id'];
        $name = $id;
        $class = !empty($args['class']) ? esc_attr($args['class']) : '';
        ?>
        <input
            type="text"
            id="<?php echo esc_attr($id) ?>"
            name="<?php echo esc_attr($name) ?>"
            class="<?php echo esc_attr($class) ?>"
            value="<?php echo esc_attr($args['value']) ?>"
            <?php echo (isset($args['required']) ? 'required' : '') ?>
        >
        <?php if (isset($args['description'])): ?>
            <p class="description"><?php echo wp_kses_post($args['description']) ?></p>
        <?php endif ?>
        <?php
    }

    /**
     * Outputs the html code of the selectbox field.
     *
     * @since 1.0.0
     * @param array $args The configuration parameters of the select input field.
     */
    public function render_selectbox($args) {
        $id = $args['id'];
        $name = $id;
        $class = !empty($args['class']) ? $args['class'] : '';
        $options = !empty($args['options']) ? $args['options'] : [];
        $value = $args['value'] ?? '';
        if (!empty($args['multiple'])) {
            $multiple = 'multiple';
            $name = $name . '[]';
        } else {
            $multiple = '';
        }
        ?>
        <select id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" class="<?php echo esc_attr($class); ?>" <?php echo $multiple ?>>
            <?php foreach ($options as $option) {
                $option_value = isset($option['value']) ? $option['value'] : $option;
                $option_label = isset($option['label']) ? $option['label'] : $option;
                if (!empty($multiple) && is_array($value)) {
                    $selected = in_array($option_value, $value) ? 'selected' : '';
                } else {
                    $selected = ($value == $option_value ? 'selected' : '');
                }
                ?>
                <option value="<?php echo esc_attr($option_value); ?>" <?php echo $selected; ?>>
                    <?php echo esc_html($option_label); ?>
                </option>
            <?php } ?>
        </select>
        <?php if (isset($args['description'])): ?>
            <p class="description"><?php echo wp_kses_post($args['description']) ?></p>
        <?php endif ?>
        <?php
    }

    /**
     * Checks whether the given token or token stored in the options is valid or not.
     *
     * @param string $token
     * @return bool
     */
    public function is_token_valid($token = null) {
        if (!isset($token)) {
            $token = $this->get_token();
        }

        return !empty($token) && $this->api->check_token($token);
    }

    /**
     * Get the API token value.
     *
     * @since 1.0.0
     * @return false|string
     */
    public function get_token() {
        if (!isset($this->token)) {
            $this->token = $this->get_field_value('token', 'general');
        }
        return $this->token;
    }

    /**
     * Helper function that displays the settings error message to the user
     *
     * @since 1.0.0
     * @param string $message The message to display.
     * @param string $type The message type.
     */
    public function add_message($message, $type) {
        add_settings_error(
            'monetizer_messages',
            'monetizer_message',
            __( $message, 'monetizer' ),
             $type
        );
    }

    /**
     * Determines whether specified module is enabled or not.
     *
     * @since 1.0.0
     * @param string $module Module id (name).
     * @return bool
     */
    public function is_module_enabled($module) {
        return $this->get_field_value($module, 'modules') == 1;
    }

    /**
     * Downloads and saves the push scripts from Monetizer site.
     *
     * @since 1.0.0
     * @return array
     */
    public function update_push_scripts()
    {
        $scripts = $this->api->get_push_monetization_scripts();
        if ($scripts && !empty($scripts['service_worker_code']) && !empty($scripts['page_head_script_code'])) {
            $this->set_field_value('service_worker_code', 'push', $scripts['service_worker_code']);
            $this->set_field_value('page_head_script_code', 'push', $scripts['page_head_script_code']);
        }

        return $scripts;
    }

    /**
     * Clear current token and disable all modules.
     *
     * @since 1.0.0
     */
    public function invalidate_token() {
        if (!empty($this->get_token())) {
            $this->set_field_value('token', 'general', '');
        }
        $this->disable_modules();
    }

    /**
     * Disable all modules
     *
     * @since 1.0.0
     */
    public function disable_modules() {
        $this->set_field_value('push', 'modules', 0);
        $this->set_field_value('links', 'modules', 0);
        $this->set_field_value('redirect', 'modules', 0);
        $this->set_field_value('popup', 'modules', 0);
    }
}
