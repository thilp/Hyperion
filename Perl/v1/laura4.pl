#! /usr/bin/perl -w

# -------------------------------------------------.
#  * laura4.pl                                     !
# Code du robot de patrouille LAURA (v4) en Perl   !
# Écrites en juillet 2011 par thilp                !
#   (http://fr.vikidia.org/wiki/User:thilp)        !
# Disponibles sous licence GPL 3 avec conservation !
#   de cet encadré.                                !
# -------------------------------------------------'

use Data::Dumper;
require "edit.pl";

# PARAMÈTRES
my @utilisateurs_de_confiance = (
  'Astirmays'
);
my @ns_surveilles = (0);
my @pages_protegees_simples = (
  'Utilisateur:Greta GarBot'
);
my @pages_protegees_recursives = (
  'Utilisateur:LAURA'
);
my @pages_ignorees = (
  
);
my $malus_ip = -20;
my $seuil_revert = -100;
#FIN PARAMÈTRES

sub page_est_protegee {
  $page = $_[0];
  return 1 if ($page ~~ @pages_protegees_simples);
  foreach $protegee (@pages_protegees_recursives) {
    return 1 if ($page =~ m/^$protegee\//);
  }
  return 0;
}

sub patrouilleRC {
# Produit le tableau des modifications récentes
  my ($date) = @_; # format AAAAMMJJhhmmss
  my @liste_rc = split(/<\/rc>/, requeteAPI('action#=query#&list#=recentchanges#&rcend#='.$date.'#&rclimit#=5000#&rcprop#=user|comment|flags|title|ids|sizes|loginfo|tags|timestamp'));
  pop @liste_rc;
  my @pages_visitees;
# exemples de RC
#
# <rc type="edit" ns="0" title="Condom" rcid="388637" pageid="63192" revid="380925" old_revid="377650" user="Ptyx" oldlen="480" newlen="705" timestamp="2011-07-30T18:30:04Z" comment="vieille réclame"><tags />
#
#<rc type="edit" ns="0" title="Pompier" rcid="388746" pageid="6146" revid="381033" old_revid="367112" user="2.3.147.186" anon="" oldlen="3865" newlen="4381" timestamp="2011-07-31T19:10:24Z" comment="/* Le langage pompier */ "><tags /></rc>
#
#<rc type="new" ns="3" title="Discussion utilisateur:2.3.147.186" rcid="388747" pageid="63587" revid="381034" old_revid="0" user="Thilp" oldlen="0" newlen="178" timestamp="2011-07-31T19:25:47Z" comment="bienvenue ip"><tags /></rc>
  foreach my $modif (@liste_rc) {
    
# GESTION DES MODIFICATIONS VISITÉES
    my ($pageid) = ($modif =~ m/\bpageid="(\d+)/);
    next if ( $pageid ~~ @pages_visitees );
    push( @pages_visitees, $pageid );
# FIN GESTION DES MODIFICATIONS VISITÉES

# ------------------------------------------

# COMPORTEMENTS EXCEPTIONNELS
    # initialisation du score
    my $score = 0;
    # filtre les utilisateurs de confiance
    my ($user) = ($modif =~ m/\buser="([^"]+)/);
    next if ( $user ~~ @utilisateurs_de_confiance );
    # filtre les espaces de noms non-surveillés
    my ($ns) = ($modif =~ m/\bns="(\d+)/);
    next if ( $ns ~~ @ns_surveilles );    
    # filtre les entrées du journal des opérations
    my ($type) = ($modif =~ m/\btype="(new|edit|log)/);
    next if ( $type eq "log" );
    # filtre les pages à ne pas surveiller
    my ($titre) = ($modif =~ m/\btitle="([^"]+)/);
    next if ( $titre ~~ @pages_ignorees );
    # si la page est protégée par le robot et que l’utilisateur ne modifie pas ses propres pages
    if ( page_est_protegee($titre) && ($titre !~ m/:$user/) ) {
      # revert !
    } else { next; }
    if ($modif =~ m/\banon=""/) { my $ip = 1; } else { my $ip = 0; }
    # interdit les modifications de PU par une adresse IP
    if ( $ip && ($titre =~ m/^Utilisateur:/) && $titre ne "Utilisateur:$ip" ) {
      # revert !
    }
    # application du malus des ip
    $score-= $malus_ip if $ip;
# FIN COMPORTEMENTS EXCEPTIONNELS

# ------------------------------------------

# COMPORTEMENTS SYSTÉMATIQUES    
    my ($revid) = ($modif =~ m/\brevid="(\d+)/);
    #my ($old_revid) = ($modif =~ m/\bold_revid="(\d+)/);
    my ($oldlen) = ($modif =~ m/\boldlen="(\d+)/);
    my ($newlen) = ($modif =~ m/\bnewlen="(\d+)/);
    my ($timestamp) = ($modif =~ m/\btimestamp="(\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}Z)/);
    my ($comment) = ($modif =~ m/\bcomment="([^"]*)/);
    my $commentaire_filtrage;
    next if ( ! filtrage($type,$ns,$titre,$ip,$oldlen,$newlen,$comment, $seuil_revert, \$score,\$commentaire_filtrage) );
    # le score de la modification a dépassé le seuil de filtrage : revert !
    
# FIN COMPORTEMENTS SYSTÉMATIQUES
  }
}

sub getHistorique {
  my $rep_api = requeteAPI('action#=query#&prop#=revisions#&titles#='.$_[0].'#&rvlimit#=5000#&rvprop#=ids|timestamp|user|size');
  my %hist_auteurs;
  my %hist_parentids;
  while ( $rep_api =~ m/\brevid="(\d+)".*?\bparentid="(\d+)".*?\buser="([^"]+)/g ) {
    $hist_parentids{$1} = $2;
    $hist_auteurs{$1} = $3;
  }
  return (\%hist_parentids,\%hist_auteurs);
}

sub getId_auteur_precedent {
  my ($titre,$revid) = @_;
  my ($refHist_parentids,$refHist_auteurs) = getHistorique($titre);
  my ($temoin,$parentid) = 1;
  while ($temoin) {
    $parentid = $$refHist_parentids{$revid};
    return $parentid if ( $$refHist_auteurs{$revid} ne $$refHist_auteurs{$parentid} );
    $revid = $parentid;
  }
  return undef;
}

sub retour_a_la_version {
# Reverte toutes les versions d’une page jusqu’à l’id de la version indiquée en argument.
  my ($titre,$revid_actuel,$revid_voulu,$resume,$bot) = @_;
  if ( defined($resume) ) { $resume = '#&summary#='.$resume; } else { $resume = ''; }
  if ( defined($bot) ) { $bot = '#&bot#=1'; } else { $bot = ''; }
  my $jetonEdit = getJeton_edit() unless defined($jetonEdit);
  return 1 if ( requeteAPI('action#=edit#&title#='.$titre.'#&undo#='.$revid_actuel.'#&undoafter#='.$revid_voulu.'#&token#='.$jetonEdit.$resume.$bot) =~ m/\bresult="Success"/ );
  return 0;
}



#my ($titre,$revid) = ('Vikidia:Bavardages/2011/31','381298');
#print "OK !\n" if retour_a_la_version($titre,$revid,getId_auteur_precedent($titre,$revid),'Annulation des dernières modifications de thilp (test)',1);
#print "Fin.\n";

#print requeteAPI('action#=query#&list#=recentchanges#&rcend#=20110731170000#&rclimit#=5000#&rcprop#=user|comment|title|ids|sizes|loginfo|tags|timestamp#&rctype#=new');
#print "\n\n";
#patrouilleRC('20110731170000');
