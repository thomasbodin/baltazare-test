<?php

/**
 * Logs users
 */

add_action( 'init', 'wp_log_last_visit' );
//Enregistre les visites des users connecté à wp
function wp_log_last_visit(){
    $time_between2log = 3600;  // temps en seconde entre 2 log
    $userId = get_current_user_id();
    // Je récupère la dernière connexion si elle existe déja
    $visit = get_user_meta($userId, 'last_connexion');
    // S'il n'y a pas de note, j'entre une note
    if (empty($visit))
        add_user_meta($userId, 'last_connexion', time());
    // Sinon, j'update celle existante seulement si il s'est passé le temps entre les 2 logs
    elseif (time() > $visit[0] + $time_between2log) {
        update_user_meta($userId, 'last_connexion', time());
    }
}
add_filter('manage_users_columns', 'add_status_column');
add_filter('manage_users_custom_column', 'manage_status_column', 10, 3);
function add_status_column($columns) {
    $columns['last_connexion'] = 'Dernière connexion';
    return $columns;
}
function manage_status_column($empty='', $column_name, $id) {
    if( $column_name == 'last_connexion' ) {
        $visit = get_user_meta($id, 'last_connexion');
        if(!empty($visit)){
            $last_connexion = strftime('%d/%m/%Y à %Hh%M',$visit[0]);
            return $last_connexion;
        }else{
            return 'Pas encore connecté';
        }
    }
}