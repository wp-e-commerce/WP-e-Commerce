
// The following is all for Share this.
function wpsc_akst_share(id, url, title) {
	if ((jQuery('#wpsc_akst_form').css("display") == 'block') && (jQuery('#wpsc_akst_post_id').attr("value") == id)) {
		jQuery('#wpsc_akst_form').css("display", "none");
		return;
	}

	var offset = {};
	new_container_offset = jQuery('#wpsc_akst_link_' + id).offset();

	if (offset['left'] == null) {
		offset['left'] = new_container_offset.left;
		offset['top'] = new_container_offset.top;
	}

	jQuery("#wpsc_akst_delicious").attr("href", wpsc_akst_share_url("http://del.icio.us/post?url={url}&title={title}", url, title));
	jQuery("#wpsc_akst_digg").attr("href", wpsc_akst_share_url("http://digg.com/submit?phase=2&url={url}&title={title}", url, title));
	jQuery("#wpsc_akst_furl").attr("href", wpsc_akst_share_url("http://furl.net/storeIt.jsp?u={url}&t={title}", url, title));
	jQuery("#wpsc_akst_netscape").attr("href", wpsc_akst_share_url(" http://www.netscape.com/submit/?U={url}&T={title}", url, title));
	jQuery("#wpsc_akst_yahoo_myweb").attr("href", wpsc_akst_share_url("http://myweb2.search.yahoo.com/myresults/bookmarklet?u={url}&t={title}", url, title));
	jQuery("#wpsc_akst_stumbleupon").attr("href", wpsc_akst_share_url("http://www.stumbleupon.com/submit?url={url}&title={title}", url, title));
	jQuery("#wpsc_akst_google_bmarks").attr("href", wpsc_akst_share_url("  http://www.google.com/bookmarks/mark?op=edit&bkmk={url}&title={title}", url, title));
	jQuery("#wpsc_akst_technorati").attr("href", wpsc_akst_share_url("http://www.technorati.com/faves?add={url}", url, title));
	jQuery("#wpsc_akst_blinklist").attr("href", wpsc_akst_share_url("http://blinklist.com/index.php?Action=Blink/addblink.php&Url={url}&Title={title}", url, title));
	jQuery("#wpsc_akst_newsvine").attr("href", wpsc_akst_share_url("http://www.newsvine.com/_wine/save?u={url}&h={title}", url, title));
	jQuery("#wpsc_akst_magnolia").attr("href", wpsc_akst_share_url("http://ma.gnolia.com/bookmarklet/add?url={url}&title={title}", url, title));
	jQuery("#wpsc_akst_reddit").attr("href", wpsc_akst_share_url("http://reddit.com/submit?url={url}&title={title}", url, title));
	jQuery("#wpsc_akst_windows_live").attr("href", wpsc_akst_share_url("https://favorites.live.com/quickadd.aspx?marklet=1&mkt=en-us&url={url}&title={title}&top=1", url, title));
	jQuery("#wpsc_akst_tailrank").attr("href", wpsc_akst_share_url("http://tailrank.com/share/?link_href={url}&title={title}", url, title));

	jQuery('#wpsc_akst_post_id').value = id;
	jQuery('#wpsc_akst_form').css("left", offset['left'] + 'px');
	jQuery('#wpsc_akst_form').css("top", (offset['top']+ 14 + 3) + 'px');
	jQuery('#wpsc_akst_form').css("display", 'block');
}

function wpsc_akst_share_url(base, url, title) {
	base = base.replace('{url}', url);
	return base.replace('{title}', title);
}

function wpsc_akst_share_tab(tab) {
	var tab1 = document.getElementById('wpsc_akst_tab1');
	var tab2 = document.getElementById('wpsc_akst_tab2');
	var body1 = document.getElementById('wpsc_akst_social');
	var body2 = document.getElementById('wpsc_akst_email');

	switch (tab) {
		case '1':
			tab2.className = '';
			tab1.className = 'selected';
			body2.style.display = 'none';
			body1.style.display = 'block';
			break;
		case '2':
			tab1.className = '';
			tab2.className = 'selected';
			body1.style.display = 'none';
			body2.style.display = 'block';
			break;
	}
}
