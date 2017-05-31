<?php
//This is not configured properly; I've decided to set a cookie instead; but will keep this if I decide to do a DB entry

global $wpdb;

// prepare entry details for third party API
$fbId = $_GET["fbId"];

// add response value to entry meta
$wpdb->insert( 'wp_idxervoteips', array( 'voteid' => $voteID[0]->id, 'userip' => $fbId ), array( '%d', '%d' ) );
?>
