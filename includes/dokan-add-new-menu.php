<?php

add_filter( 'dokan_query_var_filter', 'dokan_load_document_menu' );
function dokan_load_document_menu( $query_vars ) {
    $query_vars['tazapay-information'] = 'tazapay-information';
    return $query_vars;
}

add_filter( 'dokan_get_dashboard_nav', 'dokan_add_help_menu' );
function dokan_add_help_menu( $urls ) {
    $urls['help'] = array(
        'title' => __( 'Tazapay Information', 'dokan'),
        'icon'  => '<i class="fa fa-user"></i>',
        'url'   => dokan_get_navigation_url( 'tazapay-information' ),
        'pos'   => 51
    );
    return $urls;
}

add_action( 'dokan_load_custom_template', 'dokan_load_template' );
function dokan_load_template( $query_vars ) {
    if ( isset( $query_vars['tazapay-information'] ) ) {
        echo do_shortcode('[tazapay-account]');
    }
}