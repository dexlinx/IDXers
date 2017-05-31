<?php

/*
 Plugin Name: IDX Enhancment Voting
 Plugin URI:  http://www.idxbroker.com
 Description: Plugin to provide ER voting
 Version:     1.0
 Author:      Louis (Tony) Rappa
 Author URI:  http://www.rappaenterprises.com
 License:     GPL2
 License URI: https://www.gnu.org/licenses/gpl-2.0.html
  */


//====================================================================================
// REQUIRED
//====================================================================================
require 'includes.php';
require 'fbFunc.php';
require 'funcs.php';

//====================================================================================
// DO STUFF ON INIT
//====================================================================================
function idxers_activate() {

			
//We need these plugins to run; so check for them first and only enable if they're active
		
if (!is_plugin_active( 'gravityforms/gravityforms.php')||!is_plugin_active( 'gravity-forms-custom-post-types/gfcptaddon.php')) {
				
				// Deactivate the plugin
				deactivate_plugins(__FILE__);
				
				// Throw an error in the wordpress admin console
				$error_message = __('This plugin requires <a href="http://www.gravityforms.com/">Gravity Forms</a> &amp; <a href="https://wordpress.org/plugins/gravity-forms-custom-post-types/">Custom Post Types</a> plugins to be active!', 'woocommerce');
				die($error_message);
				
}
	

	
//Create Some Categories for our ER Request Form
//------------------------------------------------------

$catControlPanel = array('cat_name' => 'Control Panel', 'category_description' => 'Enhancements to IDX Control Panel', 'category_nicename' => 'controlpanel', 'category_parent' => '');
$catWordPress = array('cat_name' => 'Word Press', 'category_description' => 'Enhancements to IDX WP Plugin', 'category_nicename' => 'wp', 'category_parent' => '');
$catPageTemplates = array('cat_name' => 'Page Templates', 'category_description' => 'Enhancements to Page Templates', 'category_nicename' => 'templates', 'category_parent' => '');
$catSEO = array('cat_name' => 'SEO', 'category_description' => 'Enhancements to SEO', 'category_nicename' => 'seo', 'category_parent' => '');
$catLeadManagement = array('cat_name' => 'Lead Management', 'category_description' => 'Enhancements to Lead Management', 'category_nicename' => 'leadmanagement', 'category_parent' => '');
$catOther = array('cat_name' => 'Other', 'category_description' => 'Enhancements to Other', 'category_nicename' => 'other', 'category_parent' => '');

// Create the category
$catControlPanel_id = wp_insert_category($catControlPanel);
$catWordPress_id = wp_insert_category($catWordPress);
$catPageTemplates_id = wp_insert_category($catPageTemplates);
$catSEO_id = wp_insert_category($catSEO);
$catLeadManagement_id = wp_insert_category($catLeadManagement);
$catOther_id = wp_insert_category($catOther);


//Add Tables, Votes, VoteIps
//------------------------------------------------------
global $wpdb;

$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE wp_idxervotes (
  id mediumint(9) NOT NULL AUTO_INCREMENT,
  postid mediumint(9) UNIQUE NOT NULL,
  numvotes mediumint(9)NOT NULL,
  UNIQUE KEY id (id)
) $charset_collate;";

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
dbDelta( $sql );

$sql = "CREATE TABLE wp_idxervoteips (
id mediumint(9) NOT NULL AUTO_INCREMENT,
voteid int(50) NOT NULL,
userip varchar(50)NOT NULL,
UNIQUE KEY id (id)
) $charset_collate;";

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
dbDelta( $sql );


// ----------- Turn FB Login ON / OFF -> On by Default
//---------------------------------------------------------------------------------
$wpdb->insert( 'wp_postmeta', array('meta_key' => 'fbloginonoff','meta_value' => '0' ), array( '%s', '%d' ) );



//---------------------------------------
//------------ END INIT HOOK ------------
}
//---- Go Ahead and Register That Now...
register_activation_hook( __FILE__, 'idxers_activate' );


//====================================================================================
// SOME ACTIONS
//====================================================================================


//-- Add my Styling
//-------------------
function load_vote_css() {
	$plugin_url = plugin_dir_url( __FILE__ );
	wp_enqueue_style( 'voteStyles', $plugin_url.'voteStyles.css' );
}
add_action( 'wp_enqueue_scripts', 'load_vote_css' );


//-- Add FB SDK
//------------------
function load_fbsdk() {
	//Load JS files in here
	$plugin_url = plugin_dir_url( __FILE__ );
	wp_register_script ('fbsdk', $plugin_url.'fbsdk.js');
	wp_enqueue_script('fbsdk');
}
add_action( 'wp_enqueue_scripts', 'load_fbsdk' );



