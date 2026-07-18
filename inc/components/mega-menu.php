<?php
/**
 * Header mega menu component.
 *
 * @package WM_Theme
 */

function wm_header_mega_get_image_html( $image, $alt = '' ) {
    if ( empty( $image ) ) {
        return '';
    }

    if ( is_array( $image ) && ! empty( $image['ID'] ) ) {
        return wp_get_attachment_image(
            absint( $image['ID'] ),
            'thumbnail',
            false,
            array(
                'alt'     => $alt,
                'loading' => 'lazy',
            )
        );
    }

    if ( is_numeric( $image ) ) {
        return wp_get_attachment_image(
            absint( $image ),
            'thumbnail',
            false,
            array(
                'alt'     => $alt,
                'loading' => 'lazy',
            )
        );
    }

    return '';
}

function wm_get_header_mega_menus() {
    $GLOBALS['wm_header_mega_debug_messages'] = array();

    if ( ! function_exists( 'get_field' ) ) {
        return array();
    }

    $debug = defined( 'WP_DEBUG' ) && WP_DEBUG;
    if ( ! $debug ) {
        $cached = get_transient( 'wm_header_mega_menus' );
        if ( false !== $cached ) {
            return $cached;
        }
    }

    $output = wm_build_header_mega_menus();

    if ( ! $debug ) {
        set_transient( 'wm_header_mega_menus', $output, DAY_IN_SECONDS );
    }

    return $output;
}

function wm_clear_header_mega_menus_cache() {
    delete_transient( 'wm_header_mega_menus' );
}
add_action( 'save_post_wm_mega_menu', 'wm_clear_header_mega_menus_cache' );
add_action( 'acf/save_post', 'wm_clear_header_mega_menus_cache', 20 );

function wm_build_header_mega_menus() {
    $posts = get_posts(
        array(
            'post_type'   => 'wm_mega_menu',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby'     => 'menu_order title',
            'order'       => 'ASC',
        )
    );

    $output = array();

    foreach ( $posts as $post ) {
        $key = (string) $post->ID;
        $fields = get_fields( $post->ID );
        if ( ! is_array( $fields ) ) {
            $fields = array();
        }

        $columns = array();
        foreach ( (array) ( $fields['mega_columns'] ?? array() ) as $column ) {
            if ( empty( $column['column_enabled'] ) ) {
                continue;
            }

            $links = array();
            foreach ( (array) ( $column['column_links'] ?? array() ) as $link ) {
                if ( empty( $link['link_enabled'] ) || empty( $link['link_label'] ) || empty( $link['link_url'] ) ) {
                    continue;
                }

                $links[] = $link;
            }

            $has_column_title = ! empty( $column['column_title'] );
            if ( empty( $links ) && ! $has_column_title ) {
                continue;
            }

            $column['column_links'] = $links;
            $columns[] = $column;
        }

        $menu = array(
            'mega_trigger_key'          => $key,
            'mega_title'                => $post->post_title,
            'mega_subtitle'             => $fields['mega_subtitle'] ?? '',
            'mega_columns'              => $columns,
            'mega_feature_card_enabled' => $fields['mega_feature_card_enabled'] ?? '',
            'mega_feature_title'        => $fields['mega_feature_title'] ?? '',
            'mega_feature_text'         => $fields['mega_feature_text'] ?? '',
            'mega_feature_image'        => $fields['mega_feature_image'] ?? '',
            'mega_feature_url'          => $fields['mega_feature_url'] ?? '',
            'mega_feature_button_text'  => $fields['mega_feature_button_text'] ?? '',
        );

        $has_feature = ! empty( $menu['mega_feature_card_enabled'] ) && ( ! empty( $menu['mega_feature_title'] ) || ! empty( $menu['mega_feature_text'] ) || ! empty( $menu['mega_feature_image'] ) );
        $has_intro = ! empty( $menu['mega_title'] ) || ! empty( $menu['mega_subtitle'] );
        if ( empty( $columns ) && ! $has_feature && ! $has_intro ) {
            if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
                $GLOBALS['wm_header_mega_debug_messages'][] = 'skipped post ' . $key . ': no title, active column, active link, or feature';
            }
            continue;
        }

        $menu['mega_columns'] = $columns;
        $menu['has_feature'] = $has_feature;
        $menu['has_intro'] = $has_intro;
        $output[] = $menu;
    }

    return $output;
}

function wm_get_nav_item_mega_menu_post_id( $item ) {
    if ( ! function_exists( 'get_field' ) ) {
        return 0;
    }

    $mega_post_id = get_field( 'wm_mega_menu_post', $item->ID );
    if ( empty( $mega_post_id ) || 'publish' !== get_post_status( $mega_post_id ) ) {
        return 0;
    }

    return (int) $mega_post_id;
}

function wm_mega_menu_nav_link_attributes( $atts, $item, $args, $depth ) {
    $mega_post_id = wm_get_nav_item_mega_menu_post_id( $item );
    if ( ! $mega_post_id ) {
        return $atts;
    }

    $atts['data-mega-key'] = (string) $mega_post_id;
    $atts['aria-haspopup'] = 'true';
    $atts['aria-expanded'] = 'false';

    return $atts;
}
add_filter( 'nav_menu_link_attributes', 'wm_mega_menu_nav_link_attributes', 10, 4 );

function wm_mega_menu_nav_css_class( $classes, $item ) {
    if ( ! wm_get_nav_item_mega_menu_post_id( $item ) ) {
        return $classes;
    }

    $classes[] = 'wm-mega-trigger';

    return $classes;
}
add_filter( 'nav_menu_css_class', 'wm_mega_menu_nav_css_class', 10, 2 );

