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
require "edit.pl";

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
