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
@include("./paa.class.php");

class dcp extends paa{

	private $versionUrl = "http://www.haggybear.de/download/dcp";
	private $queryString;      
	private $allDyns;
	private $noDyns;
	private $saveTmp =  "\$dynhost[\"#HOSTNAME#\"] = array(\"token\" => \"#TOKEN#\",\"ipv4\" => \"#IPV4#\",\"orgipv4\" => \"#ORGIPV4#\",\"ipv6\" => \"#IPV6#\",\"orgipv6\" => \"#ORGIPV6#\",\"ipv6iid\" => \"#IPV6IID#\",\"ipv6prefixmask\" => \"#IPV6PREFIXMASK#\");\n";
	public $hasAccess = false;

	function __construct($sess,$getVars,$db){
		parent::paa($sess,@$getVars["dom_name"],$db);
		parent::openDatabase();
		parent::setPleskSkin();
		parent::setPleskAllowed();
		$this->domainId = @$getVars["dom_id"];
		if(PSA_VERSION >= 10){
			$this->domainId = $_SESSION["subscriptionId"]->current;
			$this->psa10_domainGrab();   
		}
		parent::setPleskAllowedDomains($getVars["cl_id"]);
		$this->cleanProperties();

		if($this->plesk_session->chkLevel(IS_ADMIN) || file_exists("dbs/".$this->plesk_domain)) $this->hasAccess = true; 
	}

	function __destruct(){
		parent::closeDatabase();
	}

	function setViewPage($vp){
		$this->viewPage = (empty($vp))?"domain":$vp;
	}

	function setSqlEntry($host,$ipv4,$ipv6='',$ipmode='all'){
		if($ipv4 != '' AND $ipmode != 'ipv6only'){
			mysql_query('UPDATE dns_recs SET displayVal = "'.$ipv4.'", val = "'.$ipv4.'" where host ="'.$host.'." and type = "A"');
		} else {
			mysql_query('UPDATE dns_recs SET displayVal = "127.0.0.1", val = "127.0.0.1" where host ="'.$host.'." and type = "A"');
		}

		if($ipv6 != '' AND $ipmode != 'ipv4only'){
			mysql_query('UPDATE dns_recs SET displayVal = "'.$ipv6.'", val = "'.$ipv6.'" where host ="'.$host.'." and type = "AAAA"');
		} else {
			mysql_query('UPDATE dns_recs SET displayVal = "::1", val = "::1" where host ="'.$host.'." and type = "AAAA"');
		}
	}

	function checkRelease(){
		echo (file_exists("dbs/".$this->plesk_domain))?1:0;
	}

	function newrelease(){
		if(!$this->plesk_session->chkLevel(IS_ADMIN))return;  
		if($_POST["release"]==0) touch("dbs/".$this->plesk_domain);
		if($_POST["release"]==1) unlink("dbs/".$this->plesk_domain);
	}

	function newtoken(){
		require("dbs/hosts.php");
		$this->deactivate();
		$this->activate($dynhost[$_POST["hostname"]]["ipv4"],md5(uniqid($_POST["hostname"], true)),$dynhost[$_POST["hostname"]]["orgipv4"],$dynhost[$_POST["hostname"]]["ipv6"],$dynhost[$_POST["hostname"]]["orgipv6"]);
	}

	function cleanProperties(){
		require("dbs/hosts.php");
		if(filemtime("dbs/") > time()-86400){
			return;
		}
		$allDoms = array();
		$result = mysql_query("SELECT name from psa.domains");
		while($data = mysql_fetch_object($result)){
			$allDoms[] = $data->name;
		}
		while(list($k)=each($dynhost)){
			$tmp = explode(".",$k);
			unset($tmp[0]);
			$dom = implode(".",$tmp);
			if(!in_array($dom,$allDoms)){
				$_POST["hostname"] = $k;
				$this->deactivate();                      
			}
		}
		$handle = opendir('dbs');
		while (false !== ($file = readdir($handle))) {
			if ($file != "." && $file != ".." && $file != "hosts.php") {
				if(!in_array($file,$allDoms)) unlink("dbs/".$file);
			}
		}
		closedir($handle);
		touch("dbs/");
	}

