<?php
/**
 * @package pragyan
 * @copyright (c) 2010 Pragyan Team
 * @license http://www.gnu.org/licenses/ GNU Public License
 * For more details, see README
 */

function userManagementForm()
{
	$usermgmtform=<<<USERFORM
	<script type='text/javascript' language='javascript'>
	function checkAll(formobj)
	{
		for(i=0;i<formobj.elements.length;i++)
		{
			
			if(formobj.elements[i].type=='checkbox') formobj.elements[i].checked=true;
		}
	}
	function unCheckAll(formobj)
	{
		for(i=0;i<formobj.elements.length;i++)
		{
			
			if(formobj.elements[i].type=='checkbox') formobj.elements[i].checked=false;
		}
	}
	</script>
	<form name='user_mgmt_form' action='./+admin&subaction=useradmin' method='POST'>
	<fieldset>
	<legend>User Management</legend>
	
	Select Fields to Display : <input type='button' onclick='return checkAll(this.form);' value='Check All' /><input type='button' onclick='return unCheckAll(this.form);' value='Uncheck All' />
	<table><tr><td>Field Name</td><td>Display ?</td><td>Field Name</td><td>Display ?</td><td>Field Name</td><td>Display ?</td></tr>
USERFORM;
	
	$usertablefields=getTableFieldsName('users');
	$userfieldprettynames=array("User ID","Username","Email","Full Name","Password","Registration","Last Login","Activated","Login Method");
	$cols=3;
	for($i=0;$i<count($usertablefields);$i=$i+$cols)
	{	
		$usermgmtform.="<tr>";
		for($j=0;$j<$cols;$j++)
		{
			if($i+$j<count($usertablefields))
			{
				$checked="";
				if(isset($_POST['not_first_time']))
					$checked=isset($_POST[$usertablefields[$i+$j].'_sel'])?"checked":"";
				else if($usertablefields[$i+$j]=="user_name" || $usertablefields[$i+$j]=="user_fullname" || $usertablefields[$i+$j]=="user_email" || $usertablefields[$i+$j]=="user_lastlogin" || $usertablefields[$i+$j]=="user_activated")
					$checked="checked";
				
				$usermgmtform.="<td>{$userfieldprettynames[$i+$j]}</td><td><input type='checkbox' name='{$usertablefields[$i+$j]}_sel' $checked /></td>";
			}
		}
		$usermgmtform.="</tr>";
	}
	$usermgmtform.=<<<USERFORM
	<input type='hidden' name='not_first_time' />
	</table>
	<fieldset style="float:left;">
	<legend>All Registered</legend>
	<input type='submit' value='View' name='view_reg_users'/>
	<input type='submit' value='Edit' name='edit_reg_users'/>
	</fieldset>&nbsp;
	<fieldset style="float:left;">
	<legend>Activated Users</legend>
	<input type='submit' value='View' name='view_activated_users'/>
	<input type='submit' value='Edit' name='edit_activated_users'/>
	
	</fieldset>&nbsp;
	<fieldset style="float:left;">
	<legend>Non-Activated Users</legend>
	<input type='submit' value='View' name='view_nonactivated_users'/>
	<input type='submit' value='Edit' name='edit_nonactivated_users'/>
	
	</fieldset>
	<div style="clear:both"></div>
	<hr/>
	<input type="submit" onclick="this.form.action+='&subsubaction=search'" value="Search User" />
	<input type="submit" onclick="this.form.action+='&subsubaction=create'" value="New User" />
	<input type='submit' value='Deactivate All' name='deactivate_all_users'/>
	<input type='submit' value='Activate All' name='activate_all_users'/>
	</fieldset>
	
	
	</form>
USERFORM;
	return $usermgmtform;
}
function handleUserMgmt()
{
	if(isset($_GET['userid']))
	 $_GET['userid']=escape($_GET['userid']);
	if(isset($_POST['editusertype'])) $_POST['editusertype']=escape($_POST['editusertype']);
	if(isset($_POST['user_activate']))
	{
		$query="UPDATE ".MYSQL_DATABASE_PREFIX."users SET user_activated=1 WHERE user_id={$_GET['userid']}";
		if(mysql_query($query))
			displayInfo("User Successfully Activated!");
		else displayerror("User Not Activated!");
		return registeredUsersList($_POST['editusertype'],"edit",false);
	}
	else if(isset($_POST['activate_all_users']))
	{
		
		$query="UPDATE ".MYSQL_DATABASE_PREFIX."users SET user_activated=1";
		if(mysql_query($query))
			displayInfo("All users activated successfully!");
		else displayerror("Users Not Deactivated!");
		
		return;
	}
	else if(isset($_POST['user_deactivate']))
	{
		if($_GET['userid']==ADMIN_USERID)
		{
			displayError("You cannot deactivate administrator!");
			return registeredUsersList($_POST['editusertype'],"edit",false);
		}
		$query="UPDATE ".MYSQL_DATABASE_PREFIX."users SET user_activated=0 WHERE user_id={$_GET['userid']}";
		if(mysql_query($query))
			displayInfo("User Successfully Deactivated!");
		else displayerror("User Not Deactivated!");
		
		return registeredUsersList($_POST['editusertype'],"edit",false);
	}
	else if(isset($_POST['deactivate_all_users']))
	{
		
		$query="UPDATE ".MYSQL_DATABASE_PREFIX."users SET user_activated=0 WHERE user_id != ".ADMIN_USERID;
		if(mysql_query($query))
			displayInfo("All users deactivated successfully except Administrator!");
		else displayerror("Users Not Deactivated!");
		
		return;
	}
	else if(isset($_POST['user_delete']))
	{
		$userId=$_GET['userid'];
		if($userId==ADMIN_USERID)
		{
			displayError("You cannot delete administrator!");
			return registeredUsersList($_POST['editusertype'],"edit",false);
		}
		$query="DELETE FROM `".MYSQL_DATABASE_PREFIX."users` WHERE `user_id` = $userId";
		if(mysql_query($query))
			displayinfo("User Successfully Deleted!");
		else displayerror("User Not Deleted!");
		return registeredUsersList($_POST['editusertype'],"edit",false);
		
	}
	else if(isset($_POST['user_info']) || (isset($_POST['user_info_update'])))
	{	
		if(isset($_POST['user_info_update']))
		{
			$updates = array();
			$query="SELECT * FROM `".MYSQL_DATABASE_PREFIX."users` WHERE `user_id`={$_GET['userid']}";
			$row=mysql_fetch_assoc(mysql_query($query));
			$errors = false;
			
			if(isset($_POST['user_name']) && $row['user_name']!=$_POST['user_name'])
			{
				$chkquery="SELECT * FROM `".MYSQL_DATABASE_PREFIX."users` WHERE `user_name`='".escape($_POST['user_name'])."'";
				$result=mysql_query($chkquery) or die("failed  : $chkquery");
				if(mysql_num_rows($result)>0) 
				{
					displayerror("User Name already exists in database!");
					$errors=true;
				}
				
			}
			
			
			if (isset($_POST['user_name']) && $_POST['user_name'] != ''  && $_POST['user_name']!=$row['user_name']) {
				$updates[] = "`user_name` = '".escape($_POST['user_name'])."'";
				
			}
			if (isset($_POST['user_email']) && $_POST['user_email'] != ''  && $_POST['user_email']!=$row['user_email']) {
				$updates[] = "`user_email` = '".escape($_POST['user_email'])."'";
				
			}
			if (isset($_POST['user_fullname']) && $_POST['user_fullname'] != ''  && $_POST['user_fullname']!=$row['user_fullname']) {
				$updates[] = "`user_fullname` = '".escape($_POST['user_fullname'])."'";
				
			}
			
			if ($_POST['user_password'] != '') {
				
				if ($_POST['user_password'] != $_POST['user_password2']) {
					displayerror('Error! The New Password you entered does not match the password you typed in the Confirmation Box.');					$errors=true;
				}
				else if(md5($_POST['user_password']) != $row['user_password']) {
					$updates[] = "`user_password` = MD5('{$_POST['user_password']}')";
					
				}
			}
			if (isset($_POST['user_regdate']) && $_POST['user_regdate'] != ''  && $_POST['user_regdate']!=$row['user_regdate']) {
				$updates[] = "`user_regdate` = '".escape($_POST['user_regdate'])."'";
				
			}
			if (isset($_POST['user_lastlogin']) && $_POST['user_lastlogin'] != ''  && $_POST['user_lastlogin']!=$row['user_lastlogin']) {
				$updates[] = "`user_lastlogin` = '".escape($_POST['user_lastlogin'])."'";
				
			}
			if ($_GET['userid']!=ADMIN_USERID && (isset($_POST['user_activated'])?1:0)!=$row['user_activated']) {
				$checked=isset($_POST['user_activated'])?1:0;
				$updates[] = "`user_activated` = $checked";
				
			}
			if (isset($_POST['user_loginmethod']) && $_POST['user_loginmethod'] != ''  && $_POST['user_loginmethod']!=$row['user_loginmethod']) 	{
				$updates[] = "`user_loginmethod` = '".escape($_POST['user_loginmethod'])."'";
				if($_POST['user_loginmethod']!='db')
				displaywarning("Please make sure ".strtoupper(escape($_POST['user_loginmethod']))." is configured properly, otherwise the user will not be able to login to the website.");
			}

			if(!$errors && count($updates) > 0) {
				$profileQuery = 'UPDATE `' . MYSQL_DATABASE_PREFIX . 'users` SET ' . join($updates, ', ') . " WHERE `user_id` = {$_GET['userid']}";
				$profileResult = mysql_query($profileQuery);
				if(!$profileResult) {
					displayerror('An error was encountered while attempting to process your request.'.$profileQuery);
					$errors = true;
				}
				else displayinfo('All fields updated successfully!');
			}
			
				
				
			
		}
		$query="SELECT * FROM `".MYSQL_DATABASE_PREFIX."users` WHERE `user_id`={$_GET['userid']}";
		$row=mysql_fetch_assoc(mysql_query($query));
		$userfieldprettynames=array("User ID","Username","Email","Full Name","Password","Registration","Last Login","Activated","Login Method");
		$userinfo="<fieldset><legend>Edit User Information</legend><form name='user_info_edit' action='./+admin&subaction=useradmin&userid={$_GET['userid']}' method='post'>";
		
		
		
		$usertablefields=getTableFieldsName('users');		
		for($i=0;$i<count($usertablefields);$i++)
			if(isset($_POST[$usertablefields[$i].'_sel']))
				$userinfo.="<input type='hidden' name='{$usertablefields[$i]}_sel' value='checked'/>";
		$userinfo.="<input type='hidden' name='not_first_time' />";
		
	
		
		$userinfo.=userProfileForm($userfieldprettynames,$row);
		$userinfo.="<input type='submit' value='Update' name='user_info_update' />
		<input type='reset' value='Reset' /></form></fieldset>";
		return $userinfo;
	
	
	}
	else if(isset($_POST['view_reg_users']))
	{
		return registeredUsersList("all","view",false);
	}
	else if(isset($_POST['edit_reg_users']))
	{
		return registeredUsersList("all","edit",false);
	}
	else if(isset($_POST['view_activated_users']))
	{
		return registeredUsersList("activated","view",false);
	}
	else if(isset($_POST['edit_activated_users']))
	{
		return registeredUsersList("activated","edit",false);
	}
	else if(isset($_POST['view_nonactivated_users']))
	{
		return registeredUsersList("nonactivated","view",false);
	}
	else if(isset($_POST['edit_nonactivated_users']))
	{
		return registeredUsersList("nonactivated","edit",false);
	}
	else if(isset($_GET['subsubaction']) && $_GET['subsubaction']=='search')
	{
	
		$results="";
		$userfieldprettynames=array("User ID","Username","Email","Full Name","Password","Registration","Last Login","Activated","Login Method");		
		$usertablefields=getTableFieldsName('users');
		
		$first=true;
		
		$qstring="";
		foreach ($usertablefields as $field) {
			if(isset($_POST[$field]) && $_POST[$field]!='')
			{
				if ($first == false)
					$qstring .= ($_POST['user_search_op']=='and')?" AND ":" OR ";
				$val=escape($_POST[$field]);
				if($field=='user_activated') ${$field.'_lastval'}=$val=isset($_POST[$field])?1:0;
				else ${$field.'_lastval'}=$val;
				$qstring .= "`$field` LIKE CONVERT( _utf8 '%$val%'USING latin1 ) ";
				$first=false;
			}
		}
		if($qstring!="")
		{
			$query = "SELECT * FROM `" . MYSQL_DATABASE_PREFIX . "users` WHERE $qstring ";
			$resultSearch = mysql_query($query);
			if (mysql_num_rows($resultSearch) > 0) {
				$num = mysql_num_rows($resultSearch);
				
				$userInfo=array();
				
				
				while($row=mysql_fetch_assoc($resultSearch))
				{
					$userInfo['user_id'][]=$row['user_id'];
					$userInfo['user_name'][]=$row['user_name'];
					$userInfo['user_email'][]=$row['user_email'];
					$userInfo['user_fullname'][]=$row['user_fullname'];
					$userInfo['user_password'][]=$row['user_password'];
					$userInfo['user_lastlogin'][]=$row['user_lastlogin'];
					$userInfo['user_regdate'][]=$row['user_regdate'];
					$userInfo['user_activated'][]=$row['user_activated'];
					$userInfo['user_loginmethod'][]=$row['user_loginmethod'];	
				}
				$results=registeredUsersList("all","edit",false,$userInfo);
			} else
				displayerror("No users matched your query!");
			
		}
		
		$searchForm="<form name='user_search_form' action='./+admin&subaction=useradmin&subsubaction=search' method='POST'>";
		for($i=0;$i<count($usertablefields);$i++)
			if(isset($_POST[$usertablefields[$i].'_sel']))
				$searchForm.="<input type='hidden' name='{$usertablefields[$i]}_sel' value='checked'/>";
		$searchForm.="<input type='hidden' name='not_first_time' />";
		
		$infoarray=array();
		foreach ($usertablefields as $field)
			$infoarray[$field]=${$field.'_lastval'};
			
		$searchForm.=userProfileForm($userfieldprettynames,$infoarray,true);
		
		$searchForm.="Operation : <input type='radio' name='user_search_op' value='and'  />AND  <input type='radio' name='user_search_op' value='or' checked='true' />OR<br/><br/><input type='submit' onclick name='user_search_submit' value='Search' /><input type='reset' value='Clear' /></form>";
		return $searchForm.$results;
		
		
	}
	
	else if(isset($_GET['subsubaction']) && $_GET['subsubaction']=='create')
	{
		
		$userfieldprettynames=array("User ID","Username","Email","Full Name","Password","Registration","Last Login","Activated","Login Method");		
		$usertablefields=getTableFieldsName('users');
		
		
		if(isset($_POST['create_user_submit']))
		{
			$incomplete=false;
			foreach($usertablefields as $field)
			{
				if(($field != 'user_regdate') && ($var != 'user_lastlogin') && ($var != 'user_activated') && ($_POST[$field]==""))
				{
					displayerror("New user could not be created. Some fields are missing!");
					$incomplete=true;
					break;
				}
				${$field}=escape($_POST[$field]);
			}
			if(!$incomplete)
			{
				$user_id=$_GET['userid'];
				$chkquery="SELECT COUNT(user_id) FROM `".MYSQL_DATABASE_PREFIX."users` WHERE `user_id`=$user_id OR `user_name`='$user_name' OR `user_email`='$user_email'";
			
				$result=mysql_query($chkquery);
				$row=mysql_fetch_row($result);
			
				if($row[0]>0) displayerror("Another user with the same name or email already exists!");
				else if($user_password!=$_POST['user_password2']) displayerror("Passwords mismatch!");
				else 
				{
					if(isset($_POST['user_activated'])) $user_activated=1;
					$query = "INSERT INTO `" . MYSQL_DATABASE_PREFIX . "users` (`user_id` ,`user_name` ,`user_email` ,`user_fullname` ,`user_password` ,`user_regdate` ,`user_lastlogin` ,`user_activated`,`user_loginmethod`)VALUES ('$user_id' ,'$user_name' ,'$user_email' ,'$user_fullname' , MD5('$user_password') ,CURRENT_TIMESTAMP , '', '$user_activated','$user_loginmethod')";
					$result = mysql_query($query) or die(mysql_error());
					if (mysql_affected_rows()) displayinfo("User $user_fullname Successfully Created!");
					else displayerror("Failed to create user");
				}
			}
		}
		
		$nextUserId=getNextUserId();
		$userForm="<form name='user_create_form' action='./+admin&subaction=useradmin&subsubaction=create&userid=$nextUserId' method='POST'>";
		for($i=0;$i<count($usertablefields);$i++)
			if(isset($_POST[$usertablefields[$i].'_sel']))
				$userForm.="<input type='hidden' name='{$usertablefields[$i]}_sel' value='checked'/>";
		$userForm.="<input type='hidden' name='not_first_time' />";
		$infoarray=array();
		foreach ($usertablefields as $field)
			$infoarray[$field]="";
		$infoarray['user_id']=$nextUserId;
		
		$userForm.=userProfileForm($userfieldprettynames,$infoarray,false);
		
		$userForm.="<input type='submit' onclick name='create_user_submit' value='Create' /><input type='reset' value='Clear' /></form>";
		return $userForm;
		
		
		
		

	}
	
}
function getAllUsersInfo(&$userId,&$userName,&$userEmail,&$userFullName,&$userPassword,&$userLastLogin,&$userRegDate,&$userActivated,&$userLoginMethod)
{
	$query="SELECT * FROM `".MYSQL_DATABASE_PREFIX."users` ORDER BY `user_id` ASC";
	$result=mysql_query($query);
	$userId=array();
	$userEmail=array();
	$userName=array();
	$userFullName=array();
	$userPassword=array();
	$userLastLogin=array();
	$userRegDate=array();
	$userActivated=array();
	$userLoginMethod=array();
	$i=0;
	while($row=mysql_fetch_assoc($result))
	{
		$userId[$i]=$row['user_id'];
		$userName[$i]=$row['user_name'];
		$userEmail[$i]=$row['user_email'];
	
		$userFullName[$i]=$row['user_fullname'];
		$userPassword[$i]=$row['user_password'];
		$userLastLogin[$i]=$row['user_lastlogin'];
		$userRegDate[$i]=$row['user_regdate'];
		$userActivated[$i]=$row['user_activated'];
		$userLoginMethod[$i]=$row['user_loginmethod'];
		$i++;
	}
	
}
function registeredUsersList($type,$act,$allfields,$userInfo=NULL)
{
	if($userInfo==NULL)
	 getAllUsersInfo($userId,$userName,$userEmail,$userFullName,$userPassword,$userLastLogin,$userRegDate,$userActivated,$userLoginMethod);
	else 
	{
		$userId=$userInfo['user_id'];
		$userName=$userInfo['user_name'];
		$userEmail=$userInfo['user_email'];
	
		$userFullName=$userInfo['user_fullname'];
		$userPassword=$userInfo['user_password'];
		$userLastLogin=$userInfo['user_lastlogin'];
		$userRegDate=$userInfo['user_regdate'];
		$userActivated=$userInfo['user_activated'];
		$userLoginMethod=$userInfo['user_loginmethod'];
		
	}
	
	global $urlRequestRoot,$cmsFolder;
	$userfieldprettynames=array("User ID","Username","Email","Full Name","Password","Registration","Last Login","Activated","Login Method");
	$userlisttdids=array("user_id","user_name","user_email","user_fullname","user_password","user_regdate","user_lastlogin","user_activated","user_loginmethod");
	$userfieldvars=array("userId","userName","userEmail","userFullName","userPassword","userRegDate","userLastLogin","userActivated","userLoginMethod");
	$userlist="";
	$columns=count($userfieldvars);
	if($act=="edit")
	{
		$userlist.="<form name='user_edit_form' method='POST' action='./+admin&subaction=useradmin&userid=' >\n";
		$userlist.="<input type='hidden' name='editusertype' value='$type' />";
		$columns+=3;
	}
	
	$userlist.=<<<USERLIST
	<style type="text/css" title="currentStyle">
			@import "$urlRequestRoot/$cmsFolder/modules/datatables/css/demo_page.css";
			@import "$urlRequestRoot/$cmsFolder/modules/datatables/css/demo_table_jui.css";
			@import "$urlRequestRoot/$cmsFolder/modules/datatables/themes/smoothness/jquery-ui-1.7.2.custom.css";
	</style>
	<script type="text/javascript" language="javascript" src="$urlRequestRoot/$cmsFolder/modules/datatables/js/jquery.js"></script>
	<script type="text/javascript" language="javascript" src="$urlRequestRoot/$cmsFolder/modules/datatables/js/jquery.dataTables.min.js"></script>
	<script type="text/javascript" charset="utf-8">
		$(document).ready(function() {
			oTable = $('#userstable').dataTable({
				"bJQueryUI": true,
				"sPaginationType": "full_numbers"
			});
		} );
	</script>
	<script language="javascript">
	function checkDelete(butt,userDel,userId)
	{
		if(confirm('Are you sure you want to delete '+userDel+' (User ID='+userId+')?'))
		{
			butt.form.action+=userId;
		}
		else return false;
	}
	</script>
	<a name='userlist'></a><table class="userlisttable display" border="1" id='userstable'>
	<thead>
	<tr><th colspan="$columns">Users Registered on the Website</th></tr>
	<tr>
USERLIST;

	$usertablefields=getTableFieldsName('users');
	$displayfieldsindex=array();
	$c=0;
	for($i=0;$i<count($usertablefields);$i++)
	{
		if(isset($_POST[$usertablefields[$i].'_sel']) || $allfields)
		{
			$userlist.="<th>".$userfieldprettynames[$i];
			if($act=="edit") $userlist.="<input type='hidden' name='{$usertablefields[$i]}_sel' value='checked'/>";
			$userlist.="</th>";
			$displayfieldsindex[$c++]=$i;
		}
	}
	$userlist.="<input type='hidden' name='not_first_time' />";
		
	
	if($act=="edit")
	{
		$userlist.="<th>De/Activate</th><th>Edit User Information</th><th>Delete User</th>";
	}
	$userlist.="</tr></thead><tbody>";
	$rowclass="oddrow";
	$flag=false;
	$usercount=0;
	for($i=0; $i<count($userId); $i++)
	{
		if($type=="activated" && $userActivated[$i]==0)
			continue;
		if($type=="nonactivated" && $userActivated[$i]==1)
			continue;
		$flag=true;
		$userlist.="<tr class='$rowclass'>";
		
		for($j=0; $j<count($displayfieldsindex); $j++)
		{
			$userlist.="<td id='{$userlisttdids[$j]}'>".${$userfieldvars[$displayfieldsindex[$j]]}[$i]."</td>";
		}
		if($act=="edit")
		{
			if($userActivated[$i]==0)
				$userlist.="<td id='user_activate'><input type='submit' onclick=\"this.form.action+='{$userId[$i]}'\" name='user_activate' value='Activate'></td>\n";
			else $userlist.="<td id='user_deactivate'><input type='submit' onclick=\"this.form.action+='{$userId[$i]}'\" name='user_deactivate' value='Deactivate'></td>\n";
			$userlist.="<td id='user_profile'><input type='submit' onclick=\"this.form.action+='{$userId[$i]}'\" name='user_info' value='Edit'></td>\n";
			$userlist.="<td id='user_delete'><input type='submit' onclick=\"return checkDelete(this,'".$userName[$i]."','".$userId[$i]."')\" name='user_delete' value='Delete'></td>\n";
			
		}
		$userlist.="</tr>";
		$rowclass=$rowclass=="evenrow"?"oddrow":"evenrow";
		$usercount++;
	}
	$userlist.="</tbody></table>";
	if($act=="edit") $userlist.="</form>";
	if($usercount>0)
		displayinfo("<a href='#userlist'>Click Here to view the $usercount users found.</a>");
	
	return ($flag)?$userlist:"No Users Found!";
}
function userProfileForm($userfieldprettynames,$profileInfoRows,$editID=false)
{
	$i=0;
	$userinfo="<table>";
	foreach ($profileInfoRows as $field => $value)
	{
		if($field=='user_password')
		{
			$userinfo.="<tr><td>{$userfieldprettynames[$i]}</td><td><input type='password' name='$field'/></td></tr>";
			$field.='2';
			$userinfo.="<tr><td>{$userfieldprettynames[$i++]} (Verify)</td><td><input type='password' name='$field'/></td></tr>";
		}
		else if($field=='user_activated')
		{
			$value=($value==1)?"checked":"";
			$userinfo.="<tr><td>{$userfieldprettynames[$i++]}</td><td><input type='checkbox' name='$field' $value /></td></tr>";
		}
		else if($field=='user_loginmethod')
		{
			${$profileInfoRows[$field].'sel'}=" selected = 'selected' ";
			$userinfo.="<tr><td>{$userfieldprettynames[$i++]}</td><td><select id='$field' name='$field'>
			<option></option>
			<option $ldapsel>ldap</option>
			<option $imapsel>imap</option>
			<option $adssel>ads</option>
			<option $dbsel>db</option>
			</select>
			</td></tr>";
		}
		else if((!$editID && $field=='user_id') || (!$editID && $field=='user_regdate'))
			$userinfo.="<tr><td>{$userfieldprettynames[$i++]}</td><td>$value</td></tr>";
		
		else $userinfo.="<tr><td>{$userfieldprettynames[$i++]}</td><td><input type='text' name='$field' value='$value'/></td></tr>";
		
	}
	return $userinfo."</table>";
}
?>
