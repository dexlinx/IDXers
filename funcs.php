<?php

//------------------------------------------------------------------------------
// UPDATING THE DATABASE WITH VOTES
//------------------------------------------------------------------------------

function voteFunction($votedSet){

	global $wpdb;
	//$wpdb->show_errors();
	//$wpdb->get_col_info('type', offset);
	
	$votedSet = $_POST["voted"];
	$postId = $_POST["postId"];
	$upVote = 1;

	
	
	//We only run this if the form is submitted
	//--------------------------------------------
	if($votedSet == "yes"){

		//Votes Exist?
		$findVotes = "SELECT * FROM wp_idxervotes WHERE postId =".$postId;
		$votesExist = $GLOBALS['wpdb']->get_results( $findVotes, OBJECT );


		if (empty($votesExist)) {
			//Post Has No Votes -> Insert
			$wpdb->insert( 'wp_idxervotes', array( 'postId' => $postId, 'numvotes' => $upVote ), array( '%d', '%d' ) );

			//Get the Vote ID
			$getVote = "SELECT id FROM wp_idxervotes WHERE postId =".$postId;
			$voteId = $GLOBALS['wpdb']->get_results( $getVote, OBJECT );
			//Update Voted Table
			$wpdb->insert( 'wp_idxervoteips', array( 'voteid' => $voteId[0]->id, 'userip' => protectVotes() ), array( '%d', '%d' ) );



		}else{

			//------------ A VOTE EXISTS; WE DO THE UPDATE WORK
			//---------------------------------------------------

			$userIp = protectVotes();
			
			//-- Did This User Vote?
			//-----------------------
			$votedQuery = "SELECT wp_idxervotes.postid,wp_idxervotes.id,wp_idxervoteips.voteid,wp_idxervoteips.userip
			FROM wp_idxervoteips
			INNER JOIN wp_idxervotes
			ON wp_idxervotes.id=wp_idxervoteips.voteid
			WHERE wp_idxervoteips.userip=".$userIp."
			AND wp_idxervotes.postid=".$postId;
				
			$alreadyVoted = $GLOBALS['wpdb']->get_results( $votedQuery, OBJECT );


			//-- If They Did Not Vote: Enter Vote and Capture IP Address
			//----------------------------------------------------------------
			if($alreadyVoted[0]->postid != $postId OR $alreadyVoted[0]->userip != protectVotes()){


				//Run The Update To Add Vote
				//--------------------------------
				$addVote = $votesExist[0]->numvotes+1;
				$wpdb->update( 'wp_idxervotes', array( 'postId' => $postId, 'numvotes' => $addVote ),array('postId' => $postId), array( '%d', '%d' ) );


				//Get the Vote ID
				$getVote = "SELECT id FROM wp_idxervotes WHERE postId =".$postId;
				$voteId = $GLOBALS['wpdb']->get_results( $getVote, OBJECT );


				//Update Voted Table
			
				$wpdb->insert( 'wp_idxervoteips', array( 'voteid' => $voteId[0]->id, 'userip' => $userIp ), array( '%d', '%d' ) );
				$wpdb->print_error();
							
				
			}else{
					
				//Get the Post Title
				$postTitleQuery = "SELECT post_title
 				FROM wp_posts
 				LEFT JOIN wp_idxervotes
 				ON wp_idxervotes.postid=wp_posts.ID
 				WHERE wp_idxervotes.postid=".$postId;
				$postTitle = $GLOBALS['wpdb']->get_results( $postTitleQuery, OBJECT );

				//Give Already Voted Message
				//echo "<center><b>You Already Voted On <font color=red>".$postTitle[0]->post_title."</font></b></center><p>";
			}
					
					


		}
	}
	}


