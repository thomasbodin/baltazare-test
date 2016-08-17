<?php
/*****************NE PAS TOUCHER A CE CODE *****************************/
// FONCTION D'INITIALISATION
/**
 * @param $nom
 * @param $menu_name
 * @param $genre
 * @return array
 */
function init($nom,$menu_name,$genre){
    // Création du slug (fonction wordpress)
    $slug = sanitize_title($nom);
    global $icons;
    if(!isset($icons) || empty($icons)){
        $icons = Array();
    }
    // Fin gestion icones
    // INITIALISATION
    if(!isset($genre))$genre = "M";
    if($genre == 'F'){
        $prep = ' une ';
        $prep2 = 'la ';
        $tous = "Toutes ";
        $aucun = "Aucune ";
        $trouve = " trouvée";
        $nouveau = "Nouvelle ";
    }
    else {
        $prep = ' un ';
        $prep2 = "le ";
        $tous = "Tous ";
        $aucun = "Aucun ";
        $trouve = " trouvé";
        $nouveau = "Nouveau ";
    }
    if(!isset($menu_name) OR empty($menu_name)) $menu_name = ucfirst(plural($nom));
    $icone = (array_key_exists($slug, $icons)) ? $icons[$slug] : "admin-post";
    // Je transforme les le/la en l' si la première lettre est une voyelle
    if(in_array($nom[0],array("a","e","i","o","u","y","A","E","I","O","U","Y")))$prep2 = "l'";

    // Création des valeurs pour renvoyer
    $result = array(
        'nom' => $nom,
        'slug' => $slug,
        'prep' => $prep,
        'tous' => $tous,
        'prep2'=> $prep2,
        'genre' => $genre,
        'icone' => $icone,
        'aucun' => $aucun,
        'trouve' => $trouve,
        'nouveau' => $nouveau,
        'menu_name' => $menu_name,
    );
    return $result;
}

// CREATION DES CHAMPS ET DU CUSTOM POST TYPE
/**
 * @param $result
 */
function CreateCPT($result){
    $labels = array(
        'name' => $result['nom'],
        'singular_name' => ucfirst($result['nom']),
        'add_new' => 'Ajouter' . $result['prep'] . $result['nom'],
        'add_new_item' => 'Ajouter' .$result['prep'] . $result['nom'],
        'edit_item' => 'Editer'.$result['prep'] . $result['nom'],
        'new_item' =>  $result['nouveau'] . $result['nom'],
        'all_items' => $result['tous'] .'les '. plural($result['nom']),
        'view_item' => 'Voir '. $result['nom'],
        'search_items' => 'Chercher '. $result['prep'] . $result['nom'],
        'not_found' =>  $result['aucun'] .$result['nom']. $result['trouve'],
        'not_found_in_trash' => $result['aucun'] .$result['nom']. $result['trouve'].' dans la corbeille',
        'parent_item_colon' => '',
        'menu_name' => $result['menu_name']
    );
    $args = array(
        'labels' => $labels,
        'public' => true,
        'publicly_queryable' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'query_var' => true,
        'rewrite' => array( 'slug' => $result['slug'] ),
        'capability_type' => 'post',
        'has_archive' => true,
        'menu_icon' => 'dashicons-' . $result['icone'],
        'hierarchical' => false,
        'menu_position' => null,
		'taxonomies' => array('post_tag'),
        'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt')
    );
    register_post_type($result['slug'], $args );
}

// FUNCTION DE CREATION DES CUSTOM TAXONOMY :
function CreateCTXM($cpt,$result){
    $cpt = sanitize_title($cpt);
    register_taxonomy(
        $result['slug'],
        $cpt,
        array(
            'label' => ucfirst($result['nom']) . "s",
            'labels' => array(
                'name' => ucfirst(plural($result['nom'])),
                'singular_name' => ucfirst($result['nom']),
                'all_items' => $result['tous'] . 'les ' . plural($result['nom']),
                'edit_item' => 'Editer' . $result['prep'] . $result['nom'],
                'view_item' => 'Voir ' . $result['nom'],
                'update_item' => 'Mettre à jour ' . $result['prep2'] . $result['nom'],
                'add_new_item' => 'Ajouter' . $result['prep'] . $result['nom'],
                'new_item_name' => $result['nouveau'] . $result['nom'],
                'search_items' => 'Chercher ' . $result['prep'] . $result['nom'],
                'popular_items' => 'Types les plus utilisés'
            ),
            'hierarchical' => true
        )
    );
    register_taxonomy_for_object_type($result['slug'], $cpt);
}

// Fonction pluriel
/**
 * @param $mot
 * @return string
 */
function plural($mot){
// Le mot est un groupe de mots
    if(substr_count($mot, ' ') != 0){

        if(substr_count($mot, ' ') >= 3){
            return "Cette fonction ne gère pas les phrases. seulement des groupes de mots de plus de 3 mots.";
        }
// Je découpe le mot pour jouer sur chacun des mots le composant
        $mots=explode(' ',$mot);
        $inv = Array('de','une','un');
        $newmot = "";
        foreach ($mots as $mot){
            if(!in_array($mot,$inv)){
                $newmot .= ' ' .addPlural($mot);
            }
            else{
                $newmot .= ' ' . $mot;
            }
        }
        $mot = $newmot;
    }
    else{
        $mot = addPlural($mot);
    }


    return $mot;
}

