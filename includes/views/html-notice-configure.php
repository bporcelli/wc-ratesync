<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<div class="notice error">
    <p>
    <?php
        $settings_url = esc_url( admin_url( 'admin.php?page=wc-settings&tab=tax#ratesync_options' ) );

        printf(
            __( '%1$sRateSync is inactive.%2$s Please %3$sactivate your license%4$s and %5$sconfigure your tax states%6$s to dismiss this notice.', 'wc-ratesync' ),
            '<strong>',
            '</strong>',
            '<a href="'. $settings_url .'">',
            '</a>',
            '<a href="'. $settings_url .'">',
            '</a>'
        );
    ?>
    </p>
</div>