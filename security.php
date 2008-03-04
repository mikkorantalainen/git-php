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

function security_load_repos() 
{
	global $repo_directory, $validargs, $repos;

	if( isset($repo_directory) && file_exists($repo_directory) && is_dir($repo_directory))
	{
        if ($handle = opendir($repo_directory)) 
		{
            while (false !== ($file = readdir($handle))) 
			{
				$fullpath = $repo_directory . "/" . $file;
				//printf( "%s,%d\n", $file, is_dir($repo_directory . "/" . $file) );
                if ($file[0] != '.' && is_dir($fullpath) ) 
				{
                    /* TODO: Check for valid git repos */
					// fill the security array.
					$validargs[] = trim($file);
                    $repos[] = trim($fullpath);
                }
            }
            closedir($handle);
        } 
    }
    sort($repos);

	// check for cookie attack
	if( isset($_COOKIE['validargs']) )
	{
		hacker_gaught("You have attack cookie named validargs[] set on your browser!\n");
	}
	// testing attack
	//setcookie( 'validargs[]', 'value1 value2' );

}

function security_test_repository_arg()
{
	// security test the arguments
	if( isset($_GET['p']) )
	{
		// check for valid repository name
		if( !is_valid($_GET['p']) )
			hacker_gaught();
	}
}

function security_load_names()
{
	global $validargs, $repo_direcotry, $branches, $tags;

	if( isset($_GET['p'] ) )
	{
		// now load the repository into validargs
		$repo=$_GET['p'];
		$out=array();
		$branches=git_parse($repo, "branches" );
		foreach( array_keys($branches) as $tg )
		{
			$validargs[] = $tg;
		}
		$tags=git_parse($repo, "tags");
		foreach( array_keys($tags) as $tg )
		{
			$validargs[] = $tg;
		}
		// add files
		unset($out);
		$head="HEAD";
		if( isset( $_GET['tr'] ) && is_valid( $_GET['tr'] ) ) $head = $_GET['tr'];
		$cmd="GIT_DIR=".get_repo_path(basename($repo))." git-ls-tree -r -t ".escapeshellarg($head)." | sed -e 's/\t/ /g'";
        exec($cmd, &$out);
        foreach ($out as $line) 
		{
            $arr = explode(" ", $line);
            //$validargs[] = $arr[2]; // add the hash to valid array
            $validargs[] = basename($arr[3]); // add the file name to valid array
        }	
	}
}

function security_test_arg()
{
	// now, all arguments must be in validargs
	foreach( $_GET as $value )
	{
		if( !is_valid($value) )
			hacker_gaught();
	}
	foreach( $_POST as $value )
	{
		if( !is_valid($value) )
			hacker_gaught();
	}
}

// this function checks if a token is valid argument
function is_valid($token)
{
	global $validargs, $failedarg;

	if( $token == "" ) // empty token is valid too
		return true;
	if( is_numeric( $token ) ) // numeric arguments do not harm
	    return true;
	if( is_sha1( $token ) ) // we usually apply sha1 as arguments
		return true;
	foreach($validargs as $va)
	{
		if( $va == $token )
			return true;
	}
	$failedarg = $token;
	return false;
}

function hacker_gaught($mess="")
{
	global $failedarg, $validargs;

	header("Content-Type: text/plain");
	echo "please, do not attack.\n";
	echo "this site is not your enemy.\n\n";
	echo "the failed argument is $failedarg.\n";
	echo "$mess \n";
	foreach( $validargs as $va )
		echo "$va\n";
	die();
}

// checks if the argument is sha1
function is_sha1($val)
{
	//if( !is_string($val) ) return false;
	if( strlen($val) != 40 ) return false;
	for( $i=0; $i<40; $i++ ){
		if( strrpos( "00123456789abcdef", "{$val[$i]}" ) == FALSE ) return false;
	}
	return true;
}

function get_repo_path($proj)   {
    global $repos;

    foreach ($repos as $repo)   {
        $path = basename($repo);
        if ($path == $proj)
            return $repo;
    }
	return "";
}

function draw_human_checker( $amessage )
{
    $w = 100; $wo = 50;
    $h = 30; $ho = 10;
	$prob = 6;

	$fh = imagefontheight(1);
	$fw = imagefontwidth(1);

    $im = imagecreate( $w, $h );
    $cvar[0] = imagecolorallocate( $im, 255, 255, 255 );
	$cvar[1] = imagecolorallocate($im, 200,   0,   0 );
	$cvar[2] = imagecolorallocate($im,   0, 200,   0 );
	$cvar[3] = imagecolorallocate($im,   0,   0, 200 );
	$cvar[4] = imagecolorallocate($im,   0,   0,   0 );

	$col1 = rand(0,4);
	do{ $col2 = rand(0,4); } while( $col1 == $col2 );

	//echo "$fh,$fw";
	//die();

	imagefill( $im, 0, 0, $cvar[$col1] );
	
	imagestring( $im, 5, ($h-$fh)/2, 5, $amessage, $cvar[$col2] );
	//if( !imagestring( $im, 3, 0, 0, "Message", $cvar[$col2] ) ) { echo "false"; die(); }

	for( $x=0; $x<$w; $x++ ){
		for( $y=0; $y<$h; $y++ ){
			if( rand(0,100) > $prob ) continue;
			$idx = imagecolorat( $im, $x, $y );
			if( $idx == $col1 ){
				imagesetpixel($im,$x,$y,$cvar[$col2]);
			}
			else{
				imagesetpixel($im,$x,$y,$cvar[$col1]);
			}
		}
	}

    //imagepng( $im, "/home/peeter/public_html/test/proov.png" );
    imagepng( $im );
	die();
}

?>
