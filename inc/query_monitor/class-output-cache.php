<?php
/**
 * Define the QM Output class.
 */

namespace Cache_Debugging_Query_Monitor_Panel\Query_Monitor;

use function HM\Platform\XRay\get_root_trace_id;
use QM_Collector;
use QM_Output_Html;

const OUTPUT_ELEMENT_ID = 'qm-aws-xray-cache-stats';

class Output_Cache extends QM_Output_Html {

	private $db_components;

	public function __construct( QM_Collector $collector, $output ) {
		parent::__construct( $collector );
		add_filter( 'qm/output/panel_menus', [ $this, 'panel_menu' ], 40 );
	}

	public function name() {
		return __( 'Cache', 'cache-debugging-stats-qm-panel' );
	}

	/*
	public function output() {
		?>
		<?php $this->before_non_tabular_output( OUTPUT_ELEMENT_ID ); ?>
		<caption>
			<?php /* translators: Trace ID *//* ?>
			<h2><?php printf( esc_html__( 'Trace ID: %s', 'cache-debugging-stats-qm-panel' ), get_root_trace_id() ); ?></h2>
		</caption>
		<div style="width:100%;margin-top:1em;">
			<?php foreach ( $this->collector->traces as $trace ) : ?>
				<?php
				error_log( gettype( $GLOBALS['redis_timing'] ) );
				if ( is_array( $trace ) && $trace['name'] !== 'local' ) {
					continue;
				}
				if ( ! isset( $trace['metadata']['stats']['object_cache'] ) ) {
					continue;
				}
				?>
				<ul>
					<li>
						<strong>Cache Hits</strong>: <?php echo esc_html( $trace['metadata']['stats']['object_cache']['hits'] ); ?>
						<ul style="padding-left:1em; list-style-type:disc">
							<li><strong>Remote gets</strong>: <?php echo esc_html( $trace['metadata']['stats']['object_cache']['remote_calls']['get'] ); ?></li>
							<li><strong>Remote mgets</strong>: <?php echo esc_html( $trace['metadata']['stats']['object_cache']['remote_calls']['mget'] ); ?></li>
							<li>Exists: <?php echo esc_html( $trace['metadata']['stats']['object_cache']['remote_calls']['exists'] ); ?></li>
						</ul>
					</li>

					<li><strong>Misses</strong>: <?php echo esc_html( $trace['metadata']['stats']['object_cache']['misses'] ); ?></li>
					<li><strong>Time</strong>: <?php echo esc_html( $trace['metadata']['stats']['object_cache']['time'] ); ?></li>
				</ul>
			<?php endforeach ?>
		</div>
		<?php
		$this->after_non_tabular_output();
	}
	*/

