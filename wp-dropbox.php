<?php

/*

Plugin Name: WP-Dropbox
Plugin URI: http://limaso.de
Description: The WP-Dropbox Plugin will give you the ability to add files from your Dropbox to your Wordpress installation.
Version: 0.1
Author: Marc Lipscke
Author URI: http://limaso.de

*/

function wp_dropbox_options_page() {
    
}

function wp_dropbox_menu() {
    add_options_page('WP-Dropbox Einstellungen', 'Dropbox', 9, __FILE__, 'wp_dropbox_options_page');
}

add_action('admin_menu', 'wp_dropbox_menu');
?>