<?php
/* Copyright (C) 2008-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2008-2009 Regis Houssin        <regis@dolibarr.fr>
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
 * or see http://www.gnu.org/
 */

/**
 *  \file			htdocs/lib/security.lib.php
 *  \brief			Set of function used for dolibarr security
 *  \version		$Id$
 */


/**
 *	\brief      Show Dolibarr default login page
 *	\param		langs		Lang object
 *	\param		conf		Conf object
 *	\param		mysoc		Company object
 */
function dol_loginfunction($langs,$conf,$mysoc)
{
	global $dolibarr_main_demo,$db;

	$langcode=(empty($_GET["lang"])?'auto':$_GET["lang"]);
	$langs->setDefaultLang($langcode);

	$langs->load("main");
	$langs->load("other");
	$langs->load("help");

	$main_authentication=$conf->file->main_authentication;
	$session_name=session_name();

	$php_self = $_SERVER['PHP_SELF'];
	$php_self.= $_SERVER["QUERY_STRING"]?'?'.$_SERVER["QUERY_STRING"]:'';

	// Select templates
	if ($conf->browser->phone)
	{
		if (file_exists(DOL_DOCUMENT_ROOT."/theme/phones/".$conf->browser->phone))
		{
			$template_dir=DOL_DOCUMENT_ROOT."/theme/phones/".$conf->browser->phone."/templates/";
		}
		else
		{
			$template_dir=DOL_DOCUMENT_ROOT."/theme/phones/others/templates/";
		}
	}
	else
	{
		if (file_exists(DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/templates/login.tpl")
			|| file_exists(DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/templates/login.tpl.php"))
		{
			$template_dir=DOL_DOCUMENT_ROOT."/theme/".$conf->theme."/templates/";
		}
		else
		{
			$template_dir=DOL_DOCUMENT_ROOT.'/core/templates/';
		}

		$conf->css = "/theme/".$conf->theme."/".$conf->theme.".css.php?lang=".$langs->defaultlang;
	}

	// Set cookie for timeout management
	$sessiontimeout='DOLSESSTIMEOUT_'.md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"]);
	if (! empty($conf->global->MAIN_SESSION_TIMEOUT)) setcookie($sessiontimeout, $conf->global->MAIN_SESSION_TIMEOUT, 0, "/", '', 0);

	if (! empty($_REQUEST["urlfrom"])) $_SESSION["urlfrom"]=$_REQUEST["urlfrom"];
	else unset($_SESSION["urlfrom"]);

	if (! $_REQUEST["username"]) $focus_element='username';
	else $focus_element='password';

	$login_background=DOL_URL_ROOT.'/theme/login_background.png';
	if (file_exists(DOL_DOCUMENT_ROOT.'/theme/'.$conf->theme.'/img/login_background.png'))
	{
		$login_background=DOL_URL_ROOT.'/theme/'.$conf->theme.'/img/login_background.png';
	}

	// Title
	$title='Dolibarr '.DOL_VERSION;
	if (! empty($conf->global->MAIN_APPLICATION_TITLE)) $title=$conf->global->MAIN_APPLICATION_TITLE;

	$demologin='';
	$demopassword='';
	if (! empty($dolibarr_main_demo))
	{
		$tab=explode(',',$dolibarr_main_demo);
		$demologin=$tab[0];
		$demopassword=$tab[1];
	}

	// Entity cookie
	if (! empty($conf->global->MAIN_MODULE_MULTICOMPANY))
	{
		$lastuser = '';
		$lastentity = $_POST['entity'];

		if (! empty($conf->global->MAIN_MULTICOMPANY_COOKIE))
		{
			$entityCookieName = 'DOLENTITYID_'.md5($_SERVER["SERVER_NAME"].$_SERVER["DOCUMENT_ROOT"]);
			if (isset($_COOKIE[$entityCookieName]))
			{
				include_once(DOL_DOCUMENT_ROOT . "/core/cookie.class.php");

				$cryptkey = (! empty($conf->file->cookie_cryptkey) ? $conf->file->cookie_cryptkey : '' );

				$entityCookie = new DolCookie($cryptkey);
				$cookieValue = $entityCookie->_getCookie($entityCookieName);
				list($lastuser, $lastentity) = explode('|', $cookieValue);
			}
		}
	}

	// Login
	$login = (!empty($lastuser)?$lastuser:(isset($_REQUEST["username"])?$_REQUEST["username"]:$demologin));
	$password = $demopassword;

	// Show logo (search in order: small company logo, large company logo, theme logo, common logo)
	$width=0;
	$rowspan=2;
	$urllogo=DOL_URL_ROOT.'/theme/login_logo.png';

	if (! empty($mysoc->logo_small) && is_readable($conf->mycompany->dir_output.'/logos/thumbs/'.$mysoc->logo_small))
	{
		$urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode('thumbs/'.$mysoc->logo_small);
	}
	elseif (! empty($mysoc->logo) && is_readable($conf->mycompany->dir_output.'/logos/'.$mysoc->logo))
	{
		$urllogo=DOL_URL_ROOT.'/viewimage.php?modulepart=companylogo&amp;file='.urlencode($mysoc->logo);
		$width=128;
	}
	elseif (is_readable(DOL_DOCUMENT_ROOT.'/theme/dolibarr_logo.png'))
	{
		$urllogo=DOL_URL_ROOT.'/theme/dolibarr_logo.png';
	}

	if (! empty($conf->global->MAIN_MODULE_MULTICOMPANY)) $rowspan++;

	// Entity field
	$select_entity='';
	if (! empty($conf->global->MAIN_MODULE_MULTICOMPANY))
	{
		require_once(DOL_DOCUMENT_ROOT.'/multicompany/multicompany.class.php');

		$mc = new Multicompany($db);
		$mc->getEntities(0,1);

		$select_entity=$mc->select_entities($mc->entities,$lastentity,'tabindex="3"');
	}

	// Security graphical code
	$captcha=0;
	$captcha_refresh='';
	if (function_exists("imagecreatefrompng") && ! empty($conf->global->MAIN_SECURITY_ENABLECAPTCHA))
	{
		$captcha=1;
		$captcha_refresh=img_refresh();
	}

	// Extra link
	$forgetpasslink=0;
	$helpcenterlink=0;
	if (empty($conf->global->MAIN_SECURITY_DISABLEFORGETPASSLINK) || empty($conf->global->MAIN_HELPCENTER_DISABLELINK))
	{
		if (empty($conf->global->MAIN_SECURITY_DISABLEFORGETPASSLINK))
		{
			$forgetpasslink=1;
		}

		if (empty($conf->global->MAIN_HELPCENTER_DISABLELINK))
		{
			$helpcenterlink=1;
		}
	}

	// Home message
	if (! empty($conf->global->MAIN_HOME))
	{
		$i=0;
		while (preg_match('/__\(([a-zA-Z]+)\)__/i',$conf->global->MAIN_HOME,$reg) && $i < 100)
		{
			$conf->global->MAIN_HOME=preg_replace('/__\('.$reg[1].'\)__/i',$langs->trans($reg[1]),$conf->global->MAIN_HOME);
			$i++;
		}
	}
	$main_home=nl2br($conf->global->MAIN_HOME);

	$conf_css=DOL_URL_ROOT.$conf->css;


	// START SMARTY
	if ($conf->global->MAIN_SMARTY)
	{
		global $smarty;

		$smarty->template_dir=$template_dir;

		$smarty->assign('conf_css', $conf_css);
		$smarty->assign('langs', $langs);

		if (! empty($conf->global->MAIN_HTML_HEADER)) $smarty->assign('main_html_header', $conf->global->MAIN_HTML_HEADER);

		$smarty->assign('php_self', $php_self);
		$smarty->assign('character_set_client',$conf->file->character_set_client);

		$smarty->assign('theme', 'default');

		$smarty->assign('dol_url_root', DOL_URL_ROOT);

		$smarty->assign('focus_element', $focus_element);

		$smarty->assign('login_background', $login_background);

		$smarty->assign('title', $title);

		$smarty->assign('login', $login);
		$smarty->assign('password', $password);

		$smarty->assign('logo', $urllogo);
		$smarty->assign('logo_width', $width);
		$smarty->assign('logo_rowspan', $rowspan);

		$smarty->assign('select_entity', $select_entity);
		$smarty->assign('captcha', $captcha);
		$smarty->assign('captcha_refresh', $captcha_refresh);

		$smarty->assign('forgetpasslink', $forgetpasslink);
		$smarty->assign('helpcenterlink', $helpcenterlink);

		$smarty->assign('main_home', $main_home);

	    // Google Adsense (ex: demo mode)
		if (! empty($conf->global->MAIN_GOOGLE_AD_CLIENT) && ! empty($conf->global->MAIN_GOOGLE_AD_SLOT))
		{
			$smarty->assign('main_google_ad_client', $conf->global->MAIN_GOOGLE_AD_CLIENT);
			$smarty->assign('main_google_ad_name', $conf->global->MAIN_GOOGLE_AD_NAME);
			$smarty->assign('main_google_ad_slot', $conf->global->MAIN_GOOGLE_AD_SLOT);
			$smarty->assign('main_google_ad_width', $conf->global->MAIN_GOOGLE_AD_WIDTH);
			$smarty->assign('main_google_ad_height', $conf->global->MAIN_GOOGLE_AD_HEIGHT);

			$google_ad_template = DOL_DOCUMENT_ROOT."/core/templates/google_ad.tpl";
			$smarty->assign('google_ad_tpl', $google_ad_template);
		}

		if (! empty($conf->global->MAIN_HTML_FOOTER)) $smarty->assign('main_html_footer', $conf->global->MAIN_HTML_FOOTER);
		$smarty->assign('main_authentication', $main_authentication);
		$smarty->assign('session_name', $session_name);

		// Message
		if (! empty($_SESSION["dol_loginmesg"]))
		{
			$smarty->assign('dol_loginmesg', $_SESSION["dol_loginmesg"]);
		}

		// Creation du template
		$smarty->display('login.tpl');	// To use Smarty
		// Suppression de la version compilee
		$smarty->clear_compiled_tpl('login.tpl');

		// END SMARTY
	}
	else
	{
		include($template_dir.'login.tpl.php');	// To use native PHP
	}

	$_SESSION["dol_loginmesg"] = '';
}

/**
 *  \brief      Fonction pour initialiser un salt pour la fonction crypt
 *  \param		$type		2=>renvoi un salt pour cryptage DES
 *							12=>renvoi un salt pour cryptage MD5
 *							non defini=>renvoi un salt pour cryptage par defaut
 *	\return		string		Chaine salt
 */
function makesalt($type=CRYPT_SALT_LENGTH)
{
	dol_syslog("security.lib.php::makesalt type=".$type);
	switch($type)
	{
	case 12:	// 8 + 4
		$saltlen=8; $saltprefix='$1$'; $saltsuffix='$'; break;
	case 8:		// 8 + 4 (Pour compatibilite, ne devrait pas etre utilise)
		$saltlen=8; $saltprefix='$1$'; $saltsuffix='$'; break;
	case 2:		// 2
	default: 	// by default, fall back on Standard DES (should work everywhere)
		$saltlen=2; $saltprefix=''; $saltsuffix=''; break;
	}
	$salt='';
	while(strlen($salt) < $saltlen) $salt.=chr(mt_rand(64,126));

	$result=$saltprefix.$salt.$saltsuffix;
	dol_syslog("security.lib.php::makesalt return=".$result);
	return $result;
}

/**
 *  \brief   	Encode\decode database password in config file
 *  \param   	level   	Encode level: 0 no encoding, 1 encoding
 *	\return		int			<0 if KO, >0 if OK
 */
function encodedecode_dbpassconf($level=0)
{
	dol_syslog("security.lib::encodedecode_dbpassconf level=".$level, LOG_DEBUG);
	$config = '';
	$passwd='';
	$passwd_crypted='';

	if ($fp = fopen(DOL_DOCUMENT_ROOT.'/conf/conf.php','r'))
	{
		while(!feof($fp))
		{
			$buffer = fgets($fp,4096);

			$lineofpass=0;

			if (preg_match('/^[^#]*dolibarr_main_db_encrypted_pass[\s]*=[\s]*(.*)/i',$buffer,$reg))	// Old way to save crypted value
			{
				$val = trim($reg[1]);	// This also remove CR/LF
				$val=preg_replace('/^["\']/','',$val);
				$val=preg_replace('/["\'][\s;]*$/','',$val);
				if (! empty($val))
				{
					$passwd_crypted = $val;
					$val = dol_decode($val);
					$passwd = $val;
					$lineofpass=1;
				}
			}
			elseif (preg_match('/^[^#]*dolibarr_main_db_pass[\s]*=[\s]*(.*)/i',$buffer,$reg))
			{
				$val = trim($reg[1]);	// This also remove CR/LF
				$val=preg_replace('/^["\']/','',$val);
				$val=preg_replace('/["\'][\s;]*$/','',$val);
				if (preg_match('/crypted:/i',$buffer))
				{
					$val = preg_replace('/crypted:/i','',$val);
					$passwd_crypted = $val;
					$val = dol_decode($val);
					$passwd = $val;
				}
				else
				{
					$passwd = $val;
					$val = dol_encode($val);
					$passwd_crypted = $val;
				}
				$lineofpass=1;
			}

			// Output line
			if ($lineofpass)
			{
				// Add value at end of file
				if ($level == 0)
				{
					$config .= '$dolibarr_main_db_pass="'.$passwd.'";'."\n";
				}
				if ($level == 1)
				{
					$config .= '$dolibarr_main_db_pass="crypted:'.$passwd_crypted.'";'."\n";
				}

				//print 'passwd = '.$passwd.' - passwd_crypted = '.$passwd_crypted;
				//exit;
			}
			else
			{
				$config .= $buffer;
			}
		}
		fclose($fp);

		// Write new conf file
		$file=DOL_DOCUMENT_ROOT.'/conf/conf.php';
		if ($fp = @fopen($file,'w'))
		{
			fputs($fp, $config, strlen($config));
			fclose($fp);
			// It's config file, so we set read permission for creator only.
			// Should set permission to web user and groups for users used by batch
			//@chmod($file, octdec('0600'));

			return 1;
		}
		else
		{
			dol_syslog("security.lib::encodedecode_dbpassconf Failed to open conf.php file for writing", LOG_WARNING);
			return -1;
		}
	}
	else
	{
		dol_syslog("security.lib::encodedecode_dbpassconf Failed to read conf.php", LOG_ERR);
		return -2;
	}
}

/**
 *	\brief   Encode une chaine de caractere
 *	\param   chaine			chaine de caracteres a encoder
 *	\return  string_coded  	chaine de caracteres encodee
 */
function dol_encode($chain)
{
	for($i=0;$i<strlen($chain);$i++)
	{
		$output_tab[$i] = chr(ord(substr($chain,$i,1))+17);
	}

	$string_coded = base64_encode(implode("",$output_tab));
	return $string_coded;
}

/**
 *	\brief   Decode une chaine de caractere
 *	\param   chain    chaine de caracteres a decoder
 *	\return  string_coded  chaine de caracteres decodee
 */
function dol_decode($chain)
{
	$chain = base64_decode($chain);

	for($i=0;$i<strlen($chain);$i++)
	{
		$output_tab[$i] = chr(ord(substr($chain,$i,1))-17);
	}

	$string_decoded = implode("",$output_tab);
	return $string_decoded;
}


/**
 * Return array of ciphers mode available
 *
 * @return strAv	Configuration file content
 */
function dol_efc_config()
{
	// Make sure we can use mcrypt_generic_init
	if (!function_exists("mcrypt_generic_init"))
	{
		return -1;
	}

	// Set a temporary $key and $data for encryption tests
	$key = md5(time() . getmypid());
	$data = mt_rand();

	// Get and sort available cipher methods
	$ciphers = mcrypt_list_algorithms();
	natsort($ciphers);

	// Get and sort available cipher modes
	$modes = mcrypt_list_modes();
	natsort($modes);

	foreach ($ciphers as $cipher)
	{
		foreach ($modes as $mode)
		{
			// Not Compatible
			$result = 'false';

			// open encryption module
			$td = @mcrypt_module_open($cipher, '', $mode, '');

			// if we could open the cipher
			if ($td)
			{
				// try to generate the iv
				$iv = @mcrypt_create_iv(mcrypt_enc_get_iv_size ($td), MCRYPT_RAND);

				// if we could generate the iv
				if ($iv)
				{
					// initialize encryption
					@mcrypt_generic_init ($td, $key, $iv);

					// encrypt data
					$encrypted_data = mcrypt_generic($td, $data);

					// cleanup
					mcrypt_generic_deinit($td);

					// No error issued
					$result = 'true';
				}

				// close
				@mcrypt_module_close($td);
			}

			if ($result == "true") $available["$cipher"][] = $mode;
		}
	}

	if (count($available) > 0)
	{
       // Content of configuration
       $strAv = "<?php\n";
       $strAv.= "/* Copyright (C) 2003 HumanEasy, Lda. <humaneasy@sitaar.com>\n";
       $strAv.= " * Copyright (C) 2009 Regis Houssin <regis@dolibarr.fr>\n";
       $strAv.= " *\n";
       $strAv.= " * All rights reserved.\n";
       $strAv.= " * This file is licensed under GNU GPL version 2 or above.\n";
       $strAv.= " * Please visit http://www.gnu.org to now more about it.\n";
       $strAv.= " */\n\n";
       $strAv.= "/**\n";
       $strAv.= " *  Name: EasyFileCrypt Extending Crypt Class\n";
       $strAv.= " *  Version: 1.0\n";
       $strAv.= " *  Created: ".date("r")."\n";
       $strAv.= " *  Ciphers Installed on this system: ".count($ciphers)."\n";
       $strAv.= " */\n\n";
       $strAv.= "    \$xfss = Array ( ";

       foreach ($ciphers as $avCipher) {

           $v = "";
           if (count($available["$avCipher"]) > 0) {
              foreach ($available["$avCipher"] as $avMode)
                  $v .= " '".$avMode."', ";

                  $i = strlen($v) - 2;
                  if ($v[$i] == ",")
                    $v = substr($v, 2, $i - 3);
           }
           if (!empty($v)) $v = " '".$v."' ";
           $strAv .= "'".$avCipher."' => Array (".$v."),\n                    ";
       }
       $strAv = rtrim($strAv);
       if ($strAv[strlen($strAv) - 1] == ",")
          $strAv = substr($strAv, 0, strlen($strAv) - 1);
       $strAv .= " );\n\n";
       $strAv .= "?>";

       return $strAv;
   }
}

?>