<?php

$headcommands='
<!-- <link type="text/css" href="widgets.css" rel="stylesheet" /> -->

<script type="text/javascript" src="js/jquery/jquery-1.3.2.min.js"></script>
<script type="text/javascript" src="js/svg/jquery.svg.min.js"></script>
<script type="text/javascript" src="js/jquery/jquery-ui-1.7.2.custom.min.js"></script>
<script type="text/javascript" src="js/jquery/jquery.livequery.js"></script>
<script type="text/javascript" src="js/vilfredo.php"></script>';

include('header.php');

// sanitize url
if ( !isset($_GET[QUERY_KEY_QUESTION]) || !is_numeric($_GET[QUERY_KEY_QUESTION]) )
{
	header("Location: error_page.php");
	exit;
}

if ( isset($_GET[QUERY_KEY_ROOM]) && (hasTags($_GET[QUERY_KEY_ROOM]) || !checkMaxStringLength($_GET[QUERY_KEY_ROOM], MAX_LEN_ROOM)) )
{
	header("Location: error_page.php");
	exit;
}

$question = (int)$_GET[QUERY_KEY_QUESTION];
$room = isset($_GET[QUERY_KEY_ROOM]) ? $_GET[QUERY_KEY_ROOM] : "";


$QuestionInfo = GetQuestion($question);

if (!$QuestionInfo)
{
	header("Location: error_page.php");
	exit;
}

WriteQuestionInfo($question,$userid);

$title=$QuestionInfo['title'];
$content=$QuestionInfo['question'];
#	$room=$QuestionInfo['room'];
$phase=$QuestionInfo['phase'];
$generation=$QuestionInfo['roundid'];
$author=$QuestionInfo['usercreatorid'];



//if ($userid)
//{
	// Check if user has room access.
	if (!HasQuestionAccess())
	{
		header("Location: index.php");
	}

#echo "<table>";
$g=$generation-1;
$questionData=array();//we store all the data at the beginning. What need do we have to extract it again?
$questionData["proposalsEndorsers"]=array();
$questionData["ParetoFront"]=array();
while($g>0)
{
	$proposalsEndorsers=ReturnProposalsEndorsersArray($question,$g); 
	$ParetoFront=CalculateParetoFrontFromProposals($proposalsEndorsers);
	$questionData["proposalsEndorsers"][$g]=$proposalsEndorsers;
	$questionData["ParetoFront"][$g]=$ParetoFront;	
	$g=$g-1;
}	


#while($g>0)
#{
#	$proposalsEndorsers=ReturnProposalsEndorsersArray($question,$g); 
#	$questionData[$g]=$proposalsEndorsers;
	
