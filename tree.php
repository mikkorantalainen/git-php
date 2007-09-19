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
//v=putenv( "PATH=/home/peeter/local/bin:/usr/local/bin:/usr/bin:/bin:/usr/bin/X11" );
//echo "$rv";
//create_images( "fiekassaraha.git" );

class node
{
	var $parents = array(); // the nodes this node is merged from
	var $merges = array(); // the nodes that are merging from this node
	var $x = -1; // the x coordinate of the node
	var $y = -1; // the y coordinate of the node
	var $length=0; // the verticl tree length
	var $longest=""; // the sha of longest path
};
global $entries, $order, $nr;
$entries=array();
 

// this function creates images into the cache directory and
// returns a array that countains the entries of type node
// $entries are indexed by the SHA1 key
function create_images( $repo ){
	global $entries, $order, $nr;
	global $repo_directory, $cache_directory;

    $dirname=$cache_directory.$repo;
    if( ! is_dir($dirname) ){
        if( ! mkdir($dirname) ){
            echo "Error by making directory $dirname\n";
            die();
        }
    }

    //echo "$repo\n";
    $entries=array();
    $order=array(); // keeps record of output order
    $nr=0;
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
        		$entries[$commit] = new node;
        		$entries[$commit]->parents=$parents;
        		$entries[$commit]->merges=array();
        		$order[$nr] = $commit;
        		$entries[$commit]->y = $nr;
        		$entries[$commit]->x = -1;
        		$nr = $nr +1;
        		unset($descriptor);
        		unset($commit);
        		unset($parents);
        		$parents=array();
        		break;
        	}
        }
        //echo count( $out ) ."\n";
    }while( count( $out ) > 0 );
    unset($out);
    //echo "number of items $nr\n";
    $rows = $nr;

    // filling in childs
    foreach( $entries as $item )
    {
    	foreach( $item->parents as $commit )
    	{
    		if( $entries[$commit]->x != -1 ) continue;
    		$entries[$commit]->merges[] = $order[$item->y];
    	}
    }

    //$nr number of elements
    $x = 0;
    while( place_elems( 0, $x ) > 0 ){ $x++; } // fill in all the elements
    $columns=$x;
    //die();

    // counting dependencies for graph
    // points to rows that do have lines in the slice
    $grids=array();
    foreach( $entries as $e ){
        foreach( $e->parents as $pm ){
            $p = $entries[$pm];
            for( $i=$p->y; $i>=$e->y; $i-- ){
                $grids[$i][] = $e->y;
            }
        }
    }


    // creating graph picture

    // columns, rows
    $w = 15; $wo = 7;
    $h = 19; $ho = 9;
    $r = 8;

    // draw the graph slices
    for( $y=0; $y<$rows; $y++ ){
    //for( $y=0; $y<3; $y++ ){
        $im = imagecreate( $w * $columns, $h );
        $cbg = imagecolorallocate( $im, 255, 255, 255 );
        $ctr = imagecolortransparent( $im, $cbg );
        $cmg = imagecolorallocate( $im, 0, 0, 200 );
        $cbl = imagecolorallocate( $im, 0, 0, 0 );
        if( ! is_array($grids[$y]) ) $grids[$y] = array( $grids[$y] );
        foreach( $grids[$y] as $g ){
            $e = $entries[$order[$g]];
            if( ! is_array($e->parents) ) $e->parents = array( $e->parents );
            foreach( $e->parents as $pm ){
                $p = $entries[$pm];
                imageline( $im, $e->x * $w + $wo, ($e->y-$y) * $h + $ho, $p->x * $w + $wo, ($p->y - $y) * $h + $ho, $cmg );
            }
        }
        $e = $entries[$order[$y]];
        imagefilledellipse( $im, $e->x * $w + $wo, $ho, $r, $r, $cbl );
        $filename = $dirname."/tree-".$y.".png";
        imagepng( $im, $filename );
        //echo "$filename\n";
    }
    
    //return $entries;
}


//place nodes into tree
function place_elems( $ns, $x ){
	global $entries, $order, $nr;
	//echo "place_elems " .$ns." ".$x."\n";
	$tk = 0;
	for( $n=$ns; $n<$nr; $n++ ){
		$p = $order[$n];
		$tk = longest( $p, $x ); // load the longest path into the elements
		//echo $entries[$p]->length;
		if( $tk > 0 ){  // the vertical has placed
			// look, how long the  vertical became
			//echo "tk= ".$tk."\n";
			do{ $a = $entries[$p]; $p = $a->longest; } while( $a->length > 0 );
			//echo $a->y."\n";
			$tk += place_elems( $a->y, $x ); // place another verticals
			return $tk;
		}
	}
	return $tk;
}


function longest( $p, $x ){
	global $entries;
	//echo $entries[$p]->x ." ";
	if( $entries[$p]->x != -1 ) return 0; // this is already in tree
	$entries[$p]->length=0; // count nodes below it
	if( count_same_merges( $entries[$p]->merges, $x ) > 1 ) return 0; // two or more merges into same stream
	$entries[$p]->x = $x; // place this node into tree
	if( count( $entries[$p]->parents ) == 0 ) return 1; // tree ends here
	foreach( $entries[$p]->parents as $t ){
		// enter into the tree recursively
		$lo = longest( $t, $x );
		if( $lo > 0 ){
			$entries[$p]->length = $lo;
			$entries[$p]->longest = $t;
			break; // take only one parent
		}
	}
	//echo "longest ".$entries[$p]->y." ".$x." ".$entries[$p]->length."\n";
	return $entries[$p]->length + 1 ; // count this node into in retval
}

// counts number of merges that are in column $x
function count_same_merges( $arp, $x ){
	global $entries;
	$c=0;
	foreach( $arp as $m ){
		if( $entries[$m]->x == $x )
			$c++;
	}
	return $c;
}



?>
