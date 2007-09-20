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
// | Author: Peeter Vois                                  |
// +------------------------------------------------------------------------+ 

//var_dump(gd_info());
//header("Content-Type: text/plain");
//$repo_directory = "/home/peeter/public_html/git/";
//$cache_directory = $repo_directory.".cache/";
//$rv=putenv( "PATH=/home/peeter/local/bin:/usr/local/bin:/usr/bin:/bin:/usr/bin/X11" );
//create_images( "git-git.git" );

function create_images( $repo ){
	global $repo_directory, $cache_directory;
	
    $order=array(); // the commit sha-s
    $coord=array(); // x coordinate of the item, y is the index

    $dirname=$cache_directory.$repo;
    if( ! is_dir($dirname) ){
        if( ! mkdir($dirname) ){
            echo "Error by making directory $dirname\n";
            die();
        }
    }


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
        $cmd .= "--max-count=100 --skip=" .$nr ." ";
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
            // tking the data descriptor
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
        		$order[$nr] = $commit;
        		// figure out the position of this node
        		if( in_array($commit,$pin,true) ){
        		    $coord[$nr] = array_search( $commit,$pin,true ); // take reserved coordinate
        		    $pin[$coord[$nr]] = "."; // free the reserved coordinate
        		}else{
        		    if( ! in_array( ".", $pin, true ) ){ // make empty coord plce
        		        $pin[] = ".";
        		    }
        		    $coord[$nr] = array_search( ".", $pin, true ); // take the first unused coordinate
        		    // do not allocate this place in array as this is already freed place
        		}
        		//reserve place for parents
        		foreach( $parents as $p ){ 
        		    if( in_array( $p, $pin, true ) ) continue; // the parent alredy has place
        		    if( in_array( ".", $pin, true ) ){ // take first empty place from array
        		        $x = array_search( ".", $pin, true );
        		        $pin[$x] = $p;
        		    }else{ // allcate new place into array
        		        $pin[] = $p;
        		    }
        		}
        		//manage graph drawing sections
        		$cross[$nr] = array(); // array of participating lines for this slice
        		foreach( $parents as $p ){
            		$crossf[] = $coord[$nr];
            		$crossf[] = $nr;
            		$crossf[] = array_search( $p, $pin, true );
            		$crossf[] = $p; // store unknown y as parent sha for later replacement
            		$countf++; // increase the unknown y counter
            	}
        		$cross[$nr] = $crossf; // the floating section
        		$count[$nr] = $countf; // the floating unknown counter
            	///echo $nr ." ". $countf ." | ". $commit . " | " .implode(" ", $parents) ."\n";
        		//fill in the old pieces
        		for( $i=$nr; $i>=0; $i-- ){
        		    $y=-1;
        		    if( ! is_array( $cross[$i] ) ) break;
        		    while( in_array( $commit, $cross[$i], true ) == true ){
        		        $y = array_search( $commit, $cross[$i], true );
        		        $cross[$i][$y] = $nr;
        		        $count[$i]--;
        		    }
    		        if( $count[$i] <= 0 ){
    		            draw_slice( $dirname, count($pin), $coord[$i], $i, $cross[$i] ); // draw the slice as all the info is available
    		            unset( $count[$i], $cross[$i] ); // free this as not needed anymore
    		        }
        		    if( $y < 0 ) break; // no more to fill in
        		}
        		// remove obsolete lines, the known lines are not carried around
        		$crossf = $cross[$nr];
        		$countf = $count[$nr];
        		$i = 0;
        		while( $i < count( $crossf ) ){ 
            		if( is_numeric( $crossf[$i+3] ) && ! is_string( $crossf[$i+3] ) )
            		{ // this is known line and it will not intersect with coming slices
            		    array_splice( $crossf, $i, 4 );
            		    continue;
            		}
            		$i = $i + 4;
            	}
            	//echo $nr ." ". $count[$nr] ." | ". $commit . " | " .implode(" ", $cross[$nr]) ."\n";
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
function draw_slice( $dirname, $columns, $x, $y, $cross )
{
    $w = 11; $wo = 5;
    $h = 15; $ho = 7;
    $r = 8;
    
    $im = imagecreate( $w * $columns, $h );
    $cbg = imagecolorallocate( $im, 255, 255, 255 );
    $ctr = imagecolortransparent( $im, $cbg );
    $cmg = imagecolorallocate( $im, 0, 0, 200 );
    $cbl = imagecolorallocate( $im, 0, 0, 0 );

    for( $i=0; $i<count($cross); $i=$i+4 ){
        $x1 = $cross[$i+0] * $w + $wo;
        $y1 = ($cross[$i+1]-$y) * $h + $ho;
        $x2 = $cross[$i+2] * $w + $wo;
        $y2 = ($cross[$i+3]-$y) * $h + $ho;
        imageline( $im, $x1, $y1, $x2, $y2, $cmg );
    }
    imagefilledellipse( $im, $x * $w + $wo, $ho, $r, $r, $cbl );
    $filename = $dirname."/tree-".$y.".png";
    imagepng( $im, $filename );
    //echo "$filename\n";
}


?>
