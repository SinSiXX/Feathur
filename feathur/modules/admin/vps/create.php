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

if($router->uMethod == "post")
{
	$router->uVariables["ajax"] = true; /* POST requests to this module always result in an AJAX response. */
	$sErrors = array();
	
	/* Because PHP thinks something can only be made global from a function if
	 * it already existed before that function was called, we need to pre-define a
	 * few variables here - they'll be set from within validator functions. 
	 * PHP is like that little kid that always complains, even if he gets what he
	 * wants. Typical. */
	global $sServer, $sOwner, $sTemplate;
	$sServer = false;
	$sOwner = false;
	$sTemplate = false;
	
	$uDataSource = isset($uApiData) ? $uApiData : $_POST;
	
	/* We need to do validation in two stages here; the form data doesn't include
	 * the virtualization type. We'll check for some vitals, then retrieve the server
	 * from the database, and validate further based on its virtualization type. */
	
	try
	{
		$handler = new CPHPFormHandler($uDataSource);
		
		$handler
			->ValidateCustom("user", "You must select a user.", function($key, $value, $args, $handler) {
				return (!empty($value) && $value !== "z" && is_numeric($value));
			})
			->ValidateCustom("server", "You must select a server.", function($key, $value, $args, $handler) {
				return (!empty($value) && $value !== "z" && is_numeric($value));
			})
			->AbortIfErrors()
			->ValidateCustom("user", "The specified user does not exist.", function($key, $value, $args, $handler){
				global $sOwner;
				try
				{
					$sOwner = new User($value);
					return true;
				}
				catch (NotFoundException $e)
				{
					return false;
				}
			})
			->ValidateCustom("server", "The specified server does not exist.", function($key, $value, $args, $handler){
				global $sServer;
				try
				{
					$sServer = new Server($value);
					return true;
				}
				catch (NotFoundException $e)
				{
					return false;
				}
			})
			->Done();
	}
	catch (FormValidationException $e)
	{
		$sErrors = $e->GetErrorMessages();
	}
	
	if(empty($sErrors))
	{
		/* Continue ... Start out fresh with a new handler. For convenience, we
		 * will add the virtualization type to the form data. */
		
		$uDataSource["virt_type"] = $sServer->sType;
		$handler = new CPHPFormHandler($uDataSource);
		
		try
		{
			$handler
				->ValidateNumeric("disk")
				->ValidateNumeric("ram")
				->ValidateNumeric("bandwidthlimit")
				->ValidateNumeric("ipaddresses")
				->Switch_("virt_type", "",  /* TODO: Filter out empty error messages */
					/* Either OpenVZ options must be filled in... */
					$handler->Case_("openvz",
						$handler->ValidateCustom("openvz_template", "You must select a valid template.", function($key, $value, $args, $handler) {
							return (!empty($value) && $value !== "z" && is_numeric($value));
						}),
						$handler->ValidateNumeric("openvz_cpulimit"),
						$handler->ValidateNumeric("swap"), /* TODO: Validate format, allow MB etc. suffixes */
						$handler->ValidateNumeric("cpuunits"),
						$handler->ValidateNumeric("inodes"),
						$handler->ValidateNumeric("numproc"),
						$handler->ValidateNumeric("numiptent"),
						/* The rest is optional. */
						$handler->AbortIfErrors(),
						$handler->ValidateCustom("openvz_template", "The specified template is not a valid OpenVZ template.", function($key, $value, $args, $handler){
							global $sTemplate;
							try
							{
								$sTemplate = new Template($value);
								return ($sTemplate->sType === "openvz"); /* Only allow selection of OpenVZ templates */
							}
							catch (NotFoundException $e)
							{
								return false;
							}
						})
					),
					/* ... or KVM options must be. */
					$handler->Case_("kvm",
						$handler->ValidateCustom("kvm_template", "You must select a valid template.", function($key, $value, $args, $handler) {
							return (!empty($value) && $value !== "z" && is_numeric($value));
						}),
						$handler->ValidateNumeric("kvm_cpulimit"),
						$handler->AbortIfErrors(),
						$handler->ValidateCustom("kvm_template", "The specified template is not a valid OpenVZ template.", function($key, $value, $args, $handler){
							global $sTemplate;
							try
							{
								$sTemplate = new Template($value);
								return ($sTemplate->sType === "kvm"); /* Only allow selection of KVM templates */
							}
							catch (NotFoundException $e)
							{
								return false;
							}
						})
					)
				)
				->Done();
		}
		catch (FormValidationException $e)
		{
			$sErrors = $e->GetErrorMessages(array(
				"numeric" => array(
					"disk" => "You must enter a valid amount of disk space.",
					"ram" => "You must enter a valid amount of RAM.",
					"swap" => "You must enter a valid amount of swap memory.",
					"ipaddresses" => "You must enter a valid amount of IP addresses.",
					"inodes" => "You must enter a valid amount of inodes.",
					"numproc" => "You must enter a valid process limit.",
					"numiptent" => "You must enter a valid connection count limit.",
					"bandwidthlimit" => "You must enter a valid bandwidth limit.", /* TODO: "Traffic"? */
					"openvz_cpulimit" => "You must enter a valid CPU limit.",
					"kvm_cpulimit" => "You must enter a valid CPU limit."
				)
			));
		}
	}
	
	if(empty($sErrors))
	{
		/* Pfew, finally done with validation. This is where we actually create the VPS. */
		$sDriver = new $sServer->sType;
		
		/* FIXME: Log any VpsCreationErrors that occur here. */
		
		try
		{
			/* Check if sufficient IPs are available on the selected server. */
			if($handler->GetValue("ipaddresses") > 0)
			{
				try
				{
					$sServer->RequireIPs($handler->GetValue("ipaddresses"));
				}
				catch (IpSpaceException $e)
				{
					throw new VpsCreationException($e->getMessage());
				}
			}
			
			try
			{
				$sSSH = $sServer->Connect();
			}
			catch (ConnectionException $e)
			{
				throw new VpsCreationException($e->getMessage());
			}
			
			$uHostname = $handler->GetValue("hostname", "vps.example.com");
			$uNameserver = $handler->GetValue("nameserver", "8.8.8.8");
			
			$sVPS = new VPS();
			$sVPS->uType = $handler->GetValue("virt_type");
			$sVPS->uHostname = $uHostname;
			$sVPS->uUserId = $sOwner->sId;
			$sVPS->uServerId = $sServer->sId;
			$sVPS->uRAM = $handler->GetValue("ram");
			$sVPS->uDisk = $handler->GetValue("disk");
			$sVPS->uTemplateId = $sTemplate->sId;
			$sVPS->uBandwidthLimit = $handler->GetValue("bandwidthlimit");
			
			/* Virtualization-specific code... TODO: Move these back to a driver? */
			switch($handler->GetValue("virt_type"))
			{
				case "openvz":
					$sSetting = Core::GetSetting("container_id");
					$sContainerId = $sSetting->sValue;
					
					/* Update the CTID for the next VPS...
					 * TODO: Possible race condition! */
					$sSetting->uValue = $sSetting->sValue + 1;
					$sSetting->InsertIntoDatabase();
					
					$sVPS->uContainerId = $sContainerId;
					$sVPS->uNumIPTent = $handler->GetValue("numiptent");
					$sVPS->uNumProc = $handler->GetValue("numproc");
					$sVPS->uSWAP = $handler->GetValue("swap");
					$sVPS->uCPUUnits = $handler->GetValue("cpuunits");
					$sVPS->uCPULimit = $handler->GetValue("openvz_cpulimit");
					$sVPS->InsertIntoDatabase();
					
					$sAssignedIps = array();
					$desired_ips = $handler->GetValue("ipaddresses");
					
					if($desired_ips > 0)
					{
						$total_assigned = 0;
						
						try {
							$sBlocks = Block::CreateFromQuery("SELECT * FROM server_blocks WHERE `server_id` = :ServerId AND `ipv6` = 0", array("ServerId" => $sServer->sId), 0);
						} catch (NotFoundException $e) { throw new VpsCreationException("No IPv4 blocks are available for the selected server."); }
						
						foreach($sBlocks as $sBlock)
						{
							try {
								$sIpAddresses = IP::CreateFromQuery("SELECT * FROM ipaddresses WHERE `block_id` = :BlockID and `vps_id` = 0", array("BlockId" => $sBlock->sId));
							} catch (NotFoundException $e) { continue; /* Ignore this block, move on to the next block. */ }
							
							foreach($sIpAddresses as $sIpAddress)
							{
								if($total_assigned < $desired_ips)
								{
									$sIpAddress->uVPSId = $sVPS->sId;
									$sIpAddress->InsertIntoDatabase();
									$sAssignedIps[] = $sIpAddress; /* Add to the 'assigned' list, for later use in vzctl commands. */
									
									if($total_assigned == 0)
									{
										/* This is the first IP to be assigned, thus the primary IP. */
										$sVPS->uPrimaryIP = $sIpAddress->sIPAddress;
										$sVPS->InsertIntoDatabase();
									}
									
									$total_assigned += 1;
								}
							}
						}
						
						if($total_assigned < $desired_ips)
						{
							/* FIXME: Log a warning! */
						}
					}
					
					/* TODO: Actual creation. */
					break;
				case "kvm":
					break; /* TODO: KVM implementation... */
			}
		}
		catch (VpsCreationException $e)
		{
			/* FIXME: Log these! */
			$sErrors = array($e->getMessage);
		}
		
		/*
		$sDatabaseMethodName = "database_{$sServer->sType}_create";
		$sMainMethodName = "{$sServer->sType}_create";
		$sCreate = $sServerType->$sMethod($sUser, $sRequested);
		if(is_array($sCreate)){
			echo json_encode($sCreate);
			die();
		}
		$sFinish = $sServerType->$sSecond($sUser, $sRequested);
		if(is_array($sFinish)){
			echo json_encode($sFinish);
			die();
		}
		*/
		
		
		
		$sPageContents = ""; /* Not used on this page */
		$sJsonVariables["type"] = "success";
		$sJsonVariables["result"] = "VPS created.";
	}
	
	if(!empty($sErrors))
	{
		/* FIXME: Normalize this with the rest of Feathur... */
		$sJsonVariables["type"] = "error";
		$sJsonVariables["result"] = implode("<br>", $sErrors);
	}
}
else
{
	$sKvmTemplateList = array();
	$sOpenvzTemplateList = array();
	$sUserList = array();
	$sServerList = array();
	
	/* TODO: I'm sure this can be made more reusable. */
	try
	{
		foreach(Template::CreateFromQuery("SELECT * FROM templates WHERE `Type` = 'kvm'") as $sVpsTemplate)
		{
			$sKvmTemplateList[] = array(
				"id" => $sVpsTemplate->sId,
				"name" => $sVpsTemplate->sName
			);
		}
	}
	catch (NotFoundException $e)
	{
		/* No KVM templates. */
	}
	
	try
	{
		foreach(Template::CreateFromQuery("SELECT * FROM templates WHERE `Type` = 'openvz'") as $sVpsTemplate)
		{
			$sOpenvzTemplateList[] = array(
				"id" => $sVpsTemplate->sId,
				"name" => $sVpsTemplate->sName
			);
		}
	}
	catch (NotFoundException $e)
	{
		/* No OpenVZ templates. */
	}
	
	try
	{
		foreach(User::CreateFromQuery("SELECT * FROM accounts ORDER BY `email_address` ASC") as $sUser)
		{
			$sUserList[] = array(
				"id" => $sUser->sId,
				"email" => $sUser->sEmailAddress
			);
		}
	}
	catch (NotFoundException $e)
	{
		/* No OpenVZ templates. */
	}
	
	try
	{
		foreach(Server::CreateFromQuery("SELECT * FROM servers") as $sServer)
		{
			$sServerList[] = array(
				"id" => $sServer->sId,
				"name" => $sServer->sName,
				"type" => $sServer->sType
			);
		}
	}
	catch (NotFoundException $e)
	{
		/* No OpenVZ templates. */
	}
	
	$sPageCurrent = "create";
	$sPageTitle = "Create VPS";
	$sPageContents = Templater::AdvancedParse($sAdminTemplate->sValue . "/createvps", $locale->strings, array(
		"Error" => $sErrors,
		"KvmTemplateList" => $sKvmTemplateList,
		"OpenvzTemplateList" => $sOpenvzTemplateList,
		"ServerList" => $sServerList,
		"UserList" => $sUserList
	));
}
