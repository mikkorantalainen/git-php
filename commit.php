<?php

/* vim: set expandtab tabstop=4 shiftwidth=4 foldmethod=marker: */
// +------------------------------------------------------------------------+
// | git-php - PHP front end to git repositories                            |
// +------------------------------------------------------------------------+
// | Copyright (c) 2006 Zack Bartel                                         |
// +------------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or          |
// | modify it under the terms of the GNU General Public License            |
// | as published by the Free Software Foundation; either version 2         |
// | of the License, or (at your option) any later version.                 |
// |                                                                        |
// | This program is distributed in the hope that it will be useful,        |
// | but WITHOUT ANY WARRANTY; without even the implied warranty of         |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the          |
// | GNU General Public License for more details.                           |
// |                                                                        |
// | You should have received a copy of the GNU General Public License      |
// | along with this program; if not, write to the Free Software            |
// | Foundation, Inc., 59 Temple Place - Suite 330,                         |
// | Boston, MA  02111-1307, USA.                                           |
// +------------------------------------------------------------------------+
// |                                                                        |
// | Author: Peeter Vois http://people.proekspert.ee/peeter/blog            |
// +------------------------------------------------------------------------+ 

    global $title;
    global $repos; // list of repositories
	global $validargs; // list of allowed arguments
    global $git_embed;
    global $git_css;
    global $git_logo;
    global $http_method_prefix; // prefix path for http clone method
    global $communication_link; // link for sending a message to owner
	global $failedarg;
	global $cache_name;
	global $tags;
	global $branches;
	global $nr_of_shortlog_lines;
	
	global $keepurl; //the arguments that must be resent

    //repos could be made by an embeder script
    if (!is_array($repos))
        $repos = array();

	if(!is_array($validargs))
		$validargs = array();
		
	require_once( "config.php" );
	require_once( "security.php" );
	require_once( "html_helpers.php" );
	require_once( "filestuff.php" );

	if( !$git_commiting_active ) die();

	security_load_repos();
	security_test_repository_arg();
	//security_load_names();

	// some simple methods do not need checking
	
	clean_up_secrets();

    if (isset($_GET['dl']))
        if ( in_array( $_GET['dl'], $icondesc, true ) )
            write_img_png($_GET['dl']);
        else if ( in_array( $_GET['dl'], $flagdesc, true ) )
            write_img_png($_GET['dl']);
		else if ( $_GET['dl'] =="human_check" )
			draw_human_checker(create_secret());

    if (isset($_POST['action']))
    if (check_secret($_POST['check']))
    {
        echo "Secret OK"; 
		die();
    }
	else
	{
		echo "Secret not OK";
		die();
	}

	send_the_main_page();
	die();


function send_the_submit_form()
{
    html_spacer();
	html_title("COMMIT A BUNDLE");
	html_spacer();

	echo html_ref( array( 'p'=>$_GET['p'], 'a'=>"jump_to_tag" ),"<form method=post action=\"");
	echo "<div class=\"optiontable\">";
	echo "<table>\n";
	echo "<tr><td class=\"descol\">Your name / alias e.t.c </td><td class=\"valcol\"><input type=\"text\" name=\"commiter name\" size=\"40\"></td></tr>\n";
	echo "<tr><td class=\"descol\">Bundle file </td><td class=\"valcol\"><input type=\"file\" name=\"bundle_file\" size=\"40\"></td></tr>\n";
	echo "<tr><td class=\"descol\">enter the value <img src=\"".sanitized_url()."dl=human_check\"/> here </td><td class=\"valcol\"><input type=\"text\" name=\"check\" size=\"40\"></td></tr>\n";
	echo "<tr><td class=\"descol\">Submit </td><td class=\"valcol\"><input type=\"submit\" name=\"action\"  value=\"commit\" size=\"10\"></td></tr>\n";
	echo "</table></div>\n";

	echo "</form>\n";

	send_the_help_section_of_submit();
}

function send_the_help_section_of_submit()
{
	html_spacer();
	html_title("HELP");
	html_spacer();
	echo "<table><tr><td>";
	echo "To create a bundle, you can use the command similar to the following:<br>";
	echo "<b>git bundle create mybundle.bdl master ^v1.0.0</b><br>";
	echo "where v1.0.0 is the tag name that exists in yours and this repository<br>";
	echo "</td></tr></table>";
}

function send_the_bundles_in_queue()
{
	html_spacer();
	html_title("BUNDLES IN QUEUE");
	html_spacer();
	echo "...";
}

// the main page
function send_the_main_page()
{
	if( !isset($_GET['p'] ) ) die();

	html_header();
    html_style();
    html_breadcrumbs();
	send_the_submit_form();
	send_the_bundles_in_queue();
	html_spacer();
	html_footer();
	die();
}

?>