	public function output() {
		// Keep an eye out for a timings array from a modified version of humanmade/wp-redis.
		global $redis_timing;

		$local_trace = null;
		foreach ( $this->collector->traces as $trace ) {
			if ( is_array( $trace ) && $trace['name'] !== 'local' ) {
				continue;
			}
			if ( ! isset( $trace['metadata']['stats']['object_cache'] ) ) {
				continue;
			}
			$local_trace = $trace;
		}

		if ( empty( $local_trace ) ) {
			$this->before_non_tabular_output( OUTPUT_ELEMENT_ID );
			echo sprintf(
				'<caption><h2>%</h2></caption>',
				'No AWS X-Ray trace data with object cache information could be found.'
			);
			$this->after_non_tabular_output();
			return;
		}

		error_log( print_r( $local_trace, true ) );
		$this->before_tabular_output( OUTPUT_ELEMENT_ID );
		?>
		<caption>
			<?php /* translators: Trace ID */ ?>
			<h2><?php printf( esc_html__( 'Trace ID: %s', 'cache-debugging-stats-qm-panel' ), get_root_trace_id() ); ?></h2>
		</caption>
		<thead>
			<tr>
				<th><?php echo esc_html__( 'Measurement', 'cache-debugging-stats-qm-panel' ) ?></th>
				<th><?php echo esc_html__( 'Value', 'cache-debugging-stats-qm-panel' ) ?></th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<th>Cache Hits</th>
				<td><?php echo esc_html( $local_trace['metadata']['stats']['object_cache']['hits'] ); ?></td>
			</tr>
			<tr>
				<th>Remote Gets</th>
				<td><?php echo esc_html( $local_trace['metadata']['stats']['object_cache']['remote_calls']['get'] ); ?></td>
			</tr>
			<tr>
				<th>Remote Multi-Gets</th>
				<td><?php echo esc_html( $local_trace['metadata']['stats']['object_cache']['remote_calls']['mget'] ); ?></td>
			</tr>
			<tr>
				<td>Exists</td>
				<td><?php echo esc_html( $local_trace['metadata']['stats']['object_cache']['remote_calls']['exists'] ); ?></td>
			</tr>
			<tr>
				<th>Misses</th>
				<td><?php echo esc_html( $local_trace['metadata']['stats']['object_cache']['misses'] ); ?></td>
			</tr>
			<tr>
				<th>Time</th>
				<td><?php echo esc_html( $local_trace['metadata']['stats']['object_cache']['time'] ); ?></td>
			</tr>

			<?php if ( isset( $redis_timing ) && is_array( $redis_timing ) ) : ?>
			<?php
			$total_time = 0;
			$call_count = 0;
			$cache_call_report = array_values(
				array_reduce(
					$redis_timing,
					function( $carry, $timing_row ) use ( &$total_time, &$call_count ) : array {
						$time = $timing_row[0];
						$item = is_array( $timing_row[1] )
							? $timing_row[1][0]
							: $timing_row[1];
						$method = $timing_row[2];
						$item = preg_replace( '/:[a-f0-9]{32,40}/', ':#', $item );
						$item = preg_replace( '/_[a-f0-9]{32}/', '_#', $item );
						$item = preg_replace( '/:\d+(\.\d+)?$/', ':n', $item );
						$carry[ "$method:$item" ] = $carry[ "$method:$item" ] ?? [
							'item' => $item,
							'time' => 0,
							'calls' => 0,
							'method' => $method,
						];
						$total_time += $time;
						$carry[ "$method:$item" ]['time'] += $time;
						$call_count += 1;
						$carry[ "$method:$item" ]['calls'] += 1;
						return $carry;
					},
					[]
				)
			);
			uasort(
				$cache_call_report,
				function ( $a, $b ) {
					return $a['time'] < $b['time'];
				}
			);
			?>
			<tr>
				<td>Cache Interactions</td>
				<td class="qm-has-toggle">
					<ol class="qm-toggler">
						<?php echo $this->build_toggler() ?>
						<p>Total time: <?php echo esc_html( $total_time ); ?> Total calls: <?php echo esc_html( $call_count ); ?></p>
						<div class="qm-toggled">
							<table>
								<thead>
									<tr>
										<th>Cache Item</th>
										<th scope="col" class="qm-sortable-column">
											<?php echo $this->build_sorter( __( 'Method', 'cache-debugging-stats-query-panel' ) ); // phpcs:ignore ?>
										</th>
										<th scope="col" class="qm-num qm-sortable-column">
											<?php echo $this->build_sorter( __( 'Total Time', 'cache-debugging-stats-query-panel' ) ); // phpcs:ignore ?>
										</th>
										<th scope="col" class="qm-num qm-sortable-column">
											<?php echo $this->build_sorter( __( 'Calls', 'cache-debugging-stats-query-panel' ) ); // phpcs:ignore ?>
										</th>
										<th>
											<?php echo $this->build_sorter( __( '%', 'cache-debugging-stats-query-panel' ) ); // phpcs:ignore ?>
										</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $cache_call_report as $cache_item ) : ?>
									<tr>
										<td><?php echo esc_html( $cache_item['item'] ); ?></td>
										<td><?php echo esc_html( $cache_item['method'] ); ?></td>
										<td><?php echo esc_html( round( $cache_item['time'], 10 ) ); ?></td>
										<td><?php echo esc_html( $cache_item['calls'] ); ?></td>
										<td><?php echo esc_html( round( $cache_item['time'] / $total_time * 100, 1 ) ); ?>%</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</ol>
				</td>
			</tr>
			<?php endif; ?>

		</tbody>
		<?php
		$this->after_tabular_output();
	}

	/**
	 * Add the Cache child menu item to the AWS X-Ray Query Monitor panel menu.
	 *
	 * @param array $menu
	 * @return array
	 */
	public function panel_menu( array $menu ) : array {
		$menu['aws-xray']['children'][] = [
			'title' => __( 'Cache', 'cache-debugging-stats-qm-panel' ),
			'id'    => 'query-monitor-aws-xray-cache-stats',
			'href'  => '#qm-aws-xray-cache-stats',
		];
		return $menu;
	}
}
