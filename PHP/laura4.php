<?php
require_once "edit.php";

function getHistorique($titre){
// Récupère l’historique de la page $titre et le renvoie sous forme de tableau. Champs : ([0] => masque entier ;) [1] = id ; [2] = id de la précédente ; [3] = auteur ; [4] = timestamp ; [5] = taille.
  $retour = requeteAPI('format=xml&action=query&prop=revisions&titles='.$titre.'&rvlimit=5000&rvprop=ids|timestamp|user|size');
  preg_match_all('~<rev revid="(\d+)" parentid="(\d+)" user="([^"]+)" timestamp="([0-9\-TZ:]+)" size="(\d+)" />~',$retour,$tabHist,PREG_SET_ORDER);
  return $tabHist;
}

function id_version_of_precedent_auteur($tabHist,$id){
// Retourne l’id de la dernière version de l’utilisateur précédant l’auteur de la version $id dans la page dont l’historique est codé par $tabHist. Retourne false si non défini.
  $i=0;
  while ( isset($tabHist[$i]) && $tabHist[$i][1]!=$id ) $i++;
  while ( isset($tabHist[$i]) && $tabHist[$i][1]==$id ) $i++;
  if( isset($tabHist[$i]) ) return $tabHist[$i][1];
  else return false;
}//*/

function lectureArticle_of_id ( $idPage, $prefixe='fr' ) {
// Renvoie, comme lectureArticle(), le contenu d’un article, mais ici en fonction de l’id de la version.
  $retour = requeteAPI('action=query&prop=revisions&rvprop=content&format=xml&revids='.$idPage, $prefixe);
  preg_match('#<rev xml:space="preserve">(.*)</rev>#s', $retour, $tab);
  return htmlspecialchars_decode($tab[1]);
}

function derniere_version_saine($tabHist){
// Renvoie l’id de la dernière version saine (par auteur) de la page dont l’historique est $tabHist. Vérifie (en appelant les filtres) que la dernière version de la page est saine ; si oui, renvoie son id ; si non, vérifie la dernière version de l’auteur précédent, etc., jusqu’à trouver la dernière version saine et renvoyer alors l’id de celle-ci. Renvoie -1 si la dernière version n’est pas saine et si son auteur est le seul utilisateur ayant contribué à la page concernée.
  $id = $tabHist[0][1];
  while ( true ) {
    if ( filtres($id) ) {
      $id = id_version_of_precedent_auteur($tabHist,$id);
      if ( ! $id ) return -1;
    }
    else return $id;
  }
} //*/

if(!connexion('LAURA','************************',$motif)){ echo "Pas de connexion ! Motif : $motif."; exit; }

print_r(getHistorique('Utilisateur:LAURA/1/test'));
//retour_a_la_version() FONCTIONNE
//id_version_of_precedent_auteur() FONCTIONNE

?>
