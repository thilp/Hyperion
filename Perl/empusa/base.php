<?php
// Fonction de requête URL
function cURL($url, $cook, $post)
{
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
  curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
  curl_setopt($ch, CURLOPT_HTTPHEADER,
    Array('Content-type: application/x-www-form-urlencoded',
    'User-Agent: Mozilla/5.0 (Windows; U; Windows NT 6.1; fr; rv:1.9.2) '.
    'Gecko/20100115 Firefox/3.6'));
  curl_setopt ($ch, CURLOPT_COOKIEJAR, $cook);
  curl_setopt ($ch, CURLOPT_COOKIEFILE, $cook);
  $reponse = curl_exec($ch);
  curl_close($ch);
  return $reponse;
}

function requeteAPI ($params, $interwiki = 'fr')
{
  $cook = 'cookie_'.$interwiki.'.is';
  switch ($interwiki)
  {
    case 'fr': return cURL('https://fr.vikidia.org/w/api.php',$cook,$params);
    case 'commons': return cURL('http://commons.wikimedia.org/w/api.php',$cook,$params);
    case 'wp': return cURL('http://fr.wikipedia.org/w/api.php',$cook,$params);
    case 'nl': return cURL('http://wikikids.wiki.kennisnet.nl/api.php',$cook,$params);
    case 'es': return cURL('http://es.vikidia.org/w/api.php',$cook,$params);
    case 'de': return cURL('http://grundschulwiki.zum.de/api.php',$cook,$params);
    case 'en': return cURL('http://simple.wikipedia.org/w/api.php',$cook,$params);
    default: return false;
  }
}
?>
