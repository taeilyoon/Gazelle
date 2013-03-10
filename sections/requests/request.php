<?

/*
 * This is the page that displays the request to the end user after being created.
 */

include(SERVER_ROOT.'/sections/bookmarks/functions.php'); // has_bookmarked()
include(SERVER_ROOT.'/classes/class_text.php');

$Text = new TEXT;

if(empty($_GET['id']) || !is_number($_GET['id'])) {
	error(0);
}

$RequestID = $_GET['id'];

//First things first, lets get the data for the request.

$Request = Requests::get_requests(array($RequestID));
$Request = $Request['matches'][$RequestID];
if(empty($Request)) {
	error(404);
}

list($RequestID, $RequestorID, $RequestorName, $TimeAdded, $LastVote, $CategoryID, $Title, $Year, $Image, $Description, $CatalogueNumber, $RecordLabel, $ReleaseType,
	$BitrateList, $FormatList, $MediaList, $LogCue, $FillerID, $FillerName, $TorrentID, $TimeFilled, $GroupID, $OCLC) = $Request;

//Convenience variables
$IsFilled = !empty($TorrentID);
$CanVote = (empty($TorrentID) && check_perms('site_vote'));

if($CategoryID == 0) {
	$CategoryName = "Unknown";
} else {
	$CategoryName = $Categories[$CategoryID - 1];
}

//Do we need to get artists?
if($CategoryName == "Music") {
	$ArtistForm = get_request_artists($RequestID);
	$ArtistName = Artists::display_artists($ArtistForm, false, true);
	$ArtistLink = Artists::display_artists($ArtistForm, true, true);

	if($IsFilled) {
		$DisplayLink = $ArtistLink."<a href='torrents.php?torrentid=".$TorrentID."'>".$Title."</a> [".$Year."]";
	} else {
		$DisplayLink = $ArtistLink.$Title." [".$Year."]";
	}
	$FullName = $ArtistName.$Title." [".$Year."]";

	if($BitrateList != "") {
		$BitrateString = implode(", ", explode("|", $BitrateList));
		$FormatString = implode(", ", explode("|", $FormatList));
		$MediaString = implode(", ", explode("|", $MediaList));
	} else {
		$BitrateString = "Unknown, please read the description.";
		$FormatString = "Unknown, please read the description.";
		$MediaString = "Unknown, please read the description.";
	}

	if(empty($ReleaseType)) {
		$ReleaseName = "Unknown";
	} else {
		$ReleaseName = $ReleaseTypes[$ReleaseType];
	}

} else if($CategoryName == "Audiobooks" || $CategoryName == "Comedy") {
	$FullName = $Title." [".$Year."]";
	$DisplayLink = $Title." [".$Year."]";
} else {
	$FullName = $Title;
	$DisplayLink = $Title;
}

//Votes time
$RequestVotes = get_votes_array($RequestID);
$VoteCount = count($RequestVotes['Voters']);
$ProjectCanEdit = (check_perms('project_team') && !$IsFilled && (($CategoryID == 0) || ($CategoryName == "Music" && $Year == 0)));
$UserCanEdit = (!$IsFilled && $LoggedUser['ID'] == $RequestorID && $VoteCount < 2);
$CanEdit = ($UserCanEdit || $ProjectCanEdit || check_perms('site_moderate_requests'));

View::show_header('View request: '.$FullName, 'comments,requests,bbcode,jquery');

?>
<div class="thin">
	<div class="header">
		<h2><a href="requests.php">Requests</a> &gt; <?=$CategoryName?> &gt; <?=$DisplayLink?></h2>
		<div class="linkbox">
<? if($CanEdit) { ?>
			<a href="requests.php?action=edit&amp;id=<?=$RequestID?>" class="brackets">Edit</a>
<? }
if($UserCanEdit || check_perms('users_mod')) { //check_perms('site_moderate_requests')) { ?>
			<a href="requests.php?action=delete&amp;id=<?=$RequestID?>" class="brackets">Delete</a>
<? } ?>
<?	if(has_bookmarked('request', $RequestID)) { ?>
			<a href="#" id="bookmarklink_request_<?=$RequestID?>" onclick="Unbookmark('request', <?=$RequestID?>,'Bookmark');return false;" class="brackets">Remove bookmark</a>
<?	} else { ?>
			<a href="#" id="bookmarklink_request_<?=$RequestID?>" onclick="Bookmark('request', <?=$RequestID?>,'Remove bookmark');return false;" class="brackets">Bookmark</a>
<?	} ?>
			<a href="reports.php?action=report&amp;type=request&amp;id=<?=$RequestID?>" class="brackets">Report request</a>