// -- Adding a Cookie for voting security
//---------------------------------------
add_action('init', function() {
    if (!isset($_COOKIE['vote_cookie'])) {
    	$cookie_value = $_SERVER['REQUEST_TIME'];
        setcookie('vote_cookie', $cookie_value, strtotime('+365 day'),"/");
    }
});





// ----------- How we get the Vote Function on Individual Posts
//-------------------------------------------------------------

function myNewContent(){
global $wpdb;
	$myPostType = get_post_type(get_the_ID());

	if ($myPostType == 'idxers'){
			
  	$addContent = voteForm($postId = $post->ID);
        $addContentTwo = backToList();
  	
  	return $newContent;
  }
}
add_action('avada_after_content', 'myNewContent');
  
// ----------- This will set the default meta (Enhancement State) for all new posts
//---------------------------------------------------------------------------------
add_action( 'gform_post_submission', 'send_to_api' );
function send_to_api( $entry ) {
	global $wpdb;
		
    // prepare entry details for third party API
    $lead_id = $entry['post_id'];
    
    // add response value to entry meta
    $wpdb->insert( 'wp_postmeta', array( 'post_id' => $lead_id, 'meta_key' => '_idxers_idxerstate','meta_value' => '0' ), array( '%d', '%s','%s' ) );
}


//---- Add idxers Custom Post Type

add_action( 'init', 'create_post_type' );
function create_post_type() {
	register_post_type( 'idxers',
			array(
					'labels' => array(
							'name' => __( 'Enhancement Requests' ),
							'singular_name' => __( 'Enhancment Request' )
					),
					'public' => true,
					'has_archive' => true,
			)
			);
}



//----- Add Meta Box to Post Editor: ER State -------------------
//---------------------------------------------------------------

function idxers_add_meta_boxes( $post ){
	add_meta_box( 'idxers_meta_box', __( 'Enhancement State', 'idxers' ), 'idxers_build_meta_box', 'idxers', 'side', 'low' );
        add_meta_box( 'idxerRef_meta_box', __( 'ER Reference', 'idxers' ), 'idxerRef_build_meta_box', 'idxers', 'side', 'low' );
	add_meta_box( 'idxerNote_meta_box', __( 'ER Note', 'idxers' ), 'idxerNote_build_meta_box', 'idxers', 'side', 'low' );
}
add_action( 'add_meta_boxes_idxers', 'idxers_add_meta_boxes' );
/**
 * Build custom field meta box: ER State
 */
function idxers_build_meta_box( $post ){
	// make sure the form request comes from WordPress
	wp_nonce_field( basename( __FILE__ ), 'idxers_meta_box_nonce' );
	// retrieve the _idxers_idxerstate current value
	$current_idxerstate = get_post_meta( $post->ID, '_idxers_idxerstate', true );
	?>
	<div class='inside'>

		<p>
			<input type="radio" name="idxerstate" value="0" <?php checked( $current_idxerstate, '0' ); ?> /> Requested<br />
			<input type="radio" name="idxerstate" value="1" <?php checked( $current_idxerstate, '1' ); ?> /> In Dev<br />
			<input type="radio" name="idxerstate" value="2" <?php checked( $current_idxerstate, '2' ); ?> /> Released<br />
			<input type="radio" name="idxerstate" value="3" <?php checked( $current_idxerstate, '3' ); ?> /> Documented
		</p>

			</div>
	<?php
}

/**
 * Build custom field meta box: ER Reference
 */
function idxerRef_build_meta_box( $post ){
	// make sure the form request comes from WordPress
	wp_nonce_field( basename( __FILE__ ), 'idxerRef_meta_box_nonce' );
	// retrieve the _idxers_idxerRef current value
	$current_idxerRef = get_post_meta( $post->ID, '_idxers_idxerRef', true );
	?>
	<div class='inside'>
		<p>
		<input type="text" name="idxerRef" value="<?php echo $current_idxerRef; ?>" />
		</p>
	</div>
<?php
}

/**
 * Build custom field meta box: ER Note
 */
function idxerNote_build_meta_box( $post ){
	// make sure the form request comes from WordPress
	wp_nonce_field( basename( __FILE__ ), 'idxerNote_meta_box_nonce' );
	// retrieve the _idxers_idxerNote current value
	$current_idxerNote = get_post_meta( $post->ID, '_idxers_idxerNote', true );
	?>
	<div class='inside'>
		<p>
<textarea rows="10" cols="30" name="idxerNote"><?php echo $current_idxerNote; ?></textarea>
		</p>
	</div>
<?php
}

/**
 * Store custom field meta box data: ER State, ER Ref, ER Note
 *
 */
