#! /usr/bin/perl -w

require "base.pl";
require "edit.pl";

##
##########
#######################
####################################################
##############  CONFIGURATION HERE #################
####################################################

$local_language = 'fr';

####################################################
########### NO MORE CONFIGURATION NOW ##############
####################################################
########################
#########
##

# Prefixes with the two-sided interwiki links
@tab_bilateral = ('es','fr','nl');
# Prefixes with the one-sided interwiki links
@tab_unilateral = ('wp','de','en');

# Return the translation of the wiki name linked to $prefix in the
# $sitelang language
sub name_of_prefix
{
  my ($prefix, $sitelang) = @_;
  $sitelang = $local_language if (!defined($sitelang));

  if ($prefix eq 'fr')
  {
    return 'Vikidia en francés' if ($sitelang eq 'es');
    return 'franse Vikidia' if ($sitelang eq 'nl');
    return 'French Vikidia';
  }
  elsif ($prefix =~ /^wp_/)
  {
    return 'Wikipédia' if ($sitelang eq 'fr');
    return 'Wikipedia';
  }
  elsif ($prefix eq 'nl')
  {
    return 'WikiKids';
  }
  elsif ($prefix eq 'es')
  {
    return 'Vikidia hispanophone' if ($sitelang eq 'fr');
    return 'spaanse Vikidia' if ($sitelang eq 'nl');
    return 'Spanish Vikidia';
  }
  elsif ($prefix eq 'en')
  {
    return 'Simple English Wikipedia';
  }
  elsif ($prefix eq 'de')
  {
    return 'Grundschulwiki';
  }
  else
  {
    return '(préfixe wiki inconnu)' if ($sitelang eq 'fr');
    return '(wiki prefix onbekend)' if ($sitelang eq 'nl');
    return '(wiki prefijo desconocidos)' if ($sitelang eq 'es');
    return '(unknown wiki prefix)';
  }
}

# Checks if the $title article exists in the wiki that matches $prefix
sub article_exists
{
  my ($title, $prefix) = @_;
  return (requeteAPI('action#=query#&prop#=revisions#&titles#='.$title.
      '#&rvprop#=content', $prefix) !~ / missing=""/);
}

# Checks if the article $text has to be interwikified
sub has_to_be_interwikified
{
  my ($text, $title_prefix_to, $prefix_from, $prefix_to) = @_;
  return ($text !~ /\[\[$prefix_to:/ and
    article_exists($title_prefix_to, $prefix_to));
}

# Give the summary corresponding to the publication of an interwiki link
# to the linked wikis on the $prefix_from wiki
sub summary
{
  my ($prefix_from, $ref_htab_interwikis) = @_;
  my $wikis = '';

  foreach (keys(%$ref_htab_interwikis))
  {
    $wikis .= ', '.name_of_prefix($_, $prefix_from);
  }
  $wikis =~ s/^.//;
  $wikis .= '.';

  return 'Liens interwikis automatiques vers'.$wikis if ($prefix_from eq 'fr');
  return 'Automáticas interwiki enlaces con dirección a'.$wikis
    if ($prefix_from == 'es');
  return 'Automatische interwiki links naar'.$wikis if ($prefix_from eq 'nl');
  return 'Automatic interwiki links towards'.$wikis;
}

# Returns a hashtable A => B where A is a prefix in $tab_prefixes and B
# is the translation of $title in the language corresponding to the prefix,
# using the language-specific Wikipedia
sub translate_title
{
  my $title = shift;
  my @tab_prefixes = @_;
  my $htab_translate[$local_language] = $title;
  my $wp_article = lectureArticle($title, 'wp_'.$local_language);

  foreach (@tab_prefixes)
  {
    ($htab_translate[$_]) = ($wp_article =~ /\[\[$_:([^\]]+)\]\]/)
      if ($_ ne $local_language);
  }
}