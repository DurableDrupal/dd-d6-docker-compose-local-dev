#!/usr/local/bin/drush
# place drush PATH on shabang!
##!/usr/bin/drush

/**
 * Execute from Drupal 6 document root:
 *
 * .scripts/dd-list-content-types-and-item-count.php > scripts/data/inv-content-types-with-item-count.txt
 */

$the_content_types = node_get_types();
// print_r($the_content_types);
foreach($the_content_types as $the_content_type) {
  $node_type = $the_content_type->type;
  $node_name = $the_content_type->name;
  $node_description = $the_content_type->description;
  $num = 0;
  $num_pub = 0;
  $result = db_query('SELECT nid, title, status FROM node WHERE type = "%s" ', $node_type);
  while ($obj = db_fetch_object($result)) {
// print_r($obj);
    $num++;
    if ($obj->status > 0) $num_pub++;
  }
  print($node_type . ' (' . $node_name . '): ' .  $num_pub . ' of ' . $num . " published items\n");
  print($node_description . "\n\n");
}

