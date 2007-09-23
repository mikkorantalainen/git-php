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
// | Author: Peeter Vois                                                    |
// +------------------------------------------------------------------------+ 

//var_dump(gd_info());
//header("Content-Type: text/plain");
//$repo_directory = "/home/peeter/public_html/git/";
//$cache_directory = $repo_directory.".cache/";
//$rv=putenv( "PATH=/home/peeter/local/bin:/usr/local/bin:/usr/bin:/bin:/usr/bin/X11" );
//create_images( "git-git.git" );

function create_cache_directory( $repo ){
	global $repo_directory, $cache_directory;
	$dirname=$cache_directory.$repo;
	
    if( ! is_dir($dirname) ){
        if( ! mkdir($dirname) ){
            echo "Error by making directory $dirname\n";
            die();
        }
    }
    chmod( $dirname, 0777 );
    //chgrp( $dirname, intval(filegroup($repo_directory)) );
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
        $cmd="GIT_DIR=$repo_directory$repo git-rev-list --all --full-history --date-order ";
        $cmd .= "--max-count=1000 --skip=" .$nr ." ";
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
        $cmd="GIT_DIR=$repo_directory$repo git-rev-list --all --full-history --date-order ";
        $cmd .= "--max-count=1000 --skip=" .$nr ." ";
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


	for( $i=0; $i<$columns; $i++ ){
		if( $vin[$i] == $commit ){
			// small vertical
			imageline( $im, $i * $w + $wo, $ho, $i * $w + $wo, 0, $cmg );
			imageline( $im, $i * $w + $wo-1, $ho, $i * $w + $wo-1, 0, $cmg );
			//imageline( $im, $i * $w + $wo+1, $ho, $i * $w + $wo+1, 0, $cmg );
		}
		if( $pin[$i] != "." ){
			// we have a parent
			if( in_array($pin[$i],$parents,true) ){
				// the parent is our parent
				// draw the horisontal for it
				imageline( $im, $i * $w + $wo, $ho, $x * $w + $wo, $ho, $cmg );
				imageline( $im, $i * $w + $wo, $ho-1, $x * $w + $wo, $ho-1, $cmg );
				//imageline( $im, $i * $w + $wo, $ho+1, $x * $w + $wo, $ho+1, $cmg );
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

    imagefilledellipse( $im, $x * $w + $wo, $ho, $r, $r, $cbl );
    $filename = $dirname."/graph-".$commit.".png";
    imagepng( $im, $filename );
    chmod( $filename, 0777 );
    //chgrp( $filename, intval(filegroup($repo_directory)) );
    //echo "$filename\n";
}


?>