<?	if(!$IsFilled) { ?>
			<a href="upload.php?requestid=<?=$RequestID?><?= ($GroupID ? "&amp;groupid=$GroupID" : '') ?>" class="brackets">Upload request</a>
<?	}
	if(!$IsFilled && (($CategoryID == 0) || ($CategoryName == "Music" && $Year == 0))) { ?>
			<a href="reports.php?action=report&amp;type=request_update&amp;id=<?=$RequestID?>" class="brackets">Request update</a>
<? } ?>

<?
//create a search url to worldcat and google based on title
$encoded_title = urlencode(preg_replace("/\([^\)]+\)/", "", $Title));
$encoded_artist = substr(str_replace("&amp;","and",$ArtistName), 0, -3);
$encoded_artist = str_ireplace("Performed By", "", $encoded_artist);
$encoded_artist = preg_replace("/\([^\)]+\)/", "", $encoded_artist);
$encoded_artist = urlencode($encoded_artist);

$worldcat_url = "http://worldcat.org/search?q=" . $encoded_artist . " " . $encoded_title;
$google_url = "https://www.google.com/search?&tbm=shop&q=" . $encoded_artist . " " . $encoded_title;

?>
			<a href="<? echo $worldcat_url; ?>" class="brackets">Find in library</a>
			<a href="<? echo $google_url; ?>" class="brackets">Find in stores</a>
		</div>
	</div>
	<div class="sidebar">
<? if($CategoryID != 0) { ?>
		<div class="box box_image box_image_albumart box_albumart"><!-- .box_albumart deprecated -->
			<div class="head"><strong>Cover</strong></div>
<?
if (!empty($Image)) {
	if(check_perms('site_proxy_images')) {
		$Image = 'http'.($SSL?'s':'').'://'.SITE_URL.'/image.php?i='.urlencode($Image);
	}
?>
			<p align="center"><img style="max-width: 220px;" src="<?=ImageTools::thumbnail($Image)?>" alt="<?=$FullName?>" onclick="lightbox.init('<?=$Image?>',220);" /></p>
<?	} else { ?>
			<p align="center"><img src="<?=STATIC_SERVER?>common/noartwork/<?=$CategoryIcons[$CategoryID-1]?>" alt="<?=$CategoryName?>" title="<?=$CategoryName?>" width="220" height="220" border="0" /></p>
<?	} ?>
		</div>
<? }
	if($CategoryName == "Music") { ?>
		<div class="box box_artists">
			<div class="head"><strong>Artists</strong></div>
			<ul class="stats nobullet">
<?
		if(!empty($ArtistForm[4]) && count($ArtistForm[4]) > 0) {
?>
				<li class="artists_composer"><strong>Composers:</strong></li>
<?			foreach($ArtistForm[4] as $Artist) {
?>
				<li class="artists_composer">
					<?=Artists::display_artist($Artist)?>
				</li>
<?			}
		}
		if(!empty($ArtistForm[6]) && count($ArtistForm[6]) > 0) {
?>
				<li class="artists_dj"><strong>DJ / Compiler:</strong></li>
<?			foreach($ArtistForm[6] as $Artist) {
?>
				<li class="artists_dj">
					<?=Artists::display_artist($Artist)?>
				</li>
<?
			}
		}
		if ((count($ArtistForm[6]) > 0) && (count($ArtistForm[1]) > 0)) {
			print '				<li class="artists_main"><strong>Artists:</strong></li>';
		} elseif ((count($ArtistForm[4]) > 0) && (count($ArtistForm[1]) > 0)) {
			print '				<li class="artists_main"><strong>Performers:</strong></li>';
		}
		foreach($ArtistForm[1] as $Artist) {
?>
				<li class="artists_main">
					<?=Artists::display_artist($Artist)?>
				</li>
<?		}
		if(!empty($ArtistForm[2]) && count($ArtistForm[2]) > 0) {
?>
				<li class="artists_with"><strong>With:</strong></li>
<?			foreach($ArtistForm[2] as $Artist) {
?>
				<li class="artists_with">
					<?=Artists::display_artist($Artist)?>
				</li>
<?			}
		}
		if(!empty($ArtistForm[5]) && count($ArtistForm[5]) > 0) {
?>
				<li class="artists_conductor"><strong>Conducted by:</strong></li>
<?			foreach($ArtistForm[5] as $Artist) {
?>
				<li class="artist_guest">
					<?=Artists::display_artist($Artist)?>
				</li>
<?			}
		}
		if(!empty($ArtistForm[3]) && count($ArtistForm[3]) > 0) {
?>
				<li class="artists_remix"><strong>Remixed by:</strong></li>
<?			foreach($ArtistForm[3] as $Artist) {
?>
				<li class="artists_remix">
					<?=Artists::display_artist($Artist)?>
				</li>
<?
			}
		}
		if(!empty($ArtistForm[7]) && count($ArtistForm[7]) > 0) {
?>
				<li class="artists_producer"><strong>Produced by:</strong></li>
<?			foreach($ArtistForm[7] as $Artist) {
?>
				<li class="artists_remix">
					<?=Artists::display_artist($Artist)?>
				</li>
<?
			}
		}
?>
			</ul>
		</div>
<?	} ?>
		<div class="box box_tags">
			<div class="head"><strong>Tags</strong></div>
			<ul class="stats nobullet">
