<?php
/**
 * Adds an 'Tazapay information' tab to the Dokan settings navigation menu.
 *
 * @param array $menu_items
 *
 * @return array
 */
function dokan_add_about_tab( $menu_items ) {
    $menu_items['tazapay-information'] = [
        'title'      => __( 'Tazapay information' ),
        'icon'       => '<i class="fa fa-user-circle"></i>',
        'url'        => dokan_get_navigation_url( 'settings/tazapay-information' ),
        'pos'        => 90,
        'permission' => 'dokan_view_store_settings_menu',
    ];
    return $menu_items;
}
add_filter( 'dokan_get_dashboard_settings_nav', 'dokan_add_about_tab' );

/**
 * Sets the title for the 'Tazapay information' settings tab.
 *
 * @param string $title
 * @param string $tab
 *
 * @return string Title for tab with slug $tab
 */
function dokan_set_about_tab_title( $title, $tab ) {
    if ( 'tazapay-information' === $tab ) {
        $title = __( 'Tazapay information' );
    }

    return $title;
}

add_filter( 'dokan_dashboard_settings_heading_title', 'dokan_set_about_tab_title', 10, 2 );

/**
 * Sets the help text for the 'Tazapay information' settings tab.
 *
 * @param string $help_text
 * @param string $tab
 *
 * @return string Help text for tab with slug $tab
 */
function dokan_set_about_tab_help_text( $help_text, $tab ) {
    if ( 'tazapay-information' === $tab ) {
        $help_text = __( 'Personalize your store page by telling customers a little about yourself.' );
    }

    return $help_text;
}

add_filter( 'dokan_dashboard_settings_helper_text', 'dokan_set_about_tab_help_text', 10, 2 );

/**
 * Outputs the content for the 'Tazapay information' settings tab.
 *
 * @param array $query_vars WP query vars
 */
function dokan_output_help_tab_content( $query_vars ) {
    if ( isset( $query_vars['settings'] ) && 'tazapay-information' === $query_vars['settings'] ) {
            echo do_shortcode('[tazapay-account]');
    }
}

add_action( 'dokan_render_settings_content', 'dokan_output_help_tab_content' );