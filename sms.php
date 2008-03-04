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
// | Author: Zack Bartel <zack@bartel.com>                                  |
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
	
	global $keepurl; //the arguments that must be resent

	require_once( "config.php" );
	require_once( "security.php" );
	require_once( "html_helpers.php" );
	require_once( "statis.php" );

	// end of server configuration
	//-------------------------------------------------

	$keepurl = array();
	
    //repos could be made by an embeder script
    if (!is_array($repos))
        $repos = array();

	if(!is_array($validargs))
		$validargs = array();

	security_load_repos();
	security_test_repository_arg();

	// add some keywords to valid array
	$validargs = array_merge( $validargs, array( 
		"targz", "zip", "plain", "dlfile", "rss2",
		"commitdiff", "jump_to_tag", "GO", "HEAD",
		"htvote"
		), $icondesc, $flagdesc );

	// some simple methods do not need checking

    if (isset($_GET['dl']))
        if ( in_array( $_GET['dl'], $icondesc, true ) )
            write_img_png($_GET['dl']);
		else if ($_GET['dl'] == 'htvote')
			how_to_vote_this_project();
        else if ( in_array( $_GET['dl'], $flagdesc, true ) )
            write_img_png($_GET['dl']);
		else if( $_GET['dl'] == 'vote' ) vote_a_project();

	vote_a_project();
	die();

	// *************

    function get_project_link($repo, $type = false, $tag="HEAD")    {
        $path = basename($repo);
        if (!$type)
            return "<a href=\"".sanitized_url()."p=$path\">$path</a>";
        else if ($type == "targz")
            return html_ahref( array( 'p'=>$path, 'dl'=>'targz', 'h'=>$tag ) ).".tar.gz</a>";
        else if ($type == "zip")
            return html_ahref( array( 'p'=>$path, 'dl'=>'zip', 'h'=>$tag ) ).".zip</a>";
		else if ($type == "htvote" )
			return '<a href="sms.php?p='.$path.'&dl=htvote>';
    }


function vote_a_project()
{
	// this function increases votes counter if a message comes from fortumo(tm)
	global $repos;

	if(!in_array($_SERVER["REMOTE_ADDR"], array("81.20.151.38", "81.20.148.122"))) 
	{
    	die("System error: unknown IP: ".$_SERVER["REMOTE_ADDR"]);
  	}

	if( isset($_GET["message"]) )
	{
		$message = $_GET["message"]; // the message within SMS
		// figure out the project. The project is written as order number to display it
		if( $message >= 0 && $message < count( $repos ) )
		{
			$proj = $repos[$message];
			$projbase = basename( $proj );
		}

		$td = 0; $tt = 0;
		if( isset($proj) ) 
		{
			file_stat_get_count( $proj, $td, $tt, true, 'votes' );
			echo "You voted for $projbase";
		}
		else
		{
			echo "Sorry, no project with nr $message";
		}
	}
	else
	{
		echo "Sorry, please specify a project number";
	}

	die();
}

function how_to_vote_this_project()
{
	// this function explains the prizelist and how to send SMS
	global $repos;

	$nr = 0;
	if( isset($_GET['p']) ) $nr = array_search(get_repo_path($_GET['p']),$repos);

    html_header();
	if( isset($_GET['p']) )
	{
		echo "<center><H1> Voting for <u>".$_GET['p']."</u></H1>";
	}
	else
	{
		echo "<center><H1>Pricelist</H1>";
	}
	echo "If you think that this project is great, needs more attention or you got rich with it ;) you are welcome to vote for this project. ";
	echo "The vote costs some money that will hopefully prevent bots from raising hands. Notice that you vote here for a project only from your goodwill \n";
	echo "and by voting you do not buy the software and do not get any additional rights. \n";
	echo "To vote for this project, send a SMS with your mobile phone to the phone number in your country. The phone numbers are listed below.<p>\n";
	echo "<center>The message is written inbetween []<p>\n";
	echo "<H2>Phone numbers and prizes</H2>";
	echo "<table cellspacing=10 rules=rows>";
	echo "<tr><th>Country</th><th>Phone #</th><th>Prize</th><th>Message</th></tr>\n";
	echo "<tr><td><img src=\"".sanitized_url()."dl=finland.png\" style=\"border-width: 0px;\"/> Finland </td><td>17211</td><td>2.50 EUR</td><td>[TXT25 VALI $nr]</td></tr>\n";
	echo "<tr><td><img src=\"".sanitized_url()."dl=sweden.png\" style=\"border-width: 0px;\"/> Sweden </td><td>72401</td><td>30.00 SEK</td><td>[TXT VALI $nr]</td></tr>\n";
	echo "<tr><td><img src=\"".sanitized_url()."dl=norway.png\" style=\"border-width: 0px;\"/> Norway </td><td>2223</td><td>30.00 NOK</td><td>[TXT VALI $nr]</td></tr>\n";
	//echo "<tr><td><img src=\"".sanitized_url()."dl=denmark.png\" style=\"border-width: 0px;\"/> Denmark </td><td>1230</td><td>30.00 DKK</td><td>[TXT VALI $nr]</td></tr>\n";
	echo "<tr><td><img src=\"".sanitized_url()."dl=estonia.png\" style=\"border-width: 0px;\"/> Estonia </td><td>13015</td><td>35.00 EEK</td><td>[TXT VALI $nr]</td></tr>\n";
	echo "<tr><td><img src=\"".sanitized_url()."dl=latvia.png\" style=\"border-width: 0px;\"/> Latvia </td><td>29024242 (Bite)<br> 29300242 (LMT)<br> 26000242 (Tele2)</td><td>0.95 LVL</td><td>[TXT VALI $nr]</td></tr>\n";
	echo "<tr><td><img src=\"".sanitized_url()."dl=lithuania.png\" style=\"border-width: 0px;\"/> Lithuania </td><td>1337</td><td>5.00 LTL<td>[TXT VALI $nr]</td></tr>\n";
	echo "</table><p>\n";
	echo "Support: Peeter (dot) Vois (at) mail (dot) ee<p>\n";
	echo "Powered by <a href=\"http://fortumo.com/\">fortumo.com</a><p>\n";

	echo "</center></body></html>";
	die();
}

?>
