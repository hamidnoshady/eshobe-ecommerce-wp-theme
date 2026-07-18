import re

with open('inc/components/mega-menu.php', 'r') as f:
    content = f.read()

search = """function wm_get_nav_item_mega_menu_post_id( $item ) {
    if ( ! function_exists( 'get_field' ) ) {
        return 0;
    }

    $mega_post_id = get_field( 'wm_mega_menu_post', $item->ID );
    if ( empty( $mega_post_id ) || 'publish' !== get_post_status( $mega_post_id ) ) {
        return 0;
    }

    return (int) $mega_post_id;
}"""

replace = """function wm_get_nav_item_mega_menu_post_id( $item ) {
    if ( ! function_exists( 'get_field' ) || empty( $item->ID ) ) {
        return 0;
    }

    static $cache = array();

    if ( isset( $cache[ $item->ID ] ) ) {
        return $cache[ $item->ID ];
    }

    $mega_post_id = get_field( 'wm_mega_menu_post', $item->ID );
    if ( empty( $mega_post_id ) || 'publish' !== get_post_status( $mega_post_id ) ) {
        $cache[ $item->ID ] = 0;
        return 0;
    }

    $cache[ $item->ID ] = (int) $mega_post_id;
    return $cache[ $item->ID ];
}"""

if search in content:
    with open('inc/components/mega-menu.php', 'w') as f:
        f.write(content.replace(search, replace))
    print("Replaced successfully.")
else:
    print("Search string not found.")
