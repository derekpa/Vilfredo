<?php
include('header.php');

//set_log(__FILE__." called....");

//set_log($_POST);


//print_array($_POST);
//exit;

if (isset($_POST['proposal']) and isset($_POST['prev_proposal']))
{
	foreach ($_POST['proposal'] as $key => $value)
	{
		if ($_POST['proposal'][$key] != $_POST['prev_proposal'][$key])
		{
			set_log("Vote for $key changed...");
			$_SESSION["updatemap"] = true;
			break;
		}
	}
}

$question = fetchValidIntValFromPostWithKey('question');
if ($question === false)
{
	header("Location: error_page.php");
	exit;
}

//set_log("Question $question");

if (IsQuestionWriting($question))
{
	//set_message("user", "Sorry, question $question now in writing stage.");
	//header("Location: messagepage.php?q=$question");
	header("Location: viewquestion.php?q=$question");
	exit;
}

// User is anonymous if anon checkbox has been clicked (is defined)
$is_anon = isset($_POST['anon']);

$generation = (int)$_POST['generation'];

$userid=isloggedin();

if (!$userid)
{
	DoLogin();
}

if ($is_anon)
{
	//set_log("Form submitted anonymously");
	// userid should be false
	if ($userid)
	{
		set_log("User $userid submitted anonymously whilst logged in!");
	}
}

$is_subscribe = isset($_POST['subscribe']);

if ($userid) {
	$sql = "SELECT * FROM updates WHERE question = ".$question." AND user = ".$userid." LIMIT 1 ";
	$response = mysql_query($sql);
	$row = mysql_fetch_array($response);
	if ($row)
	{
		if(!$is_subscribe)
		{
			$sql = "DELETE FROM updates WHERE updates.question = " . $question . " AND updates.user = " . $userid . "  ";
			if (!mysql_query($sql)) error("Database update failed");				
		}
	}
	else
	{
		if($is_subscribe)
		{
			$how="asap";
			$sql = 'INSERT INTO `updates` (`user`, `question`, `how`) VALUES (\'' . $userid . '\', \'' . $question . '\', \'' . $how . '\');';
			if (!mysql_query($sql)) error("Database update failed");				
		}
	}
}

$roundid;
$sql2 = "SELECT roundid FROM questions WHERE id = ".$question." LIMIT 1 ";
$response2 = mysql_query($sql2);
while ($row2 = mysql_fetch_array($response2))
{		
	$roundid =	$row2[0];
}

DeleteGraph($question,$roundid);

$nEndorsers=CountEndorsers($question,$roundid);
$allproposals = getCurrentProposalIDs($question, $roundid);

$origids = getOriginalIDs($allproposals);
//set_log('$origids');
//set_log($origids);

if ($is_anon)
{
	//$userid = getAnonymousUserForVoting($allproposals);
	$userid = getAnonymousUser($question);
	
	//$wait = getDelayForRemoteIP();
	//set_log("Delay for this user should be $wait seconds");
	//logUser($userid);
}

if (!$userid)
{
	set_log("Error: Could not create anonymous user!");
}

$currentuserendorsements = getUserEndorsedFromList($userid, $allproposals);
$user_comment = (isset($_POST['user_comment'])) ? $_POST['user_comment'] : array();
$prev_proposals = (isset($_POST['prev_proposal'])) ? $_POST['prev_proposal'] : array();

$endorsedproposals = (isset($_POST['proposal'])) ? $_POST['proposal'] : array();
$prev_userlikes = getUserCommentLikesFromProposals($userid, $allproposals);
$select_comment = (isset($_POST['select_comment'])) ? $_POST['select_comment'] : array();

$replies = (isset($_POST['replies'])) ? $_POST['replies'] : array();
$user_comment_type = (isset($_POST['user_comment_type'])) ? $_POST['user_comment_type'] : array();

set_log($user_comment_type);

$comment_form_displayed  = (isset($_POST['comment_form_displayed'])) ? $_POST['comment_form_displayed'] : array();

//$user_likes = get_object_vars(json_decode($_POST['user_likes']));
//print_array('$user_likes');
//print_array($user_likes);

/*
print_array($prev_userlikes);
$delete_likes = compare_userlikes($prev_userlikes, $select_comment);
printbr('delete_likes');
print_array($delete_likes);
foreach ($comment_form_displayed as $pid)
{
	if (!isset($select_comment[$pid]))
	{
		$select_comment[$pid] = array();
	}
}
$delete_likes = compare_userlikes($prev_userlikes, $select_comment);
printbr('delete_likes adjusted');
print_array($delete_likes);
*/

// Add and remove comment support
//

//$add_likes = compare_userlikes($select_comment, $prev_userlikes);

//$delete_likes = compare_userlikes($prev_userlikes, $select_comment);

//if ($select_comment != $prev_userlikes)
//{
	
