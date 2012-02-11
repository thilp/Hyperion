<?php
require_once "base.php";

  // Les fonctions de délinéarisation pallient l’impossibilité d’utiliser unserialize().
function connect_xml2array_1 ($chaine)
{
  $nb = preg_match (
    '#<login result="([^"]+)" token="([0-9a-f]{32})" cookieprefix="[^"]+" '.
    'sessionid="[^"]+" />#', $chaine, $tab
  );
  if (!$nb)
    return false;
  return array(
    'result' => "${tab[1]}",
    'token' => "${tab[2]}"
  );
}

function connect_xml2array_2 ($chaine)
{
  $nb = preg_match (
    '#<login result="([^"]+)"(?: lguserid="\d+" lgusername="[^"]+" '.
    'lgtoken="([0-9a-f]{32})" cookieprefix="[^"]+" sessionid="[a-z0-9]+")? />#',
    $chaine, $tab
  );
  if (!$nb)
    return false;
  if (!isset ($tab[2]))
    return array('result' => $tab[1]);
  return array(
    'result' => "${tab[1]}",
    'token' => "${tab[2]}"
  );
}

function connexion ($pseudo, $mdp, &$motif, $prefixe = 'fr')
{
  $retour = connect_xml2array_1 (
    requeteAPI (
      'action=login&format=xml&lgname='.$pseudo.'&lgpassword='.$mdp,
      $prefixe
    )
  );

  $retour = connect_xml2array_2 (
    requeteAPI (
      'action=login&lgname='.$pseudo.'&lgpassword='.$mdp.'&lgtoken='.
      $retour['token'].'&format=xml',
      $prefixe
    )
  );

  $resultat = $retour['result'];
  $motif = $resultat;
  return $resultat == 'Success';
}
?>
