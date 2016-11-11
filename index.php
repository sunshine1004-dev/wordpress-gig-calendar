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

// Backend
class GigCalendar 
{
    // Holds the values to be used in the fields callbacks
    private $options;

    // Start up
    public function __construct(){
      add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'page_init' ) );
    }

    // Add options page to main menu
    public function add_plugin_page(){
		add_menu_page('Gig Calendar', 'Gig Calendar', 'administrator', 'gig-settings', array( $this, 'gig_settings_page' ), 'dashicons-admin-generic'); }

    // Show Options page
    public function gig_settings_page(){
        $this->options = get_option( 'gig_option_name' );
        ?>
        <div class="wrap">
            <form method="post" action="options.php">
				<input name="action" type="hidden" value="wpsact">
            <?php
                // This prints out all hidden setting fields
                settings_fields( 'gig_option_group' );   
                do_settings_sections( 'gig-settings-admin' );
                submit_button(); 
            ?>
            </form>
        </div>
        <?php
    }

    // Register and add settings
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
            '', //array( $this, 'print_section_info' ),
            'gig-settings-admin' // Page
        );  

		  // Username input
        add_settings_field(
            'gig_username', 
            'Your Optune Username', 
            array( $this, 'gig_username' ), 
            'gig-settings-admin', 
            'gig_setting_section_id'
        );      

		  // Default post status
        add_settings_field(
            'gig_post_status', 
            'Default Post Status', 
            array( $this, 'gig_post_status' ), 
            'gig-settings-admin', 
            'gig_setting_section_id'
        );      
		  
		  // Default post type
        add_settings_field(
            'gig_post_type', 
            'Default Post Type', 
            array( $this, 'gig_post_type' ), 
            'gig-settings-admin', 
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

        if( isset( $input['gig_post_status'] ) )
            $new_input['gig_post_status'] = sanitize_text_field( $input['gig_post_status'] );

        if( isset( $input['gig_post_type'] ) )
            $new_input['gig_post_type'] = sanitize_text_field( $input['gig_post_type'] );

        return $new_input;
    }

    // Print username field
    public function gig_username(){
        printf(
            '<input type="text" id="gig_username" name="gig_option_name[gig_username]" value="%s" />',
            isset( $this->options['gig_username'] ) ? esc_attr( $this->options['gig_username']) : ''
        );
    }

    // Print post status field
    public function gig_post_status(){
        echo
				'<select type="option" id="gig_post_status" name="gig_option_name[gig_post_status]">
					<option ', ( $this->options['gig_post_status'] == 'publish' ? 'selected' : '' ), ' value="publish">publish</option>
					<option ', ( $this->options['gig_post_status'] == 'draft' ? 'selected' : '' ), ' value="draft">draft</option>
					<option ', ( $this->options['gig_post_status'] == 'pending' ? 'selected' : '' ), ' value="pending">pending</option>
				</select>';
    }

    // Print post type field
    public function gig_post_type(){
        echo
				'<select type="option" id="gig_post_type" name="gig_option_name[gig_post_type]">
					<option ', ( $this->options['gig_post_type'] == 'post' ? 'selected' : '' ), ' value="post">post</option>
					<option ', ( $this->options['gig_post_type'] == 'gig' ? 'selected' : '' ), ' value="gig">gig</option>
				</select>';
    }

}

// Api helper
class GigPosts
{
	// Special post type to detect Gigs
	const SPEC = 'gig';

	// Plugin settings here
	private $options;

	public function __construct(){
      $this->options = get_option( 'gig_option_name' );
	}

	public function getOldGigs(){
		$args = array(
			'posts_per_page'	=> -1,
			'post_type'			=> $this->options['gig_post_type'],
			'post_content'		=> self::SPEC,
			'orderby'			=> 'date',
			'sort_order'		=> 'desc',
			'date_query' => array(
				array( 
					'before' => '1 days ago',
					'inclusive'		=> TRUE,
				),
			),
			'post_status'		=> array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash' ),
		);
		
		$posts = get_posts( $args );
		if( count( $posts ) > 0 )
			return TRUE;

		return FALSE;
	}

