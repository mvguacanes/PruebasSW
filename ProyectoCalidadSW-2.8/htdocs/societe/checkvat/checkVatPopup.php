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
 *		\file       htdocs/societe/checkvat/checkVatPopup.php
 *		\ingroup    societe
 *		\brief      Popup screen to validate VAT
 *		\version    $Id$
 */

require ("../../main.inc.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");
require_once(DOL_DOCUMENT_ROOT."/includes/nusoap/lib/nusoap.php");

$langs->load("companies");

$WS_DOL_URL='http://ec.europa.eu/taxation_customs/vies/api/checkVatPort';
$WS_METHOD ='checkVat';


top_htmlhead("", $langs->trans("VATIntraCheckableOnEUSite"));
print '<body style="margin: 10px">';
print '<div>';
print '<div>';

print_fiche_titre($langs->trans("VATIntraCheckableOnEUSite"),'','setup');


if (! $_REQUEST["vatNumber"])
{
	print '<br>';
	print '<font class="error">'.$langs->transnoentities("ErrorFieldRequired",$langs->trans("VATIntraShort")).'</font><br>';
}
else
{
	$countryCode=substr($_REQUEST["vatNumber"],0,2);
	$vatNumber=substr($_REQUEST["vatNumber"],2);
	print '<b>'.$langs->trans("Country").'</b>: '.$countryCode.'<br>';
	print '<b>'.$langs->trans("VATIntraShort").'</b>: '.$vatNumber.'<br>';
	print '<br>';

	// Set the parameters to send to the WebService
	$parameters = array("countryCode" => $countryCode,
						"vatNumber" => $vatNumber);

	// Set the WebService URL
	dol_syslog("Create nusoap_client for URL=".$WS_DOL_URL);
	$soapclient = new nusoap_client($WS_DOL_URL);

	// Call the WebService and store its result in $result.
	dol_syslog("Call method ".$WS_METHOD);
	$result = $soapclient->call($WS_METHOD,$parameters);

	//	print "x".is_array($result)."i";
	//	print_r($result);
	//	print $soapclient->request.'<br>';
	//	print $soapclient->response.'<br>';

	$messagetoshow='';
	print '<b>'.$langs->trans("Response").'</b>:<br>';

	// Service indisponible
	if (! is_array($result) || preg_match('/SERVICE_UNAVAILABLE/i',$result['faultstring']))
	{
		print '<font class="error">'.$langs->trans("ErrorServiceUnavailableTryLater").'</font><br>';
		$messagetoshow=$soapclient->response;
	}
	elseif (preg_match('/TIMEOUT/i',$result['faultstring']))
	{
		print '<font class="error">'.$langs->trans("ErrorServiceUnavailableTryLater").'</font><br>';
		$messagetoshow=$soapclient->response;
	}
	elseif (preg_match('/SERVER_BUSY/i',$result['faultstring']))
	{
		print '<font class="error">'.$langs->trans("ErrorServiceUnavailableTryLater").'</font><br>';
		$messagetoshow=$soapclient->response;
	}
	// Syntaxe ko
	elseif (preg_match('/INVALID_INPUT/i',$result['faultstring'])
	|| ($result['requestDate'] && ! $result['valid']))
	{
		if ($result['requestDate']) print $langs->trans("Date").': '.$result['requestDate'].'<br>';
		print $langs->trans("VATIntraSyntaxIsValid").': <font class="error">'.$langs->trans("No").'</font> (Might be a non europeen VAT)<br>';
		print $langs->trans("VATIntraValueIsValid").': <font class="error">'.$langs->trans("No").'</font> (Might be a non europeen VAT)<br>';
		//$messagetoshow=$soapclient->response;
	}
	else
	{
		// Syntaxe ok
		if ($result['requestDate']) print $langs->trans("Date").': '.$result['requestDate'].'<br>';
		print $langs->trans("VATIntraSyntaxIsValid").': <font class="ok">'.$langs->trans("Yes").'</font><br>';
		print $langs->trans("VATIntraValueIsValid").': ';
		if (preg_match('/MS_UNAVAILABLE/i',$result['faultstring']))
		{
			print '<font class="error">'.$langs->trans("ErrorVATCheckMS_UNAVAILABLE",$countryCode).'</font><br>';
		}
		else
		{
			if ($result['valid'])
			{
				print '<font class="ok">'.$langs->trans("Yes").'</font>';
				print '<br>';
				print $langs->trans("Name").': '.$result['name'].'<br>';
				print $langs->trans("Address").': '.$result['address'].'<br>';
			}
			else
			{
				print '<font class="error">'.$langs->trans("No").'</font>';
				print '<br>';
			}
		}
	}
}

print '<br>';
print $langs->trans("VATIntraManualCheck",$langs->trans("VATIntraCheckURL"),$langs->trans("VATIntraCheckURL")).'<br>';
print '<br>';
print '<center><input type="button" class="button" value="'.$langs->trans("CloseWindow").'" onclick="javascript: window.close()"></center>';

if ($messagetoshow)
{
	print '<br><br>Error returned:<br>';
	print nl2br($messagetoshow);
}


llxFooter('$Date$ - $Revision$',0);
?>
