<?php
/*
 * render.php (c) Shish 2006
 *
 * take the background from background.php, the query from query.php,
 * draw all the villages and the key
 */

require_once "libaima.php";
require_once "util.php";
require_once "options.php";
require_once "imageinit.php";     # rendering requires an image
require_once "rainbow.php";       # a set of colours to allocate from
require_once "localise.php";      # words to use
require_once "loadentities.php";  # and a list of entities to render

$races = Array($words["roman"], $words["teuton"], $words["gaul"]);
$kcount = 0;
$kcount1 = 0; $kcount2 = 0; $kcount3 = 0; $kcount4 = 0;
$minx = 512; $miny = 512; $maxx = -512; $maxy = -512;
$kx = 530;


/*
 * figure out where to zoom, if auto
 */
if($azoom) {
	foreach($entities as $entity) {
		foreach($entity['villages'] as $village) {
			$x = $village['x'];
			$y = $village['y'];

			if($x < $minx) {$minx = $x;}
			if($y < $miny) {$miny = $y;}
			if($x > $maxx) {$maxx = $x;}
			if($y > $maxy) {$maxy = $y;}
		}
	}
	$zx = ($minx+$maxx)/2;
	$zy = ($miny+$maxy)/2;
	
	$bigdiff = (($maxx-$minx) > ($maxy-$miny)) ? ($maxx-$minx) : ($maxy-$miny);
	$zz = (450/$bigdiff);
}


/*
 * count keys in each area
 */
if($layout == "spread") {
	foreach($entities as $entity_name => $entity) {
		$x = $cx+($entity['villages'][0]['x']-$zx)*$zz;
		$y = $cy-($entity['villages'][0]['y']-$zy)*$zz;

		if(($x < $cx) && ($y >= $cy)) {$pbl++;}
		else if(($x >= $cx) && ($y >= $cy)) {$pbr++;}
		else if(($x < $cx) && ($y < $cy)) {$ptl++;}
		else if(($x >= $cx) && ($y < $cy)) {$ptr++;}
	}
}


/*
 * figure out where each key entry should go in the key
 */
foreach($entities as $entity_name => $entity) {
	$x = $cx+($entity['villages'][0]['x']-$zx)*$zz;
	$y = $cy-($entity['villages'][0]['y']-$zy)*$zz;

	if($layout == "spread") {
		if(($x < $cx) && ($y >= $cy)) {
			$entity['y'] = 500-(($pbl-1)*15)+($kcount3++*15);
		}
		else if(($x >= $cx) && ($y >= $cy)) {
			$entity['y'] = 500-(($pbr-1)*15)+($kcount4++*15);
		}
		else if(($x < $cx) && ($y < $cy)) {
			$entity['y'] = 40+($kcount1++*15);
		}
		else if(($x >= $cx) && ($y < $cy)) {
			$entity['y'] = 40+($kcount2++*15);
		}

		if($x < $cx) {
			$entity['dx'] = 114;
			$entity['x'] = 10;
		}
		else {
			$entity['dx'] = 654;
			$entity['x'] = 662;
		}

		$entity['dy'] = $entity['y'];
	}
	else {
		if($kcount == 31) {$kx += 130;$kcount = 0;}
		$entity['dx'] = $kx;
		$entity['dy'] = 40+($kcount*15);
		$entity['x'] = $kx+8;
		$entity['y'] = 40+($kcount*15);
		$kcount++;
	}

	$entities[$entity_name] = $entity;
}


/*
 * Draw grid
 */
$inc = ($zz > 10) ? 1 : 10;
for($v=-250; $v<=250; $v+=$inc) {
	    if($v % 1000 == 0) $col = $grey;
	elseif($v % 100 == 0) $col = $mgrey;
	elseif($v % 10 == 0) $col = $lgrey;
	elseif($v % 1 == 0) $col = $wgrey;

	if(in($cx+($v-$zx)*$zz, $cx-250, $cx+250)) $imageline($im, $cx+($v-$zx)*$zz, $cy+(-250+$zy)*$zz, $cx+($v-$zx)*$zz, $cy+(250+$zy)*$zz, $col);
	if(in($cy+($v+$zy)*$zz, $cy-250, $cy+250)) $imageline($im, $cx-(-250+$zx)*$zz, $cy+($v+$zy)*$zz, $cx-(250+$zx)*$zz, $cy+($v+$zy)*$zz, $col);
} 


/*
 * Draw grid axes
 */
$inc = ($zz > 10) ? 10 : 50;

$x = bound($cx-$zx*$zz, $cx-250, $cx+225);
$y = bound($cy+$zy*$zz, $cy-250, $cy+240);

for($v=-250; $v<=250; $v+=$inc) {
	$imagestring($im, 3, $x+2, $cy-($v-$zy)*$zz+1, $v, $mgrey);
	$imagestring($im, 3, $cx+($v-$zx)*$zz+2, $y+1, $v, $mgrey);
}


