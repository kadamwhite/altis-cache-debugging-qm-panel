# Altis cache stats panel for query monitor

This plugin adds a "Cache" panel within the "AWS X-Ray" query monitor panel, and outputs within that panel a quick summary of the cache data for the current page.

To get detailed Redis call reporting, you need to make this change to the `vendor/humanmade/wp-redis` plugin:

```diff
diff --git a/object-cache.php b/object-cache.php
index 537a516..f259412 100644
--- a/object-cache.php
+++ b/object-cache.php
@@ -1249,6 +1249,8 @@ class WP_Object_Cache {
 	protected function _call_redis( $method ) {
 		global $wpdb;

+		global $redis_timing;
+
 		$arguments = func_get_args();
 		array_shift( $arguments ); // ignore $method

@@ -1263,7 +1265,13 @@ class WP_Object_Cache {
 					$this->redis_calls[ $method ] = 0;
 				}
 				$this->redis_calls[ $method ]++;
+				$start_time = microtime( true );
 				$retval = call_user_func_array( array( $this->redis, $method ), $arguments );
+				$redis_timing[] = [
+					microtime( true ) - $start_time,
+					$arguments[0] ?? '',
+					$method
+				];
 				return $retval;
 			} catch ( Exception $e ) {
 				$retry_exception_messages = $this->retry_exception_messages();
```
