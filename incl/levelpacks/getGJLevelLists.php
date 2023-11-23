<?php
//header
chdir(dirname(__FILE__));
include "../lib/connection.php";
require_once "../lib/GJPCheck.php";
require_once "../lib/exploitPatch.php";
require_once "../lib/mainLib.php";
$gs = new mainLib();
require "../lib/generateHash.php";

//initializing variables
$lvlstring = ""; $userstring = ""; $songsstring = ""; $lvlsmultistring = []; $str = ""; $order = "uploadDate";
$morejoins = "";

if(!empty($_POST["type"])){
	$type = ExploitPatch::number($_POST["type"]);
}else{
	$type = 0;
}
if(!empty($_POST["diff"])){
	$diff = ExploitPatch::numbercolon($_POST["diff"]);
}else{
	$diff = "-";
}


//ADDITIONAL PARAMETERS
if(!empty($_POST["star"]) OR (!empty($_POST["featured"]) AND $_POST["featured"]==1)){
	$params[] = "NOT starFeatured = 0";
}

//DIFFICULTY FILTERS
switch($diff){
	case -1:
		$params[] = "starDifficulty = '-1'";
		break;
	case -3:
		$params[] = "starDifficulty = '0'";
		break;
	case -2:
		$params[] = "starDifficulty > '5'";
		break;
	case "-";
		break;
	default:
		if($diff){
			$diff = str_replace(",", "0,", $diff) . "0";
			$params[] = "starDifficulty IN ($diff) AND starAuto = '0' AND starDemon = '0'";
		}
		break;
}
//TYPE DETECTION
if(!empty($_POST["str"])){
	$str = ExploitPatch::remove($_POST["str"]);
}
if(isset($_POST["page"]) AND is_numeric($_POST["page"])){
	$offset = ExploitPatch::number($_POST["page"]) . "0";
}else{
	$offset = 0;
}
switch($type){
	case 0:
		$order = "likes";
		if(!empty($str)){
			if(is_numeric($str)){
				$params = array("levelID = '$str'");
			}else{
				$params[] = "levelName LIKE '%$str%'";
			}
		}
		break;
	case 1:
		$order = "downloads";
		break;
	case 2:
		$order = "likes";
		break;
	case 5:
		$params[] = "lists.accountID = '$str'";
		break;
	case 12: //FOLLOWED
		$followed = ExploitPatch::numbercolon($_POST["followed"]);
		$params[] = "lists.accountID IN ($followed)";
		break;
	case 13: //FRIENDS
		$accountID = GJPCheck::getAccountIDOrDie();
		$peoplearray = $gs->getFriends($accountID);
		$whereor = implode(",", $peoplearray);
		$params[] = "lists.accountID IN ($whereor)";
		break;
}
//ACTUAL QUERY EXECUTION
$querybase = "FROM lists LEFT JOIN users ON lists.accountID = users.extID $morejoins";
if(!empty($params)){
	$querybase .= " WHERE (" . implode(" ) AND ( ", $params) . ")";
}
$query = "SELECT lists.*, users.userID, users.userName, users.extID $querybase $morejoins ";
if($order){
	if($ordergauntlet){
		$query .= "ORDER BY $order ASC";
	}else{
		$query .= "ORDER BY $order DESC";
	}
}
$query .= " LIMIT 10 OFFSET $offset";
//echo $query;
$countquery = "SELECT count(*) $querybase";
//echo $query;
$query = $db->prepare($query);
$query->execute();
//echo $countquery;
$countquery = $db->prepare($countquery);
$countquery->execute();
$totallvlcount = $countquery->fetchColumn();
$result = $query->fetchAll();
$levelcount = $query->rowCount();
foreach($result as &$list) {
	if($list["levelID"]!=""){
		//$lvlsmultistring[] = ["listID" => $list["listID"], "stars" => $list["starStars"], 'coins' => $list["starCoins"]];
		$lvlstring .= "1:{$list['listID']}:2:{$list['listName']}:3:{$list['listDesc']}:5:{$list['listVersion']}:49:{$list['accountID']}:50:{$list['userName']}:10:{$list['downloads']}:7:{$list['starDifficulty']}:14:{$list['likes']}:19:{$list['starFeatured']}:51:{$list['listlevels']}:28:{$list['uploadDate']}:29:{$list['updateDate']}"."|";
		$userstring .= $gs->getUserString($list)."|";
	}
}
$lvlstring = substr($lvlstring, 0, -1);
$userstring = substr($userstring, 0, -1);
echo $lvlstring."#".$userstring;
echo "#".$totallvlcount.":".$offset.":10";
echo "#";
//echo GenerateHash::genMulti($lvlsmultistring);
?>