/*
 * Draw the rectangles
 */
$imagerectangle($im, $cx-256, $cy-256, $cx+255, $cy+255, $black);
if($layout == "spread") {
	$imagefilledrectangle($im, 0, 0, 124, 511, $white);
	$imagefilledrectangle($im, 643, 0, 767, 511, $white);
	$imagerectangle($im, 0, 0, 124, 511, $black);
	$imagerectangle($im, 643, 0, 767, 511, $black);
	$imagestring($im, 3, 704-strlen($caption)*3.45, 10, $caption, $black);
	$imagestring($im, 3, 64-strlen($caption)*3.45, 10, $caption, $black);
}
else {
	$imagefilledrectangle($im, 515, 0, 767, 511, $white);
	$imagerectangle($im, 515, 0, 767, 511, $black);
	$imagestring($im, 3, 640-strlen($caption)*3.45, 10, $caption, $black);
}


/*
 * Draw each entity, it's villages, and it's key entry
 */
$cals = Array();

foreach($entities as $entity_name => $entity) {
	if(is_int($entity_name)) {
		$entity_name = $races[$entity["race_id"]-1];
	}

	$count = $entity['count'];

	if($colby == "alliance") {
		if(!is_null($cals[$entity["guild"]])) $colour = $cals[$entity["guild"]];
		else $colour = $cals[$entity["guild"]] = $ct[($ca++)%count($ct)];
	}
	else if($colby == "race") {
		if(!is_null($cals[$entity["race_id"]])) $colour = $cals[$entity["race_id"]];
		else $colour = $cals[$entity["race_id"]] = $ct[($ca++)%count($ct)];
	}
	else {
		$colour = $ct[($ca++)%count($ct)];
	}

	foreach($entity['villages'] as $village) {
		$vx = $cx+($village['x']-$zx)*$zz;
		$vy = $cy-($village['y']-$zy)*$zz;
		if($lines) $imageline($im, $entity['dx'], $entity['dy'], $vx, $vy, $colour);
		$cohash = (256-$village['y'])*512 + ($village['x']+257);
		aimacustom($im, "<a xlink:href='http://$server/karte.php?z=$cohash'>");
		dot($im, $vx, $vy, $colour, (log($village['population']+1)+1)*$dotsize);
		aimacustom($im, "</a>");
	}
	
	aimacustom($im, "<a xlink:href='http://$server/".$key['link']."'>");
	# yes, we want this to only apply to aima, not gd
	aimafilledrectangle($im, $key['x']+7, $key['y']-7, $key['x']+100, $key['y']+7, $white);
	dot($im, $entity['dx'], $entity['dy'], $colour);
	$imagestring($im, 3, $entity['x'], $entity['y']-6, ($count ? "$entity_name (".($count+1).")" : $entity_name), $colour);
	aimacustom($im, "</a>");
}


/*
 * navigator widget for SVG
 */
if($_GET["format"] == "svg") {
	$base_query = preg_replace("/&amp;zoom=-?\d+,-?\d+,-?\d+/", "", str_replace("&", "&amp;", $_SERVER["QUERY_STRING"]));
	
	aimacustom($im, "<a xlink:href='map.php?$base_query&amp;zoom=".($zx-100/$zz).",$zy,$zz'>");
	dot($im, $cx+230-9, $cy+230+0, $ct[($ca++)%count($ct)]);
	aimacustom($im, "</a>");
	
	aimacustom($im, "<a xlink:href='map.php?$base_query&amp;zoom=$zx,".($zy+100/$zz).",$zz'>");
	dot($im, $cx+230+0, $cy+230-9, $ct[($ca++)%count($ct)]);
	aimacustom($im, "</a>");
	
	aimacustom($im, "<a xlink:href='map.php?$base_query&amp;zoom=$zx,".($zy-100/$zz).",$zz'>");
	dot($im, $cx+230+0, $cy+230+9, $ct[($ca++)%count($ct)]);
	aimacustom($im, "</a>");
	
	aimacustom($im, "<a xlink:href='map.php?$base_query&amp;zoom=".($zx+100/$zz).",$zy,$zz'>");
	dot($im, $cx+230+9, $cy+230-0, $ct[($ca++)%count($ct)]);
	aimacustom($im, "</a>");

	
	aimacustom($im, "<a xlink:href='map.php?$base_query&amp;zoom=$zx,$zy,".($zz-2)."'>");
	dot($im, $cx+230+15, $cy+245, $ct[($ca++)%count($ct)]);
	aimacustom($im, "</a>");
	
	aimacustom($im, "<a xlink:href='map.php?$base_query&amp;zoom=$zx,$zy,".($zz+2)."'>");
	dot($im, $cx+230-15, $cy+245, $ct[($ca++)%count($ct)]);
	aimacustom($im, "</a>");
}
?>