<?	foreach($Request['Tags'] as $TagID => $TagName) { ?>
				<li>
					<a href="torrents.php?taglist=<?=$TagName?>"><?=display_str($TagName)?></a>
					<br style="clear:both" />
				</li>
<?	} ?>
			</ul>
		</div>
		<div class="box box_votes">
			<div class="head"><strong>Top contributors</strong></div>
			<table class="layout">
<?	$VoteMax = ($VoteCount < 5 ? $VoteCount : 5);
	$ViewerVote = false;
	for($i = 0; $i < $VoteMax; $i++) {
		$User = array_shift($RequestVotes['Voters']);
		$Boldify = false;
		if ($User['UserID'] == $LoggedUser['ID']) {
			$ViewerVote = true;
			$Boldify = true;
		}
?>
				<tr>
					<td>
						<a href="user.php?id=<?=$User['UserID']?>"><?=$Boldify?'<strong>':''?><?=display_str($User['Username'])?><?=$Boldify?'</strong>':''?></a>
					</td>
					<td>
						<?=$Boldify?'<strong>':''?><?=Format::get_size($User['Bounty'])?><?=$Boldify?'</strong>':''?>
					</td>
				</tr>
<?	}
	reset($RequestVotes['Voters']);
	if (!$ViewerVote) {
		foreach ($RequestVotes['Voters'] as $User) {
			if ($User['UserID'] == $LoggedUser['ID']) { ?>
				<tr>
					<td>
						<a href="user.php?id=<?=$User['UserID']?>"><strong><?=display_str($User['Username'])?></strong></a>
					</td>
					<td>
						<strong><?=Format::get_size($User['Bounty'])?></strong>
					</td>
				</tr>
<?			}
		}
	}
?>
			</table>
		</div>
	</div>
	<div class="main_column">
		<table class="layout">
			<tr>
				<td class="label">Created</td>
				<td>
					<?=time_diff($TimeAdded)?>	by  <strong><?=Users::format_username($RequestorID, false, false, false)?></strong>
				</td>
			</tr>
