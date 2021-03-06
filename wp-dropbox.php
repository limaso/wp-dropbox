<?php

/*

Plugin Name: WP-Dropbox
Plugin URI: http://limaso.de
Description: The WP-Dropbox Plugin will give you the ability to add files from your Dropbox to your Wordpress installation.
Version: 0.1
Author: Marc Lipscke
Author URI: http://limaso.de

*/

require_once('dropboxConnections.php');

if ('insert' == $HTTP_POST_VARS['action']) {
    update_option("dropbox_email",$HTTP_POST_VARS['dropbox_email']);
    update_option("dropbox_password",$HTTP_POST_VARS['dropbox_password']);
    ?>
    <div id="message" class="updated"><p>
    Einstellungen gespeichert!
    </p></div>
    <?php
}
if ('backupoptions' == $HTTP_POST_VARS['action']) {
    update_option("dropbox_folder",$HTTP_POST_VARS['dropbox_folder']);
    ?>
    <div id="message" class="updated"><p>
    Einstellungen gespeichert!
    </p></div>
    <?php
}

function wp_dropbox_main_page() {
?>
    
    <div class="wrap">
      <h2>My Dropbox</h2>
      <hr />
      <?php
        //echo strpos('/users/maso/www/wp-content/uploads/', 'uploads', 0);
        
        $ordner = ordner("/users/maso/www/wp-content/uploads");
        dodbBackup("/users/maso/www/wp-content/uploads");
      ?>
      <h3>Statistik f&uuml;r den /wp-content/uploads Ordner:</h3>
      <p>
      <strong>Ordner:</strong> <?=$ordner[0]; ?><br />
      <strong>Dateien:</strong> <?=$ordner[1]; ?><br />
      <strong>Gesamte Gr&ouml;&szlig;e:</strong> <?=round($ordner[2]/1024/1024, 2) . " MB" ?>
      </p>
    </div>
    
<?php
}   // function wp_dropbox_main_page() ends

function wp_dropbox_options_page() {
?>
    
    <div class="wrap">
      <h2>Dropbox Login</h2>
      <hr />
      <form name="form1" method="post" action="<?=$location ?>">
        <table>
            <tr>
                <td colspan="2"><h3>Ihr Dropbox Login</h3></td>
            </tr>
            <tr>
                <td width="200">E-Mail:</td>
                <td><input type="text" name="dropbox_email" value="<?=get_option("dropbox_email");?>" size="35" /></td>
            </tr>
            <tr>
                <td>Passwort:</td>
                <td><input type="password" name="dropbox_password" value="<?=get_option("dropbox_password");?>" size="35" /></td>
            </tr>
            <tr>
                <td colspan="2" align="right"><input type="submit" value="Speichern" /></td>
            </tr>
        </table>
      	<input name="action" value="insert" type="hidden" />
      </form>
      
      <h2>Backup Einstellungen</h2>
      <hr />
      <form name="form2" method="post" action="<?=$location ?>">
        <table>
            <tr>
                <td colspan="2"><h3>Ihr Dropbox Login</h3></td>
            </tr>
            <tr>
                <td width="200">Backupordner:</td>
                <td><input type="text" name="dropbox_folder" value="<?=get_option("dropbox_folder");?>" size="35" /></td>
            </tr>
            <tr>
                <td colspan="2" align="right"><input type="submit" value="Speichern" /></td>
            </tr>
        </table>
      	<input name="action" value="backupoptions" type="hidden" />
      </form>
    </div>
    
<?php
}   // function wp_dropbox_options_page() ends

function wp_dropbox_menu() {
    add_menu_page('My Dropbox', 'My Dropbox', 9, __FILE__, 'wp_dropbox_main_page', '../wp-content/plugins/wp-dropbox/images/dropbox_icon.gif');
    add_submenu_page(__FILE__, 'My Dropbox', 'My Dropbox', 9, __FILE__, 'wp_dropbox_main_page');
    add_submenu_page(__FILE__, 'Dropbox Einstellungen', 'Einstellungen', 9, '', 'wp_dropbox_options_page');
}

function ordner($f) {
    global $dateien, $ordner, $groesse;
    $handle = opendir($f);
    while ($datei = readdir($handle)) {
        if($datei == ".") continue;
        if($datei == "..") continue;
        
        if(is_dir($f."/".$datei)) {
            $ordner++;
            ordner($f."/".$datei);
        } else {
            $dateien++;
            $groesse += filesize($f."/".$datei);
        }
    }
    closedir($handle);
    return array($ordner, $dateien, $groesse);
}

function dodbBackup($f) {
    $dropboxEmail = get_option('dropbox_email');
    $dropboxPassword = get_option('dropbox_password');
    $dbConn = new dropboxConnection($dropboxEmail, $dropboxPassword);
    
    $handle = opendir($f);
    while ($datei = readdir($handle)) {
        if($datei == ".") continue;
        if($datei == "..") continue;
        
        if(is_dir($f."/".$datei)) {
            dodbBackup($f."/".$datei);
        } else {
            $dbConn->upload($f."/".$datei, get_option("dropbox_folder")."/".substr($f, strpos($f, 'uploads', 0) + 8));
        }
    }
    closedir($handle);
}

add_action('admin_menu', 'wp_dropbox_menu');

?>