<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div class="notice error">
	<p><?php printf( __( '<strong>RateSync is inactive.</strong> Please <a href="%1$s">activate your license</a> and <a href="%1$s">configure your tax states</a> to dismiss this notice.', 'wc-ratesync' ), esc_url( admin_url( 'admin.php?page=wc-settings&tab=tax#ratesync_options' ) ) ); ?></p>
</div>