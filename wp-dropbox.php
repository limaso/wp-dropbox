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
?>
    
    <div class="wrap">
      <h2>Dropbox Optionen</h2>
      <form name="form1" method="post" action="<?=$location ?>">
      	<input name="fb_meta_field" value="<?=get_option("fb_meta_field");?>" type="text" />
      	<input type="submit" value="Speichern" />
      	<input name="action" value="insert" type="hidden" />
      </form>
    </div>
    
<?php
}

function wp_dropbox_menu() {
    add_menu_page('WP-Dropbox Einstellungen', 'Dropbox', 9, __FILE__, 'wp_dropbox_options_page', '../wp-content/plugins/wp-dropbox/images/dropbox_icon.gif');
}

add_action('admin_menu', 'wp_dropbox_menu');
?>