<?	if($CategoryName == "Music") {
		if(!empty($RecordLabel)) { ?>
			<tr>
				<td class="label">Record label</td>
				<td>
					<?=$RecordLabel?>
				</td>
			</tr>
<?		}
		if(!empty($CatalogueNumber)) { ?>
			<tr>
				<td class="label">Catalogue number</td>
				<td>
					<?=$CatalogueNumber?>
				</td>
			</tr>
<?		} ?>
			<tr>
				<td class="label">Release type</td>
				<td>
					<?=$ReleaseName?>
				</td>
			</tr>
			<tr>
				<td class="label">Acceptable bitrates</td>
				<td>
					<?=$BitrateString?>
				</td>
			</tr>
			<tr>
				<td class="label">Acceptable formats</td>
				<td>
					<?=$FormatString?>
				</td>
			</tr>
			<tr>
				<td class="label">Acceptable media</td>
				<td>
					<?=$MediaString?>
				</td>
			</tr>
<?		if(!empty($LogCue)) { ?>
			<tr>
				<td class="label">Required CD FLAC only extras</td>
				<td>
					<?=$LogCue?>
				</td>
			</tr>
<?		}
	}
	$Worldcat = "";
	$OCLC = str_replace(" ", "", $OCLC);
	if ($OCLC != "") {
		$OCLCs = explode(",", $OCLC);
		for ($i = 0; $i < count($OCLCs); $i++) {
			if (!empty($Worldcat)) {
				$Worldcat .= ', <a href="http://www.worldcat.org/oclc/'.$OCLCs[$i].'">'.$OCLCs[$i].'</a>';
			} else {
				$Worldcat = '<a href="http://www.worldcat.org/oclc/'.$OCLCs[$i].'">'.$OCLCs[$i].'</a>';
			}
		}
	}
	if (!empty($Worldcat)) { ?>
		<tr>
			<td class="label">WorldCat (OCLC) ID</td>
			<td>
				<?=$Worldcat?>
			</td>
		</tr>
<? 	}
	if ($GroupID) {
		/*$Groups = Torrents::get_groups(array($GroupID), true, true, false);
		$Group = $Groups['matches'][$GroupID];
		$GroupLink = Artists::display_artists($Group['ExtendedArtists']).'<a href="torrents.php?id='.$GroupID.'">'.$Group['Name'].'</a>';*/
?>
			<tr>
				<td class="label">Torrent group</td>
				<td><a href="torrents.php?id=<?=$GroupID?>">torrents.php?id=<?=$GroupID?></td>
			</tr>
<?	} ?>
			<tr>
				<td class="label">Votes</td>
				<td>
					<span id="votecount"><?=number_format($VoteCount)?></span>
<?	if($CanVote) { ?>
					&nbsp;&nbsp;<a href="javascript:Vote(0)" class="brackets"><strong>+</strong></a>
					<strong>Costs <?=Format::get_size($MinimumVote, 0)?></strong>
<?	} ?>
				</td>
			</tr>
<?	if ($LastVote > $TimeAdded) { ?>
			<tr>
				<td class="label">Last voted</td>
				<td>
					<?=time_diff($LastVote)?>
				</td>
			</tr>
<?	} ?>
<?	if($CanVote) { ?>
			<tr id="voting">
				<td class="label" title="These units are in base 2, not base 10. For example, there are 1,024 MB in 1 GB.">Custom vote (MB)</td>
				<td>
					<input type="text" id="amount_box" size="8" onchange="Calculate();" />
					<select id="unit" name="unit" onchange="Calculate();">
						<option value='mb'>MB</option>
						<option value='gb'>GB</option>
					</select>
					<input type="button" value="Preview" onclick="Calculate();" />
					<strong><?=($RequestTax * 100)?>% of this is deducted as tax by the system.</strong>
				</td>
			</tr>
			<tr>
				<td class="label">Post vote information</td>
				<td>
					<form class="add_form" name="request" action="requests.php" method="get" id="request_form">
						<input type="hidden" name="action" value="vote" />
						<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
						<input type="hidden" id="request_tax" value="<?=$RequestTax?>" />
						<input type="hidden" id="requestid" name="id" value="<?=$RequestID?>" />
						<input type="hidden" id="auth" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
						<input type="hidden" id="amount" name="amount" value="0" />
						<input type="hidden" id="current_uploaded" value="<?=$LoggedUser['BytesUploaded']?>" />
						<input type="hidden" id="current_downloaded" value="<?=$LoggedUser['BytesDownloaded']?>" />
						<input type="hidden" id="current_rr" value="<?=(float)$LoggedUser['RequiredRatio']?>" />
						<input id="total_bounty" type="hidden" value="<?=$RequestVotes['TotalBounty']?>" />
						If you add the entered <strong><span id="new_bounty">0.00 MB</span></strong> of bounty, your new stats will be: <br />
						Uploaded: <span id="new_uploaded"><?=Format::get_size($LoggedUser['BytesUploaded'])?></span>
						Ratio: <span id="new_ratio"><?=Format::get_ratio_html($LoggedUser['BytesUploaded'],$LoggedUser['BytesDownloaded'])?></span>
						<input type="button" id="button" value="Vote!" disabled="disabled" onclick="Vote();" />
					</form>
				</td>
			</tr>
<? }?>
			<tr id="bounty">
				<td class="label">Bounty</td>
				<td id="formatted_bounty"><?=Format::get_size($RequestVotes['TotalBounty'])?></td>
			</tr>
