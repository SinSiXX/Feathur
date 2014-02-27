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

$sPageCurrent = "about";
$sPageTitle = "About Feathur";
$sPageContents = Templater::AdvancedParse($sTemplate->sValue . "/about", $locale->strings, array());
