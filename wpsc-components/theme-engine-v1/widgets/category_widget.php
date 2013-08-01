<?php
/**
 * Product Categories widget class
 *
 * Takes the settings, works out if there is anything to display, if so, displays it.
 *
 * @since 3.7.1
 */
class WP_Widget_Product_Categories extends WP_Widget {

	/**
	 * Widget Constuctor
	 */
	function WP_Widget_Product_Categories() {
		$widget_ops = array(
			'classname' => 'widget_wpsc_categorisation',
			'description' => __( 'Product Grouping Widget', 'wpsc' )
		);
		$this->WP_Widget( 'wpsc_categorisation', __( '(WPEC) Product Categories', 'wpsc' ), $widget_ops );
	}

	/**
	 * Widget Output
	 *
	 * @param $args (array)
	 * @param $instance (array) Widget values.
	 */
	function widget( $args, $instance ) {

		global $wpdb;

		extract( $args );

		$title = apply_filters( 'widget_title', empty( $instance['title'] ) ? __( 'Product Categories', 'wpsc'  ) : $instance['title'] );

		echo $before_widget;

		if ( $title )
			echo $before_title . $title . $after_title;

		$show_thumbnails = $instance['image'];

		if ( isset($instance['grid'] ) )
			$grid = (bool)$instance['grid'];

		if ( isset($instance['width'] ) )
			$width = $instance['width'];

		if ( isset( $instance['height'] ) )
			$height = $instance['height'];

		if ( !isset( $instance['categories'] ) ){
			$instance_categories = get_terms( 'wpsc_product_category', 'hide_empty=0&parent=0');
			if(!empty($instance_categories)){
				foreach($instance_categories as $categories){

					$instance['categories'][$categories->term_id] = 'on';
				}
			}

		}
		foreach ( array_keys( (array)$instance['categories'] ) as $category_id ) {

			if (!get_term($category_id, "wpsc_product_category"))
				continue;

			if ( file_exists( wpsc_get_template_file_path( 'wpsc-category_widget.php' ) ) ) {
				include( wpsc_get_template_file_path( 'wpsc-category_widget.php' ) );
			} else {
				include( wpsc_get_template_file_path( 'category_widget.php' ) );
			}
		}

		if ( isset( $grid ) && $grid )
			echo "<div class='clear_category_group'></div>";

		echo $after_widget;
	}

	/**
	 * Update Widget
	 *
	 * @param $new_instance (array) New widget values.
	 * @param $old_instance (array) Old widget values.
	 *
	 * @return (array) New values.
	 */
	function update( $new_instance, $old_instance ) {

		$instance = $old_instance;
		$instance['title']      = strip_tags( $new_instance['title'] );
		$instance['image']      = $new_instance['image'] ? 1 : 0;
		$instance['categories'] = $new_instance['categories'];
		$instance['grid']       = $new_instance['grid'] ? 1 : 0;
		$instance['height']     = (int)$new_instance['height'];
		$instance['width']      = (int)$new_instance['width'];
		$instance['show_name']	= (bool)$new_instance['show_name'];
		return $instance;

	}

	/**
	 * Widget Options Form
	 *
	 * @param $instance (array) Widget values.
	 */
	function form( $instance ) {

		global $wpdb;

		// Defaults
		$instance = wp_parse_args((array) $instance, array(
			'title' => '',
			'width' => 45,
			'height' => 45,
			'image' => false,
			'grid' => false,
			'show_name' => false,
		));

		// Values
		$title    = esc_attr( $instance['title'] );
		$image    = (bool) $instance['image'];
		$width    = (int) $instance['width'];
		$height   = (int) $instance['height'];
		$grid     = (bool) $instance['grid'];
		$show_name= (bool) $instance['show_name'];
		 ?>

		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e( 'Title:', 'wpsc' ); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
		</p>

		<p>
			<?php _e('Show Categories','wpsc'); ?>:<br />
			<?php wpsc_list_categories('wpsc_category_widget_admin_category_list', array("id"=>$this->get_field_id('categories'),"name"=>$this->get_field_name('categories'),"instance"=>$instance), 0); ?>
			<?php _e('(leave all unchecked if you want to display all)','wpsc'); ?>
		</p>

		<p>
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('grid'); ?>" name="<?php echo $this->get_field_name('grid'); ?>"<?php checked( $grid ); ?> />
			<label for="<?php echo $this->get_field_id('grid'); ?>"><?php _e('Use Category Grid View', 'wpsc'); ?></label><br />
			<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('image'); ?>" name="<?php echo $this->get_field_name('image'); ?>"<?php checked( $image ); ?> onclick="jQuery('.wpsc_category_image').toggle()" />
			<label for="<?php echo $this->get_field_id('image'); ?>"><?php _e('Show Thumbnails', 'wpsc'); ?></label>
		</p>

		<div class="wpsc_category_image"<?php if( !checked( $image ) ) { echo ' style="display:none;"'; } ?>>
			<p>

				<input type="checkbox" class="checkbox" id="<?php echo $this->get_field_id('show_name'); ?>" name="<?php echo $this->get_field_name('show_name'); ?>"<?php checked( $show_name ); ?> /><label for="<?php echo $this->get_field_id('show_name'); ?>"><?php _e(' Show N/A when No Image Available', 'wpsc'); ?></label>
			</p>
			<p>
				<label for="<?php echo $this->get_field_id('width'); ?>"><?php _e('Width:', 'wpsc'); ?></label>
				<input type="text" id="<?php echo $this->get_field_id('width'); ?>" name="<?php echo $this->get_field_name('width'); ?>" value="<?php echo $width ; ?>" size="3" />
				<label for="<?php echo $this->get_field_id('height'); ?>"><?php _e('Height:', 'wpsc'); ?></label>
				<input type="text" id="<?php echo $this->get_field_id('height'); ?>" name="<?php echo $this->get_field_name('height'); ?>" value="<?php echo $height ; ?>" size="3" />
			</p>
		</div>
<?php
	}
}

add_action( 'widgets_init', create_function( '', 'return register_widget("WP_Widget_Product_Categories");' ) );

function wpsc_category_widget_admin_category_list( $category, $level, $fieldconfig ) {
	// Only let the user choose top-level categories
	if ( $level )
		return;

	if ( !empty( $fieldconfig['instance']['categories'] ) && array_key_exists( $category->term_id, $fieldconfig['instance']['categories'] ) )
		$checked = 'checked';
	else
		$checked = ''; ?>

	<input type="checkbox" class="checkbox" id="<?php echo esc_attr( $fieldconfig['id'] ); ?>-<?php echo esc_attr( $category->term_id ); ?>" name="<?php echo esc_attr( $fieldconfig['name'] ); ?>[<?php echo esc_attr( $category->term_id ); ?>]" <?php echo $checked; ?>></input> <label for="<?php echo esc_attr( $fieldconfig['id'] ); ?>-<?php echo esc_attr( $category->term_id ); ?>"><?php echo esc_html( $category->name ); ?></label><br />

<?php
}