#	$ParetoFront=CalculateParetoFrontFromProposals($proposalsEndorsers);

	
	
	

	$Disabled="DISABLED";
	$Tootip="{$VGA_CONTENT['wait_to_write_tooltip']}";
	if($phase==0)	
	{
		$Disabled="";
		$Tootip="{$VGA_CONTENT['reprop_this_title']}";
	}
	

	echo "<h1>{$VGA_CONTENT['hist_props_txt']}</h1>";
	#	echo '<quote>"History, teach us nothing": Sting</quote><br /><br />';

	#WriteIntergenerationalGVMap($question);
	$sql = "SELECT * FROM proposals WHERE experimentid = ".$question." and roundid < ".$generation." ORDER BY `roundid` DESC, `dominatedby` ASC  ";
	$response = mysql_query($sql);
	if ($response)
	{
		
		
		echo '<div id="historybox">';
		echo '<table border="1" class="historytable">';
		#echo '<tr><th><strong>' . $VGA_CONTENT['proposal_txt'] . '</strong></th><th><strong>' . $VGA_CONTENT['author_txt'] . '</strong></th><th><strong>' . $VGA_CONTENT['endorsers_txt'] . '</strong></th><th><strong>' . $VGA_CONTENT['result_txt'] . '</strong></th><th><strong>' . $VGA_CONTENT['you_txt'] . '</strong></th></tr>';
		$genshowing=$generation;
		$i = 0;
		while ($row = mysql_fetch_array($response))
		{
			if ($row[3]!=$genshowing)
			{
				
				$genshowing=$row[3];
				WriteGraphVizMap($question,$genshowing);
				
				echo '<tr><td colspan="5" class="genhist"><h3>'.WriteGenerationPage($question,$genshowing,$room).' ';
				$proposers=AuthorsOfNewProposals($question,$genshowing);
#				echo "Proposers:";
#				foreach ($proposers as $p)
#				{
#					$sql5 = "SELECT username FROM users WHERE id = ".$p." ";
#					$response5 = mysql_query($sql5);
#					$row5 = mysql_fetch_row($response5);
#					echo " ".$row5[0]." ";
#				}
				$endorsers=Endorsers($question,$genshowing);
#				echo "<br />";
#				echo "Endorsers:";
#				foreach ($endorsers as $e)
#				{
#					$sql5 = "SELECT username FROM users WHERE id = ".$e." ";
#					$response5 = mysql_query($sql5);
#					$row5 = mysql_fetch_row($response5);
#					echo " ".$row5[0]." ";
#				}
				
				echo '</h3>';

				#$ProposalsCouldDominate=CalculateKeyPlayers($question,$genshowing);
				
				#if (count($ProposalsCouldDominate) > 0)
				#{
				#	echo '<br/><p>Key Players (proposals they should work on):<br/>';
					

				#	$KeyPlayers=array_keys($ProposalsCouldDominate);
				#	foreach ($KeyPlayers as $KP)
				#	{
				#		echo " ".WriteUserVsReader($KP,$userid)." ( ";
				#		foreach ($ProposalsCouldDominate[$KP] as $PCD)
				#		{
				#			$urlquery = CreateProposalURL($PCD, $room);
				#			echo '<a href="viewproposal.php'.$urlquery.'" title="'.SafeStringProposal($PCD).'">'.$PCD.'</a> ';
				#		}
				#		echo ")<br/>";
				#	}
				#	echo '</p>';
				#}
				#else
				#{
			#		echo '<br/><p>No Key Players</p>';
			#	}
				
				echo '</td></tr>';
				
				echo '<tr><td colspan="1" class="genhist">';

				$proposalsEndorsers=$questionData["proposalsEndorsers"][$genshowing];
				$ParetoFront=$questionData["ParetoFront"][$genshowing];
				InsertMapFromArray($question,$g,$proposalsEndorsers,$ParetoFront,$room,$userid,"M",0,/*$InternalLinks=*/false);
				
#				InsertMap($question,$genshowing,$userid,"M");
				/*
				$mapid = 'svggraph' . $genshowing;
				$graphsize = 'mediumgraph';
				if ($filename = InsertMap2($question,$genshowing,$userid,"M"))
				{
					$filename .= '.svg';
					?>
					<script type="text/javascript">
					$(document).ready(function() {
						var svgfile = '<?= $filename; ?>';
						$('#' + '<?=$mapid?>').svg({loadURL: svgfile});
					});
					</script>
				<?php
				}
				echo '<div id="' . $mapid . '" class="'.$graphsize.'"></div>';
				*/
				echo '</td>';

				echo '<td colspan="4">';
				#echo drawVanDerWallSets($question,$genshowing,$endorsers,$PreviousAuthors,$proposers);
				
				$ParetoFrontEndorsers=	array_intersect_key($proposalsEndorsers, array_flip($ParetoFront));
				InsertMapFromArray($question,$genshowing,$ParetoFrontEndorsers,$ParetoFront,$room,$userid,"S",0,/*$InternalLinks=*/false);
				
				echo '</td></tr>';
				echo '<tr><th><strong>' . $VGA_CONTENT['proposal_txt'] . '</strong></th><th><strong>' . $VGA_CONTENT['author_txt'] . '</strong></th><th><strong>' . $VGA_CONTENT['endorsers_txt'] . '</strong></th><th><strong>' . $VGA_CONTENT['result_txt'] . '</strong></th><th><strong>' . $VGA_CONTENT['you_txt'] . '</strong></th></tr>';
				
			}


			$dominatedby=$row[6];
			$source=$row[5];

			$Endorsed=0;
			
			if ($userid) 
			{
				$sql6 = "SELECT  id FROM endorse WHERE  endorse.userid = " . $userid . " and endorse.proposalid = " . $row[0] . " LIMIT 1";
				if(mysql_fetch_row(mysql_query($sql6)))
				{$Endorsed=1;}
				else
				{$Endorsed=0;}
			}
			
			$urlquery = CreateProposalURL($row[0], $room);
			
			echo '<tr class="paretorow">';
#			echo '<td><a href="viewproposal.php'.$urlquery.'">link</a></td>';
			
			echo '<td class="paretocell">';
			
			// ***
			//
			echo '<div class="paretoproposal">';
			
			
			
			#echo '<div class="paretoproposal">';
				
				?>
				<form method="get" action="npv.php" target="_blank"><?php	echo '<h3>'.WriteProposalPage($row[0],$room)." ";?>	
						<input type="hidden" name="p" id="p" value="<?php echo $row[0]; ?>" />
						<?php	
			if($room) 
						{ 
							?><input type="hidden" name="room" id="room" value="<?php echo $room; ?>" /><?php	
						}
						
					?>
					<input type="submit" name="submit" title="<?php echo $Tootip; ?>" id="submit" value="<?=$VGA_CONTENT['reprop_mutate_button']?>" <?php echo $Disabled; ?>/></form>
						<?php	echo '</h3>';
				WriteProposalOnlyContent($row[0],$question,$generation,$room,$userid);
			
			
			
			
			
			
			
			
			
			
			
			
			
			
			#WriteProposalOnlyText($row[0],$question,$generation,$room,$userid);
			
			#if (!empty($row['abstract'])) {
			#	echo '<div class="paretoabstract">';
			#	echo display_fulltext_link();
			##	echo '<h3>Proposal Abstract</h3>';
			#	echo $row['abstract'] ;
			#	echo '</div>';
			#	echo '<div class="paretotext">';
			#	echo '<h3>Proposal</h3>';
			#	echo $row['blurb'];
			#	echo '</div>';
			#}
			#else {
			#	echo '<div class="paretofulltext">';
			#	echo '<h3>Proposal</h3>';
			#	echo $row['blurb'] ;
			#	echo '</div>';
			#}
			
			
			/*
			if ($phase == 1)
			{
				$mod_btn = "disabled=\"disabled\"";
			}
			else {
				$mod_btn = "";
			}
			
			?>
			<form method="post" action="npv.php">
			<input type="hidden" name="p" id="p" value="<?php echo $row[0]; ?>" />
			<input <?php echo $mod_btn; ?> type="submit" name="submit" id="submit" value="Modify" title="Click here to create a new version of this proposal"/>
			</form>
			<?php*/
			
			echo '</div>';
			//
			// ***
			/*
			$has_abstract = false;
			if (!empty($row['abstract'])) {
				$has_abstract = true;
				echo display_viewall_link();
				echo $row['abstract'];
				if ($has_abstract) {
				}
			}
			else {
				echo $row['blurb'];
			}
			
			if ($has_abstract)
			{
				echo '<div class="paretotext">';
				echo '<h3>Proposal</h3>';
				echo $row['blurb'];
				echo '</div>';
			}
			echo '<br />';
			*/
			// ****
			echo '</td>';
			

			echo '<td>';
			if ($row[5])
			{
				echo "<h6>{$VGA_CONTENT['inherited_txt']} ";
				echo WriteUserVsReader($row[2],$userid);
				echo "</h6>";
			}
			else
			{
				echo WriteUserVsReader($row[2],$userid);
				#echo " New";
			}
			echo '</td>';

			$endorsers=EndorsersToAProposal($row[0]);
			echo '<td>';

			foreach ($endorsers as $user)
			{
			echo WriteUserVsReader($user,$userid);
			}
#			echo '<a title="The list might not be complete, due to a recent Bug" href="FAQ.php#bugendorsmen"><sup>*</sup></a>';

			echo '&nbsp;</td>';
			echo '<td>';

			if($row[6])
			{
				echo '<img src="images/thumbsdown.gif" title="' . $VGA_CONTENT['comm_reject_title'] . '" height="45">';
			}
			else
			{
				echo '<img src="images/thumbsup.gif" title="' . $VGA_CONTENT['comm_accept_title'] . '"  height="48">';
			}
			echo '</td>';
			echo '<td>';
			
			if ($userid)
			{
				if($Endorsed)
				{
					echo ' <img src="images/thumbsup.gif" title="' . $VGA_CONTENT['you_end_prop_title'] . '"  height="28">';
				}
				else
				{
					echo ' <img src="images/thumbsdown.gif" title="' . $VGA_CONTENT['you_ign_prop_title'] . '" height="25">';
				}
				echo '<a title="' . $VGA_CONTENT['res_incon_link'] . '" href="FAQ.php#bugendorsmen"><sup>*</sup></a>';
			}
			else
			{
				echo '&nbsp;&nbsp;_';
			}
			
			
			echo '</td>';

			echo '</tr> ';
		}
		echo '</table>';

		echo '</div>';
	}
	// echo "<a href=logout.php>Logout</a>";
/*
}
else
{
		DoLogin();
}*/

include('footer.php');

?>







