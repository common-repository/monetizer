<?php

/**
 * Provide an admin area settings view for the plugin
 *
 * @link       https://www.monetizer.com/
 * @since      1.0.0
 *
 * @package    Monetizer
 * @subpackage Monetizer/admin/partials
 */

if ( ! current_user_can( 'manage_options' ) ) {
    return;
}

if ( isset( $_GET['settings-updated'] ) ) {
    $this->settings->add_message('Settings Saved', 'success');
}

settings_errors('monetizer_messages');

?>

<div class="wrap">
    <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

    <form class="monetizer" method="post" action="options.php">
        <?php
        settings_fields($this->plugin_name);
        // output setting sections and their fields
        do_settings_sections($this->plugin_name);
        // output save settings button
        submit_button( 'Save Settings' );
        ?>
    </form>
</div>

