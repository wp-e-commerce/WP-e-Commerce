( function( $ ) {
  wp.customize( 'wpsc_layout', function( value ) {
    value.bind( function( to, from ) {
        $( 'body' ).removeClass( 'wpsc-' + from );
        $( 'body' ).addClass( 'wpsc-' + to );

        if ( 'grid' === to ) {
            $( '#customize-control-wpsc_products_per_row' ).slideDown( 150 );
        } else {
            $( '#customize-control-wpsc_products_per_row' ).slideUp( 150 );
        }
    } );
  } );

  wp.customize.bind( 'ready', function() {
     var value = wp.customize.control( 'wpsc_layout' ).setting();

     if ( 'list' === value ) {
         $( '#customize-control-wpsc_products_per_row' ).slideUp( 150 );
     } else {
         $( '#customize-control-wpsc_products_per_row' ).slideDown( 150 );
     }
  } );

} )( jQuery );