// Fonction qui ajoute le pluriel
/**
 * @param $mot
 * @return string
 */
function addPlural($mot){
    $exeptions1 = Array("bijou", "caillou", "chou", "genou", "hibou", "joujou", "pou");
    $exeptions2 = Array("Bal" , "carnaval" , "chacal", "festival", "récital", "cérémonial");
    $exeptions3 = Array("landau", "sarrau", "bleu", "pneu", "émeu");
    $invariables = Array("ainsi","après","au-dessous","au-dessus","aujourd’hui","auparavant","auprès","aussi","aussitôt","autant","autour","autrefois","autrement","avant","avec", "beaucoup","bien","bientôt","car","ceci","cela","cependant","certes","chez","comme","comment","d’abord","davantage","déjà","demain","devant","donc","dont","dorénavant","durant","encore","enfin","ensuite","entre","guère","gré","hier","ici","loin","lorsque","maintenant","malgré","mieux","moins","naguère","non","par","parce que","parmi","pendant","peu","plutôt","pour","pourquoi","pourtant","presque","quand","quoi","quoique","sauf","selon","seulement","sinon","sitôt","soudain","souvent","surtout","tant","tant mieux","tantôt","tard","tôt","trop","voici","voilà","vraiment");
    if(in_array(strtolower($mot),$invariables)){
        // Ne rien faire
    }
    elseif(in_array(strtolower($mot),$exeptions1)){
        $mot .= "x";
    }
    elseif('s' == $mot[strlen($mot)-1] || 'x' == $mot[strlen($mot)-1]){
        // Ne rien faire
    }
    elseif("al" == (substr($mot,strlen($mot)-2,2)) && !in_array(strtolower($mot),$exeptions2)){
        $mot = substr($mot,0,strlen($mot)-2) . "aux";
    }
    elseif("ail" == (substr($mot,strlen($mot)-3,3))){
        $mot = substr($mot,0,strlen($mot)-3) . "aux";
    }
    elseif(("au" == (substr($mot,strlen($mot)-2,2)) || "eu" == (substr($mot,strlen($mot)-2,2)) || "au" == (substr($mot,strlen($mot)-3,3))) && !in_array(strtolower($mot),$exeptions3)){
        $mot = $mot . 'x';

    }
    else{
        $mot = $mot . 's';
    }

    return $mot;
}

// TABLEAU DES ICONES
$icons = array(
    'video' => "video-alt3",
    'videos' => "video-alt3",
    'mp4' => "video-alt3",
    'image' => 'format-image',
    'images' => 'format-image',
    'photo' => 'format-image',
    'photos' => 'format-image',
    'photographie' => 'format-image',
    'photographies' => 'format-image',
    'telephone' => 'smartphone',
    'telephones' => 'smartphone',
    'phone' => 'smartphone',
    'phones' => 'smartphone',
    'smartphone' => 'smartphone',
    'smartphones' => 'smartphone',
    'application' => 'smartphone',
    'applications' => 'smartphone',
    'mobile' => 'smartphone',
    'site' => 'desktop',
    'sites' => 'desktop',
    'web' => 'desktop',
    'site-web' => 'desktop',
    'sites-web' => 'desktop',
    'competence' => 'welcome-learn-more',
    'competences' => 'welcome-learn-more',
    'universite' => 'welcome-learn-more',
    'etudes' => 'welcome-learn-more',
    'etude' => 'welcome-learn-more',
    'formation' => 'welcome-learn-more',
    'formations' => 'welcome-learn-more',
    'contact' => 'email',
    'contacts' => 'email',
    'mail' => 'email',
    'mails' => 'email',
    'e-mail' => 'email',
    'e-mails' => 'email',
    'emails' => 'email',
    'email' => 'email',
    'enveloppe' => 'email',
    'enveloppes' => 'email',
    'message' => 'email',
    'messages' => 'email',
    'panier' => 'cart',
    'paniers' => 'cart',
    'achat' => 'cart',
    'achats' => 'cart',
    'paiement' => 'cart',
    'paiements' => 'cart',
    'experience' => 'businessman',
    'experiences' => 'businessman',
    'experiences-pro' => 'businessman',
    'experience-pro' => 'businessman',
    'pro' => 'businessman',
    'professionnel' => 'businessman',
    'professionnels' => 'businessman',
    'profession' => 'businessman',
    'emploi' => 'businessman',
    'emplois' => 'businessman',
    'job' => 'businessman',
    'jobs' => 'businessman',
    'mission' => 'businessman',
    'missions' => 'businessman',
    'mission-pro' => 'businessman',
    'missions-pro' => 'businessman',
    'missions-professionnel' => 'businessman',
    'missions-professionnels' => 'businessman',
    'mission-professionnels' => 'businessman',
    'mission-professionnel' => 'businessman',
    'habitation' => 'admin-home',
);
?>