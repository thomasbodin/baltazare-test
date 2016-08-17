<?php

/**
 * Affichage des erreurs dans la balise <pre>
 *
 * utilisation : vardump($array);
 */
function vardump($var = null){
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
}

