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

$dcp->setQueryString($_SERVER["QUERY_STRING"]);

?>
<html>
<head>
<title><?php echo DCP_ADMIN;?></title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1">
<script language="javascript" type="text/javascript" src="/javascript/common.js"></script>
<script language="javascript" type="text/javascript" src="/javascript/chk.js.php"></script>
<script language="javascript" type="text/javascript" src="js/jquery-1.7.1.min.js"></script>
<script language="javascript" type="text/javascript" src="js/jquery.easyui.min.js"></script>
<script language="javascript" type="text/javascript" src="js/jquery.flot.js"></script>
<script language="javascript" type="text/javascript" src="js/jquery.flot.pie.js"></script>

<script type="text/javascript">

var updStep = 0;
var updErg = 0;
var isRelease = <?php $dcp->checkRelease();?>;

function _body_onload(){
        loff();
        loadHosts();
        <?php if($session->chkLevel(IS_ADMIN) && AUTOUPDATE):?>
        $("#updateCheck").click(function() {
	       runUpdate();
        });
        <?php endif;?>

        $("#Rel_"+isRelease).show();
        
        }

function loadHosts(){
	$('#dynhosts').datagrid({
		url:"json.php?view=hosts",
		title:"DynDNS Hosts", 
		rownumbers:false,
 	        remoteSort:false,
                border:true,
 	        fitColumns:true,
 		pageList:[10,25,50],
 		pageSize:10, 			       
 	        pagination:true,
 	        singleSelect:true,
 	        rowStyler: function(index,row){
										 if (!row.view){
												 return 'color:#CCCCCC;';
												 }
					},
 	        columns:[[
 	                {field:'view',title:'<?php echo DCP_VIEW;?>',hidden:true,width:50,sortable:true},  
                        {field:'aktiv',title:'<?php echo DCP_AKTIV;?>',width:30,sortable:true},  
                        {field:'hostname',title:'<?php echo DCP_HOSTNAME;?>',width:150,sortable:true},
                        {field:'aktip',title:'<?php echo DCP_AKTIP;?>',width:75,sortable:true},
                        {field:'lastupd',title:'<?php echo DCP_LASTUPD;?>',width:100,sortable:true},
                        {field:'update',title:'<?php echo DCP_UPDATE;?>',width:375,sortable:true}]]
		});
        $('.datagrid-body').css("overflow-y","hidden");
}

function aktivate(){
         var row = $('#dynhosts').datagrid('getSelected');
         if(!row.view){
            $.messager.alert('<?php echo DCP_ERROR;?>','<?php printf(DCP_JS_HOST_NO_NAMESERVER,"'+row.hostname+'");?>','error');
            return;
            }
         if(row.update!=""){
            $.messager.alert('<?php echo DCP_ERROR;?>','<?php printf(DCP_JS_HOST_IS_ACTIVE,"'+row.hostname+'");?>','error');
            return;
            }
	 $.messager.confirm('<?php echo DCP_DDNS_ACTIVATE;?>', '<?php printf(DCP_JS_HOST_DO_ACT,"'+row.hostname+'");?>', function(r){  
                if (r){  
                    $('#dynhosts').datagrid('loading'); 
                    $.post("json.php?do=act", { "hostname": row.hostname,"orgip": row.aktip },
			  function(data){
				$('#dynhosts').datagrid('reload');
			  });
                }  
            });
	 }
	 
function deaktivate(){
         var row = $('#dynhosts').datagrid('getSelected');
         if(row.update==""){
            $.messager.alert('<?php echo DCP_ERROR;?>','<?php printf(DCP_JS_HOST_NOT_ACTIVE,"'+row.hostname+'");?>','error');
            return;
            }
	 $.messager.confirm('<?php echo DCP_DDNS_DEACTIVATE;?>', '<?php printf(DCP_JS_HOST_DO_DEACT,"'+row.hostname+'");?>', function(r){  
                if (r){
                    $('#dynhosts').datagrid('loading');   
                    $.post("json.php?do=deact", { "hostname": row.hostname },
			  function(data){
				$('#dynhosts').datagrid('reload');
			  });
                }  
            });
	 }	 

