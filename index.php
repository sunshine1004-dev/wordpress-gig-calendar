<?php
/**
 * Plugin Name: Wordpress Gig Calendar
 * Plugin URI: https://github.com/optune/wordpress-gig-calendar
 * Description: This plugin adds Optune.me gigs to your website
 * Version: 0.0.9
 * Author: Sergei Pestin
 * Author URI: https://www.upwork.com/fl/sergeypestin
 * License: GPL2
 */

require_once( 'optune.php' );

// Add shortcode to display Gig calendar
add_shortcode('gig-calendar', 'show_gig_calendar');
function show_gig_calendar(){
	$gigs = new GigPosts();

	// If our Gigs info is old...
	if( $gigs->isOld() ){
		require_once( 'lib/Http.php' );

		// Remove old gigs to insert new one
		$gigs->removeOldGigs();

		$web = new Simplify_HTTP();
		$data = $web->apiRequest( $gigs->options['gig_username'], 'GET' );
		if( $data )
			$gigs->storeGigs( $data );
	}

	if( $posts = $gigs->getAllGigs() ){
		$gigs->displayGigs( $posts );
	} else {
		$gigs->displayError();
	}
}

// Add default CSS
add_action( 'wp_enqueue_scripts', 'add_css_styles' );
function add_css_styles() {
    wp_register_style(
        'main_css',
			plugins_url( 'css/main.scss.css', __FILE__ )
    );

    wp_enqueue_style( 'main_css', plugins_url( 'css/main.scss.css', __FILE__ ), array(), '' );
}

// Display settings page if we in Admin section
if( is_admin() ){
		$admin = new GigCalendar(); }