function wm_header_mega_debug_comment( $message ) {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG && is_string( $message ) && '' !== $message ) {
        echo "\n<!-- WM Mega Menu: " . esc_html( $message ) . " -->\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
    }
}

function wm_render_header_mega_feature( $menu ) {
    if ( empty( $menu['has_feature'] ) ) {
        return;
    }

    $title = ! empty( $menu['mega_feature_title'] ) ? $menu['mega_feature_title'] : '';
    $text = ! empty( $menu['mega_feature_text'] ) ? $menu['mega_feature_text'] : '';
    $url = ! empty( $menu['mega_feature_url'] ) ? $menu['mega_feature_url'] : '';
    $button = ! empty( $menu['mega_feature_button_text'] ) ? $menu['mega_feature_button_text'] : __( 'مشاهده', 'eshobe-ecommerce' );
    $image = wm_header_mega_get_image_html( $menu['mega_feature_image'] ?? '', $title );

    ?>
    <aside class="wm-mega-menu__feature">
        <?php if ( $image ) : ?>
            <div class="wm-mega-menu__feature-image"><?php echo $image; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
        <?php endif; ?>

        <?php if ( $title ) : ?>
            <h3 class="wm-mega-menu__feature-title"><?php echo esc_html( $title ); ?></h3>
        <?php endif; ?>

        <?php if ( $text ) : ?>
            <p class="wm-mega-menu__feature-text"><?php echo esc_html( $text ); ?></p>
        <?php endif; ?>

        <?php if ( $url ) : ?>
            <a class="wm-mega-menu__feature-button" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $button ); ?></a>
        <?php endif; ?>
    </aside>
    <?php
}

function wm_render_header_mega_menus() {
    $menus = wm_get_header_mega_menus();
    $debug_messages = ! empty( $GLOBALS['wm_header_mega_debug_messages'] ) && is_array( $GLOBALS['wm_header_mega_debug_messages'] ) ? $GLOBALS['wm_header_mega_debug_messages'] : array();

    foreach ( $debug_messages as $message ) {
        wm_header_mega_debug_comment( $message );
    }

    if ( empty( $menus ) ) {
        wm_header_mega_debug_comment( 'no configured menus rendered' );
        return;
    }

    ?>
    <div class="wm-mega-menu" aria-label="<?php echo esc_attr__( 'Mega Menu', 'eshobe-ecommerce' ); ?>">
        <?php foreach ( $menus as $menu ) : ?>
            <?php
            $columns = (array) ( $menu['mega_columns'] ?? array() );
            $has_feature = ! empty( $menu['has_feature'] );
            wm_header_mega_debug_comment( 'key ' . $menu['mega_trigger_key'] . ' rendered' );
            ?>
            <div class="wm-mega-menu__panel" data-mega-key="<?php echo esc_attr( $menu['mega_trigger_key'] ); ?>" aria-hidden="true">
                <div class="wm-mega-menu__inner <?php echo $has_feature ? '' : 'wm-mega-menu__inner--no-feature'; ?>">
                    <div class="wm-mega-menu__main">
                        <?php if ( ! empty( $menu['mega_title'] ) || ! empty( $menu['mega_subtitle'] ) ) : ?>
                            <div class="wm-mega-menu__intro">
                                <?php if ( ! empty( $menu['mega_title'] ) ) : ?>
                                    <h2 class="wm-mega-menu__title"><?php echo esc_html( $menu['mega_title'] ); ?></h2>
                                <?php endif; ?>
                                <?php if ( ! empty( $menu['mega_subtitle'] ) ) : ?>
                                    <p class="wm-mega-menu__subtitle"><?php echo esc_html( $menu['mega_subtitle'] ); ?></p>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ( $columns ) : ?>
                            <div class="wm-mega-menu__columns">
                                <?php foreach ( $columns as $column ) : ?>
                                    <section class="wm-mega-menu__column">
                                        <?php if ( ! empty( $column['column_title'] ) ) : ?>
                                            <h3 class="wm-mega-menu__column-title"><?php echo esc_html( $column['column_title'] ); ?></h3>
                                        <?php endif; ?>
                                        <?php if ( ! empty( $column['column_links'] ) ) : ?>
                                            <ul class="wm-mega-menu__links">
                                                <?php foreach ( (array) $column['column_links'] as $link ) : ?>
                                                    <li>
                                                        <a class="wm-mega-menu__link" href="<?php echo esc_url( $link['link_url'] ); ?>">
                                                            <?php $icon = wm_header_mega_get_image_html( $link['link_icon'] ?? '', $link['link_label'] ); ?>
                                                            <?php if ( $icon ) : ?>
                                                                <span class="wm-mega-menu__link-icon"><?php echo $icon; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
                                                            <?php endif; ?>
                                                            <span class="wm-mega-menu__link-content">
                                                                <span class="wm-mega-menu__link-title">
                                                                    <?php echo esc_html( $link['link_label'] ); ?>
                                                                    <?php if ( ! empty( $link['link_badge'] ) ) : ?>
                                                                        <span class="wm-mega-menu__badge"><?php echo esc_html( $link['link_badge'] ); ?></span>
                                                                    <?php endif; ?>
                                                                </span>
                                                                <?php if ( ! empty( $link['link_description'] ) ) : ?>
                                                                    <span class="wm-mega-menu__link-description"><?php echo esc_html( $link['link_description'] ); ?></span>
                                                                <?php endif; ?>
                                                            </span>
                                                        </a>
                                                    </li>
                                                <?php endforeach; ?>
                                            </ul>
                                        <?php endif; ?>
                                    </section>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <?php wm_render_header_mega_feature( $menu ); ?>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}
