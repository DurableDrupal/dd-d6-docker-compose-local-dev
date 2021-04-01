#!/usr/local/bin/drush

/**
 * dd-get-content-items-by-content-type.php
 *   list a parsable array of content items by Drupal 6 content type
 *
 * Sample execution from document root
 *
 * drush scripts/dd-get-content-items-by-content-type.php story
 */
$args = drush_get_arguments();
$content_type = $args[2];
$content_items = array();
/* d6 */
$result = db_query('SELECT nid FROM node WHERE type = "%s"', $content_type);
while ($obj = db_fetch_object($result)) {
  $node = node_load($obj->nid);
  array_push($content_items, $node);
}
// file_put_contents('scripts/data/inv-atype.txt',  json_encode($content_items));
print_r(json_encode($content_items), false);
  
/* d7 */
/*
$result = db_query("SELECT nid FROM node WHERE type = :contentType ", array(':contentType'=>$content_type));
foreach ($result as $obj) {
  $node = node_load($obj->nid);
  array_push($content_items, $node);
}
print_r(json_encode($content_items), false);
*/

