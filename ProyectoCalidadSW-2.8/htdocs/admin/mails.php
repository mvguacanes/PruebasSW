<?php
/* Copyright (C) 2007-2010 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2009      Regis Houssin        <regis@dolibarr.fr>
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
 *       \file       htdocs/admin/mails.php
 *       \brief      Page de configuration des emails
 *       \version    $Id$
 */

require("./pre.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/admin.lib.php");

$langs->load("companies");
$langs->load("products");
$langs->load("admin");
$langs->load("mails");
$langs->load("other");

if (!$user->admin)
accessforbidden();

$substitutionarrayfortest=array(
'__ID__' => 'TESTIdRecord',
'__EMAIL__' => 'TESTEMail',
'__LASTNAME__' => 'TESTLastname',
'__FIRSTNAME__' => 'TESTFirstname'
);


/*
 * Actions
 */

if (isset($_POST["action"]) && $_POST["action"] == 'update')
{
	dolibarr_set_const($db, "MAIN_DISABLE_ALL_MAILS",   $_POST["MAIN_DISABLE_ALL_MAILS"],'chaine',0,'',$conf->entity);
	dolibarr_set_const($db, "MAIN_MAIL_SENDMODE",       $_POST["MAIN_MAIL_SENDMODE"],'chaine',0,'',0);
	dolibarr_set_const($db, "MAIN_MAIL_SMTP_PORT",      $_POST["MAIN_MAIL_SMTP_PORT"],'chaine',0,'',0);
	dolibarr_set_const($db, "MAIN_MAIL_SMTP_SERVER",    $_POST["MAIN_MAIL_SMTP_SERVER"],'chaine',0,'',0);
	if (isset($_POST["MAIN_MAIL_SMTPS_ID"]))  dolibarr_set_const($db, "MAIN_MAIL_SMTPS_ID",  $_POST["MAIN_MAIL_SMTPS_ID"],'chaine',0,'',0);
	if (isset($_POST["MAIN_MAIL_SMTPS_PW"]))  dolibarr_set_const($db, "MAIN_MAIL_SMTPS_PW",  $_POST["MAIN_MAIL_SMTPS_PW"],'chaine',0,'',0);
	if (isset($_POST["MAIN_MAIL_EMAIL_TLS"])) dolibarr_set_const($db, "MAIN_MAIL_EMAIL_TLS", $_POST["MAIN_MAIL_EMAIL_TLS"],'chaine',0,'',0);
	dolibarr_set_const($db, "MAIN_MAIL_EMAIL_FROM",     $_POST["MAIN_MAIL_EMAIL_FROM"],'chaine',0,'',$conf->entity);

	Header("Location: ".$_SERVER["PHP_SELF"]."?mainmenu=home&leftmenu=setup");
	exit;
}


/*
 * Add file in email form
 */
if ($_POST['addfile'] || $_POST['addfilehtml'])
{
	// Set tmp user directory
	$vardir=$conf->user->dir_output."/".$user->id;
	$upload_dir = $vardir.'/temp/';

	if (! empty($_FILES['addedfile']['tmp_name']))
	{
		if (! is_dir($upload_dir)) create_exdir($upload_dir);

		if (is_dir($upload_dir))
		{
			$result = dol_move_uploaded_file($_FILES['addedfile']['tmp_name'], $upload_dir . "/" . $_FILES['addedfile']['name'],0);
			if ($result > 0)
			{
				$message = '<div class="ok">'.$langs->trans("FileTransferComplete").'</div>';
				//print_r($_FILES);

				include_once(DOL_DOCUMENT_ROOT.'/html.formmail.class.php');
				$formmail = new FormMail($db);
				$formmail->add_attached_files($upload_dir . "/" . $_FILES['addedfile']['name'],$_FILES['addedfile']['name'],$_FILES['addedfile']['type']);
			}
			else if ($result == -99)
			{
				// Files infected by a virus
				$langs->load("errors");
				$mesg = '<div class="error">'.$langs->trans("ErrorFileIsInfectedWithAVirus").'</div>';
			}
			else if ($result < 0)
			{
				// Echec transfert (fichier depassant la limite ?)
				$mesg = '<div class="error">'.$langs->trans("ErrorFileNotUploaded").'</div>';
				// print_r($_FILES);
			}
		}
	}
	if ($_POST['addfile'])     $_GET["action"]='test';
	if ($_POST['addfilehtml']) $_GET["action"]='testhtml';
}