function newtoken(){
         var row = $('#dynhosts').datagrid('getSelected');
         if(row.update==""){
            $.messager.alert('<?php echo DCP_ERROR;?>','<?php printf(DCP_JS_HOST_NOT_ACTIVE,"'+row.hostname+'");?>','error');
            return;
            }
         $('#dynhosts').datagrid('loading');          
         $.post("json.php?do=token",{ "hostname": row.hostname }, function(data){$('#dynhosts').datagrid('reload');});
         }

<?php if($dcp->plesk_session->chkLevel(IS_ADMIN)):?>            
function release(){
         var t = [];
         var tt = [];
         t[0] = '<?php echo DCP_DDNS_RELEASE;?>';
         tt[0] = '<?php echo DCP_DDNS_RELEASE_SHORT;?>';
         t[1] = '<?php echo DCP_DDNS_REFUSE;?>';
         tt[1] = '<?php echo DCP_DDNS_REFUSE_SHORT;?>';
	 $.messager.confirm(tt[isRelease], t[isRelease],function(r){  
                if (r){  
		    $.post("json.php?do=release",{ "release": isRelease }, function(data){
		        oldIsRel = isRelease;
		        isRelease = (isRelease==0)?1:0;
		        $("#Rel_"+oldIsRel).fadeOut("slow",function(){$("#Rel_"+isRelease).fadeIn("slow");});
		        });
                }  
            });
         
         }         
<?php endif;?>
         
function newip(){
         var row = $('#dynhosts').datagrid('getSelected');
         if(row.update==""){
            $.messager.alert('<?php echo DCP_ERROR;?>','<?php printf(DCP_JS_HOST_NOT_ACTIVE,"'+row.hostname+'");?>','error');
            return;
            }
	 $.messager.prompt('<?php echo DCP_DDNS_NEWIP;?>', '<?php printf(DCP_JS_NEW_IP,"'+row.hostname+'");?>',function(r){  
                if (r){  
		    if(!ipOnly(r)){
		       $.messager.alert('<?php echo DCP_ERROR;?>','<?php echo DCP_JS_VALID_IP;?>','error');
                       return;
                       }                
                    $('#dynhosts').datagrid('loading'); 
                    $.post("json.php?do=ip", { "hostname": row.hostname , "ip": r},
			  function(data){
				$('#dynhosts').datagrid('reload');
			  });
                }  
            });
         $('.messager-input').val("<?php echo str_replace("::ffff:","",$_SERVER["REMOTE_ADDR"]);?>");
         $('.messager-input').css("margin-left","46px");
         $('.messager-input').css("width","110px");
	 }
	 

 function ipOnly(ipAddress) {
	  var pattern = /^[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}$/;
	  if (pattern.test(ipAddress)){
	      return true;
	      }
	  else{
	      return false;
	      }
	  }

<?php if($dcp->plesk_session->chkLevel(IS_ADMIN)):?>         
function runUpdate(){
	
	       ergs = new Array('<span class="block"><?php echo UPDATE_DCP_NOK;?></span>','<span class="pass"><?php echo UPDATE_DCP_OK;?></span>');
	       
	       tabs = new Array('DUMMY','#upd_down','#upd_conf','#upd_inst','#upd_done');
	       	       
	       if(updStep==0)$('#doUpdate').window('open');
	       updStep++;
	       
	       if(updStep>=5){
	       	  $.get('up.php?step='+updStep, function(data) {
	       	  	var ergStr = '<span class="block"><?php echo UPDATE_DCP_FAILED;?></span>';
              if(updErg==4) ergStr = '<span class="pass">'+data+'</span>';
              $('#updBut').linkbutton('enable');
              $('#updPanel').html(ergStr);
            });
            return;
	        }
	       
	       $.get('up.php?step='+updStep, function(data) {
	       	  if(data==1)updErg++;
	       	  $(tabs[updStep]).removeClass("pagination-loading");
	       	  $(tabs[updStep]).html(ergs[data]);
	       	  runUpdate();
            });
         }
