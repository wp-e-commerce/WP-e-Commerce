<?php

/**
 * The HTML outputting the tax and shipping form
 * 
 * @package wp-e-commerce
 */ 
global $wpdb;
$changes_made = false;
$country_isocode = preg_match( "/[a-zA-Z]{2,4}/", $_GET['isocode'] ) ? $_GET['isocode'] : get_option( 'base_country' );
$base_region = get_option('base_region');
?>
<div class="wrap">
  <h2><?php esc_html_e( 'GST/Tax Rate', 'wpsc' );?></h2>
  <?php
  if($changes_made === true)
    {
      echo esc_html__( 'Thanks, your changes have been made', 'wpsc' ) . "<br />";
    }
  ?>
  <form action='' method='post' name='regional_tax' class='wpsc_form_track'>
  <?php
  $wpsc_country = wpsc_get_country_object( $country_isocode );
  $country_data = $wpsc_country->get_array();

  if( $wpsc_country->has_regions() ) {
	$region_data = $wpsc_country->get_regions( true );

    $region_data = array_chunk($region_data, 14);
    
    echo "<table>\n\r";
    echo "  <tr>\n\r";
    foreach($region_data as $region_col)
      {
      echo "    <td style='vertical-align: top; padding-right: 3em;'>\n\r";
      echo "<table>\n\r";
      foreach($region_col as $wpsc_region)
        {
        $region = $wpsc_region->as_array();
        $tax_percentage =  $region['tax'];
        echo "  <tr>\n\r";
        if($region['id'] == $base_region)
          {
          echo "    <td><label for='region_tax_".$region['id']."' style='text-decoration: underline;'>".$region['name'].":</label></td>\n\r";
          }
          else
            {
            echo "    <td><label for='region_tax_".$region['id']."'>".$region['name'].":</label></td>\n\r";
            }
        echo "    <td><input type='text' id='region_tax_".$region['id']."' name='region_tax[".$region['id']."]' value='".$tax_percentage."' class='tax_forms'  size='2'/>%</td>\n\r";
        echo "  </tr>\n\r";
        }      
      echo "</table>\n\r";
      echo "    </td>\n\r";
      }
    echo "  </tr>\n\r";
    echo "</table>\n\r";
    }
    else
      {
      $tax_percentage =  $country_data['tax'];
      echo "<label for='country_tax'>" . esc_html__( 'Tax Rate', 'wpsc' ) .":</label> ";
      echo "<input type='text' id='country_tax' name='country_tax' value='".$tax_percentage."' class='tax_forms' maxlength='3' size='3'/>%";
      }
  ?>
  <input type='hidden' name='wpsc_admin_action' value='change_region_tax' />
  <input class='button-secondary' type='submit' name='submit' value='<?php esc_attr_e( 'Save Changes', 'wpsc' );?>' />
  </form>
</div>
