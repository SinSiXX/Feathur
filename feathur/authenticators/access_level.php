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

/* The user must also be logged in. */
require("authenticators/logged_in.php");

$sRequiredAccessLevel = (int) $router->uVariables["access_level"];

if($sUser->sPermissions >= $sRequiredAccessLevel)
{
	$sRouterAuthenticated = true;
}
