<?php
/**
 * WordPress Shopping-Cart Upgrade Administration API
 *
 * @package WordPress
 * @subpackage Administration
 */

/**
 * Parse the upgrade contents to retrieve upgrade's metadata.
 *
 * The metadata of the upgrade's data searches for the following in the upgrade's
 * header. All upgrade data must be on its own line. For upgrade description, it
 * must not have any newlines or only parts of the description will be displayed
 * and the same goes for the upgrade data. The below is formatted for printing.
 *
 * <code>
 * /*
 * Upgrade Name: Name of upgrade
 * Upgrade URI: Link to upgrade information
 * Description: upgrade Description
 * Author: upgrade author's name
 * Author URI: Link to the author's web site
 * Version: Must be set in the upgrade for WordPress 2.3+
 * Text Domain: Optional. Unique identifier, should be same as the one used in
 *		upgrade_text_domain()
 * Domain Path: Optional. Only useful if the translations are located in a
 *		folder above the upgrade's base path. For example, if .mo files are
 *		located in the locale folder then Domain Path will be "/locale/" and
 *		must have the first slash. Defaults to the base folder the upgrade is
 *		located in.
 *  * / # Remove the space to close comment
 * </code>
 *
 * upgrade data returned array contains the following:
 *		'Name' - Name of the upgrade, must be unique.
 *		'Title' - Title of the upgrade and the link to the upgrade's web site.
 *		'Description' - Description of what the upgrade does and/or notes
 *		from the author.
 *		'Author' - The author's name
 *		'AuthorURI' - The authors web site address.
 *		'Version' - The upgrade version number.
 *		'upgradeURI' - upgrade web site address.
 *		'TextDomain' - upgrade's text domain for localization.
 *		'DomainPath' - upgrade's relative directory path to .mo files.
 *
 * Some users have issues with opening large files and manipulating the contents
 * for want is usually the first 1kiB or 2kiB. This function stops pulling in
 * the upgrade contents when it has all of the required upgrade data.
 *
 * The first 8kiB of the file will be pulled in and if the upgrade data is not
 * within that first 8kiB, then the upgrade author should correct their upgrade
 * and move the upgrade data headers to the top.
 *
 * The upgrade file is assumed to have permissions to allow for scripts to read
 * the file. This is not checked however and the file is only opened for
 * reading.
 *
 * @link http://trac.wordpress.org/ticket/5651 Previous Optimizations.
 * @link http://trac.wordpress.org/ticket/7372 Further and better Optimizations.
 * @since 1.5.0
 *
 * @param string $upgrade_file Path to the upgrade file
 * @param bool $markup If the returned data should have HTML markup applied
 * @param bool $translate If the returned data should be translated
 * @return array See above for description.
 */
function get_upgrade_data( $upgrade_file, $markup = true, $translate = true ) {
	// We don't need to write to the file, so just open for reading.
	$fp = fopen($upgrade_file, 'r');

	// Pull only the first 8kiB of the file in.
	$upgrade_data = fread( $fp, 8192 );

	// PHP will close file handle, but we are good citizens.
	fclose($fp);

	preg_match( '|Upgrade Name:(.*)$|mi', $upgrade_data, $name );
	preg_match( '|Upgrade URI:(.*)$|mi', $upgrade_data, $uri );
	preg_match( '|Version:(.*)|i', $upgrade_data, $version );
	preg_match( '|Description:(.*)$|mi', $upgrade_data, $description );
	preg_match( '|Author:(.*)$|mi', $upgrade_data, $author_name );
	preg_match( '|Author URI:(.*)$|mi', $upgrade_data, $author_uri );
	preg_match( '|Text Domain:(.*)$|mi', $upgrade_data, $text_domain );
	preg_match( '|Domain Path:(.*)$|mi', $upgrade_data, $domain_path );

	foreach ( array( 'name', 'uri', 'version', 'description', 'author_name', 'author_uri', 'text_domain', 'domain_path' ) as $field ) {
		if ( !empty( ${$field} ) )
			${$field} = _cleanup_header_comment(${$field}[1]);
		else
			${$field} = '';
	}

	$upgrade_data = array(
				'Name' => $name, 'Title' => $name, 'UpgradeURI' => $uri, 'Description' => $description,
				'Author' => $author_name, 'AuthorURI' => $author_uri, 'Version' => $version,
				'TextDomain' => $text_domain, 'DomainPath' => $domain_path
				);
	if ( $markup || $translate )
		$upgrade_data = _get_upgrade_data_markup_translate($upgrade_file, $upgrade_data, $markup, $translate);

	return $upgrade_data;
}