	function newip(){
		require("dbs/hosts.php");
		$this->deactivate();
		$this->activate($_POST["ipv4"],$dynhost[$_POST["hostname"]]["token"],$dynhost[$_POST["hostname"]]["orgipv4"],$_POST["ipv6"],$dynhost[$_POST["hostname"]]["orgipv6"]);
		$this->setSqlEntry($_POST["hostname"],$_POST["ipv4"],$_POST["ipv6"]);
		exec('sh/wrapper "1" "refreshdns" "'.PSA_PATH.'bin/dns" "'.$this->plesk_domain.'"');
	}               

	function activate($ipv4,$token,$orgipv4,$ipv6='',$orgipv6=''){
		$saveTmp = str_replace("#HOSTNAME#",$_POST["hostname"],$this->saveTmp);
		$saveTmp = str_replace("#TOKEN#",$token,$saveTmp);
		$saveTmp = str_replace("#IPV4#",str_replace("::ffff:","",$ipv4),$saveTmp);
		$saveTmp = str_replace("#ORGIPV4#",str_replace("::ffff:","",$orgipv4),$saveTmp);
		$saveTmp = str_replace("#IPV6#",$ipv6,$saveTmp);
		$saveTmp = str_replace("#ORGIPV6#",$orgipv6,$saveTmp);
		$saveTmp = str_replace("#IPV6IID#",'',$saveTmp);
		$saveTmp = str_replace("#IPV6PREFIXMASK#",'56',$saveTmp);

		$this->activateCleanup($_POST["hostname"]);

		$cfg = file_get_contents("dbs/hosts.php");
		$cfg = str_replace("?>",$saveTmp."?>",$cfg);
		file_put_contents("dbs/hosts.php",$cfg);
	}

	function activateCleanup($hostname){   
		require("dbs/hosts.php");            
		$cfg = file("dbs/hosts.php");
		$cfgNew = array();
		for($i=0;$i<count($cfg);$i++){
			if(!stristr($cfg[$i],'["'.$hostname.'"]')) $cfgNew[] = $cfg[$i];
		}
		file_put_contents("dbs/hosts.php",implode("",$cfgNew));
	}

	function activateFirst($ipv4,$ipv6=''){
		$this->setSqlEntry($_POST["hostname"],str_replace("::ffff:","",$ipv4),$ipv6);  
		exec('sh/wrapper "1" "refreshdns" "'.PSA_PATH.'bin/dns" "'.$this->plesk_domain.'"');
		$domain = explode(".",$_POST["hostname"]);
		$dom = $domain[count($domain)-2].'.'.$domain[count($domain)-1];

		$sub = str_replace('.'.$dom,"",implode(".",$domain));

		$vhostPath = (PSA_VERSION >= 11.5 && is_dir(VHOSTS_PATH.'/system'))?VHOSTS_PATH.'/system':VHOSTS_PATH;

		exec('sh/wrapper "1" "createdns" "'.PSA_PATH.'bin/subdomain" "dynupd.'.$sub.'" "'.$dom.'" "'.PSA_PATH.'admin/htdocs'.DCP_PATH.'/dynupd" "'.PSA_PATH.'" "'.$vhostPath.'"');
	}

	function deactivateLast(){
		require("dbs/hosts.php");            
		$cfg = file("dbs/hosts.php");
		exec('sh/wrapper "1" "deletedns" "'.PSA_PATH.'bin/domain" "dynupd.'.$_POST["hostname"].'"');
		$cfgNew = array();
		for($i=0;$i<count($cfg);$i++){
			if(!stristr($cfg[$i],'["'.$_POST["hostname"].'"]')) $cfgNew[] = $cfg[$i];
		}
		file_put_contents("dbs/hosts.php",implode("",$cfgNew));
	}

	function deactivate(){ 
		require("dbs/hosts.php");  
		$this->setSqlEntry($_POST["hostname"],$dynhost[$_POST["hostname"]]["orgipv4"],$dynhost[$_POST["hostname"]]["orgipv6"]);
		exec('sh/wrapper "1" "refreshdns" "'.PSA_PATH.'bin/dns" "'.$this->plesk_domain.'"');
	}

	function checkVersion($v){
		$url = $this->versionUrl.".txt";
		$p = fopen($url,"r");
		$ver  = fgets($p,16);
		fclose($p);

		if($this->plesk_session->chkLevel(IS_ADMIN) && AUTOUPDATE){
			if($v != $ver){ 
				echo "<a href=\"#\" id=\"updateCheck\">".str_replace("{VER}",$ver,DCP_VERSION_UPD)."</a>";
				return;
			}
		}

		if($v != $ver){
			echo "<a href=\"".$this->versionUrl.".zip\">".str_replace("{VER}",$ver,DCP_VERSION_NOK)."</a>";
			return;
		}

		echo DCP_VERSION_OK;
	}

