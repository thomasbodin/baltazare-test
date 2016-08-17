<?php

/**
 * @return string Get user role
 * Get user role
 */
function get_user_role($userId = null) {
    global $wp_roles;

    $userId = $userId == null ? get_current_user_id() : $userId;

    $current_user = get_user_by('id',$userId);
    $roles = $current_user->roles;
    $role = array_shift($roles);
    return isset($wp_roles->role_names[$role]) ? translate_user_role($wp_roles->role_names[$role] ) : false;
}