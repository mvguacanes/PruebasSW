<?php
/* Copyright (C) 2006-2009 Laurent Destailleur  <eldy@users.sourceforge.net>
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
 *       \file       htdocs/webservices/server.php
 *       \brief      File that is entry point to call Dolibarr WebServices
 *       \version    $Id$
 */

// This is to make Dolibarr working with Plesk
set_include_path($_SERVER['DOCUMENT_ROOT'].'/htdocs');

require_once("../master.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/functions2.lib.php");
require_once(NUSOAP_PATH.'/nusoap.php');		// Include SOAP


dol_syslog("Call Dolibarr webservices interfaces");

// Enable and test if module web services is enabled
if (empty($conf->global->MAIN_MODULE_WEBSERVICES))
{
	$langs->load("admin");
	dol_syslog("Call Dolibarr webservices interfaces with module webservices disabled");
	print $langs->trans("WarningModuleNotActive",'WebServices').'.<br><br>';
	print $langs->trans("ToActivateModule");
	exit;
}

// Create the soap Object
$server = new soap_server();
$server->soap_defencoding='UTF-8';
$ns='dolibarr';
$server->configureWSDL('WebServicesDolibarr',$ns);
$server->wsdl->schemaTargetNamespace=$ns;

// Register methods available for clients
/*
$server->register('getVersions',
array(),								// Tableau parametres entree
array('result' => 'xsd:array'),			// Tableau parametres sortie
$ns);
*/

$server->register('getVersions',
// Tableau parametres entree
array(),
// Tableau parametres sortie
array('dolibarr'=>'xsd:string','os'=>'xsd:string','php'=>'xsd:string','webserver'=>'xsd:string'),
$ns);



// Return the results.
$server->service($HTTP_RAW_POST_DATA);


// Full methods code
function getVersions()
{
	dol_syslog("Function: getVersions");

	$versions_array=array();

	$versions_array['dolibarr']=version_dolibarr();
	$versions_array['os']=version_os();
	$versions_array['php']=version_php();
	$versions_array['webserver']=version_webserver();

	return $versions_array;
}


?>
