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

$repo_directory = "/home/peeter/public_html/git/git-git.git";

$cmd="GIT_DIR=$repo_directory git-rev-list --all --full-history --date-order ";
$cmd .= "--pretty=format:\"";
$cmd .= "parents %P%n";
$cmd .= "tree %T%n";
$cmd .= "author %an%n";
$cmd .= "subject %s%n";
$cmd .= "endrecord%n\"";
$out = array();

exec( $cmd, &$out );

//echo implode( "\n", $out );
class node
{
	var $commit = ""; // the sha of this node
	var $parents = array(); // the nodes this node is merged from
	var $merges = array(); // the nodes that are merging from this node
	var $subject = "";
	var $x = -1; // the x coordinate of the node
	var $y = -1; // the y coordinate of the node
	var $length=0; // the verticl tree length
	var $longest=""; // the sha of longest path
};

// reading the commit tree
$entries=array();
$descriptor="";
$commit="";
$subject="";
$parents=array();
$order=array(); // keeps record of output order
$nr=0;
foreach( $out as $line )
{
    // tking the data descriptor
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
	case "tree":
		break;
	case "author":
		break;
	case "subject":
		$subject = implode( " ", $d );
		break;
	case "endrecord":
		$entries[$commit] = new node;
		$entries[$commit]->parents=$parents;
		$entries[$commit]->merges=array();
		$entries[$commit]->commit=$commit;
		$entries[$commit]->subject=$subject;
		$order[$nr] = $commit;
		$entries[$commit]->y = $nr;
		$entries[$commit]->x = -1;
		$nr = $nr +1;
		//echo implode(" ",$entries[$commit]->parents) ."\n"; die();
		$descriptor="";
		$commit="";
		$parents=array();
        $subject="";
		break;
	}
}

//echo "number of items $nr\n";
$rows = $nr;

// filling in childs
foreach( $entries as $item )
{
	foreach( $item->parents as $commit )
	{
		if( $entries[$commit]->x != -1 ) continue;
		$entries[$commit]->merges[] = $item->commit;
		//if( $entries[$commit]->commit != $commit ) echo "khk " . $item->y ." ". $commit . "\n";
	}
}

// child checks
/*foreach( $entries as $item )
{
	foreach( $item->merges as $m ){
		if( ! in_array( $item->commit, $entries[$m]->parents ) )
			echo "tree error " . $item->y ." u \n";
	}
}*/


// building up the tree
//$nr = count( $entries );

$x = 0;
//$nr number of elements

while( place_elems( 0, $x ) > 0 ){ $x++; } // fill in all the elements

//echo "number of columns " . $x ."\n";
$columns=$x;

//place nodes into tree
function place_elems( $ns, $x ){
	global $entries, $order, $nr;
	//echo "place_elems " .$ns." ".$x."\n";
	$tk = 0;
	for( $n=$ns; $n<$nr; $n++ ){
		$p = $order[$n];
		$tk = longest( $p, $x ); // load the longest path into the elements
		if( $tk > 0 ){  // the vertical has placed
			// look, how long the  vertical became
			//echo "tk= ".$tk." ";
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
	$a=$entries[$p];
	if( $a->x != -1 ) return 0; // this is already in tree
	$a->length=0; // count nodes below it
	if( count_same_merges( $a->merges, $x ) > 1 ) return 0; // two or more merges into same stream
	$a->x = $x; // place this node into tree
	if( count( $a->parents ) == 0 ) return 1; // tree ends here
	foreach( $a->parents as $t ){
		// enter into the tree recursively
		$lo = longest( $t, $x );
		if( $lo > 0 ){
			$a->length = $lo;
			$a->longest = $t;
			break; // take only one parent
		}
	}
	//echo "longest ".$a->y." ".$x." ".$a->length."\n";
	return $a->length + 1 ; // count this node into in retval
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
$h = 21; $ho = 10;
$r = 8;

// draw the graph slices
for( $y=0; $y<$rows; $y++ ){
//for( $y=0; $y<3; $y++ ){
    $im = imagecreate( $w * $columns, $h );
    $cbg = imagecolorallocate( $im, 255, 255, 255 );
    $ctr = imagecolortransparent( $im, $cbg );
    $cmg = imagecolorallocate( $im, 0, 0, 150 );
    $cbl = imagecolorallocate( $im, 0, 0, 0 );
    foreach( $grids[$y] as $g ){
        $e = $entries[$order[$g]];
        foreach( $e->parents as $pm ){
            $p = $entries[$pm];
            imageline( $im, $e->x * $w + $wo, ($e->y-$y) * $h + $ho, $p->x * $w + $wo, ($p->y - $y) * $h + $ho, $cmg );
        }
    }
    $e = $entries[$order[$y]];
    imagefilledellipse( $im, $e->x * $w + $wo, $ho, $r, $r, $cbl );
    $filename = "tree-".$y.".png";
    imagepng( $im, $filename );
}

die();

?>
