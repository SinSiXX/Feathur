<?php
/*
 * Copyright (c) 2013-2014 Feathur Developers
 * 
 * This file is part of Feathur, a VPS control panel.
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

$sErrors = array();

if($router->uMethod == "post")
{
	try
	{
		$handler = new CPHPFormHandler();
		
		$handler
			->RequireNonEmpty("name")
			->RequireNonEmpty("hostname")
			->RequireNonEmpty("username")
			->RequireNonEmpty("key")
			->RequireNonEmpty("type")
			->RequireNonEmpty("location")
			->ValidateCustom("volume_group", "KVM servers must have a volume group.", function($key, $value, $args, $handler) {
				return (!empty($value) || (!empty($handler->formdata["type"]) && $handler->formdata["type"] !== "kvm"));
			})
			->Done();
	}
	catch (FormValidationException $e)
	{
		$sErrors = $e->GetErrorMessages(array(
			"required" => array(
				"name" => "You must give the server a name.",
				"hostname" => "You must enter an IP or hostname.",
				"username" => "You must enter a superuser (eg. root)",
				"key" => "You must enter an SSH key.",
				"type" => "You must select a server type",
				"location" => "You must enter a location for this server."
			)
		));
	}
	
	if(empty($sErrors))
	{
		$sSSH = new Net_SSH2($_POST["hostname"]);
		$sKey = new Crypt_RSA();
		$sKey->loadKey($_POST["key"]);
		if($sSSH->login($_POST["username"], $sKey)) {
			$sKeyLocation = random_string(30).'.txt';
			file_put_contents("/var/feathur/data/keys/".$sKeyLocation, $_POST["key"]);
			$sServer = new Server(0);
			$sServer->uName = $_POST["name"];
			$sServer->uIPAddress = $_POST["hostname"];
			$sServer->uUser = $_POST["username"];
			$sServer->uKey = $sKeyLocation;
			$sServer->uType = $_POST["type"];
			$sServer->uQEMUPath = !empty($_POST["qemu"]) ? $_POST["qemu"] : "";
			$sServer->uVolumeGroup = !empty($_POST["volume_group"]) ? $_POST["volume_group"] : "";
			$sServer->uURL = !empty($_POST["status"]) ? $_POST["status"] : "http://{$_POST['hostname']}/uptime.php";
			$sServer->uLocation = $_POST["location"];
			$sServer->uStatusType = "full";
			$sServer->uDisplayMemory = 1;
			$sServer->uDisplayLoad = 1;
			$sServer->uDisplayHardDisk = 1;
			$sServer->uDisplayNetworkUptime = 1;
			$sServer->uDisplayHardwareUptime = 1;
			$sServer->uDisplayLocation = 1;
			$sServer->uDisplayHistory = 1;
			$sServer->uDisplayStatistics = 1;
			$sServer->uDisplayHS = 1;
			$sServer->uDisplayBandwidth = 1;
			$sServer->uContainerBandwidth = 1;
			$sServer->uHardwareUptime = 1;
			$sServer->uUpSince = 1;
			$sServer->InsertIntoDatabase();
			redirect("/admin");
		} else {
			$sErrors[] = "Could not connect to the server.";
		}
	}
}

$sPageCurrent = "addserver";
$sPageTitle = "Add Server";
$sPageContents = Templater::AdvancedParse($sAdminTemplate->sValue . "/addserver", $locale->strings, array("Errors" => $sErrors));