	function setQueryString($qs){
		$expr = '/statmessage=(.*)&/i';
		$qs = preg_replace($expr, '', $qs);
		$expr = '/&todo=(.*)/i';
		$qs = preg_replace($expr, '', $qs);
		$expr = '/&admin=(.*)/i';
		$qs = preg_replace($expr, '', $qs);
		$expr = '/&action=(.*)/i';
		$qs = preg_replace($expr, '', $qs);
		$this->queryString = $qs;
	}

	function getQueryString(){
		echo $this->queryString;
	}

	function getAllDomainsAndAliases(){
		$doms[] = $this->plesk_domain;
		$domYes = array();
		$domNo = array();
		$ips = array();

		$result = mysql_query("SELECT ip_address from IP_Addresses");
		while($data = mysql_fetch_object($result)){
			$ips[] = $data->ip_address;
		}


		$hasDomAlias = false;

		if(mysql_num_rows(mysql_query("SHOW TABLES LIKE 'domainaliases'"))>0) $hasDomAlias = true;
		$result = mysql_query("SELECT a.id, a.name, b.id FROM psa.domainaliases AS a, psa.domains AS b WHERE b.name = '".$this->plesk_domain."' AND b.id = a.dom_id");
		if($result && $hasDomAlias){
			while($data = mysql_fetch_object($result)){
				$doms[] = $data->name;
			}
		}
		for($i=0;$i<count($doms);$i++){
			$out = array();
			exec('sh/wrapper "1" "checkdns" "'.$doms[$i].'"',$out);

			for($ii=0;$ii<count($out);$ii++){
				if(stristr($out[$ii],"nameserver")){
					$tmp = explode("=",$out[$ii]);
					break;
				}
			}
			$ip = gethostbyname(substr(trim($tmp[1]),0,strlen(trim($tmp[1]))-1));

			if(!in_array($ip,$ips))$domNo[] = $doms[$i];
		}

		$result = mysql_query("SELECT name FROM domains WHERE parentDomainId = '".$this->domainId."'");
		if($result){
			while($data = mysql_fetch_object($result)){
				if(stristr($data->name,"dynupd.")) continue;
				for($i=0;$i<count($doms);$i++){
					$allDyns[] = str_replace($this->plesk_domain,"",$data->name).$doms[$i].".";
				}
				for($i=0;$i<count($domNo);$i++){
					$noDyns[] = str_replace($this->plesk_domain,"",$data->name).$domNo[$i].".";
				}
			}
		}

		$this->allDyns = $allDyns;
		$this->noDyns = $noDyns;
	}

	function getDnsEntries(){
		require("dbs/hosts.php");
		$json = array();
		$cells = array();
		$result = mysql_query("SELECT ip4.host, ip4.val AS val4, ip4.time_stamp AS time_stamp4, ip6.val AS val6, ip6.time_stamp AS time_stamp6
			FROM dns_recs ip4
			INNER JOIN dns_recs ip6
			ON ip4.host = ip6.host
			WHERE ip4.type = 'A' AND ip6.type = 'AAAA' 
			AND ip4.host IN ('".implode("','",$this->allDyns)."')
			AND ip6.host IN ('".implode("','",$this->allDyns)."')
			GROUP BY host;");

		$json["total"] = mysql_num_rows($result);
		$i=0;
		while($data = mysql_fetch_object($result)){
			$i++;
			$token = "";
			if($i > ($_POST["rows"]*$_POST["page"]) ||
			$i <= ($_POST["rows"]*($_POST["page"]-1))) continue;

			$hostname = strtolower(substr($data->host,0,strlen($data->host)-1));
			if(is_array($dynhost[$hostname])) $token = $dynhost[$hostname]["token"];
			$view = (@in_array($data->host,$this->noDyns))?false:true;
			$aktiv = (empty($token))?'<span style="color:#FF0000">NEIN</a>':'<span style="color:#006600">JA</span>';
			if(!$view)$aktiv="NEIN";
			$cells[] = array("aktiv"=>$aktiv,
				"view"=>$view,
				"hostname"=>$hostname,
				"aktip"=>$data->val4,
				"lastupd"=>date(TIME_SCHEME,strtotime($data->time_stamp4)),
				"update"=>(empty($token))?'':$token);
		}
		$json["rows"] = $cells;
		echo json_encode($json);
	}

}
?>
