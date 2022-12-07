<?php
/**
 * Configure Query Monitor integration.
 */

namespace Cache_Debugging_Query_Monitor_Panel\Query_Monitor;

use QM_Collectors;

function bootstrap() {
	// Priority 20 to hook in after Altis sets up its own panels.
	add_filter( 'qm/outputter/html', __NAMESPACE__ . '\\register_qm_output_html', 20 );
	add_filter( 'qm/output/panel_menus', __NAMESPACE__ . '\\remove_extraneous_qm_panels', 100 );
}

/**
 * Register the HTML outputter for the Xray panel
 *
 * @param array $output
 * @return array
 */
function register_qm_output_html( array $output ) : array {
	require_once __DIR__ . '/class-output-cache.php';

	$output['aws-xray-cache'] = new Output_Cache( QM_Collectors::get( 'aws-xray' ), $output );

	return $output;
}

/**
 * Add the Cache child menu to the AWS X-Ray panel
 *
 * @param array $menu
 * @return array
 */
function remove_extraneous_qm_panels( array $menu ) : array {
	return [
		'qm-db_queries-$wpdb' => $menu['qm-db_queries-$wpdb'],
		'aws-xray' => $menu['aws-xray'],
	];
}
