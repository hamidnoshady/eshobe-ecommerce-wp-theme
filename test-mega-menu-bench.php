<?php
// Mocking WP functions
function get_field($field, $id) {
    global $get_field_calls;
    $get_field_calls++;
    return $id * 10;
}
function get_post_status($id) {
    global $get_post_status_calls;
    $get_post_status_calls++;
    return 'publish';
}
function add_action() {}
function add_filter() {}
function __() {}
$get_field_calls = 0;
$get_post_status_calls = 0;

require_once 'inc/components/mega-menu.php';

$items = array();
for ($i=1; $i<=50; $i++) {
    $item = new stdClass();
    $item->ID = $i;
    $items[] = $item;
}

$start = microtime(true);
for ($j=0; $j<2; $j++) { // called twice per item typically
    foreach ($items as $item) {
        wm_get_nav_item_mega_menu_post_id($item);
    }
}
$end = microtime(true);

echo "Optimized\n";
echo "get_field_calls: $get_field_calls\n";
echo "get_post_status_calls: $get_post_status_calls\n";
echo "Time: " . ($end - $start) . "\n";
