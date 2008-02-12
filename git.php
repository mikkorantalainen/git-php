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

	$keepurl = array();
	
    //repos could be made by an embeder script
    if (!is_array($repos))
        $repos = array();

	if(!is_array($validargs))
		$validargs = array();

	if((file_exists($repo_directory)) && (is_dir($repo_directory)))
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
    else    
    {
		header("Content-Type: text/plain");
		echo "Error: no repositories found!";
		die();
	}

    sort($repos);

	// check for cookie attack
	if( isset($_COOKIE['validargs']) )
	{
		hacker_gaught("You have attack cookie named validargs[] set on your browser!\n");
	}

	// testing attack
	//setcookie( 'validargs[]', 'value1 value2' );

	// security test the arguments
	if( isset($_GET['p']) )
	{
		// check for valid repository name
		if( !is_valid($_GET['p']) )
			hacker_gaught();
		// increase statistic counters
		if( $_GET['dl'] != 'rss2' ) // do not count the rss2 requests
		if( (floor(time()/15/60)-intval($_GET['tm'])) > 4 ) // do not count the one hour session
		if( ((count($_GET) > 1) && isset($_GET['tm'])) || count($_GET) == 1 ) // prevent counting if no time set and more than one argument given
			stat_inc_count( get_repo_path($_GET['p']) );
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
        exec("GIT_DIR=".escapeshellarg($repo_directory.$repo)." git-ls-tree -r -t ".escapeshellarg($head)." | sed -e 's/\t/ /g'", &$out);
        foreach ($out as $line) 
		{
            $arr = explode(" ", $line);
            //$validargs[] = $arr[2]; // add the hash to valid array
            $validargs[] = basename($arr[3]); // add the file name to valid array
        }	

	}


	// add some keywords to valid array
	$icondesc = array( 'git_logo', 'icon_folder', 'icon_plain', 'icon_color' );
	$validargs = array_merge( $validargs, array( 
		"targz", "zip", "plain", "dlfile", "rss2",
		"commitdiff", "jump_to_tag", "GO", "HEAD",
		), $icondesc );

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
	
	// fill keepurl fields
	$keepargs = array( 'tr', 'pg', 'tag' );
	foreach( $keepargs as $idx ){
		if( isset($_GET[$idx]) ) 
			$keepurl[$idx]=$_GET[$idx];
	}

	unset( $validargs );
	// end of validity check


