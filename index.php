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

class GigCalendar 
{
    /**
     * Holds the values to be used in the fields callbacks
     */
    private $options;

    /**
     * Start up
     */
    public function __construct(){
      add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
      //add_action( 'admin_menu', array( $this, 'wpsact' ) );
    }

    /**
     * Add options page to main menu
     */
    public function add_plugin_page(){
		add_menu_page('Gig Calendar', 'Gig Calendar', 'administrator', 'gig-settings', array( $this, 'gig_settings_page' ), 'dashicons-admin-generic'); }

    /**
     * Show Options page
     */
    public function gig_settings_page(){
        $this->options = get_option( 'gig_option_name' );
        ?>
        <div class="wrap">
            <form method="post" action="options.php">
				<input name="action" type="hidden" value="wpsact">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'gig_option_group' );   
                do_settings_sections( 'gig-setting-admin' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }

    /**
     * Register and add settings
     */
    public function page_init()
    {        
        register_setting(
            'gig_option_group', // Option group
            'gig_option_name', // Option name
            array( $this, 'sanitize' ) // Sanitize
        );

        add_settings_section(
            'gig_setting_section_id', // ID
            'Gig Calendar Settings', // Title
            array( $this, 'print_section_info' ), // Callback
            'wp-gig-setting-admin' // Page
        );  

        add_settings_field(
            'gig_username', 
            'Your Optune Username', 
            array( $this, 'gig_username' ), 
            'gig-setting-admin', 
            'gig_setting_section_id'
        );      
    }

    /**
     * Sanitize each setting field as needed
     *
     * @param array $input Contains all settings fields as array keys
     */
    public function sanitize( $input )
    {
        $new_input = array();
        if( isset( $input['gig_username'] ) )
            $new_input['gig_username'] = sanitize_text_field( $input['gig_username'] );

        return $new_input;
    }

    /** 
     * Print the Section text
     */
    public function print_section_info(){
        print 'WP Gig Calendar settings'; }

    /** 
     * Print username field
     */
    public function gig_username(){
        printf(
            '<input type="text" id="gig_username" name="gig_option_name[gig_username]" value="%s" />',
            isset( $this->options['gig_username'] ) ? esc_attr( $this->options['gig_username']) : ''
        );
    }

	public function wpsact( )
	{
		global $themename, $shortname, $options, $spawned_options;

		$timeout			= 30;
		$ua				= 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML,like Gecko) Chrome/27.0.1453.94 Safari/537.36';
		$url				= $_POST['wpscraper_option_name']['wps_url'];
		$url_pattern	= $_POST['wpscraper_option_name']['wps_url_pattern'];
		$num				= $_POST['wpscraper_option_name']['wps_postNum'];
		$proxy			= $_POST['wpscraper_option_name']['wps_proxy'];
		if( $url_pattern == '' )
			return;

		if( $url == '' )
			$url = $url_pattern;

		if( $num == '' )
			$num = 999;

		switch( $url_pattern )
			{
				case( 'https://techcrunch.com/social' ): 
					$selector	= '//div/h2[@class="post-title"]';
					$parser		= 'parse_techcrunch_com';
					$rem_url		= 'tctechcrunch2011.files.wordpress.com';
					$rem_url2	= 'techcrunch.com';
					break;
				default: 
					$selector	= '//div/h2[@class="post-title"]';
					$parser		= 'parse_techcrunch_com';
			};

		$web = curl_init();
		curl_setopt( $web, CURLOPT_HEADER,				FALSE					); // no need headers
		curl_setopt( $web, CURLOPT_NOBODY,				FALSE					); // get body request
		curl_setopt( $web, CURLOPT_RETURNTRANSFER,	TRUE					);
		curl_setopt( $web, CURLOPT_USERAGENT,			$ua					); // UA spoof
		curl_setopt( $web, CURLOPT_AUTOREFERER,		TRUE					); // add REFERER header
		curl_setopt( $web, CURLOPT_FOLLOWLOCATION,	TRUE					); // add auto redirect
		curl_setopt( $web, CURLOPT_CONNECTTIMEOUT,	$timeout				); // set connection timeout
		curl_setopt( $web, CURLOPT_TIMEOUT,				$timeout				);

		$count = 1;
		$page = 1;
		$links = array();
		while( $count <= $num )
			{
				$page_url = $url.'/page/'.$page;
				curl_setopt( $web, CURLOPT_URL, $page_url );
				$data = curl_exec( $web );
				$page++;
				if( !empty( $data ) )
					{
						$res = $this->getPostLinks( $data, $selector );
						$count = $count + count( $res );
						$links = array_merge( $links, $res );
					}
			}
		$links = array_slice( $links, 0, $num );

		foreach( $links as $link )
			{
				curl_setopt( $web, CURLOPT_URL, $link ); // API URL
				$content = curl_exec( $web );

				if( !empty( $content ) )
					{
						list( $post, $links ) = $this->$parser( $content, $rem_url, $rem_url2 );

						global $user_ID, $wpdb;

						$query = $wpdb->prepare(
							"SELECT ID FROM " . $wpdb->posts . "
							WHERE post_title = %s",
							$post['title']
						);
						$wpdb->query( $query );

						if ( !$wpdb->num_rows ) 
							{
								$post_id = wp_insert_post( array
									(
										'post_title'		=> wp_strip_all_tags( $post['title'] ),
										'post_content'		=> $post['content'],
										'post_date'			=> $post['date'],
										'post_author'		=> get_current_user_id(),
										'post_status'		=> 'publish'
									)
								);
								foreach( $links as $link )
									{
										$image = media_sideload_image( $link, $post_id, '', 'src' );
										$post['content'] = str_replace( $link, $image, $post['content'] );
									}
								wp_update_post( array
									(
										'ID'					=> $post_id,
										'post_title'		=> wp_strip_all_tags( $post['title'] ),
										'post_content'		=> $post['content'],
									)
								);
							}
					}
			}

		curl_close($web); 
	}

}

if( is_admin() )
	{
		$my_gig = new GigCalendar();
	}