function idxers_save_meta_box_data( $post_id ){
	// verify meta box nonce
	if ( !isset( $_POST['idxers_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['idxers_meta_box_nonce'], basename( __FILE__ ) ) ){
		return;
	}
	// return if autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
		return;
	}
  // Check the user's permissions.
	if ( ! current_user_can( 'edit_post', $post_id ) ){
		return;
	}
	// store custom fields values
	// idxerstate string
	if ( isset( $_REQUEST['idxerstate'] ) ) {
		update_post_meta( $post_id, '_idxers_idxerstate', sanitize_text_field( $_POST['idxerstate'] ) );
	}
		if ( isset( $_REQUEST['idxerRef'] ) ) {
		update_post_meta( $post_id, '_idxers_idxerRef', sanitize_text_field( $_POST['idxerRef'] ) );
	}
		if ( isset( $_REQUEST['idxerRef'] ) ) {
		update_post_meta( $post_id, '_idxers_idxerNote', sanitize_text_field( $_POST['idxerNote'] ) );
	}
}
add_action( 'save_post_idxers', 'idxers_save_meta_box_data' );



//----------- ADDING ADMIN MENU WTH INSTRUCTIONS ---------------
//--------------------------------------------------------------

add_action( 'admin_menu', 'my_plugin_menu' );

function my_plugin_menu() {
	add_options_page( 'IDX ERS', 'IDX ERS', 'manage_options', 'idxers', 'my_plugin_options' );
}

function my_plugin_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}
	
	echo '<div class="wrap">';
	echo '<p><h1><u>Turn On FB Login Option (Off by Default):</u></h1></p>';
	

//Is FB ON or OFF?
//-------------------------------------------------
	global $wpdb;
		
	$fbonoffquery = "SELECT *
 		FROM wp_postmeta
 		WHERE wp_postmeta.meta_key='fbloginonoff'
 		";
$isfbloginon = $wpdb->get_results($fbonoffquery, OBJECT);
	
//-------- The Form To turn FB ON or OFF
//-------------------------------------------

echo "<form method=post action=http://learn.idxbroker.com/wp-admin/options-general.php?page=idxers>";

// If No Toggle, Get Status from DB
//-----------------------------------
if (!isset($_POST['fbonoff']))	{
	
	global $wpdb;
	
	$fbonoffquery = "SELECT *
 		FROM wp_postmeta
 		WHERE wp_postmeta.meta_key='fbloginonoff'
 		";
	$isfbloginon = $wpdb->get_results($fbonoffquery, OBJECT);
	
	if ($isfbloginon[0]->meta_value == 0){
		echo "<input type='checkbox' name='togglefb' value='on'>";
	}else{
		echo "<input type='checkbox' name='togglefb' value='on' checked='checked'>";
	}

}


//Check for Form Submit and Toggle

if (isset($_POST['fbonoff'])){

	if ($_POST['togglefb'] == 'on'){
	
	
	$wpdb->update( 'wp_postmeta', array( 'meta_key' => 'fbloginonoff', 'meta_value' => 1 ), array( 'meta_id' => $isfbloginon[0]->meta_id ), array( '%s', '%d' ), array( '%d' ) );
	echo "<input type='checkbox' name='togglefb' value='on' checked='checked'>";
	}else{
	$wpdb->update( 'wp_postmeta', array( 'meta_key' => 'fbloginonoff', 'meta_value' => 0 ), array( 'meta_id' => $isfbloginon[0]->meta_id ), array( '%s', '%d' ), array( '%d' ) );
	echo "<input type='checkbox' name='togglefb' value='on'>";
	}

}

echo <<<EOL
				
		Toggle FB login
						<input type=submit value=Update name=fbonoff>
						</form><p>
		
						
	<h2>Prior to turning on this feature you'll need to setup your App Domain on FaceBook. Here are the instructions:</h2><p>
		
							<ol>
		<li>Goto https://developers.facebook.com/apps/ and Click Add a New App.
		<li>Choose the Display Name, Contact Email, and Category (As desired)
		<li>Click Create App ID and follow the screens
		<li>Click Get Started next to Facebook Login
		<li>Click "platform settings" in the first bullet
		<li>Click Add Platform and choose Website
		<li>Add the website URL where the login will be used
		<li>Add the same URL to App Domains
		<li>Click Save Changes
		</ol>
		
		
NOTE: To make the App live be sure to visit https://developers.facebook.com/apps/. Select the App and click App Review. Change the "Make X public" to Yes.
		
