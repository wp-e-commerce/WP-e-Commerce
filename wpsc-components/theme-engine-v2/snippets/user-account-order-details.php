<div class="wpsc-customer-account-order-details">
	<?php foreach ( $fields as $field ): ?>
	<p>
		<?php if ( $field->type == 'heading' ): ?>
			<strong><?php echo esc_html( $field->name ); ?></strong>
		<?php elseif ( isset( $c->form_data[ (int) $field->id ] ) ): ?>
			<span class="wpsc-customer-account-order-field-label">
				<?php echo esc_html( $field->name ); ?>:
			</span>
			<span class="wpsc-customer-account-order-field-value">
				<?php echo esc_html( $c->form_data[ (int) $field->id ]->value ); ?>
			</span>
		<?php endif; ?>
	</p>
	<?php endforeach ?>
</div>