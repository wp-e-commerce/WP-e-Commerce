;(function($) {

  $( ".wpsc-field-quantity .wpsc-controls" ).append( '<div class="inc wpsc-qty-button">+</div><div class="dec wpsc-qty-button">-</div>' );

  $(".wpsc-qty-button").on("click", function() {

    var $button = $(this);
    var oldValue = $button.parent().find("input").val();

    if ($button.text() == "+") {
    var newVal = parseFloat(oldValue) + 1;
    } else {
    // Don't allow decrementing below zero
    if (oldValue > 0) {
    var newVal = parseFloat(oldValue) - 1;
    } else {
    newVal = 0;
    }
  }

  $button.parent().find("input").val(newVal);

  });

})(jQuery);