/*
 * Remove file in email form
 */
if (! empty($_POST['removedfile']) || ! empty($_POST['removedfilehtml']))
{
	// Set tmp user directory
	$vardir=$conf->user->dir_output."/".$user->id;
	$upload_dir = $vardir.'/temp/';

	$keytodelete=isset($_POST['removedfile'])?$_POST['removedfile']:$_POST['removedfilehtml'];
	$keytodelete--;

	$listofpaths=array();
	$listofnames=array();
	$listofmimes=array();
	if (! empty($_SESSION["listofpaths"])) $listofpaths=explode(';',$_SESSION["listofpaths"]);
	if (! empty($_SESSION["listofnames"])) $listofnames=explode(';',$_SESSION["listofnames"]);
	if (! empty($_SESSION["listofmimes"])) $listofmimes=explode(';',$_SESSION["listofmimes"]);

	if ($keytodelete >= 0)
	{
		$pathtodelete=$listofpaths[$keytodelete];
		$filetodelete=$listofnames[$keytodelete];
		$result = dol_delete_file($pathtodelete,1);
		if ($result >= 0)
		{
			$message = '<div class="ok">'.$langs->trans("FileWasRemoved",$filetodelete).'</div>';
			//print_r($_FILES);

			include_once(DOL_DOCUMENT_ROOT.'/html.formmail.class.php');
			$formmail = new FormMail($db);
			$formmail->remove_attached_files($keytodelete);
		}
	}
	if ($_POST['removedfile'])     $_GET["action"]='test';
	if ($_POST['removedfilehtml']) $_GET["action"]='testhtml';
}

/*
 * Send mail
 */
if (($_POST['action'] == 'send' || $_POST['action'] == 'sendhtml')
&& ! $_POST['addfile'] && ! $_POST['addfilehtml'] && ! $_POST["removedfile"] && ! $_POST['cancel'])
{
	$error=0;

	$email_from='';
	if (! empty($_POST["fromname"])) $email_from=$_POST["fromname"].' ';
	if (! empty($_POST["frommail"])) $email_from.='<'.$_POST["frommail"].'>';

	$errors_to  = $_POST["errorstomail"];
	$sendto     = $_POST["sendto"];
	$sendtocc   = $_POST["sendtocc"];
	$sendtoccc  = $_POST["sendtoccc"];
	$subject    = $_POST['subject'];
	$body       = $_POST['message'];
	$deliveryreceipt= $_POST["deliveryreceipt"];

	// Create form object
	include_once('../html.formmail.class.php');
	$formmail = new FormMail($db);

	$attachedfiles=$formmail->get_attached_files();
	$filepath = $attachedfiles['paths'];
	$filename = $attachedfiles['names'];
	$mimetype = $attachedfiles['mimes'];

	if (empty($_POST["frommail"]))
	{
		$message='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("MailFrom")).'</div>';
		$_GET["action"]='test';
		$error++;
	}
	if (empty($sendto))
	{
		$message='<div class="error">'.$langs->trans("ErrorFieldRequired",$langs->transnoentities("MailTo")).'</div>';
		$_GET["action"]='test';
		$error++;
	}
	if (! $error)
	{
		// Le message est-il en html
		$msgishtml=0;	// Message is not HTML
		if ($_POST['action'] == 'sendhtml') $msgishtml=1;	// Force message to HTML

		// Pratique les substitutions sur le sujet et message
		$subject=make_substitutions($subject,$substitutionarrayfortest,$langs);
		$body=make_substitutions($body,$substitutionarrayfortest,$langs);

		require_once(DOL_DOCUMENT_ROOT."/lib/CMailFile.class.php");
		$mailfile = new CMailFile($subject,$sendto,$email_from,$body,
		$filepath,$mimetype,$filename,
		$sendtocc, $sendtoccc, $deliveryreceipt, $msgishtml,$errors_to);

		$result=$mailfile->sendfile();

		if ($result)
		{
			$message='<div class="ok">'.$langs->trans("MailSuccessfulySent",$email_from,$sendto).'</div>';
		}
		else
		{
			$message='<div class="error">'.$langs->trans("ResultKo").'<br>'.$mailfile->error.' '.$result.'</div>';
		}

		$_GET["action"]='';
	}
}



