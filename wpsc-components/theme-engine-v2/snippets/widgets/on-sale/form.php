<p>
	<?php
	wpsc_form_label(
		__( 'Title:', 'wp-e-commerce' ),
		$this->get_field_id( 'title')
	); ?><br />
	<?php
	wpsc_form_input(
		$this->get_field_name( 'title' ),
		$instance['title'],
		array( 'id' => $this->get_field_id( 'title' ), 'class' => 'widefat' )
	); ?>
</p>

<p>
	<?php
	wpsc_form_label(
		__( 'Number of posts:', 'wp-e-commerce' ),
		$this->get_field_id( 'post_count')
	); ?><br />
	<?php
	wpsc_form_input(
		$this->get_field_name( 'post_count' ),
		$instance['post_count'],
		array( 'id' => $this->get_field_id( 'post_count' ), 'size' => '3' )
	); ?>
</p>

<p>
	<?php
	wpsc_form_checkbox(
		$this->get_field_name( 'show_name' ),
		1,
		_x( 'Show Product Name', 'on sale widget', 'wp-e-commerce' ),
		$instance['show_name']
	); ?>
</p>

<p>
	<?php
	wpsc_form_checkbox(
		$this->get_field_name( 'show_sale_price' ),
		1,
		_x( 'Show Sale Price', 'on sale widget', 'wp-e-commerce' ),
		$instance['show_sale_price']
	); ?>
</p>

<p>
	<?php
	wpsc_form_checkbox(
		$this->get_field_name( 'show_normal_price' ),
		1,
		_x( 'Show Normal Price', 'on sale widget', 'wp-e-commerce' ),
		$instance['show_normal_price']
	); ?>
</p>

<p>
	<?php
	wpsc_form_checkbox(
		$this->get_field_name( 'show_you_save' ),
		1,
		_x( 'Show "You save"', 'on sale widget', 'wp-e-commerce' ),
		$instance['show_you_save']
	); ?>
</p>

<p>
	<?php
	wpsc_form_checkbox(
		$this->get_field_name( 'show_description' ),
		1,
		_x( 'Show Description', 'on sale widget', 'wp-e-commerce' ),
		$instance['show_description']
	); ?>
</p>

<p>
	<?php
	wpsc_form_checkbox(
		$this->get_field_name( 'show_image' ),
		1,
		_x( 'Show Thumbnails', 'on sale widget', 'wp-e-commerce' ),
		! empty( $instance['show_image'] )
	); ?>
</p>
<ul style="margin-left:18px;">
	<li>
		<?php
		printf(
			/** translators: %1$s: Label, %2$s: Input box, %3$s: pixel unit **/
			_x( '%1$s %2$s %3$s', 'on sale widget width / height option', 'wp-e-commerce' ),
			wpsc_form_label(
				_x( 'Width:', 'on sale widget', 'wp-e-commerce' ),
				$this->get_field_id( 'width' ),
				array(),
				false
			),
			wpsc_form_input(
				$this->get_field_name( 'width' ),
				$instance['width'],
				array( 'id' => $this->get_field_id( 'width' ), 'size' => 3 ),
				false
			),
			__( 'px', 'wp-e-commerce' )
		); ?>
	</li>
	<li>
		<?php
		printf(
			/** translators: %1$s: Label, %2$s: Input box, %3$s: pixel unit **/
			_x( '%1$s %2$s %3$s', 'on sale widget width / height option', 'wp-e-commerce' ),
			wpsc_form_label(
				_x( 'Height:', 'on sale widget', 'wp-e-commerce' ),
				$this->get_field_id( 'height' ),
				array(),
				false
			),
			wpsc_form_input(
				$this->get_field_name( 'height' ),
				$instance['width'],
				array( 'id' => $this->get_field_id( 'height' ), 'size' => 3 ),
				false
			),
			__( 'px', 'wp-e-commerce' )
		); ?>
	</li>
</ul>