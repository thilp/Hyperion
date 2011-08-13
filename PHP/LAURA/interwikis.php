<?php
  /* Ce fichier comporte les fonctions utilisées pour établir les liens interwikis entre Vikidia francophone,
     Vikidia hispanophone et WikiKids (réciproquement), et de ces trois wikis en direction de Grundschulwiki,
     Simple English Wikipedia et Wikipédia dans sa version linguistique adaptée (francophone, hispanophone ou
     néerlandophone) unilatéralement. Ces fonctions sont écrites de manière abstraite et peuvent donc être
     généralisées pour servir avec n’importe quels interwikis, à condition que la fonction requeteAPI() du
     fichier base.php connaisse l’adresse des API correspondantes.

   Ce code est écrit par thilp et placé sous licence GPL 3.
  */

require_once "edit.php";

  // Variable globale de type array indexé comprenant les préfixes des interlangues pris en charge :
$GLOBALE_tabInterlangues = array('es','fr','nl');
  // Variable globale de type array indexé comprenant les préfixes des liens unilatéraux pris en charge :
$GLOBALE_tabLiensUnilateraux = array('wp','de','en');

function prefixe2nom($prefixe,$langue_site='fr'){ // Retourne la traduction du nom du wiki correspondant à $prefixe dans la langue $langue_site.
  switch($prefixe){
    case 'fr':
      switch($langue_site){
        case 'es': return 'Vikidia en francés';
        case 'nl': return 'franse Vikidia';
        default: return 'French Vikidia';
      }
    case 'wp':
      switch($langue_site){
        case 'fr': return 'Wikipédia';
        default: 'Wikipedia';
      }
    case 'nl': return 'Wikikids';
    case 'es':
      switch($langue_site){
        case 'fr': return 'Vikidia hispanphone';
        case 'nl': return 'spaanse Vikidia';
        default: 'Spanish Vikidia';
      }
    case 'en': return 'Simple English Wikipedia';
    case 'de': return 'Grundschulwiki';
    default:
      switch($langue_site){
        case 'fr': return '(préfixe wiki inconnu)';
        case 'nl': return '(wiki prefix onbekend)';
        case 'es': return '(wiki prefijo desconocidos)';
        default: return '(unknown wiki prefix)';
      }
  }
}

function article_existe($titre,$prefixe){ // Teste l’existence d’un article intitulé $titre dans le wiki correspondant à $prefixe.
  $retour = requeteAPI('action=query&prop=revisions&titles='.$titre.'&rvprop=content&format=xml',$prefixe);
  return strpos($retour,' missing=""') === false;
}

function est_a_vikifier_econome($texte,$titre_prefixe_vers,$prefixe_de,$prefixe_vers){ // Teste l’opportunité d’interwikifier un article et renvoie le titre traduit si oui, false sinon.
  return stripos($texte,'[['.$prefixe_vers.':') === false && article_existe($titre_prefixe_vers,$prefixe_vers);
}

function resume_publication_interwiki_multiples($prefixe_de,$tabInterwikis){ // Renvoie le résumé adapté pour la publication d’un interwiki vers $prefixe_vers sur le wiki correspondant à $prefixe_de.
  $chaine = '';
  foreach($tabInterwikis as $clef => $valeur){
    $chaine.= ', '.prefixe2nom($clef,$prefixe_de);
  }
  $chaine = substr($chaine,1).'.';
  switch($prefixe_de){
    case 'fr': return 'Liens interwiki automatiques vers'.$chaine;
    case 'es': return 'Automáticas interwiki enlaces con dirección a'.$chaine;
    case 'nl': return 'Automatische interwiki links naar'.$chaine;
    default: return 'Automatic interwiki links towards'.$chaine;
  }
}

function traduction_titre($titre,$tabPrefixes){ // Renvoie un tableau associant chaque préfixe de $tabPrefixes à la traduction de $titre en la langue correspondant au préfixe.
  $tabRetour = array('fr' => $titre);
  $chaine_er_prefixes = '';
  foreach($tabPrefixes as $prefixe){
    if($prefixe != 'fr'){ $chaine_er_prefixes = $chaine_er_prefixes.'|'.$prefixe; }
  }
  $chaine_er_prefixes = substr($chaine_er_prefixes,1);
  preg_match_all('#\[\[('.$chaine_er_prefixes.'):([^\]]+)\]\]#',lectureArticle_wp($titre),$tabMatches,PREG_SET_ORDER);
  foreach($tabMatches as $serie){
    $pref = $serie[1];
    $nom = $serie[2];
    if(resolution_existence_page_et_redirection($nom,$pref,$redirection,$page_redirect)){
      if($redirection){
        $tabRetour[$pref] = $page_redirect; }
      else{ $tabRetour[$pref] = $nom; }
    }
  }
  $tabRetour['wp'] = $titre;
    // Gestion des titres non trouvés avec fr.wikipédia
  $tabInterwikis_manquants = array_diff_key(array_flip($tabPrefixes),$tabRetour);
  foreach($tabInterwikis_manquants as $interwiki => $truc){
    $trad_wikt = traduction_titre_wiktionary($titre,$interwiki);
    if($trad_wikt !== ''){
      $tabRetour[$interwiki] = $trad_wikt;
    }
  }
  return $tabRetour;
}