/*
 * View
 */

$linuxlike=1;
if (preg_match('/^win/i',PHP_OS)) $linuxlike=0;
if (preg_match('/^mac/i',PHP_OS)) $linuxlike=0;


if (empty($conf->global->MAIN_MAIL_SENDMODE)) $conf->global->MAIN_MAIL_SENDMODE='mail';
$port=! empty($conf->global->MAIN_MAIL_SMTP_PORT)?$conf->global->MAIN_MAIL_SMTP_PORT:ini_get('smtp_port');
if (! $port) $port=25;
$server=! empty($conf->global->MAIN_MAIL_SMTP_SERVER)?$conf->global->MAIN_MAIL_SMTP_SERVER:ini_get('SMTP');
if (! $server) $server='127.0.0.1';


/*
 * View
 */

$wikihelp='EN:Setup EMails|FR:Param??trage EMails|ES:Configuraci??n EMails';
llxHeader($langs->trans("Setup"),'',$wikihelp);

print_fiche_titre($langs->trans("EMailsSetup"),'','setup');

print $langs->trans("EMailsDesc")."<br>\n";
print "<br>\n";

if ($message) print $message.'<br>';

// List of sending methods
$listofmethods=array();
$listofmethods['mail']='PHP mail function';
//$listofmethods['simplemail']='Simplemail class';
$listofmethods['smtps']='SMTP/SMTPS socket library';


