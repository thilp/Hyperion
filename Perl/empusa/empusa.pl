#! /usr/bin/perl -w

####################################################
# empusa.pl: Vikidia emergency spam fighter         #
# written by thilp (fr.vikidia.org/wiki/user:thilp) #
# in Perl on February 2012                          #
####################################################

use Data::Dumper;
use strict;
require 'base.pl';

sub get_special_rc
{
  my $rep = requeteAPI ('action#=query#&list#=recentchanges#&rcnamespace#=0|2'.
    '#&rcprop#=user|timestamp|title|sizes|loginfo'.
    '#&rcshow#=minor|!anon|!bot|!redirect#&rclimit#=5000'.
    '#&rcexcludeuser#=Penarc#&rctype#=new|log', 'es');
  $rep =~ s/^.+<recentchanges><rc (.*) \/><\/recentchanges>.+$/$1/s;
  my @tab = split (/ \/>\s*<rc /, $rep);
  return @tab;
}

sub spam_filter
{
  # Use get_special_rc() to get the RC list, then keep only the pseudo of the
  # spambots and return them in an @array
  my @rc = get_special_rc ();
}

print Dumper(get_special_rc());
