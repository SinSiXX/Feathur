<?php
/*
 * Copyright (c) 2014 Feathur Developers
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

$_APP = true;
require("./includes/loader.php");

$sPageTitle = "";
$sPageContents = "";
$sPageCurrent = "about";
$sPageErrors = array();

$router = new CPHPRouter();

$router->allow_slash = true;
$router->ignore_query = true;

/* Access levels defined here, to make it easier to change them later on. */
define("ACCESS_USER", 0);
define("ACCESS_ADMIN", 7);

$router->routes = array(
	0 => array(
		/* Admin section */
		"^/admin$" => array(
			"target" => "modules/admin/dashboard.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "admin"
		),
		"^/admin/search$" => array(
			"target" => "modules/admin/list.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "search"
		),
		/* Admin section -> Servers */
		"^/admin/servers$" => array(
			"target" => "modules/admin/list.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "servers"
		),
		"^/admin/servers/([0-9]+)$" => array(
			"target" => "modules/admin/servers/view.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "servers"
		),
		"^/admin/servers/([0-9]+)/vps$" => array(
			"target" => "modules/admin/list.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "servers",
			"_lookup" => true
		),
		"^/admin/servers/add$" => array(
			"target" => "modules/admin/servers/add.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "servers"
		),
		/* Admin section -> Users */
		"^/admin/users$" => array(
			"target" => "modules/admin/list.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "users"
		),
		"^/admin/users/([0-9]+)$" => array( /* There is no functionality for this right now? */
			"target" => "modules/admin/users/view.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "users"
		),
		"^/admin/users/([0-9]+)/vps$" => array(
			"target" => "modules/admin/list.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "users",
			"_lookup" => true
		),
		"^/admin/users/add$" => array(
			"target" => "modules/admin/users/add.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "users"
		),
		/* Admin section -> Configuration */
		"^/admin/settings$" => array(
			"target" => "modules/admin/settings.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "configuration"
		),
		"^/admin/update-settings$" => array(
			"target" => "modules/admin/update_settings.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "configuration"
		),
		/* Admin section -> Templates / ISOs */
		"^/admin/templates$" => array(
			"target" => "modules/admin/templates/index.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "configuration"
		),
		"^/admin/templates/(kvm|openvz)$" => array(
			"target" => "modules/admin/templates/list.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "configuration"
		),
		/* Possibly useful later, to for example show a list of all VPSes currently using a template.
		"^/admin/templates/(kvm|openvz)/([0-9]+)$" => array(
			"target" => "modules/admin/templates/view.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "configuration"
		), */
		"^/admin/templates/(kvm|openvz)/([0-9]+)/edit$" => array(
			"target" => "modules/admin/templates/edit.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "configuration"
		),
		"^/admin/templates/(kvm|openvz)/([0-9]+)/delete$" => array(
			"target" => "modules/admin/templates/delete.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "configuration"
		),
		"^/admin/templates/(kvm|openvz)/add$" => array(
			"target" => "modules/admin/templates/add.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "configuration"
		),
		/* Admin section -> IP pools */
		"^/admin/ip-pools$" => array(
			"target" => "modules/admin/ip_pools/index.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "configuration"
		),
		"^/admin/ip-pools/ipv(4|6)$" => array(
			"target" => "modules/admin/ip_pools/list.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "configuration"
		),
		"^/admin/ip-pools/ipv(4|6)/add$" => array(
			"target" => "modules/admin/ip_pools/add.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "configuration"
		),
		"^/admin/ip-pools/ipv(4|6)/edit$" => array(
			"target" => "modules/admin/ip_pools/edit.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "configuration"
		),
		"^/admin/ip-pools/ipv(4|6)/delete$" => array(
			"target" => "modules/admin/ip_pools/delete.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "configuration"
		),
		"^/admin/ip-pools/ipv(4|6)/([0-9]+)$" => array(
			"target" => "modules/admin/ip_pools/pool_view.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "configuration"
		),
		"^/admin/ip-pools/ipv(4|6)/([0-9]+)/add-ip$" => array(
			"target" => "modules/admin/ip_pools/pool_add_ip.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "configuration"
		),
		"^/admin/ip-pools/ipv(4|6)/([0-9]+)/delete-ip$" => array(
			"target" => "modules/admin/ip_pools/pool_delete_ip.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "configuration"
		),
		"^/admin/ip-pools/ipv(4|6)/([0-9]+)/add-server$" => array(
			"target" => "modules/admin/ip_pools/pool_add_server.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "configuration"
		),
		"^/admin/ip-pools/ipv(4|6)/([0-9]+)/delete-server$" => array(
			"target" => "modules/admin/ip_pools/pool_delete_server.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "configuration"
		),
		/* Admin section -> VPSes (technically a tab on the 'client' page) */
		"^/admin/vps$" => array(
			"target" => "modules/admin/list.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "vps"
		),
		"^/admin/vps/create$" => array(
			"target" => "modules/admin/vps/create.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "vps"
		),
		"^/admin/vps/(kvm|openvz)$" => array( /* This shows a list of VPSes, filtered by type */
			"target" => "modules/admin/list.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "vps",
			"_lookup" => true
		),
		"^/admin/vps/(suspended)$" => array( /* This shows a list of suspended VPSes */
			"target" => "modules/admin/list.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_page_type" => "vps",
			"_lookup" => true
		),
		"^/admin/vps/([0-9]+)/edit$" => array(
			"target" => "modules/admin/vps/edit.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_ajax" => true
		),
		"^/admin/vps/([0-9]+)/transfer$" => array(
			"target" => "modules/admin/vps/transfer.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_ajax" => true
		),
		"^/admin/vps/([0-9]+)/terminate$" => array(
			"target" => "modules/admin/vps/terminate.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_ajax" => true
		),
		"^/admin/vps/([0-9]+)/add-ip$" => array(
			"target" => "modules/admin/vps/add_ip.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_ajax" => true
		),
		"^/admin/vps/([0-9]+)/delete-ip$" => array(
			"target" => "modules/admin/vps/delete_ip.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_ajax" => true
		),
		"^/admin/vps/([0-9]+)/add-custom-ip$" => array( /* This is for assigning an IP manually; that is, <input type="text" />, and not using an IP pool. */
			"target" => "modules/admin/vps/add_custom_ip.php",
			"authenticator" => "authenticators/access_level.php",
			"auth_error" => "modules/error/insufficient_access.php",
			"_access_level" => ACCESS_ADMIN,
			"_ajax" => true
		),
		/* Client section */
		"^/$" => array(
			"target" => "modules/client/vps/list.php",
			"authenticator" => "authenticators/logged_in.php",
			"auth_error" => "modules/error/not_logged_in.php",
			"_page_type" => "vps"
		),
		"^/about$" => array( /* This is only shown to logged-in users; might prove useful in the future, for example if the About page will at some point show version info. */
			"target" => "modules/client/about.php",
			"authenticator" => "authenticators/logged_in.php",
			"auth_error" => "modules/error/not_logged_in.php"
		),
		"^/profile$" => array(
			"target" => "modules/client/profile.php",
			"authenticator" => "authenticators/logged_in.php",
			"auth_error" => "modules/error/not_logged_in.php"
		),
		"^/logout$" => array(
			"target" => "modules/client/logout.php",
			"authenticator" => "authenticators/logged_in.php",
			"auth_error" => "modules/error/not_logged_in.php"
		),
		"^/login$" => array(
			"target" => "modules/client/login.php"
		),
		"^/vps/([0-9]+)$" => array(
			"target" => "modules/client/vps/view.php",
			"authenticator" => "authenticators/vps_owner.php",
			"auth_error" => "modules/error/not_logged_in.php",
			"_page_type" => "vps"
		),
		"^/vps/([0-9]+)/settings$" => array(
			"target" => "modules/client/vps/settings.php",
			"authenticator" => "authenticators/vps_owner.php",
			"auth_error" => "modules/error/not_logged_in.php",
			"_ajax" => true
		),
		"^/vps/([0-9]+)/start$" => array(
			"target" => "modules/client/vps/start.php",
			"authenticator" => "authenticators/vps_owner.php",
			"auth_error" => "modules/error/not_logged_in.php",
			"_ajax" => true
		),
		"^/vps/([0-9]+)/restart$" => array(
			"target" => "modules/client/vps/restart.php",
			"authenticator" => "authenticators/vps_owner.php",
			"auth_error" => "modules/error/not_logged_in.php",
			"_ajax" => true
		),
		"^/vps/([0-9]+)/stop$" => array(
			"target" => "modules/client/vps/stop.php",
			"authenticator" => "authenticators/vps_owner.php",
			"auth_error" => "modules/error/not_logged_in.php",
			"_ajax" => true
		),
		"^/vps/([0-9]+)/kill$" => array( /* What does this do? */
			"target" => "modules/client/vps/kill.php",
			"authenticator" => "authenticators/vps_owner.php",
			"auth_error" => "modules/error/not_logged_in.php",
			"_ajax" => true
		),
		"^/vps/([0-9]+)/rebuild$" => array(
			"target" => "modules/client/vps/rebuild.php",
			"authenticator" => "authenticators/vps_owner.php",
			"auth_error" => "modules/error/not_logged_in.php",
			"_ajax" => true
		),
		"^/vps/([0-9]+)/command$" => array(
			"target" => "modules/client/vps/command.php",
			"authenticator" => "authenticators/vps_owner.php",
			"auth_error" => "modules/error/not_logged_in.php",
			"_ajax" => true
		),
	)
);

$router->RouteRequest();

if(!empty($router->uVariables["ajax"]))
{
	/* Asynchronous request, return as JSON */
	echo(json_encode(array(
		"content" => $sPageContents,
		"title" => $sPageTitle
	)));
}
else
{
	echo(Templater::AdvancedParse($sTemplate->sValue . "/master", $locale->strings, array(
		"Content" => $sPageContents,
		"Page" => $sPageCurrent,
		"Title" => $sPageTitle,
		"Errors" => $sPageErrors,
		"PageType" => isset($router->uVariables["page_type"]) ? $router->uVariables["page_type"] : "" /* Safe, because hardcoded values */
	)));
}
