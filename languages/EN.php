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
  ENGLISH LANGUAGE FILE FOR THE HELLO WORLD MODULE
 -----------------------------------------------------------------------------------------
**/

// array for all language dependen text outputs in the front- and backend
// Note: stick to the naming convention: $MOD_MODULE_DIRECTORY
$MOD_HELLOWORLD = array(
	// variables for the frontend file: view.php
	'TXT_HEADING_F'			=> 'The Hello World module in action',		// heading text frontend
	'TXT_DESC_F'				=> 'Text from module database',						// description of output text
	'TXT_ERROR_F'				=> 'Uups, no page content found.',				// some description text
	'TXT_LINK_F'				=> 'Click me...',													// Javascript ahref link text
	'TXT_MODIFIED_F'		=> 'last modified:',											// text for last modified info
	'DATE_FORMAT_F'			=> 'm/d/y, h:i:s a',											// English date and time format (04/23/07, 11:02:32 pm)
	// variables for the backend file: modify.php
	'TXT_HEADING_B'			=> 'Hello world settings',								// heading text backend
	'TXT_INPUT_DESC_B'	=> 'text to display',											// description of expected input data 
	'TXT_LINK_B'				=> 'who am i...'													// Javascript ahref link text
);

?>