var wpsc_resize_iframe = function() {
	var iframe = jQuery('#wpsc_product_variation_forms iframe')[0];
	var inside = jQuery("#wpsc_product_variation_forms .inside");
	var i_document = iframe.contentDocument;
	var i_document_element = i_document.documentElement;

	iframe.style.height = '';

	// getting true height of iframes in different browsers is a tricky business
	var content_height = Math.max(
		i_document.body.scrollHeight,
		i_document.body.offsetHeight,
		i_document.body.clientHeight,
		i_document_element.scrollHeight,
		i_document_element.offsetHeight,
		i_document_element.clientHeight
	);

	inside.innerHeight(content_height);
	iframe.style.height = content_height + 'px';
};

var wpsc_display_thickbox = function(title, url) {
	tb_show(WPSC_Variations.thickbox_title.replace('%s', title), url);
};

var wpsc_set_variation_product_thumbnail = function(id, src) {
	var iframe = jQuery('#wpsc_product_variation_forms iframe');
	iframe.contents().find('#wpsc-variation-thumbnail-' + id).attr('src', src);
};

(function($) {
	var table = $('body.edit-tags-php .wp-list-table');
	table.find('tbody tr').each(function(){
		var t = $(this),
			id = t.attr('id').replace(/[^0-9]+/g, '');
		t.data('level', WPSC_Term_List_Levels[id]);
		t.data('id', id);
	});

	table.wpsc_sortable_table({
		stop : variation_sort
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
