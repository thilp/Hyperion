#! /usr/bin/perl -w

####################################################
# pur.pl: Vikidia emergency spam fighter            #
# written by thilp (fr.vikidia.org/wiki/user:thilp) #
# in Perl on March 2012                             #
####################################################

use strict;
require 'base.pl';

my @es_login = ('Greta GarBot', '');
my @fr_login = ('thilp', '');

my %logins = (
  'es' => \@es_login,
  'fr' => \@fr_login
);


#####################################################################
#####################################################################

our ($logged_user, $wiki, $block_token, $delete_token);

print "   \033[35m\033[01mThis is PURPLE v3!\033[0m\n";

foreach (keys (%logins))
{
  my $ref_logtab = $logins{$_};

  local ($logged_user, $wiki) = ($$ref_logtab[0], $_);

  print "Connecting on ".$_.".vikidia.org as ".$logged_user."...\n";
  if (connexion ($logged_user, $$ref_logtab[1], $_))
  {
    print "\033[32m".$logged_user." is now \033[01mconnected\033[0m\n";

    # Getting the tokens
    my $rep = api_get (
      'action' => 'block',
      'user' => $logged_user,
      'gettoken' => '',
      'prefix' => $wiki);
    local ($block_token) = ($rep =~ /\bblocktoken="([a-f0-9]+)\+\\"/);
    $rep = api_get (
      'action' => 'query',
      'prop' => 'info',
      'intoken' => 'delete',
      'titles' => 'User:'.$logged_user,
      'prefix' => $wiki);
    local ($delete_token) = ($rep =~ /\bdeletetoken="([a-f0-9]+)\+\\"/);
    $delete_token .= '+\\';

    act ();

    $rep = api_get ('action' => 'logout', 'prefix' => $wiki);
    print "\033[32m".$logged_user." is now \033[01mdisconnected\033[0m from ".
      $wiki.".vikidia.org\n";
  }
  else
  {
    print "\033[31m\033[01mConnexion failed!\033[0m\n";
  }
}


#####################################################################
#####################################################################


sub tim
{
  my ($year, $month, $day, $hour, $minute, $second) =
    ($_[0] =~ /\btimestamp="(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})Z"/);
  return (($year-2012)*31536000 + $month*2592000 + $day*86400 + $hour*3600 +
    $minute*60 + $second);
}

sub rc_action
{
  my ($type) = ($_[0] =~ /\btype="([^"]+)"/);
  return $type;
}

sub get_special_rc_1
{
  my $rep = api_get (
    'action' => 'query',
    'list' => 'recentchanges',
    'rcnamespace' => '0|2',
    'rcprop' => 'user|timestamp|title|sizes|loginfo',
    'rcshow' => '!anon|!redirect',
    'rclimit' => '500',
    'rctype' => 'new|log',
    'prefix' => $wiki);
  $rep =~ s/^.+<recentchanges><rc (.*)( \/>|<\/rc>)<\/recentchanges>.+$/$1/s;
  my @tab = split (/<rc /, $rep);
  return @tab;
}

sub get_special_rc_2
{
  my $rep = api_get (
    'action' => 'query',
    'list' => 'recentchanges',
    'rcnamespace' => '0',
    'rcprop' => 'user|title|comment',
    'rcshow' => '!anon|!redirect',
    'rctype' => 'new',
    'rclimit' => '500',
    'prefix' => $wiki);
  $rep =~ s/^.+<recentchanges><rc (.*)( \/>|<\/rc>)<\/recentchanges>.+$/$1/s;
  my @tab = split (/<rc /, $rep);
  return @tab;
}

sub get_special_rc_3
{
  my $rep = api_get (
    'action' => 'query',
    'list' => 'recentchanges',
    'rcnamespace' => '0|2',
    'rcprop' => 'user|title',
    'rcshow' => '!redirect|!bot|!anon',
    'rctype' => 'new',
    'rclimit' => '500',
    'prefix' => $wiki);
  $rep =~ s/^.+<recentchanges><rc (.*)( \/>|<\/rc>)<\/recentchanges>.+$/$1/s;
  my @tab = split (/ \/><rc /, $rep);
  return @tab;
}

