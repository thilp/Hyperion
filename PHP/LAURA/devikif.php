<?php
require_once('connexion.php');
require_once('edit.php');

set_time_limit(5*60);

// Connexion à Vikidia
$connect = connexion();
if($connect !== true){ exit; }

// Fonctions auxilliaires
function erp($chaine){ // Protège les caractères spéciaux pour le passage dans une expression rationnelle : quotemeta + |
  return addcslashes(quotemeta($chaine),'|');
}

function page_au_hasard($nombre=1,$taille_limite=0){ // Renvoie le titre d’une page de l’espace de nom principal, ou un tableau de tels titres, choisis au hasard.
  if($taille_limite == 0){
    if(!preg_match_all('#title="([^"]+)"#',requeteAPI('action=query&list=random&rnnamespace=0&rnlimit='.$nombre.'&format=xml'),$tabTitres)){
      echo "\nErreur : impossible de récupérer un titre au hasard via API. Interruption.\n";
      exit; }
    $tabTitres = $tabTitres[1];
  }
  else{ // Si une limite maximale a été définie pour la taille des articles choisis
    $tabTitres = array();
    $i = 0;
    do{
      if(!preg_match_all('#title="([^"]+)"><revisions><rev size="(\d+)"#',requeteAPI('action=query&generator=random&grnnamespace=0&grnlimit='.$nombre.'&prop=revisions&rvprop=size&format=xml'),$tabInfos,PREG_SET_ORDER)){
        echo "\nErreur : impossible de récupérer un titre au hasard via API. Interruption.\n";
        exit; }
      foreach($tabInfos as $couple){
        if($couple[2] < $taille_limite){
          $tabTitres[$i] = $couple[1];
          $i++;
        }
      }
      if(count($tabTitres)<$nombre){
        $nombre-= count($tabTitres);
      }else{ break; }
    }while(true);
  }
  // Renvoie le titre s’il n’y en a qu’un, le tableau de titres s’il y en a plusieurs.
  if(count($tabTitres)==1){ return $tabTitres[0]; }
  else{ return $tabTitres; }
}

function liens_dans_page($titre,$limite=500){ // Renvoie un tableau des liens (uniques) vers d’autres articles trouvés dans la page.
  if(preg_match_all('#<pl ns="0" title="([^"]+)" />#',requeteAPI('action=query&prop=links&titles='.urlencode($titre).'&format=xml&pllimit='.$limite),$tabLiens_uniques)>0){
    return $tabLiens_uniques[1];}
  else{ return array(); }
}

function nettoyage_multilien_dans_page($texte,$lien){ // Renvoie $texte où seule la première occurrence de $lien est vikifiée, les autres déliées.
  if(stripos($texte,'[['.$lien.']]') == 0){ $pos_apres_premier_lien = stripos($texte,'[['.$lien.'|') + strlen($lien) + 4; }
  elseif(stripos($texte,'[['.$lien.'|') == 0){ $pos_apres_premier_lien = stripos($texte,'[['.$lien.']]') + strlen($lien) + 4; }
  else{ $pos_apres_premier_lien = min(stripos($texte,'[['.$lien.'|'),stripos($texte,'[['.$lien.']]')) + strlen($lien) + 4; }
  $sub_texte = substr($texte,$pos_apres_premier_lien);
  if( max(stripos($texte,'[['.$lien.'|'),stripos($texte,'[['.$lien.']]')) > 0 ){
    $pre_texte = substr($texte,0,$pos_apres_premier_lien);
    $sub_texte = preg_replace('~\[\[('.erp($lien).')\]\]~i','$1',$sub_texte); // Nettoyage des liens nus
    $sub_texte = preg_replace('~\[\['.erp($lien).'\|([^\]]+)\]\]~i','$1',$sub_texte); // Nettoyage des liens habillés
    return $pre_texte.$sub_texte;
  }
  else{ return $texte; }
}

function nettoyage_tous_multiliens_dans_page($page,$tabLiens_uniques,$botflag=true){ // Édite $page pour que chaque lien de $tabLiens_uniques n’y soit vikifié qu’une seule fois.
  $texte = lectureArticle($page);
  foreach($tabLiens_uniques as $lien){
    $texte = nettoyage_multilien_dans_page($texte,$lien);
  }
  if($botflag){ $bot = '1'; } else{ $bot = ''; }
  return edit($page,$texte,"Dévikification automatique des liens plusieurs fois vikifiés.",$bot);
}

function procedure_nettoyage_multiliens_randompages_1($remplacements,$taille_limite=3000){
  for($i=0; $i<$remplacements; $i++){
    $titre = page_au_hasard(1,$taille_limite);
    $tabLiens_uniques = liens_dans_page($titre);
    $retour = nettoyage_tous_multiliens_dans_page($titre,$tabLiens_uniques,false);
    if($retour!==true){return 'Arrêt de procedure_nettoyage_multiliens_randompages_1() à la page '.$titre.' (traitement n°'.$i.'). Motif : '.$retour; }
  }
  return 'Le traitement de '.$i.' pages s’est correctement déroulé. Dernière page éditée : '.$titre.'.';
}

