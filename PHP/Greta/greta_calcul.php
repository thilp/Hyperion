<?php

/* Fonctions appelées par greta.php */

// Durée maximale d’exécution du script (en secondes)
set_time_limit(10*60);

// Chargement des modules
require_once "edit.php";

// Fonctions annexes *************************************

function autorisation_ip($ip){
  // Vérifie l’existence d’un verrou exclusif pour $ip
  // Si verrou, renvoie False
  // Sinon, crée le verrou et renvoie True
  if(file_exists('verrou_'.$ip)){
    return false; }
  else{ // Si PAS de verrou
    if(file_put_contents('verrou_'.$ip,'') !== false){
      return true; }
    else{ return false; }
  }
}
function annule_verrouillage_ip($ip){
  // Supprime le fichier de verrou exclusif correspondant à $ip s’il existe
  if(file_exists('verrou_'.$ip)){
    unlink('verrou_'.$ip); }
  return true;
}
function notification_erreur($chaine){
  // Affiche (par AJAX) un message d’erreur comportant $chaine
  // puis interrompt l’exécution
  global $pseudo;
  echo '<br /><strong>Une erreur est survenue :</strong> '.$chaine.' ; <strong>interruption du programme.</strong>';
  annule_verrouillage_ip($pseudo);
  exit;
}


// Fonctions générales ***********************************
function recherche_remplacement($rech,$rempl,&$nb_erreurs=0){
  $retour = requeteAPI('action=query&list=search&srwhat=text&srsearch="'.$rech.'"&srnamespace=0&srredirects&srlimit=500&format=xml');
  preg_match_all('# title="([^"]+)" snippet="[^"]#',$retour,$tabTitres,PREG_PATTERN_ORDER);
  $tabTitres = $tabTitres[1];
  $nb_pages = 0;
  $nb_erreurs = 0;
  foreach($tabTitres as $titre){
    $texte = lectureArticle($titre);
    $n_texte = str_replace(' '.$rech.' ',' '.$rempl.' ',$texte);
    $edit = edit($titre,$n_texte,'Remplacement de « '.$rech.' » par « '.$rempl.' » avec [[User:Greta GarBot|Greta GarBot]].');
    if($edit === true){
      $nb_pages++; }
    else { echo '<br />Impossible d’éditer l’article <em>'.$titre.'</em> ! Vikidia a renvoyé :<br /><tt>'.$edit.'</tt>'; $nb_erreurs++; }
  }
  return $nb_pages;
}

function correction($texte,$ctypo,$cffrappe,$cortho,$csyntaxe){  
  // Découpage du texte pour éviter les liens, les fichiers et les modèles.
  // Délimiteurs : [[(?:Fichier|Image):([^[\]]*([[.+]])*)+]]
  $tabDecoupage = preg_split('#\[\[(?:Fichier|Image|Catégorie|:?wp|:?en|:?nl|:?es|:?de|:?wikt):([^[\]]*(\[\[[^\]]+\]\])*)+\]\]|\{\{(?!a[vp]jc)([^{}]*(\{\{[^}]\}\})*)+\}\}|\[\[[^\]]*\]\]|\[http:[^\]]+\]#i',$texte,0,PREG_SPLIT_OFFSET_CAPTURE|PREG_SPLIT_NO_EMPTY);
  // Transformation en tableau plus agréable
  $tabDecoupage2 = array();
  foreach($tabDecoupage as $morceau){
    $num = $morceau[1];
    $tabDecoupage2[$num] = $morceau[0];
  }
  // Récupération des « délimiteurs » (liens, modèles, fichiers, etc.) dans $tabRebut
  // ET application des corrections à chaque morceau de texte de $tabDecoupage2
  if($tabDecoupage[0][1] > 0){ $bloc = 0; }
  else{ $bloc = strlen($tabDecoupage[0][0]);}
  $tabRebut = array();
  foreach($tabDecoupage2 as $pos => $morceau){
    if($pos != $bloc){
      $tabRebut[$bloc] = substr($texte,$bloc,$pos-$bloc);
      $bloc = $pos+strlen($morceau);
    }
    if($cffrappe){ correction_fautes_frappe_locale($tabDecoupage2[$pos]); }
    if($ctypo){ correction_typographique_locale($tabDecoupage2[$pos]); }
    if($cortho){ correction_orthographique_locale($tabDecoupage2[$pos]); }
    if($csyntaxe){ correction_syntaxique_locale($tabDecoupage2[$pos]); }
  }
  // Récupération d’un éventuel rebut en fin de fichier
  $kmax_d = max(array_keys($tabDecoupage2));
  $kmax_r = $kmax_d + strlen($tabDecoupage2[$kmax_d]);
  $tabRebut[$kmax_r] = substr($texte,$kmax_r);
  // Réunion du texte corrigé et des délimiteurs
  $tabGeneral = $tabDecoupage2 + $tabRebut;
  // Mise en ordre (par offset)
  ksort($tabGeneral);
  // Régénération d’une chaine
  $nouv_texte = implode('',$tabGeneral);
  // Application des corrections globales
  if($ctypo){ correction_typographique_globale($nouv_texte); }
  if($csyntaxe){ correction_syntaxique_globale($nouv_texte); }
  // Retour
  return $nouv_texte;
}

