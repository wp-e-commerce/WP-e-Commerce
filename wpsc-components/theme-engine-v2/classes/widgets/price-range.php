<?php

class WPSC_Widget_Price_Range extends WP_Widget {
	private $defaults;

	public function __construct() {
		$this->defaults = array(
			'title' => __( 'Price Range', 'wp-e-commerce' ),
		);

		parent::__construct(
			'wpsc_widget_price_range',
			__( '(WPEC) Price Range', 'wp-e-commerce' ),
			array(
				'description' => __( 'WP eCommerce Price Range Widget', 'wp-e-commerce' )
			)
		);

		add_filter( 'wpsc_register_post_types_products_args', array( $this, '_action_init_permalinks' ), 99 );
		add_filter( 'query_vars', array( $this, '_filter_query_vars' ) );

		add_action( 'pre_get_posts', array( $this, '_action_pre_get_posts' ), 2, 1 );
	}

	public function _action_pre_get_posts( $query ) {
		$min = $query->get( 'wpsc_min_price' );
		$max = $query->get( 'wpsc_max_price' );

		if ( ! $query->is_main_query() || ! $min ) {
			return;
		}

		$meta_query = array();

		if ( $min ) {
			$meta_query[] = array(
				'key' => '_wpsc_price',
				'value' => $min,
				'compare' => '>=',
				'type' => 'numeric',
			);
		}

		if ( $max ) {
			$meta_query[] = array(
				'key' => '_wpsc_price',
				'value' => $max,
				'compare' => '<=',
				'type' => 'numeric',
			);
		}

		$query->set( 'meta_query', $meta_query );
	}

	public function _action_init_permalinks( $args ) {
		add_rewrite_tag( '%wpsc_min_price%', '([\d\.]+)', 'post_type=wpsc-product&wpsc_min_price=' );
		add_rewrite_tag( '%wpsc_max_price%', '([\d\.]+)', 'wpsc_max_price=' );
		add_permastruct( 'wpsc_price_range', $args['has_archive'] . '/%wpsc_min_price%/%wpsc_max_price%', array( ) );

		return $args;
	}

	public function _filter_query_vars( $q ) {
		$q[] = 'wpsc_min_price';
		$q[] = 'wpsc_max_price';
		return $q;
	}

	public function widget( $args, $instance ) {
		global $wpdb;
		$prices = $wpdb->get_row( 'SELECT COUNT(DISTINCT meta_value) AS count, MAX(meta_value) AS max, MIN(meta_value) AS min FROM ' . $wpdb->postmeta . ' AS m INNER JOIN ' . $wpdb->posts . ' ON m.post_id = ID WHERE meta_key = "_wpsc_price" AND meta_value > 0' );

		if ( empty( $prices->count ) ) {
			return;
		}

		$prices->min = round( $prices->min );
		$prices->max = round( $prices->max );
		$range_count = $prices->count > 5
		               ? 6
		               : $prices->count;

		$diff     = ( $prices->max - $prices->min ) / $range_count;
		$instance = wp_parse_args( $instance, $this->defaults );
		$title    = apply_filters( 'widget_title', $instance['title'] );

		if ( $range_count == 1 || $prices->min == $prices->max ) {
			return;
		}

		extract( $args );

		echo $before_widget;

		if ( ! empty( $title ) ) {
			echo $before_title . $title . $after_title;
		}

		echo '<ul>';
		/** %1$s: min price, %2$s: max price **/
		$text      = _x( 'From %1$s to %2$s', 'price range widget', 'wp-e-commerce' );
		$range_max = $prices->min - 0.01;

		$i = 0;

		while ( $range_max <= $prices->max ) {
			$range_min = $range_max + 0.01;
			$range_max = $range_min + round( $diff ) - 0.01;

			$href = wpsc_get_store_url() . $range_min . '/' . $range_max;

			echo '<li>';

			if ( $i === 0 ) {
				echo '<a href="' . esc_url( $href ) . '">' . sprintf( _x( 'Under %s', 'price range widget', 'wp-e-commerce' ), wpsc_format_currency( $range_max ) ) . '</a>';
			} elseif ( $range_max >= $prices->max ) {
				echo '<a href="' . esc_url( $href ) . '">' . sprintf( _x( 'Over %s', 'price range widget', 'wp-e-commerce' ), wpsc_format_currency( $range_min ) ) . '</a>';
			} else {
				echo '<a href="' . esc_url( $href ) . '">' . sprintf( $text, wpsc_format_currency( $range_min ), wpsc_format_currency( $range_max ) ) . '</a>';
			}
			echo '</li>';

			$i++;
		}

		echo '</ul>';

		echo $after_widget;
	}

	public function form( $instance ) {
		$instance = wp_parse_args( $instance, $this->defaults );
?>
<p>
	<?php wpsc_form_label(
		__( 'Title:', 'wp-e-commerce' ),
		$this->get_field_id( 'title' )
	); ?><br />
	<?php wpsc_form_input(
		$this->get_field_name( 'title' ),
		$instance['title'],
		array( 'id' => $this->get_field_id( 'title' ), 'class' => 'widefat' )
	); ?>
</p>
<?php
	}

	public function update( $new_instance, $old_instance ) {
		$instance          = wp_parse_args( $new_instance, $old_instance );
		$instance['title'] = strip_tags( $new_instance['title'] );
		return $instance;
	}
}

