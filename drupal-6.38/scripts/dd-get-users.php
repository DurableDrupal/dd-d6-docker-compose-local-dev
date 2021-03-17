#!/usr/local/bin/drush

$the_users = array();
$result = db_query('SELECT uid FROM users');
while ($obj = db_fetch_object($result)) {
  $user = user_load($obj->uid);
  // print_r($user);
  array_push($the_users, $user);
}
// print_r($the_users);
// problematic
// file_put_contents('scripts/data/inv-users.txt',  '<?php return ' . var_export($the_users, true) . ';');
// use json instead, more useful too
file_put_contents('scripts/data/inv-users.txt',  json_encode($the_users));
