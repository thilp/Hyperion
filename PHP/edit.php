<?php
require_once "connexion.php";

function getJeton_edit(){
// Récupère le jeton d’édition de la session.
  $req = requeteAPI('format=xml&action=query&prop=info&intoken=edit&titles=T');
  if ( preg_match('~edittoken="([^"]+)"~',$req,$tab) ) return urlencode($tab[1]);
  else {
    echo 'Erreur : jeton d’édition inaccessible. Réponse de l’API : '.htmlspecialchars($req).'.';
    exit;
  }
}
if ( ! isset($jetonEdit) ) $jetonEdit = getJeton_edit();

function edit($page,$texte,$resume,$bot='',$prefixe='fr') {
  // Gestion du botflag
  if ( $bot = 'bot' ) $bot = '&bot=1';
  else $bot = '';
  
  // Jeton d’édition
  global $jetonEdit;
  
  // Requête d’édition
  $post = 'action=edit&title='.urlencode($page).'&text='.urlencode($texte).'&token='.$jetonEdit.'&summary='.urlencode($resume).'&notminor=true'.$bot.'&format=xml';
  $reponse = requeteAPI($post,$prefixe);
  
  // Traitement de la réponse
  if (strpos($reponse,'result="Success"')) {$retour = true;}
  else {
    $retour=false; $log='Erreur : ';
    preg_match('#code="([^"]+)"#',$reponse,$code);
    switch($code[1]) { // Correspondances des codes d’erreur
      case 'notitle':$log.='titre manquant'; break;
      case 'notext':$log.='l’un des paramètres "text", "appendtext", "prependtext" ou "undo" est manquant'; break;
      case 'notoken':$log.='jeton "token" manquant'; break;
      case 'protectedtitle':$log.='le titre est protégé contre la création'; break;
      case 'cantcreate':$log.='permissions insuffisantes pour créer de nouvelles pages'; break;
      case 'articleexists':$log.='l’article est déjà créé'; break;
      case 'noimageredirect':$log.='permissions insuffisantes pour créer des redirections d’images'; break;
      case 'spamdetected':$log.='édition refusée pour cause de spam'; break;
      case 'contenttoobig':$log.='dépassement de la taille limite d’édition'; break;
      case 'noedit':$log.='permissions insuffisantes pour éditer des pages'; break;
      case 'pagedeleted':$log.='page supprimée à l’instant ou interdiction de créer de nouvelles pages'; break;
      case 'emptypage':$log.='créer des pages vides n’est pas autorisé'; break;
      case 'editconflict':$log.='conflit d’édition détecté'; break;
      default:$log='Erreur non répertoriée : '.$code[1];
    }
    $log.='.'; }
  if($retour){ return true; }
  else { return $log; }
}

function ajoutTexte($page,$texte,$resume,$bot='',$prefixe='fr'){
  $preTexte = lectureArticle($page,$prefixe);
  return edit($page,"$preTexte\n$texte",$resume,$bot,$prefixe);
}
function ajoutTexte_debut($page,$texte,$resume,$bot='',$prefixe='fr'){
  $preTexte = lectureArticle($page,$prefixe);
  return edit($page,"$texte\n$preTexte",$resume,$bot,$prefixe);
}
function ajoutTexte_avantcategs($page, $texte, $resume, $bot='bot', $prefixe='fr'){
// Place $texte dans $page avant les catégories (un saut de ligne d’écart) et juste après les modèles existants (sans saut de ligne) ou le texte si pas de modèle existant (un saut de ligne).
  $preTexte = lectureArticle($page,$prefixe);
  $n = "\n";
  preg_match('~\[\[(?:wp|es|nl|en|de):~i',$preTexte,$tabMatch);
  $tinterw = stristr($preTexte,$tabMatch[0]);
  $tcateg = stristr($preTexte,'[[Catégorie:');
  $tcledetri =  stristr($preTexte,'{{CLEDETRI:');
  if(strlen($tcateg)>strlen($tcledetri)){
    if(strlen($tcateg)>strlen($tinterw)){ $tfin = $tcateg; }
    else{ $tfin = $tinterw; }
  }
  else{
    if(strlen($tcledetri)>strlen($tinterw)){ $tfin = $tcledetri; }
    else{ $tfin = $tinterw; }
  }
  $tdeb = substr($preTexte,0,strlen($preTexte)-strlen($tfin));
  $tampon = $tdeb;  
  do{
    $dern_ret = strrpos($tampon,"\n");
    $dern_ligne = substr($tampon,$dern_ret);
    $tampon = substr($tampon,0,$dern_ret);
  }while($tampon!='' && !isset($dern_ligne[1]));
  if($dern_ligne[1]=='{'){ return edit($page, $tampon.$dern_ligne.$n.$texte.$n.$n.$tfin, $resume, $bot, $prefixe); }
  else{ return edit($page, $tampon.$dern_ligne.$n.$n.$texte.$n.$n.$tfin, $resume, $bot, $prefixe); }
}