EOL;
	
	
	
	echo '<p><h1><u>Setup the ER System:</u></h1></p>';
	echo <<<EOL
	
	<h2>Create Gravity Form</h2><p>
	
	<b>Field:</b> Post Fields -> Title (Name: Enhancement Title)<br>
	<b>-</b> Post Type -> Enhancement Requests<br>
	<b>-</b> Post Status -> Pending Review<p>
			
	<b>Field:</b> Post Fields -> Category (Name: Product Area)<p>
			
	<b>Field:</b> Post Fields -> Body (Name: Describe Enhancment)<p>
			
	<h2>Create Request Page</h2><p>	
	
	<ol>
	<li> Go to Pages -> Add New
	<li> Give the Page a Title
	<li> Add ShortCode: [gravityform id="6" title="false" description="false"] (NOTE: Change the ID to your form ID)
	<li> Publish the Page
	</ol>
			
	<h2>Create Post List</h2><p>
			
	<ol>
	<li>Go to Pages -> Add New
	<li>Give the Page a Title
	<li>Add ShortCode: [listIDXers]
	<li>Publish Page
	</ol>
			
	<h2>Update Menu</h2><p>
			
			<ol>
			<li>Go to Appearance -> Menus
			<li>Add the two pages to your menu
			<li>Save Menu
			<li>Eat a Candy
			</ol>
			
			
	
	
EOL;
	
	echo '</div>';
}
//====================================================================================
// SOME SHORTCODE FUN
//====================================================================================

//Add ShortCode to List Posts - [listIDXers]
//---------------------------------------------
function listIDXers_func( $atts ){
	
//====================================================================================
// THE MAIN LOOP
//====================================================================================

//------------ Switch Between ER States

echo <<<EOL

<div class="statusLinks">
<form action="$SITE_URL.$PAGE_PATH" method="post">
<input type="hidden" name="erstate" value="0">
<button type="submit" name="erstatebtn" value="erstatebtn" class="btn-link">Requests</button>
</form>
	
	
<form action="$SITE_URL.$PAGE_PATH" method="post">
<input type="hidden" name="erstate" value="1">
<button type="submit" name="erstatebtn" value="erstatebtn" class="btn-link">In Development</button>
</form>
		
<form action="$SITE_URL.$PAGE_PATH" method="post">
<input type="hidden" name="erstate" value="2">
<button type="submit" name="erstatebtn" value="erstatebtn" class="btn-link">Released</button>
</form>

<form action="$SITE_URL.$PAGE_PATH" method="post">
<input type="hidden" name="erstate" value="3">
<button type="submit" name="erstatebtn" value="erstatebtn" class="btn-link">Documented</button>
</form>

</div>
EOL;

if (isset($_POST["erstate"])){
	
	$erstate = $_POST["erstate"];
}else{
	$erstate=0;
}

//------ FAEBOOK FUNCTION FOR POST LIST
global $wpdb;

$fbonoffquery = "SELECT *
 		FROM wp_postmeta
 		WHERE wp_postmeta.meta_key='fbloginonoff'
 		";
$isfbloginon = $wpdb->get_results($fbonoffquery, OBJECT);

if ($isfbloginon[0]->meta_value == 1){

	if ($_POST['erstate'] == 0){
		fbFunc();
	}
	
}



//------ MY COMBINED QUERY FOR THE MAIN LOOP (POSTS AND VOTES)
global $wpdb;
 $querystr = "SELECT *
 		FROM wp_posts
 		LEFT JOIN wp_idxervotes ON wp_idxervotes.postid=wp_posts.ID
		LEFT JOIN wp_postmeta ON wp_postmeta.post_id=wp_posts.ID
 		WHERE wp_posts.post_status='publish'
 		AND wp_posts.post_type='idxers'
		AND wp_postmeta.meta_key='_idxers_idxerstate'
		AND wp_postmeta.meta_value='".$erstate."'
 		ORDER BY wp_idxervotes.numvotes DESC
 		";

 
 
 $pageposts = $wpdb->get_results($querystr, OBJECT);
 

//------------------------------------------------------------
?>
<div class="post" id="post-<?php the_ID(); ?>">

<?php
 if ($pageposts): ?>
  <?php global $post; ?>
  
  <!-- --------------- LOOP ------------------------>
<?php foreach ($pageposts as $post): ?>
<?php setup_postdata($post); ?>
  

<div class="listContainer">
<div class="listItemContainer">
<h2><a href="<?php the_permalink() ?>" rel="bookmark" title="Permanent Link to <?php the_title_attribute(); ?>">
<?php the_title(); ?></a></h2>
<!--<div class="entry">-->
<?php the_excerpt(); ?>
</div>

<?php voteForm($post); ?>
</div>


     
     
  <?php endforeach; ?>



  <?php else : ?>
     <h2 class="center">Enhancement Category Empty</h2>
     <p class="center">There are no enhancements in this section.</p>
     <?php //include (TEMPLATEPATH . "/searchform.php"); ?>

 
  <?php endif; ?>
 
</div>
 
<?php 
}
add_shortcode( 'listIDXers', 'listIDXers_func' );
?>
