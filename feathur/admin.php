<?php
include('./includes/loader.php');

$sId = $_GET['id'];
$sView = $_GET['view'];
$sAction = $_GET['action'];
$sSearch = $_GET['search'];
$sType = $_GET['type'];
$sPage = "admin";
$sPageType = "";

if(empty($sUser)){
	header("Location: index.php");
	die();
}

if($sUser->sPermissions != 7){
	header("Location: main.php");
	die();
}

if(file_exists('./admin2/'.$sView.'.php')) {
	include('./admin2/'.$sView.'.php');
} else {
	include("./admin2/dashboard.php");
}

echo Templater::AdvancedParse($sTemplate->sValue.'/master', $locale->strings, array("Content" => $sContent, "Page" => $sPage, "PageType" => $sPageType, "Errors" => $sErrors));