	public function storeGigs( $gigs ){
		foreach( $gigs as $gig ){
			global $user_ID, $wpdb;

			$post_id = wp_insert_post( array
				(
					'post_title'		=> wp_strip_all_tags( $gig['title'] ),
					'post_type'			=> $this->options['gig_post_type'],
					'post_content'		=> self::SPEC,
					'post_author'		=> get_current_user_id(),
					'post_status'		=> $this->options['gig_post_status'],
				)
			);

			// Insert or Update Gig dates
			if ( ! add_post_meta( $post_id, 'playDate', $gig['playDate'], true ) ){
				update_post_meta ( $post_id, 'playDate', $gig['playDate'] ); }

			if ( ! add_post_meta( $post_id, 'playTime', $gig['playTime'], true ) ){
				update_post_meta ( $post_id, 'playTIme', $gig['playTime'] ); }
		}
	}

	public function removeOldGigs(){
		if( $posts = getAllGigs() ){
			foreach( $posts as $post ){
				delete_post_meta( $post->ID, 'playDate' );
				delete_post_meta( $post->ID, 'playTime' );
				wp_delete_post( $post->ID, TRUE );
			}
		}
	}

	public function getAllGigs(){
	/*
			$args = array(
				'posts_per_page'	=> -1,
				'post_title'		=> wp_strip_all_tags( $gig['title'] ),
				'post_type'			=> 'gig',
				'meta_query'		=> array(
					'relation'		=> 'AND',
					array(
						'key'			=> 'playDate',
						'value'		=> $gig['playDate'],
					),
					array(
						'key'			=> 'playTime',
						'value'		=> $gig['playTime'],
					),
				),
			);
			
			$post = get_posts( $args );
			if( count( $post ) > 0 ) {
				var_dump( $post );
			}
	*/
		$args = array(
			'posts_per_page'	=> -1,
			'post_type'			=> $this->options['gig_post_type'],
			'post_content'		=> self::SPEC,
			'orderby'			=> 'date',
			'sort_order'		=> 'desc',
			'post_status'		=> array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash' ),
		);
		
		$posts = get_posts( $args );
		if( count( $posts ) > 0 )
			return $posts;

		return FALSE;
	}

	public function displayError(){
		echo 'No Gigs Available';
	}

	public function displayGigs( $gigs ){
		foreach( $gigs as $gig ){
?>
		<div class="summary card padded row push top mini interactive">
			<div class="column third">
				<div class="media">
					<div class="push right small">
						<p class="sized normal">&nbsp;</p>
					</div>
					<div class="body flex">
						<header class="text small uppercase">
							<sub>We</sub>
							<!-- react-text: 6024 -->&nbsp;<!-- /react-text -->
							<!-- react-text: 6025 -->16.11.2016<!-- /react-text -->
							<!-- react-text: 6026 -->&nbsp;<!-- /react-text -->
						</header>
						<div class="body push top bottom micro">
							<h3 class="h1 bold">Moscow meet</h3>
						</div>
						<div class="footer text small">
							<!-- react-text: 6030 -->Test Venue<!-- /react-text -->
							<!-- react-text: 6031 --> <!-- /react-text -->
							<sub></sub>
						</div>
					</div>
				</div>
			</div>
			<div class="column third">
				<h4 class="font-small-bold push bottom micro">
					<!-- react-text: 6035 -->Status<!-- /react-text -->
					<span class="font-small-regular neutral push left small">Confirmed</span>
				</h4>
			</div>
		</div>
<?php
		}
	}

}

// Add shortcode to display Gig calendar
add_shortcode('gig-calendar', 'show_gig_calendar');
function show_gig_calendar(){
	$gigs = new GigPosts();

	// If our Gigs info is old...
	if( $gigs->getOldGigs() ){
		require_once( 'lib/Http.php' );

		// Remove old gigs to insert new one
		$gigs->removeOldGigs();

		$web = new Simplify_HTTP();
		$data = $web->apiRequest( $gigs->options['gig_username'], 'GET' );
		$gigs->storeGigs( $data );
	}

	
	if( $posts = $gigs->getAllGigs() ){
		$gigs->displayGigs( $posts );
	} else {
		$gigs->displayError();
	}
}

if( is_admin() ){
		$admin = new GigCalendar(); }