<?php endif;?>	      
</script>

<!--[if IE]><script language="javascript" type="text/javascript" src="js/excanvas.min.js"></script><![endif]-->
<script language="javascript" type="text/javascript" src="lang/easyui-lang-<?php echo LANG;?>.js"></script>
<script language="javascript" type="text/javascript" src="js/dateformat.js"></script>
<?php if(PSA_VERSION < 10):?>
<link rel="stylesheet" type="text/css" href="/skins/<?php echo $dcp->getPleskSkin();?>/css/general.css">
<link rel="stylesheet" type="text/css" href="/skins/<?php echo $dcp->getPleskSkin();?>/css/main/custom.css">
<link rel="stylesheet" type="text/css" href="/skins/<?php echo $dcp->getPleskSkin();?>/css/main/layout.css">
<link rel="stylesheet" type="text/nonsense" href="/skins/<?php echo $dcp->getPleskSkin();?>/css/misc.css">
<?php elseif(PSA_VERSION < 11.5):?>
<link rel="stylesheet" href="/skins/<?php echo $dcp->getPleskSkin();?>/css/btns.css" type="text/css" />
<link rel="stylesheet" href="/skins/<?php echo $dcp->getPleskSkin();?>/css/customer/main.css" type="text/css" />
<link rel="stylesheet" href="/skins/<?php echo $dcp->getPleskSkin();?>/css/customer/custom.css" type="text/css" />
<?php else:?>
<link rel="stylesheet" href="/<?php echo $dcp->getPleskSkin();?>/css/common.css" type="text/css" />
<link rel="stylesheet" href="/<?php echo $dcp->getPleskSkin();?>/css/main.css" type="text/css" />
<link rel="stylesheet" href="/<?php echo $dcp->getPleskSkin();?>/css/main-buttons.css" type="text/css" />
<link rel="stylesheet" href="/<?php echo $dcp->getPleskSkin();?>/css/custom.css" type="text/css" />
<?php endif;?>

<link rel="stylesheet" type="text/css" href="themes/default/easyui.css">
<link rel="stylesheet" type="text/css" href="themes/icon.css">
<link rel="stylesheet" type="text/css" href="themes/<?php echo (stristr($_SERVER["HTTP_USER_AGENT"],"msie"))?"doof":"schlau";?>_browser.css">
<style>
#hl{
width:600px;
margin-left:10px;
padding-left:45px;
height:40px;
background-image:url(images/dcplogo.png);
background-repeat:no-repeat;
}

#hl h1{
padding-top:10px;
}

table{
font-size:11px !important;
}

.fitem{
margin-bottom:5px;
}

.fitem label{
display:inline-block;
width:290px;
}

.fitem input{
height:22px;
}

.noaccess{
text-align:center;
color:#FF0000;
font-weight:bold;
}
</style>

