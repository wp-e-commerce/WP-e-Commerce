function init() {
	tinyMCEPopup.resizeToInnerSize();
}

function getCheckedValue(radioObj) {
	if(!radioObj)
		return "";
	var radioLength = radioObj.length;
	if(radioLength == undefined)
		if(radioObj.checked)
			return radioObj.value;
		else
			return "";
	for(var i = 0; i < radioLength; i++) {
		if(radioObj[i].checked) {
			return radioObj[i].value;
		}
	}
	return "";
}

function insertWPSCLink() {
	var tagtext;
	var select_category=document.getElementById('wpsc_category_panel');
	var category = document.getElementById('wpsc_category');
	var slider = document.getElementById('product_slider_panel');
	var add_product = document.getElementById('add_product_panel');
	var items_per_page = 0;

	// who is active ?
	if (select_category.className.indexOf('current') != -1) {

		items_per_page = jQuery('#wpsc_perpage').val();
	//work out which radio button is selected and get the value
		for (var i=0; i < document.WPSC.wpsc_sale_shortcode.length; i++)
		   {
		   if (document.WPSC.wpsc_sale_shortcode[i].checked)
		      {
		      var shortcode = document.WPSC.wpsc_sale_shortcode[i].value;
		      }
		   }

		var shortcodeid = shortcode;
		var categoryid = category.value;
		var tags = ['wpsc_products'];

		if (categoryid <= 0 && shortcodeid != 1) {
			tinyMCEPopup.close();
			return;
		}

		if (shortcodeid == 1 || shortcodeid == 2) {
			tags.push("price='sale'");
		}

		if (categoryid > 0) {
			tags.push("category_id='" + categoryid + "'");
		}

		if (items_per_page > 0) {
			tags.push("number_per_page='" + items_per_page + "'");
		}

		tagtext = '[' + tags.join(' ') + ']';
	}

	if (slider.className.indexOf('current') != -1) {
		category = document.getElementById('wpsc_slider_category');
		visi = document.getElementById('wpsc_slider_visibles');
		var categoryid = category.value;
		var visibles = visi.value;

		if (categoryid > 0) {

			if (visibles != '') {
				tagtext = "[wpec_product_slider category_id='"+categoryid+"' visible_items='"+visibles+"']";
			} else {
				tagtext = "[wpec_product_slider category_id='"+categoryid+"']";
			}

		}
		else if(categoryid == 'all'){
			tagtext = "[wpec_product_slider]";
		}else {
			tinyMCEPopup.close();
		}
	}

	if (add_product.className.indexOf('current') != -1) {

		product = document.getElementById('wpsc_product_name');

			for (var i=0; i < document.WPSC.wpsc_product_shortcode.length; i++)
		   {
		   if (document.WPSC.wpsc_product_shortcode[i].checked)
		      {
		      var shortcode = document.WPSC.wpsc_product_shortcode[i].value;
		      }
		   }
		var productid = product.value;
		var shortcodeid = shortcode ;

		if (productid > 0) {
			if (shortcodeid == 1)
				tagtext = "[buy_now_button product_id='"+productid+"']";

			if (shortcodeid == 2)
				tagtext = "[add_to_cart="+productid+"]";

			if (shortcodeid == 3)
				tagtext = "[wpsc_products product_id='"+productid+"']";
		} else {
			tinyMCEPopup.close();
		}
	}

	if ( window.tinyMCE ) {
		if ( window.tinyMCE.majorVersion < 4 ) {

			window.tinyMCE.execInstanceCommand('content', 'mceInsertContent', false, tagtext);
			//Peforms a clean up of the current editor HTML.
			//tinyMCEPopup.editor.execCommand('mceCleanup');
			//Repaints the editor. Sometimes the browser has graphic glitches.
			tinyMCEPopup.editor.execCommand('mceRepaint');
			tinyMCEPopup.close();
		} else {
            parent.tinyMCE.execCommand( 'mceInsertContent', false, tagtext );
            parent.tinyMCE.activeEditor.windowManager.close();
		}
	}
	return;
}
