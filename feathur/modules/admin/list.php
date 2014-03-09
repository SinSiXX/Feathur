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

$sVPS = array();
$sUsers = array();
$sServers = array();

if($router->uVariables["page_type"] == "search")
{
	/* This handles freeform text search. */
	$uQuery = "%{$_POST['query']}%";
	try {
		$sVPS = VPS::CreateFromQuery("SELECT * FROM vps WHERE (`container_id` LIKE :Search || `hostname` LIKE :Search || `primary_ip` LIKE :Search || `server_id` LIKE :Search)", array('Search' => $uQuery));
	} catch (NotFoundException $e) { /* No results */ }
	
	try {
		$sUsers = User::CreateFromQuery("SELECT * FROM accounts WHERE (`username` LIKE :Search || `email_address` LIKE :Search)", array('Search' => $uQuery));
	} catch (NotFoundException $e) { /* No results */ }
	
	try {
		$sServers = Server::CreateFromQuery("SELECT * FROM servers WHERE (`name` LIKE :ServerId || `id` = :ServerId)", array('ServerId' => $uQuery));
	} catch (NotFoundException $e) { /* No results */ }
	
	$sPageCurrent = "search";
	$sPageTitle = "Search";
}
elseif($router->uVariables["page_type"] == "vps")
{
	/* This handles the VPS list, and the VPS-by-type list. */
	if(empty($router->uVariables["lookup"]))
	{
		/* Display all VPSes. */
		try {
			$sVPS = VPS::CreateFromQuery("SELECT * FROM vps", array());
		} catch (NotFoundException $e) { /* No results */ }
		
		$sPageCurrent = "listvps";
		$sPageTitle = "VPS list";
	}
	else
	{
		/* Display VPSes by type. */
		if($router->uParameters[1] == "suspended")
		{
			try {
				$sVPS = VPS::CreateFromQuery("SELECT * FROM vps WHERE `suspended` = 1", array());
			} catch (NotFoundException $e) { /* No results */ }
		
			$sPageCurrent = "search";
			$sPageTitle = "Suspended VPSes";
		}
		else
		{
			try {
				$sVPS = VPS::CreateFromQuery("SELECT * FROM vps WHERE `type` = :Type", array('Type' => $router->uParameters[1]));
			} catch (NotFoundException $e) { /* No results */ }
			
			$sTypes = array("kvm" => "KVM", "openvz" => "OpenVZ");
			$sFormattedType = $sTypes[$router->uParameters[1]];
			
			$sPageCurrent = "search";
			$sPageTitle = "{$sFormattedType} VPSes";
		}
	}
}
elseif($router->uVariables["page_type"] == "users")
{
	/* This handles the user list, and the "VPSes for user" list. */
	if(empty($router->uVariables["lookup"]))
	{
		/* Display all users. */
		try {
			$sUsers =  User::CreateFromQuery("SELECT * FROM accounts", array());
		} catch (NotFoundException $e) { /* No results */ }
		
		$sPageCurrent = "listusers";
		$sPageTitle = "User list";
	}
	else
	{
		/* Display all VPSes that a particular user owns (and the user itself). */
		try {
			$sUsers = User::CreateFromQuery("SELECT * FROM accounts WHERE `id` = :UserId", array('UserId' => $router->uParameters[1]));
		} catch (NotFoundException $e) { /* No results */ }
		
		try {
			$sVPS = VPS::CreateFromQuery("SELECT * FROM vps WHERE `user_id` = :UserId", array('UserId' => $router->uParameters[1]));
		} catch (NotFoundException $e) { /* No results */ }
		
		$sPageCurrent = "search";
		$sPageTitle = "VPSes for user #" . (int) $router->uParameters[1];
	}
}
elseif($router->uVariables["page_type"] == "servers")
{
	/* This handles the server list, and the "VPSes on server" list. */
	if(empty($router->uVariables["lookup"]))
	{
		/* Display all servers. */
		try {
			$sServers =  Server::CreateFromQuery("SELECT * FROM servers", array());
		} catch (NotFoundException $e) { /* No results */ }
		
		$sPageCurrent = "listservers";
		$sPageTitle = "Server list";
	}
	else
	{
		/* Display all VPSes that are located on a particular server (and the server itself). */
		try {
			$sServers = Server::CreateFromQuery("SELECT * FROM servers WHERE `id` = :ServerId", array('ServerId' => $router->uParameters[1]));
		} catch (NotFoundException $e) { /* No results */ }
		
		try {
			$sVPS = VPS::CreateFromQuery("SELECT * FROM vps WHERE `server_id` = :ServerId", array('ServerId' => $router->uParameters[1]));
		} catch (NotFoundException $e) { /* No results */ }
		
		$sPageCurrent = "search";
		$sPageTitle = "VPSes on server #" . (int) $router->uParameters[1];
	}
}

/* This generates the actual lists of parameters for the template... */

$sTemplateServers = array();

if(!empty($sServers))
{
	foreach($sServers as $sServer)
	{
		$sTemplateServers[] = array(
			"id" => $sServer->sId,
			"name" => $sServer->sName,
			"type" => $sServer->sType,
			"ip_address" => $sServer->sIPAddress
		);
	}
}

$sServerCount = count($sTemplateServers);

$sTemplateVPS = array();

if(!empty($sVPS))
{
	foreach($sVPS as $sVPSItem)
	{
		try
		{
			$sUsername = $sVPSItem->sUser->sUsername;
			$sUserId = $sVPSItem->sUser->sId;
		}
		catch (NotFoundException $e)
		{
			$sUsername = "N/A";
			$sUserId = 0;
		}
		
		$sTemplateVPS[] = array(
			"id" => $sVPSItem->sId,
			"user_id" => $sUserId,
			"username" => $sUsername,
			"server_id" => $sVPSItem->sServerId,
			"hostname" => $sVPSItem->sHostname,
			"primary_ip" => $sVPSItem->sPrimaryIP,
			"suspended" => $sVPSItem->sSuspended,
			"type" => $sVPSItem->sType
		);
	}
}

$sVPSCount = count($sTemplateVPS);

$sTemplateUsers = array();

if(!empty($sUsers))
{
	foreach($sUsers as $sUser)
	{
		$sTemplateUsers[] = array(
			"id" => $sUser->sId,
			"username" => $sUser->sUsername,
			"email_address" => $sUser->sEmailAddress
		);
	}
}

$sUserCount = count($sTemplateUsers);

$sTotalCount = $sServerCount + $sVPSCount + $sUserCount;

$sPageContents = Templater::AdvancedParse($sAdminTemplate->sValue . "/list", $locale->strings, array(
	"vps" => $sTemplateVPS,
	"servers" => $sTemplateServers,
	"users" => $sTemplateUsers,
	"vps_count" => $sVPSCount,
	"server_count" => $sServerCount,
	"user_count" => $sUserCount,
	"total_count" => $sTotalCount
));
