<?php echo "<?xml version='1.0'?>";?>
<rss version='2.0' xmlns:atom='http://www.w3.org/2005/Atom' xmlns:g="http://base.google.com/ns/1.0">
    <channel>
        <title><![CDATA[<?php bloginfo('name'); ?> - <?php echo wpsc_obtain_the_title(); ?>]]></title>
        <link><![CDATA[<?php echo wpsc_this_page_url(); ?>]]></link>
        <description></description>
        <generator><![CDATA[<?php _e('WP e-Commerce', 'wpsc')." ".WPSC_PRESENTABLE_VERSION; ?>]]></generator>
        <atom:link href='<?php echo wpsc_this_page_url(); ?>' rel='self' type='application/rss+xml' />
<?php while (wpsc_have_products()) :  wpsc_the_product(); ?>
          <item>
            <title><![CDATA[<?php echo wpsc_the_product_title(); ?>]]></title>
            <link><![CDATA[<?php echo wpsc_the_product_permalink(); ?>]]></link>
            <description><![CDATA[<?php echo wpsc_the_product_description(); ?>]]></description>
            <pubDate><![CDATA[<?php echo wpsc_product_creation_time('D, d M Y H:i:s +0000'); ?>]]></pubDate>
            <guid><![CDATA[<?php echo wpsc_the_product_permalink(); ?>]]></guid>
            <g:price><![CDATA[<?php echo wpsc_product_normal_price(true); ?>]]></g:price>
            <g:image_link><![CDATA[<?php echo wpsc_the_product_thumbnail(); ?>]]></g:image_link>
          </item>
<?php endwhile; ?>
      </channel>
    </rss>
