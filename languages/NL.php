<?php

######################################################################################################################
#
#	PURPOSE OF THIS FILE:
#	Allows to add multi-lingual support to your module, both on WB Frontend and Backend.
#	Frontend: language of the module page is set via Backend: Pages -> your module type -> Settings -> Language
#	Backend:  language in the backend is set via Backend: Preferences -> Language
#	It is good working practice to provide a modification history of the language file as shown below.
#
#	INVOKED BY:
# 	The language files must be invoked from view.php and modify.php of your module and from all other files which
#	will provide text outputs to the user in the front- or backend of WB.
#
#	EXAMPLE HELLO WORLD:
#	The hello world module has two interfaces with the user. The file modify.php provides some settings in the Backend,
#	the file view.php shows the text stored in the database. For that reason, the language support routines must be 
#	defined in this two files only.
#
#	PLEASE NOTE: 
#	Language arrays which store the language depending text outputs have to stick to the following naming convention:
#	$MOD_MODULE_DIRECTORY (e.g. $MOD_HELLOWORLD) 
#	This ensures no interference with other modules will occur!
#
######################################################################################################################

/**
  Module developed for the Open Source Content Management System Website Baker (http://websitebaker.org)
  Copyright (C) year, Authors name
  Contact me: author(at)domain.xxx, http://authorwebsite.xxx

  This module is free software. You can redistribute it and/or modify it 
  under the terms of the GNU General Public License  - version 2 or later, 
  as published by the Free Software Foundation: http://www.gnu.org/licenses/gpl.html.

  This module is distributed in the hope that it will be useful, 
  but WITHOUT ANY WARRANTY; without even the implied warranty of 
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the 
  GNU General Public License for more details.

 -----------------------------------------------------------------------------------------
  DUTCH LANGUAGE FILE FOR THE HELLO WORLD MODULE
 -----------------------------------------------------------------------------------------
**/

// use this description below to provide a module description in Dutch (introduced with WB 2.7)
$module_description = 'This module provides the basics for developing own modules. Includes all features like multi-languages support, optional module files ...';	// short description of the modules purpose -- to be translated

// array voor alle taal-specifieke tekstweergaven in de front- en backend 
// Opmerking: Behoud de naam-conventie: $MOD_MODULE_DIRECTORY
$MOD_HELLOWORLD = array(
	// variables for the frontend file: view.php
	'TXT_HEADING_F'		=> 'De Hello World module in actie',	// koptekst frontend
	'TXT_DESC_F'			=> 'Tekst uit de module database',		// omschrijving van de output-tekst
	'TXT_ERROR_F'			=> 'Oeps, geen pagina-inhoud gevonden.',			// tekst als er geen data in de database gevonden wordt
	'TXT_LINK_F'			=> 'Klik op mij...',									// Javascript ahref link tekst
	'TXT_MODIFIED_F'	=> 'laatst gewijzigd:',								// tekst voor laatst gewijzigd info
	'DATE_FORMAT_F'		=> 'd.m.y, H:i:s',										// Nederlandse datum- en tijdsaanduiding (12.12.07, 23:02:32)
	// variables for the backend file: modify.php
	'TXT_HEADING_B'		=> 'Hello world instellingen',				// koptekst backend
	'TXT_INPUT_DESC_B'=> 'tekst die getoond wordt',					// omschrijving van de verwachte input-data
	'TXT_LINK_B'			=> 'wie ben ik...'										// Javascript ahref link tekst
);

?>