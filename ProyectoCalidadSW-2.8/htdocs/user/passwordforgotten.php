<?php
/* Copyright (C) 2007-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 */

/**
 *       \file       htdocs/user/passwordforgotten.php
 *       \brief      Page demande nouveau mot de passe
 *       \version    $Id$
 */

define("NOLOGIN",1);	// This means this output page does not require to be logged.

require("../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/contact.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/ldap.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/usergroups.lib.php");

$langs->load("other");
$langs->load("users");
$langs->load("companies");
$langs->load("ldap");

// Security check
if ($conf->global->MAIN_SECURITY_DISABLEFORGETPASSLINK)
	accessforbidden();

$action=isset($_GET["action"])?$_GET["action"]:$_POST["action"];
$mode=$dolibarr_main_authentication;
if (! $mode) $mode='http';

$login = isset($_POST["username"])?$_POST["username"]:'';
$conf->entity = isset($_POST["entity"])?$_POST["entity"]:1;



/**
 * Actions
 */

// Action modif mot de passe
if ($_GET["action"] == 'validatenewpassword' && $_GET["username"] && $_GET["passwordmd5"])
{
    $edituser = new User($db);
    $result=$edituser->fetch($_GET["username"]);
	if ($result < 0)
	{
        $message = '<div class="error">'.$langs->trans("ErrorLoginDoesNotExists",$_GET["username"]).'</div>';
	}
	else
	{
		if (md5($edituser->pass_temp) == $_GET["passwordmd5"])
		{
			$newpassword=$edituser->setPassword($user,$edituser->pass_temp,0);
			dol_syslog("passwordforgotten.php new password for user->id=".$edituser->id." validated in database");
			//session_start();
			//$_SESSION["loginmesg"]=$langs->trans("PasswordChanged");
			header("Location: ".DOL_URL_ROOT.'/');
			exit;
		}
		else
		{
	        $message = '<div class="error">'.$langs->trans("ErrorFailedToValidatePassword").'</div>';
		}
	}
}
// Action modif mot de passe
if ($_POST["action"] == 'buildnewpassword' && $_POST["username"])
{
	require_once DOL_DOCUMENT_ROOT.'/includes/artichow/Artichow.cfg.php';
	require_once ARTICHOW."/AntiSpam.class.php";

	// We create anti-spam object
	$object = new AntiSpam();

	// Verify code
	if (! $object->check('dol_antispam_value',$_POST['code'],true))
	{
		$message = '<div class="error">'.$langs->trans("ErrorBadValueForCode").'</div>';
	}
	else
	{
	    $edituser = new User($db);
	    $result=$edituser->fetch($_POST["username"],'',1);
		if ($result <= 0 && $edituser->error == 'USERNOTFOUND')
		{
	        $message = '<div class="error">'.$langs->trans("ErrorLoginDoesNotExists",$_POST["username"]).'</div>';
			$_POST["username"]='';
		}
		else
		{
			if (! $edituser->email)
			{
		        $message = '<div class="error">'.$langs->trans("ErrorLoginHasNoEmail").'</div>';
			}
			else
			{
				$newpassword=$edituser->setPassword($user,'',1);
			    if ($newpassword < 0)
			    {
			        // Failed
			        $message = '<div class="error">'.$langs->trans("ErrorFailedToChangePassword").'</div>';
			    }
			    else
			    {
			        // Success
			        if ($edituser->send_password($user,$newpassword,1) > 0)
			        {
			        	$message = '<div class="ok">'.$langs->trans("PasswordChangeRequestSent",$edituser->login,$edituser->email).'</div>';
						//$message.=$newpassword;
						$_POST["username"]='';
					}
					else
					{
					   	//$message = '<div class="ok">'.$langs->trans("PasswordChangedTo",$newpassword).'</div>';
					    $message.= '<div class="error">'.$edituser->error.'</div>';
					}
			    }
			}
		}
	}
}



/*
 * Affichage page
 */
