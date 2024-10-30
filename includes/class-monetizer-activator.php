<?php

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Monetizer
 * @subpackage Monetizer/includes
 */
class Monetizer_Activator {

	/**
	 * Performs actions when plugin is activated.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
	    self::setSchedulers();
    }

    public static function setSchedulers() {
	    if (!wp_next_scheduled(Monetizer::ACTION_SCHEDULE_UPDATE_DATA)) {
	        wp_schedule_event(time(), 'hourly', Monetizer::ACTION_SCHEDULE_UPDATE_DATA);
        }
    }
}
