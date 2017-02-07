<?php

/**
 * Class for logging events and errors.
 *
 * Significant gratitude to Pippin Williamson and other contributors to the WP_Logging project
 * This class is experimental and may change in the future in such a way as to break back compat.
 * You have been warned.
 *
 * @access private
 */

class WPSC_Logging {

    /**
     * WPSC_Logging Class
     *
     *
     * @access public
     * @return void
     *
     * @since 3.9
     */
    function __construct() {

        // create the log post type
        add_action( 'init', array( $this, 'register_post_type' ) );

        // create types taxonomy and default types
        add_action( 'init', array( $this, 'register_taxonomy' ) );

        // make a cron job for this hook to start pruning
        add_action( 'wpsc_logging_prune_routine', array( $this, 'prune_logs' ) );

    }

    /**
     * Allows you to tie in a cron job and prune old logs.
     *
     * @access public
     *
     * @uses $this->get_logs_to_prune()     Returns array of posts via get_posts of logs to prune
     * @uses $this->prune_old_logs()        Deletes the logs that we don't want anymore
     *
     * @since 3.9
     */
    public function prune_logs(){

        if ( false === apply_filters( 'wpsc_logging_should_we_prune', false ) ) {
            return;
        }

        $logs_to_prune = $this->get_logs_to_prune();

        if ( isset( $logs_to_prune ) && ! empty( $logs_to_prune ) ){
            $this->prune_old_logs( $logs_to_prune );
        }

    }

    /**
     * Deletes the old logs that we don't want
     *
     * @access private
     *
     * @param array[ WP_Post ]  $logs The array of logs we want to prune
     *
     * @since 3.9
     */
    private function prune_old_logs( $logs ) {

        foreach( $logs as $l ){
            wp_delete_post( $l->ID, apply_filters( 'wpsc_logging_force_delete_log', true ) );
        }

    }

    /**
     * Returns an array of posts that are prune candidates.
     *
     * @access private
     *
     * @return array     $old_logs     The array of posts that were returned from get_posts
     *
     * @uses apply_filters()           Allows users to change given args
     * @uses get_posts()               Returns an array of posts from given args
     *
     * @filter wp_logging_prune_when           Users can change how long ago we are looking for logs to prune
     * @filter wp_logging_prune_query_args     Gives users access to change any query args for pruning
     *
     * @since 3.9
     */
    private function get_logs_to_prune(){

        $how_old = apply_filters( 'wpsc_logging_prune_when', '2 weeks ago' );

        $args = array(
            'post_type'      => 'wpsc_log',
            'posts_per_page' => '100',
            'date_query'     => array(
                array(
                    'column' => 'post_date_gmt',
                    'before' => (string) $how_old,
                )
            )
        );

       return get_posts( apply_filters( 'wpsc_logging_prune_query_args', $args ) );
    }

    /**
     * Log types
     *
     * Sets up the default log types and allows for new ones to be created
     *
     * @access      private
     *
     * @return     array
     *
     * @since 3.9
     */
    private static function log_types() {
        $terms = array(
            'error', 'event'
        );

        return apply_filters( 'wpsc_log_types', $terms );
    }


    /**
     * Registers the wp_log Post Type
     *
     * @access      public
     * @uses        register_post_type()
     *
     * @return     void
     *
     * @since 3.9
     */
    public function register_post_type() {

        /* logs post type */

        $log_args = array(
            'labels'          => array( 'name' => __( 'Logs', 'wp-e-commerce' ) ),
            'public'          => false,
            'show_in_ui'      => defined( 'WP_DEBUG' ) && WP_DEBUG,
            'query_var'       => false,
            'rewrite'         => false,
            'capability_type' => 'post',
            'supports'        => array( 'title', 'editor', 'custom-fields' ),
            'can_export'      => false
        );

        register_post_type( 'wpsc_log', apply_filters( 'wpsc_logging_post_type_args', $log_args ) );

    }

    /**
     * Registers the Type Taxonomy
     *
     * The Type taxonomy is used to determine the type of log entry
     *
     * @access      public
     *
     * @uses        register_taxonomy()
     * @uses        term_exists()
     * @uses        wp_insert_term()
     *
     * @return     void
     *
     * @since 3.9
     */
    public function register_taxonomy() {

        register_taxonomy(
            'wpsc_log_type',
            'wpsc_log',
            apply_filters( 'wpsc_logging_taxonomy_args', array( 'public' => false, 'show_ui' => defined( 'WP_DEBUG' ) && WP_DEBUG ) )
        );

        $types = self::log_types();

        foreach ( $types as $type ) {
            if ( ! term_exists( $type, 'wpsc_log_type' ) ) {
                wp_insert_term( $type, 'wpsc_log_type' );
            }
        }
    }

    /**
     * Check if a log type is valid
     *
     * Checks to see if the specified type is in the registered list of types
     *
     * @access      private
     *
     * @return     boolean
     *
     * @since 3.9
     */
    private static function valid_type( $type ) {
        return in_array( $type, self::log_types() );
    }

    /**
     * Create new log entry
     *
     * This is just a simple and fast way to log something. Use self::insert_log()
     * if you need to store custom meta data
     *
     * @access      private
     *
     * @uses        self::insert_log()
     *
     * @return      int The ID of the new log entry
     *
     * @since 3.9
     */
    public static function add( $title = '', $message = '', $parent = 0, $type = null ) {

        $log_data = array(
            'post_title'   => $title,
            'post_content' => $message,
            'post_parent'  => $parent,
            'log_type'     => $type
        );

        return self::insert_log( $log_data );

    }

