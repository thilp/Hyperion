<?php
// Fonction de requête cURL
function cURL($url, $cook, $post) {
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  //curl_setopt($ch, CURLOPT_CAINFO, '/data/web/9a/dc/a4/ubiquite.tuxfamily.org/htdocs/greta/certificat_tuxfamily.p7b');
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
  curl_setopt($ch, CURLOPT_HTTPHEADER, Array('Content-type: application/x-www-form-urlencoded', 'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 6.1; fr; rv:1.9.2) Gecko/20100115 Firefox/3.6'));
  curl_setopt ($ch, CURLOPT_COOKIEJAR, $cook);
  curl_setopt ($ch, CURLOPT_COOKIEFILE, $cook);
  $reponse = curl_exec($ch);
  curl_close($ch);
  return $reponse;
}

function lauradate($nom=true){
  if($nom){
    switch(date('N')){
      case '1': $jour_nom = "lundi "; break;
      case '2': $jour_nom = "mardi "; break;
      case '3': $jour_nom = "mercredi "; break;
      case '4': $jour_nom = "jeudi "; break;
      case '5': $jour_nom = "vendredi "; break;
      case '6': $jour_nom = "samedi "; break;
      default: $jour_nom = "dimanche ";
    }
  }else{ $jour_nom = ''; }
  $jour_numero = date('j');
  switch(date('n')){
    case '1': $mois = "janvier"; break;
    case '2': $mois = "février"; break;
    case '3': $mois = "mars"; break;
    case '4': $mois = "avril"; break;
    case '5': $mois = "mai"; break;
    case '6': $mois = "juin"; break;
    case '7': $mois = "juillet"; break;
    case '8': $mois = "août"; break;
    case '9': $mois = "septembre"; break;
    case '10': $mois = "octobre"; break;
    case '11': $mois = "novembre"; break;
    default: $mois = "décembre";
  }
  $annee = date('Y');
  $heure = date('G');
  $minute = date('i');
  return $jour_nom.$jour_numero.' '.$mois.' '.$annee.' à '.$heure.' h '.$minute;
}

function requeteAPI($params,$interwiki='fr'){
  $cook = 'cookie_'.$interwiki.'.is';
  switch($interwiki){
    case 'fr': return cURL('https://fr.vikidia.org/w/api.php',$cook,$params);
    case 'commons': return cURL('http://commons.wikimedia.org/w/api.php',$cook,$params);
    case 'wp': return cURL('http://fr.wikipedia.org/w/api.php',$cook,$params);
    case 'nl': return cURL('http://wikikids.wiki.kennisnet.nl/api.php',$cook,$params);
    case 'es': return cURL('http://es.vikidia.org/w/api.php',$cook,$params);
    case 'de': return cURL('http://grundschulwiki.zum.de/api.php',$cook,$params);
    case 'en': return cURL('http://simple.wikipedia.org/w/api.php',$cook,$params);
    default: return false;
  }
}
function requeteAPI_wp($params,$prefixe='fr'){
  return cURL('http://'.$prefixe.'.wikipedia.org/w/api.php','',$params);
}

function lectureArticle($page,$prefixe='fr') {
  $retour = requeteAPI('action=query&prop=revisions&rvprop=content&format=xml&titles='.urlencode($page),$prefixe);
  preg_match('#<rev xml:space="preserve">(.*)</rev>#s',$retour,$tab);
  return htmlspecialchars_decode($tab[1]);
}
function lectureArticle_wp($page,$prefixe='fr') {
  $retour = requeteAPI_wp('action=query&prop=revisions&rvprop=content&format=xml&titles='.urlencode($page),$prefixe);
  preg_match('#<rev[^>]*>([^<]*)</rev>#s',$retour,$tab);
  return htmlspecialchars_decode($tab[1]);
}

