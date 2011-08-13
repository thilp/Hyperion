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
# Exemple : confiance = seuil_de_confiance_statutaire + ( ( âge + 2*nbre_de_modifications ) / 3 ) / nbre_d’avertissements + note_de_circonstance.
#   Cette ébauche de formule prend en compte le statut de l’utilisateur (un statut « plus élevé » dans la hiérarchie de LAURA écrasant les autres),
#     sa « note de circonstance » (attribuée en fonction des modifications) et la moyenne pondérée de son âge et de son activité — celle-ci étant
#     deux fois plus importante — moyenne divisée par le nombre d’avertissements reçus sur la page de discussion (par des contributeurs au statut correct).
#   On peut l’améliorer : y incorporer les blocages (fréquence, durée cumulée), la présence d’un bandeau « IP scolaire » sur la page de discussion,
#     utiliser une puissance de 2 pour accroître l’importance des avertissements, son ratio (modification_de_pages_existantes / création_de_nouvelles_pages)
#     et des statistiques de déclenchement des balises de filtre anti-erreur.