//------------------------------------------------------------------------------
// VOTE FORM
//------------------------------------------------------------------------------
function voteForm(){
	
	
	include 'includes.php';
	$postId = get_the_ID();
	global $wpdb;
	
	//Run The Voting Function
	//--------------------------
	voteFunction($votedset);
	
	//-- Did This User Vote?
	//-----------------------
	$votedQueryPer = "SELECT wp_idxervotes.postid,wp_idxervotes.id,wp_idxervoteips.voteid,wp_idxervoteips.userip
			FROM wp_idxervoteips
			INNER JOIN wp_idxervotes
			ON wp_idxervotes.id=wp_idxervoteips.voteid
			WHERE wp_idxervoteips.userip=".protectVotes()."
			AND wp_idxervotes.postid=".$postId;
	
	$alreadyVotedPer = $GLOBALS['wpdb']->get_results( $votedQueryPer, OBJECT );	
	
	
	
	//-- Grab The Meta Data
	//----------------------
	$metaQueryData = "SELECT wp_postmeta.meta_key,wp_postmeta.meta_value
			FROM wp_postmeta
			WHERE wp_postmeta.post_id=".$postId."
			AND wp_postmeta.meta_key='_idxers_idxerstate' ";
	
	$metaQuery = $GLOBALS['wpdb']->get_results( $metaQueryData, OBJECT );
	
	$erstate = $metaQuery[0]->meta_value;
	
	//- If ER is dev or released don't show vote form
	if ($erstate==1 || $erstate==2){
		echo <<<EOL
<style>	
.voteform{display:none;}
.showVotes{display:none;}				
</style>
EOL;
	}
?>

<!-- This is the Vote Form -->
<!-- --------------------- -->

<div class="voteContainer">
<div class="showVotes"><?php showVotes(); ?></div>

<div class="voteform">
		
		
	<?php  echo $post->numvotes; ?>
	<form method="post" action="<?php echo $SITE_URL.$_SERVER['REQUEST_URI']; ?>">
	<input type="hidden" name="voted" value="yes">
	<input type="hidden" name="postId" value="<?php echo $postId;?>">

	<?php
	//-- If They Did Not Vote: Enter Vote and Capture IP Address
	//----------------------------------------------------------------
	if($alreadyVotedPer[0]->postid != $postId OR $alreadyVotedPer[0]->userip != protectVotes()){
	?>

		<input type="image" src="<?php echo $SITE_URL.$VOTE_IMG; ?>" alt="Submit">

		<?php 
		
	}else{
		echo "<img src=".$SITE_URL.$VOTED_IMG.">";
	}
	?>
	</form>
	</div>
	</div>
	<?php


	
	
}


//------------------------------------------------------------------------------
// Back to List Link
//------------------------------------------------------------------------------
function backToList(){
	
	global $wpdb;
	
	$single = is_single();
	//echo $single."<p>";
	
if ($single == 1){

	$fbonoffquery = "SELECT *
 		FROM wp_postmeta
 		WHERE wp_postmeta.meta_key='fbloginonoff'
 		";
	$isfbloginon = $wpdb->get_results($fbonoffquery, OBJECT);
	
	echo "<div class='backToList'><h3><a href=".$SITE_URL."/idealist/>Back to List</a></h3></center></div>";
	
	if ($isfbloginon[0]->meta_value == 1){
		
		fbFunc();	
		
	}
 }
}
//------------------------------------------------------------------------------
// SHOW NUMBER OF VOTES ON POSTS
//------------------------------------------------------------------------------
function showVotes(){
	
	$postId = get_the_ID();
	global $wpdb;
	
	$numVotesData = "SELECT *
			FROM wp_idxervotes
			WHERE postid=".$postId;
			
	
	$numVotesQuery = $GLOBALS['wpdb']->get_results( $numVotesData, OBJECT );
	$numVotes = $numVotesQuery[0]->numvotes;
	
	
	if(!empty($numVotes)){
		echo "<font color=#D26604><b>(Votes: ".$numVotes.")</b></font>";
	}else{
		echo "<font color=#D26604><b>(Votes: 0)</b></font>";
	}
	
	
	
}

//------------------------------------------------------------------------------
// GET USER IP AND/OR SET COOKIE (COOKIE CODE NOT YET IMPLEMENTED)
//------------------------------------------------------------------------------

function protectVotes(){

	
	//-- Get Cookie Value
	
	$voteCookieValue = $_COOKIE['vote_cookie'];
	$FbCookieValue = $_COOKIE['FbUserId'];
	
	if(isset($FbCookieValue)){
		$voteKey = $FbCookieValue;
	}else{
		$voteKey = $voteCookieValue;
	}
	
	
	
		return $voteKey;
	
	
}

?>