<?
	if($IsFilled) {
		$TimeCompare = 1267643718; // Requests v2 was implemented 2010-03-03 20:15:18
?>
			<tr>
				<td class="label">Filled</td>
				<td>
					<strong><a href="torrents.php?<?=(strtotime($TimeFilled)<$TimeCompare?'id=':'torrentid=').$TorrentID?>">Yes</a></strong>,
					by user <?=Users::format_username($FillerID, false, false, false)?>
<?		if($LoggedUser['ID'] == $RequestorID || $LoggedUser['ID'] == $FillerID || check_perms('site_moderate_requests')) { ?>
						<strong><a href="requests.php?action=unfill&amp;id=<?=$RequestID?>" class="brackets">Unfill</a></strong> Unfilling a request without a valid, nontrivial reason will result in a warning.
<?		} ?>
				</td>
			</tr>
<?	} else { ?>
			<tr>
				<td class="label" valign="top">Fill request</td>
				<td>
					<form class="edit_form" name="request" action="" method="post">
						<div>
							<input type="hidden" name="action" value="takefill" />
							<input type="hidden" name="auth" value="<?=$LoggedUser['AuthKey']?>" />
							<input type="hidden" name="requestid" value="<?=$RequestID?>" />
							<input type="text" size="50" name="link" <?=(!empty($Link) ? "value='$Link' " : '')?>/>
							<strong>Should be the permalink (PL) to the torrent (e.g. https://<?=SSL_SITE_URL?>/torrents.php?torrentid=xxxx).</strong>
							<br />
							<br />
							<? if(check_perms('site_moderate_requests')) { ?> For user: <input type="text" size="25" name="user" <?=(!empty($FillerUsername) ? "value='$FillerUsername' " : '')?>/>
							<br />
							<? } ?>
							<input type="submit" value="Fill request" />
							<br />
						</div>
					</form>
				</td>
			</tr>
<?	} ?>
			<tr>
				<td colspan="2" class="center"><strong>Description</strong></td>
			</tr>
			<tr>
				<td colspan="2"><?=$Text->full_format($Description);?></td>
			</tr>
		</table>
<?

$Results = $Cache->get_value('request_comments_'.$RequestID);
if($Results === false) {
	$DB->query("SELECT
			COUNT(c.ID)
			FROM requests_comments as c
			WHERE c.RequestID = '$RequestID'");
	list($Results) = $DB->next_record();
	$Cache->cache_value('request_comments_'.$RequestID, $Results, 0);
}

if(isset($_GET['postid']) && is_number($_GET['postid']) && $Results > TORRENT_COMMENTS_PER_PAGE) {
	$DB->query("SELECT COUNT(ID) FROM requests_comments WHERE RequestID = $RequestID AND ID <= $_GET[postid]");
	list($PostNum) = $DB->next_record();
	list($Page,$Limit) = Format::page_limit(TORRENT_COMMENTS_PER_PAGE,$PostNum);
} else {
	list($Page,$Limit) = Format::page_limit(TORRENT_COMMENTS_PER_PAGE,$Results);
}

//Get the cache catalogue
$CatalogueID = floor((TORRENT_COMMENTS_PER_PAGE*$Page-TORRENT_COMMENTS_PER_PAGE)/THREAD_CATALOGUE);
$CatalogueLimit=$CatalogueID*THREAD_CATALOGUE . ', ' . THREAD_CATALOGUE;

//---------- Get some data to start processing

// Cache catalogue from which the page is selected, allows block caches and future ability to specify posts per page
$Catalogue = $Cache->get_value('request_comments_'.$RequestID.'_catalogue_'.$CatalogueID);
if($Catalogue === false) {
	$DB->query("SELECT
			c.ID,
			c.AuthorID,
			c.AddedTime,
			c.Body,
			c.EditedUserID,
			c.EditedTime,
			u.Username
			FROM requests_comments as c
			LEFT JOIN users_main AS u ON u.ID=c.EditedUserID
			WHERE c.RequestID = '$RequestID'
			ORDER BY c.ID
			LIMIT $CatalogueLimit");
	$Catalogue = $DB->to_array(false,MYSQLI_ASSOC);
	$Cache->cache_value('request_comments_'.$RequestID.'_catalogue_'.$CatalogueID, $Catalogue, 0);
}

//This is a hybrid to reduce the catalogue down to the page elements: We use the page limit % catalogue
$Thread = array_slice($Catalogue,((TORRENT_COMMENTS_PER_PAGE*$Page-TORRENT_COMMENTS_PER_PAGE)%THREAD_CATALOGUE),TORRENT_COMMENTS_PER_PAGE,true);
?>
	<div class="linkbox"><a name="comments"></a>
<?
$Pages=Format::get_pages($Page,$Results,TORRENT_COMMENTS_PER_PAGE,9,'#comments');
echo $Pages;
?>
	</div>
<?

//---------- Begin printing
foreach($Thread as $Key => $Post) {
	list($PostID, $AuthorID, $AddedTime, $Body, $EditedUserID, $EditedTime, $EditedUsername) = array_values($Post);
	list($AuthorID, $Username, $PermissionID, $Paranoia, $Artist, $Donor, $Warned, $Avatar, $Enabled, $UserTitle) = array_values(Users::user_info($AuthorID));
?>
<table class="forum_post box vertical_margin<?=!Users::has_avatars_enabled() ? ' noavatar' : ''?>" id="post<?=$PostID?>">
	<colgroup>
<?	if(Users::has_avatars_enabled()) { ?>
		<col class="col_avatar" />
<? 	} ?>
		<col class="col_post_body" />
	</colgroup>
	<tr class="colhead_dark">
		<td colspan="<?=Users::has_avatars_enabled() ? 2 : 1?>">
			<div style="float:left;"><a href='#post<?=$PostID?>'>#<?=$PostID?></a>
				by <strong><?=Users::format_username($AuthorID, true, true, true, true)?></strong> <?=time_diff($AddedTime)?>
				- <a href="#quickpost" onclick="Quote('<?=$PostID?>','<?=$Username?>');" class="brackets">Quote</a>
<?	if ($AuthorID == $LoggedUser['ID'] || check_perms('site_moderate_forums')) { ?>
				- <a href="#post<?=$PostID?>" onclick="Edit_Form('<?=$PostID?>','<?=$Key?>');" class="brackets">Edit</a>
<?	}
	if (check_perms('site_moderate_forums')) { ?>
				- <a href="#post<?=$PostID?>" onclick="Delete('<?=$PostID?>');" class="brackets">Delete</a>
<?	} ?>
			</div>
			<div id="bar<?=$PostID?>" style="float:right;">
				<a href="reports.php?action=report&amp;type=requests_comment&amp;id=<?=$PostID?>" class="brackets">Report</a>
				<?	if (check_perms('users_warn') && $AuthorID != $LoggedUser['ID']) {
			$AuthorInfo = Users::user_info($AuthorID);
			if ($LoggedUser['Class'] >= $AuthorInfo['Class']) {
				?>
                <form class="manage_form hidden" name="user" id="warn<?=$PostID?>" action="" method="post">
                    <input type="hidden" name="action" value="warn" />
                    <input type="hidden" name="groupid" value="<?=$RequestID?>" />
                    <input type="hidden" name="postid" value="<?=$PostID?>" />
                    <input type="hidden" name="userid" value="<?=$AuthorID?>" />
                    <input type="hidden" name="key" value="<?=$Key?>" />
                </form>
                - <a href="#" onclick="$('#warn<?=$PostID?>').raw().submit(); return false;">[Warn]</a>
				<?		}
		}
			?>
				<a href="#">&uarr;</a>
			</div>
		</td>
	</tr>
	<tr>
<?	if (Users::has_avatars_enabled()) { ?>
		<td class="avatar" valign="top">
		<?=Users::show_avatar($Avatar, $Username, $HeavyInfo['DisableAvatars'])?>
		</td>
<?	} ?>
		<td class="body" valign="top">
			<div id="content<?=$PostID?>">
<?=$Text->full_format($Body)?>
<?	if ($EditedUserID) { ?>
				<br />
				<br />
<?		if(check_perms('site_moderate_forums')) { ?>
				<a href="#content<?=$PostID?>" onclick="LoadEdit('requests', <?=$PostID?>, 1); return false;">&laquo;</a>
<? 		} ?>
				Last edited by
				<?=Users::format_username($EditedUserID, false, false, false) ?> <?=time_diff($EditedTime,2,true,true)?>
<?	} ?>
			</div>
		</td>
	</tr>
</table>
<? } ?>
		<div class="linkbox">
		<?=$Pages?>
		</div>
<?
	View::parse('generic/reply/quickreply.php', array(
			'InputName' => 'requestid',
			'InputID' => $RequestID));
?>
	</div>
</div>
<? View::show_footer(); ?>
