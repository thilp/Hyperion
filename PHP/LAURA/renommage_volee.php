<?php
require_once('base.php');
require_once('connexion.php');

set_time_limit(0);

function renomme($ancien_titre,$nouveau_titre,$motif){
  // Récupération du jeton
  $demande_jeton = requeteAPI('action=query&prop=info&intoken=move&titles='.urlencode($ancien_titre).'&format=xml');    
  if (preg_match('# movetoken="([^+]{32})#',$demande_jeton,$tabJeton)==0) {
    echo 'Erreur : jeton d’édition inaccessible.'; exit; }
  $jeton = $tabJeton[1].'%2B%5C';    
  // Déplacement
  return requeteAPI('action=move&from='.urlencode($ancien_titre).'&to='.urlencode($nouveau_titre).'&token='.$jeton.'&reason='.urlencode($motif).'&movetalk=1');
}

function renommage_volee(){
  $connect = connexion();
  if($connect!==true){echo $connect;}
  $retour = requeteAPI('action=query&list=allpages&apprefix=Bavardages/Semaine&apnamespace=4&aplimit=500&format=xml');
  //print_r($retour); 185 pages
  //echo "\n\n";
  preg_match_all('#title="([^"]+)"#',$retour,$tabTitres);
  foreach($tabTitres[1] as $titre){
    // Fabriquer le nouveau titre
    //Vikidia:Bavardages/Semaine 10 2008 -> Vikidia:Bavardages/2008/10
    $nouveau_titre = preg_replace('#Semaine (\d{1,2}) (\d{4})#','$2/$1',$titre);
    // Renommer
    renomme($titre,$nouveau_titre,'Réorganisation des Bavardages. Vivent l’Islande et les gâteau !');
    //if(renomme($titre,$nouveau_titre,'Réorganisation des Bavardages. Vivent l’Islande et les gâteau !') !== true){
    //  return 'erreur lors du renommage de '.$titre.' en '.$nouveau_titre.'. LAURA s’est interrompue.';
    //}
  }
  return 'tout s’est correctement déroulé ! Dodo.';
}

echo renommage_volee();
?>