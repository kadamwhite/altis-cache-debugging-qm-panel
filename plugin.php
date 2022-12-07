<?php
/**
 * Plugin Name: Cache Debugging
 * Description: Query Monitor panel to highlight Altis cache stats.
 * Author: K. Adam White
 * Version: 0.1.0
 */

namespace Cache_Debugging_Query_Monitor_Panel;

if ( ! function_exists( 'HM\\Platform\\XRay\\bootstrap' ) ) {
	// Exit early in the event functions are not available. This likely means X-Ray is
	// disabled in your environment, or this is not an Altis environment.
	return;
}

require_once __DIR__ . '/inc/query_monitor/namespace.php';

add_action( 'plugins_loaded', __NAMESPACE__ . '\\Query_Monitor\\bootstrap', 9 );
