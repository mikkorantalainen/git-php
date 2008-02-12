<?php

	/*
		This file configures the git.php script
	*/

    /* Add the default css */
    $git_css = true;

    /* Add the git logo in the footer */
    $git_logo = true;

	/* Configure if the voting mechanism with SMS is active */
	$git_sms_active = true;

    $title  = "git";
    $repo_index = "index.aux";
    $repo_directory = "/home/peeter/public_html/git/";
    $cache_name=".cache/";
    $cache_directory = $repo_directory.$cache_name;
    $http_method_prefix = "http://people.proekspert.ee/peeter/git/";
    $communication_link = "http://people.proekspert.ee/peeter/blog";

    //if git is not installed into standard path, we need to set the path
    putenv( "PATH=/home/peeter/local/bin:/opt/j2sdk1.4/bin:/usr/local/bin:/usr/bin:/bin:/usr/bin/X11:/usr/games" );

?>
