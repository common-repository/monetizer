<?php

/**
 * Fired when the plugin is uninstalled.
 *
 * @link       https://www.monetizer.com/
 * @since      1.0.0
 *
 * @package    Monetizer
 */

// If uninstall not called from WordPress, then exit.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

function monetizer_clear_settings()
{
    delete_option('monetizer_general_token');
    delete_option('monetizer_modules_push');
    delete_option('monetizer_modules_links');
    delete_option('monetizer_modules_redirect');
    delete_option('monetizer_modules_popup');
    delete_option('monetizer_push_allow_url');
    delete_option('monetizer_push_deny_url');
    delete_option('monetizer_push_tracking_tag');
    delete_option('monetizer_push_enable_prompt');
    delete_option('monetizer_push_prompt_text');
    delete_option('monetizer_push_prompt_accept_btn_text');
    delete_option('monetizer_push_prompt_deny_btn_text');
    delete_option('monetizer_push_prompt_hide_deny_btn');
    delete_option('monetizer_push_service_worker_code');
    delete_option('monetizer_push_page_head_script_code');
    delete_option('monetizer_links_keywords');
    delete_option('monetizer_links_url');
    delete_option('monetizer_links_max_count');
    delete_option('monetizer_redirect_browsers');
    delete_option('monetizer_redirect_oss');
    delete_option('monetizer_popup_period');
    delete_option('monetizer_popup_frequency');
}

function monetizer_run_uninstall() {
    monetizer_clear_settings();
}

monetizer_run_uninstall();
