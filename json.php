<?php
/*
Plesk DynDNS Control Panel (Version see version.php) - GUI for Plesk to build and administrate a DynDNS Service

Copyright (C) [2013 [Matthias Hackbarth / www.haggybear.de]

This program is free software; you can redistribute it and/or modify it under the terms of the GNU General Public License as 
published by the Free Software Foundation; either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty 
of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this program; if not, see <http://www.gnu.org/licenses/>.
*/
@apc_clear_cache();
@session_start();
include("./config.inc.php");
include("./version.php");
include("./lang/".LANG.".inc.php");
include("./dcp.class.php");

$dcp = new dcp($session,$_GET,array(DB_HOST,DB_NAME,DB_USR,DB_PWD));	
$dcp->setViewpage($_GET["action"]);

if($_GET["view"] == "hosts"){
   $dcp->getAllDomainsAndAliases();
   $dcp->getDnsEntries();

   }
   
if($_GET["do"] == "act"){
   $dcp->activate($_SERVER["REMOTE_ADDR"],md5(uniqid($_POST["hostname"], true)),$_POST["orgip"],0);
   $dcp->activateFirst($_SERVER["REMOTE_ADDR"]);
   } 
   
if($_GET["do"] == "deact"){
   $dcp->deactivate();
   $dcp->deactivateLast();
   }    

if($_GET["do"] == "token"){
   $dcp->newtoken();
   }
   
if($_GET["do"] == "ip"){
   $dcp->newip();
   }
   
if($_GET["do"] == "release"){
   $dcp->newrelease();
   }   
?>

