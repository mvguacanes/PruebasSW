<?php
/* Copyright (C) 2004-2008 Laurent Destailleur   <eldy@users.sourceforge.net>
 * Copyright (C) 2005-2009 Regis Houssin         <regis@dolibarr.fr>
 * Copyright (C) 2007      Franky Van Liedekerke <franky.van.liedekerke@telenet.be>
 * Copyright (C) 2008      Chiptronik

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
 *	\file       htdocs/includes/modules/livraison/pdf/pdf_typhon.modules.php
 *	\ingroup    livraison
 *	\brief      Fichier de la classe permettant de generer les bons de livraison au modele Typhon
 *	\author	    Laurent Destailleur
 *	\version    $Id$
 */

require_once(DOL_DOCUMENT_ROOT."/includes/modules/livraison/modules_livraison.php");
require_once(DOL_DOCUMENT_ROOT."/livraison/livraison.class.php");
require_once(DOL_DOCUMENT_ROOT."/lib/company.lib.php");


/**
 *	\class      pdf_typhon
 *	\brief      Classe permettant de generer les bons de livraison au modele Typho
 */

class pdf_typhon extends ModelePDFDeliveryOrder
{
	var $emetteur;	// Objet societe qui emet

	/**
	 *		\brief  Constructor
	 *		\param	db		Database handler
	 */
	function pdf_typhon($db)
	{
		global $conf,$langs,$mysoc;

		$langs->load("main");
		$langs->load("bills");
		$langs->load("sendings");
		$langs->load("companies");

		$this->db = $db;
		$this->name = "typhon";
		$this->description = $langs->trans("DocumentModelTyphon");

		// Dimension page pour format A4
		$this->type = 'pdf';
		$this->page_largeur = 210;
		$this->page_hauteur = 297;
		$this->format = array($this->page_largeur,$this->page_hauteur);
		$this->marge_gauche=10;
		$this->marge_droite=10;
		$this->marge_haute=10;
		$this->marge_basse=10;

		$this->option_logo = 1;                    // Affiche logo FAC_PDF_LOGO
		$this->option_tva = 1;                     // Gere option tva FACTURE_TVAOPTION
		$this->option_codeproduitservice = 1;      // Affiche code produit-service

		$this->franchise=!$mysoc->tva_assuj;

		// Recupere emmetteur
		$this->emetteur=$mysoc;
		if (! $this->emetteur->pays_code) $this->emetteur->pays_code=substr($langs->defaultlang,-2);    // Par defaut, si n'???tait pas d???fini

		// Defini position des colonnes
		$this->posxdesc=$this->marge_gauche+1;
		$this->posxcomm=120;
		$this->posxtva=121;
		$this->posxup=132;
		$this->posxqty=168;
		$this->posxdiscount=162;
		$this->postotalht=177;

		$this->tva=array();
		$this->atleastoneratenotnull=0;
		$this->atleastonediscount=0;
	}

	/**
	 *	\brief      Renvoi dernere erreur
	 *	\return     string      Derniere erreur
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
		global $user,$langs,$conf;

		if (! is_object($outputlangs)) $outputlangs=$langs;
		// Force output charset to ISO, because, FPDF expect text encoded in ISO
		$sav_charset_output=$outputlangs->charset_output;
		$outputlangs->charset_output='ISO-8859-1';

		$outputlangs->load("main");
		$outputlangs->load("dict");
		$outputlangs->load("companies");
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

				/*
				 // Positionne $this->atleastonediscount si on a au moins une remise
				 for ($i = 0 ; $i < $nblignes ; $i++)
				 {
				 if ($object->lignes[$i]->remise_percent)
				 {
				 $this->atleastonediscount++;
				 }
				 }
				 */

				// New page
				$pdf->AddPage();
				$pagenb++;
				$this->_pagehead($pdf, $object, 1, $outputlangs);
				$pdf->SetFont('Arial','', 9);
				$pdf->MultiCell(0, 3, '', 0, 'J');		// Set interline to 3
				$pdf->SetTextColor(0,0,0);

				$tab_top = 90;
				$tab_top_newpage = 50;
				$tab_height = 110;
				$tab_height_newpage = 150;

