<?php
/*
 * Copyright (c) 2013-2014 Feathur Developers
 * 
 * This file is part of Feathur, a VPS control panel. 
 * 
 * If you intend on selling VPS from Feathur it is highly recommended that you 
 * purchase a license. Purchasing a license or donating via our site helps pay
 * for Feathur's development including new features and support costs.
 *
 * Website: http://feathur.com
 * IRC: irc.obsidianirc.net / 6667 - #feathur
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 * 
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

if(!isset($_APP)) { die("Unauthorized."); }

$sPageCurrent = "dashboard";
$sPageTitle = "Dashboard";

$sType = 0;

if($sServerList = $database->CachedQuery("SELECT * FROM `servers`", array())){
	sort($sServerList->data, 1);
	foreach($sServerList->data as $sServer){
		$sServer = new Server($sServer["id"]);
		
		// Calculates hard disk usage percentages.
		$sServerHDF = $sServer->sHardDiskFree;
		$sServerHDT = $sServer->sHardDiskTotal;
		if((!empty($sServerHDF)) && (!empty($sServerHDT))){
			$sHardDiskUsed = (100 - (round(((100 / $sServer->sHardDiskTotal) * $sServer->sHardDiskFree), 1)));
			$sHardDiskFree = (round(((100 / $sServer->sHardDiskTotal) * $sServer->sHardDiskFree), 1));
		} else {
			$sHardDiskUsed = 1;
			$sHardDiskFree = 1;
		}
		
		// Calculates memory usage percentages.
		$sServerFM = $sServer->sFreeMemory;
		$sServerTM = $sServer->sTotalMemory;
		if((!empty($sServerTM)) && (!empty($sServerFM))){
			$sRAMUsed = (100 - (round(((100 / $sServer->sTotalMemory) * $sServer->sFreeMemory), 1)));
			$sRAMFree = (round(((100 / $sServer->sTotalMemory) * $sServer->sFreeMemory), 1));
		} else {
			$sRAMUsed = 1;
			$sRAMFree = 1;
		}
		
		// Calculates bandwidth average usage in mbps.
		$sBandwidthDifference = "N/A";
		$sLastCheck = $sServer->sLastCheck;
		$sPreviousCheck = $sServer->sPreviousCheck;
		$sBandwidth = $sServer->sBandwidth;
		$sLastBandwidth = $sServer->sLastBandwidth;
		if((!empty($sLastCheck)) && (!empty($sPreviousCheck)) && (!empty($sBandwidth)) && (!empty($sLastBandwidth))){
			$sTimeDifference = $sLastCheck - $sPreviousCheck;
			if(!empty($sTimeDifference)){
				$sBandwidthDifference = round((($sBandwidth - $sLastBandwidth) / $sTimeDifference), 2);
				// Alert if bandwidth average over 100 mbps.
				if($sBandwidthDifference > 100){
					$sHigh[] = array("name" => $sServer->sName);
				}
				$sBandwidthDifference = "{$sBandwidthDifference} Mbps";
			}
		}
		
		// Calculates Free IP Space
		if($sBlockList = $database->CachedQuery("SELECT * FROM `server_blocks` WHERE `server_id` = :ServerId", array("ServerId" => $sServer->sId))){
			foreach($sBlockList->data as $sBlockRow){
				if($sIPList = $database->CachedQuery("SELECT * FROM `ipaddresses` WHERE `block_id` = :BlockId AND `vps_id` = :VPSId", array("BlockId" => $sBlockRow["block_id"], "VPSId" => 0))){
					$sIPCount = ($sIPCount + count($sIPList->data));
				}
			}
		} else {
			$sIPCount = "0";
		}
		
		$sStatistics[] = array("name" => $sServer->sName,
								"load_average" => $sServer->sLoadAverage,
								"disk_usage" => $sHardDiskUsed,
								"disk_free" => $sHardDiskFree,
								"ram_usage" => $sRAMUsed,
								"ram_free" => $sRAMFree,
								"status" => $sServer->sStatus,
								"uptime" => ConvertTime(round($sServer->sHardwareUptime, 0)),
								"ip_count" => $sIPCount,
								"bandwidth" => $sBandwidthDifference);
		
		if(empty($sServer->sStatus)){
			$sDown[] = array("name" => $sServer->sName);
		}
	}
}

if(!empty($_GET["json"]))
{
	/* This is for a live update... we'll manually change the 'ajax' variable in the router, to make it return
	 * it as JSON-packed HTML rather than templated HTML. */
	$router->uVariables["ajax"] = true;
}

$sPageContents = Templater::AdvancedParse($sAdminTemplate->sValue . "/status", $locale->strings, array("Statistics" => $sStatistics, "Down" => $sDown, "High" => $sHigh, "Status" => $sRequested["GET"]["json"]));
