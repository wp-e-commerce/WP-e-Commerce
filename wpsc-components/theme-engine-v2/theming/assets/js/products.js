;( function( $ ) {

  $( '.wpsc-field-quantity .wpsc-controls' ).append( '<div class="inc wpsc-qty-button">+</div><div class="dec wpsc-qty-button">-</div>' );

  $( '.wpsc-qty-button' ).on( 'click', function() {

    var $button = $( this );
    var $input  = $button.parent().find( 'input' );
    var oldVal  = parseInt( $input.val(), 10 );
    var newVal  = oldVal + 1;

    if ( '-' === $button.text() ) {
      // Don't allow decrementing below zero
      if ( oldVal > 0 ) {
          newVal = oldVal - 1;
      } else {
          newVal = 0;
      }
    }

    $input.val( newVal ).trigger( 'change' );
  } );

} )( jQuery );
