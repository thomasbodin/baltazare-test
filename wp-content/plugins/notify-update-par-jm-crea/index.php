<?php
/**
 * Plugin Name: Notify Update par JM Créa
 * Plugin URI: http://www.jm-crea.com
 * Description: Soyez notifié lors d'une mise à jour sur vos plugins, vos thèmes et vos versions de Wordpress.
 * Version: 2.2
 * Author: JM Créa
 * Author URI: http://www.jm-crea.com
 */

//On créé le menu
function menu_nu_jm_crea() {
add_submenu_page( 'tools.php', 'Notify Update', 'Notify Update', 'manage_options', 'nu_jmcrea', 'afficher_nu_jmcrea' ); 
}

add_action('admin_menu', 'menu_nu_jm_crea');
add_action( 'admin_enqueue_scripts', 'style_nu_jm_crea' );

//Appel du css
function style_nu_jm_crea() {
wp_register_style('css_nu_jm_crea', plugins_url( 'css/style.css', __FILE__ ));
wp_enqueue_style('css_nu_jm_crea');	
}


//On créé la table mysql
function creer_table_nu_jm_crea() {
global $wpdb;
$table_nu_jm_crea = $wpdb->prefix . 'nu_jm_crea';
$sql = "CREATE TABLE IF NOT EXISTS $table_nu_jm_crea (
id_nujmcrea int(11) NOT NULL AUTO_INCREMENT,
nu_jm_crea_actif text DEFAULT NULL,
verif_nu text DEFAULT NULL,
nu_jm_crea_actif_mail text DEFAULT NULL,
email_nu_jm_crea text DEFAULT NULL,
nu_jm_crea_actif_sms text DEFAULT NULL,
nu_jm_crea_id_freemobile text DEFAULT NULL,
nu_jm_crea_cle_freemobile text DEFAULT NULL,
exec_sms text DEFAULT NULL,
UNIQUE KEY id (id_nujmcrea)
);";
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
dbDelta( $sql );
}

//On insere les infos dans la table
function insert_table_nu_jm_crea() {
global $wpdb;
$table_nu_jm_crea = $wpdb->prefix . 'nu_jm_crea';
$wpdb->insert( 
$table_nu_jm_crea, 
array('nu_jm_crea_actif'=>'ON','verif_nu'=>'86400','nu_jm_crea_actif_mail'=>'ON','email_nu_jm_crea'=>get_option(admin_email),'nu_jm_crea_actif_sms'=>'OFF','nu_jm_crea_id_freemobile'=>'OFF','nu_jm_crea_cle_freemobile'=>'OFF','exec_sms'=>'fgc'), 
array('%s','%s','%s','%s','%s','%s','%s','%s','%s')
);
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
dbDelta( $sql );
}
register_activation_hook( __FILE__, 'creer_table_nu_jm_crea' );
register_activation_hook( __FILE__, 'insert_table_nu_jm_crea' );


