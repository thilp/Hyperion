#! /usr/bin/perl -w

####################################################
# empusa.pl: Vikidia emergency spam fighter         #
# written by thilp (fr.vikidia.org/wiki/user:thilp) #
# in Perl on February 2012                          #
####################################################

use Data::Dumper;
use strict;
require 'base.pl';

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
  my $rep = requeteAPI ('action#=query#&list#=recentchanges#&rcnamespace#=0|2'.
    '#&rcprop#=user|timestamp|title|sizes|loginfo'.
    '#&rcshow#=!anon|!redirect#&rclimit#=5000'.
    '#&rcexcludeuser#=Penarc#&rctype#=new|log', 'es');
  $rep =~ s/^.+<recentchanges><rc (.*)( \/>|<\/rc>)<\/recentchanges>.+$/$1/s;
  my @tab = split (/<rc /, $rep);
  return @tab;
}

sub get_special_rc_2
{
  my $rep = requeteAPI ('action#=query#&list#=recentchanges#&rcnamespace#=0|2'.
    '#&rcprop#=user|timestamp|title|sizes|loginfo'.
    '#&rcshow#=!anon|!redirect#&rclimit#=5000'.
    '#&rcexcludeuser#=Penarc#&rctype#=new|log', 'es');
  $rep =~ s/^.+<recentchanges><rc (.*)( \/>|<\/rc>)<\/recentchanges>.+$/$1/s;
  my @tab = split (/<rc /, $rep);
  return @tab;
}

sub recognize_spam_pseudo
{
  my $ref_pseudo = $_[0];
  $$ref_pseudo =~ s/^Usuario://;
  return 1 if ($$ref_pseudo =~ /^[A-Z]'?[a-z]+[A-Z][a-z]+\d{2,4}$/);
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
  # Getting the tokens
  my $rep = requeteAPI('action#=block#&user#=Greta GarBot#&gettoken', 'es');
  my ($block_token) = ($rep =~ /\bblocktoken="([a-f0-9]+)\+\\"/);
  $rep = requeteAPI('action#=query#&prop#=info#&intoken#=delete'.
    '#&titles#=Vikidia:Portada', 'es');
  my ($delete_token) = ($rep =~ /\bdeletetoken="([a-f0-9]+)\+\\"/);
  $delete_token .= '+\\';
  foreach (keys(%candidates))
  {
    $counteracts{$_} = 0 if (!exists($counteracts{$_}));
    if ($candidates{$_} > 0 and $counteracts{$_} < 10)
    {
      print "Caught: $_: $candidates{$_}/$counteracts{$_}\n";
      # Blocking
      $rep = requeteAPI('action#=block#&user#='.$_.
	'#&reason#=Automatic spam fighter#&nocreate#&autoblock#&token#='.
	$block_token, 'es');
      $rep = requeteAPI('action#=delete#&title#='.$_.'#&reason#='.
	'Automatic spam fighter#&token#='.$delete_token, 'es');
      $rep = requeteAPI('action#=delete#&title#=Usuario:'.$_.'#&reason#='.
	'Automatic spam fighter#&token#='.$delete_token, 'es');
    }
  }
}

sub act
{
  print "\n\tStep 1: Beautiful stories ###\n";
  spam_fighter_1 ();
  #print "\n\tStep 2: Websites dance ###\n";
  #spam_fighter_2 ();
}

#####################################################################
#####################################################################

if (connexion('Greta GarBot', '', 'es'))
{
  print "Greta GarBot is now connected.\n";
}
else
{
  print "Connexion failure!\n";
  exit;
}

act ();

my $rep = requeteAPI('action#=logout', 'es');
print "Greta GarBot is now disconnected\n";