// Réception des données et traitement *******************
if(isset($_POST['op'])){
  $op = htmlspecialchars($_POST['op']);
  switch($op){

    // ***************************************************
  case 'typo': // Si opération de correction typographique
    //exit;
    require_once "greta_typo.php";

    // Verrouillage du pseudo et préparation
    if(!isset($_POST['pseudo']) || !isset($_POST['mdp'])){
      notification_erreur('probable erreur de transmission (aucun pseudo ni mot de passe reçu)'); }
    $pseudo = htmlspecialchars($_POST['pseudo']);
    unset($_POST['pseudo']);
    if(!isset($_POST['titre'])){
      notification_erreur('probable erreur de transmission (aucun titre d’article reçu)'); }
    if(!autorisation_ip($pseudo)){
     notification_erreur('ne lancez qu’une procédure de recherche-remplacement à la fois'); }
    $titre = $_POST['titre'];
    if(isset($_POST['c_typo']) && $_POST['c_typo'] === '1'){ $ctypo = true; }else{ $ctypo = false; }
    if(isset($_POST['c_ffrappe']) && $_POST['c_ffrappe'] === '1'){$cffrappe = true;}else{$cffrappe=false;}
    if(isset($_POST['c_ortho']) && $_POST['c_ortho'] === '1'){ $cortho = true; }else{$cortho = false;}
    if(isset($_POST['c_syntaxe'])&&$_POST['c_syntaxe']==='1'){$csyntaxe = true;}else{$csyntaxe = false;}
    if(!$ctypo && !$cffrappe && !$cortho && !$csyntaxe){
      notification_erreur('aucune option de correction activée'); }

    // Connexion de l’utilisateur
    $mdp = htmlspecialchars($_POST['mdp']);
    unset($_POST['mdp']);
    if(!connexion_greta($pseudo,$mdp)){
      unset($mdp);
      notification_erreur('impossible de se connecter à Vikidia (erreur dans le pseudo ou le mot de passe ?)'); }
    echo '<br />Greta GarBot est désormais connectée à Vikidia en tant que '.$pseudo.'.';
    unset($mdp);

    $infos = requeteAPI('action=query&meta=userinfo&uiprop=blockinfo|hasmsg&format=xml');
    if(strpos($infos,' blockedby=') !== false){
      requeteAPI('action=logout');
      echo '<br />Le compte d’utilisateur <em>'.$pseudo.'</em> faisant l’objet d’un blocage, il n’est pas possible de l’utiliser pour la correction typographique dans les articles ! Je m’en suis donc déconnectée.';
      annule_verrouillage_ip($pseudo);
      exit; }
    if(strpos($infos,' hasmsg') != false){
      echo '<br />(Note : vous avez de nouveaux messages sur Vikidia !)'; }

    // Démarrage de la procédure de correction typographique
    $titre = ucfirst(htmlspecialchars($_POST['titre']));
    if(!resolution_existence_page_et_redirection($titre)){
      echo '<br />Désolée, l’article <em>'.$titre.'</em> n’existe pas sur Vikidia ! Vérifiez qu’il ne s’agit pas d’une page de redirection en cliquant sur <a href="http://fr.vikidia.org/wiki/'.$titre.'" title="Page '.$titre.' sur Vikidia">ce lien</a>.';
      annule_verrouillage_ip($pseudo);
      exit;}
    $texte = lectureArticle($titre);
    $texte_corrige = correction($texte,$ctypo,$cffrappe,$cortho,$csyntaxe);
    $operations = array();
    if($ctypo){$operations[0] = 'typographie';}
    if($cffrappe){$operations[1] = 'fautes de frappe';}
    if($cortho){$operations[2] = 'orthographe';}
    if($csyntaxe){$operations[3] = 'syntaxe';}
    $operations = implode(', ',$operations);
    if($texte != $texte_corrige){
      $edit = edit($titre,$texte_corrige,'Correction ('.$operations.') par [[User:Greta GarBot|Greta GarBot]]');
      if($edit === true){
	echo '<br />L’article <em>'.$titre.'</em> a bien été corrigé sur Vikidia !';
      }
      else{ echo '<br />'.$edit; notification_erreur('la publication sur Vikidia a échoué'); }
    }
    else{
      echo '<br />Je n’ai détecté aucune erreur dans la version actuelle de l’article <em>'.$titre.'</em>.<br /><span style="font-size:small">Si jamais je m’étais trompée ou avais oublié une faute, je vous serais reconnaissante de le signaler à thilp afin qu’il améliore mes filtres en conséquence.</span>';
    }
  
    // Sortie du programme correctement exécuté
    annule_verrouillage_ip($pseudo);
    echo '<br />La procédure s’est correctement déroulée. Au revoir !';
    break;

    // ***********************************************
  case 'rr': // Si opération de recherche-remplacement
    
    // Protection du pseudo et préparation
    if(!isset($_POST['pseudo']) || !isset($_POST['mdp'])){
      notification_erreur('probable erreur de transmission (pas de pseudo ou de mot de passe reçu)'); }
    $pseudo = htmlspecialchars($_POST['pseudo']);
    unset($_POST['pseudo']);
    if(!isset($_POST['rech']) || !isset($_POST['rempl'])){
      notification_erreur('probable erreur de transmission (aucun motif de recherche-remplacement reçu)'); }
    if(!autorisation_ip($pseudo)){
     notification_erreur('ne lancez qu’une procédure de recherche-remplacement à la fois'); }
    $recherche = $_POST['rech'];
    $remplacement = $_POST['rempl'];

    // Connexion de l’utilisateur
    $mdp = htmlspecialchars($_POST['mdp']);
    unset($_POST['mdp']);
    if(!connexion_greta($pseudo,$mdp)){
      unset($mdp);
      notification_erreur('impossible de se connecter à Vikidia (erreur dans le pseudo ou le mot de passe ?)'); }
    echo '<br />Greta GarBot est désormais connectée à Vikidia en tant que '.$pseudo.'.';
    unset($mdp);
    echo '<br />Mot de passe oublié.';

    $infos = requeteAPI('action=query&meta=userinfo&uiprop=blockinfo|hasmsg&format=xml');
    if(strpos($infos,' blockedby=') !== false){
      requeteAPI('action=logout');
      echo '<br />Le compte d’utilisateur <em>'.$pseudo.'</em> faisant l’objet d’un blocage, il n’est pas possible de l’utiliser pour des recherches-remplacements dans les articles ! Je m’en suis donc déconnectée.';
      annule_verrouillage_ip($pseudo);
      exit; }
    if(strpos($infos,' hasmsg') != false){
      echo '<br />(Note : vous avez de nouveaux messages sur Vikidia !)'; }
    
    // Recherche-remplacement
    $nb_pages = recherche_remplacement($recherche, $remplacement,$nb_erreurs);

    //Conclusion
    requeteAPI('action=logout');
    annule_verrouillage_ip($pseudo);
    if($nb_pages > 1){
      $nb_pages = $nb_pages.' pages ont été vérifiées'; }
    else{
      $nb_pages = $nb_pages.' page a été vérifiée'; }
    if($nb_erreurs > 1){
      $nb_erreurs = $nb_erreurs.' erreurs'; }
    else{
      $nb_erreurs = $nb_erreurs.' erreur'; }
    echo '<br />La procédure s’est correctement déroulée : '.$nb_pages.' ('.$nb_erreurs.'). Je me suis bien déconnectée de votre compte d’utilisateur. Au revoir !';
    break;
  default:
    echo "requête mal formulée. Annulation";
  }
}

?>
