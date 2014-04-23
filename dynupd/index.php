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
$host = str_replace("dynupd.","",$url["host"]);
$token = substr($url["path"],1);

if(!empty($token) && $token == $dynhost[$host]["token"]){
	 exec('sh/wrapper "1" "configs"',$out);
   $outNew = array();
   for($i=0;$i<count($out);$i++){
       $tmp = trim($out[$i]);
       if(!empty($tmp))$outNew[]=$tmp;
       }
   DEFINE("DB_PWD",$outNew[0]);
   DEFINE("PSA_PATH",$outNew[1]);
   DEFINE("VHOSTS_PATH",$outNew[2]);
	 require("../config.inc.php");
	 require("../paa.class.php");
	 require("../dcp.class.php");
	 
	 $dom = explode(".",$host);
	 $domain = $dom[count($dom)-2].".".$dom[count($dom)-1];
	 
	 $dcp = new dcp(new s(),$_GET,array(DB_HOST,DB_NAME,DB_USR,DB_PWD));	
	 $ip = str_replace("::ffff:","",$_SERVER["REMOTE_ADDR"]);
	 $_POST["hostname"] = $host;
	 $dcp->deactivate();
	 $dcp->activate($ip,$dynhost[$host]["token"],$dynhost[$host]["orgip"]);
	 $dcp->setSqlEntry($host,$ip);
	 exec('sh/wrapper "1" "refreshdns" "'.PSA_PATH.'/bin/dns" "'.$domain.'"');

	 echo "OK";
	 }
else{
	 echo "ERROR";
  }


class s{
      function chkLevel($i){
      	       return true;
               }
}
?>