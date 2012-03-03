#! /usr/bin/perl -w

# ---------------------------------------------.
#  * base.pl                                   !
# Low-level functions for LAURA robots in Perl !
# Written (using vim) in 2011 by thilp         !
#   (http://fr.vikidia.org/wiki/User:thilp)    !
# Code under GPL 3, please keep this box.      !
# ---------------------------------------------'

use strict;
use LWP::UserAgent;
use HTTP::Cookies;
use HTML::Entities;

#### HTTP HEADER ##
# Describes the header of each HTTP request made by the script
# Décrit l'en-tête de chaque requête HTTP faite par le script
my $ua = LWP::UserAgent->new(
  agent => 'Hyperion/4.1.perl (thilp/bot/lol)', # User-Agent
  cookie_jar => HTTP::Cookies->new(
    file => 'cookies.txt', # where to save the cookie?
    autosave => 1,
    keep_alive => 1,
    ignore_discard => 1
  )
);
$ua->timeout(10);


#### INTERNATIONAL MODE ##
# Activate if the script must support other API than the fr.vikidia.org's one
# Activer si le script doit supporter d'autres API que celles de fr.vikidia.org
# (0:off; 1:on)
my $international_mode = 1;


#### API_GET () ##
# Main function for interacting with the MediaWiki API
# Fonction principale d'interaction avec l'API de MediaWiki
# $response = api_get (%post_fields) with:
#	%post_fields = ($attribute_1 => $value_1,
#			$attribute_2 => $value_2,
#			...)
sub api_get
{
  my %args = @_;
  $args{'format'} = 'xml';

  my $api = 'http://fr.vikidia.org/w/api.php';

  if ($international_mode)
  {
    my $prefix = 'fr';

    $prefix = $args{'prefix'} if (exists ($args{'prefix'}));

    $api = 'http://wikikids.wiki.kennisnet.nl/api.php' if ($prefix eq 'nl');
    $api = 'http://es.vikidia.org/w/api.php' if ($prefix eq 'es');
    $api = 'http://simple.wikipedia.org/w/api.php' if ($prefix eq 'en');
    $api = 'http://grundschulwiki.zum.de/api.php' if ($prefix eq 'de');
    if ($prefix =~ /^wp_(\w\w)$/)
    {
      $api = 'http://'.$1.'.wikipedia.org/w/api.php';
    }
    if ($prefix =~ /^wikt_(\w\w)$/)
    {
      $api = 'http://'.$1.'.wiktionary.org/w/api.php';
    }
  }
  delete ($args{'prefix'});

  %args = map ( { encode_entities ($_) } %args);

  my $attempts = 10;
  my $rep;
  while ($attempts)
  {
    $rep = $ua->post (
      $api,
      Content_Type => 'application/x-www-form-urlencoded',
      Content => \%args
    );
    print((11 - $attempts).". Error: ".$rep->status_line."\n")
      unless $rep->is_success;
    $attempts-- unless $rep->is_success;
    $attempts = 0 if $rep->is_success;
  }
  return decode_entities ($rep->content);
}


#### CONNEXION () ##
# Connects the script to an user's account, allowing it to behave as this user
# Connecte le script à un compte, l'autorisant à agir au nom de l'utilisateur
# $success = connexion ($user, $password, [$prefix, [$result]]) with:
#	$success: 1 if success of the operation, 0 if fail;
#	$user: the account login;
#	$password: the account password;
#	$prefix: (optionnal) if $international_mode enabled, prefix of
#	  the targeted wiki;
#	$result: (optionnal) reference to store the API answer.
sub connexion
{
  my ($pseudo,$mdp,$prefix,$ref_result) = @_;
  my ($result,$jeton) = (api_get (
      'action' => 'login',
      'lgname' => $pseudo,
      'prefix' => $prefix
    ) =~ /\bresult="([^"]+)"(?: token="([0-9a-f]{32})")?/);

  if (!$jeton)
  {
    $$ref_result = $result;
    return 0;
  }
  ($result) = (api_get (
      'action' => 'login',
      'lgname' => $pseudo,
      'lgpassword' => $mdp,
      'lgtoken' => $jeton,
      'prefix' => $prefix
    ) =~ /\bresult="([^"]+)"/);
  $$ref_result = $result;

  return $result eq 'Success';
}


#### ARTICLE_GET () ##
# Returns the body of the page
# Retourne le corps de la page
# $body = article_get ($title, [$prefix]) with:
#	$body: the page content, as a string;
#	$title: the title of the page you want to read;
sub article_get
{
  my ($retour) = (api_get (
      'action' => 'query',
      'prop' => 'revisions',
      'rvprop' => 'content',
      'titles' => $_[0],
      'prefix' => $_[1]
    ) =~ /<rev xml:space="preserve">(.*)<\/rev>/s);
  return $retour if (defined($retour));
  return "";
}


#### EXISTS_OR_REDIRECTS () ##
# Checks if a page exists and if it is only a redirection page
# Teste si une page existe et s'il s'agit d'une page de redirection
# $exists = exists_or_redirects ($title, [$prefix, [$redir, [$redir_page]]])
# with:	$exists: 1 if the page exists, 0 if it does not;
#	$title: the title of the page we check;
#	$redir: reference to a scalar that is equal to 1 if the page is
#	  a redirection page;
#	$redir_page: reference to a string that contains, if $redir = 1, the
#	  title of the page pointed by the redirection.
sub exists_or_redirects
{
  my ($title, $prefix, $ref_is_redirect, $ref_page_redirect) = @_;

  my $retour = api_get (
    'action' => 'query',
    'prop' => 'revisions',
    'titles' => $title,
    'rvprop' => 'content',
    'prefix' => $prefix);
  if ($retour !~ / missing="" \/><\/pages>/)
  {
    if (defined($ref_is_redirect) and $retour =~
      /(?:REDIRECT(?:ION)?|DOORVERWIJZING|REDIRECCIÓN)\s*\[\[([^\]]+)\]\]/)
    {
      $$ref_is_redirect = 1;
      $$ref_page_redirect = $1;
    }
    return 1;
  }
  return 0;
}

# Necessary to allow inclusion by the "require" keyword
# Nécessaire pour pouvoir inclure cette page avec le mot-clef "require"
return 1;