//Appel de la fonction pour vérifier les mises à jour
function nu_par_jm_crea() {

global $wpdb;
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
$table_nu_jm_crea = $wpdb->prefix . "nu_jm_crea";
$voir_nu_update = $wpdb->get_row("SELECT * FROM $table_nu_jm_crea WHERE id_nujmcrea='1'");


if ($voir_nu_update->nu_jm_crea_actif == 'ON') {

//Mise à jour des plugins
do_action("wp_update_plugins");
$maj_plugins = get_site_transient('update_plugins');
if (!empty($maj_plugins->response) && (count($maj_plugins->response) >= 1)) {

foreach ($maj_plugins->response as $key => $value) {
$plugin_details = get_plugin_data(WP_PLUGIN_DIR . '/' . $key);
$email_nu_plugin .= '<p>Plugin "' . $plugin_details['Name'] . '<strong> (' . $plugin_details['Version'] . ')"</strong> est disponible en version <strong>(' . $value->new_version . ')</strong></p>';
$sms_nu_plugin .= 'Plugin "' . $plugin_details['Name'] . ' (' . $plugin_details['Version'] . ')" évolue en (' . $value->new_version . '). ';
$plugin_maj = 'OK';
}
}

//Mise à jour de Wordpress
do_action("wp_version_check");
$maj_wp = get_site_transient("update_core");
if ('upgrade' == $maj_wp->updates[0]->response) {
$email_nu_wordpress .= '<p>Votre version de Wordpress <strong>(' . get_bloginfo('version') . ')</strong> est disponible en version <strong>' . $maj_wp->updates[0]->current . '</strong></p>';
$sms_nu_wordpress .= 'Votre version de Wordpress (' . get_bloginfo('version') . ') est disponible en version ' . $maj_wp->updates[0]->current;
$wordpress_maj = 'OK';
}

//Mise à jour des thèmes
do_action("wp_update_themes");
$maj_themes = get_site_transient('update_themes');
if (!empty($maj_themes->response) && (count($maj_themes->response) >= 1)) {
foreach ($maj_themes->response as $key => $value) {
$theme_details = get_theme_data(WP_CONTENT_DIR . '/themes/' . $key . '/style.css');
$email_nu_theme .= '<p>Une nouvelle version pour votre thème "<strong>' . $theme_details['Name'] . '</strong>" est disponible. La version version <strong>' . $theme_details['Version'] . '</strong> est disponible en version <strong>' . $value['new_version'] . '</strong></p>' ;
$sms_nu_theme .= 'Une nouvelle version pour votre thème "' . $theme_details['Name'] . '" est disponible. La version version ' . $theme_details['Version'] . ' est disponible en version ' . $value['new_version'];
$theme_maj = 'OK';
}
}


//On créé l'entête du mail
$sujet = "Notify Update " . get_bloginfo('name')  . "";
$from  = "From:" . get_option( 'admin_email' )  . "\n";
$from .= "MIME-version: 1.0\n";
$from .= "Reply-To: " . get_option( 'admin_email' )  . "\n";
$from .= "Return-Path: <" . get_option( 'admin_email' )  . ">\n";
$from .= "X-Mailer: Notify Update Wordpress par JM Créa\n";
$from .= "Content-type: text/html; charset= utf-8\n";


//On verifie si il y a des mises à jour avant de les envoyer
if ($plugin_maj == 'OK') {
/* Envoi par mail */
$msg_plugin .= '<p>De nouvelles mises à jour sont disponibles pour votre site ' . get_bloginfo('name') . '</p><br />';
$msg_plugin .= $email_nu_plugin;
$msg_plugin .= '<p>Pour faire la mise à jour, <a href="' . get_bloginfo('url') . '/wp-admin/update-core.php">cliquez ici.</a></p>';
$msg_plugin .= '<br /><p><small><u>PS</u> : Si vous trouvez ce plugin utile, merci de laisser une note sur <a href="https://wordpress.org/plugins/notify-update-par-jm-crea" target="_blank">Wordpress.org :)</a></small></p>';
if ( ($voir_nu_update->nu_jm_crea_actif_mail == 'ON')&&($voir_nu_update->email_nu_jm_crea !== '') ) {
mail($voir_nu_update->email_nu_jm_crea,$sujet,$msg_plugin,$from);
}
/* Envoi par sms */
$msg2_plugin .= 'De nouvelles mises à jour sont disponibles pour votre site ' . get_bloginfo('name') . '. ';
$msg2_plugin .= $sms_nu_plugin;
$url_freemobile_plugin = "https://smsapi.free-mobile.fr/sendmsg?user=" . $voir_nu_update->nu_jm_crea_id_freemobile . "&pass=" . $voir_nu_update->nu_jm_crea_cle_freemobile . "&msg=" . $msg2_plugin;
if ( ($voir_nu_update->nu_jm_crea_actif_sms == 'ON')&&($voir_nu_update->exec_sms == 'fgc') ) {
file_get_contents($url_freemobile_plugin);
}
elseif ( ($voir_nu_update->nu_jm_crea_actif_sms == 'ON')&&($voir_nu_update->exec_sms == 'curl') ) {
$handle = curl_init($url_freemobile_plugin);
curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
$response = curl_exec($handle);
$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
curl_close($handle);
}
}

if ($wordpress_maj == 'OK') {
/* Envoi par mail */
$msg_wp .= '<p>De nouvelles mises à jour sont disponibles pour votre site ' . get_bloginfo('name') . '<p><br />';
$msg_wp .= $email_nu_wordpress;
$msg_wp .= '<p>Pour faire la mise à jour, <a href="' . get_bloginfo('url') . '/wp-admin/update-core.php">cliquez ici.</a></p>';
if ( ($voir_nu_update->nu_jm_crea_actif_mail == 'ON')&&($voir_nu_update->email_nu_jm_crea !== '') ) {
mail($voir_nu_update->email_nu_jm_crea,$sujet,$msg_wp,$from);
}
/* Envoi par sms */
$msg_wp2 .= 'De nouvelles mises à jour sont disponibles pour votre site ' . get_bloginfo('name') . '. ';
$msg_wp2 .= $sms_nu_wordpress;
$url_freemobile_wordpress = "https://smsapi.free-mobile.fr/sendmsg?user=" . $voir_nu_update->nu_jm_crea_id_freemobile . "&pass=" . $voir_nu_update->nu_jm_crea_cle_freemobile . "&msg=" . $msg_wp2;
if ( ($voir_nu_update->nu_jm_crea_actif_sms == 'ON')&&($voir_nu_update->exec_sms == 'fgc') ) {
file_get_contents($url_freemobile_wordpress);
}
elseif ( ($voir_nu_update->nu_jm_crea_actif_sms == 'ON')&&($voir_nu_update->exec_sms == 'curl') ) {
$handle = curl_init($url_freemobile_wordpress);
curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
$response = curl_exec($handle);
$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
curl_close($handle);
}
}


if ($theme_maj == 'OK') {
/* Envoi par mail */
$msg_theme .= '<p>De nouvelles mises à jour sont disponibles pour votre site ' . get_bloginfo('name') . '</p><br />';
$msg_theme .= $email_nu_theme;
$msg_theme .= '<p>Pour faire la mise à jour, <a href="' . get_bloginfo('url') . '/wp-admin/update-core.php">cliquez ici.</a></p>';
if ( ($voir_nu_update->nu_jm_crea_actif_mail == 'ON')&&($voir_nu_update->email_nu_jm_crea !== '') ) {
mail($voir_nu_update->email_nu_jm_crea,$sujet,$msg_theme,$from);
}
/* Envoi par sms */
$msg_theme2 .= 'De nouvelles mises à jour sont disponibles pour votre site ' . get_bloginfo('name') . '. ';
$msg_theme2 .= $sms_nu_theme;
$url_freemobile_theme = "https://smsapi.free-mobile.fr/sendmsg?user=" . $voir_nu_update->nu_jm_crea_id_freemobile . "&pass=" . $voir_nu_update->nu_jm_crea_cle_freemobile . "&msg=" . $msg_theme2;
if ( ($voir_nu_update->nu_jm_crea_actif_sms == 'ON')&&($voir_nu_update->exec_sms == 'fgc') ) {
file_get_contents($url_freemobile_theme);
}
elseif ( ($voir_nu_update->nu_jm_crea_actif_sms == 'ON')&&($voir_nu_update->exec_sms == 'curl') ) {
$handle = curl_init($url_freemobile_theme);
curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
$response = curl_exec($handle);
$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
curl_close($handle);
}
}

}
}
//On execute la tache Cron
register_activation_hook(__FILE__, 'cron_nu_jm_crea');
add_action('cron_schedules', 'cron_nu_jm_crea');

