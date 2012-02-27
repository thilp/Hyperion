<?php

require_once "connexion.php";

function error ()
{
  echo "Erreur !";
  exit;
}

if (isset($_POST['login']) && isset($_POST['pass']))
{
  // Login check
  $authorized = array('Astirmays', 'Macassar', 'Greta GarBot');
  $login = htmlspecialchars($_POST['login']);
  if (!in_array($login, $authorized))
    error();

  // Password check
  $pass = htmlspecialchars($_POST['pass']);
  $motif = '';
  if (!connexion($login, $pass, $motif, 'es'))
    error();

  echo "Identification réussie.<br />Lancement du script...<br />";
  echo str_replace("\n", "<br />", exec('perl perl/empusa.pl'));
  echo "Fin d'exécution du script.";
}
else
{
?>
<div>
  <p>Pour utiliser les pouvoirs d'administrateur de Greta GarBot, vous devez
  d'abord vous identifier.</p>
  <form method="post" action="#">
    Nom d'utilisateur&nbsp;: <input type="text" name="login" required /><br />
    Mot de passe&nbsp;: <input type="password" name="pass" required /><br />
    <input type="submit" value="connexion" />
  </form>
</div>
<?php
}

?>
