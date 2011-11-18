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
  $ua->from('*******@*********');
# END GLOBAL

sub requeteAPI
{
  my @params = split(/#&/, $_[0]);
  my %params = ('format' => 'xml');

  foreach my $champ (@params)
  {
    my ($attr,$val) = split(/#=/, $champ);
    $params{$attr} = $val;
  }
  my $rep = $ua->post(
    'http://fr.vikidia.org/w/api.php',
    Content_Type => 'application/x-www-form-urlencoded',
    Content => \%params
   );
  die $rep->status_line unless $rep->is_success;
  return $rep->content;
}

sub lectureArticle
{
  my ($retour) = (requeteAPI('action#=query#&prop#=revisions#&rvprop#=content'.
    '#&titles#='.$_[0]) =~ /<rev xml:space="preserve">(.*)<\/rev>/s);
  return decode_entities($retour) if (defined($retour));
  return "";
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
