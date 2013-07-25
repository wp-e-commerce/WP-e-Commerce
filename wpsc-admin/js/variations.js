/**
 * Resize iframe to its content's height.
 * iframe is a pain in the ass. This is just some mild ointment to put on that pain.
 * In the next iteration (3.9), the iframe content will be pulled out of the iframe and AJAXified.
 *
 * @since  3.8.9
 */
var wpsc_resize_iframe = function() {
	var jiframe = jQuery('#wpsc_product_variation_forms iframe');
	var iframe = jiframe[0];
	var i_document = iframe.contentDocument;
	var height_elements = [
		i_document,
		i_document.documentElement,
		i_document.body
	];

	// if iframe's parents are somehow hidden, need to briefly display them to get rendered height
	var invisible_parent = jiframe.parents(':not(:visible)');
	if (invisible_parent.length) {
		invisible_parent.show();
	}

	iframe.style.height = '';

	// getting true height of iframes in different browsers is a tricky business
	var content_height = 0;
	for (var i in height_elements) {
		content_height = Math.max(
			content_height,
			height_elements[i].scrollHeight || 0,
			height_elements[i].offsetHeight || 0,
			height_elements[i].clientHeight || 0
		);
	}

	iframe.style.height = content_height + 'px';

	// in case the invisible parent was originally hidden and then displayed, we need to hide it again
	if (invisible_parent.length) {
		invisible_parent.css('display', '');
	}
};

var wpsc_display_thickbox = function(title, url) {
	tb_show(WPSC_Variations.thickbox_title.replace('%s', title), url);
};

var wpsc_set_variation_product_thumbnail = function(id, src, thumbId) {
	var iframe = jQuery('#wpsc_product_variation_forms iframe');
	var el = iframe.contents().find('#wpsc-variation-thumbnail-' + id);
	el.attr('src', src);
	el.parent().data( 'image-id', thumbId );
};

var wpsc_refresh_variation_iframe = function() {
	jQuery('#wpsc_product_variation_forms iframe')[0].contentWindow.location.reload();
};

(function($) {
	$(function(){
		var table = $('body.edit-tags-php .wp-list-table');
		table.find('tbody tr').each(function(){
			var t = $(this);
			if (!t.hasClass('no-items')) {
				id = t.attr('id').replace(/[^0-9]+/g, '');
				t.data('level', WPSC_Term_List_Levels[id]);
				t.data('id', id);
			}
		});

		table.wpsc_sortable_table({
			stop : variation_sort
		});
	});

	/**
	 * Save variation sort order when user has finished dragging & dropping
	 * @param  {Object} e  Event Object
	 * @param  {Object} ui UI Object
	 * @since 3.8.8
	 */
	var variation_sort = function(e, ui){
		var order = $(this).sortable('toArray'),
			data = {
			action: 'variation_sort_order',
			sort_order: order,
			parent_id: 0
		};
		jQuery.post(ajaxurl, data);
	};
})(jQuery);