function _get_upgrade_data_markup_translate($upgrade_file, $upgrade_data, $markup = true, $translate = true) {

	//Translate fields
	if( $translate && ! empty($upgrade_data['TextDomain']) ) {
		if( ! empty( $upgrade_data['DomainPath'] ) )
			load_upgrade_textdomain($upgrade_data['TextDomain'], dirname($upgrade_file). $upgrade_data['DomainPath']);
		else
			load_upgrade_textdomain($upgrade_data['TextDomain'], dirname($upgrade_file));

		foreach ( array('Name', 'UpgradeURI', 'Description', 'Author', 'AuthorURI', 'Version') as $field )
			$upgrade_data[ $field ] = translate($upgrade_data[ $field ], $upgrade_data['TextDomain']);
	}

	//Apply Markup
	if ( $markup ) {
		if ( ! empty($upgrade_data['UpgradeURI']) && ! empty($upgrade_data['Name']) )
			$upgrade_data['Title'] = '<a href="' . $upgrade_data['UpgradeURI'] . '" title="' . __( 'Visit upgrade homepage', 'wpsc' ) . '">' . $upgrade_data['Name'] . '</a>';
		else
			$upgrade_data['Title'] = $upgrade_data['Name'];

		if ( ! empty($upgrade_data['AuthorURI']) && ! empty($upgrade_data['Author']) )
			$upgrade_data['Author'] = '<a href="' . $upgrade_data['AuthorURI'] . '" title="' . __( 'Visit author homepage', 'wpsc' ) . '">' . $upgrade_data['Author'] . '</a>';

		$upgrade_data['Description'] = wptexturize( $upgrade_data['Description'] );
		if( ! empty($upgrade_data['Author']) )
			$upgrade_data['Description'] .= ' <cite>' . sprintf( __('By %s', 'wpsc'), $upgrade_data['Author'] ) . '.</cite>';
	}

	$upgrades_allowedtags = array('a' => array('href' => array(),'title' => array()),'abbr' => array('title' => array()),'acronym' => array('title' => array()),'code' => array(),'em' => array(),'strong' => array());

	// Sanitize all displayed data
	$upgrade_data['Title']       = wp_kses($upgrade_data['Title'], $upgrades_allowedtags);
	$upgrade_data['Version']     = wp_kses($upgrade_data['Version'], $upgrades_allowedtags);
	$upgrade_data['Description'] = wp_kses($upgrade_data['Description'], $upgrades_allowedtags);
	$upgrade_data['Author']      = wp_kses($upgrade_data['Author'], $upgrades_allowedtags);

	return $upgrade_data;
}

/**
 * Get a list of a upgrade's files.
 *
 * @since 2.8.0
 *
 * @param string $upgrade upgrade ID
 * @return array List of files relative to the upgrade root.
 */