sub recognize_spam_pseudo
{
  my $ref_pseudo = $_[0];
  $$ref_pseudo =~ s/^(Usuario|Utilisateur)://;
  return 1 if ($$ref_pseudo =~ /^[A-Z]'?[a-z]+[A-Z][a-z]+\d{1,4}[a-z]?$/);
  return 1 if ($$ref_pseudo =~ /^[A-Z]'?[a-z]+[A-Z][a-z]+\d[a-z]\d{2}$/);
  return 0;
}

sub spam_filter_1
{
  # Use get_special_rc() to get the RC list, then keep only the pseudo of the
  # spambots and return them in an @array
  my @rc = get_special_rc_1 ();
  my ($ref_candidates, $ref_timestamps, $ref_sizes, $ref_counteracts) = @_;
  foreach (@rc)
  {
    my ($title) = ($_ =~ m/\btitle="([^"]+)"/);
    if (recognize_spam_pseudo(\$title))
    {
      if (rc_action($_) eq "log")
      {
	$ref_counteracts->{$title} += 1 if ($_ =~ /logaction="(re)?block"/);
	$ref_counteracts->{$title} += 5 if ($_ =~ /logaction="delete"/);
	$ref_candidates->{$title}++ if ($_ =~ /logaction="create"/);
      }
      else
      {
	# Timestamp checking
	my $timestamp = tim($_);
	if (exists($ref_timestamps->{$title})) # Defined timestamp
	{
	  if (abs($ref_timestamps->{$title} - $timestamp) < 180)
	  {
	    $ref_timestamps->{$title} = -1;
	  }
	  else # Incorrect timestamp
	  {
	    delete($ref_candidates->{$title});
	    delete($ref_timestamps->{$title});
	    delete($ref_sizes->{$title});
	    delete($ref_counteracts->{$title});
	    next;
	  }
	}
	else
	{
	  $ref_timestamps->{$title} = $timestamp;
	}
	my ($size) = ($_ =~ /\bnewlen="(\d+)"/);
	if (exists($ref_sizes->{$title}))
	{
	  if ($ref_sizes->{$title} eq $size)
	  {
	    $ref_candidates->{$title}++;
	  }
	  else
	  {
	    delete($ref_candidates->{$title});
	    delete($ref_timestamps->{$title});
	    delete($ref_sizes->{$title});
	    delete($ref_counteracts->{$title});
	    next;
	  }
	}
	else
	{
	  $ref_sizes->{$title} = $size;
	  $ref_candidates->{$title}++;
	}
      }
    }
  }
}

sub spam_fighter_1
{
  my %candidates;
  my %timestamps;
  my %sizes;
  my %counteracts;
  spam_filter_1(\%candidates, \%timestamps, \%sizes, \%counteracts);
  print "Searching...\n";
  foreach (keys(%candidates))
  {
    $counteracts{$_} = 0 if (!exists($counteracts{$_}));
    if (($wiki ne 'fr' and $counteracts{$_} == 0) or $candidates{$_} > 1)
    {
      print "Caught: $_: $candidates{$_}/$counteracts{$_}\n";
      # Blocking
      if ($_ =~ /'/)
      {
	print "\033[41mWARNING:\033[0m ";
	print "due to what is probably a bug in the MediaWiki API, ";
	print "you must block \"by-hand\" the ".$_."'s account.\n";
      }
      else
      {
      my $rep = api_get (
	'action' => 'block',
	'user' => $_,
	'reason' => 'Automatic spam fighter: beautiful stories',
	'nocreate' => '',
	'autoblock' => '',
	'token' => $block_token,
	'prefix' => $wiki);
      }
      # and deleting
      my $rep = api_get (
	'action' => 'delete',
	'title' => $_,
	'reason' => 'Automatic spam fighter: beautiful stories',
	'token' => $delete_token,
	'prefix' => $wiki);
      $rep = api_get (
	'action' => 'delete',
	'title' => 'User:'.$_,
	'reason' => 'Automatic spam fighter: beautiful stories',
	'token' => $delete_token,
	'prefix' => $wiki);
    }
  }
  print "Done.\n";
}

sub spam_filter_2
{
  my $ref_tbl = $_[0];
  my @rc = get_special_rc_2();
  foreach (@rc)
  {
    my ($title, $user, $comment) =
      ($_ =~ /^type="new" ns="0" title="([^"]+)" user="([^"]+)" comment="([^"]+)"/);
    $ref_tbl->{$title} = $user
      if ($comment =~ /^Página creada con \\' ==<center>/);
  }
}

sub spam_fighter_2
{
  my %tbl;
  spam_filter_2(\%tbl);
  if (!%tbl)
  {
    print "(nothing to do)\n";
    return;
  }
  foreach (keys(%tbl))
  {
    print "Caught: $tbl{$_} on $_\n";
    # Blocking
    if ($tbl{$_} =~ /'/)
    {
      print "\033[41mWARNING:\033[0m ";
      print "due to what is probably a bug in the MediaWiki API, ";
      print "you must block \"by-hand\" the ".$tbl{$_}."'s account.\n";
    }
    else
    {
      my $rep = api_get (
	'action' => 'block',
	'user' => $tbl{$_},
	'reason' => 'Automatic spam fighter: websites dance',
	'nocreate' => '',
	'autoblock' => '',
	'token' => $block_token,
	'prefix' => $wiki);
    }
    # and deleting
    my $rep = api_get (
      'action' => 'delete',
      'title' => $_,
      'reason' => 'Automatic spam fighter: websites dance',
      'token' => $delete_token,
      'prefix' => $wiki);
    $rep = api_get (
      'action' => 'delete',
      'title' => 'User:'.$_,
      'reason' => 'Automatic spam fighter: websites dance',
      'token' => $delete_token,
      'prefix' => $wiki);
  }
}

sub to_pseudo_form_3
{
  my $pseudo = $_[0];
  if ($pseudo !~ s/^(Usuario|Utilisateur)://)
  {
    my $offset = index ($pseudo, " ", 0);
    if ($offset > 0)
    {
      $pseudo =~ s/ //g;
      substr($pseudo, $offset) = ucfirst (lc (substr ($pseudo, $offset)));
    }
  }
  return $pseudo;
}

sub spam_filter_3
{
  my ($ref_delete, $ref_block) = @_;
  my @rc = get_special_rc_3 ();
  my %seen;
  foreach (@rc)
  {
    my ($title, $user) = ($_ =~
      /^type="new" ns="[02]" title="([^"]+)" user="([^"]+)"$/);
    my $pseudoform = to_pseudo_form_3 ($title);
    next if ($pseudoform !~ /^[A-Z][a-z]+[A-Z][a-z]+$/);
    if (exists($seen{$pseudoform}))
    {
      push (@$ref_delete, $title, $seen{$pseudoform});
      push (@$ref_block, $user);
    }
    else
    {
      $seen{$pseudoform} = $title;
    }
  }
}

sub spam_fighter_3
{
  my @to_delete;
  my @to_block;
  spam_filter_3 (\@to_delete, \@to_block);
  if (!@to_delete and !@to_block)
  {
    print "(nothing to do)\n";
    return;
  }
  foreach (@to_delete)
  {
    print "Deleting $_...\n";
    api_get (
      'action' => 'delete',
      'title' => $_,
      'reason' => 'Automatic spam fighter: OdysseyInonepseudo',
      'token' => $delete_token,
      'prefix' => $wiki);
  }
  foreach (@to_block)
  {
    print "Blocking $_...\n";
    api_get (
      'action' => 'block',
      'user' => $_,
      'reason' => 'Automatic spam fighter: OdysseyInonepseudo',
      'nocreate' => '',
      'autoblock' => '',
      'token' => $block_token,
      'prefix' => $wiki);
  }
}

sub spam_filter_4
{
  my $ref_block = $_[0];
  my @rc = get_special_rc_3 (); # same needs
  my %seen;
  foreach (@rc)
  {
    my ($title, $user) = ($_ =~
      /^type="new" ns="[02]" title="(?:Usuario:)?([^"]+)" user="([^"]+)"$/);
    if ($title eq $user)
    {
      if (exists($seen{$user}))
      {
	push (@$ref_block, $user);
      }
      else
      {
	$seen{$user} = 1;
      }
    }
  }
}

sub spam_fighter_4
{
  my @to_block;
  spam_filter_4 (\@to_block);
  if (!@to_block)
  {
    print "(nothing to do)\n";
    return;
  }
  foreach (@to_block)
  {
    print "Deleting $_...\n";
    api_get (
      'action' => 'delete',
      'title' => $_,
      'reason' => 'Automatic spam fighter: eradication',
      'token' => $delete_token,
      'prefix' => $wiki);
    print "Deleting $_...\n";
    api_get (
      'action' => 'delete',
      'title' => 'User:'.$_,
      'reason' => 'Automatic spam fighter: eradication',
      'token' => $delete_token,
      'prefix' => $wiki);
    print "Blocking $_...\n";
    api_get (
      'action' => 'block',
      'user' => $_,
      'reason' => 'Automatic spam fighter: eradication',
      'nocreate' => '',
      'autoblock' => '',
      'token' => $block_token,
      'prefix' => $wiki);
  }
}

sub act
{
  print "\t\033[35mStep 1: Beautiful stories ###\033[0m\n";
  spam_fighter_1 ();
  print "\t\033[35mStep 2: Websites dance ###\033[0m\n";
  spam_fighter_2 ();
  print "\t\033[35mStep 3: OdysseyInonepseudo ###\033[0m\n";
  spam_fighter_3 ();
  print "\t\033[35mStep 4: Eradication ###\033[0m\n";
  spam_fighter_4 ();
}
