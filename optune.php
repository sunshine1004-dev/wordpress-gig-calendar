<?php

// API classes

// Backend
class Optune_gig_calendar 
{
    // Holds the values to be used in the fields callbacks
    private $options;

    // Start up
    public function __construct(){
      add_action( 'admin_menu', array( $this, 'optune_add_plugin_page' ) );
		add_action( 'admin_init', array( $this, 'optune_page_init' ) );
    }

    // Add options page to main menu
    public function optune_add_plugin_page(){
		 add_menu_page('Optune Gigs', 'Optune Gigs', 'administrator', 'gig-settings', array( $this, 'optune_gig_settings_page' ), 'dashicons-calendar-alt'); }

    // Show Options page
    public function optune_gig_settings_page(){
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
    public function optune_page_init()
    {        
        register_setting(
            'gig_option_group', // Option group
            'gig_option_name', // Option name
            array( $this, 'optune_sanitize' ) // Sanitize
        );

        add_settings_section(
            'gig_setting_section_id', // ID
            'Gig Calendar Settings', // Title
            '', //array( $this, 'print_section_info' ),
            'gig-settings-admin' // Page
        );  

		  // Username input
        add_settings_field(
            'optune_gig_username', 
            'Your Optune Username', 
            array( $this, 'optune_gig_username' ), 
            'gig-settings-admin', 
            'gig_setting_section_id'
        );      

		  // Default post status
        add_settings_field(
            'optune_gig_post_status', 
            'Default Post Status', 
            array( $this, 'optune_gig_post_status' ), 
            'gig-settings-admin', 
            'gig_setting_section_id'
        );      
    }

    // Sanitize each setting field as needed: @param array $input Contains all settings fields as array keys
    public function optune_sanitize( $input )
    {
        $this->options = get_option( 'gig_option_name' );
		  if( $input['gig_username'] != $this->options['gig_username'] || $input['gig_post_status'] != $this->options['gig_post_status'] ) 
			  {
					if ( get_option( 'gig_last_update' ) !== false ) { 
						update_option( 'gig_last_update', $date ); 
					} else { 
						add_option( 'gig_last_update', $date, '', 'yes' ); 
					}
			  }

        $new_input = array();
        if( isset( $input['gig_username'] ) )
            $new_input['gig_username'] = sanitize_text_field( $input['gig_username'] );

        if( isset( $input['gig_post_status'] ) )
            $new_input['gig_post_status'] = sanitize_text_field( $input['gig_post_status'] );

        return $new_input;
    }

    // Print username field
    public function optune_gig_username(){
        printf(
            '<input type="text" id="gig_username" name="gig_option_name[gig_username]" value="%s" />',
            isset( $this->options['gig_username'] ) ? esc_attr( $this->options['gig_username']) : ''
        );
    }

    // Print post status field
    public function optune_gig_post_status(){
        echo
				'<select type="option" id="gig_post_status" name="gig_option_name[gig_post_status]">
					<option ', ( $this->options['gig_post_status'] == 'publish' ? 'selected' : '' ), ' value="publish">publish</option>
					<option ', ( $this->options['gig_post_status'] == 'draft' ? 'selected' : '' ), ' value="draft">draft</option>
					<option ', ( $this->options['gig_post_status'] == 'pending' ? 'selected' : '' ), ' value="pending">pending</option>
				</select>';
    }
}

// Api helper
class Optune_gig_posts
{
	// Special post type to detect Gigs
	const SPEC = 'gig';

	// Plugin settings here
	public $options;

	public function __construct(){
      $this->options = get_option( 'gig_option_name' );
	}

	// Update date in option
	public function updateDate( $date ){
		if ( get_option( 'gig_last_update' ) !== false ) { 
			update_option( 'gig_last_update', $date ); 
		} else { 
			add_option( 'gig_last_update', $date, '', 'yes' ); 
		}
	}

	// Check if we need update Gigs(in case post is older than 24h)
	public function isOld(){
		$date = date("Y-m-d H:i:s");
		if( ! $last = get_option( 'gig_last_update' ) ){
			$this->updateDate( $date );
			return TRUE;
		}

		# FOR TEST
		#$last = "2016-11-10 20:14:28";

		if( ! $last = strtotime( $last ) ){
			$this->updateDate( $date );
			return TRUE;
		}

		$diff = round( ( strtotime( $date ) - $last ) / 3600 );
		if( $diff > 24 ){
			$this->updateDate( $date );
			return TRUE;
		}

		return FALSE;
	}

	// Save Gigs to WP database
	public function storeGigs( $gigs ){
		foreach( $gigs as $gig ){
			global $user_ID, $wpdb;

			$post_id = wp_insert_post( array
				(
					'post_title'		=> wp_strip_all_tags( $gig['title'] ),
					'post_type'			=> 'post',
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

			if ( ! add_post_meta( $post_id, 'venue', serialize( $gig['venue'] ), true ) ){
				update_post_meta ( $post_id, 'venue', serialize( $gig['venue'] ) ); }
		}
	}

	// Remove info about all Gigs from WP database
	public function removeOldGigs(){
		if( $posts = $this->getAllGigs() ){
			foreach( $posts as $post ){
				delete_post_meta( $post->ID, 'playDate' );
				delete_post_meta( $post->ID, 'playTime' );
				wp_delete_post( $post->ID, TRUE );
			}
		}
	}

	// Get info about all Gigs available
	public function getAllGigs(){
		$gigs = array();
		$args = array(
			'posts_per_page'	=> -1,
			'post_type'			=> array( 'post', 'gig' ), //$this->options['gig_post_type'],
			'orderby'			=> 'date',
			'sort_order'		=> 'desc',
			'post_status'		=> array( 'publish', 'pending', 'draft', 'auto-draft', 'future', 'private', 'inherit', 'trash' ),
		);
		
		$posts = get_posts( $args );

		// Append only posts with specified content
		foreach( $posts as $post )
			if( $post->post_content == self::SPEC )
				$gigs[] = $post;

		wp_reset_postdata();

		if( count( $gigs ) > 0 )
			return $gigs;

		return FALSE;
	}

	// If no Gigs availbale - display error(user should check settings - username).
	public function displayError(){
		echo '<p>No Gigs Available</p>';
	}

	// Display HTML formatted info about available Gigs
	public function displayGigs( $gigs ){
		asort( $gigs );
		foreach( $gigs as $gig ){
			$venue = get_post_meta( $gig->ID, 'venue', TRUE );
			$venue = unserialize(  $venue );
			$day = date( 'D', strtotime( get_post_meta( $gig->ID, 'playDate', TRUE ) ) );
			$date = date( 'd-m-Y', strtotime( get_post_meta( $gig->ID, 'playDate', TRUE ) ) );
?>
		<div class="commentlist gig-container">
				<div class="commentmetadata gig-date">
					<span><?php echo $day; ?></span>
					<small><?php echo $date; ?></small>
				</div>
				<div class="commentmetadata gig-title">
					<h3><?php echo $gig->post_title; ?></h3>
				</div>
				<div class="commentmetadata gig-location">
					<span><?php echo $venue['name']; ?></span>
					<small><?php echo $venue['city']; ?></small>
				</div>
		</div>
<?php
		}
		
		wp_reset_postdata();
	}

}