$extEnscript = array
(
//  '.svg'     => 'sml',
  '.ada'     => 'ada',
  '.adb'     => 'ada',
  '.ads'     => 'ada',
  '.awk'     => 'awk',
  '.c'       => 'c',
  '.c++'     => 'cpp',
  '.cc'      => 'cpp',
  '.cpp'     => 'cpp',
  '.csh'     => 'csh',
  '.cxx'     => 'cpp',
  '.diff'    => 'diffu',
  '.dpr'     => 'delphi',
  '.e'       => 'eiffel',
  '.el'      => 'elisp',
  '.eps'     => 'postscript',
  '.f'       => 'fortran',
  '.for'     => 'fortran',
  '.gs'      => 'haskell',
  '.h'       => 'c',
  '.hpp'     => 'cpp',
  '.hs'      => 'haskell',
  '.htm'     => 'html',
  '.html'    => 'html',
  '.idl'     => 'idl',
  '.java'    => 'java',
  '.js'      => 'javascript',
  '.lgs'     => 'haskell',
  '.lhs'     => 'haskell',
  '.m'       => 'objc',
  '.m4'      => 'm4',
  '.man'     => 'nroff',
  '.nr'      => 'nroff',
  '.p'       => 'pascal',
  '.pas'     => 'delphi',
  '.patch'   => 'diffu',
  '.pkg'     => 'sql', 
  '.pl'      => 'perl',
  '.pm'      => 'perl',
  '.pp'      => 'pascal',
  '.ps'      => 'postscript',
  '.s'       => 'asm',
  '.scheme'  => 'scheme',
  '.scm'     => 'scheme',
  '.scr'     => 'synopsys',
  '.sh'      => 'sh',
  '.shtml'   => 'html',
  '.sql'     => 'sql',
  '.st'      => 'states',
  '.syn'     => 'synopsys',
  '.synth'   => 'synopsys',
  '.tcl'     => 'tcl',
  '.tex'     => 'tex',
  '.texi'    => 'tex',
  '.texinfo' => 'tex',
  '.v'       => 'verilog',
  '.vba'     => 'vba',
  '.vh'      => 'verilog',
  '.vhd'     => 'vhdl',
  '.vhdl'    => 'vhdl',
  '.py'      => 'python',
                                                                                                                           
  // The following are handled internally, since there's no
  // support for them in Enscript
                                                                                                                                 
  '.php'     => 'php',
  '.phtml'   => 'php',
  '.php3'    => 'php',
  '.php'     => 'php'  
);


    if (!isset($git_embed) && $git_embed != true)
        $git_embed = false;

    if (isset($_GET['dl']))
        if ($_GET['dl'] == 'targz') 
            write_targz(get_repo_path($_GET['p']));
        else if ($_GET['dl'] == 'zip')
            write_zip(get_repo_path($_GET['p']));
        else if ($_GET['dl'] == 'plain')
            write_plain();
        else if ( in_array( $_GET['dl'], $icondesc, true ) )
            write_img_png($_GET['dl']);
		else if ($_GET['dl'] == 'dlfile' )
			write_dlfile();
        else if ($_GET['dl'] == 'rss2')
            write_rss2();

    html_header();

    html_style();

    html_breadcrumbs();

    if (isset($_GET['p']))  { 
        html_spacer();
        html_summary($_GET['p']);
        html_spacer();
        if ($_GET['a'] == "commitdiff"){
            html_title("Changes");
            html_diff($_GET['p'], $_GET['h']);
        }
        else if (isset($_GET['tr']))   {
            html_title("Files");
            html_browse($_GET['p']);
        }
    }
    else    {
        html_spacer();
        html_home();
    }

    html_title("Help");
    if ( isset($_GET['p'])) {
        html_help($_GET['p']);
    }
    else {
        html_help("projectname.git ");
    }

    html_footer();

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

	// this functions existance starts from php5
	function array_diff_ukey( $array1, $array2 )
	{
	
		$a1 = array_keys( $array1 );
		$res = array();
		
		foreach( $a1 as $b ){
			if( isset( $array2[$b] ) ) continue;
			$res[$b] = $array1[$b];
		}
		return $res;
	}
	
	// creates a href= beginning and keeps record with the carryon arguments
	function html_ahref( $arguments, $class="" )
	{
		global $keepurl;
		
		$diff = array_diff_ukey( $keepurl, $arguments );
		$ahref = "<a ";
		if( $class != "" ) $ahref .= "class=\"$class\" ";
		$ahref .= "href=\"".sanitized_url();
		$a = array_keys( $diff );
		foreach( $a as $d ){
			if( $diff[$d] != "" ) $ahref .= "$d={$diff[$d]}&";
		}
		$a = array_keys( $arguments );
		foreach( $a as $d ){
			if( $arguments[$d] != "" ) $ahref .= "$d={$arguments[$d]}&";
		}
		$now = floor(time()/15/60); // one hour
		$ahref .= "tm=$now";
		$ahref .= "\">";
		return $ahref;
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
	
	
	// ******************************************************

    function html_summary($proj)    {
        $repo = get_repo_path($proj);
        html_summary_title($repo);
        html_desc($repo);
        if (!isset($_GET['t']) && !isset($_GET['b']))
            html_shortlog($proj, 20);
		else
            html_shortlog($proj, 4);
    }

    function html_browse($proj)   {

        if (isset($_GET['b']))
            html_blob($proj, $_GET['b']);
        else    {
            if (isset($_GET['t']))
                $tree = $_GET['t'];
            else if (isset($_GET['tr']))
				$tree = $_GET['tr'];
			else
                $tree = "HEAD";
            html_tree($proj, $tree); 
        }

    }

    function html_help($proj)    {
        global $http_method_prefix;
        global $communication_link;
        echo "<table>\n";
        echo "<tr><td>To clone: </td><td>git clone ";
            echo "$http_method_prefix";
            echo "$proj yourpath</td></tr>\n";
        echo "<tr><td>To communicate: </td><td><a href=$communication_link>Visit this page</a></td></tr>";
        echo "</table>\n";
    }

    function html_blob($proj, $blob)    {
        global $extEnscript;
        $repo = get_repo_path($proj);
        $out = array();
        $name=$_GET['n'];
        $plain = html_ahref( array( 'p'=>$proj, 'dl'=>"plain", 'h'=>$blob, 'n'=>$name ) ). "plain</a>";
        $ext=@$extEnscript[strrchr($name,".")];
        echo "<div style=\"float:right;padding:7px;\">$plain</div>\n";
        //echo "$ext";
        echo "<div class=\"gitcode\">\n";
        if( $ext == "" )
        {
            //echo "nonhighlight!";
            $cmd="GIT_DIR=".escapeshellarg($repo)." git-cat-file blob ".escapeshellarg($blob);
            exec( $cmd, &$out );
            $out = "<PRE>".htmlspecialchars(implode("\n",$out))."</PRE>";
            echo "$out";
            //$out = highlight_string( $out );
        }
        else if( $ext == "php" )
        {
            $cmd="GIT_DIR=".escapeshellarg($repo)." git-cat-file blob ".escapeshellarg($blob);
            exec( $cmd, &$out );
            //$out = "<PRE>".htmlspecialchars(implode("\n",$out))."</PRE>";
            highlight_string( implode("\n",$out) );
        }
        else
        {
            //echo "highlight";
            $result=0;
            $cmd="GIT_DIR=".escapeshellarg($repo)." git-cat-file blob ".escapeshellarg($blob).
				"| enscript --language=html --color=1 --highlight=".escapeshellarg($ext)." -o - | sed -n \"/<PRE/,/<\\/PRE/p\" ";
            exec("$cmd", &$out);
            $out = implode("\n",$out);
            echo "$out";
        }
        echo "</div>\n";
    }

    function html_diff($proj, $commit)    {
        $repo = get_repo_path($proj);
        //$c = git_commit( $proj, $commit );
		$c['parent'] = $_GET['hb'];
        $out = array();
        exec("GIT_DIR=".escapeshellarg($repo)." git-diff ".escapeshellarg($c['parent'])." ".
			escapeshellarg($commit)." | enscript --language=html --color=1 --highlight=diffu -o - | sed -n \"/<PRE/,/<\\/PRE/p\"  ", &$out);
        echo "<div class=\"gitcode\">\n";
        echo implode("\n",$out);
        echo "</div>\n";
    }

    function html_tree($proj, $tree)   {
		global $extEnscript;
        $t = git_ls_tree(get_repo_path($proj), $tree);

        echo "<div class=\"gitbrowse\">\n";
        echo "<table>\n";
        foreach ($t as $obj)    {
            $plain = "";
			$dlfile = "";
			$icon = "";
            $perm = perm_string($obj['perm']);
            if ($obj['type'] == 'tree'){
                $objlink = html_ahref( array( 'p'=>$proj, 'a'=>"jump_to_tag", 't'=>$obj['hash'] ) ) . $obj['file'] . "</a>\n";
				$icon = "<img src=\"".sanitized_url()."dl=icon_folder\" style=\"border-width: 0px;\"/>";
			}
            else if ($obj['type'] == 'blob')    {
                $plain = html_ahref( array( 'p'=>$proj, 'dl'=>"plain", 'h'=>$obj['hash'], 'n'=>$obj['file'] ) ) . "plain</a>";
				$dlfile = " | " . html_ahref( array( 'p'=>$proj, 'dl'=>"dlfile", 'h'=>$obj['hash'], 'n'=>$obj['file'] ) ) . "file</a>";
                $objlink = html_ahref( array( 'p'=>$proj, 'a'=>"jump_to_tag", 'b'=>$obj['hash'], 'n'=>$obj['file'] ), "blob" ) . $obj['file'] . "</a>\n";
				$ext=@$extEnscript[strrchr($obj['file'],".")];
				if( $ext == "" )
					$icon = "<img src=\"".sanitized_url()."dl=icon_plain\" style=\"border-width: 0px;\"/>";
				else
					$icon = "<img src=\"".sanitized_url()."dl=icon_color\" style=\"border-width: 0px;\"/>";
            }

            echo "<tr><td>$perm</td><td>$icon</td></td><td>$objlink</td><td>$plain$dlfile</td></tr>\n";
        }
        echo "</table>\n";
        echo "</div>\n";
    }

    function html_shortlog($repo, $lines)   {
        global $cache_name,$branches,$tags;
        $page=0;
        if( isset($_GET['pg']) )
            $page=$_GET['pg'];
        if( $page < 0 ) $page = 0;
        echo "</br><div class=\"imgtable\">\n";
        echo "<table cellspacing=\"0\" cellpadding=\"0\" border=\"0\">\n";
		switch( $_GET['a'] ){
		case "commitdiff":
			$order = create_images_parents($repo,$page,$lines,$_GET['h']);
			break;
		case "jump_to_tag":
			if( isset($_POST['tag']) && $_POST['tag'] != "" ) $start = $_POST['tag'];
			else if( isset($_POST['branch']) && $_POST['branch'] != "" ) $start = $_POST['branch'];
			else if( isset($_GET['tag']) && $_GET['tag'] != "" ) $start = $_GET['tag'];
			else $start = "HEAD";
			if( $start != "" ){
				$order = create_images_starting($repo,$page,$lines,$start);
				break;
			}
		default:
			$order = create_images($repo,$page,$lines);
			break;
		}
		$treeid = "";
		if( isset($_GET['tr']) ) $treeid = $_GET['tr'];
		//echo $treeid;
		echo "<tr height=\"20\"><th>Date</th><th>Graph</th><th>Commiter</th><th>Summary</th><th>Actions</th></tr>\n";
        for ($i = 0; ($i < $lines) && ($order[$i]!= ""); $i++)  {
            $c = git_commit($repo, $order[$i]);
            $date = date("n/j/y G:i", (int)$c['date']);
            $cid = $order[$i];
            $pid = $c['parent'];
            $mess = short_desc($c['message'], 40);
            $auth = short_desc($c['author'], 25);
			$tid = $c['tree'];
			// different ways of displaying diff
			if( $_GET['a'] == "commitdiff" ){
				if( $_GET['h'] == $cid )
					$diff = "diff";
				else if( $_GET['hb'] == $cid )
					$diff = "pare";
				else
					$diff = html_ahref( array( 'p'=>$_GET['p'], 'a'=>"commitdiff", 'h'=>$order[0], 'hb'=>$cid, 'pg'=>"", 'tr'=>"" )) ."pare</a>";
			}
			else if( $pid == "" )
                $diff = "diff";
            else
                $diff = html_ahref( array( 'p'=>$_GET['p'], 'a'=>"commitdiff", 'h'=>$cid, 'hb'=>$pid, 'pg'=>"", 'tr'=>"" )) ."diff</a>";
			// displaying tree
			if( $tid == $treeid )
				$tree = "tree";
			else
				$tree = html_ahref( array( 'p'=>$_GET['p'], 'a'=>"jump_to_tag", 'tag'=>$cid, 'tr'=>$tid, 't'=>$tid, 'pg'=>"" )) ."tree</a>";
            echo "<tr><td>$date</td>";
            echo "<td><img src=\"" . $cache_name . $repo. "/graph-".$cid.".png\" /></td>";
            echo "<td>{$auth}</td><td>";
			if( in_array($cid,$branches) ) foreach( $branches as $symbolic => $hashic ) if( $hashic == $cid ) 
				echo "<branches>".$symbolic."</branches> ";
			if( in_array($cid,$tags) ) foreach( $tags as $symbolic => $hashic ) if( $hashic == $cid ) 
				echo "<tags>".$symbolic."</tags> ";
			echo $mess;
			echo "</td><td>$diff | $tree | ".get_project_link($repo, "targz", $cid)." | ".get_project_link($repo, "zip", $cid)."</td></tr>\n"; 
            if( $_GET['a'] == "commitdiff" ) echo "<tr><td>-</td></tr>\n";
        }
		$n=0;
		$maxr=git_number_of_commits($repo);
		echo "</table><table>";
		echo "<tr height=\"20\"><td>";
		for ($j = -7; $n < 15; $j++ ){
		    $i = $page + $j * $j * $j * $lines/2;
		    if( $i < 0 ) continue;
		    if( $n>0 ) echo " | ";
		    $n++;
			if( $i > $maxr )
				$i = $maxr;
		    if( $i == $page )
		        echo "<b>[".$i."]</b>\n";
		    else
		        echo html_ahref( array( 'p'=>$_GET['p'], 'pg'=>$i, 'tr'=>"", 'tag'=>"" ) ) .$i."</a>\n";
			if( $i == $maxr )
				break;
		}
		echo "</td></tr>\n";
        echo "</table></div>\n";
}

function html_summary_title($repo){
	global $branches, $tags;
	if( $_GET['a'] != "commitdiff" ){
		echo "<form method=post action=\"".sanitized_url()."p={$_GET['p']}&a=jump_to_tag\">";
		echo "<div class=\"gittitle\">Summary :: ";
		echo "<select name=\"branch\">";
		echo "<option selected value=\"\">select a branch</option>";
		foreach( array_keys($branches) as $br ){
			echo "<option value=\"".$br."\">".$br."</option>";
		}
		echo "</select> or <select name=\"tag\">";
		echo "<option selected value=\"\">select a tag</option>";
		foreach( array_keys($tags) as $br ){
			echo "<option value=\"".$br."\">".$br."</option>";
		}
		echo "</select> and press <input type=\"submit\" name=\"branch_or_tag\" value=\"GO\">";
		echo "</div></form>";
		return $rval;
	} else {	
		echo "<div class=\"gittitle\">Summary</div>\n";
	}
}

function git_parse($repo, $what ){
	$cmd1="GIT_DIR=".escapeshellarg($repo)." git-rev-parse  --symbolic --".escapeshellarg($what)."  ";
	$out1 = array();
	$bran=array();
	exec( $cmd1, &$out1 );
	for( $i=0; $i < count( $out1 ); $i++ ){
	    $cmd2="GIT_DIR=".escapeshellarg($repo)." git-rev-list ";
    	$cmd2 .= "--max-count=1 ".escapeshellarg($out1[$i]);
		$out2 = array();
		exec( $cmd2, &$out2 );
		$bran[$out1[$i]] = $out2[0];
	}
	return  $bran;
}

    function html_desc($repo)    {
        
        $desc = file_get_contents("$repo/description"); 
        $owner = get_file_owner($repo);
        $last =  get_last($repo);

        echo "<table>\n";
        echo "<tr><td>description</td><td>$desc</td></tr>\n";
        echo "<tr><td>owner</td><td>$owner</td></tr>\n";
        echo "<tr><td>last change</td><td>$last</td></tr>\n";
        echo "</table>\n";
    }

    function html_home()    
	{
        global $repos, $git_sms_active; 
		
        echo "<table>\n<tr>";
		echo "<th>Project</th>";
		echo "<th>Description</th>";
		echo "<th>Owner</th>";
		echo "<th>Last Changed</th>";
		echo "<th>Download</th>";
		echo "<th>Hits</th>";
		if( $git_sms_active ) echo "<th>Votes</th>";
        echo "</tr>\n";
        foreach ($repos as $repo)   {
			$today = 0; $total = 0; stat_get_count( $repo, $today, $total );
			$votes = 0; get_votes( $repo, $votes );
            $desc = short_desc(file_get_contents("$repo/description")); 
            $owner = get_file_owner($repo);
            $last =  get_last($repo);
            $proj = get_project_link($repo);
            $dlt = get_project_link($repo, "targz");
            $dlz = get_project_link($repo, "zip");
			$htvote = ""; if( $git_sms_active ) $htvote = "<td>".get_project_link($repo, "htvote")." $votes </a></td>";
            echo "<tr><td>$proj</td><td>$desc</td><td>$owner</td><td>$last</td><td>$dlt | $dlz</td><td> ( $today / $total ) </td>$htvote</tr>\n";
        }
        echo "</table>";
    }

    function html_header()  {
        global $title;
        global $git_embed;
        
        if (!$git_embed)    {
            echo "<!DOCTYPE html PUBLIC \"-//W3C//DTD XHTML 1.1//EN\" \"http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd\">\n";
            echo "<html xmlns=\"http://www.w3.org/1999/xhtml\" xml:lang=\"en\">\n";
            echo "<head>\n";
            echo "\t<title>$title</title>\n";
            echo "\t<meta http-equiv=\"content-type\" content=\"text/html; charset=utf-8\"/>\n";
			echo "\t<meta NAME=\"ROBOTS\" CONTENT=\"NOFOLLOW\" />\n";
            echo "</head>\n";
            echo "<body>\n";
        }
        /* Add rss2 link */
        if (isset($_GET['p']))  {
            echo "<link rel=\"alternate\" title=\"{$_GET['p']}\" href=\"".sanitized_url()."p={$_GET['p']}&dl=rss2\" type=\"application/rss+xml\" />\n";
        }
        echo "<div id=\"gitbody\">\n";
        
    }


    function html_footer()  {
        global $git_embed;
        global $git_logo;

        echo "<div class=\"gitfooter\">\n";

        if (isset($_GET['p']))  {
            echo "<a class=\"rss_logo\" href=\"".sanitized_url()."p={$_GET['p']}&dl=rss2\" >RSS</a>\n";
        }

        if ($git_logo)    {
            echo "<a href=\"http://www.kernel.org/pub/software/scm/git/docs/\">" . 
                 "<img src=\"".sanitized_url()."dl=git_logo\" style=\"border-width: 0px;\"/></a>\n";
        }

        echo "</div>\n";
        echo "</div>\n";
        if (!$git_embed)    {
            echo "</body>\n";
            echo "</html>\n";
        }
    }


    function git_tree_head($gitdir) {
        return git_tree($gitdir, "HEAD");
    }

    function git_tree($gitdir, $tree) {

        $out = array();
        $command = "GIT_DIR=".escapeshellarg($gitdir)." git-ls-tree --name-only ".escapeshellarg($tree);
        exec($command, &$out);
    }

    function get_git($repo) {

        if (file_exists("$repo/.git"))
            $gitdir = "$repo/.git";
        else
            $gitdir = $repo;
        return $gitdir;
    }

    function get_file_owner($path)  {
        $s = stat($path);
        $pw = posix_getpwuid($s["uid"]);
        return preg_replace("/[,;]/", "", $pw["gecos"]);
//        $out = array();
//        $own = exec("GIT_DIR=".escapeshellarg($path)." git-rev-list  --header --max-count=1 HEAD | grep -a committer | cut -d' ' -f2-3" ,&$out);
//        return $own;
    }

    function get_last($repo)    {
        $out = array();
        $date = exec("GIT_DIR=".escapeshellarg($repo)." git-rev-list  --header --max-count=1 HEAD | grep -a committer | cut -f5-6 -d' '", &$out);
        return date("D n/j/y G:i", (int)$date);
    }

    function get_project_link($repo, $type = false, $tag="HEAD")    {
        $path = basename($repo);
        if (!$type)
            return "<a href=\"".sanitized_url()."p=$path\">$path</a>";
        else if ($type == "targz")
            return html_ahref( array( 'p'=>$path, 'dl'=>'targz', 'h'=>$tag ) ).".tar.gz</a>";
        else if ($type == "zip")
            return html_ahref( array( 'p'=>$path, 'dl'=>'zip', 'h'=>$tag ) ).".zip</a>";
		else if ($type == "htvote" )
			return '<a href="sms.php?p='.$path.'&amp;dl=htvote">';
    }

    function git_commit($repo, $cid)  {
        global $repo_directory;
        $out = array();
        $commit = array();

        if (strlen($cid) <= 0)
            return 0;

        $cmd="GIT_DIR=".escapeshellarg($repo_directory.$repo)." git-rev-list ";
        $cmd .= "--max-count=1 ";
        $cmd .= "--pretty=format:\"";
        $cmd .= "parents %P%n";
        $cmd .= "tree %T%n";
        $cmd .= "author %an%n";
        $cmd .= "date %at%n";
        $cmd .= "message %s%n";
        $cmd .= "endrecord%n\" ";
        $cmd .= $cid;

        exec($cmd, &$out);
        
        foreach( $out as $line )
        {
            // tking the data descriptor
            unset($d);
        	$d = explode( " ", $line );
        	$descriptor = $d[0];
        	$d = array_slice( $d, 1 );
        	switch($descriptor)
        	{
        	case "commit":
        		$commit["commit_id"] = $d[0];
        		break;
        	case "parents":
        		$commit["parent"] = $d[0];
        		break;
        	case "tree":
        		$commit["tree"] = $d[0];
        		break;
        	case "author":
        		$commit["author"] = implode( " ", $d );
        		break;
        	case "date":
        		$commit["date"] = $d[0];
        		break;
        	case "message":
        		$commit["message"] = implode( " ", $d );
        		break;
        	case "endrecord":
        	    break;
        	}
        }        
        return $commit;
    }

    function get_repo_path($proj)   {
        global $repos;
    
        foreach ($repos as $repo)   {
            $path = basename($repo);
            if ($path == $proj)
                return $repo;
        }
    }

    function git_ls_tree($repo, $tree) {
        $ary = array();
            
        $out = array();
        //Have to strip the \t between hash and file
        exec("GIT_DIR=".escapeshellarg($repo)." git-ls-tree ".escapeshellarg($tree)." | sed -e 's/\t/ /g'", &$out);

        foreach ($out as $line) {
            $entry = array();
            $arr = explode(" ", $line);
            $entry['perm'] = $arr[0];
            $entry['type'] = $arr[1];
            $entry['hash'] = $arr[2];
            $entry['file'] = $arr[3];
            $ary[] = $entry;
        }
        return $ary;
    }

    /* TODO: cache this */
    function sanitized_url()    {
        global $git_embed;

        /* the sanitized url */
        $url = "{$_SERVER['SCRIPT_NAME']}?";

        if (!$git_embed)    {
            return $url;
        }

        /* the GET vars used by git-php */
        $git_get = array('p', 'dl', 'b', 'a', 'h', 't');


        foreach ($_GET as $var => $val) {
            if (!in_array($var, $git_get))   {
                $get[$var] = $val;
                $url.="$var=$val&amp;";
            }
        }
        return $url;
    }

	function write_plain() {
        $repo = get_repo_path($_GET['p']);
        $name = $_GET['n'];
        $hash = $_GET['h'];
		$out = array();
		exec("GIT_DIR=".escapeshellarg($repo)." git-cat-file blob ".escapeshellarg($hash), &$out);
		header("Content-Type: text/plain");
		echo implode("\n",$out);
		die();
	}
	
    function write_dlfile()  {
        $repo = get_repo_path($_GET['p']);
        $name = $_GET['n'];
        $hash = $_GET['h'];
		exec("GIT_DIR=".escapeshellarg($repo)." git-cat-file blob ".escapeshellarg($hash)." > ".escapeshellarg("/tmp/$hash.$name"));
        $filesize = filesize("/tmp/$hash.$name");
        header("Pragma: public"); // required
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private",false); // required for certain browsers
        header("Content-Transfer-Encoding: binary");
        //header("Content-Type: application/x-tar-gz");
        header("Content-Length: " . $filesize);
        header("Content-Disposition: attachment; filename=\"$name\";" );
        //$str = system("GIT_DIR=$repo git-cat-file blob $hash 2>/dev/null");
		readfile( "/tmp/$hash.$name" );
        die();
    }

	function hash_to_tag( $hash ){
		global $tags;
		if( in_array( $hash, $tags, true ) ){
			return array_search( $hash, $tags, true );
		}
		return $hash;
	}
	
    function write_targz($repo) {
        $p = basename($repo);
		$head = hash_to_tag($_GET['h']);
        $proj = explode(".", $p);
        $proj = $proj[0]; 
        exec("cd /tmp && git-clone ".escapeshellarg($repo)." ".escapeshellarg($proj)." && cd ".
			escapeshellarg($proj)." && git-checkout ".escapeshellarg($head).
			" && cd /tmp && rm -Rf ".escapeshellarg("/tmp/$proj/.git")." && tar czvf ".
			escapeshellarg("$proj-$head.tar.gz")." ".escapeshellarg($proj));
		exec("rm -Rf ".escapeshellarg("/tmp/$proj"));
        
        $filesize = filesize("/tmp/$proj-$head.tar.gz");
        header("Pragma: public"); // required
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private",false); // required for certain browsers
        header("Content-Transfer-Encoding: binary");
        header("Content-Type: application/x-tar-gz");
        header("Content-Length: " . $filesize);
        header("Content-Disposition: attachment; filename=\"$proj-$head.tar.gz\";" );
        readfile("/tmp/$proj-$head.tar.gz");
        die();
    }

    function write_zip($repo) {
        $p = basename($repo);
		$head = hash_to_tag($_GET['h']);
        $proj = explode(".", $p);
        $proj = $proj[0]; 
        exec("cd /tmp && git-clone ".escapeshellarg($repo)." ".escapeshellarg($proj)." && cd ".
			escapeshellarg($proj)." && git-checkout ".escapeshellarg($head)." && cd /tmp && rm -Rf ".escapeshellarg("/tmp/$proj/.git").
			" && zip -r ".escapeshellarg("$proj-$head.zip")." ".escapeshellarg($proj));
		exec("rm -Rf ".escapeshellarg("/tmp/$proj"));
        
        $filesize = filesize("/tmp/$proj-$head.zip");
        header("Pragma: public"); // required
        header("Expires: 0");
        header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header("Cache-Control: private",false); // required for certain browsers
        header("Content-Transfer-Encoding: binary");
        header("Content-Type: application/x-zip");
        header("Content-Length: " . $filesize);
        header("Content-Disposition: attachment; filename=\"$proj-$head.zip\";" );
        readfile("/tmp/$proj-$head.zip");
        die();
    }

    function write_rss2()   {
        $proj = $_GET['p'];
        //$repo = get_repo_path($proj);
        $link = "http://{$_SERVER['HTTP_HOST']}".sanitized_url()."p=$proj";
        $c = git_commit($proj, "HEAD");

        header("Content-type: text/xml", true);
        
        echo '<?xml version="1.0" encoding="UTF-8"?>';
        ?>
        <rss version="2.0"
        xmlns:content="http://purl.org/rss/1.0/modules/content/"
        xmlns:wfw="http://wellformedweb.org/CommentAPI/"
        xmlns:dc="http://purl.org/dc/elements/1.1/"
        >

       
        <channel>
            <title><?php echo $proj ?></title>
            <link><?php echo $link ?></link>
            <description><?php echo $proj ?></description>
            <pubDate><?php echo date('D, d M Y G:i:s', $c['date'])?></pubDate>
            <generator>http://code.google.com/p/git-php/</generator>
            <language>en</language>
            <?php for ($i = 0; $i < 10 && $c; $i++): ?>
            <item>
                <title><?php echo $c['message'] ?></title>
                <link><?php echo $link?></link>
                <pubDate><?php echo date('D, d M Y G:i:s', $c['date'])?></pubDate>
                <guid isPermaLink="false"><?php echo $link ?></guid>
                <description><?php echo $c['message'] ?></description>
                <content><?php echo $c['message'] ?></content>
            </item>
            <?php $c = git_commit($proj, $c['parent']);
                $link = "http://{$_SERVER['HTTP_HOST']}".sanitized_url()."p=$proj&amp;a=commitdiff&amp;h={$c['commit_id']}&amp;hb={$c['parent']}&amp;tm=0";
                  endfor;
            ?>
        </channel>
        </rss>
        <?php
        die();
    }

    function perm_string($perms)    {

        //This sucks
        switch ($perms) {
            case '040000':
                return 'drwxr-xr-x';
            case '100644':
                return '-rw-r--r--';
            case '100755':
                return '-rwxr-xr-x';
            case '120000':
                return 'lrwxrwxrwx';

            default:
                return '----------';
        }
    }

    function short_desc($desc, $size=25)  {
        $trunc = false;
        $short = "";
        $d = explode(" ", $desc);
        foreach ($d as $str)    {
            if (strlen($short) < $size)
                $short .= "$str ";
            else    {
                $trunc = true;
                break;
            }
        }

        if ($trunc)
            $short .= "...";

        return $short;
    }

    function html_spacer($text = "&nbsp;")  {
        echo "<div class=\"gitspacer\">$text</div>\n";
    }

    function html_title($text = "&nbsp;")  {
        echo "<div class=\"gittitle\">".$text."</div>\n";
    }

    function html_breadcrumbs()  {
        echo "<div class=\"githead\">\n";
        $crumb = "<a href=\"".sanitized_url()."\">projects</a> / ";

        if (isset($_GET['p']))
            $crumb .= html_ahref( array( 'p'=>$_GET['p'], 'pg'=>"" ) ) . $_GET['p'] ."</a> / ";
        
        if (isset($_GET['b']))
            $crumb .= "blob";

        if (isset($_GET['t']))
            $crumb .= "tree";

        if ($_GET['a'] == 'commitdiff')
            $crumb .= 'commitdiff';

        echo $crumb;
        echo "</div>\n";
    }

    function zpr ($arr) {
        print "<pre>" .print_r($arr, true). "</pre>";
    }

    function highlight($code) {

        if (substr($code, 0,2) != '<?')    {
            $code = "<?\n$code\n?>";
            $add_tags = true;
        }
        $code = highlight_string($code,1);

        if ($add_tags)  {
            //$code = substr($code, 0, 26).substr($code, 36, (strlen($code) - 74));
            $code = substr($code, 83, strlen($code) - 140);    
            $code.="</span>";
        }

        return $code;
    }

    function highlight_code($code) {

        define(COLOR_DEFAULT, '000');
        define(COLOR_FUNCTION, '00b'); //also for variables, numbers and constants
        define(COLOR_KEYWORD, '070');
        define(COLOR_COMMENT, '800080');
        define(COLOR_STRING, 'd00');

        // Check it if code starts with PHP tags, if not: add 'em.
        if(substr($code, 0, 2) != '<?') {
            $code = "<?\n".$code."\n?>";
            $add_tags = true;
        }
        
        $code = highlight_string($code, true);

        // Remove the first "<code>" tag from "$code" (if any)
        if(substr($code, 0, 6) == '<code>') {
           $code = substr($code, 6, (strlen($code) - 13));
        }

        // Replacement-map to replace deprecated "<font>" tag with "<span>"
        $xhtml_convmap = array(
           '<font' => '<span',
           '</font>' => '</span>',
           'color="' => 'style="color:',
           '<br />' => '<br/>',
           '#000000">' => '#'.COLOR_DEFAULT.'">',
           '#0000BB">' => '#'.COLOR_FUNCTION.'">',
           '#007700">' => '#'.COLOR_KEYWORD.'">',
           '#FF8000">' => '#'.COLOR_COMMENT.'">',
           '#DD0000">' => '#'.COLOR_STRING.'">'
        );

        // Replace "<font>" tags with "<span>" tags, to generate a valid XHTML code
        $code = strtr($code, $xhtml_convmap);

        //strip default color (black) tags
        $code = substr($code, 25, (strlen($code) -33));

        //strip the PHP tags if they were added by the script
        if($add_tags) {
            
            $code = substr($code, 0, 26).substr($code, 36, (strlen($code) - 74));
        }

        return $code;
    }

    function html_style()   {
        global $git_css;
        
        if (file_exists("style.css"))
            echo "<link rel=\"stylesheet\" href=\"style.css\" type=\"text/css\" />\n";
        if (!$git_css)
            return;

        else    {
        echo "<style type=\"text/css\">\n";
        echo <<< EOF
            #gitbody    {
                margin: 10px 10px 10px 10px;
                border-style: solid;
                border-width: 1px;
                border-color: gray;
                font-family: sans-serif;
                font-size: 12px;
            }

            div.githead    {
                margin: 0px 0px 0px 0px;
                padding: 10px 10px 10px 10px;
                background-color: #d9d8d1;
                font-weight: bold;
                font-size: 18px;
            }

            #gitbody th {
                text-align: left;
                padding: 0px 0px 0px 7px;
            }

            #gitbody td {
                padding: 0px 0px 0px 7px;
            }
            
            div.imgtable table{
                padding: 0px 0px 0px 7px;
                border-width: 0px;
                line-height: 1px;
                font-size: 9px;
            }
			
			div.imgtable tags{
				color: #009900;
				background-color: #FFFF00;
			}

			div.imgtable branches{
				color: #CC6600;
				background-color: #99FF99;
			}
			
			
            tr:hover { background-color:#cdccc6; }

            div.gitbrowse a.blob {
                text-decoration: none;
                color: #000000;
            }

            div.gitcode {
                padding: 10px;
            }

            div.gitspacer   {
                padding: 1px 0px 0px 0px;
                background-color: #FFFFFF;
            }

            div.gitfooter {
                padding: 7px 2px 2px 2px;
                background-color: #d9d8d1;
                text-align: right;
            }

            div.gittitle   {
                padding: 7px 7px 7px 7px;
                background-color: #d9d8d1;
                font-weight: bold;
            }

            div.gitbrowse a.blob:hover {
                text-decoration: underline;
            }
            a.gitbrowse:hover { text-decoration:underline; color:#880000; }
            a.rss_logo {
                float:left; padding:3px 0px; width:35px; line-height:10px;
                    margin: 2px 5px 5px 5px;
                    border:1px solid; border-color:#fcc7a5 #7d3302 #3e1a01 #ff954e;
                    color:#ffffff; background-color:#ff6600;
                    font-weight:bold; font-family:sans-serif; font-size:10px;
                    text-align:center; text-decoration:none;
                }
            a.rss_logo:hover { background-color:#ee5500; }
EOF;

        echo "</style>\n";
        }
    }
	
function git_number_of_commits( $repo )
{
	global $repo_directory;
	
    $cmd="GIT_DIR=".escapeshellarg($repo_directory.$repo)." git-rev-list --all --full-history | grep -c \"\" ";
	unset($out);
	$out = array();
    //echo "$cmd\n";
    $rrv= exec( $cmd, &$out );
	return intval( $out[0] );
}
	
	// *****************************************************************************
	// statistics
	//

function stat_inc_count( $proj )
{
	$td = 0; $tt = 0;
	stat_get_count( $proj, $td, $tt, true );
}

function stat_get_count( $proj, &$today, &$total, $inc=false )
{
	file_stat_get_count( $proj, $today, $total, $inc, 'counters' );
}
		
	// *****************************************************************************
	// filesystem functions
	//
	
function create_directory( $fullpath )
{
	if( ($fullpath[0] != '/') && ($fullpath[1] == 0) ){
		echo "Wrong path name $fullpath\n";
		die();
	}
    if( ! is_dir($fullpath) ){
        if( ! mkdir($fullpath) ){
            echo "Error by making directory $fullpath\n";
            die();
        }
    }
    chmod( $fullpath, 0777 );	
}

function file_stat_get_count( $proj, &$today, &$total, $inc, $fbasename )
{
	global $cache_name;
	$rtoday = 0;
	$rtotal = 0;
	$now = floor(time()/24/60/60); // number of days since 1970
	$fname = dirname($proj)."/".$cache_name."/".$fbasename."-".basename($proj,".git");
	$fd = 0;
	
	
	//$fp1 = sem_get(fileinode($fname), 1);
	//sem_acquire($fp1);
	
	if( file_exists( $fname ) )
		$file = fopen( $fname, "r" ); // open or create the counter file
	else
		$file = FALSE;
	if( $file != FALSE ){
		fseek( $file, 0 ); // rewind the file to beginning
		// read out the counter value
		fscanf( $file, "%d %d %d", $fd, $rtoday, $rtotal );
		if( $fd != $now ){
			$rtoday = 0;
			$fd = $now;
		}
		if( $inc ){
			$rtoday++;
			$rtotal++;
		}
		fclose( $file );
	}
	// uncomment the next lines to erase the counters
	//$rtoday = 0;
	//$rtotal = 0;	
	$file = fopen( $fname, "w" ); // open or create the counter file	
	// write the counter value
	fseek( $file, 0 ); // rewind the file to beginning
	fwrite( $file, "$fd $rtoday $rtotal\n" );
	fclose( $file );
	$today = $rtoday;
	$total = $rtotal;	
}

	
	// *****************************************************************************
	// Graph tree drawing section
	//
function create_cache_directory( $repo )
{
	global $repo_directory, $cache_directory;
	$dirname=$cache_directory.$repo;
	
    create_directory( $dirname );
}

function analyze_hierarchy( &$vin, &$pin, &$commit, &$coord, &$parents, &$nr ){
    // figure out the position of this node
    if( in_array($commit,$pin,true) ){
        $coord[$nr] = array_search( $commit,$pin,true ); // take reserved coordinate
        $pin[$coord[$nr]] = "."; // free the reserved coordinate
    }else{
        if( ! in_array( ".", $pin, true ) ){ // make empty coord plce
            $pin[] = ".";
    		$vin[] = ".";
        }
        $coord[$nr] = array_search( ".", $pin, true ); // take the first unused coordinate
        // do not allocate this place in array as this is already freed place
    }
    //reserve place for parents
    $pc=0;
    foreach( $parents as $p ){ 
        if( in_array( $p, $pin, true ) ){ $pc++; continue; } // the parent alredy has place
        if( $pc == 0 ){ $pin[$coord[$nr]] = $p; $pc++; continue; } // try to keep the head straigth
        if( in_array( ".", $pin, true ) ){ // take leftmost empty place from array
            $x = array_search( ".", $pin, true );
            $pin[$x] = $p;
        }else{ // allcate new place into array
            $pin[] = $p;
    		$vin[] = ".";
        }
    }
	//reduce image width if possible
	while( count($pin) > ($coord[$nr]+1) )
	{
		$valpin = array_pop($pin);
		$valvin = array_pop($vin);
		if( $valpin == "." && $valvin == "." ) continue;
		$pin[] = $valpin;
		$vin[] = $valvin;
		break;
	}
}

function create_images_starting( $repo, &$retpage, $lines, $commit_name ){

	global $repo_directory, $cache_directory;
	$dirname=$cache_directory.$repo;
    create_cache_directory( $repo );
	
    $cmd="GIT_DIR=".escapeshellarg($repo_directory.$repo)." git-rev-list ";
    $cmd .= "--max-count=1 ".escapeshellarg($commit_name);
	unset($out);
	$out = array();

    //echo "$cmd\n";
    $rrv= exec( $cmd, &$out );
	$commit_start = $out[0];
	//echo "$commit_start\n";

    $page=-1; // the counter of made lines
    $order=array(); // the commit sha-s
    $coord=array(); // holds X position in tree
    $pin=array( "." ); // holds reserved X positions in tree
    $cross = array(); // lists rows that participate on the drawing of the slice as xstart,ystart,xend,yend,xstart,ystart,xend,yend,...
    $count = array(); // holds number of open lines, if this becomes 0, the slice can be drawn
    $crossf = array(); // the floating open lines section
    $countf = 0; // the counter of unknown coordinates of floating section
    $nr=0; // counts rows
    $top=0; // the topmost undrawn slice
    $todo=array( $commit );
    $todoc=1;
    do{
        unset($cmd);
        $cmd="GIT_DIR=".escapeshellarg($repo_directory.$repo)." git-rev-list --all --full-history --date-order ";
        $cmd .= "--max-count=1000 --skip=" .escapeshellarg($nr) ." ";
        $cmd .= "--pretty=format:\"";
        $cmd .= "parents %P%n";
        $cmd .= "endrecord%n\"";
   		unset($out);
        $out = array();

        //echo "$cmd\n";
        $rrv= exec( $cmd, &$out );
        //echo implode("\n",$out);
                
        // reading the commit tree
        $descriptor="";
        $commit="";
        $parents=array();
        foreach( $out as $line )
        {
            if( $page > $lines ) return $order; // break the image creation if more is not needed
            // taking the data descriptor
            unset($d);
        	$d = explode( " ", $line );
        	$descriptor = $d[0];
        	$d = array_slice( $d, 1 );
        	switch($descriptor)
        	{
        	case "commit":
        		$commit=$d[0];
        		break;
        	case "parents":
        		$parents=$d;
        		break;
        	case "endrecord":
        		if( $page >=0 || $commit == $commit_start ){ 
					$page++;
        		    $order[$page] = $commit; 
					if( $page == 0 ) $retpage = $nr;
        		}
                $vin = $pin;
        		analyze_hierarchy( $vin, $pin, $commit, $coord, $parents, $nr );
        		if( $page >= 0 )
    				draw_slice( $dirname, $commit, $coord[$nr], $nr, $parents, $pin, $vin );
				unset($vin);
        		//take next row
        		$nr = $nr +1;
        		unset($descriptor);
        		unset($commit);
        		unset($parents);
        		$parents=array();
        		break;
        	}
        }
    }while( count( $out ) > 0 );
    unset($out);
    $rows = $nr;
    $cols = count($pin);
    unset($pin,$nr);
    //echo "number of items ".$rows."\n";
    //echo "width ".$cols."\n";
    
    return $order;
}

function create_images_parents( $repo, &$retpage, $lines, $commit ){
	global $repo_directory, $cache_directory;
	$dirname=$cache_directory.$repo;
    create_cache_directory( $repo );

    $page=0; // the counter of made lines
    $order=array(); // the commit sha-s
    $coord=array(); // holds X position in tree
    $pin=array( "." ); // holds reserved X positions in tree
    $cross = array(); // lists rows that participate on the drawing of the slice as xstart,ystart,xend,yend,xstart,ystart,xend,yend,...
    $count = array(); // holds number of open lines, if this becomes 0, the slice can be drawn
    $crossf = array(); // the floating open lines section
    $countf = 0; // the counter of unknown coordinates of floating section
    $nr=0; // counts rows
    $top=0; // the topmost undrawn slice
    $todo=array( $commit );
    $todoc=1;
    do{
        unset($cmd);
        $cmd="GIT_DIR=".escapeshellarg($repo_directory.$repo)." git-rev-list --all --full-history --date-order ";
        $cmd .= "--max-count=1000 --skip=" .escapeshellarg($nr) ." ";
        $cmd .= "--pretty=format:\"";
        $cmd .= "parents %P%n";
        $cmd .= "endrecord%n\"";
   		unset($out);
        $out = array();

        //echo "$cmd\n";
        $rrv= exec( $cmd, &$out );
        //echo implode("\n",$out);
                
        // reading the commit tree
        $descriptor="";
        $commit="";
        $parents=array();
        foreach( $out as $line )
        {
            if( $page > $lines ) return $order; // break the image creation if more is not needed
            if( $todoc <= 0 ) return $order; // break the image creation if more is not needed
            // taking the data descriptor
            unset($d);
        	$d = explode( " ", $line );
        	$descriptor = $d[0];
        	$d = array_slice( $d, 1 );
        	switch($descriptor)
        	{
        	case "commit":
        		$commit=$d[0];
        		break;
        	case "parents":
        		$parents=$d;
        		break;
        	case "endrecord":
        		if( in_array($commit,$todo,true) ){ 
        		    $order[$page] = $commit; 
        		    $todoc--;
        		    if($page==0){
        		        $todo = array_merge( $todo, $parents );
        		        $retpage = $nr;
        		    }
        		    $page++;
        		    $todoc = $todoc + count( $parents );
        		}
                $vin = $pin;
        		analyze_hierarchy( $vin, $pin, $commit, $coord, $parents, $nr );
        		if( in_array($commit,$todo,true) )
    				draw_slice( $dirname, $commit, $coord[$nr], $nr, $parents, $pin, $vin );
				unset($vin);
        		//take next row
        		$nr = $nr +1;
        		unset($descriptor);
        		unset($commit);
        		unset($parents);
        		$parents=array();
        		break;
        	}
        }
    }while( count( $out ) > 0 );
    unset($out);
    $rows = $nr;
    $cols = count($pin);
    unset($pin,$nr);
    //echo "number of items ".$rows."\n";
    //echo "width ".$cols."\n";
    
    return $order;
}

function create_images( $repo, $page, $lines ){
	global $repo_directory, $cache_directory;
	$dirname=$cache_directory.$repo;
    create_cache_directory( $repo );
	
    $order=array(); // the commit sha-s
    $coord=array(); // holds X position in tree
    $pin=array( "." ); // holds reserved X positions in tree
    $cross = array(); // lists rows that participate on the drawing of the slice as xstart,ystart,xend,yend,xstart,ystart,xend,yend,...
    $count = array(); // holds number of open lines, if this becomes 0, the slice can be drawn
    $crossf = array(); // the floating open lines section
    $countf = 0; // the counter of unknown coordinates of floating section
    $nr=0; // counts rows
    $top=0; // the topmost undrawn slice
    do{
        unset($cmd);
        $cmd="GIT_DIR=".escapeshellarg($repo_directory.$repo)." git-rev-list --all --full-history --date-order ";
        $cmd .= "--max-count=1000 --skip=" .escapeshellarg($nr) ." ";
        $cmd .= "--pretty=format:\"";
        $cmd .= "parents %P%n";
        $cmd .= "endrecord%n\"";
   		unset($out);
        $out = array();

        //echo "$cmd\n";
        $rrv= exec( $cmd, &$out );
        //echo implode("\n",$out);
                
        // reading the commit tree
        $descriptor="";
        $commit="";
        $parents=array();
        foreach( $out as $line )
        {
            if( $nr > $page + $lines ) return $order; // break the image creation if more is not needed
            // taking the data descriptor
            unset($d);
        	$d = explode( " ", $line );
        	$descriptor = $d[0];
        	$d = array_slice( $d, 1 );
        	switch($descriptor)
        	{
        	case "commit":
        		$commit=$d[0];
        		break;
        	case "parents":
        		$parents=$d;
        		break;
        	case "endrecord":
        		if($nr-$page >= 0) $order[$nr-$page] = $commit;
                $vin = $pin;
        		analyze_hierarchy( $vin, $pin, $commit, $coord, $parents, $nr );
				draw_slice( $dirname, $commit, $coord[$nr], $nr, $parents, $pin, $vin );
				unset($vin);
        		//take next row
        		$nr = $nr +1;
        		unset($descriptor);
        		unset($commit);
        		unset($parents);
        		$parents=array();
        		break;
        	}
        }
    }while( count( $out ) > 0 );
    unset($out);
    $rows = $nr;
    $cols = count($pin);
    unset($pin,$nr);
    //echo "number of items ".$rows."\n";
    //echo "width ".$cols."\n";
    
    return $order;
}

// draw the graph slices
function draw_slice( $dirname, $commit, $x, $y, $parents, $pin, $vin )
{
	global $tags, $branches;

    $w = 7; $wo = 3;
    $h = 15; $ho = 7;
    $r = 7; $rj = 8;

    $columns = count($pin);
	$lin = array_fill(0,$columns,'-');

    $im = imagecreate( $w * $columns, $h );
    $cbg = imagecolorallocate( $im, 255, 255, 255 );
    $ctr = imagecolortransparent( $im, $cbg );
    $cmg = imagecolorallocate( $im, 0, 0, 200 );
    $cbl = imagecolorallocate( $im, 0, 0, 0 );
    $crd = imagecolorallocate( $im, 255, 0, 0 );
	
	$cci = imagecolorallocate( $im, 150, 150, 150 );
	$ctg = imagecolorallocate( $im, 255, 255, 0 );
	$cbr = imagecolorallocate( $im, 255, 0, 0 );


	for( $i=0; $i<$columns; $i++ ){
		if( $vin[$i] == $commit ){
			// small vertical
			imageline( $im, $i * $w + $wo, $ho, $i * $w + $wo, 0, $cmg );
			imageline( $im, $i * $w + $wo-1, $ho, $i * $w + $wo-1, 0, $cmg );
		}
		if( $pin[$i] != "." ){
			// we have a parent
			if( in_array($pin[$i],$parents,true) ){
				// the parent is our parent
				// draw the horisontal for it
				imageline( $im, $i * $w + $wo, $ho, $x * $w + $wo, $ho, $cmg );
				imageline( $im, $i * $w + $wo, $ho-1, $x * $w + $wo, $ho-1, $cmg );
				// draw the little vertical for it
				if( $pin[$i] == $parents[0] ){
    				imageline( $im, $i * $w + $wo, $ho, $i * $w + $wo, $h, $crd );
    				imageline( $im, $i * $w + $wo-1, $ho, $i * $w + $wo-1, $h, $crd );
    			}
    			else{
    				imageline( $im, $i * $w + $wo, $ho, $i * $w + $wo, $h, $cmg );
    				imageline( $im, $i * $w + $wo-1, $ho, $i * $w + $wo-1, $h, $cmg );
    			}
				// look if this is requested for the upper side
				if( $vin[$i] == $pin[$i] ){
					// small vertical for upper side
					imageline( $im, $i * $w + $wo, $ho, $i * $w + $wo, 0, $cmg );
					imageline( $im, $i * $w + $wo-1, $ho, $i * $w + $wo-1, 0, $cmg );
				}
				// mark the cell to have horisontal
				$k = $x;
				while( $k != $i ){
					$lin[$k] = '#';
					if( $k > $i ){ $k = $k-1; } else { $k = $k+1; }
				}
			}
		}
	}
	// draw passthrough lines
	for( $i=0; $i<$columns; $i++ ){
		if( $pin[$i] != "." && ! in_array($pin[$i],$parents,true) ){
			// it is not a parent for this node
			// check if we have horisontal for this column
			if( $lin[$i] == '#' ){
				// draw pass-by junction
				if( $i < $x )
				    imagearc( $im, $i * $w + $wo, $ho, $rj, $rj+1, 90, 270, $cmg );
				else
				    imagearc( $im, $i * $w + $wo, $ho, $rj, $rj+1, 270, 90, $cmg );
				imageline( $im, $i * $w + $wo, 0, $i * $w + $wo, ($h - $rj) / 2, $cmg );
				imageline( $im, $i * $w + $wo-1, 0, $i * $w + $wo-1, ($h - $rj) / 2, $cmg );
				imageline( $im, $i * $w + $wo, $h-($h - $rj) / 2, $i * $w + $wo, $h, $cmg );
				imageline( $im, $i * $w + $wo-1, $h-($h - $rj) / 2, $i * $w + $wo-1, $h, $cmg );
			} else {
				// draw vertical
				imageline( $im, $i * $w + $wo, 0, $i * $w + $wo, $h, $cmg );
				imageline( $im, $i * $w + $wo-1, 0, $i * $w + $wo-1, $h, $cmg );
			}
		}
	}

	$fillcolor = $cci;
	$color = $cmg;
	
	if( in_array( $commit, $tags ) ) $fillcolor = $ctg;
	if( in_array( $commit, $branches ) ) $color = $cbl;
	
    imagefilledellipse( $im, $x * $w + $wo, $ho, $r, $r, $fillcolor );
	imageellipse( $im, $x * $w + $wo, $ho, $r, $r, $color );
    $filename = $dirname."/graph-".$commit.".png";
    imagepng( $im, $filename );
    chmod( $filename, 0777 );
    //chgrp( $filename, intval(filegroup($repo_directory)) );
    //echo "$filename\n";
}

	// *****************************************************************************
	// SMS voting section
	// http://fortumo.com/main/about_premium
	//

function get_votes( $proj, &$total )
{
	$td = 0;
	file_stat_get_count( $proj, $td, $total, false, 'votes' );
}


	// *****************************************************************************
	// Icons, hardcoded pictures ...
	//


function write_img_png($imgptr)
{
	$img['icon_folder']['name'] = "icon_folder.png";
	$img['icon_folder']['bin'] = "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a\x00\x00\x00\x0d\x49\x48\x44\x52".
	"\x00\x00\x00\x10\x00\x00\x00\x10\x08\x06\x00\x00\x00\x1f\xf3\xff" .
	"\x61\x00\x00\x00\x06\x62\x4b\x47\x44\x00\xff\x00\xff\x00\xff\xa0" .
	"\xbd\xa7\x93\x00\x00\x00\x09\x70\x48\x59\x73\x00\x00\x0b\x13\x00" .
	"\x00\x0b\x13\x01\x00\x9a\x9c\x18\x00\x00\x00\x07\x74\x49\x4d\x45" .
	"\x07\xd7\x0a\x07\x13\x33\x34\xdf\x37\x2a\x83\x00\x00\x00\x1d\x74" .
	"\x45\x58\x74\x43\x6f\x6d\x6d\x65\x6e\x74\x00\x43\x72\x65\x61\x74" .
	"\x65\x64\x20\x77\x69\x74\x68\x20\x54\x68\x65\x20\x47\x49\x4d\x50" .
	"\xef\x64\x25\x6e\x00\x00\x00\xbb\x49\x44\x41\x54\x38\xcb\xc5\x92" .
	"\xb1\x0e\xc2\x20\x10\x86\xbf\xa2\xaf\x40\xe2\x02\xc3\x25\x3e\x8f" .
	"\x8f\xd0\xd5\xd5\xc5\x87\x70\x71\xf5\x35\x7c\x1e\x13\x86\x76\xec" .
	"\xd8\xd9\xe0\x52\x9a\x13\xa1\x75\xeb\x9f\x10\x02\xf7\x1f\xf7\x01" .
	"\x07\x5b\xab\xa9\xec\xc7\x3f\x7d\xc5\x40\x7c\x3e\x8e\x78\x67\x01" .
	"\xe8\xfa\x81\xd3\xf9\x55\x3d\xc4\xe4\xc9\x22\x82\x77\x96\x43\x3b" .
	"\x72\x68\x47\xbc\xb3\x88\x48\xa2\x8a\x93\x27\xfe\x10\x88\x48\xbc" .
	"\x5f\x77\x00\x73\xf5\x92\xba\x7e\x00\xe0\x72\x7b\x13\x42\x68\x66" .
	"\x82\x10\xc2\x6a\xb2\x8e\x27\xbf\x29\x05\x75\xa5\xd2\x5a\xfb\x4c" .
	"\x0d\xd3\x3b\x4b\xd7\x0f\x73\x62\x5a\xe7\xda\x2f\x61\xe6\xd7\x29" .
	"\x5d\xaf\x4a\x90\xaa\xe9\x79\x95\x20\xa1\xeb\x1e\x28\xd1\xe8\x83" .
	"\x8c\xfa\xc6\xc5\xc7\xca\x93\x93\xff\x4b\x53\x83\xac\x0e\xdd\x48" .
	"\xdb\xeb\x03\x99\xd1\x5c\xda\xa6\x06\x82\x95\x00\x00\x00\x00\x49" .
	"\x45\x4e\x44\xae\x42\x60\x82";

	$img['icon_plain']['name'] = "icon_plain.png";
	$img['icon_plain']['bin'] =  "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a\x00\x00\x00\x0d\x49\x48\x44\x52" .
	"\x00\x00\x00\x10\x00\x00\x00\x10\x08\x06\x00\x00\x00\x1f\xf3\xff" .
	"\x61\x00\x00\x00\x06\x62\x4b\x47\x44\x00\xff\x00\xff\x00\xff\xa0" .
	"\xbd\xa7\x93\x00\x00\x00\x09\x70\x48\x59\x73\x00\x00\x0b\x13\x00" .
	"\x00\x0b\x13\x01\x00\x9a\x9c\x18\x00\x00\x00\x07\x74\x49\x4d\x45" .
	"\x07\xd7\x0a\x07\x13\x35\x1d\xcb\xdf\x15\x69\x00\x00\x00\x1d\x74" .
	"\x45\x58\x74\x43\x6f\x6d\x6d\x65\x6e\x74\x00\x43\x72\x65\x61\x74" .
	"\x65\x64\x20\x77\x69\x74\x68\x20\x54\x68\x65\x20\x47\x49\x4d\x50" .
	"\xef\x64\x25\x6e\x00\x00\x00\x7a\x49\x44\x41\x54\x38\xcb\xbd\x93" .
	"\x5d\x0a\x80\x30\x0c\x83\x53\x4f\xd6\xec\xdd\x1d\x76\x1e\xc0\xdd" .
	"\xac\xbe\x4c\x29\xfb\x81\x29\x6a\x60\x50\xda\x8e\x7c\x04\x0a\xbc" .
	"\x25\xaa\x1a\x80\xe1\x2b\xf3\x46\xe2\x6a\x33\xb3\xa1\x81\x88\xf4" .
	"\xfe\x60\xa9\x17\x03\x89\x40\x36\x75\xa1\x44\x21\xba\x4f\x10\x48" .
	"\xac\x31\x62\x4b\x09\x00\xb0\xe7\x2c\xf5\x8e\x9d\xa2\xaa\x51\xf5" .
	"\xaa\x7d\xcf\xe5\x72\x8f\xa0\x93\x87\x4c\x65\xe0\x7b\x3e\x8f\x6f" .
	"\x09\x7a\xae\x3d\xf7\xff\x33\xf8\x87\xe0\xb3\x63\x9a\x39\xac\x47" .
	"\x3a\x00\x9a\x8c\x62\xbd\x3f\x77\x7d\x0b\x00\x00\x00\x00\x49\x45" .
	"\x4e\x44\xae\x42\x60\x82";

	$img['icon_color']['name'] = "icon_color.png";
	$img['icon_color']['bin'] =  "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a\x00\x00\x00\x0d\x49\x48\x44\x52" .
	"\x00\x00\x00\x10\x00\x00\x00\x10\x08\x06\x00\x00\x00\x1f\xf3\xff" .
	"\x61\x00\x00\x00\x06\x62\x4b\x47\x44\x00\xff\x00\xff\x00\xff\xa0" .
	"\xbd\xa7\x93\x00\x00\x00\x09\x70\x48\x59\x73\x00\x00\x0b\x13\x00" .
	"\x00\x0b\x13\x01\x00\x9a\x9c\x18\x00\x00\x00\x07\x74\x49\x4d\x45" .
	"\x07\xd7\x0a\x08\x07\x1d\x34\x97\x7c\x38\x55\x00\x00\x00\x1d\x74" .
	"\x45\x58\x74\x43\x6f\x6d\x6d\x65\x6e\x74\x00\x43\x72\x65\x61\x74" .
	"\x65\x64\x20\x77\x69\x74\x68\x20\x54\x68\x65\x20\x47\x49\x4d\x50" .
	"\xef\x64\x25\x6e\x00\x00\x00\xae\x49\x44\x41\x54\x38\xcb\xb5\x93" .
	"\x41\x0e\x82\x30\x10\x45\x5f\xc1\x23\x78\x0d\xef\xd0\x76\x2f\x7b" .
	"\x2e\xe0\xa1\x38\x80\xec\xcb\x5e\xba\x91\xbd\x89\x77\x70\x61\xbc" .
	"\x02\xc3\x86\x90\x2a\xc5\x20\xa9\x3f\x99\x74\x26\x9d\x76\xfe\xfc" .
	"\x76\x20\x15\x8c\xd6\x02\x2c\xda\xb8\x3f\x83\x0a\x7c\xa9\xa5\x5f" .
	"\x2c\x50\xaa\x2c\x76\x86\x6c\x96\xd8\xe5\x94\x5d\x0e\x40\x65\x2c" .
	"\x95\xb1\x21\x4b\x46\x46\xbf\x33\xa8\x8c\xe5\x58\x14\x34\xce\x01" .
	"\xd0\x7a\xaf\x00\x76\xb1\xc4\xf3\xfd\x35\xc5\xfe\x79\x9b\xfc\xc6" .
	"\x39\x5a\xef\xb7\x69\x10\xd1\x43\x45\x35\x08\xfb\xfe\xa6\x47\x32" .
	"\x06\xab\x34\x08\x2b\x9f\xda\x4b\x5a\x06\x7c\x5c\x20\x5c\xd5\x64" .
	"\x8f\xfd\x41\x6a\xe9\xc5\x68\xfd\xb6\x86\x7f\x21\xfd\x2b\xfc\x6d" .
	"\x98\xd6\x0c\xd6\x26\x0c\x52\x3d\x71\xd0\xf9\x33\x21\x71\x00\x00" .
	"\x00\x00\x49\x45\x4e\x44\xae\x42\x60\x82";

	$img['git_logo']['name'] = "git-logo.png";
    $img['git_logo']['bin'] = "\x89\x50\x4e\x47\x0d\x0a\x1a\x0a\x00\x00\x00\x0d\x49\x48\x44\x52" .
    "\x00\x00\x00\x48\x00\x00\x00\x1b\x04\x03\x00\x00\x00\x2d\xd9\xd4" .
    "\x2d\x00\x00\x00\x18\x50\x4c\x54\x45\xff\xff\xff\x60\x60\x5d\xb0" .
    "\xaf\xaa\x00\x80\x00\xce\xcd\xc7\xc0\x00\x00\xe8\xe8\xe6\xf7\xf7" .
    "\xf6\x95\x0c\xa7\x47\x00\x00\x00\x73\x49\x44\x41\x54\x28\xcf\x63" .
    "\x48\x67\x20\x04\x4a\x5c\x18\x0a\x08\x2a\x62\x53\x61\x20\x02\x08" .
    "\x0d\x69\x45\xac\xa1\xa1\x01\x30\x0c\x93\x60\x36\x26\x52\x91\xb1" .
    "\x01\x11\xd6\xe1\x55\x64\x6c\x6c\xcc\x6c\x6c\x0c\xa2\x0c\x70\x2a" .
    "\x62\x06\x2a\xc1\x62\x1d\xb3\x01\x02\x53\xa4\x08\xe8\x00\x03\x18" .
    "\x26\x56\x11\xd4\xe1\x20\x97\x1b\xe0\xb4\x0e\x35\x24\x71\x29\x82" .
    "\x99\x30\xb8\x93\x0a\x11\xb9\x45\x88\xc1\x8d\xa0\xa2\x44\x21\x06" .
    "\x27\x41\x82\x40\x85\xc1\x45\x89\x20\x70\x01\x00\xa4\x3d\x21\xc5" .
    "\x12\x1c\x9a\xfe\x00\x00\x00\x00\x49\x45\x4e\x44\xae\x42\x60\x82";
		
	if( !isset($img[$imgptr]['name']) ){ $img[$imgptr]['name'] = "$imgptr.png"; }
	$filesize = strlen($img[$imgptr]['bin']);
    header("Pragma: public"); // required
    header("Expires: 0");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
    header("Cache-Control: private",false); // required for certain browsers
    header("Content-Transfer-Encoding: binary");
    header("Content-Type: img/png");
    header("Content-Length: " . $filesize);
    header("Content-Disposition: attachment; filename=".$img[$imgptr]['name'].";" );
    header("Expires: +1d");
    echo $img[$imgptr]['bin'];
    die();
}

?>
