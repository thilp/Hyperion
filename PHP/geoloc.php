<?php
require_once('edit.php');



function coord_vk_of_wp ($titre) {

// Récupère les coordonnées géographiques présentes dans l’article $titre sur Wikipédia et les renvoie sous la forme d’une chaîne de caractères "{{coord|55|45|0|N|37|37|0|E|display=title}}" (format pour Vikidia). Si pas de coordonnées trouvées dans $titre, renvoie une chaîne vide.

  $cont_wp = lectureArticle($titre,'wp');

  if ( preg_match('~\{\{[Cc]oord\|[^}]+\}\}~', $cont_wp, $tabContent) ) {

    if ( strpos($tabContent[0],'display=') ){

      if ( !strpos($tabContent[0],'display=title') )

        $tabContent[0] = preg_replace('~(display=)\w+([}|])~','$1title$2',$tabContent[0]);

    }

    else $tabContent[0] = preg_replace('~(\{\{[^}]+)(\}\})~','$1|display=title$2',$tabContent[0]);

    return $tabContent[0];

  }

  if ( preg_match('~\| *(?:dec)?l(at|ong)itude *= *([0-9\-./ NSEWO]+)[\n ]*\| *(?:dec)?l(?:at|ong)itude *= *([0-9\-./ NSEWO]+)[\n ]*~i', $cont_wp, $tabContent) ) {

    if ( $tabContent[1]==='at' ) {

      $latitude_wp = $tabContent[2];

      $longitude_wp = $tabContent[3]; }

    else {

      $latitude_wp = $tabContent[3];

      $longitude_wp = $tabContent[2]; }

    $latitude_vk = str_replace('/','|',$latitude_wp);

    $latitude_vk = str_replace(' ','',$latitude_vk);

    $longitude_vk = str_replace('/','|',$longitude_wp);

    $longitude_vk = str_replace(' ','',$longitude_vk);

    return '{{coord|'.$latitude_vk.'|'.$longitude_vk.'|display=title}}';

  }

  if ( preg_match('~\| *(?:dec)?l(at|ong)itude *= *(\{\{Coord/(?:dms|dec)2(?:dms|dec)\|[^|}]+(?:\|[^|}]+)?(?:\|[^|}]+)?(?:\|[^|}]+)?\}\})[\n ]*\| *(?:dec)?l(?:at|ong)itude *= *(\{\{Coord/(?:dms|dec)2(?:dms|dec)\|[^|}]+(?:\|[^|}]+)?(?:\|[^|}]+)?(?:\|[^|}]+)?\}\})[\n ]*~i', $cont_wp, $tabContent) ) {

    if ( $tabContent[1]==='at' ) {

      $latitude_wp = $tabContent[2];

      $longitude_wp = $tabContent[3]; }

    else {

      $latitude_wp = $tabContent[3];

      $longitude_wp = $tabContent[2]; }

    return '{{coord|'.$latitude_vk.'|'.$longitude_vk.'|display=title}}';

  }

  return '';

}



function poste_coord_ds_vk ($titre,$coord) {

// Écrit dans l’article $titre sur Vikidia les coordonnées géographiques $coord comprises dans le modèle {{coord}}

  return ajoutTexte_avantcategs($titre,$coord,'Importation et adaptation des coordonnées géographiques depuis Wikipédia. Mais qui vous assure, humains crédules, que je ne les ai pas changées un peu ? :');

}



function publicoord_of_article ($titre) {

// Ajoute à la page $titre les coordonnées géographiques récupérées depuis l’interwiki Wikipédia.

  $content_vk = lectureArticle($titre);

  if ( stripos($content_vk, '{{coord|') !== false ) { return false; }

  if ( preg_match('~\[\[wp:([^\]]+)\]\]~i', $content_vk, $tabContent) ) {

    $c = coord_vk_of_wp($tabContent[1]);

    if ($c === '') { return false; }

    if ( poste_coord_ds_vk($titre, $c) === true ) { return true; }

    else { echo "L’écriture des coordonnées a échoué dans l’article $titre.\n"; return false; }

  }

  else { return false; }

}



function publicoord_of_tous_articles () {

// Applique publicoord_of_article à tous les articles de Vikidia

  $deb = "";

  $compteur_chang = 0;

  $compteur_visi = 0;

  for ($i=0 ; $i<1 ; $i++) {

// Récupère la liste de tous les articles de Vikidia

    $liste = requeteAPI('format=xml&action=query&list=allpages&apfilterredir=nonredirects&aplimit=5000&apfrom='.urlencode($deb));

    print_r($liste);

    preg_match_all('~title="([^"]+)"~', $liste, $tabListe);

    foreach ( $tabListe[1] as $titre ) {

      $compteur_visi++;

      if ( publicoord_of_article($titre) ) { $compteur_chang++;}

    }

    if ( preg_match('~apfrom="([^"]+)"~',$liste,$tabDeb) ) { $deb = $tabDeb[1]; }

  }

  echo "Terminé. Articles visités / articles modifiés : $compteur_visi / $compteur_chang.";

}

function verifDisplaytitle ($titre) {

// Vérifie que la page $titre, si elle contient le modèle {{coord}}, contient également display=title.

  $content_vk = lectureArticle($titre);

  if ( stripos($content_vk, '{{coord|') === false ) return false;

  if ( strpos($content_vk, 'display=title') ) return false;

  if ( strpos($content_vk,'display=') ) $content_vk = preg_replace('~(display=)\w+([}|])~','$1title$2',$content_vk);

  else $content_vk = preg_replace('~(\{\{[Cc]oord\|[^}]+)(\}\})~','$1|display=title$2',$content_vk);

  return edit($titre,$content_vk,'Les heures sup’ : je n’avais pas vérifié la présence du « display=title ».','bot');

}

set_time_limit(0);



publicoord_of_tous_articles();
?>
