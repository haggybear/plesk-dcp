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

require("dbs/hosts.php");

$url = parse_url($_SERVER["SCRIPT_URI"]);
$hostname = str_replace("dynupd.","",$url["host"]);

//$hostname = isset($_GET["hostname"]) ? $_GET["hostname"] : ''; 
//$hostname = htmlspecialchars($hostname);

//$user = isset($_GET["user"]) ? $_GET["user"] : ''; 
//$user = htmlspecialchars($user);

//$pw = isset($_GET["pw"]) ? $_GET["pw"] : ''; 
//$pw = htmlspecialchars($pw);

$token = isset($_GET["token"]) ? $_GET["token"] : ''; 
$token = htmlspecialchars($token);

$ipmode = isset($_GET["ipmode"]) ? $_GET["ipmode"] : ''; 
$ipmode = htmlspecialchars($ipmode);
switch ($ipmode) {
    case "ipv4only":
        $ipmode = "ipv4only";
        break;
    case "ipv6only":
        $ipmode = "ipv6only";
        break;
 	default:
        $ipmode = "all";
        break;
}

$ipv6 = isset($_GET["ipv6"]) ? $_GET["ipv6"] : ''; 
if (!filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
	$ipv6 = '';
	$ipmode = 'ipv4only';
}

$ipv4 = isset($_GET["ipv4"]) ? $_GET["ipv4"] : ''; 
if (!filter_var($ipv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
	$ipv4 = str_replace("::ffff:","",$_SERVER["REMOTE_ADDR"]);
}

if(!empty($token) && $token == $dynhost[$hostname]["token"]){
	exec('sh/wrapper "1" "configs"',$out);
	$outNew = array();
	for($i=0;$i<count($out);$i++){
		$tmp = trim($out[$i]);
		if(!empty($tmp))$outNew[]=$tmp;
	}

	DEFINE("DB_PWD", $outNew[0]);
	DEFINE("PSA_PATH", $outNew[1]);
	DEFINE("VHOSTS_PATH", $outNew[2]);
	require("../config.inc.php");
	require("../paa.class.php");
	require("../dcp.class.php");

	$dom = explode(".",$hostname);
	$domain = $dom[count($dom)-2].".".$dom[count($dom)-1];

	$dcp = new dcp(new s(),$_GET,array(DB_HOST,DB_NAME,DB_USR,DB_PWD));	
	$_POST["hostname"] = $hostname;
	$dcp->deactivate();
	$dcp->activate($ipv4,$dynhost[$hostname]["token"],$dynhost[$hostname]["orgipv4"],$ipv6,$dynhost[$hostname]["orgipv6"]);
	$dcp->setSqlEntry($hostname,$ipv4,$ipv6,$ipmode);
	exec('sh/wrapper "1" "refreshdns" "'.PSA_PATH.'/bin/dns" "'.$domain.'"');

	echo "OK";
} else {
	echo "ERROR";
}


class s{
	function chkLevel($i){
		return true;
	}
}
?>