<body onLoad="_body_onload();" id="mainCP" style="background:none">
<?php if($dcp->plesk_session->chkLevel(IS_ADMIN)):?>  
<div id="doUpdate"
     class="easyui-window" 
     iconCls="icon-refresh"
     title="<?php echo UPDATE_DCP;?>" 
     style="width:425px;height:210px;padding:10px;overflow:hidden"
     closed="true" 
     modal="true" 
     collapsible="false"
	   minimizable="false"
	   maximizable="false"
	   resizable="false">
     <div class="easyui-layout" fit="true">
	<div region="center" id="updPanel" border="false" style="padding:10px;background:#fff;border:1px solid #ccc;overflow:hidden">
	          <div class="fitem">
	           <label style="vertical-align:top;padding-top:4px;width:250px"><?php echo UPDATE_DOWN;?></label>
             <span id="upd_down" style="padding:4px;width:150px;line-height:22px" class="pagination-loading">&nbsp;&nbsp;&nbsp;&nbsp;</span> 
	          </div>
	          <div class="fitem">
	           <label style="vertical-align:top;width:250px"><?php echo UPDATE_CONFIG;?></label>
             <span id="upd_conf" style="padding:4px;width:150px;line-height:22px" class="pagination-loading">&nbsp;&nbsp;&nbsp;&nbsp;</span> 
	          </div>
	          <div class="fitem">
	           <label style="vertical-align:top;width:250px"><?php echo UPDATE_INSTALL;?></label>
             <span id="upd_inst" style="padding:4px;width:150px;line-height:22px" class="pagination-loading">&nbsp;&nbsp;&nbsp;&nbsp;</span> 
	          </div>
	          <div class="fitem">
	           <label style="vertical-align:top;width:250px"><?php echo UPDATE_DONE;?></label>
             <span id="upd_done" style="padding:4px;width:150px;line-height:22px" class="pagination-loading">&nbsp;&nbsp;&nbsp;&nbsp;</span> 
	          </div>
	</div>
	<div region="south" border="false" style="text-align:right;padding:5px 0;">
		<a class="easyui-linkbutton" id="updBut" disabled="true" iconCls="icon-ok" href="index.php?<?php $dcp->getQueryString();?>">OK</a>
	</div>
     </div>
</div>
<?php endif;?>
<?php if($dcp->hasAccess):?>
<div id="hl"><h1><?php echo DCP_ADMIN;?> <?php echo DCP_VERSION;?> &nbsp;&nbsp;[<?php $dcp->checkVersion(DCP_VERSION);?>]</h1></div>
    <table id="dynhosts" toolbar="#toolbar"></table>  
    <div id="toolbar" style="padding-top:5px">  
        <a href="javascript:void(0)" class="easyui-linkbutton" iconCls="icon-ok" plain="false" onclick="aktivate()"><?php echo DCP_DDNS_ACTIVATE;?></a>  
        <a href="javascript:void(0)" class="easyui-linkbutton" iconCls="icon-no" plain="false" onclick="deaktivate()"><?php echo DCP_DDNS_DEACTIVATE;?></a>  
        <a href="javascript:void(0)" class="easyui-linkbutton" iconCls="icon-settings" plain="false" onclick="newtoken()"><?php echo DCP_DDNS_NEWTOKEN;?></a>  
        <a href="javascript:void(0)" class="easyui-linkbutton" iconCls="icon-reload" plain="false" onclick="newip()"><?php echo DCP_DDNS_NEWIP;?></a>  
        <?php if($dcp->plesk_session->chkLevel(IS_ADMIN)):?>   
        <a href="javascript:void(0)" class="easyui-linkbutton" iconCls="icon-domain" plain="false" onclick="release()" id="Rel_0" style="display:none"><?php echo DCP_DDNS_RELEASE;?></a>
        <a href="javascript:void(0)" class="easyui-linkbutton" iconCls="icon-cancel" plain="false" onclick="release()" id="Rel_1" style="display:none"><?php echo DCP_DDNS_REFUSE;?></a>
        <?php endif;?>
    </div>  
<table width="100%" cellpadding="0" cellspacing="0">
  </tr>
  <tr align="center" valign="middle"> 
    <td height="5"></td>
  </tr>
  <tr align="center" valign="middle" bgcolor="#000000"> 
    <td height="1"></td>
  </tr>
  <tr align="center" valign="middle"> 
    <td height="5"></td>
  </tr>

  <tr align="center" valign="middle"> 
    <td height="5">&copy; <?php echo date("Y");?> <a href="http://www.haggybear.de">Matthias Hackbarth</a></td>
  </tr>
</table>
<?php else:?>
<div class="noaccess">
<?php echo NO_ACCESS;?>
</center>
<?php endif;?>
	
</body>
</html>