if ($conf->global->MAIN_SMARTY)
{
	$smarty->assign('langs', $langs);

	$php_self = $_SERVER['PHP_SELF'];
	$php_self.= $_SERVER["QUERY_STRING"]?'?'.$_SERVER["QUERY_STRING"]:'';

	$smarty->assign('php_self', $php_self);
	$smarty->assign('character_set_client',$conf->file->character_set_client);
	$smarty->assign('dol_url_root', DOL_URL_ROOT);
	$smarty->assign('mode', $mode);
	$smarty->assign('login', $login);

	// Select templates
	if ($conf->browser->phone)
	{
		if (file_exists(DOL_DOCUMENT_ROOT."/theme/phones/".$conf->browser->phone))
		{
			$smarty->template_dir = DOL_DOCUMENT_ROOT."/theme/phones/".$conf->browser->phone."/templates/user/";
			$smarty->assign('theme', 'default');
		}
		else
		{
			$smarty->template_dir = DOL_DOCUMENT_ROOT."/theme/phones/others/templates/user/";
		}
	}
	else
	{
		if (file_exists(DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/core/templates/passwordforgotten.tpl"))
		{
			$smarty->template_dir = DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/templates/core/";
		}
		else
		{
			$smarty->template_dir = DOL_DOCUMENT_ROOT."/core/templates/";
		}

		$conf->css  = "/theme/".$conf->theme."/".$conf->theme.".css.php?lang=".$langs->defaultlang;

		$smarty->assign('conf_css', DOL_URL_ROOT.$conf->css);
	}

	if (file_exists(DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/img/login_background.png'))
	{
		$smarty->assign('login_background', DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/login_background.png');
	}
	else
	{
		$smarty->assign('login_background', DOL_URL_ROOT.'/theme/login_background.png');
	}

	if (! $_REQUEST["username"]) $smarty->assign('focus_element', 'username');
	else $smarty->assign('focus_element', 'password');

	// Title
	$title='Dolibarr '.DOL_VERSION;
	if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $title=$conf->global->MAIN_APPLICATION_TITLE;
	$smarty->assign('title', $title);

	// Send password button enabled ?
	$disabled='disabled';
	if ($mode == 'dolibarr' || $mode == 'dolibarr_mdb2') $disabled='';
	if ($conf->global->MAIN_SECURITY_ENABLE_SENDPASSWORD) $disabled='';	 // To force button enabled
	$smarty->assign('disabled', $disabled);

	// Show logo (search in order: small company logo, large company logo, theme logo, common logo)
	$width=0;
	$rowspan=2;
	$urllogo=DOL_URL_ROOT.'/theme/login_logo.png';

	if (! empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small))
	{
		$urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode('thumbs/'.$mysoc->logo_small);
	}
	elseif (! empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/'.$mysoc->logo))
	{
		$urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode($mysoc->logo);
		$width=128;
	}
	elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.png'))
	{
		$urllogo=DOL_URL_ROOT.'/theme/dolibarr_logo.png';
	}

	if (! empty($conf->global->MAIN_MODULE_MULTICOMPANY)) $rowspan++;

	$smarty->assign('logo', $urllogo);
	$smarty->assign('logo_width', $width);
	$smarty->assign('logo_rowspan', $rowspan);

	// Entity field
	if (! empty($conf->global->MAIN_MODULE_MULTICOMPANY)  && ! $disabled)
	{
		require_once(DOL_DOCUMENT_ROOT.'/multicompany/multicompany.class.php');

		global $db;

		$mc = new Multicompany($db);
		$mc->getEntities();

		$smarty->assign('select_entity', $mc->select_entities($mc->entities,$conf->entity,'tabindex="2"'));
	}

	// Security graphical code
	if (function_exists("imagecreatefrompng") && ! $disabled)
	{
		$smarty->assign('captcha', 1);
		$smarty->assign('captcha_refresh', img_refresh());
	}

	// Message
	if ($message)
	{
		$smarty->assign('error_message', $message);
	}

	// Creation du template
	$smarty->display('passwordforgotten.tpl');

	// Suppression de la version compilee
	$smarty->clear_compiled_tpl('passwordforgotten.tpl');
}
else
{
	$conf->css  = "/theme/".$conf->theme."/".$conf->theme.".css.php";

	header('Cache-Control: Public, must-revalidate');

	print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">'."\n";

	// En tete html
	print "<html>\n";
	print "<head>\n";
	print '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">'."\n";
	print '<meta name="robots" content="noindex,nofollow">'."\n";      // Evite indexation par robots
	print "<title>Dolibarr Authentification</title>\n";
	print '<link rel="stylesheet" type="text/css" href="'.DOL_URL_ROOT.$conf->css.'?lang='.$langs->defaultlang.'">'."\n";
	print '<style type="text/css">'."\n";
	print '<!--'."\n";
	print '#login {';
	print '  margin-top: '.(empty($conf->browser->phone)?'70px;':'10px;');
	print '  margin-bottom: '.(empty($conf->browser->phone)?'30px;':'5px;');
	print '  text-align: center;';
	print '  font: 10px arial,helvetica;';
	print '}'."\n";
	print '#login table {';
	if (empty($conf->browser->phone)) print '  width: 498px;';
	print '  border: 1px solid #C0C0C0;';
	if (file_exists(DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/img/login_background.png'))
	{
		print 'background: #F0F0F0 url('.DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/login_background.png) repeat-x;';
	}
	else
	{
		print 'background: #F0F0F0 url('.DOL_URL_ROOT.'/theme/login_background.png) repeat-x;';
	}
	print '  font-size: 12px;';
	print '}'."\n";
	print '-->'."\n";
	print '</style>'."\n";
	print '<script type="text/javascript">'."\n";
	print "function donnefocus() {\n";
	if (! $_REQUEST["username"]) print "document.getElementById('username').focus();\n";
	else print "document.getElementById('password').focus();\n";
	print "}\n";
	print '</script>'."\n";
	print '</head>'."\n";

	// Body
	print '<body class="body" onload="donnefocus();">'."\n";

	// Form
	print '<form id="login" action="'.$_SERVER["PHP_SELF"].'" method="post" name="login">'."\n";
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="buildnewpassword">'."\n";

	// Table 1
	$title='Dolibarr '.DOL_VERSION;
	if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $title=$conf->global->MAIN_APPLICATION_TITLE;
	print '<table class="login" summary="'.$title.'" cellpadding="0" cellspacing="0" border="0" align="center">'."\n";
	print '<tr class="vmenu"><td align="center">'.$title.'</td></tr>'."\n";
	print '</table>'."\n";
	print '<br>'."\n";

	// Send password button enabled ?
	$disabled='disabled';
	if ($mode == 'dolibarr' || $mode == 'dolibarr_mdb2') $disabled='';
	if ($conf->global->MAIN_SECURITY_ENABLE_SENDPASSWORD) $disabled='';				// To force button enabled

	// Table 2
	print '<table class="login" cellpadding="2" align="center">'."\n";

	print '<tr><td colspan="3">&nbsp;</td></tr>'."\n";

	print '<tr>';
	print '<td align="left" valign="bottom"><br> &nbsp; <b>'.$langs->trans("Login").'</b>  &nbsp;</td>';
	print '<td valign="bottom"><input id="username" type="text" '.$disabled.' name="username" class="flat" size="15" maxlength="25" value="'.$login.'" tabindex="1" /></td>';

	$title='';

	// Show lock logo
	$width=0;
	$urllogo=DOL_URL_ROOT.'/theme/login_logo.png';
	if (is_readable(DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/img/login_logo.png'))
	{
		$urllogo=DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/login_logo.png';
	}
	print '<td rowspan="2" align="center">';
	if (empty($conf->browser->phone))
	{
		print '<img title="'.$title.'" src="'.$urllogo.'"';
		if ($width) print ' width="'.$width.'"';
		print '>';
	}
	print '</td>';
	print '</tr>'."\n";

	if (function_exists("imagecreatefrompng") && ! $disabled)
	{
		if (! empty($conf->browser->phone)) print '<tr><td colspan="3">&nbsp;</td></tr>';	// More space with phones

		//print "Info session: ".session_name().session_id();print_r($_SESSION);
		print '<tr><td align="left" valign="middle" nowrap="nowrap"> &nbsp; <b>'.$langs->trans("SecurityCode").'</b></td>';
		print '<td valign="top" nowrap="nowrap" align="left" class="e">';

		print '<table style="width: 100px;"><tr>';	// Force width to a small value
		print '<td><input id="securitycode" class="flat" type="text" size="6" maxlength="5" name="code" tabindex="2"></td>';
		$width=128;$height=36;
		if (! empty($conf->browser->phone)) $width=64; $height=24;
		print '<td><img src="'.DOL_URL_ROOT.'/lib/antispamimage.php" border="0" width="'.$width.'" height="'.$height.'"></td>';
		print '<td><a href="'.$_SERVER["PHP_SELF"].'">'.img_refresh().'</a></td>';
		print '</tr></table>';

		print '</td>';
		print '</tr>';
	}

	print '<tr><td colspan="3">&nbsp;</td></tr>'."\n";

	print '<tr><td colspan="3" style="text-align:center;"><br>';
	print '<input id="password" type="submit" '.$disabled.' class="button" name="password" value="'.$langs->trans("SendNewPassword").'" tabindex="4">';
	print '</td></tr>'."\n";

	print "</table>"."\n";

	print "</form>"."\n";

	print '<center>'."\n";
	print '<table width="90%"><tr><td align="center">';
	if (($mode == 'dolibarr' || $mode == 'dolibarr_mdb2') || (! $disabled))
	{
		print '<font style="font-size: 12px;">'.$langs->trans("SendNewPasswordDesc").'</font>'."\n";
	}
	else
	{
		print '<div class="warning" align="center">'.$langs->trans("AuthenticationDoesNotAllowSendNewPassword",$mode).'</div>'."\n";
	}
	print '</td></tr></table><br>';

	if ($message)
	{
		print '<table width="90%"><tr><td align="center" style="font-size: 12px;">';
		print $message.'</td></tr></table><br>';
	}

	print '<br>'."\n";
	print '<a href="'.DOL_URL_ROOT.'/">'.$langs->trans("BackToLoginPage").'</a>';
	print '</center>'."\n";

	print "<br>";
	print "<br>";

	// Fin entete html
	print "\n</body>\n</html>";
}

?>
