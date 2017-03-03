<?php
/**
 * Plugin Name: Wordpress Gig Calendar
 * Plugin URI: https://github.com/optune/wordpress-gig-calendar
 * Description: This plugin adds Optune.me gigs to your website
 * Version: 1.1
 * Author: Sergei Pestin
 * Author URI: https://www.upwork.com/fl/sergeypestin
 * License: GPL2
 */

require_once( 'optune.php' );

// Add shortcode to display Gig calendar
add_shortcode('optune-gig-calendar', 'optune_show_gig_calendar');
function optune_show_gig_calendar(){
	$gigs = new Optune_gig_posts();

	// If our Gigs info is old...
	if( $gigs->isOld() ){
		// Remove old gigs to insert new one
		$gigs->removeOldGigs();

		require_once( 'lib/Http.php' );
		$web = new Optune_simplify_http();

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
add_action( 'wp_enqueue_scripts', 'optune_add_css_style' );
function optune_add_css_style() {
    wp_enqueue_style( 'optune_css_style', plugins_url( 'css/style.css', __FILE__ ), array(), '' );
}

// Display settings page if we in Admin section
if( is_admin() ){
		$admin = new Optune_gig_calendar(); }

