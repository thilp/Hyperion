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
  $authorized = ('Astirmays', 'Macassar', 'thilp');
  $login = htmlspecialchars($_POST['login']);
  if (!in_array($login, $authorized))
    error();

  // Password check
  $pass = htmlspecialchars($_POST['pass'];
  $motif = '';
  if (!connexion($login, $pass, $motif, 'es'))
    error();

  echo "Identification réussie.";
  $tab = array();
  exec('perl perl/empusa.pl', $tab);
  foreach ($tab as $line)
    echo "$line\n";
}
else
{
?>
<div>
  <p>Pour utiliser les pouvoirs d'administrateur de Greta GarBot, vous devez
  d'abord vous identifier.</p>
  <form method="post" action="frontend.php">
    Nom d'utilisateur&nbsp;: <input type="text" name="login" required /><br />
    Mot de passe&nbsp;: <input type="password" name="pass" required /><br />
    <input type="submit" value="connexion" />
  </form>
</div>
<?php
}

?>
