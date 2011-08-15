#! /usr/bin/perl -w

# --------------------------------------------------.
#  * laura5.pl                                      !
# Fonctions de patrouille pour robots thilp en Perl !
# Écrites en août 2011 par thilp                    !
#   (http://fr.vikidia.org/wiki/user:thilp)         !
# Disponibles sous licence GPL 3 avec conservation  !
#   de cet encadré.                                 !
# --------------------------------------------------'

# Cette cinquième version assimile les importants changements dus à l’adoption d’AbuseFilter sur Vikidia.
# AbuseFilter filtre les contenus et agit, en ce qui concerne LAURA, par l’affichage de balises. Il faut
#  récupérer ces balises pour en tirer des informations et ne pas appliquer un filtrage redondant avec
#  celui d’AbuseFilter. C’est l’occasion de se concentrer sur le déroulé temporel (basiquement géré par
#  AbuseFilter) et le suivi des utilisateurs (non géré), ainsi que les interactions des utilisateurs
#  avec les patrouilleurs et les administrateurs.

use Data::Dumper;
use Date::Calc qw(Mktime Localtime);
require "edit.pl";

# CHARGEMENT DE LA BASE DE DONNÉES
if (-e "lauraperl.dis") { open(BDD, "+<lauraperl.dis"); }
else { open(BDD, "+>lauraperl.dis"); }
my %bdd;
while ( <BDD> ) {
    my ($nom,$attr) = split(/#:/,$_);
    $bdd{$nom} = chomp($attr);
}
close(BDD);
# FIN CHARGEMENT

sub getMembres_d1_groupe {
    # Récupère la liste des membres d’un groupe d’utilisateurs
    return (requeteAPI('action#=query#&list#=allusers#&aulimit#=5000#&augroup#='.$_[0]) =~ m/\bname="([^"]+)"/g);
}

my @administrateurs = getMembres_d1_groupe("sysop");
my @autopatrol = getMembres_d1_groupe("autopatrol");
my @patrouilleurs = getMembres_d1_groupe("patroller");

# LAURA doit suivre l’évolution de chaque contributeur en fonction de ses contributions :
#   plus ses modifications sont annulées ou retravaillées, moins elle a confiance en lui ;
#   inversement, plus il contribue sans être repris ou averti, plus la confiance que LAURA lui porte croît.
# Il faut donc un fichier/base de données présentant : le nom d’utilisateur et la note de confiance de LAURA.
#
# À chaque modification d’une page, LAURA doit vérifier si les changements annulent ou retravaillent beaucoup ceux de l’auteur précédent.
#   Pour cela, charger le diff de la nouvelle version par rapport à l’ancienne puis de l’ancienne par rapport à celle d’encore avant
#     (action=query&prop=revisions&revids=X&rvprop=&rvdiffto=prev) ;
#   Comparer les changements. Si des lignes ajoutées ont été retirées ou très changées, modifier la confiance de LAURA envers les contributeurs
#     en fonction de leur confiance précédente : un patrouilleur corrigeant le travail d’une IP diminue la confiance de LAURA dans celle-ci,
#     tandis qu’un simple utilisateur modifiant le travail d’un administrateur perd lui-même de la confiance. Ce mécanisme s’applique plus généralement
#     en fonction de la note de confiance, et non du statut de l’utilisateur, bien que la première soit aussi liée au second.
#   Il ne faut pas sauvegarder les diffs ni les informations associées en local : trop encombrant. Charger deux diffs quand nécessaire.
#
# La confiance de LAURA augmentant systématiquement en fonction de l’âge de l’utilisateur sur Vikidia, la formule de notation doit prendre linéairement
#   en compte ce paramètre. Il est modéré par le nombre d’avertissements reçus et évolue en fonction des statuts de l’utilisateur.
#   Enfin, une partie de la note reste constamment variable pour les ajustements ad hoc de LAURA, qui dépendent seulement des modifications.
# Exemple : c_{totale} = \frac{1}{n_{semblables}}\times\sum_{semblables}\left((b_{scolaire}+1)\times\frac{c_{statut}+\frac{t_{age}+2n_{contribs}}{3(n_{averts}^2+n_{balises}+1)}}{2^{n_{blocages}}}\right)+c_{instant}
#   (où l’indice « semblables » représente, dans le cas d’une adresse IP, toutes les adresses connues de son masque /24 ;
#     dans le cas d’un utilisateur, cela n’a pas de signification et on le simplifie par le cas d’une adresse IP « seule parmi son masque » ;
#     et où b_{scolaire} représente la variable binaire valant 1 si la PdD comporte {{ip scolaire}}, 0 sinon)
#
# Pour calculer cette note de confiance, il est nécessaire de disposer des informations suivantes :
#  * IP connues du même masque /24 (d’où leur nombre et leur note, d’où la moyenne de leur note) ;
#  * compte utilisé par des scolaires ;
#  * statut de l’utilisateur ;
#  * âge de l’utilisateur sur Vikidia ;
#  * nombre de modifications effectuées ;
#  * nombre d’avertissements reçus ;
#  * nombre de balises déclenchées ;
#  * nombre de blocages subis.
#
# LAURA doit donc disposer d’un fichier listant ces informations. Elles les complète elle-même quand un changement survient et récupère les autres via l’API.
# Format ?
# (?<=\n)
# NOM#:(0|1);\(AUTRE_IP_CONNUE_1[,AUTRE_IP_CONNUE_2[,…]]\);(0|1);
#        ip ?       () si pas ip                          scolaire ?
#   (\*|user|autoconfirmed|bot|sysop|bureaucrat|patroller|autopatrol|developer|oversight|checkuser|abusefilter)[,…];
#               groupe majeur de l’utilisateur
#   

