<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div class="notice notice-<?php echo $notice['type']; ?>">
	<a class="rs-notice-dismiss" href="<?php echo esc_url( add_query_arg( 'rs_dismiss_notice', $notice_id ) ); ?>"><?php _e( 'Dismiss', 'woocommerce' ); ?></a>
	<p><?php echo $notice['content']; ?></p>
</div>