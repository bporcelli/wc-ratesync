<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

?>
<tr valign="top" class="">
	<th scope="row" class="titledesc">
		<label><?php esc_html_e( $value['title'], 'wc-ratesync' ); ?></label>
		<?php echo $tooltip_html; ?>
	</th>
	<td class="">
		<table class="wc-shipping-zone-methods wc-rs-tax-states widefat">
			<thead>
				<tr>
					<th class="wc-rs-tax-state-actions"></th>
					<th class="wc-shipping-zone-method-title">
						<?php esc_html_e( 'State', 'wc-ratesync' ); ?>
					</th>
					<th class="wc-shipping-zone-method-enabled wc-rs-shipping-tax-enabled">
						<?php esc_html_e( 'Tax Shipping', 'wc-ratesync' ); ?>
					</th>
				</tr>
			</thead>
			<tfoot>
				<tr>
					<td colspan="4">
						<button type="submit" class="button wc-rs-add-tax-state" value="<?php esc_attr_e( 'Add tax states', 'wc-ratesync' ); ?>"><?php esc_html_e( 'Add tax states', 'wc-ratesync' ); ?></button>
					</td>
				</tr>
			</tfoot>
			<tbody class="wc-rs-tax-state-rows"></tbody>
		</table>
	</td>
</tr>

<script type="text/html" id="tmpl-wc-rs-tax-state-row-blank">
	<tr>
		<td class="wc-shipping-zone-method-blank-state" colspan="4">
			<p><?php esc_html_e( 'You can collect tax in any number of states. Click the button below to get started.', 'wc-ratesync' ); ?></p>
		</td>
	</tr>
</script>

<script type="text/html" id="tmpl-wc-rs-tax-state-row">
	<tr data-abbrev="{{ data.abbrev }}" data-enabled="{{ data.shipping_taxable }}">
		<td width="1%" class="wc-rs-tax-state-actions">
			<a href="#" class="wc-rs-tax-state-delete">
				<span class="screen-reader-text"><?php esc_html_e( 'Delete', 'wc-ratesync' ); ?></span>
                <span class="dashicons dashicons-no-alt"></span>
			</a>
		</td>
		<td class="wc-shipping-zone-method-title">
			<input type="hidden" name="ratesync_tax_states[{{ data.abbrev }}][abbrev]" value="{{ data.abbrev }}">
			<input type="hidden" name="ratesync_tax_states[{{ data.abbrev }}][name]" value="{{ data.name }}">
			{{{ data.name }}}
		</td>
		<td class="wc-shipping-zone-method-enabled wc-rs-shipping-tax-enabled">
			<input 
				type="hidden"
				name="ratesync_tax_states[{{ data.abbrev }}][shipping_taxable]"
				value="{{ data.shipping_taxable }}">
			<a href="#">{{{ data.shipping_tax_icon }}}</a>
		</td>
	</tr>
</script>

<script type="text/template" id="tmpl-wc-rs-modal-add-tax-state">
	<div class="wc-backbone-modal">
		<div class="wc-backbone-modal-content wc-rs-modal-add-tax-state">
			<section class="wc-backbone-modal-main" role="main">
				<header class="wc-backbone-modal-header">
					<h1><?php esc_html_e( 'Add tax states', 'wc-ratesync' ); ?></h1>
					<button class="modal-close modal-close-link dashicons dashicons-no-alt">
						<span class="screen-reader-text">Close modal panel</span>
					</button>
				</header>
				<article>
					<form action="" method="post">
						<select multiple="multiple" name="wc_rs_tax_states[]" style="width:350px" data-placeholder="<?php esc_attr_e( 'Choose states&hellip;', 'wc-ratesync' ); ?>" aria-label="<?php esc_attr_e( 'State', 'wc-ratesync' ) ?>" class="wc-enhanced-select">
							<# for ( var state of data.states ) { #>
								<option value="{{{ state[ 'abbrev' ] }}}">{{{ state[ 'name' ] }}}</option>
							<# } #>
						</select> <br>
						<a class="select_all button" href="#"><?php _e( 'Select all', 'wc-ratesync' ); ?></a> <a class="select_none button" href="#"><?php _e( 'Select none', 'wc-ratesync' ); ?></a>
					</form>
				</article>
				<footer>
					<div class="inner">
						<button id="btn-ok" class="button button-primary button-large"><?php esc_html_e( 'Add', 'wc-ratesync' ); ?></button>
					</div>
				</footer>
			</section>
		</div>
	</div>
	<div class="wc-backbone-modal-backdrop modal-close"></div>
</script>