				// Affiche notes
				if (! empty($object->note_public))
				{
					$tab_top = 88;

					$pdf->SetFont('Arial','', 9);   // Dans boucle pour gerer multi-page
					$pdf->SetXY ($this->posxdesc-1, $tab_top);
					$pdf->MultiCell(190, 3, $outputlangs->convToOutputCharset($object->note_public), 0, 'J');
					$nexY = $pdf->GetY();
					$height_note=$nexY-$tab_top;

					// Rect prend une longueur en 3eme param
					$pdf->SetDrawColor(192,192,192);
					$pdf->Rect($this->marge_gauche, $tab_top-1, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $height_note+1);

					$tab_height = $tab_height - $height_note;
					$tab_top = $nexY+6;
				}
				else
				{
					$height_note=0;
				}

				$iniY = $tab_top + 7;
				$curY = $tab_top + 7;
				$nexY = $tab_top + 7;

				// Boucle sur les lignes
				for ($i = 0 ; $i < $nblignes ; $i++)
				{
					$curY = $nexY;

					// Description de la ligne produit
					$libelleproduitservice=pdf_getlinedesc($object->lignes[$i],$outputlangs);

					$pdf->SetFont('Arial','', 9);   // Dans boucle pour gerer multi-page

					$pdf->writeHTMLCell(108, 3, $this->posxdesc-1, $curY, $outputlangs->convToOutputCharset($libelleproduitservice), 0, 1);

					$pdf->SetFont('Arial','', 9);   // On repositionne la police par defaut
					$nexY = $pdf->GetY();

					/*
					 // TVA
					 $pdf->SetXY ($this->posxtva, $curY);
					 $pdf->MultiCell(10, 4, ($object->lignes[$i]->tva_tx < 0 ? '*':'').abs($object->lignes[$i]->tva_tx), 0, 'R');

					 // Prix unitaire HT avant remise
					 $pdf->SetXY ($this->posxup, $curY);
					 $pdf->MultiCell(18, 4, price($object->lignes[$i]->subprice), 0, 'R', 0);
					 */
					// Quantity
					$pdf->SetXY ($this->posxqty, $curY);
					$pdf->MultiCell(30, 3, $object->lignes[$i]->qty_shipped, 0, 'R');
					/*
					 // Remise sur ligne
					 $pdf->SetXY ($this->posxdiscount, $curY);
					 if ($object->lignes[$i]->remise_percent)
					 {
					 $pdf->MultiCell(14, 3, $object->lignes[$i]->remise_percent."%", 0, 'R');
					 }

					 // Total HT ligne
					 $pdf->SetXY ($this->postotalht, $curY);
					 $total = price($object->lignes[$i]->price * $object->lignes[$i]->qty);
					 $pdf->MultiCell(23, 3, $total, 0, 'R', 0);

					 // Collecte des totaux par valeur de tva
					 // dans le tableau tva["taux"]=total_tva
					 $tvaligne=$object->lignes[$i]->price * $object->lignes[$i]->qty;
					 if ($object->remise_percent) $tvaligne-=($tvaligne*$object->remise_percent)/100;
					 $this->tva[ (string)$object->lignes[$i]->tva_tx ] += $tvaligne;
					 */
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
				$this->_pagefoot($pdf,$object,$outputlangs);
				
				// Check product remaining to be delivered
				$waitingDelivery = $object->getRemainingDelivered();

				if (is_array($waitingDelivery) & !empty($waitingDelivery))
				{
					$pdf->AddPage('P', 'A4');
					
					$this->_pagehead($pdf, $object, 1, $outputlangs);
					$pdf-> SetY(90);
					
					$w=array(40,100,50);
					$header=array($outputlangs->transnoentities('Reference'),
								  $outputlangs->transnoentities('Label'),
								  $outputlangs->transnoentities('Qty')
								  );
    					
    				// Header
   					for($i=0;$i<count($header);$i++)
   					{
   						$pdf->Cell($w[$i],7,$header[$i],1,0,'C');
   					}

			    	$pdf->Ln();	

			    	// Data
					foreach($waitingDelivery as $value)
					{
						$pdf->Cell($w[0], 6, $value['ref'], 1, 0, 'L');
						$pdf->Cell($w[1], 6, $value['label'], 1, 0, 'L');
						$pdf->Cell($w[2], 6, $value['qty'], 1, 1, 'R');	
						
						if ($pdf->GetY() > 250)
						{
							$this->_pagefoot($pdf,$object,$outputlangs);
							
							$pdf->AddPage('P', 'A4');
							
							$pdf->SetFont('Arial','', 9);
							$this->_pagehead($pdf, $object, 0, $outputlangs);
							
							$pdf-> SetY(40);
							
							for($i=0;$i<count($header);$i++)
							{
								$pdf->Cell($w[$i],7,$header[$i],1,0,'C');
							}
							
							$pdf->Ln();	
						}				
					}
					
					$this->_pagefoot($pdf,$object,$outputlangs);
											
				}
				
				$pdf->AliasNbPages();

				$pdf->Close();

				$pdf->Output($file);
				if (! empty($conf->global->MAIN_UMASK))
				@chmod($file, octdec($conf->global->MAIN_UMASK));

				return 1;   // Pas d'erreur
			}
			else
			{
				$this->error=$langs->transnoentities("ErrorCanNotCreateDir",$dir);
				return 0;
			}
		}

		$this->error=$langs->transnoentities("ErrorConstantNotDefined","LIVRAISON_OUTPUTDIR");
		return 0;
	}


	/*
	 *   \brief      Affiche la grille des lignes
	 *   \param      pdf     objet PDF
	 */
	function _tableau(&$pdf, $tab_top, $tab_height, $nexY, $outputlangs)
	{
		global $conf,$mysoc;

		// Montants exprimes en     (en tab_top - 1)
		$pdf->SetTextColor(0,0,0);
		$pdf->SetFont('Arial','',8);
		//$titre = $outputlangs->transnoentities("AmountInCurrency",$outputlangs->transnoentitiesnoconv("Currency".$conf->monnaie));
		//$pdf->Text($this->page_largeur - $this->marge_droite - $pdf->GetStringWidth($titre), $tab_top-1, $titre);

		$pdf->SetDrawColor(128,128,128);

		// Rect prend une longueur en 3eme param
		$pdf->Rect($this->marge_gauche, $tab_top, $this->page_largeur-$this->marge_gauche-$this->marge_droite, $tab_height);
		// line prend une position y en 3eme param
		$pdf->line($this->marge_gauche, $tab_top+6, $this->page_largeur-$this->marge_droite, $tab_top+6);

		$pdf->SetFont('Arial','',10);

		$pdf->SetXY ($this->posxdesc-1, $tab_top+2);
		$pdf->MultiCell(80,2, $outputlangs->transnoentities("Designation"),'','L');

		// Modif SEB pour avoir une col en plus pour les commentaires clients
		$pdf->line($this->posxcomm, $tab_top, $this->posxcomm, $tab_top + $tab_height);
		$pdf->SetXY ($this->posxcomm, $tab_top+2);
		$pdf->MultiCell(80,2, $outputlangs->transnoentities("Comments"),'','L');

		// Qty
		$pdf->line($this->posxqty-1, $tab_top, $this->posxqty-1, $tab_top + $tab_height);
		$pdf->SetXY ($this->posxqty-1, $tab_top+2);
		$pdf->MultiCell(30, 2, $outputlangs->transnoentities("QtyShipped"),'','R');

		// Modif Seb cadres signatures
		$pdf->SetFont('Arial','',10);
		$larg_sign = ($this->page_largeur-$this->marge_gauche-$this->marge_droite)/3;
		$pdf->Rect($this->marge_gauche, ($tab_top + $tab_height + 3), $larg_sign, 25 );
		$pdf->SetXY ($this->marge_gauche + 2, $tab_top + $tab_height + 5);
		$pdf->MultiCell($larg_sign,2, $outputlangs->trans("For").' '.$outputlangs->convToOutputCharset($mysoc->nom).":",'','L');

		$pdf->Rect(2*$larg_sign+$this->marge_gauche, ($tab_top + $tab_height + 3), $larg_sign, 25 );
		$pdf->SetXY (2*$larg_sign+$this->marge_gauche + 2, $tab_top + $tab_height + 5);
		$pdf->MultiCell($larg_sign,2, $outputlangs->trans("ForCustomer").':','','L');

	}

	/**
	 *   	\brief      Affiche en-tete bon livraison
	 *   	\param      pdf     	objet PDF
	 *   	\param      delivery    object delivery
	 *      \param      showadress  0=non, 1=oui
	 */
	function _pagehead(&$pdf, $object, $showadress=1, $outputlangs)
	{
		global $langs,$conf,$mysoc;

		pdf_pagehead($pdf,$outputlangs,$pdf->page_hauteur);

		$pdf->SetTextColor(0,0,60);
		$pdf->SetFont('Arial','B',13);

		$posy=$this->marge_haute;

		$pdf->SetXY($this->marge_gauche,$posy);

		// Logo
		$logo=$conf->mycompany->dir_output.'/logos/'.$mysoc->logo;
		if ($mysoc->logo)
		{
			if (is_readable($logo))
			{
				$pdf->Image($logo, $this->marge_gauche, $posy, 0, 24);
			}
			else
			{
				$pdf->SetTextColor(200,0,0);
				$pdf->SetFont('Arial','B',8);
				$pdf->MultiCell(100, 3, $langs->transnoentities("ErrorLogoFileNotFound",$logo), 0, 'L');
				$pdf->MultiCell(100, 3, $langs->transnoentities("ErrorGoToModuleSetup"), 0, 'L');
			}
		}
		else $pdf->MultiCell(100, 4, $this->emetteur->nom, 0, 'L');

		$pdf->SetFont('Arial','B',13);
		$pdf->SetXY(100,$posy);
		$pdf->SetTextColor(0,0,60);
		$pdf->MultiCell(100, 4, $outputlangs->transnoentities("DeliveryOrder")." ".$outputlangs->convToOutputCharset($object->ref), '' , 'R');
		$pdf->SetFont('Arial','',12);

		$posy+=6;
		$pdf->SetXY(100,$posy);
		$pdf->SetTextColor(0,0,60);
		if ($object->date_valid)
		{
			$pdf->MultiCell(100, 4, $outputlangs->transnoentities("Date")." : " . dol_print_date($object->date_valid,"%d %b %Y",false,$outputlangs,true), '', 'R');
		}
		else
		{
			$pdf->SetTextColor(255,0,0);
			$pdf->MultiCell(100, 4, $outputlangs->transnoentities("DeliveryNotValidated"), '', 'R');
			$pdf->SetTextColor(0,0,60);
		}

		$pdf->SetTextColor(0,0,60);

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
						$pdf->SetXY(100,$posy);
						$pdf->SetFont('Arial','',9);
						$text=$newobject->ref;
						if ($newobject->ref_client) $text.=' ('.$newobject->ref_client.')';
						$pdf->MultiCell(100, 4, $outputlangs->transnoentities("RefOrder")." : ".$outputlangs->transnoentities($text), '', 'R');
					}
				}
			}
		}

		if ($showadress)
		{
			// Emetteur
			$posy=42;
			$hautcadre=40;
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',8);
			$pdf->SetXY($this->marge_gauche,$posy-5);
			$pdf->MultiCell(66,5, $outputlangs->transnoentities("BillFrom").":");


			$pdf->SetXY($this->marge_gauche,$posy);
			$pdf->SetFillColor(230,230,230);
			$pdf->MultiCell(82, $hautcadre, "", 0, 'R', 1);


			$pdf->SetXY($this->marge_gauche+2,$posy+3);

			// Nom emetteur
			$pdf->SetTextColor(0,0,60);
			$pdf->SetFont('Arial','B',11);
			$pdf->MultiCell(80, 4, $outputlangs->convToOutputCharset($this->emetteur->nom), 0, 'L');

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
			$pdf->SetXY($this->marge_gauche+2,$posy+9);
			$pdf->MultiCell(80, 3, $carac_emetteur);

			// Client destinataire
			$posy=42;
			$pdf->SetTextColor(0,0,0);
			$pdf->SetFont('Arial','',8);
			$pdf->SetXY(102,$posy-5);
			$pdf->MultiCell(80,5, $outputlangs->transnoentities("DeliveryAddress").":");

			// Cadre client destinataire
			$pdf->rect(100, $posy, 100, $hautcadre);

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


			// Show customer/recipient
			$pdf->SetXY(102,$posy+3);
			$pdf->SetFont('Arial','B',11);
			$pdf->MultiCell(106,4, $carac_client_name, 0, 'L');

			$pdf->SetFont('Arial','',9);
			$pdf->SetXY(102,$posy+8);
			$pdf->MultiCell(86,4, $carac_client);
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
