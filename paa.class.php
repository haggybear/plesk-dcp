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

class paa{

      
      /* Make your database settings here, or instantiate class with "paa(Object plesk_session, String domainname, array[host,dbname,dbuser,dbpass]) */
      var $plesk_database_host = "localhost";
      var $plesk_database_name = "plesk_db_name";
      var $plesk_database_user = "plesk_db_user";
      var $plesk_database_pass = "plesk_db_pass";
      
      
      /* Do not edit after here */
      var $plesk_session;
      var $plesk_domain;
      var $plesk_allowed_domains;
      var $plesk_skin;
      var $plesk_allowed = false;
      
      var $plesk_only_adm = true;
      
      var $plesk_db_conn;
      
      var $plesk_fe_props = array();
      
      var $plesk_smtp_type;

      function paa($sess,$dom=null,$db=null){
	
		 $this->plesk_session = $sess;
		             if(!defined("IS_ADMIN")) define("IS_ADMIN",1);
		 
                 $this->plesk_domain = $dom;
	         if(is_array($db)){
                  $this->plesk_database_host = $db[0];
                  $this->plesk_database_name = $db[1];
                  $this->plesk_database_user = $db[2];
                  $this->plesk_database_pass = $db[3];
                 }

               }
               
      function feProps($fe){
      	
      	       $fe = explode(",",$fe);
      	       $default["css"] = "normal";
      	       $default["style"] = "none";
      	       
      	       for($i=0;$i<count($fe);$i++){
      	       	
      	       	   if(empty($_COOKIE[$fe[$i]."_fe"])){
      	       	      $this->plesk_fe_props[$fe[$i]."_css"] = $default["css"];
      	       	      $this->plesk_fe_props[$fe[$i]."_style"] = $default["style"];
      	       	   }else{
      	       	   
      	       	     $cook = explode(":",$_COOKIE[$fe[$i]."_fe"]);
      	       	   
      	       	     $this->plesk_fe_props[$fe[$i]."_css"] = $cook[1];
      	       	     $this->plesk_fe_props[$fe[$i]."_style"] = $cook[0];
      	       	     }
      	       	   
      	       	  }
      	       }

      function openDatabase(){
               $this->plesk_db_conn = @mysql_connect($this->plesk_database_host,$this->plesk_database_user,$this->plesk_database_pass) or die ("No connection.");
               mysql_select_db($this->plesk_database_name) or die("No database selected.");
               }
               
      function closeDatabase(){
               @mysql_close($this->plesk_db_conn);
               }
            
      function setPleskSkin(){
      
               if(PSA_VERSION < 11.5){
               $sql = "SELECT * from misc where param='admin_skin_id'";
	       $ret = mysql_query($sql);
	       if (!$ret)
               $skin_id=8;
               else 
               if ($row_db = mysql_fetch_array($ret))
               $skin_id=$row_db["val"];
	
	
	       $sql = "SELECT * from Skins where id='$skin_id'";
	
	       $ret = mysql_query($sql);
	       if (!$ret)
	       $psa_skin = "winxp.new.compact";
	       else
               if ($row_db = mysql_fetch_array($ret))
               $this->plesk_skin = $row_db["place"];
               }
               else{
               $sql = "SELECT val from misc where param='theme_skin'";
	       $ret = mysql_query($sql);
	       $data = mysql_fetch_array($ret);
	       
	       if($data["val"]=="default"){
	          $this->plesk_skin = "theme";
	          }
	       else{
	          $this->plesk_skin = "theme-skins/".$data["val"];
	         }
               }
               }
               
      function setPleskAllowed(){
 

	       if($this->plesk_session->chkLevel(IS_ADMIN)){
                  $this->plesk_allowed = true;
                  return;
                  }

               if($this->plesk_session->_login == $this->plesk_domain) {
                  $this->plesk_allowed = true;
                  return;
                  }
	 
               $r = mysql_query("select a.name from domains as a, clients as b where a.cl_id = b.id and b.login ='".$this->plesk_session->_login."' and a.name = '".$this->plesk_domain."'");
	       if(mysql_num_rows($r)>0){
	       	  $this->plesk_allowed = true;
                  return;
                  }

               if(PSA_VERSION >= 10){
                  $r = mysql_query("SELECT c.name FROM smb_users AS a, smb_roles AS b,domains AS c WHERE a.login = '".$this->plesk_session->_login."' AND a.roleId = b.id AND b.ownerId = c.cl_id and (b.name LIKE '%".$this->plesk_domain."%' or b.name='admin')");
                  if(mysql_num_rows($r)>0){
                     $this->plesk_allowed = true;
                     return;
                     }
                  }
	 
               }

      function psa10_domainGrab(){
    	       $r = mysql_query("select name from domains where id ='".$_SESSION["subscriptionId"]->current."'");
               $res = mysql_fetch_object($r);
               $this->plesk_domain = $res->name;
               $this->setPleskAllowed();
               }               
               
      function setPleskAllowedDomains($id){
      	
      	       if($this->plesk_session->chkLevel(IS_ADMIN)){
                  $sql = "SELECT name,id,cl_id FROM domains WHERE cl_id = '$id'";
                  }
               else if($this->plesk_session->_login == $this->plesk_domain) {
                  $this->plesk_allowed_domains[] = $this->plesk_domain;
                  return;
                  }
               else{
                  $sql = "SELECT a.name, a.id, a.cl_id FROM domains AS a, clients AS b WHERE a.cl_id = b.id AND b.login = '".$this->plesk_session->_login."'";
                 }     	      
      	       
      	
      	       $r = mysql_query($sql); 
      	       while($data = mysql_fetch_object($r)){
      	       	     $this->plesk_allowed_domains[$data->name] = "?cl_id=".$data->cl_id."&dom_name=".$data->name."&dom_id=".$data->id."&previous_page=domains";
      	       	    }
      	       	  
      	       }


      function getPleskLogin(){
               return $this->plesk_session->_login;
               }

      function getPleskAllowed(){
               return $this->plesk_allowed;
               }

      function getPleskSkin(){
               return $this->plesk_skin;
               }
	       
      function setOnlyAdm($var){
               $this->plesk_only_adm=$var;
               }
      
      function getOnlyAdm(){
               return $this->plesk_only_adm;
               }
	       
         }


?>