sub unix_of_wtimestamp {
# transforme un timestamp MediaWiki en valeur de temps Unix
    return Mktime($_[0] =~ m/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})Z/);
}
sub wtimestamp_of_unix {
# transforme une valeur de temps Unix en timestamp MediaWiki
    my ($a,$m,$j,$h,$min,$s) = Localtime($_[0]);
    $m = "0".$m if ( length($m) == 1 ); $j = "0".$j if ( length($j) == 1 ); $h = "0".$h if ( length($h) == 1 );
    $min = "0".$min if ( length($min) == 1 ); $s = "0".$s if ( length($s) == 1 );
    return $a."-".$m."-".$j."T".$h.":".$min.":".$s."Z";
}
sub jours_depuis {
# calcule le nombre de jours écoulés entre aujourd’hui et le temps Unix donné
    return int( (time() - $_[0]) / 365.25 );
}

sub estIP {
# renvoie 1 si le pseudo est une IP, 0 sinon
    if ( my ($un,$deux,$trois,$quatre) = ($_[0] =~ m/(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})/) ) {
	return 1 if ($un < 255 && $deux < 255 && $trois < 255 && $quatre < 255);
	return 0;
    }
    return 0;
}

sub getUser_infos {
# renvoie l’âge, le nombre d’éditions et le statut le plus important (dans la hiérarchie de LAURA) de l’utilisateur dont le nom est donné
    my $rep = requeteAPI("action#=query#&list#=allusers#&auprop#=groups|editcount|registration#&aulimit#=1#&aufrom#=".$_[0]);
    my ($editcount,$registration) = ($rep =~ m/\beditcount="(\d+)" registration="([^"]+)"/);
    my $age = jours_depuis(wtimestamp_of_unix($registration));
    if ( $rep =~ m/<groups>/ ) {
	my @groupes = ( $rep =~ m#(?<=<g>)([^<]+)(?=</g>)#g );
	my $statut;
	if ( "developer" ~~ @groupes ) { $statut = "developer"; }
	elsif ( "bureaucrat" ~~ @groupes ) {$statut = "bureaucrat"; }
 	elsif ( "sysop" ~~ @groupes ) {$statut = "sysop"; }
	elsif ( "abusefilter" ~~ @groupes ) {$statut = "abusefilter"; }
	elsif ( "autopatrol" ~~ @groupes ) {$statut = "autopatrol"; }
	elsif ( "patroller" ~~ @groupes ) {$statut = "patroller"; }
	else { $statut = "autre"; }
    }
    if (!defined($statut)) { my $statut = "*"; }
    return ($age,$editcount,$statut);
}

sub ecritUser_infos {
# pour l’utilisateur donné en argument (0), écrit les informations passées en argument (1)
    $bdd{$_[0]} = $_[1]; return 1;
}
sub rechercheUser_infos {
# renvoie les informations de l’utilisateur donné en argument
    return $bdd{$_[0]};
}
sub majBDD {
# met à jour la base de données sauvegardée : écrit le contenu de %bdd dans le fichier lauraperl.dis
    # copie de secours du fichier
    open(OP,"|cp lauraperl.dis lauraperl.dis.sauv"); close(OP);
    # écriture
    open(BDD, ">lauraperl.dis");
    foreach $cle (keys(%bdd)) {
	print( BDD $cle.":".$bdd{$cle}."\n" );
    }
    close(BDD);
    return 1;
}
