<p>
	<?php
	wpsc_form_label(
		_x( 'Title:', 'product category widget title field', 'wpsc' ),
		$this->get_field_id( 'title' )
	); ?><br>
	<?php
	wpsc_form_input(
		$this->get_field_name( 'title' ),
		$title,
		array( 'id' => $this->get_field_id( 'title' ), 'class' => 'widefat' )
	); ?>
</p>

<p>
	<?php
	wpsc_form_checkbox(
		$this->get_field_name( 'show_name' ),
		1,
		_x( 'Show Category Name', 'product category widget', 'wpsc' ),
		$show_name
	); ?>
</p>

<p>
	<?php
	wpsc_form_checkbox(
		$this->get_field_name( 'show_count' ),
		1,
		_x( 'Show Product Count', 'product category widget', 'wpsc' ),
		$show_count
	); ?>
</p>

<p>
	<?php
	wpsc_form_checkbox(
		$this->get_field_name( 'show_hierarchy' ),
		1,
		_x( 'Show Hierarchy', 'product category widget', 'wpsc' ),
		$show_hierarchy
	); ?>
</p>

<p>
	<?php
	wpsc_form_checkbox(
		$this->get_field_name( 'show_image' ),
		1,
		_x( 'Show Thumbnails', 'product category widget', 'wpsc' ),
		! empty( $instance['show_image'] )
	); ?>
</p>
<ul style="margin-left:18px;">
	<li>
		<?php
		printf(
			/** translators: %1$s: Label, %2$s: Input box, %3$s: pixel unit **/
			_x( '%1$s %2$s %3$s', 'product category widget width / height option', 'wpsc' ),
			wpsc_form_label(
				_x( 'Width:', 'product category widget', 'wpsc' ),
				$this->get_field_id( 'width' ),
				array(),
				false
			),
			wpsc_form_input(
				$this->get_field_name( 'width' ),
				$width,
				array( 'id' => $this->get_field_id( 'width' ), 'size' => 3 ),
				false
			),
			__( 'px', 'wpsc' )
		); ?>
	</li>
	<li>
		<?php
		printf(
			/** translators: %1$s: Label, %2$s: Input box, %3$s: pixel unit **/
			_x( '%1$s %2$s %3$s', 'product category widget width / height option', 'wpsc' ),
			wpsc_form_label(
				_x( 'Height:', 'product category widget', 'wpsc' ),
				$this->get_field_id( 'height' ),
				array(),
				false
			),
			wpsc_form_input(
				$this->get_field_name( 'height' ),
				$height,
				array( 'id' => $this->get_field_id( 'height' ), 'size' => 3 ),
				false
			),
			__( 'px', 'wpsc' )
		); ?>
	</li>
</ul>

<p>
	<?php echo _x( 'Select Categories:', 'product category widget', 'wpsc' ); ?><br />
	<small><?php esc_html_e( 'Leave all unchecked if you want to display all', 'wpsc' ); ?></small><br>
</p>
<p>
	<span class="wpsc-cat-drill-down-all-actions wpsc-settings-all-none">
		<?php
			printf(
				_x( 'Select: %1$s %2$s', 'select all / none', 'wpsc' ),
				'<a href="#" data-for="' . esc_attr( $this->get_field_id( 'categories' ) ) . '" class="wpsc-multi-select-all">' . _x( 'All', 'select all', 'wpsc' ) . '</a>',
				'<a href="#" data-for="' . esc_attr( $this->get_field_id( 'categories' ) ) . '" class="wpsc-multi-select-none">' . __( 'None', 'wpsc' ) . '</a>'
			);
		?>
	</span><br>
	<?php

	wpsc_form_select(
		$this->get_field_name( 'categories' ) . '[]',
		$instance['categories'],
		$options,
		array(
			'id'               => $this->get_field_id( 'categories' ),
			'multiple'         => 'multiple',
			'size'             => 5,
			'class'            => 'wpsc-multi-select widefat',
			'data-placeholder' => __( 'Select categories', 'wpsc' ),
		)
	); ?>
</p>