if (isset($_GET["action"]) && $_GET["action"] == 'edit')
{
	$html=new Form($db);

	print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
	print '<input type="hidden" name="token" value="'.$_SESSION['newtoken'].'">';
	print '<input type="hidden" name="action" value="update">';

	clearstatcache();
	$var=true;

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	// Disable
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_DISABLE_ALL_MAILS").'</td><td>';
	print $html->selectyesno('MAIN_DISABLE_ALL_MAILS',$conf->global->MAIN_DISABLE_ALL_MAILS,1);
	print '</td></tr>';

	// Method
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_SENDMODE").'</td><td>';

	// SuperAdministrator access only
	if ((empty($conf->global->MAIN_MODULE_MULTICOMPANY)) || ($user->admin && !$user->entity))
	{
		print $html->select_array('MAIN_MAIL_SENDMODE',$listofmethods,$conf->global->MAIN_MAIL_SENDMODE);
	}
	else
	{
		$text = $listofmethods[$conf->global->MAIN_MAIL_SENDMODE];
		if (empty($text)) $text = $langs->trans("Undefined");
		$htmltext = $langs->trans("ContactSuperAdminForChange");
		print $html->textwithpicto($text,$htmltext,1,'superadmin');
		print '<input type="hidden" name="MAIN_MAIL_SENDMODE" value="'.$conf->global->MAIN_MAIL_SENDMODE.'">';
	}
	print '</td></tr>';

	// Server
	$var=!$var;
	print '<tr '.$bc[$var].'><td>';
	if ($linuxlike && ($conf->global->MAIN_MAIL_SENDMODE == 'mail' || $conf->global->MAIN_MAIL_SENDMODE == 'simplemail'))
	{
		print $langs->trans("MAIN_MAIL_SMTP_SERVER_NotAvailableOnLinuxLike");
		print '</td><td>';
		print $langs->trans("SeeLocalSendMailSetup");
	}
	else
	{
		$smtpserver = ini_get('SMTP')?ini_get('SMTP'):$langs->transnoentities("Undefined");
		print $langs->trans("MAIN_MAIL_SMTP_SERVER",$smtpserver);
		print '</td><td>';
		// SuperAdministrator access only
		if ((empty($conf->global->MAIN_MODULE_MULTICOMPANY)) || ($user->admin && !$user->entity))
		{
			print '<input class="flat" name="MAIN_MAIL_SMTP_SERVER" size="18" value="' . $conf->global->MAIN_MAIL_SMTP_SERVER . '">';
		}
		else
		{
			$text = $conf->global->MAIN_MAIL_SMTP_SERVER ? $conf->global->MAIN_MAIL_SMTP_SERVER : $smtpserver;
			$htmltext = $langs->trans("ContactSuperAdminForChange");
			print $html->textwithpicto($text,$htmltext,1,'superadmin');
			print '<input type="hidden" name="MAIN_MAIL_SMTP_SERVER" value="'.$conf->global->MAIN_MAIL_SMTP_SERVER.'">';
		}
	}
	print '</td></tr>';

	// Port
	$var=!$var;
	print '<tr '.$bc[$var].'><td>';
	if ($linuxlike && ($conf->global->MAIN_MAIL_SENDMODE == 'mail' || $conf->global->MAIN_MAIL_SENDMODE == 'simplemail'))
	{
		print $langs->trans("MAIN_MAIL_SMTP_PORT_NotAvailableOnLinuxLike");
		print '</td><td>';
		print $langs->trans("SeeLocalSendMailSetup");
	}
	else
	{
		$smtpport = ini_get('smtp_port')?ini_get('smtp_port'):$langs->transnoentities("Undefined");
		print $langs->trans("MAIN_MAIL_SMTP_PORT",$smtpport);
		print '</td><td>';
		// SuperAdministrator access only
		if ((empty($conf->global->MAIN_MODULE_MULTICOMPANY)) || ($user->admin && !$user->entity))
		{
			print '<input class="flat" name="MAIN_MAIL_SMTP_PORT" size="3" value="' . $conf->global->MAIN_MAIL_SMTP_PORT . '">';
		}
		else
		{
			$text = $conf->global->MAIN_MAIL_SMTP_PORT ? $conf->global->MAIN_MAIL_SMTP_PORT : $smtpport;
			$htmltext = $langs->trans("ContactSuperAdminForChange");
			print $html->textwithpicto($text,$htmltext,1,'superadmin');
			print '<input type="hidden" name="MAIN_MAIL_SMTP_PORT" value="'.$conf->global->MAIN_MAIL_SMTP_PORT.'">';
		}
	}
	print '</td></tr>';

	// ID
	if ($conf->global->MAIN_MAIL_SENDMODE == 'smtps')
	{
		$var=!$var;
		print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_SMTPS_ID").'</td><td>';
		// SuperAdministrator access only
		if ((empty($conf->global->MAIN_MODULE_MULTICOMPANY)) || ($user->admin && !$user->entity))
		{
			print '<input class="flat" name="MAIN_MAIL_SMTPS_ID" size="32" value="' . $conf->global->MAIN_MAIL_SMTPS_ID . '">';
		}
		else
		{
			$htmltext = $langs->trans("ContactSuperAdminForChange");
			print $html->textwithpicto($conf->global->MAIN_MAIL_SMTPS_ID,$htmltext,1,'superadmin');
			print '<input type="hidden" name="MAIN_MAIL_SMTPS_ID" value="'.$conf->global->MAIN_MAIL_SMTPS_ID.'">';
		}
		print '</td></tr>';
	}

	// PW
	if ($conf->global->MAIN_MAIL_SENDMODE == 'smtps')
	{
		$var=!$var;
		print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_SMTPS_PW").'</td><td>';
		// SuperAdministrator access only
		if ((empty($conf->global->MAIN_MODULE_MULTICOMPANY)) || ($user->admin && !$user->entity))
		{
			print '<input class="flat" name="MAIN_MAIL_SMTPS_PW" size="32" value="' . $conf->global->MAIN_MAIL_SMTPS_PW . '">';
		}
		else
		{
			$htmltext = $langs->trans("ContactSuperAdminForChange");
			print $html->textwithpicto($conf->global->MAIN_MAIL_SMTPS_PW,$htmltext,1,'superadmin');
			print '<input type="hidden" name="MAIN_MAIL_SMTPS_PW" value="'.$conf->global->MAIN_MAIL_SMTPS_PW.'">';
		}
		print '</td></tr>';
	}

	// TLS
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_EMAIL_TLS").'</td><td>';
	if ($conf->global->MAIN_MAIL_SENDMODE == 'smtps')
	{
		if (function_exists('openssl_open'))
		{
			print $html->selectyesno('MAIN_MAIL_EMAIL_TLS',$conf->global->MAIN_MAIL_EMAIL_TLS,1);
		}
		else print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
	}
	else print yn(0).' ('.$langs->trans("NotSupported").')';
	print '</td></tr>';

	// From
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_EMAIL_FROM",ini_get('sendmail_from')?ini_get('sendmail_from'):$langs->transnoentities("Undefined")).'</td><td><input class="flat" name="MAIN_MAIL_EMAIL_FROM" size="32" value="' . $conf->global->MAIN_MAIL_EMAIL_FROM . '"></td></tr>';

	print '</table>';

	print '<br><center>';
	print '<input class="button" type="submit" value="'.$langs->trans("Save").'">';
	print '</center>';

	print '</form>';
	print '<br>';
}
else
{
	$var=true;

	print '<table class="noborder" width="100%">';
	print '<tr class="liste_titre"><td>'.$langs->trans("Parameter").'</td><td>'.$langs->trans("Value").'</td></tr>';

	// Disable
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_DISABLE_ALL_MAILS").'</td><td>'.yn($conf->global->MAIN_DISABLE_ALL_MAILS).'</td></tr>';

	// Method
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_SENDMODE").'</td><td>';
	$text=$listofmethods[$conf->global->MAIN_MAIL_SENDMODE];
	if (empty($text)) $text=$langs->trans("Undefined").img_warning();
	print $text;
	print '</td></tr>';

	// Server
	$var=!$var;
	if ($linuxlike && ($conf->global->MAIN_MAIL_SENDMODE == 'mail' || $conf->global->MAIN_MAIL_SENDMODE == 'simplemail'))
	{
		print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_SMTP_SERVER_NotAvailableOnLinuxLike").'</td><td>'.$langs->trans("SeeLocalSendMailSetup").'</td></tr>';
	}
	else
	{
		print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_SMTP_SERVER",ini_get('SMTP')?ini_get('SMTP'):$langs->transnoentities("Undefined")).'</td><td>'.$conf->global->MAIN_MAIL_SMTP_SERVER.'</td></tr>';
	}

	// Port
	$var=!$var;
	if ($linuxlike && ($conf->global->MAIN_MAIL_SENDMODE == 'mail' || $conf->global->MAIN_MAIL_SENDMODE == 'simplemail'))
	{
		print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_SMTP_PORT_NotAvailableOnLinuxLike").'</td><td>'.$langs->trans("SeeLocalSendMailSetup").'</td></tr>';
	}
	else
	{
		print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_SMTP_PORT",ini_get('smtp_port')?ini_get('smtp_port'):$langs->transnoentities("Undefined")).'</td><td>'.$conf->global->MAIN_MAIL_SMTP_PORT.'</td></tr>';
	}

	// SMTPS ID
	$var=!$var;
	if ($conf->global->MAIN_MAIL_SENDMODE == 'smtps')
	{
		print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_SMTPS_ID").'</td><td>'.$conf->global->MAIN_MAIL_SMTPS_ID.'</td></tr>';
	}

	// SMTPS PW
	$var=!$var;
	if ($conf->global->MAIN_MAIL_SENDMODE == 'smtps')
	{
		print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_SMTPS_PW").'</td><td>'.$conf->global->MAIN_MAIL_SMTPS_PW.'</td></tr>';
	}

	// TLS
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_EMAIL_TLS").'</td><td>';
	if ($conf->global->MAIN_MAIL_SENDMODE == 'smtps')
	{
		if (function_exists('openssl_open'))
		{
			print yn($conf->global->MAIN_MAIL_EMAIL_TLS);
		}
		else print yn(0).' ('.$langs->trans("YourPHPDoesNotHaveSSLSupport").')';
	}
	else print yn(0).' ('.$langs->trans("NotSupported").')';
	print '</td></tr>';

	// From
	$var=!$var;
	print '<tr '.$bc[$var].'><td>'.$langs->trans("MAIN_MAIL_EMAIL_FROM",ini_get('sendmail_from')?ini_get('sendmail_from'):$langs->transnoentities("Undefined")).'</td><td>'.$conf->global->MAIN_MAIL_EMAIL_FROM.'</td></tr>';

	print '</table>';


	// Boutons actions

	print '<div class="tabsAction">';

	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=edit">'.$langs->trans("Modify").'</a>';

	if ($conf->global->MAIN_MAIL_SENDMODE != 'mail' || ! $linuxlike)
	{
		if (function_exists('fsockopen') && $port && $server)
		{
			print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=testconnect">'.$langs->trans("DoTestServerAvailability").'</a>';
		}
	}
	else
	{
		print '<a class="butActionRefused" href="#" title="'.$langs->trans("FeatureNotAvailableOnLinux").'">'.$langs->trans("DoTestServerAvailability").'</a>';
	}

	print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=test&amp;mode=init">'.$langs->trans("DoTestSend").'</a>';

	if ($conf->fckeditor->enabled)
	{
		print '<a class="butAction" href="'.$_SERVER["PHP_SELF"].'?action=testhtml&amp;mode=init">'.$langs->trans("DoTestSendHTML").'</a>';
	}

	print '</div>';


	// Run the test to connect
	if ($_GET["action"] == 'testconnect')
	{
		print '<br>';
		print_titre($langs->trans("DoTestServerAvailability"));

		// If we use SSL/TLS
		if (! empty($conf->global->MAIN_MAIL_EMAIL_TLS) && function_exists('openssl_open')) $server='ssl://'.$server;

		include_once(DOL_DOCUMENT_ROOT."/lib/CMailFile.class.php");
		$mail = new CMailFile('','','','');
		$result=$mail->check_server_port($server,$port);
		if ($result) print '<div class="ok">'.$langs->trans("ServerAvailableOnIPOrPort",$server,$port).'</div>';
		else
		{
			print '<div class="error">'.$langs->trans("ServerNotAvailableOnIPOrPort",$server,$port);
			if ($mail->error) print ' - '.$langs->convToOutputCharset($mail->error,'ISO-8859-1');
			print '</div>';
		}
		print '<br>';
	}

	// Affichage formulaire de TEST simple
	if ($_GET["action"] == 'test')
	{
		print '<br>';
		print_titre($langs->trans("DoTestSend"));

		// Cree l'objet formulaire mail
		include_once(DOL_DOCUMENT_ROOT."/html.formmail.class.php");
		$formmail = new FormMail($db);
		$formmail->fromname = (isset($_POST['fromname'])?$_POST['fromname']:$conf->global->MAIN_MAIL_EMAIL_FROM);
		$formmail->frommail = (isset($_POST['frommail'])?$_POST['frommail']:$conf->global->MAIN_MAIL_EMAIL_FROM);
		$formmail->withfromreadonly=0;
		$formmail->withsubstit=0;
		$formmail->withfrom=1;
		$formmail->witherrorsto=1;
		$formmail->withto=(isset($_POST['sendto'])?$_POST['sendto']:$user->email?$user->email:1);
		$formmail->withtocc=(isset($_POST['sendtocc'])?$_POST['sendtocc']:1);
		$formmail->withtoccc=(isset($_POST['sendtoccc'])?$_POST['sendtoccc']:1);
		$formmail->withtopic=(isset($_POST['subject'])?$_POST['subject']:$langs->trans("Test"));
		$formmail->withtopicreadonly=0;
		$formmail->withfile=2;
		$formmail->withbody=(isset($_POST['message'])?$_POST['message']:$langs->trans("PredefinedMailTest"));
		$formmail->withbodyreadonly=0;
		$formmail->withcancel=1;
		$formmail->withdeliveryreceipt=1;
		$formmail->withfckeditor=0;
		// Tableau des substitutions
		$formmail->substit=$substitutionarrayfortest;
		// Tableau des parametres complementaires du post
		$formmail->param["action"]="send";
		$formmail->param["models"]="body";
		$formmail->param["mailid"]=$mil->id;
		$formmail->param["returnurl"]=DOL_URL_ROOT."/admin/mails.php";

		// Init list of files
		if (! empty($_REQUEST["mode"]) && $_REQUEST["mode"]=='init')
		{
			$formmail->clear_attached_files();
		}

		$formmail->show_form('addfile','removefile');

		print '<br>';
	}

	// Affichage formulaire de TEST HTML
	if ($_GET["action"] == 'testhtml')
	{
		print '<br>';
		print_titre($langs->trans("DoTestSendHTML"));

		// Cree l'objet formulaire mail
		include_once(DOL_DOCUMENT_ROOT."/html.formmail.class.php");
		$formmail = new FormMail($db);
		$formmail->fromname = (isset($_POST['fromname'])?$_POST['fromname']:$conf->global->MAIN_MAIL_EMAIL_FROM);
		$formmail->frommail = (isset($_POST['frommail'])?$_POST['frommail']:$conf->global->MAIN_MAIL_EMAIL_FROM);
		$formmail->withfromreadonly=0;
		$formmail->withsubstit=0;
		$formmail->withfrom=1;
		$formmail->witherrorsto=1;
		$formmail->withto=(isset($_POST['sendto'])?$_POST['sendto']:$user->email?$user->email:1);
		$formmail->withtocc=(isset($_POST['sendtocc'])?$_POST['sendtocc']:1);
		$formmail->withtoccc=(isset($_POST['sendtoccc'])?$_POST['sendtoccc']:1);
		$formmail->withtopic=(isset($_POST['subject'])?$_POST['subject']:$langs->trans("Test"));
		$formmail->withtopicreadonly=0;
		$formmail->withfile=2;
		$formmail->withbody=(isset($_POST['message'])?$_POST['message']:$langs->trans("PredefinedMailTestHtml"));
		$formmail->withbodyreadonly=0;
		$formmail->withcancel=1;
		$formmail->withdeliveryreceipt=1;
		$formmail->withfckeditor=1;
		// Tableau des substitutions
		$formmail->substit=$substitutionarrayfortest;
		// Tableau des parametres complementaires du post
		$formmail->param["action"]="sendhtml";
		$formmail->param["models"]="body";
		$formmail->param["mailid"]=$mil->id;
		$formmail->param["returnurl"]=DOL_URL_ROOT."/admin/mails.php";

		// Init list of files
		if (! empty($_REQUEST["mode"]) && $_REQUEST["mode"]=='init')
		{
			$formmail->clear_attached_files();
		}

		$formmail->show_form('addfilehtml','removefilehtml');

		print '<br>';
	}
}


$db->close();

llxFooter('$Date$ - $Revision$');
?>
