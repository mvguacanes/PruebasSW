<?php
/* Copyright (C) 2003      Rodolphe Quiedeville <rodolphe@quiedeville.org>
 * Copyright (C) 2004-2008 Laurent Destailleur  <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2006 Regis Houssin        <regis@dolibarr.fr>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
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
 *	\file       htdocs/includes/modules/livraison/pdf/pdf_sirocco.modules.php
 *	\ingroup    livraison
 *	\brief      Fichier de la classe permettant de generer les bons de livraison au mod�le Sirocco
 *	\version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT."/includes/modules/livraison/modules_livraison.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");


/**
 *	\class      pdf_sirocco
 *	\brief      Classe permettant de generer les bons de livraison au modele Sirocco
 */

class pdf_sirocco extends ModelePDFDeliveryOrder
{

	/**		\brief      Constructor
	 *		\param	    db	    Database handler
	 */
	function pdf_sirocco($db)
	{
		global $conf,$langs,$mysoc;

        $langs->load("main");
        $langs->load("bills");
		$langs->load("sendings");
		$langs->load("companies");

        $this->db = $db;
		$this->name = "sirocco";
		$this->description = $langs->trans("DocumentModelSirocco");

		// Dimension page pour format A4
		$this->type = 'pdf';
		$this->page_largeur = 210;
		$this->page_hauteur = 297;
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=10;
		$this->marge_droite=10;
		$this->marge_haute=10;
		$this->marge_basse=10;

		// Recupere emmetteur
        $this->emetteur=$mysoc;
        if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si n'�tait pas d�fini

		$this->tva=array();
	}


	/**		\brief      Renvoi derniere erreur
	 *		\return     string      Derniere erreur
	 */
	function pdferror()
	{
		return $this->error;
	}


