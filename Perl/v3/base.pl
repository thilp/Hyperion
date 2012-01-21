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

# GLOBAL
  my $ua = LWP::UserAgent->new(
    agent => 'Hyperion/4.0.perl (thilp/bot/lol)',
    cookie_jar => HTTP::Cookies->new(
      file => 'cookies.txt',
      autosave => 1,
      ignore_discard => 1
    )
  );
  $ua->timeout(10);
  $ua->from('thilp.is@gmail.com');
# END GLOBAL

sub requeteAPI
{
  my @params = split(/#&/, $_[0]);
  my %params = ('format' => 'xml');

  my $api = 'http://fr.vikidia.org/w/api.php';
  my $prefix = $_[1];

  $api = 'http://wikikids.wiki.kennisnet.nl/api.php' if ($prefix eq 'nl');
  $api = 'http://es.vikidia.org/w/api.php' if ($prefix eq 'es');
  $api = 'http://simple.wikipedia.org/w/api.php' if ($prefix eq 'en');
  $api = 'http://grundschulwiki.zum.de/api.php' if ($prefix eq 'de');
  if ($prefix =~ /^wp_(\w\w)$/)
  {
    $api = 'http://'.$1.'.wikipedia.org/w/api.php';
  }

  foreach my $champ (@params)
  {
    my ($attr,$val) = split(/#=/, $champ);
    $params{$attr} = $val;
  }
  my $rep = $ua->post(
    $api,
    Content_Type => 'application/x-www-form-urlencoded',
    Content => \%params
   );
  die $rep->status_line unless $rep->is_success;
  return $rep->content;
}

sub lectureArticle
{
  my ($retour) = (requeteAPI('action#=query#&prop#=revisions#&rvprop#=content'.
    '#&titles#='.$_[0], $_[1]) =~ /<rev xml:space="preserve">(.*)<\/rev>/s);
  return decode_entities($retour) if (defined($retour));
  return "";
}

sub exists_or_redirects
{
  my ($title, $prefix, $ref_is_redirect, $ref_page_redirect) = @_;

  my $retour = requeteAPI('action#=query#&prop#=revisions#&titles#='.$title.
    '#&rvprop#=content', $prefix);
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

sub connexion
{
  my ($pseudo,$mdp,$ref_result) = @_;
  my ($result,$jeton) = (requeteAPI('action#=login#&lgname#='.$pseudo) =~
    /\bresult="([^"]+)"(?: token="([0-9a-f]{32})")?/);

  if (!$jeton)
  {
    $$ref_result = $result;
    return 0;
  }
  ($result) = (requeteAPI('action#=login#&lgname#='.$pseudo.'#&lgpassword#='.
    $mdp.'#&lgtoken#='.$jeton) =~ /\bresult="([^"]+)"/);
  $$ref_result = $result;

  return $result eq 'Success';
}

if (-e "cookies.txt")
{
  print "Usage du cookie déjà présent.\n";
}
else
{
  my $pass = <STDIN>;
  my ($pseudo,$mdp,$retour) = ("LAURA",chomp($pass));

  if (connexion($pseudo,$mdp,\$retour))
  {
    print "Connecté en tant que $pseudo !\n";
  }
  else
  {
    print "La connexion en tant que $pseudo a échoué : $retour.\n";
  }
}
