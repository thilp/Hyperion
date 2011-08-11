#! /usr/bin/perl -w

# -------------------------------------------------.
#  * edit.pl                                       !
# Fonctions d’édition pour robots thilp en Perl    !
# Écrites en juillet 2011 par thilp                !
#   (http://fr.vikidia.org/wiki/User:thilp)        !
# Disponibles sous licence GPL 3 avec conservation !
#   de cet encadré.                                !
# -------------------------------------------------'

require "base.pl";

sub getJeton_edit {
  my $jeton;
  return $jeton if (($jeton) = (requeteAPI('action#=query#&prop#=info#&intoken#=edit#&titles#=T') =~ m/\bedittoken="([^"]+)/));
  return 0;
}

sub edit {
  my ($titre,$texte,$resume,$bot) = @_;
  if ($bot) { $bot = "#&bot#=1"; } else { $bot = ""; }
  my $jetonEdit = getJeton_edit() unless defined($jetonEdit);
  my $rep = requeteAPI('action#=edit#&title#='.$titre.'#&text#='.$texte.'#&token#='.$jetonEdit.'#&summary#='.$resume.'#&notminor#=1'.$bot);
  return 1 if ($rep =~ m/\bresult="Success"/);
  return 0;  
}

sub edit_avant {
  my ($titre,$ajout,$resume,$bot) = @_;
  $texte = lectureArticle($titre);
  return edit($titre,"$ajout\n$texte",$resume,$bot);
}

sub edit_apres {
  my ($titre,$ajout,$resume,$bot) = @_;
  my $texte = lectureArticle($titre);
  return edit($titre,"$texte\n$ajout",$resume,$bot);
}

sub ajout_section {
  my ($titre_page,$titre_section,$niveau_section,$texte,$resume,$bot) = @_;
  return edit_apres($titre_page,"="x$niveau_section." $titre_section "."="x$niveau_section."\n".$texte,$resume,$bot);
}