function page_existe_wp($titre,&$redirection=false,&$page_redirect='',$wp_prefixe='fr'){
  $rep = cURL('http://'.$wp_prefixe.'.wikipedia.org/w/api.php','','action=query&prop=revisions&titles='.urlencode($titre).'&rvprop=content&format=xml');
  $pe = strpos($rep,' missing="" /></pages>')===false;
  if($pe){
    $redirection = (preg_match('/# ?(?:REDIRECT(?:ION)?|DOORVERWIJZING|REDIRECCIÓN)\s*\[\[([^\]]+)\]\]/',$rep,$tab)!=0);
    if($redirection){ $page_redirect = $tab[1]; }
  }
  return $pe;
}
function resolution_existence_page_et_redirection($titre,$prefixe='fr',&$redirection=false,&$page_redirect=''){
  // Teste l’existence d’une page $titre sur le wiki correspondant à $prefixe ; traite les problèmes de redirection.
  $retour = requeteAPI('action=query&prop=revisions&titles='.urlencode($titre).'&rvprop=content&format=xml',$prefixe);
  $existence = strpos($retour,' missing="" /></pages>')===false;
  if($existence){
    $redirection = preg_match('/#(?:REDIRECT(?:ION)?|DOORVERWIJZING|REDIRECCIÓN)\s*\[\[([^\]]+)\]\]/',$retour,$tab)==1;
    if($redirection){ $page_redirect = $tab[1]; }
  }
  return $existence;
}

function erp($chaine){ // Protège les caractères spéciaux pour le passage dans une expression rationnelle : quotemeta + |
  return addcslashes(quotemeta($chaine),'|');
}

function trad_pagename($titre,$prefixe){ // Traduit le préfixe de page de $titre selon la langue du wiki correspondant à $prefixe.
  $pos = strpos($titre,':');
  if($pos === false){ return $titre; }
  $namespace = substr($titre,0,$pos);
  $reste = substr($titre,$pos);
  switch($prefixe){
    case 'es':
      switch($namespace){
        case 'Discussion': $namespace = 'Conversación acerca de'; break;
        case 'Utilisateur': $namespace = 'Usuario'; break;
        case 'Discussion utilisateur': $namespace = 'Mensajes de usuario'; break;
        case 'Discussion Vikidia': $namespace = 'Conversación acerca de Vikidia'; break;
        case 'Fichier': $namespace = 'Archivo'; break;
        case 'Discussion fichier': $namespace = 'Conversación acerca de imagen'; break;
        case 'MediaWiki': $namespace = 'Mensaje MediaWiki'; break;
        case 'Discussion MediaWiki': $namespace = 'Conversación acerca de mensaje MediaWiki'; break;
        case 'Modèle': $namespace = 'Plantilla'; break;
        case 'Discussion modèle': $namespace = 'Conversación acerca de plantilla'; break;
        case 'Aide': $namespace = 'Ayuda'; break;
        case 'Discussion aide': $namespace = 'Conversación acerca de ayuda'; break;
        case 'Catégorie': $namespace = 'Categoría'; break;
        case 'Discussion catégorie': $namespace = 'Conversación acerca de categoría'; break;
        case 'Projet': $namespace = 'Proyecto'; break;
        case 'Discussion Projet': $namespace = 'Conversación de proyecto'; break;
        case 'Portail': $namespace = 'Portal'; break;
        case 'Discussion Portail': $namespace = 'Conversación de portal'; break;
        case 'Discussion Quiz': $namespace = 'Conversación acerca de quiz'; break;
        default: break;
      }
      break;
    case 'nl':
      switch($namespace){
        case 'Discussion': $namespace = 'Overleg'; break;
        case 'Utilisateur': $namespace = 'Gebruiker'; break;
        case 'Discussion utilisateur': $namespace = 'Overleg gebruiker'; break;
        case 'Vikidia': $namespace = 'Wikikids'; break;
        case 'Discussion Vikidia': $namespace = 'Overleg Wikikids'; break;
        case 'Fichier': $namespace = 'Bestand'; break;
        case 'Discussion fichier': $namespace = 'Overleg bestand'; break;
        case 'Discussion MediaWiki': $namespace = 'Overleg MediaWiki'; break;
        case 'Modèle': $namespace = 'Sjabloon'; break;
        case 'Discussion modèle': $namespace = 'Overleg sjabloon'; break;
        case 'Aide': $namespace = 'Help'; break;
        case 'Discussion aide': $namespace = 'Overleg help'; break;
        case 'Catégorie': $namespace = 'Categorie'; break;
        case 'Discussion catégorie': $namespace = 'Overleg categorie'; break;
        case 'Projet': $namespace = 'Widget'; break;
        case 'Discussion Projet': $namespace = 'Widget talk'; break;
        case 'Portail': $namespace = 'WikiForum'; break;
        case 'Discussion Portail': $namespace = 'Overleg WikiForum'; break;
        default: break;
      }
      break;
  }
  return $namespace.$reste;
}
?>
