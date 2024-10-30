<?php

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Monetizer
 * @subpackage Monetizer/includes
 */
class Monetizer_Deactivator {

	/**
	 * Performs actions when plugin is deactivated.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
	    wp_clear_scheduled_hook(Monetizer::ACTION_SCHEDULE_UPDATE_DATA);
	}

}
