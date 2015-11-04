<?php

// Include our libraries
include_once "library/mapclass.php";

// Make an example array of data
// Syntax:
// [title], [Lat], [Lon], [Info Content], [type], [radius/grid size], [z-index]
// Info Content can contain HTML and will pop up in the bubble on click
$data = array(
  array('Data Point #1', -32.0, 22.0,   'Point Location #1',    'point',    0,      1),
  array('Data Point #2', -32.25, 22.25, 'Circle #1',            'circle',   10000,  2),  
  array('Data Point #3', -32.0, 22.25,  'Grid Cell #1',         'grid',     0.25,   3),
  array('Data Point #4', -32.25, 22.0,  'Grid Cell #2',         'grid',     0.25,   4)
);

// Make a new Map object
$mymap = new Map;

// Set the center of the map
$mymap->center = array(-32.197695,22.239075);

// Set the zoom level of the map
$mymap->zoom = 9;

// Set the name of the map
$mymap->mapname = "james";

// Set the size of the map in HTML, eg 100%, 100px
$mymap->size = array("100%","550px");

// Custom marker image
//$mymap->marker = "images/Tree-icon.png";

// Use a JSON file instead of passing a locations array
// Probably better to do this and cache the JSON file when possible
//$mymap->json = TRUE;
//$mymap->jsonfile = $cachefile;

// Give the map our array of locations
$mymap->locations = $data;

// Use MarkerClusterer
// $mymap->markerclusterer = TRUE;
// $mymap->clusterer_src = "library/markerclusterer_compiled.js";

// Set the maptype if preferred (default is Satellite)
//$mymap->maptype = "TERRAIN";

// Show the map
$mymap->show();

?>