    /**
     * Stores a log entry
     *
     * @access      private
     *
     * @uses        wp_parse_args()
     * @uses        wp_insert_post()
     * @uses        update_post_meta()
     * @uses        wp_set_object_terms()
     * @uses        sanitize_key()
     *
     * @return      int The ID of the newly created log item
     *
     * @since 3.9
     */
    public static function insert_log( $log_data = array(), $log_meta = array() ) {

        $defaults = array(
            'post_type'    => 'wpsc_log',
            'post_status'  => 'publish',
            'post_parent'  => 0,
            'post_content' => '',
            'log_type'     => false
        );

        $args = wp_parse_args( $log_data, $defaults );

        do_action( 'wpsc_pre_insert_log', $args, $log_meta );

        // store the log entry
        $log_id = wp_insert_post( $args );

        // set the log type, if any
        if ( $log_data['log_type'] && self::valid_type( $log_data['log_type'] ) ) {
            wp_set_object_terms( $log_id, $log_data['log_type'], 'wpsc_log_type', false );
        }

        // set log meta, if any
        if ( $log_id && ! empty( $log_meta ) ) {
            foreach ( (array) $log_meta as $key => $meta ) {
                update_post_meta( $log_id, 'wp_log_' . sanitize_key( $key ), $meta );
            }
        }

        do_action( 'wpsc_post_insert_log', $log_id );

        return $log_id;
    }

    /**
     * Update an existing log item.
     *
     * @access private
     *
     * @uses   wp_parse_args()
     * @uses   wp_update_post()
     * @uses   update_post_meta()
     *
     * @return integer The ID of the post if the post is successfully updated in the database. Otherwise, returns 0.
     *
     * @since 3.9
     */
    public static function update_log( $log_data = array(), $log_meta = array() ) {

        $defaults = array(
            'post_type'   => 'wpsc_log',
            'post_status' => 'publish',
            'post_parent' => 0
        );

        $args = wp_parse_args( $log_data, $defaults );

        do_action( 'wpsc_pre_update_log', $args, $log_meta );

        // store the log entry
        $log_id = wp_update_post( $args );

        if ( $log_id && ! empty( $log_meta ) ) {
            foreach ( (array) $log_meta as $key => $meta ) {
                if ( ! empty( $meta ) ) {
                    update_post_meta( $log_id, '_wp_log_' . sanitize_key( $key ), $meta );
                }
            }
        }

        do_action( 'wpsc_post_update_log', $log_id );

        return $log_id;

    }

    /**
     * Easily retrieves log items for a particular object ID
     *
     * @access      private
     *
     * @uses        self::get_connected_logs()
     *
     * @return      array
     *
     * @since 3.9
     */
    public static function get_logs( $object_id = 0, $type = null, $paged = null ) {

        return self::get_connected_logs( array( 'post_parent' => $object_id, 'paged' => $paged, 'log_type' => $type ) );
    }

    /**
     * Retrieve all connected logs
     *
     * Used for retrieving logs related to particular items, such as a specific purchase.
     *
     * @access  private
     *
     * @uses    wp_parse_args()
     * @uses    get_posts()
     * @uses    get_query_var()
     * @uses    self::valid_type()
     *
     * @return  array|false Array of logs, if any.  Otherwise, false.
     *
     * @since 3.9
     */
    public static function get_connected_logs( $args = array() ) {

        $defaults = array(
            'post_parent'    => 0,
            'post_type'      => 'wpsc_log',
            'posts_per_page' => 10,
            'post_status'    => 'publish',
            'paged'          => get_query_var( 'paged' ),
            'log_type'       => false
        );

        $query_args = wp_parse_args( $args, $defaults );

        if ( $query_args['log_type'] && self::valid_type( $query_args['log_type'] ) ) {

            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'wpsc_log_type',
                    'field'    => 'slug',
                    'terms'    => $query_args['log_type']
                )
            );
        }

        $logs = get_posts( $query_args );

        if ( $logs ) {
            return $logs;
        }

        // no logs found
        return false;

    }

    /**
     * Retrieves number of log entries connected to particular object ID
     *
     * @access  private
     *
     * @uses    WP_Query()
     * @uses    self::valid_type()
     *
     * @return  int
     *
     * @since 3.9
     */
    public static function get_log_count( $object_id = 0, $type = null, $meta_query = null ) {

        // Re-consider usage of posts_per_page => -1 here.
        $query_args = array(
            'post_parent'    => $object_id,
            'post_type'      => 'wpsc_log',
            'posts_per_page' => -1,
            'post_status'    => 'publish'
        );

        if ( ! empty( $type ) && self::valid_type( $type ) ) {

            $query_args['tax_query'] = array(
                array(
                    'taxonomy' => 'wpsc_log_type',
                    'field'    => 'slug',
                    'terms'    => $type
                )
            );

        }

        if ( ! empty( $meta_query ) ) {
            $query_args['meta_query'] = $meta_query;
        }

        $logs = new WP_Query( $query_args );

        return (int) $logs->post_count;

    }

    /**
     * Allows other plugins or settings to force UI visibility.
     *
     * Helpful for more granular 'debug' settings for things like payment gateways.
     *
     * @param  array $args Arguments passed to the taxonomy and post type hooks.
     * @return array $args Arguments passed to the taxonomy and post type hooks.
     *
     * @since  3.11.0
     */
    public static function force_ui( $args ) {
        $args['show_ui'] = true;
        return $args;
    }

}

$GLOBALS['wpsc_logs'] = new WPSC_Logging();
