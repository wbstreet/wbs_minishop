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
  DEUTSCHE SPRACHDATEI FÜR DAS HELLO WORLD MODUL
 -----------------------------------------------------------------------------------------
**/

// sprachabhängige Modulbeschreibungen wurden mit WB 2.7 eingeführt (default English in info.php)
$module_description = 'Dieses Modul dient als Grundlagen f&uuml;r die Entwicklung eigener Module. Unterst&uuml;tzt Mehrsprachigkeit. Ab WB 2.7 k&ouml;nnen die optionalen CSS Moduldateien frontend.css und backend.css vom WB-Backend aus bearbeitet werden.';

// Array für alle sprachabhängigen Textausgaben im Front- und Backend
// Hinweis: Verwende nachfolgende Namenskonvention für die Sprachausgabe des Moduls: $MOD_MODULE_DIRECTORY
$MOD_HELLOWORLD = array(
	// Variablen für Textausgaben im Frontend (view.php)
	'TXT_HEADING_F'		=> 'Das Modul Hello World in Aktion',		// Überschrift Frontend
	'TXT_DESC_F'			=> 'Text aus der Moduldatenbank',				// Beschreibung des Ausgabetextes
	'TXT_ERROR_F'			=> 'Uups, keine Daten vorhanden.',			// Text wenn keine Daten in Datenbank vorhanden
	'TXT_LINK_F'			=> 'Klick mich...',											// Beschriftung für Javascript ahref Beschreibung
	'TXT_MODIFIED_F'	=> 'ge&auml;ndert am:',									// Text für Änderungsdatum
	'DATE_FORMAT_F'		=> 'd.m.y, H:i:s',											// Deutsches Datums- und Zeitformat (23.04.07, 23:02:32)
	// Variablen für Textausgaben im Backend (modify.php)
	'TXT_HEADING_B'		=> 'Hello World Einstellungen',					// Überschrift Backend
	'TXT_INPUT_DESC_B'=> 'Anzeigetext im Frontend',						// Beschreibung der Eingabedaten
	'TXT_LINK_B'			=> 'wer bin ich...'											// Javascript ahref link text
);

?>