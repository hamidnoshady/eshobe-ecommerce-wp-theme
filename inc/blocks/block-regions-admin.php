<?php
/**
 * Admin screen for turning on block-based management of the homepage and
 * the other block regions. Nothing here runs automatically on activation —
 * every action is an explicit, nonce-verified click from an administrator.
 *
 * @package WM_Theme
 */

function wm_blocks_admin_menu() {
    add_submenu_page(
        'eshobe-ecommerce',
        __( 'مدیریت بلوک‌ها', 'eshobe-ecommerce' ),
        __( 'مدیریت بلوک‌ها', 'eshobe-ecommerce' ),
        'edit_posts',
        'eshobe-ecommerce-blocks',
        'wm_render_blocks_admin_page'
    );

    // Also surface it under Appearance (نمایش), same place FSE/block themes put
    // "Editor" — so it's reachable even if something is hiding the custom
    // "تنظیمات قالب" menu, and it's a more natural location for it anyway.
    add_theme_page(
        __( 'مدیریت بلوک‌ها', 'eshobe-ecommerce' ),
        __( 'مدیریت بلوک‌ها', 'eshobe-ecommerce' ),
        'edit_posts',
        'eshobe-ecommerce-blocks',
        'wm_render_blocks_admin_page'
    );
}
add_action( 'admin_menu', 'wm_blocks_admin_menu', 20 );

/**
 * Builds block markup that reproduces the current, ACF-driven homepage
 * section order 1:1 as wm/home-section blocks, so turning on block
 * management doesn't change anything until the admin edits it.
 *
 * @return string
 */
function wm_home_block_seed_markup() {
    $blocks = array();

    foreach ( wm_get_home_sections_order() as $section_key ) {
        $blocks[] = sprintf( '<!-- wp:wm/home-section {"section":"%s"} /-->', esc_attr( $section_key ) );
    }

    return implode( "\n\n", $blocks );
}

function wm_blocks_admin_handle_setup_home() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_die( esc_html__( 'دسترسی غیرمجاز.', 'eshobe-ecommerce' ) );
    }
    check_admin_referer( 'wm_blocks_setup_home' );

    $front_page_id = ( 'page' === get_option( 'show_on_front' ) ) ? (int) get_option( 'page_on_front' ) : 0;
    $post          = $front_page_id ? get_post( $front_page_id ) : null;

    if ( ! $post ) {
        $front_page_id = wp_insert_post(
            array(
                'post_type'   => 'page',
                'post_status' => 'publish',
                'post_title'  => __( 'صفحه اصلی', 'eshobe-ecommerce' ),
            ),
            true
        );

        if ( is_wp_error( $front_page_id ) ) {
            wp_die( esc_html( $front_page_id->get_error_message() ) );
        }

        update_option( 'show_on_front', 'page' );
        update_option( 'page_on_front', $front_page_id );

        $post = get_post( $front_page_id );
    }

    if ( $post && ( empty( $post->post_content ) || ! has_blocks( $post->post_content ) ) ) {
        wp_update_post(
            array(
                'ID'           => $front_page_id,
                'post_content' => wm_home_block_seed_markup(),
            )
        );
    }

    wp_safe_redirect( admin_url( 'post.php?post=' . $front_page_id . '&action=edit' ) );
    exit;
}
add_action( 'admin_post_wm_blocks_setup_home', 'wm_blocks_admin_handle_setup_home' );

function wm_blocks_admin_handle_setup_region() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        wp_die( esc_html__( 'دسترسی غیرمجاز.', 'eshobe-ecommerce' ) );
    }
    check_admin_referer( 'wm_blocks_setup_region' );

    $region = isset( $_GET['region'] ) ? sanitize_key( wp_unslash( $_GET['region'] ) ) : '';
    $id     = wm_get_or_create_block_region_page( $region );

    if ( ! $id ) {
        wp_die( esc_html__( 'ناحیه نامعتبر است.', 'eshobe-ecommerce' ) );
    }

    wp_safe_redirect( admin_url( 'post.php?post=' . $id . '&action=edit' ) );
    exit;
}
add_action( 'admin_post_wm_blocks_setup_region', 'wm_blocks_admin_handle_setup_region' );

function wm_render_blocks_admin_page() {
    $front_page_id = ( 'page' === get_option( 'show_on_front' ) ) ? (int) get_option( 'page_on_front' ) : 0;
    $front_page    = $front_page_id ? get_post( $front_page_id ) : null;
    $home_ready    = $front_page && has_blocks( $front_page->post_content );
    ?>
    <div class="wrap">
        <h1><?php esc_html_e( 'مدیریت بلوک‌ها', 'eshobe-ecommerce' ); ?></h1>
        <p>
            <?php esc_html_e( 'با این ابزار می‌توانید ترتیب بخش‌های صفحه اصلی را با کشیدن‌وگذاشتن بلوک‌ها در ویرایشگر وردپرس تغییر دهید، کاروسل‌های محصول جدید بر اساس دسته/برند دلخواه اضافه کنید و کارت‌های فیلتر قیمت را کامل مدیریت کنید — بدون هیچ تغییری در ظاهر یا عملکرد فعلی سایت تا زمانی که خودتان ویرایش کنید.', 'eshobe-ecommerce' ); ?>
        </p>

        <h2><?php esc_html_e( 'صفحه اصلی', 'eshobe-ecommerce' ); ?></h2>
        <?php if ( $home_ready ) : ?>
            <p>
                <a class="button button-primary" href="<?php echo esc_url( admin_url( 'post.php?post=' . $front_page_id . '&action=edit' ) ); ?>">
                    <?php esc_html_e( 'ویرایش بلوک‌های صفحه اصلی', 'eshobe-ecommerce' ); ?>
                </a>
            </p>
        <?php else : ?>
            <p>
                <a class="button button-primary" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wm_blocks_setup_home' ), 'wm_blocks_setup_home' ) ); ?>">
                    <?php esc_html_e( 'فعال‌سازی مدیریت بلوکی صفحه اصلی', 'eshobe-ecommerce' ); ?>
                </a>
            </p>
            <p class="description"><?php esc_html_e( 'ترتیب فعلی بخش‌ها عیناً به بلوک تبدیل می‌شود؛ چیزی در سایت تغییر نمی‌کند تا خودتان چیزی را جابه‌جا یا ویرایش کنید.', 'eshobe-ecommerce' ); ?></p>
        <?php endif; ?>

        <h2><?php esc_html_e( 'نواحی دیگر', 'eshobe-ecommerce' ); ?></h2>
        <table class="widefat striped" style="max-width:720px">
            <tbody>
            <?php foreach ( wm_block_region_definitions() as $region => $definition ) : ?>
                <?php $page_id = wm_get_block_region_page_id( $region ); ?>
                <tr>
                    <td><?php echo esc_html( $definition['label'] ); ?></td>
                    <td>
                        <?php if ( $page_id ) : ?>
                            <a class="button" href="<?php echo esc_url( admin_url( 'post.php?post=' . $page_id . '&action=edit' ) ); ?>">
                                <?php esc_html_e( 'ویرایش', 'eshobe-ecommerce' ); ?>
                            </a>
                        <?php else : ?>
                            <a class="button" href="<?php echo esc_url( wp_nonce_url( admin_url( 'admin-post.php?action=wm_blocks_setup_region&region=' . $region ), 'wm_blocks_setup_region' ) ); ?>">
                                <?php esc_html_e( 'فعال‌سازی', 'eshobe-ecommerce' ); ?>
                            </a>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}
