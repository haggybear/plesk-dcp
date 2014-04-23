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
apc_clear_cache();
session_start();
include("./config.inc.php");
include("./version.php");
include("./lang/".LANG.".inc.php");

if(!$session->chkLevel(1)){
	echo "0";
	exit;
	}

$_SESSION["stopupd"] = false;

if($_GET["step"]==1){
  exec('sh/wrapper "2" "0"');
  if(file_exists("dcp_update.zip")){
     echo "1";  	
  	 }
  else{
  	 echo "0";
  	 $_SESSION["stopupd"] = true; 
    }
  exit;
}

if($_GET["step"]==2){
	 if($_SESSION["stopupd"]){
	 	  echo "0";
	 	  exit;
	 	  }
   $zip = zip_open("dcp_update.zip");
   if ($zip) {
   
       while ($zip_entry = zip_read($zip)) {
              $file = basename(zip_entry_name($zip_entry));
    	         if($file == "version.php"){
                    if (zip_entry_open($zip, $zip_entry, "r")) {
                         $_SESSION["aktVer"] = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));
                         zip_entry_close($zip_entry);
                       }
                    }
	      }		
      }
   else{
   echo "0";
   $_SESSION["stopupd"] = true;
   exit;	
   }				              
   zip_close($zip);
   echo "1";
   exit;
}

if($_GET["step"]==3){
 if($_SESSION["stopupd"]){
	 	  echo "0";
	 	  exit;
	 	  }
  $weiter = "1";
  exec('sh/wrapper "2" "2"');
  $fp = fopen ("version.php", "r");
  $insVer = fread ($fp, filesize ("version.php"));
  fclose ($fp);
  if($insVer != $_SESSION["aktVer"]) $weiter = "0";

  if(file_exists("config.new.txt")){
    ob_start();
    include("config.new.txt");
    $conf = ob_get_contents();
    ob_end_clean();
    $conf = "<?php\n$conf\n?>";
    exec("sh/wrapper '2' '2a' '".$conf."'");
    }

  $_SESSION["stopupd"] = ($weiter)?false:true;
  echo $weiter;
  exit;
}

if($_GET["step"]==4){
	if($_SESSION["stopupd"]){
	   echo "0";
	   exit;
	   }
  exec('sh/wrapper "2" "3"');
  echo (file_exists("dcp_update.zip"))?"0":"1";
  exit;
  }

if($_GET["step"]==5){
  $infoTxt = file("INSTALL.txt");
  $infoTxtOut = false;
  $aktVer =  str_replace("<?php","",$_SESSION["aktVer"]);
  $aktVer =  str_replace('define("DCP_VERSION","','',$aktVer);
  $aktVer =  str_replace('");','',$aktVer);
  $aktVer =  str_replace("?>","",$aktVer);
  $aktVer = str_replace("{VER}","<u>".trim($aktVer)."</u>",UPDATE_DCP_SUCCESS);
  for($r=0;$r<count($infoTxt);$r++){
      if($infoTxtOut) $outTextUpd.=$infoTxt[$r];
      if(trim($infoTxt[$r])== "History:") $infoTxtOut = true;
      if(is_numeric(substr($infoTxt[$r],0,1))) $infoTxtOut = false;
      }
  echo $aktVer;
  echo "<hr>";
  echo $outTextUpd;
} 
?>