function get_upgrade_files($upgrade) {
	$upgrade_file = WPSC_UPGRADES_DIR . '/' . $upgrade;
	$dir = dirname($upgrade_file);
	$upgrade_files = array($upgrade);
	if ( is_dir($dir) && $dir != WPSC_UPGRADES_DIR ) {
		$upgrades_dir = @ opendir( $dir );
		if ( $upgrades_dir ) {
			while (($file = readdir( $upgrades_dir ) ) !== false ) {
				if ( substr($file, 0, 1) == '.' )
					continue;
				if ( is_dir( $dir . '/' . $file ) ) {
					$upgrades_subdir = @ opendir( $dir . '/' . $file );
					if ( $upgrades_subdir ) {
						while (($subfile = readdir( $upgrades_subdir ) ) !== false ) {
							if ( substr($subfile, 0, 1) == '.' )
								continue;
							$upgrade_files[] = plugin_basename("$dir/$file/$subfile");
						}
						@closedir( $upgrades_subdir );
					}
				} else {
					if ( plugin_basename("$dir/$file") != $upgrade )
						$upgrade_files[] = plugin_basename("$dir/$file");
				}
			}
			@closedir( $upgrades_dir );
		}
	}

	return $upgrade_files;
}

/**
 * Check the upgrades directory and retrieve all upgrade files with upgrade data.
 *
 * WordPress Shopping Cart only supports upgrade files in the base upgrades directory
 * (uploads/wpsc/upgrades) and in one directory above the upgrades directory
 * (uploads/wpsc/upgrades/my-upgrade). The file it looks for has the upgrade data and
 * must be found in those two locations. It is recommended that do keep your
 * upgrade files in directories.
 *
 * The file with the upgrade data is the file that will be included and therefore
 * needs to have the main execution for the upgrade. This does not mean
 * everything must be contained in the file and it is recommended that the file
 * be split for maintainability. Keep everything in one file for extreme
 * optimization purposes.
 *
 * @since unknown
 *
 * @param string $upgrade_folder Optional. Relative path to single upgrade folder.
 * @return array Key is the upgrade file path and the value is an array of the upgrade data.
 */
function get_upgrades($upgrade_folder = '') {

	if ( ! $cache_upgrades = wp_cache_get('wpsc_upgrades', 'wpsc_upgrades') )
		$cache_upgrades = array();

	if ( isset($cache_upgrades[ $upgrade_folder ]) )
		return $cache_upgrades[ $upgrade_folder ];

	$wpsc_upgrades = array ();
	$upgrade_root = WPSC_UPGRADES_DIR;
	if( !empty($upgrade_folder) )
		$upgrade_root .= $upgrade_folder;

	// Files in wp-content/upgrades directory
	$upgrades_dir = @ opendir( $upgrade_root);
	$upgrade_files = array();

	if ( $upgrades_dir ) {
		while (($file = readdir( $upgrades_dir ) ) !== false ) {
			if ( substr($file, 0, 1) == '.' )
				continue;
			if ( is_dir( $upgrade_root.'/'.$file ) ) {
				$upgrades_subdir = @ opendir( $upgrade_root.'/'.$file );
				if ( $upgrades_subdir ) {
					while (($subfile = readdir( $upgrades_subdir ) ) !== false ) {
						if ( substr($subfile, 0, 1) == '.' )
							continue;
						if ( substr($subfile, -4) == '.php' )
							$upgrade_files[] = "$file/$subfile";
					}
				}
			} else {
				if ( substr($file, -4) == '.php' )
					$upgrade_files[] = $file;
			}
		}
	}

	@closedir( $upgrades_dir );
	@closedir( $upgrades_subdir );

	if ( !$upgrades_dir || empty($upgrade_files) )
		return $wpsc_upgrades;

	foreach ( $upgrade_files as $upgrade_file ) {
		if ( !is_readable( "$upgrade_root/$upgrade_file" ) )
			continue;

		$upgrade_data = get_upgrade_data( "$upgrade_root/$upgrade_file", false, false ); //Do not apply markup/translate as it'll be cached.

		if ( empty ( $upgrade_data['Name'] ) )
			continue;

		$wpsc_upgrades[plugin_basename( $upgrade_file )] = $upgrade_data;
	}

	uasort( $wpsc_upgrades, create_function( '$a, $b', 'return strnatcasecmp( $a["Name"], $b["Name"] );' ));

	$cache_upgrades[ $upgrade_folder ] = $wpsc_upgrades;
	wp_cache_set('wpsc_upgrades', $cache_upgrades, 'wpsc_upgrades');

	return $wpsc_upgrades;
}
?>
