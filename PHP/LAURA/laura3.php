<?php
/* Fichier « racine » de LAURA
 *
 * LAURA doit pouvoir fonctionner de deux manières :
 * * automatiquement, en se lançant régulièrement elle-même avec cron ;
 * * semi-automatiquement, en étant lancée par un contributeur.
 * Il lui faut donc une fonction de connexion capable d’assumer cette double nécessité :
 * * agir sur Vikidia avec son propre compte de bot ;
 * * se connecter avec les identifiants d’un utilisateur pour vérifier son identité. */

require_once "edit.php";
date_default_timezone_set('GMT');

//*********************************** VARIABLES ***********************************************
$pageConfiguration = 'Utilisateur:LAURA/1/config';
$fichierUtilisateurs_de_confiance = 'utilisateurs_de_confiance.is';
$fichierDate_derniere_patrouille = 'date_derniere_patrouille.is';

//******************************* FONCTIONS ANNEXES *******************************************
/* Dernière modif :
 * si _pas d’argument_ : renvoie la date (YYYYmmddHHiiss) de dernière patrouille de LAURA ;
 * si _une chaîne_ en argument : enregistre cette chaîne comme date de dernière patrouille de LAURA;
 * si _true_ en argument : enregistre la date (YYYYmmddHHiiss) actuelle comme celle de la dernière patrouille de LAURA.
 * Utilise un fichier .is en lecture/écriture. */
function date_derniere_modif($ch=false){
  global $fichierDate_derniere_patrouille;
  if($ch===false) // Mode lecture
    return file_get_contents($fichierDate_derniere_patrouille);
  elseif($ch===true)
    file_put_contents($fichierDate_derniere_patrouille,date('YmdHis'));
  else // Mode écriture
    file_put_contents($fichierDate_derniere_patrouille,$ch);
}
// Conversion timestamp "2011-06-21T17:04:38Z" -> 20110621170438 (int)
function convert_timestamp($ch){
  return 0 + preg_replace('~[\-T:]~','',rtrim($ch,'Z'));
}
//###############################################################################################
/* FONCTION D’AUTHENTIFICATION des utilisateurs confirmés
 * arguments : $pseudo, $mot_de_passe
 * Vérifie que $pseudo se trouve bien dans le fichier des utilisateurs de confiance,
 * puis que $pseudo est bien associé à $mot_de_passe sur Vikidia (connexion et déconnexion). */
function authentification_user($pseudo,$mdp){
  global $fichierUtilisateurs_de_confiance;
  if(stripos(file_get_contents($fichierUtilisateurs_de_confiance),'#'.$pseudo.'#')===false)
    return false;
  $connect = connexion_laura($pseudo,$mdp);
  if($connect)
    requeteAPI('action=logout');
  return $connect;
}
// FONCTION D’IMPORTATION des pseudos d’utilisateurs de confiance
function importeUtilisateurs_de_confiance(){
  global $pageConfiguration, $fichierUtilisateurs_de_confiance;
  preg_match('~<pre>(.+)</pre>~Us',lectureArticle($page),$tabUsers);
  preg_match_all('~\n([\w-]+)\n~',$tabUsers[1],$tabUsers);
  foreach($tabUsers[1] as $user)
    file_put_contents($fichier,'#'.$user.'#',FILE_APPEND);
}

//###############################################################################################
/* FONCTION DE RÉCUPÉRATION DES DONNÉES de modifications récentes :
 * * chargement de la liste des personnes de confiance (dans un fichier) ;
 * RÉCUPÉRATION
 * * requête à l’API pour demander la liste des modifications effectuées entre maintenant et
 *   la date enregistrée dans un fichier "derniere_patrouille.is" (non GMT) ; les informations demandées
 *   sont : titre, nom d’utilisateur, timestamp ;
 * * récupération sous forme d’un tableau par expression rationnelle ;
 * * mise en forme du tableau : [titre] => array([auteur] => str(), [timestamp] => int/float(), [score] => int(0)) ;
 * TRIS
 * * tri des doublons : LAURA ne garde que la dernière modification de chaque page ;
 * * tri des auteurs : les modifications effectuées par les utilisateurs de confiance sont éliminées ;
 * RETOUR
 * renvoie le tableau trié. */
function fabrique_tabRC(){
  global $fichierUtilisateurs_de_confiance;
  // Chargement de la liste des personnes de confiance dans un tableau
  preg_match_all('~#([^#]+)#~U',file_get_contents($fichierUtilisateurs_de_confiance),$tabUtilisateurs_de_confiance);
  $tabUtilisateurs_de_confiance=array_merge($tabUtilisateurs_de_confiance[1]);
  // Connexion en tant que robot (pour les apihighlimits)
  if(!connexion_laura()){
    echo 'Impossible de se connecter avec le compte LAURA !<br />';
    return false;
  }
  // Récupération de la liste brute des modifications récentes
  $requete = requeteAPI('action=query&list=recentchanges&rcend='.date_derniere_modif().'&rclimit=5000&rcnamespace=0&rcprop=title|user|timestamp&format=xml');
  // Conversion en tableau
  preg_match_all('~ title="([^"]+)" user="([^"]+)"[^t/]+timestamp="([^"]+)"~',$requete,$tabTemp,PREG_SET_ORDER);
  // Transformation sous forme exploitable.
  $tabRC=array();
  foreach($tabTemp as $tabModif){
    // Il est nécessaire de filtrer dès à présent les versions d’une même page afin de n’en garder que la plus récente. (Sinon, collisions de titres.)
    $titre=htmlspecialchars_decode($tabModif[1]); $auteur=htmlspecialchars_decode($tabModif[2]); $timestamp=convert_timestamp($tabModif[3]);
    if(!array_key_exists($titre,$tabRC) || $tabRC[$titre]['timestamp']<$timestamp){ // Si cette modif est plus récente que celle de $tabRC, elle prend sa place
      $tabRC[$titre]=array('auteur'=>$auteur,'timestamp'=>$timestamp,'score'=>0);
    }
  }
  // Suppression des modifs effectuées par les utilisateurs de confiance
  foreach($tabRC as $titre=>$tabModif){
    if(in_array($tabModif['auteur'],$tabUtilisateurs_de_confiance))
      unset($tabRC[$titre]);
  }
  return $tabRC;
}
print_r(fabrique_tabRC());
?>