function ajoutSection($page,$titre,$niveau,$texte,$resume,$bot='',$prefixe='fr'){
  $titre = str_pad('',$niveau,'=').' '.$titre.' '.str_pad('',$niveau,'=');
  $chapo = "\n$titre\n";
  return ajoutTexte($page,$chapo.$texte,$resume,$bot,$prefixe);
}

function notificationErreur($message,$prefixe='fr'){
  try {
    ajoutSection('Utilisateur:LAURA/Activité','Notification d’erreur',2,$message,'Notification d’erreur.','pas bot',$prefixe);
  } catch(Exception $e){
    file_put_contents('mesSystM.is','<is><propriétés type="notification erreur" date="'.date('d/m/Y H:i:s').'" /><contenu>'.$message.'</contenu><notes>Ce message n’a pu être publié sur Utilisateur:LAURA/Activité en raison de l’exception suivante : '.$e.'.</notes></is>',FILE_APPEND);
  }
}

function retour_a_la_version($titre,$numero_version,$resume,$bot='',$prefixe='fr'){
// Rétablit la version $numero_version de la page $titre.
  // 1. Gère le botflag.
  if($bot=='bot') $bot='&bot=1';
  else $bot='';
  // 2. Récupère le jeton d’édition.
  global $jetonEdit;
  // 3. Récupère le numéro de la dernière version de la page.
  preg_match('~lastrevid="(\d+)"~',requeteAPI('format=xml&action=query&prop=info&titles='.$titre,$prefixe),$tab);
  // 4. Effectue la révocation.
  $retour = requeteAPI('format=xml&action=edit&title='.$titre.'&undo='.$tab[1].'&undoafter='.$numero_version.'&token='.$jetonEdit.'&summary='.$resume.$bot,$prefixe);
  // 5. Retourne le résultat de l’opération.
  if(strpos($retour,'result="Success"')!== false) return true;
  return false;
}

function alerter_en_resume($page,$message,$prefixe){
  return ajoutTexte($page,"<!-- -->",'# MESSAGE # : '.$message,$prefixe);
}

function message_avertissement($params,$prefixe){
  /* $params est un tableau contenant les champs suivants :
     - "type" d’avertissement (plagiat, vandalisme) ;
     - "niveau" : usage des avertissements du niveau 0 jusqu’au niveau 3 ;
     - "article" en cause ;
     - "date" de la modification en cause ;
     - "numero" de la modification (pour la repérer dans le journal d’activité de Laura sur Vikidia) ;
     - "motifs" de l’avertissement (description) ;
     - "pseudo"nyme de l’utilisateur ;
     - (si type=plagiat) "pourcentage" de certitude ;
     - (si type=plagiat) "article_wp" apparenté.
   */
  switch($params['type']){
    case 'plagiat':
      $titre = "LAURA a détecté une copie de Wikipédia !";
      $texte_article = "{{Utilisateur:LAURA/1/Plagiat/Article|${params['article_wp']}|${params['pourcentage']}}}";
      $texte = "{{Utilisateur:LAURA/1/Plagiat/PdD|${params['article_wp']}|${params['pourcentage']}|${params['article']}}}";
      $resume = "notification : détection de plagiat sur ${params['article']} (${params['pourcentage']} %)";
      ajoutTexte_debut($params['article'],$texte_article,$params['motifs'],$prefixe);
      break;
    case 'vandalisme':
      $titre = "LAURA a annulé ta modification";
      //$date = lauradate();
      $texte = "{{Utilisateur:LAURA/1/Revert${params['niveau']}|${params['article']}|${params['motifs']}|${params['numero']}}}\n~~~~";
      $resume = "notification : révocation";
      break;
    default:
      return false;
  }
  return ajoutSection('Discussion utilisateur:'.$params['pseudo'],$titre,2,$texte,$resume,'bot',$prefixe);
}
?>