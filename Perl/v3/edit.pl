#! /usr/bin/perl -w

# ------------------------------------------.
#  * edit.pl                                !
# Edition functions for LAURA bots in Perl  !
# Written (using vim) in 2011 by thilp      !
#   (http://fr.vikidia.org/wiki/User:thilp) !
# Code under GPL 3, please keep this box.   !
# ------------------------------------------'

require "base.pl";

sub getJeton_edit
{
  my $jeton;
  return $jeton if (($jeton) = (requeteAPI('action#=query#&prop#=info'.
    '#&intoken#=edit#&titles#=T') =~ /\bedittoken="([^"]+)/));
  return 0;
}

sub edit
{
  my ($titre,$texte,$resume,$bot) = @_;
  my $jetonEdit = getJeton_edit() unless defined($jetonEdit);

  if ($bot) { $bot = "#&bot#=1"; } else { $bot = ""; }
  my $rep = requeteAPI('action#=edit#&title#='.$titre.'#&text#='.$texte.
    '#&token#='.$jetonEdit.'#&summary#='.$resume.'#&notminor#=1'.$bot);
  return 1 if ($rep =~ /\bresult="Success"/);
  return 0;
}

sub edit_avant
{
  my ($titre,$ajout,$resume,$bot) = @_;

  $texte = lectureArticle($titre);
  return edit($titre,"$ajout\n$texte",$resume,$bot);
}

sub edit_apres
{
  my ($titre,$ajout,$resume,$bot) = @_;
  my $texte = lectureArticle($titre);
  return edit($titre,"$texte\n$ajout",$resume,$bot);
}

sub ajout_section
{
  my ($titre_page,$titre_section,$niveau_section,$texte,$resume,$bot) = @_;
  return edit_apres($titre_page,"="x$niveau_section." $titre_section ".
    "="x$niveau_section."\n".$texte,$resume,$bot);
}