	/**
	 *	\brief      Fonction generant le bon de livraison sur le disque
	 *	\param	    delivery		Object livraison a generer
	 *	\param		outputlangs		Lang output object
	 *	\return	    int         	1 if OK, <=0 if KO
	 */
	function write_file($object,$outputlangs)
	{
		global $user,$conf,$langs;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// Force output charset to ISO, because, FPDF expect text encoded in ISO
		$sav_charset_output=$outputlangs->charset_output;
		$outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("bills");
		$outputlangs->load("products");
		$outputlangs->load("deliveries");
		$outputlangs->load("sendings");

		if ($conf->expedition->dir_output."/receipt")
		{
			// If $object is id instead of object
			if (! is_object($object))
			{
				$id = $object;
				$object = new Livraison($this->db);
				$object->fetch($id);
				$object->id = $id;
				if ($result < 0)
				{
					dol_print_error($db,$object->error);
				}
			}

			$nblignes = sizeof($object->lignes);

			$objectref = dol_sanitizeFileName($object->ref);
			$dir = $conf->expedition->dir_output."/receipt";
			if (! preg_match('/specimen/i',$objectref)) $dir.= "/" . $objectref;
			$file = $dir . "/" . $objectref . ".pdf";

			if (! file_exists($dir))
			{
				if (create_exdir($dir) < 0)
				{
					$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
					return 0;
				}
			}

			if (file_exists($dir))
			{
				// Protection et encryption du pdf
				if ($conf->global->PDF_SECURITY_ENCRYPTION)
				{
					$pdf=new FPDI_Protection('P','mm',$this->format);
					$pdfrights = array('print'); // Ne permet que l'impression du document
					$pdfuserpass = ''; // Mot de passe pour l'utilisateur final
					$pdfownerpass = NULL; // Mot de passe du proprietaire, cree aleatoirement si pas defini
					$pdf->SetProtection($pdfrights,$pdfuserpass,$pdfownerpass);
				}
				else
				{
					$pdf=new FPDI('P','mm',$this->format);
				}


				// Complete object by loading several other informations
				$expedition=new Expedition($this->db);
				$result = $expedition->fetch($object->expedition_id);

				$commande = new Commande($this->db);
				if ($expedition->origin == 'commande')
				{
					$commande->fetch($expedition->origin_id);
				}
				$object->commande=$commande;


				$pdf->Open();
				$pagenb=0;
				$pdf->SetDrawColor(128,128,128);

				$pdf->SetTitle($outputlangs->convToOutputCharset($object->ref));
				$pdf->SetSubject($outputlangs->transnoentities("DeliveryOrder"));
				$pdf->SetCreator("Dolibarr ".DOL_VERSION);
				$pdf->SetAuthor($outputlangs->convToOutputCharset($user->fullname));
				$pdf->SetKeyWords($outputlangs->convToOutputCharset($object->ref)." ".$outputlangs->transnoentities("DeliveryOrder"));
				if ($conf->global->MAIN_DISABLE_PDF_COMPRESSION) $pdf->SetCompression(false);

				$pdf->SetMargins($this->marge_gauche, $this->marge_haute, $this->marge_droite);   // Left, Top, Right
				$pdf->SetAutoPageBreak(1,0);

				// New page
				$pdf->AddPage();
				$pagenb++;
				$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('Arial','', 9);
				$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);

				$tab_top = 100;
				$tab_top_newpage = 50;
				$tab_height = 140;
				$tab_height_newpage = 190;

				$iniY = $tab_top + 7;
				$curY = $tab_top + 7;
				$nexY = $tab_top + 7;

				for ($i = 0 ; $i < $nblignes ; $i++)
				{
					$curY = $nexY;

					// Description de la ligne produit
					$libelleproduitservice=pdf_getlinedesc($object->lignes[$i],$outputlangs);

					$pdf->SetFont('Arial','', 9);   // Dans boucle pour gerer multi-page

					$pdf->writeHTMLCell(100, 3, 30, $curY, $outputlangs->convToOutputCharset($libelleproduitservice), 0, 1);

					$pdf->SetFont('Arial','', 9);   // Dans boucle pour gerer multi-page
					$nexY = $pdf->GetY();

					$pdf->SetXY (10, $curY );

					$pdf->MultiCell(20, 3, $outputlangs->convToOutputCharset($object->lignes[$i]->ref), 0, 'C');

					// \TODO Field not yet saved in database
					//$pdf->SetXY (133, $curY );
					//$pdf->MultiCell(10, 5, $object->lignes[$i]->tva_tx, 0, 'C');

					$pdf->SetXY (145, $curY );
					$pdf->MultiCell(10, 3, $object->lignes[$i]->qty_shipped, 0, 'C');

					// \TODO Field not yet saved in database
					//$pdf->SetXY (156, $curY );
					//$pdf->MultiCell(18, 3, price($object->lignes[$i]->price), 0, 'R', 0);

					// \TODO Field not yet saved in database
					//$pdf->SetXY (174, $curY );
					//$total = price($object->lignes[$i]->price * $object->lignes[$i]->qty_shipped);
					//$pdf->MultiCell(26, 3, $total, 0, 'R', 0);

					$pdf->line(10, $curY-1, 200, $curY-1);


					$nexY+=2;    // Passe espace entre les lignes

					// Cherche nombre de lignes a venir pour savoir si place suffisante
					if ($i < ($nblignes - 1))	// If it's not last line
					{
						//on recupere la description du produit suivant
						$follow_descproduitservice = $object->lignes[$i+1]->desc;
						//on compte le nombre de ligne afin de verifier la place disponible (largeur de ligne 52 caracteres)
						$nblineFollowDesc = (dol_nboflines_bis($follow_descproduitservice,52,$outputlangs->charset_output)*4);
					}
					else	// If it's last line
					{
						$nblineFollowDesc = 0;
					}

					// Test if a new page is required
					if ($pagenb == 1)
					{
						$tab_top_in_current_page=$tab_top;
						$tab_height_in_current_page=$tab_height;
					}
					else
					{
						$tab_top_in_current_page=$tab_top_newpage;
						$tab_height_in_current_page=$tab_height_newpage;
					}

					if (($nexY+$nblineFollowDesc) > ($tab_top_in_current_page+$tab_height_in_current_page) && $i < ($nblignes - 1))
					{
						if ($pagenb == 1)
						{
							$this->_tableau($pdf, $tab_top, $tab_height + 20, $nexY, $outputlangs);
						}
						else
						{
							$this->_tableau($pdf, $tab_top_newpage, $tab_height_newpage, $nexY, $outputlangs);
						}

						$this->_pagefoot($pdf, $object, $outputlangs);

						// New page
						$pdf->AddPage();
						$pagenb++;
						$this->_pagehead($pdf, $object, 0, $outputlangs);
						$pdf->SetFont('Arial','', 9);
						$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
						$pdf->SetTextColor(0,0,0);

						$nexY = $tab_top_newpage + 7;
					}
				}

				// Show square
				if ($pagenb == 1)
				{
					$this->_tableau($pdf, $tab_top, $tab_height, $nexY, $outputlangs);
					$bottomlasttab=$tab_top + $tab_height + 1;
				}
				else
				{
					$this->_tableau($pdf, $tab_top_newpage, $tab_height_newpage, $nexY, $outputlangs);
					$bottomlasttab=$tab_top_newpage + $tab_height_newpage + 1;
				}

				/*
				 * Pied de page
				 */
				$this->_pagefoot($pdf, $object, $outputlangs);
				$pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file);
				if (! empty($conf->global->MAIN_UMASK))
					@chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;
			}
		}
		else
		{
			$this->error=$langs->transnoentities("ErrorConstantNotDefined","LIVRAISON_OUTPUTDIR");
			return 0;
		}
	}

	/**
	 *   \brief      Affiche la grille des lignes
	 *   \param      pdf     objet PDF
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs)
	{
		$pdf->SetFont('Arial','',11);

		$pdf->Text(30,$tab_top + 5,$outputlangs->transnoentities("Designation"));

		//		$pdf->line(132, $tab_top, 132, $tab_top + $tab_height);
		//		$pdf->Text(134,$tab_top + 5,$langs->transnoentities("VAT"));

		$pdf->line(144, $tab_top, 144, $tab_top + $tab_height);
		$pdf->Text(147,$tab_top + 5,$outputlangs->transnoentities("QtyShipped"));

		//		$pdf->line(156, $tab_top, 156, $tab_top + $tab_height);
		//		$pdf->Text(160,$tab_top + 5,$langs->transnoentities("PriceU"));

		//		$pdf->line(174, $tab_top, 174, $tab_top + $tab_height);
		//		$pdf->Text(187,$tab_top + 5,$langs->transnoentities("Total"));

		//      $pdf->Rect(10, $tab_top, 190, $nexY - $tab_top);
		$pdf->Rect(10, $tab_top, 190, $tab_height);


		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',10);
		//		$titre = $langs->transnoentities("AmountInCurrency",$langs->transnoentitiesnoconv("Currency".$conf->monnaie));
		//		$pdf->Text(200 - $pdf->GetStringWidth($titre), 98, $titre);

	}

	/**
	 *   	\brief      Affiche en-tete
	 *   	\param      pdf     		objet PDF
	 *   	\param      delivery    	object delivery
	 *      \param      showadress      0=non, 1=oui
	 */
	function _pagehead(&$pdf, $object, $showadress=1, $outputlangs)
	{
		global $langs,$conf,$mysoc;

		pdf_pagehead($pdf,$outputlangs,$pdf->page_hauteur);

		$pdf->SetTextColor(0,0,60);
		$pdf->SetFont('Arial','B',13);

		$posy=$this->marge_haute;

		$pdf->SetXY($this->marge_gauche,$posy);

		if ($conf->global->MAIN_INFO_SOCIETE_NOM)
		{
			$pdf->SetTextColor(0,0,200);
			$pdf->SetFont('Arial','B',12);
			$pdf->MultiCell(76, 4, $outputlangs->convToOutputCharset(MAIN_INFO_SOCIETE_NOM), 0, 'L');
		}

		// Sender properties
		$carac_emetteur = '';
		$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->convToOutputCharset($this->emetteur->address);
		$carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->convToOutputCharset($this->emetteur->cp).' '.$outputlangs->convToOutputCharset($this->emetteur->ville);
		$carac_emetteur .= "\n";
		// Tel
		if ($this->emetteur->tel) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Phone").": ".$outputlangs->convToOutputCharset($this->emetteur->tel);
		// Fax
		if ($this->emetteur->fax) $carac_emetteur .= ($carac_emetteur ? ($this->emetteur->tel ? " - " : "\n") : '' ).$outputlangs->transnoentities("Fax").": ".$outputlangs->convToOutputCharset($this->emetteur->fax);
		// EMail
		if ($this->emetteur->email) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Email").": ".$outputlangs->convToOutputCharset($this->emetteur->email);
		// Web
		if ($this->emetteur->url) $carac_emetteur .= ($carac_emetteur ? "\n" : '' ).$outputlangs->transnoentities("Web").": ".$outputlangs->convToOutputCharset($this->emetteur->url);

		$pdf->SetFont('Arial','',9);
		$pdf->SetXY($this->marge_gauche,$posy+4);
		$pdf->MultiCell(80, 3, $carac_emetteur);


		/*
		 * Adresse Client
		 */

		$object->fetch_client();

		// If SHIPPING contact defined on invoice, we use it
		$usecontact=false;
		$arrayidcontact=$object->commande->getIdContact('external','SHIPPING');
		if (sizeof($arrayidcontact) > 0)
		{
			$usecontact=true;
			$result=$object->fetch_contact($arrayidcontact[0]);
		}

		if ($usecontact)
		{
			$socname = $object->client->nom;
			$carac_client_name=$outputlangs->convToOutputCharset($socname);

			// Customer name
			$carac_client = $outputlangs->convToOutputCharset($object->contact->getFullName($outputlangs,1,1));

			// Customer properties
			$carac_client.="\n".$outputlangs->convToOutputCharset($object->contact->address);
			$carac_client.="\n".$outputlangs->convToOutputCharset($object->contact->cp) . " " . $outputlangs->convToOutputCharset($object->contact->ville)."\n";
			if ($object->contact->pays_code && $object->contact->pays_code != $this->emetteur->pays_code) $carac_client.=$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$object->contact->pays_code))."\n";
		}
		else
		{
			// Nom client
			$carac_client_name=$outputlangs->convToOutputCharset($object->client->nom);

			// Nom du contact facturation si c'est une societe
			$arrayidcontact = $object->getIdContact('external','SHIPPING');
			if (sizeof($arrayidcontact) > 0)
			{
				$object->fetch_contact($arrayidcontact[0]);
				// On verifie si c'est une societe ou un particulier
				if( !preg_match('#'.$object->contact->getFullName($outputlangs,1).'#isU',$object->client->nom) )
				{
					$carac_client .= "\n".$outputlangs->convToOutputCharset($object->contact->getFullName($outputlangs,1,1));
				}
			}

			// Caracteristiques client
			$carac_client.="\n".$outputlangs->convToOutputCharset($object->client->address);
			$carac_client.="\n".$outputlangs->convToOutputCharset($object->client->cp) . " " . $outputlangs->convToOutputCharset($object->client->ville)."\n";
			if ($object->client->pays_code && $object->client->pays_code != $this->emetteur->pays_code) $carac_client.=$outputlangs->convToOutputCharset($outputlangs->transnoentitiesnoconv("Country".$object->client->pays_code))."\n";
		}
		// Numero TVA intracom
		if ($object->client->tva_intra) $carac_client.="\n".$outputlangs->transnoentities("VATIntraShort").': '.$outputlangs->convToOutputCharset($object->client->tva_intra);


		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','B',12);

		$pdf->SetXY(102,42);
		$pdf->MultiCell(96,5, $carac_client_name);
		$pdf->SetFont('Arial','B',11);
		$pdf->SetXY(102,47);
		$pdf->MultiCell(96,5, $carac_client);
		$pdf->rect(100, 40, 100, 40);


		$pdf->SetTextColor(200,0,0);
		$pdf->SetFont('Arial','B',12);
		$pdf->Text(11, 88, $outputlangs->transnoentities("Date")." : " . dol_print_date($object->date_valid,"day",false,$outputlangs,true));
		$pdf->Text(11, 94, $outputlangs->transnoentities("DeliveryOrder")." ".$outputlangs->convToOutputCharset($object->ref));

		$pdf->SetFont('Arial','B',9);

		// Add list of linked orders
	    $object->load_object_linked();

	    if ($conf->commande->enabled)
		{
			$outputlangs->load('orders');
			foreach($object->linked_object as $key => $val)
			{
				if ($val['type'] == 'commande')
				{
					$newobject=new Commande($this->db);
					$result=$newobject->fetch($val['linkid']);
					if ($result >= 0)
					{
						$posy+=4;
						$pdf->SetXY(102,$posy);
						$pdf->SetFont('Arial','',9);
						$text=$newobject->ref;
						if ($newobject->ref_client) $text.=' ('.$newobject->ref_client.')';
						$pdf->Text(11, 94, $outputlangs->transnoentities("RefOrder")." : ".$outputlangs->transnoentities($text), '', 'R');
					}
				}
			}
		}

	}

	/**
	 *   	\brief      Show footer of page
	 *   	\param      pdf     		PDF factory
	 * 		\param		object			Object invoice
	 *      \param      outputlangs		Object lang for output
	 * 		\remarks	Need this->emetteur object
	 */
	function _pagefoot(&$pdf,$object,$outputlangs)
	{
		return pdf_pagefoot($pdf,$outputlangs,'DELIVERY_FREE_TEXT',$this->emetteur,$this->marge_basse,$this->marge_gauche,$this->page_hauteur,$object);
	}
}

?>
