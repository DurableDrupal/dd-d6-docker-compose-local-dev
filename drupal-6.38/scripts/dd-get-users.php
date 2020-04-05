#!/usr/local/bin/drush

$result = db_query('SELECT uid FROM users');
while ($obj = db_fetch_object($result)) {
  $user = user_load($obj->uid);
  print_r($user);
}