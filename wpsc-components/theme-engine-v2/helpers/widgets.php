<?php
add_action( 'widgets_init', '_wpsc_te2_action_widgets' );

function _wpsc_te2_action_widgets() {
	require_once( WPSC_TE_V2_CLASSES_PATH . '/widgets/cart.php'  );
	require_once( WPSC_TE_V2_CLASSES_PATH . '/widgets/product-categories.php' );
	require_once( WPSC_TE_V2_CLASSES_PATH . '/widgets/latest-products.php' );
	require_once( WPSC_TE_V2_CLASSES_PATH . '/widgets/tag-cloud.php' );
	require_once( WPSC_TE_V2_CLASSES_PATH . '/widgets/on-sale.php' );
	require_once( WPSC_TE_V2_CLASSES_PATH . '/widgets/price-range.php' );
	/* require_once( WPSC_TE_V2_CLASSES_PATH . '/widgets/category-drill-down.php' ); */

	register_widget( 'WPSC_Widget_Cart' );
	register_widget( 'WPSC_Widget_Product_Categories' );
	register_widget( 'WPSC_Widget_Tag_Cloud' );
	register_widget( 'WPSC_Widget_Latest_Products' );
	register_widget( 'WPSC_Widget_On_Sale' );
	register_widget( 'WPSC_Widget_Price_Range' );
	/* register_widget( 'WPSC_Widget_Category_Drill_Down' ); */
}