function procedure_nettoyage_multiliens_randompages_2($remplacements,$taille_limite=3000,$bot=false){
  $tabPages = page_au_hasard($remplacements,$taille_limite);
  foreach($tabPages as $titre){
    $tabLiens_uniques = liens_dans_page($titre);
    $retour = nettoyage_tous_multiliens_dans_page($titre,$tabLiens_uniques,$bot);
    if($retour!==true){return 'Arrêt de procedure_nettoyage_multiliens_randompages_1() à la page '.$titre.' (traitement n°'.$i.'). Motif : '.$retour; }
  }
  return 'Le traitement de '.count($tabPages).' pages s’est correctement déroulé. Dernière page éditée : '.$titre.'.';
}
echo procedure_nettoyage_multiliens_randompages_2(100,3000,true);

function nettoyage_voir_aussi($nb_pages=100){
  $compteur = 0;
  $tabCompteur = array();
  for($i=0;$i<$nb_pages/20;$i++){
    $tabTitres = page_au_hasard($nb_pages-$i*20);
    foreach($tabTitres as $titre){
      $texte = lectureArticle($titre);
      if(preg_match("#={2,4}\s?(?:Voir aussi|(?:Vikiliens?|Liens? internes?)(?: ?pour compléter|complémentaires?)?|(?:Vikiliens?|Liens? internes?)?(?: ?pour compléter|complémentaires?)|Articles? (?:liés?|connexes?))\s?={2,4}\n{1,2}(?!===)#i",$texte)){
        $pos_paragraphe = max(stripos($texte,'Voir aussi'),stripos($texte,'Vikilien'),stripos($texte,'Liens internes'),stripos($texte,'Lien interne'),stripos($texte,'Pour compléter'),stripos($texte,' connexe'));
        $pos_paragraphe = strrpos(substr($texte,0,$pos_paragraphe),"\n=");
        $pre_texte = substr($texte,0,$pos_paragraphe);
        $sub_texte = substr($texte,$pos_paragraphe);
        if(preg_match('#== ?sources? ?==#i',$sub_texte)){
          $pos_source = strripos($sub_texte,'source');
          $pos_source = strrpos(substr($texte,0,$pos_source),"\n=");
          $post_texte = substr($sub_texte,$pos_source);
          $sub_texte = substr($sub_texte,0,$pos_source);
        }
        else{ $post_texte = ''; }
        preg_match_all("#\n\*[^[]*\[\[([^\]|]+)(?:\|[^\]]+)?\]\]#",$sub_texte,$tabLiens);
        foreach($tabLiens[1] as $lien){
          if(stripos($pre_texte,'[['.$lien.']') !== false || stripos($pre_texte,'[['.$lien.'|') !== false){
            $sub_texte = preg_replace('#\n\*[^[]*\[\['.$lien.'(?:\|[^\]]+)?\]\][^[\n]*\n#Ui',"\n",$sub_texte);
          }
          elseif(stripos($pre_texte,' '.$lien.' ') !== false || stripos($pre_texte,' '.$lien.', ') !== false || stripos($pre_texte,' '.$lien.'. ') !== false){
            $pre_texte = preg_replace('#\b('.$lien.')(?=. |, |\s)#i','[[$1]]',$pre_texte,1);
            $sub_texte = preg_replace('#\n\*[^[]*\[\['.$lien.'(?:\|[^\]]+)?\]\][^[\n]*\n#Ui',"\n",$sub_texte);
          }
        }
        $sub_texte = preg_replace("#\n\*[^[{]*(?=\n)#U","",$sub_texte);
        $sub_texte = preg_replace("#(?<=\n)={2,4}\s?(?:Voir aussi|(?:Vikiliens?|Liens? internes?)(?: ?pour compléter|complémentaires?)?|(?:Vikiliens?|Liens? internes?)?(?: ?pour compléter|complémentaires?)|Articles? (?:liés?|connexes?))\s?={2,4}\n(?!\*|\n\*|==)#i",'',$sub_texte);
        if($texte != $pre_texte.$sub_texte.$post_texte){
          edit($titre,$pre_texte.$sub_texte.$post_texte,"Rationalisation des liens vers des « articles connexes » : dévikification, nettoyage.",true);
          $tabCompteur[$compteur] = $titre;
          $compteur++;
        }
      }
    }
  }
  echo "terminé. Nombre d’éditions : $compteur.<br />Articles édités :<br />";
  print_r($tabCompteur);
}
?>