if (!empty($comment_form_displayed))	
{
	if (!empty($select_comment))
	{
		addUserCommentLikes($userid, $select_comment);
	}
	
	foreach ($comment_form_displayed as $pid)
	{
		if (!isset($select_comment[$pid]))
		{
			$select_comment[$pid] = array();
		}
	}
	
	$delete_likes = compare_userlikes($prev_userlikes, $select_comment);
	set_log('delete_likes adjusted for form display');
	set_log($delete_likes);
	
	if (!empty($delete_likes))
	{
		$deleted_commentids = getCommentIDsFromUserCommentsList($delete_likes);
		if (!empty($deleted_commentids))
		{	
			set_log('Delete likes for comments (deleted_commentids)');
			set_log($deleted_commentids);
			removeUserCommentLikes($userid, $deleted_commentids);
			deleteUnsupportedComments($deleted_commentids);
		}
	}
}

$voting_types = array('support', 'dislike', 'confused');


	
// Add new comments and add support, or add support to existing identical comments
//
if (!empty($user_comment) && !empty($user_comment_type))
{
	$comment_ids = array();
	foreach ($user_comment as $p => $comment)
	{
		if ($comment == '')
		{
			continue;
		}
		if (!isset($user_comment_type[$p]) || !in_array($user_comment_type[$p], $voting_types))
		{
			log_error(__FILE__." New comments must have associated types");
			continue;
		}
		
		$commentid = commentExists($p, $generation, $user_comment_type[$p], $comment);
		//set_log("ID of comment search = $commentid");
		if (!$commentid)
		{
			set_log("No duplicate for new comment found for prop ID $p and origid {$origids[$p]}");
			set_log("Adding new comment {$user_comment[$p]}");
			$commentid = addComment($userid, $p, $user_comment_type[$p], $generation, $comment, $origids[$p]); // addorigid
		}
		$comment_ids[$p][] = $commentid;
	}
	if (!empty($comment_ids))
	{
		addUserCommentLikes($userid, $comment_ids);
	}
}

// Add replies
//
if (!empty($replies))
{
	$comment_ids = array();
	foreach ($replies as $p => $comment_replies)
	{
		foreach ($comment_replies as $c => $reply)
		{
			if ($reply == '')
			{
				continue;
			}
			$commentid = addComment($userid, $p, 'answer', $generation, $reply, $origids[$p], $c);
			$comment_ids[$p][] = $commentid;
		}
	}
	if (!empty($comment_ids))
	{
		set_log('Adding support for replies');
		set_log($comment_ids);
		addUserCommentLikes($userid, $comment_ids);
	}
}


// Set endorse, oppoed and comments
//
foreach ($allproposals as $p)
{
	set_log("Processing proposal $p....");
	/*
	if 
	( 
		( isset($prev_proposals[$p]) && ($endorsedproposals[$p] != $prev_proposals[$p]) ) ||
		( isset($user_comment[$p]) && empty($user_comment[$p]) == false ) ||
		( isset($prev_commentids[$p]) && isset($select_comment[$p]) && $prev_commentids[$p] != $select_comment[$p] ) ||
		( isset($select_comment[$p]) )
	)*/
	if 
	( 
		( isset($prev_proposals[$p]) && ($endorsedproposals[$p] != $prev_proposals[$p]) ) ||
		( !isset($prev_proposals[$p]) && isset($endorsedproposals[$p]) )
	)
	{
		
		// 
		// User endorses
		//
		//set_log("prev_proposal is " . $prev_proposals[$p]);
		
		if ($endorsedproposals[$p] == "1" && !in_array($p, $currentuserendorsements))
		{
			//set_log("Adding endorsement for proposal $p...");
			addEndorsement($userid, $p);
			
			if ($prev_proposals[$p] == '2' || $prev_proposals[$p] == '3')
			{	// Delete previous oppose entry
				//set_log("Delete previous oppose entry for proposal $p...");
				deleteUserOppose($userid, $p, $generation);
			}
		}
		// User Opposes
		elseif ($endorsedproposals[$p] == "2" || $endorsedproposals[$p] == "3")
		{
			//set_log("User added or changed Oppose");
			if (in_array($p, $currentuserendorsements))
			{
				deleteEndorsement($userid, $p);
			}
		
			// Set oppose type
			$type;
			if ($endorsedproposals[$p] == "2")
			{
				$type = 'dislike';
			}
			else
			{
				$type = 'confused';
			}
			$type_change = $type != $prev_proposals[$p];
		
			// Add new oppose entry or update type and commentid of existing one
			setUserOppose($userid, $p, $type, $generation, $origids[$p]); // addorigid
		}
	}
	else
	{
		if ($endorsedproposals[$p] == "1")
		{
			set_log("No change for endorsed proposal $p - continue...");
		}
		else
		{
			set_log("No change for opposed proposal $p - continue...");
		}
		continue;
	}	
}


/*
$hasvoted = (int)$_POST['hasvoted'];
if (!$hasvoted)
{
	setuservoted($userid, $question, $roundid);
}	*/
	
$NEWnEndorsers=CountEndorsers($question,$roundid);
//DeleteGraph($question,$roundid);
if(	$nEndorsers < $NEWnEndorsers)
{
	AwareAuthorOfNewEndorsement($question);
}

$room = GetRoom($question);
$urlquery = CreateQuestionURL($question, $room);

if ($is_anon)
{
	$urlquery = "?anon=$userid&query=viewquestion.php".$urlquery;
	header("Location: anonvotesfeedback.php".$urlquery);
}
else
{
	header("Location: viewquestion.php".$urlquery."#Voted");
}
?>