function traduction_titre_wiktionary($titre,$prefixe_vers,$prefixe_de='fr'){ // Tente de traduire $titre (mot de la langue correspondant à $prefixe_de) dans la langue correspondant à $prefixe_vers avec les Wiktionnaires.
    // Tentative dans le Wiktionnaire francophone.
  $retour = cURL('http://fr.wiktionary.org/w/api.php','','action=query&prop=revisions&rvprop=content&format=xml&titles='.urlencode(strtolower($titre)));
  if(preg_match('#\{\{trad[+\-]*\|'.$prefixe_vers.'\|([^}]+)\}\}#U',$retour,$tabMatches)){
    return $tabMatches[1]; }
  else{ // Tentative dans le Wiktionnaire $prefixe_vers-phone.
    $retour = cURL('http://'.$prefixe_vers.'.wiktionary.org/w/api.php','','action=query&prop=revisions&rvprop=content&format=xml&titles='.urlencode(strtolower($titre)));
    switch($prefixe_vers){
      case 'de':
        if(preg_match('#\{\{'.$prefixe_vers.'\}\}: \[1\] \[\[([^\]]+)\]\]#',$retour,$tabMatches)){
          return $tabMatches[1]; }
      case 'es':
        if(preg_match('#;1: ?\[\[([^|]+)\|#',$retour,$tabMatches)){
          return $tabMatches[1]; }
      case 'nl':
        if(preg_match("~{{=${prefixe_de}[^=]*=}}.*'''$titre'''[^#]*#[^[]*\[\[([^\]]+)\]\]~iU",$retour,$tabMatches)){
          return $tabMatches[1]; }
      default: return false;
    }
  }
}

function interwikification_totale_lexico($titre){
  global $GLOBALE_tabInterlangues,$GLOBALE_tabLiensUnilateraux;
  $tabInterwikis = array_merge($GLOBALE_tabInterlangues,$GLOBALE_tabLiensUnilateraux);
    // Traduction de $titre dans les langues de $tabInterwikis.
  $tabTraductions_titre = traduction_titre($titre,$tabInterwikis);
  foreach($GLOBALE_tabInterlangues as $prefixe_de){ // Pour chaque wiki concerné par le robot...
    $titre = $tabTraductions_titre[$prefixe_de];
    $texte = lectureArticle($titre,$prefixe_de);
    $tabInterwikis_a_faire = array();
    foreach($tabInterwikis as $prefixe_vers){ // Création des interwikis nécessaires.
      if($prefixe_de != $prefixe_vers && isset($tabTraductions_titre[$prefixe_vers]) && est_a_vikifier_econome($texte,$tabTraductions_titre[$prefixe_vers],$prefixe_de,$prefixe_vers)){
        $tabInterwikis_a_faire[$prefixe_vers] = '[['.$prefixe_vers.':'.$tabTraductions_titre[$prefixe_vers].']]';
      }
    }
      // Tri des interwikis selon la méthode Paulochon : WP d’abord, ordre lexicographique pour les autres.
    ksort($tabInterwikis_a_faire,SORT_STRING);
    if(array_key_exists('wp',$tabInterwikis_a_faire)){
      $tabInterwikis_a_faire = array_merge(array('wp' => '[[wp:'.$titre.']]'),array_diff_key($tabInterwikis_a_faire,array('wp'=>true)));
    }
      // Édition de l’article.
    $chaine = '';
    foreach($tabInterwikis_a_faire as $interwiki){
      $chaine.= "\n$interwiki";
    }
    echo "\n$titre : $chaine\n";
    ajoutTexte($titre,$chaine,resume_publication_interwiki_multiples($prefixe_de,$tabInterwikis_a_faire),'bot',$prefixe_de);
  }
}

interwikification_totale_lexico('Chien');
?>