function cron_nu_jm_crea($schedules) {
global $wpdb;
require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
$table_nu_jm_crea = $wpdb->prefix . "nu_jm_crea";
$voir_nu_cron = $wpdb->get_row("SELECT * FROM $table_nu_jm_crea WHERE id_nujmcrea='1'");

$schedules['envoi_perso'] = array('interval'=>$voir_nu_cron->verif_nu, 'display'=>'Temps personnalisé');
return $schedules;
}

if (!wp_next_scheduled('nu_jm_crea')) {
wp_schedule_event(time(), 'envoi_perso', 'nu_jm_crea');
}
add_action('nu_jm_crea', 'nu_par_jm_crea');

//On désactive le Cron lorsque le plugin est désactivé
register_deactivation_hook(__FILE__, 'cron_nu_jm_crea_stop');
function cron_nu_jm_crea_stop() {
	wp_clear_scheduled_hook('nu_jm_crea');
}
//On affiche la page de l'admin
function afficher_nu_jmcrea() {

require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
global $wpdb;
$table_nu_jm_crea = $wpdb->prefix . "nu_jm_crea";
$voir_nu_affichage = $wpdb->get_row("SELECT * FROM $table_nu_jm_crea WHERE id_nujmcrea='1'");

echo "<h1>Notify Update JM Créa</h1>";
echo "<p>Notify Update par JM Créa vous permet d'être notifié par et mail et sms (pour les abonnés Freemobile) lorsque des mises à jour sont disponibles.</p>";
echo "<p>PS : Merci de noter ce plugin sur <a href='https://wordpress.org/plugins/notify-update-par-jm-crea' target='_blank'>wordpress.org</a> si ce n'est pas déjà fait :)</a></p>";

//Mise à jour des planifications
if (isset($_POST['maj_not_nu'])) {

$nu_jm_crea_actif = stripslashes($_POST['nu_jm_crea_actif']);
$verif_nu = stripslashes($_POST['verif_nu']);
if ($verif_nu == 'h') { $verif_nu = 3600; } //heure
elseif ($verif_nu == 'j') { $verif_nu = 86400; } //jour
elseif ($verif_nu == 's') { $verif_nu = 604800; } //semaine

global $wpdb;
$table_nu_jm_crea = $wpdb->prefix . "nu_jm_crea";
$wpdb->query($wpdb->prepare("UPDATE $table_nu_jm_crea SET nu_jm_crea_actif='$nu_jm_crea_actif', verif_nu='$verif_nu' WHERE id_nujmcrea='1'"));
echo '<script>document.location.href="tools.php?page=nu_jmcrea&tab=planification&action=planing-ok"</script>';
cron_nu_jm_crea_stop();
nu_par_jm_crea();
}
if ( (isset($_GET['action']))&&($_GET['action'] == 'planing-ok') ) {
echo '<div class="updated"><p>Paramètres de planification enregistrés.</p></div>';
}
//Mise à jour du mail de notification
if (isset($_POST['maj_nu_jm_crea_mail'])) {
$nu_jm_crea_actif_mail = stripslashes($_POST['nu_jm_crea_actif_mail']);
$email_nu_jm_crea = stripslashes($_POST['email_nu_jm_crea']);
global $wpdb;
$table_nu_jm_crea = $wpdb->prefix . "nu_jm_crea";
$wpdb->query($wpdb->prepare("UPDATE $table_nu_jm_crea SET email_nu_jm_crea='$email_nu_jm_crea', nu_jm_crea_actif_mail='$nu_jm_crea_actif_mail' WHERE id_nujmcrea='1'"));
echo '<script>document.location.href="tools.php?page=nu_jmcrea&tab=notifs_email&action=mail-ok"</script>';
cron_nu_jm_crea_stop();
nu_par_jm_crea();
}
if ( (isset($_GET['action']))&&($_GET['action'] == 'mail-ok') ) {
echo '<div class="updated"><p>Paramètres des mails enregistrés.</p></div>';
}
//Mise à jour des parametres sms
if (isset($_POST['maj_nu_sms'])) {
$nu_jm_crea_actif_sms = stripslashes($_POST['nu_jm_crea_actif_sms']);
$nu_jm_crea_id_freemobile = stripslashes($_POST['nu_jm_crea_id_freemobile']);
$nu_jm_crea_cle_freemobile = stripslashes($_POST['nu_jm_crea_cle_freemobile']);
$exec_sms = stripslashes($_POST['exec_sms']);
global $wpdb;
$table_nu_jm_crea = $wpdb->prefix . "nu_jm_crea";
$wpdb->query($wpdb->prepare("UPDATE $table_nu_jm_crea SET nu_jm_crea_actif_sms='$nu_jm_crea_actif_sms', nu_jm_crea_id_freemobile='$nu_jm_crea_id_freemobile', nu_jm_crea_cle_freemobile='$nu_jm_crea_cle_freemobile', exec_sms='$exec_sms' WHERE id_nujmcrea='1'"));

cron_nu_jm_crea_stop();
nu_par_jm_crea();
echo '<script>document.location.href="tools.php?page=nu_jmcrea&tab=notifs_fm&action=sms-ok"</script>';
}
if ( (isset($_GET['action']))&&($_GET['action'] == 'sms-ok') ) {
echo '<div class="updated"><p>Paramètres des SMS enregistrés.</p></div>';
}

echo '<div class="wrap">
<h2 class="nav-tab-wrapper">';
if ( (isset($_GET['tab']))&&($_GET['tab'] == 'planification') ) {
echo '<a class="nav-tab nav-tab-active" href="' . admin_url() . 'tools.php?page=nu_jmcrea&tab=planification">Pramètres de planification</a>';
}
else {
echo '<a class="nav-tab" href="' . admin_url() . 'tools.php?page=nu_jmcrea&tab=planification">Pramètres de planification</a>';	
}
if ( (isset($_GET['tab']))&&($_GET['tab'] == 'notifs_email') ) {
echo '<a class="nav-tab nav-tab-active"  href="' . admin_url() . 'tools.php?page=nu_jmcrea&tab=notifs_email">Paramètres des mails</a>';
}
else {
echo '<a class="nav-tab"  href="' . admin_url() . 'tools.php?page=nu_jmcrea&tab=notifs_email">Paramètres des mails</a>';
}
if ( (isset($_GET['tab']))&&($_GET['tab'] == 'notifs_fm') ) {
echo '<a class="nav-tab nav-tab-active"  href="' . admin_url() . 'tools.php?page=nu_jmcrea&tab=notifs_fm">Paramètres des sms</a>';
}
else {
echo '<a class="nav-tab"  href="' . admin_url() . 'tools.php?page=nu_jmcrea&tab=notifs_fm">Paramètres des sms</a>';	
}

if ( (isset($_GET['tab']))&&($_GET['tab'] == 'aide_freemobile') ) {
echo '<a class="nav-tab nav-tab-active"  href="' . admin_url() . 'tools.php?page=nu_jmcrea&tab=aide_freemobile">Aide Free Mobile</a>';
}
else {
echo '<a class="nav-tab"  href="' . admin_url() . 'tools.php?page=nu_jmcrea&tab=aide_freemobile">Aide Free Mobile</a>';	
}


if ( (isset($_GET['tab']))&&($_GET['tab'] == 'autres_plugins') ) {
echo '<a class="nav-tab nav-tab-active" href="' . admin_url() . 'tools.php?page=nu_jmcrea&tab=autres_plugins">Nos autres plugins</a>';
}
else {
echo '<a class="nav-tab" href="' . admin_url() . 'tools.php?page=nu_jmcrea&tab=autres_plugins">Nos autres plugins</a>';	
}
echo '</h2></div>';
	
if ( (isset($_GET['page']))&&($_GET['page'] == 'nu_jmcrea') ) {
	

if ( (isset($_GET['tab']))&&($_GET['tab'] == 'planification') ) {
echo '<h2>Planification</h2>';
echo '
<div id="cadre_blanc">
<form id="form1" name="form1" method="post" action="">
  <table border="0" cellspacing="8" cellpadding="0">
    <tr>
      <td>Activer les notifications : </td>
      <td>';
	  if ($voir_nu_affichage->nu_jm_crea_actif == 'ON') {
	  echo '<input name="nu_jm_crea_actif" type="radio" id="radio" value="ON" checked="checked" /> ON  <input type="radio" name="nu_jm_crea_actif" id="radio2" value="OFF" /> OFF';
	  }
	  else {
	  echo '<input name="nu_jm_crea_actif" type="radio" id="radio" value="ON" /> ON  <input type="radio" name="nu_jm_crea_actif" id="radio2" value="OFF" checked="checked" /> OFF';
	  }
	  
	echo '</td>
    </tr>
    <tr>
      <td>Vérification :</td>
      <td><select name="verif_nu" id="verif_nu">';
	  if ($voir_nu_affichage->verif_nu == '3600') { 
	  echo '
	  <option value="h">Toutes les heures</option>
	  <option value="j">1x par jour</option>
	  <option value="s">1x par semaine</option>';
	  }
	  elseif ($voir_nu_affichage->verif_nu == '86400') { 
	  echo '
	  <option value="j">1x par jour</option>
	  <option value="h">Toutes les heures</option>
	  <option value="s">1x par semaine</option>';
	  }
	  elseif ($voir_nu_affichage->verif_nu == '604800') { 
	  echo '
	  <option value="s">1x par semaine</option>
	  <option value="j">1x par jour</option>
	  <option value="h">Toutes les heures</option>';
	  }
	  echo '
      </select></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td><input type="submit" name="maj_not_nu" id="maj_not_nu" value="Mettre à jour" class="button button-primary" /></td>
    </tr>
  </table>
</form>
</div>';
echo '<p><u>NOTE :</u> Si vous désactivez les notifications par mail et sms, il est conseillé de désactiver aussi celui du planificateur afin que votre Wordpress n\'utilise pas trop de ressources.</p>';
}

if ( (isset($_GET['tab']))&&($_GET['tab'] == 'notifs_email') ) {
echo '<h2>Notification par mail</h2>';
echo '
<div id="cadre_blanc">
<form id="form1" name="form1" method="post" action="">
  <table border="0" cellspacing="8" cellpadding="0">
    <tr>
      <td>Activer les notifications par mail : </td>
      <td>';
	  if ($voir_nu_affichage->nu_jm_crea_actif_mail == 'ON') {
	 echo '<input name="nu_jm_crea_actif_mail" type="radio" id="radio" value="ON" checked="checked" /> ON  <input type="radio" name="nu_jm_crea_actif_mail" id="radio2" value="OFF" /> OFF</td>';
	  }
	  else {
	 echo '<input name="nu_jm_crea_actif_mail" type="radio" id="radio" value="ON" /> ON  <input type="radio" name="nu_jm_crea_actif_mail" id="radio2" value="OFF" checked="checked" /> OFF</td>';	  
	  }
	  echo '
    </tr>
    <tr>
      <td>Email de notification :</td>
      <td><input type="text" name="email_nu_jm_crea" id="email_nu_jm_crea" value="' . $voir_nu_affichage->email_nu_jm_crea .'" /></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td><input type="submit" name="maj_nu_jm_crea_mail" id="maj_nu_jm_crea_mail" value="Mettre à jour" class="button button-primary" /></td>
    </tr>
  </table>
</form>
</div>';
}
if ( (isset($_GET['tab']))&&($_GET['tab'] == 'notifs_fm') ) {
echo '<h2>Notification par sms</h2>';


//On vérifie si le allow_url_fopen est activé ou pas

$allow_url_fopen = ini_get('allow_url_fopen');
if ( ($allow_url_fopen !== '1')&&($voir_nu_affichage->exec_sms == 'fgc') ) {
echo '<div class="error"><p>Notification par SMS impossible avec la fonction <code>file_get-contents</code>, vous devez activer le <code>allow_url_fopen</code> du php.ini.</p><p>Si vous ne savez pas comment faire, veuillez contacter votre hébergeur.</p><p>Il est recommandé d\'utiliser la fonction <code>CURL</code></div>';
}

echo '
<div id="cadre_blanc">
<form id="form1" name="form1" method="post" action="">
  <table border="0" cellspacing="8" cellpadding="0">
    <tr>
      <td>Activer les notifications par SMS : </td>
      <td>';
	  if ($voir_nu_affichage->nu_jm_crea_actif_sms == 'ON') {
	  echo '<input name="nu_jm_crea_actif_sms" type="radio" id="radio" value="ON" checked="checked" /> ON  <input type="radio" name="nu_jm_crea_actif_sms" id="radio2" value="OFF" /> OFF';
	  }
	  else {
	  echo '<input name="nu_jm_crea_actif_sms" type="radio" id="radio" value="ON" /> ON  <input type="radio" name="nu_jm_crea_actif_sms" id="radio2" value="OFF" checked="checked" /> OFF';  
	  }
	  echo '</td>
    </tr>
    <tr>
      <td>Identifiant Freemobile :</td>
      <td><input type="password" name="nu_jm_crea_id_freemobile" id="nu_jm_crea_id_freemobile" value="' . $voir_nu_affichage->nu_jm_crea_id_freemobile . '" /></td>
    </tr>
    <tr>
      <td>Clé d\'identification  Freemobile :</td>
      <td><input type="password" name="nu_jm_crea_cle_freemobile" id="nu_jm_crea_cle_freemobile" value="' . $voir_nu_affichage->nu_jm_crea_cle_freemobile . '" /></td>
    </tr>
    <tr>
	<td>Type d\'exectution de l\'api Freemobile :</td>
	<td>
	';
	  if ($voir_nu_affichage->exec_sms == 'fgc') {
	  echo '<input name="exec_sms" type="radio" id="radio" value="fgc" checked="checked" /> file_get_content  <input type="radio" name="exec_sms" id="radio2" value="curl" /> CURL';
	  }
	  else {
	  echo '<input name="exec_sms" type="radio" id="radio" value="fgc" /> file_get_content  <input type="radio" name="exec_sms" id="radio2" value="curl" checked="checked"/> CURL';  
	  }
	  echo '
	</td>
	</tr>
	<tr>
      <td>&nbsp;</td>
      <td align="right"><input type="submit" name="maj_nu_sms" id="maj_nu_sms" value="Mettre à jour" class="button button-primary" /></td>
    </tr>
  </table>
</form>
<hr>
<h2>Tester l\'envoi des SMS</h2>
<p>Vous pouvez choisir de tester les différentes méthodes d\'execution de l\'api Freemobile.</p>
<p>Il est conseillé d\'utiliser la méthode <code>CURL</code> dans vos paramètres.</p>';

if (isset($_POST['test_nu_sms'])) {
$test_nu = stripslashes($_POST['test_nu']);

if ($test_nu == 'curl') {
$url_test_freemobile = "https://smsapi.free-mobile.fr/sendmsg?user=" . $voir_nu_affichage->nu_jm_crea_id_freemobile . "&pass=" . $voir_nu_affichage->nu_jm_crea_cle_freemobile . "&msg=Test Notify Update par JM Créa avec execution en CURL.";
$handle = curl_init($url_test_freemobile);
curl_setopt($handle,  CURLOPT_RETURNTRANSFER, TRUE);
$response = curl_exec($handle);
$httpCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
curl_close($handle);
echo '<script>document.location.href="tools.php?page=nu_jmcrea&tab=notifs_fm&action=test-ok-curl"</script>';
}
elseif ($test_nu == 'fgc') {
$url_test_freemobile = "https://smsapi.free-mobile.fr/sendmsg?user=" . $voir_nu_affichage->nu_jm_crea_id_freemobile . "&pass=" . $voir_nu_affichage->nu_jm_crea_cle_freemobile . "&msg=Test Notify Update par JM Créa avec execution en file_get_contents.";
file_get_contents($url_test_freemobile);
echo '<script>document.location.href="tools.php?page=nu_jmcrea&tab=notifs_fm&action=test-ok-fgc"</script>';
}
}
if ( (isset($_GET['action']))&&($_GET['action'] == 'test-ok-fgc') ) {
echo '<div class="updated"><p>Le test a bien été effectué en <code>file_get_contents</code></p></div>';
}
elseif ( (isset($_GET['action']))&&($_GET['action'] == 'test-ok-curl') ) {
echo '<div class="updated"><p>Le test a bien été effectué en <code>CURL</code></p></div>';
}
echo '<form id="form1" name="form1" method="post" action="">
<table>
<tr>
<td>
<select name="test_nu" id="test_nu">
<option value="curl">CURL</option>
<option value="fgc">file_get_contents</option>
</select>
</td>
<td><input type="submit" name="test_nu_sms" id="maj_nu_sms" value="Tester" class="button button-primary" /></td>
</tr>
</table>
</form>
</div>';
echo "<p><u>NOTE</u> : <p>Une fois tous vos paramètres renseignés, la notification par SMS en <code>file_get_contents</code> ne peut fonctionner que si la fonction <code>allow_url_fopen</code> du php.ini de votre hébergeur est active.</p><p>Il est conseillé d'utiliser la fonction <code>CURL</code>.";
}

if ( (isset($_GET['tab']))&&($_GET['tab'] == 'aide_freemobile') ) {
echo "<h1>Aide au paramètrage freemobile</h1>
<div id='cadre_blanc'>
<p>&nbsp;</p>
<h2>Connectez-vous sur votre espace freemobile</h2>
<p>Rendez-vous sur votre <a href='https://mobile.free.fr/moncompte/' target='_blank'>espace abonné freemobile</a> puis identifiez-vous.</p>
<h2>Activez vos notifications SMS</h2>
<p>Cliquez sur <strong><u>Mes options</u></strong> puis activez <strong><u>Notification par SMS</u></strong> et récupérez <strong><u>Votre clé d'identification au service</u></strong>
<h2>Information importante</h2>
<p>Votre identifiant freemobile ainsi que votre clé d'activation ne peuvent être cryptés dans la base de données Wordpress. L'identifiant et la clé d'activation sont affichés en brut dans la base de données de votre site Wordpress.</p>
<p>Le développeur du plugin (JM Créa) se dégage de toute responsabilité si vous utilisez ce plugin et que votre site se fait hacké.</p></div>";
}



if ( (isset($_GET['tab']))&&($_GET['tab'] == 'autres_plugins') ) {
	echo '
	<div id="listing_plugins">
	<h3>Social Share</h3>
	<img src="' . plugins_url( 'autres-plugins-jm-crea/social-share-par-jm-crea.jpg', __FILE__ ) . '" alt="Social Share par JM Créa" />
	<p>Social Share par JM Créa vous permet de partager votre contenu sur les réseaux sociaux.</p>
	<div align="center"><a href="https://fr.wordpress.org/plugins/social-share-by-jm-crea/" target="_blank"><button class="button button-primary">Télécharger</button></a></div>
	</div>
	
    <div id="listing_plugins">
	<h3>Search box Google</h3>
	<img src="' . plugins_url( 'autres-plugins-jm-crea/search-box-google-par-jm-crea.jpg', __FILE__ ) . '" alt="Search Box Google par JM Créa" />
	<p>Search Box Google permet d’intégrer le mini moteur de recherche de votre site dans les résultats Google.</p>
	<div align="center"><a href="https://fr.wordpress.org/plugins/search-box-google-par-jm-crea/" target="_blank"><button class="button button-primary">Télécharger</button></a></div>
	</div>
	
	<div id="listing_plugins">
	<h3>Notify Update</h3>
	<img src="' . plugins_url( 'autres-plugins-jm-crea/notify-update-par-jm-crea.jpg', __FILE__ ) . '" alt="Notify Update par JM Créa" />
	<p> Notify Update par JM Créa vous notifie par email et sms (pour les abonnés freemobile) lors d’une mise à jour de votre WordPress.</p>
	<div align="center"><a href="https://fr.wordpress.org/plugins/notify-update-par-jm-crea/" target="_blank"><button class="button button-primary">Télécharger</button></a></div>
	</div>
	
	
	<div id="listing_plugins">
	<h3>Notify Connect</h3>
	<img src="' . plugins_url( 'autres-plugins-jm-crea/notify-connect-par-jm-crea.jpg', __FILE__ ) . '" alt="Notify Connect par JM Créa" />
	<p>Notify connect créé par JM Créa permet d’être notifié par email et sms (pour les abonnés freemobile) lorsqu’un admin se connecte sur l\'admin.</p>
	<div align="center"><a href="https://fr.wordpress.org/plugins/notify-connect-par-jm-crea/" target="_blank"><button class="button button-primary">Télécharger</button></a></div>
	</div>
	
	
	<div id="listing_plugins">
	<h3>Simple Google Adsense</h3>
	<img src="' . plugins_url( 'autres-plugins-jm-crea/simple-google-adsense-par-jm-crea.jpg', __FILE__ ) . '" alt="Simple Google Adsense par JM Créa" />
	<p>Simple Google Adsense par JM Créa permet d’afficher vos publicités Google Adsense avec de simples shortcodes.</p>
	<div align="center"><a href="https://fr.wordpress.org/plugins/simple-google-adsense-par-jm-crea/" target="_blank"><button class="button button-primary">Télécharger</button></a></div>
	</div>
	
	<div id="listing_plugins">
	<h3>Scan Upload</h3>
	<img src="' . plugins_url( 'autres-plugins-jm-crea/scan-upload-par-jm-crea.jpg', __FILE__ ) . '" alt="Scan Upload par JM Créa" />
	<p>Scan Upload par JM Créa détecte les fichiers suspects de votre wp-upload et permet de les supprimer en 1 clic.</p>
	<div align="center"><a href="https://fr.wordpress.org/plugins/scan-upload-par-jm-crea/" target="_blank"><button class="button button-primary">Télécharger</button></a></div>
	</div>
	
	<div id="listing_plugins">
	<h3>Knowledge Google</h3>
	<img src="' . plugins_url( 'autres-plugins-jm-crea/knowledge-google-par-jm-crea.jpg', __FILE__ ) . '" alt="Knowledge Google par JM Créa" />
	<p>Knowledge Google par JM Créa permet d\'afficher les liens de vos réseaux sociaux directement dans les résultats Google.</p>
	<div align="center"><a href="https://wordpress.org/plugins/knowledge-google-par-jm-crea/" target="_blank"><button class="button button-primary">Télécharger</button></a></div>
	</div>'
	
	;
}
elseif (!isset($_GET['tab'])) {
echo '<script>document.location.href="tools.php?page=nu_jmcrea&tab=planification"</script>';
}
}

}
function head_meta_nu_jm_crea() {
echo("<meta name='Notify Update par JM Créa' content='2.2' />\n");
}
add_action('wp_head', 'head_meta_nu_jm_crea');
?>