<?php

$headcommands='
<link rel="stylesheet" href="js/jquery/tooltip/jquery.tooltip.css" />

<script src="js/jquery/jquery.js" type="text/javascript"></script>
<script src="js/jquery/jquery.bgiframe.js" type="text/javascript"></script>
<script src="js/jquery/jquery.dimensions.js" type="text/javascript"></script>
<script src="js/jquery/tooltip/jquery.tooltip.js" type="text/javascript"></script>
<script src="js/jquery/tooltip/chili-1.7.pack.js" type="text/javascript"></script>

<title>VgtA: Questions</title>
';



include('header.php');
#$userid=isloggedin();
//if ($userid)
//{
?>
<script type="text/javascript">
$(function() {
$(".foottip a").tooltip({
	bodyHandler: function() {
		return $($(this).attr("tooltip")).html();
	},
	showURL: false
});

});
</script>
<?php
// Prepare room query param if set
$room_param = CreateNewQuestionURL();
?>

<div class="centerbox">
<div class="newquestionbox">


<?php
/*
$current_room = GetParamFromQuery(QUERY_KEY_ROOM);
echo '<h1>Current Room: ';
if  ($current_room)
	echo $current_room;
else
	echo "Common";
echo "</h1>";
*/
?>


<h3><a href="newquestion.php<?php echo $room_param?>">Ask a New Question</a></h3>
</div>
<?php
	
	// **
	// Set question filter
	// **
	$question_filter = GetQuestionFilterOpen($userid);

	
	// *****
	// NEW QUESTIONS
	// ****

	$sql = "SELECT questions.id, questions.title, questions.roundid, questions.phase, users.username, users.id, questions.question, questions.room FROM questions, users WHERE questions.phase = 0 AND questions.roundid = 1 AND users.id = questions.usercreatorid " . $question_filter . " ORDER BY questions.id DESC LIMIT 50";
	$response = mysql_query($sql);
	$newquestionswritten=0;
	while ($row = mysql_fetch_array($response))
	{
		$phase=$row[3];
		$generation=$row[2];
		$thatusername=$row[4];
		$thatuserid=$row[5];
		$proposalsid=$row[6];
		$questionid=$row[0];
		$room=$row[7];

		$urlquery = CreateQuestionURL($questionid, $room);

		$nactualproposals = CountProposals($questionid,$generation);

		// No proposals means the question is new
		if($nactualproposals == 0)
		{
			if (!$newquestionswritten)
			{
				echo '<div class="newquestionbox">';
				echo "<h2>New Questions</h2><p>";
			}

			$newquestionswritten=$newquestionswritten+1;
			echo '<p>';

			if ($thatuserid==$userid)
			{
				echo '<b>';
			}
			
			echo '<fieldset class="foottip">';
			echo '<a href="http://www.flickr.com/photos/found_drama/1023671528/"><img src="images/germinating.jpg" title="New Question" height=42 ></a> <a title="This is a new question. Be the first to suggest an answer!" href="viewquestion.php' . $urlquery . '" tooltip="#footnote' . $row[0] . '" >' . $row[1] . '</a> ';

			$UserString=WriteUserVsReader($thatuserid,$userid);

			echo RenderQIconInfo($UserString, $room);
			echo '</td></tr></table>';

			echo '<div class="invisible" id="footnote' . $row[0] . '">This is a new question. Be the first to suggest an answer!<br/>QUESTION: '.$row[6].'.</div>';
			echo '</fieldset>';
			if ($thatuserid==$userid)
			{
				echo '</b>';
			}
			echo '</p>';
		}
	}
	if ($newquestionswritten)
	{
		echo '</div>';
	}

	echo '</div>';
	
	
	// *****
	// PROPOSING
	// ****


	echo '<div class="leftfloatbox">';

	echo '<div class="proposingbox">';

	echo '<h2> <img src="images/writing.jpg" height=48> Propose an Answer to a Question</h2><p>';

	$sql = "SELECT questions.id, questions.title, questions.roundid, questions.phase, 
	users.username, users.id, MAX(proposals.id) AS latest, 
	questions.question, questions.minimumtime, questions.maximumtime, questions.room 
	FROM questions, users, proposals 
	WHERE questions.phase = 0 AND users.id = questions.usercreatorid 
	AND proposals.experimentid = questions.id " . $question_filter . " 
	GROUP BY questions.id ORDER BY latest DESC";
	$response = mysql_query($sql);
	
	while ($row = mysql_fetch_array($response))
	{
		echo '<p>';
		$phase=$row[3];
		$generation=$row[2];
		$thatusername=$row[4];
		$thatuserid=$row[5];
		$proposalsid=$row[6];
		$questionid=$row[0];
		$minimumtime=$row[8];
		$maximumtime=$row[9];
		$room=$row[10];

		$urlquery = CreateQuestionURL($questionid, $room);

		$nrecentproposals = CountProposals($questionid,$generation);
		$nrecentendorsers = CountEndorsers($questionid,$generation-1);
		$nAuthorsNewProposals = count(AuthorsOfNewProposals($questionid,$generation));
		
		if($nAuthorsNewProposals == 0)
		{
			$pastgeneration = $generation -1;
			$sql2 = "SELECT id, source FROM proposals 
			WHERE experimentid = ".$questionid." AND roundid = ".$pastgeneration." 
			AND dominatedby = 0";
			$response2 = mysql_query($sql2);
			$row2 = mysql_fetch_array($response2);

			if($row2)
			{
				if ($nrecentendorsers == CountEndorsersToAProposal($row2[0]))
				{
					continue;
				}
			}
		}

		if ($thatuserid==$userid)
		{
			echo '<b>';
		}

		//---
		$nrecentproposals=CountProposals($questionid,$generation-1);
		$nEndorsersRecentProposals=CountEndorsers($questionid,$generation-1);
		$nrecentparetofront=count(ParetoFront($questionid,$generation-1));
		$nAuthorsNewProposals=count(AuthorsOfNewProposals($questionid,$generation));
		$nAuthorsRecentProposals=count(AuthorsOfNewProposals($questionid,$generation-1));
		$nactualproposals=CountProposals($questionid,$generation);

		if ($nrecentparetofront > 0)
		{
			$newproposalswritten=$nactualproposals-$nrecentparetofront;

			echo '<fieldset class="foottip">';
			echo '<table border=0><tr><td>';
			
			if ($userid)
			{
				if (HasThisUserProposedSomething($questionid,$generation,$userid))
				{
					echo '<img src="images/tick.jpg" height=20 title="you have already proposed something this generation, but you can propose more"> ';
				}
				else
				{
					echo '<img src="images/tick_empty.png" height=20 title="you have not yes proposed anything new this generation"> ';
				}
			}
			else
			{
				echo '&nbsp;';
			}

			echo '</td><td><a href="http://www.flickr.com/photos/jphilipson/2100627902/"><img src="images/tree.jpg" title="Generation '.$generation.'" height=42 ></a></td><td><a href="viewquestion.php' . $urlquery . '"  tooltip="#footnote' . $row[0] . '" >' . $row[1] . '</a><br /> ';

				$UserString=WriteUserVsReader($thatuserid,$userid);

				echo RenderQIconInfo($UserString, $room);

				$ReadyToGo= IsQuestionReadyToBeMovedOn($questionid,$phase,$generation);
				if($ReadyToGo)
				{
					echo '<td><img src="images/clock.jpg" title="This question is ready to be moved on. The questionair can do it at any time. If you still have not proposed your possible answers, please do so as soon as possible" height=42></td>';
				}
				$ReadyToAutoGo= IsQuestionReadyToAutoMoveOn($questionid,$phase,$generation);
				if($ReadyToAutoGo)
				{
					echo '<td><img src="images/sveglia.gif" title="This question will soon be automatically moved on. If you still have not proposed your possible answers, please do so as soon as possible" height=42></td>';
				}

				echo '</td></tr></table>';

			echo '<div class="invisible" id="footnote' . $row[0] . '">' . $row[7] . '.<br/><strong>Generation</strong>='.$generation.';<br/>Recently '.$nAuthorsNewProposals.' proposers, proposed '.$newproposalswritten.' solutions.<br/>There are also '.$nrecentparetofront.' inherited from the previous generation.';
			$mintime=WriteTime($minimumtime);
			$maxtime=WriteTime($maximumtime);
			echo '<br/>Minumum time: '.$mintime.';<br/> Maximum time: '.$maxtime;

			echo '</div></fieldset>';

		}
		else
		{
			echo '<fieldset class="foottip">';



			echo '<table border=0><tr><td>';
			if ($userid)
			{
				if (HasThisUserProposedSomething($questionid,$generation,$userid))
				{
					echo '<img src="images/tick.jpg" height=20 title="you have already proposed something this generation, but you can propose more"> ';
				}
				else
				{
					echo '<img src="images/tick_empty.png" height=20 title="you have not yes proposed anything new this generation"> ';
				}
			}
			else
			{
				echo '&nbsp;';
			}

			echo '</td><td><a href="http://www.flickr.com/photos/found_drama/1023671528/"><img src="images/germinating.jpg" title="New Question" height=42 ></a></td><td><a href="viewquestion.php' . $urlquery . '" tooltip="#footnote' . $row[0] . '"  >' . $row[1] . '</a> <br />';

			$UserString=WriteUserVsReader($thatuserid,$userid);

			echo RenderQIconInfo($UserString, $room);
			$ReadyToGo= IsQuestionReadyToBeMovedOn($questionid,$phase,$generation);
			if($ReadyToGo)
			{
				echo '<td><img src="images/clock.jpg" title="This question is ready to be moved on. The questionair can do it at any time. If you still have not proposed your possible answers, please do so as soon as possible" height=42></td>';
			}
			$ReadyToAutoGo= IsQuestionReadyToAutoMoveOn($questionid,$phase,$generation);
			if($ReadyToAutoGo)
			{
				echo '<td><img src="images/sveglia.gif" title="This question will soon be automatically moved on. If you still have not proposed your possible answers, please do so as soon as possible" height=42></td>';
			}

			echo '</td></tr></table>';

			echo '<div class="invisible" id="footnote' . $row[0] . '">' . $row[7] . '.<br/><strong>Generation</strong>='.$generation.';<br/>Recently '.$nAuthorsNewProposals.' proposers, proposed '.$nactualproposals.' solutions.';
			$mintime=WriteTime($minimumtime);
			$maxtime=WriteTime($maximumtime);
			echo '<br/>Minumum time: '.$mintime.';<br/> Maximum time: '.$maxtime;

			echo '<br/></div></fieldset>';

		}
		//---
		if ($thatuserid==$userid)
		{
			echo '</b>';
		}

		echo '</p>';
	}
	echo '</div>';
	echo '</div>';
	
	
	// *****
	// ENDORSING
	// ****
		echo '<div class="rightfloatbox">';
		echo '<div class="endorsingbox">';
		
		echo '<h2> <img src="images/endorsing.jpg" height=48> Vote all the Answers you Agree on</h2><p>';
	
		$sql = "SELECT questions.id, questions.title, questions.roundid, questions.phase, 
		users.username, users.id, questions.minimumtime, questions.maximumtime, questions.room  
		FROM questions, users 
		WHERE questions.phase = 1 AND users.id = questions.usercreatorid " . $question_filter . " 
		ORDER BY questions.lastmoveon DESC, questions.roundid DESC, 
		questions.phase DESC, questions.id DESC ";
		$response = mysql_query($sql);
		
		while ($row = mysql_fetch_array($response))
		{
			echo '<p>';
			$phase=$row[3];
			$generation=$row[2];
			$thatusername=$row[4];
			$thatuserid=$row[5];
			$questionid=$row[0];
			$minimumtime=$row[6];
			$maximumtime=$row[7];
			$room=$row[8];
	
			$urlquery = CreateQuestionURL($questionid, $room);
	
			$nrecentproposals=CountProposals($questionid,$generation);
			$nrecentendorsers=CountEndorsers($questionid,$generation);
			$nAuthorsNewProposals=count(AuthorsOfNewProposals($questionid,$generation));
			$nrecentparetofront=count(ParetoFront($questionid,$generation-1));
			$newproposalswritten=$nrecentproposals-$nrecentparetofront;
	
			echo '<fieldset class="foottip">';
	
			echo '<table border=0><tr><td>';
			
			if ($userid)
			{
				if (HasThisUserEndorsedSomething($questionid,$generation,$userid))
				{
					echo '<img src="images/tick.jpg" height=20 title="you have already expressed your endorsements over here"> ';
				}
				else
				{
					echo '<img src="images/tick_empty.png" height=20 title="you have not expressed any endorsements over here"> ';
				}
			}
			else
			{
				echo '&nbsp;';
			}
	
	
			echo '</td><td><a href="http://www.flickr.com/photos/johnfahertyphotography/2675723448/"><img src="images/flowers.jpg" title="Chose the ones you like" height=42 ></a></td><td> <a href="viewquestion.php' . $urlquery. '" tooltip="#footnote' . $row[0] . '"   >' . $row[1] . '</a> <br />';
	
			$UserString=WriteUserVsReader($thatuserid,$userid);
	
			echo RenderQIconInfo($UserString, $room);
	
			$ReadyToGo= IsQuestionReadyToBeMovedOn($questionid,$phase,$generation);
			if($ReadyToGo)
			{
				echo '<td><img src="images/clock.jpg" title="This question is ready to be moved on. The questionair can do it at any time. If you still have not endorsed the answers you agree with, please do so as soon as possible" height=42></td>';
			}
	
			$ReadyToAutoGo= IsQuestionReadyToAutoMoveOn($questionid,$phase,$generation);
			if($ReadyToAutoGo)
			{
				echo '<td><img src="images/sveglia.gif" title="This question will soon be automatically moved on. If you still have not endorsed the answers you agree with, please do so as soon as possible" height=42></td>';
			}
	
			echo '</td></tr></table>';
	
			echo '<div class="invisible" id="footnote' . $row[0] . '">';
			echo '<ol>';
			$sql3 = "SELECT id, blurb  FROM proposals WHERE experimentid = ".$row[0]." and roundid = ".$generation." ";
			$response3 = mysql_query($sql3);
	
			while ($row3 = mysql_fetch_array($response3))
			{
				$answerid=$row3[0];
				$answertext=$row3[1];
				echo '<li>'.$answertext.'</li>';
			}
			echo '</ol>';
			echo '<strong>Generation</strong>='.$generation.';<br/>Recently '.$nrecentendorsers.' human beings voted on '.$nrecentproposals.' possible solutions,<br/>'.$newproposalswritten.' produced by '.$nAuthorsNewProposals.' human being(s)<br/>
			and '.$nrecentparetofront.' inherited from the previous generation.';
			$mintime=WriteTime($minimumtime);
			$maxtime=WriteTime($maximumtime);
			echo '<br/>Minumum time: '.$mintime.';<br/> Maximum time: '.$maxtime;
			echo '</div>';
	
			echo '</fieldset>';
	
	
			echo '</p>';
	
		}

		echo '</div>';
		echo '</div>';
	// ENDORSING END


	// *****
	// REACHED CONCENSUS
	// *****

	echo '<div class="centerbox">';
	echo '<div class="solvedbox">';
	echo '<h2><img src="images/manyhands.jpg" height=48>Questions that reached unanimity</h2><p>';
	echo "<p>If you do not agree with tha answers you can reopen them, by adding other proposals.</p>";

	$sql = "SELECT questions.id, questions.title, questions.roundid, 
		questions.phase, users.username, users.id, questions.room  
		FROM questions, users 
		WHERE questions.phase = 0 AND users.id = questions.usercreatorid 
		$question_filter
		ORDER BY  questions.lastmoveon DESC, questions.id DESC";
	$response = mysql_query($sql);
	$printed = false;
	if (!$printed) 
	{
		//set_log('$sql = ' . $sql);
		$printed = true;
	}
	while ($row = mysql_fetch_array($response))
	{
		echo '<p>';
		$phase=$row[3];
		$generation=$row[2];
		$thatusername=$row[4];
		$thatuserid=$row[5];
		$questionid=$row[0];
		$room=$row[6];

		$urlquery = CreateQuestionURL($questionid, $room);

		$nrecentproposals=CountProposals($questionid,$generation);
		$nrecentendorsers=CountEndorsers($questionid,$generation-1);
		$nAuthorsNewProposals=count(AuthorsOfNewProposals($questionid,$generation));
		$nrecentparetofront=count(ParetoFront($questionid,$generation-1));
		
		// If there are no new proposals, just the pareto from previous generation
		if($nAuthorsNewProposals == 0)
		{
			// Select last gen pareto
			$pastgeneration = $generation - 1;
			$sql2 = "SELECT id, source FROM proposals 
			WHERE experimentid = ".$questionid." AND roundid = ".$pastgeneration." 
			AND dominatedby = 0";
			$response2 = mysql_query($sql2);
			$row2= mysql_fetch_array($response2);

			if($row2)
			{
				if ($nrecentendorsers == CountEndorsersToAProposal($row2['id']))
				{
					echo '<fieldset class="foottip">';
					$sql3 = "SELECT id, blurb FROM proposals 
					WHERE experimentid = ".$questionid." and roundid = ".$generation." ";
					$response3 = mysql_query($sql3);

					if (mysql_num_rows($response3) > 1)
					{
						echo '<table border=0 class="unanimity"><tr><td><a href="http://www.flickr.com/photos/lencioni/2223801603/"><img src="images/fruits.jpg" title="Everybody Agreed on More than One Answer" height=42 ></a></td><td class="unanimity_info"><a href="viewquestion.php' . $urlquery . '" tooltip="#footnote' . $row[0] . '">' . $row[1] . '</a> ';
						$UserString=WriteUserVsReader($thatuserid,$userid);

						echo RenderQIconInfo($UserString, $room) . '<br />';
					}
					else
					{
						echo '<table border=0 class="unanimity"><tr><td><a href="http://www.flickr.com/photos/don-piefcone/395175227/"><img src="images/apple.jpg" title="Generated Answer" height=42 ></a></td><td class="unanimity_info"><a href="viewquestion.php' . $urlquery . '" tooltip="#footnote' . $row[0] . '">' . $row[1] . '</a> ';

						$UserString=WriteUserVsReader($thatuserid,$userid);
						echo RenderQIconInfo($UserString, $room) . '<br />';
					}
					
					$endorsers=Endorsers($questionid,$pastgeneration);
					echo "<b>Agreement found between</b>: ";
					
					foreach ($endorsers as $e)
					{
						$UserString=WriteUserVsReader($e,$userid);
						echo $UserString . '&nbsp;&nbsp;';
					}

					echo '</td></tr></table>';
					echo '<div class="invisible" id="footnote' . $row[0] . '">';
					echo '<ol>';

					while ($row3 = mysql_fetch_array($response3))
					{
						$answerid=$row3[0];
						$answertext=$row3[1];
						echo '<li>'.$answertext.'</li>';
#						echo 'ANSWER: '.$answertext.'      ';
					}
					echo '</ol>';
					echo '</div>';
					echo '</fieldset>';
				}
			}
		}
		echo '</p>';
	}
	 echo '</div>';
	 echo '</div>';
	 echo '</div>';

	// echo "<a href=logout.php>Logout</a>";  tip: 'tooltip',
/*
}
else
{
		DoLogin();
}*/
?>
	<div class="clear"></div>
<?php
		include('footer.php');
?>