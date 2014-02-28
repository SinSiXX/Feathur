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

if($router->uMethod == "post")
{
	$router->uVariables["ajax"] = true; /* POST requests to this module always result in an AJAX response. */
	$sErrors = array();
	
	try
	{
		$uDataSource = isset($uApiData) ? $uApiData : $_POST;
		$handler = new CPHPFormHandler($uDataSource);
		
		$handler
			->RequireNonEmpty("email")
			->RequireNonEmpty("username")
			->ValidateEmail("email")
			->AbortIfErrors()
			->ValidateCustom("email", "An account already exists for the given e-mail address.", function($key, $value, $args, $handler) {
				global $database;
				return !$database->CachedQuery("SELECT * FROM accounts WHERE `email_address` = :EmailAddress", array('EmailAddress' => $value));
			})
			->ValidateCustom("username", "An account already exists with the given username.", function($key, $value, $args, $handler) {
				global $database;
				return !$database->CachedQuery("SELECT * FROM accounts WHERE `username` = :Username", array('Username' => $value));
			})
			->Done();
	}
	catch (FormValidationException $e)
	{
		$sErrors = $e->GetErrorMessages(array(
			"required" => array(
				"email" => "You must enter an e-mail address.",
				"username" => "You must enter a username."
			),
			"email" => array(
				"email" => "The e-mail address you entered is not valid."
			)
		));
	}
	
	if(empty($sErrors))
	{
		/* There used to be something replacing spaces in the e-mail address with
		 * plus (+) signs. What was that for? */
		$sActivationCode = random_string(120);
	
		$sUser = new User(0);
		$sUser->uUsername = $handler->GetValue("username");
		$sUser->uEmailAddress = $handler->GetValue("email");
		$sUser->uPassword = "-1";
		$sUser->uActivationCode = $sActivationCode;
		$sUser->InsertIntoDatabase();
		
		$uPassword = $handler->GetValue("password"); /* Can't empty() this directly... sigh, PHP. */
		
		if(empty($uPassword)) /* This is not yet used. Could be useful for setting password via API. */
		{
			$sEmailResult = Core::SendEmail($sUser->sEmailAddress, "Feathur Activation Email", "new_user", array(
				"email" => urlencode($sUser->uEmailAddress), /* Need version without HTML entities. Why URL-encoded? */
				"activation_code" => urlencode($sActivationCode)
			));
			
			if($sEmailResult === true)
			{
				$sSuccess = true;
			}
			else
			{
				$sPageContents = $sEmailResult["content"]; /* Error message is currently contained in the return value. TODO: Look into using exceptions for this. */
				$sSuccess = false;
			}
		}
		else
		{
			$sSuccess = true;
		}
		
		if($sSuccess == true)
		{
			$sPageContents = "User created.";
			$sJsonVariables["created"] = 1;
			$sJsonVariables["user_id"] = $sCreatedUser->sId;
		}
	}
	else
	{
		$sPageContents = implode("<br>", $sErrors);
	}
}
else
{
	$sPageCurrent = "adduser";
	$sPageTitle = "Add User";
	$sPageContents = Templater::AdvancedParse($sAdminTemplate->sValue . "/adduser", $locale->strings, array("Error" => $sErrors));
}
