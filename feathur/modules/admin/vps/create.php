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

/* FIXME: The below are validation functions that are used further
 * in this file. They should really be moved to an include instead. */

function ValidateSelected($key, $value, $args, $handler) {
	return (!empty($value) && $value !== "z" && is_numeric($value));
}

function ValidateUser($key, $value, $args, $handler){
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
}

function ValidateServer($key, $value, $args, $handler){
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
}

function ValidateOpenvzTemplate($key, $value, $args, $handler){
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
}

function ValidateKvmTemplate($key, $value, $args, $handler){
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
}

/* Actual module code starts here. */

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
			->ValidateCustom("user", "You must select a user.", "ValidateSelected")
			->ValidateCustom("server", "You must select a server.", "ValidateSelected")
			->AbortIfErrors()
			->ValidateCustom("user", "The specified user does not exist.", "ValidateUser")
			->ValidateCustom("server", "The specified server does not exist.", "ValidateServer")
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
						$handler->ValidateCustom("openvz_template", "You must select a valid template.", "ValidateSelected"),
						$handler->ValidateNumeric("openvz_cpulimit"),
						$handler->ValidateNumeric("swap"), /* TODO: Validate format, allow MB etc. suffixes */
						$handler->ValidateNumeric("cpuunits"),
						$handler->ValidateNumeric("inodes"),
						$handler->ValidateNumeric("numproc"),
						$handler->ValidateNumeric("numiptent"),
						/* The rest is optional. */
						$handler->AbortIfErrors(),
						$handler->ValidateCustom("openvz_template", "The specified template is not a valid OpenVZ template.", "ValidateOpenvzTemplate")
					),
					/* ... or KVM options must be. */
					$handler->Case_("kvm",
						$handler->ValidateCustom("kvm_template", "You must select a valid template.", "ValidateSelected"),
						$handler->ValidateNumeric("kvm_cpulimit"),
						$handler->AbortIfErrors(),
						$handler->ValidateCustom("kvm_template", "The specified template is not a valid KVM template.", "ValidateKvmTemplate") /* Is this supposed to be required for KVM? */
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
			
			$sVPS = new VPS();
			$sVPS->uType = $handler->GetValue("virt_type");
			$sVPS->uHostname = $uHostname;
			$sVPS->uUserId = $sOwner->sId;
			$sVPS->uServerId = $sServer->sId;
			$sVPS->uRAM = $handler->GetValue("ram");
			$sVPS->uDisk = $handler->GetValue("disk");
			$sVPS->uTemplateId = $sTemplate->sId;
			$sVPS->uBandwidthLimit = $handler->GetValue("bandwidthlimit");
			$sVPS->uNameserver = $handler->GetValue("nameserver", "8.8.8.8");
			$sVPS->uHostname = $handler->GetValue("hostname", "vps.example.com");
			
			$sSetting = Core::GetSetting("container_id");
			$sContainerId = $sSetting->sValue;
			
			/* Update the CTID for the next VPS...
			 * TODO: Possible race condition! */
			$sSetting->uValue = $sSetting->sValue + 1;
			$sSetting->InsertIntoDatabase();
			
			$sVPS->uContainerId = $sContainerId;
			
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
			
			switch($handler->GetValue("virt_type"))
			{
				case "openvz":
					$sVPS->uNumIPTent = $handler->GetValue("numiptent");
					$sVPS->uNumProc = $handler->GetValue("numproc");
					$sVPS->uSWAP = $handler->GetValue("swap");
					$sVPS->uCPUUnits = $handler->GetValue("cpuunits");
					$sVPS->uCPULimit = $handler->GetValue("openvz_cpulimit");
					$sVPS->InsertIntoDatabase();
					
					/* The creation code is moved here for now. It should eventually be back
					 * into the "driver" (openvz) class, but as I don't have a full oversight of
					 * the security implications of removing the authentication code there,
					 * I am placing it here instead - that way I can be certain that this code
					 * cannot be invoked without the sufficient authentication.
					 * TODO: Move this code back to driver class as a member function. */
					$sSSH = $sVPS->sServer->Connect();
					$sPassword = $handler->GetValue("root_password");
					$sDiskLimit = $sVPS->sDisk + 1;  /* NOTE: This is in GB. If a unit change were to happen, it needs to be changed. */
					$sCPUs = round($sVPS->sCPULimit / 100);
					
					$sCommands = array(
						"vzctl create {$sVPS->sContainerId} --ostemplate {$sVPS->sTemplate->sPath}",
						"vzctl set {$sVPS->sContainerId} --onboot yes --save",
						"vzctl set {$sVPS->sContainerId} --ram {$sVPS->sRAM}M --swap {$sVPS->sSWAP}M --save",
						"vzctl set {$sVPS->sContainerId} --cpuunits {$sVPS->sCPUUnits} --save",
						"vzctl set {$sVPS->sContainerId} --cpulimit {$sVPS->sCPULimit} --save",
						"vzctl set {$sVPS->sContainerId} --cpus {$sCPUs} --save",
						"vzctl set {$sVPS->sContainerId} --diskspace {$sVPS->sDisk}G:{$sHighDisk}G --save",
						"vzctl start {$sVPS->sContainerId}",
						"vzctl set {$sVPS->sContainerId} --nameserver {$sVPS->sNameserver} --save",
						"vzctl set {$sVPS->sContainerId} --hostname {$sVPS->sHostname} --save",
						"modprobe tun;vzctl set {$sVPS->sContainerId} --devnodes net/tun:rw --save;vzctl set {$sVPS->sContainerId} --devices c:10:200:rw --save;vzctl set {$sVPS->sContainerId} --capability net_admin:on --save;vzctl exec {$sVPS->sContainerId} mkdir -p /dev/net;vzctl exec {$sVPS->sContainerId} mknod /dev/net/tun c 10 200",
						"modprobe iptables_module ipt_helper ipt_REDIRECT ipt_TCPMSS ipt_LOG ipt_TOS iptable_nat ipt_MASQUERADE ipt_multiport xt_multiport ipt_state xt_state ipt_limit xt_limit ipt_recent xt_connlimit ipt_owner xt_owner iptable_nat ipt_DNAT iptable_nat ipt_REDIRECT ipt_length ipt_tcpmss iptable_mangle ipt_tos iptable_filter ipt_helper ipt_tos ipt_ttl ipt_SAME ipt_REJECT ipt_helper ipt_owner ip_tables",
						"vzctl set {$sVPS->sContainerId} --iptables ipt_REJECT --iptables ipt_tos --iptables ipt_TOS --iptables ipt_LOG --iptables ip_conntrack --iptables ipt_limit --iptables ipt_multiport --iptables iptable_filter --iptables iptable_mangle --iptables ipt_TCPMSS --iptables ipt_tcpmss --iptables ipt_ttl --iptables ipt_length --iptables ipt_state --iptables iptable_nat --iptables ip_nat_ftp --save"
					);
					
					if(!empty($sPassword))
					{
						$sCommands[] = "vzctl set {$sVPS->sContainerId} --userpasswd root:{$sPassword} --save";
					}
					
					if(!empty($sAssignedIps))
					{
						foreach($sAssignedIps as $sIp)
						{
							$sCommands[] = "vzctl set {$sVPS->sContainerId} --ipadd {$sIp->sIPAddress} --save";
						}
					}
					
					$sCommands[] = "vzctl stop {$sVPS->sContainerId}";
					$sCommands[] = "vzctl start {$sVPS->sContainerId}";
					
					/* This combines all commands into a single command string, delimited by semicolons. */
					$sCommandString = implode("; ", $sCommands);
					
					$sResult = $sSSH->exec($sCommandString);
					
					VPS::save_vps_logs(array(
						"command" => str_replace($sPassword, "<obfuscated>", $sCommandString),
						"result" => $sResult
					), $sVPS);
					
					$sPageContents = ""; /* Not used on this page */
					$sJsonVariables["type"] = "success";
					$sJsonVariables["result"] = "VPS has been created.";
					$sJsonVariables["reload"] = 1;
					$sJsonVariables["vps"] = $sVPS->sId;
					break;
				case "kvm":
					$sMacAddresses = array();
					for($i = 0; $i <= $handler->GetValue("ip_addresses"); $i++)
					{
						$sMacAddresses[] = generate_mac();
					}
					
					$sVPS->uMac = implode(",", $sMacAddresses); /* Comma-delimited list of MACs */
					$sVPS->uCPULimit = $handler->GetValue("kvm_cpulimit");
					$sVPS->uVNCPort = ($sVPS->uContainerId + 5900);
					$sVPS->uBootOrder = "hd";
					$sVPS->InsertIntoDatabase();
					
					/* Create the VM */
					$sSSH = $sVPS->sServer->Connect();
					
					/* TODO: Finish this stuff... */
					//$sCreate = $this->kvm_config($sUser, $sVPS, $sRequested);
					//$sDHCP = $this->kvm_dhcp($sUser, $sVPS, $sRequested);
					
					//$sCommandList .= "lvcreate -n kvm{$sVPS->sContainerId}_img -L {$sVPS->sDisk}G {$sServer->sVolumeGroup};virsh create /var/feathur/configs/kvm{$sVPS->sContainerId}-vps.xml;virsh autostart kvm{$sVPS->sContainerId}";
					//$sLog[] = array("command" => $sCommandList, "result" => $sSSH->exec($sCommandList));
					//$sSave = VPS::save_vps_logs($sLog, $sVPS);
					break;
			}
		}
		catch (VpsCreationException $e)
		{
			/* FIXME: Log these! */
			$sErrors = array($e->getMessage);
		}
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
