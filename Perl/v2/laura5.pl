#! /usr/bin/perl -w

# --------------------------------------------------.
#  * laura5.pl                                      !
# Fonctions de patrouille pour robots thilp en Perl !
# Écrites en août 2011 par thilp                    !
#   (http://fr.vikidia.org/wiki/user:thilp)         !
# Disponibles sous licence GPL 3 avec conservation  !
#   de cet encadré.                                 !
# --------------------------------------------------'

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

use Data::Dumper;
use Date::Calc qw(Mktime Localtime);
use Math::Trig qw(atan tanh);
require "edit.pl";

#my @administrateurs = getMembres_d1_groupe("sysop");
#my @autopatrol = getMembres_d1_groupe("autopatrol");
#my @patrouilleurs = getMembres_d1_groupe("patroller");
my @balises = getListe_balises();

# #############################################################################################################

# CHARGEMENT DE LA BASE DE DONNÉES

my %bdd;
if (-e "lauraperl.dis") {
    open(BDD, "<lauraperl.dis");
    while ( <BDD> ) {
	chomp;
	my ($nom,$attr) = split(/#:/);
	$bdd{$nom} = $attr;
    }
    close(BDD);
}

# FIN CHARGEMENT

# ################################################################################################################

# FONCTIONS UTILITAIRES DE CALCUL

sub unix_of_wtimestamp {
# transforme un timestamp MediaWiki en valeur de temps Unix
    return Mktime( $_[0] =~ m/(\d{4})-(\d{2})-(\d{2})T(\d{2}):(\d{2}):(\d{2})Z/ );
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
    return int( (time() - $_[0]) / 86400 );
}
sub estIP {
# renvoie 1 si le pseudo est une IP, 0 sinon
    if ( my ($un,$deux,$trois,$quatre) = ($_[0] =~ m/(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})/) ) {
	return 1 if ($un < 255 && $deux < 255 && $trois < 255 && $quatre < 255);
	return 0;
    }
    return 0;
}

# FIN FONCTIONS UTILITAIRES DE CALCUL

# ################################################################################################################

# FONCTIONS DE RÉCUPÉRATION DEPUIS L’API

sub getMembres_d1_groupe {
# Récupère la liste des membres d’un groupe d’utilisateurs
    return (requeteAPI('action#=query#&list#=allusers#&aulimit#=5000#&augroup#='.$_[0]) =~ m/\bname="([^"]+)"/g);
}
sub getListe_balises {
# Récupère la liste des balises AbuseFilter
    return (requeteAPI("action#=query#&list#=tags#&tglimit#=500") =~ m/\bname="([^"]+)"/g);
}
sub estScolaire {
# renvoie 1 si la page de discussion du compte d’utilisateur mentionne qu’il est utilisé dans un cadre scolaire, 0 sinon,
    if ( lectureArticle("Discussion utilisateur:".$_[0]) =~ m/\{\{ip scolaire\|/i ) {
	return 1;
    }
    return 0;
}
sub getUser_infos {
# renvoie l’âge, le nombre d’éditions et le statut le plus important (dans la hiérarchie de LAURA) de l’utilisateur dont le nom est donné
    my $rep = requeteAPI("action#=query#&list#=allusers#&auprop#=groups|editcount|registration#&aulimit#=1#&aufrom#=".$_[0]);
    my ($editcount,$registration) = ($rep =~ m/\beditcount="(\d+)" registration="([^"]+)"/);
    my $age = jours_depuis(unix_of_wtimestamp($registration));
    my $statut = "*";
    if ( $rep =~ m/<groups>/ ) {
	my @groupes = ( $rep =~ m#(?<=<g>)[^<]+(?=</g>)#g );
	if ( "developer" ~~ @groupes ) { $statut = "developer"; }
	elsif ( "bureaucrat" ~~ @groupes ) { $statut = "bureaucrat"; }
 	elsif ( "sysop" ~~ @groupes ) { $statut = "sysop"; }
	elsif ( "abusefilter" ~~ @groupes ) { $statut = "abusefilter"; }
	elsif ( "autopatrol" ~~ @groupes ) { $statut = "autopatrol"; }
	elsif ( "patroller" ~~ @groupes ) { $statut = "patroller"; }
	else { $statut = "autre"; }
    }
    return ($age,$editcount,$statut,$registration);
}
sub getNbre_blocages {
# renvoie le nombre de blocages subis par un utilisateur
# on compte le nombre de blocages subis (hormis les autoblocages) - le nombre de blocages annulés
    # action=query&list=logevents&leaction=block/block&leprop=user&lelimit=500&letitle=Utilisateur:
    my @countblocks =
	( requeteAPI("action#=query#&list#=logevents#&leaction#=block/block#&leprop#=user#&lelimit#=500#&letitle#=Utilisateur:".$_[0]) =~ m/<item user="(?!$_[0]")/g );
    my @countunblocks =
	( requeteAPI("action#=query#&list#=logevents#&leaction#=block/unblock#&leprop#=user#&lelimit#=500#&letitle#=Utilisateur:".$_[0]) =~ m/<item user="(?!$_[0]")/g );
    return (@countblocks - @countunblocks);
}
sub getNbre_balises {
# renvoie le nombre de balises déclenchées par un utilisateur donné
    my $compte = 0;
    my @temp;
    foreach $balise (@balises) {
	@temp = ( requeteAPI("action#=query#&list#=usercontribs#&ucuser#=$_[0]#&uclimit#=5000#&ucprop#=#&uctag#=$balise") =~ m/<item\b/g );
	$compte += @temp;
    }
    return $compte;
}
sub getNbre_averts {
# renvoie le nombre d’avertissements présents *actuellement* sur la page de discussion d’un utilisateur donné
    my $texte = lectureArticle("Discussion utilisateur:".$_[0]);
    my @nbre = ( $texte =~ m/\{\{averto-/g );
    return scalar(@nbre);
}
sub getRCs {
# renvoie le tableau des modifications survenues depuis le timestamp AAAAMMJJhhmmss passé en argument, ou les 5000 dernières si pas d’argument.
    my $timestamp = $_[0];
    if ( defined($timestamp) ) { $timestamp = "#&rcend#=".$timestamp; }
    else { $timestamp = ""; }
    my @tabRCs =
	(requeteAPI("action#=query#&list#=recentchanges#&rcexcludeuser#=Alcyon#&rcprop#=user|comment|timestamp|ids|sizes|loginfo|tags#&rclimit#=5000".$timestamp) =~ m#(?<=<rc ).*?(?=</rc>)#g);
    return @tabRCs;
}

# FIN FONCTIONS DE RÉCUPÉRATION DEPUIS L’API

# ################################################################################################################

# FONCTIONS D’INTERACTION AVEC LA BASE DE DONNÉES

sub getListe_autres_ips {
# renvoie la liste des autres ips appartenant au même masque /24 que l’IP donnée et répertoriées dans %bdd.
    my $masque = $_[0];
    $masque =~ s/(?<=\.)\d{1,2}$//;
    my @ips = ( grep { /^$masque/ } keys %bdd );
    for ( my $i=0; $i<@ips; $i++ ) {
	delete $ips[$i] if ( $ips[$i] eq $_[0] );
    }
    return @ips;
}
sub getChaine_autres_ips {
# renvoie le résultat de getAutres_ips() sous forme de chaîne de caractères.
    return join(",",getListe_autres_ips($_[0]));
}
sub majUser_infos {
# met à jour le couple (nom:valeur) d’un utilisateur donné : majUser_infos(utilisateur,nom,valeur);
    $bdd{$_[0]} =~ s/$_[1]:[^;]+/$_[1]:$_[2]/;
    return 1;
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
    open(OP,"|cp -f lauraperl.dis lauraperl.dis.sauv"); close(OP);
    # écriture
    open(BDD, ">lauraperl.dis");
    foreach $cle (keys(%bdd)) {
	print( BDD $cle."#:".$bdd{$cle}."\n" );
    }
    close(BDD);
    return 1;
}
sub nouvUser_infos {
# crée le champ d’informations d’un nouvel utilisateur dont le nom est passé en argument
    my ($age,$n_contribs,$statut,$registration) = getUser_infos($_[0]);
    my $ip = estIP($_[0]);
    my $n_balises = getNbre_balises($_[0]);
    my $n_averts = getNbre_averts($_[0]);
    my $n_blocages = getNbre_blocages($_[0]);
    my $scolaire = estScolaire($_[0]);
    my $note = calculNote_confiance_base($scolaire,$ip,$statut,$age,$n_contribs,$n_averts,$n_balises,$n_blocages);
    my $aip = "";
    if ( $ip ) { $aip = getChaine_autres_ips($_[0]); }
    $bdd{$_[0]} = "not:$note;edc:$n_contribs;reg:$registration;sta:$statut;iip:$ip;aip:$aip;sco:$scolaire;nav:$n_averts;nbl:$n_blocages;nba:$n_balises;";
    return 1;
}
sub litUser_infos {
# récupère les informations concernant l’utilisateur donné et stockées dans la %bdd.
    my $champ = $bdd{$_[0]};
    my @tabAip;
    if (
	!( my ($note,$n_contribs,$registration,$statut,$ip,$scolaire,$n_averts,$n_blocages,$n_balises) =
	($champ =~ m/not:(\d+);edc:(\d+);reg:([\dTZ:-]+);sta:([a-z*]+);iip:(0|1);aip:[^;]*;sco:(0|1);nav:(\d+);nbl:(\d+);nba:(\d+);/ ))
	) { return 0; }
    else  {
	if ( $ip ) {
	    @tabAip = split( /,/ , ($champ =~ m/\baip:([^;]*)/) );
	}
	return ($note,$n_contribs,$registration,$statut,$ip,\@tabAip,$scolaire,$n_averts,$n_blocages,$n_balises);
    }
}

# FIN FONCTIONS D’INTERACTION AVEC LA BASE DE DONNÉES

# ################################################################################################################


sub calculNote_confiance_base {
# Renvoie le degré fondamental de confiance accordé par Alcyon à un contributeur selon les paramètres passés en argument.
    my ($scolaire,$ip,$statut,$age,$n_contribs,$n_averts,$n_balises,$n_blocages) = @_;
    my $val_statut = 0;
    $val_statut = 1 if ( $statut eq "patroller" );
    $val_statut = 2 if ( $statut eq "autopatrol" );
    $val_statut = 3 if ( $statut eq "sysop" or $statut eq "abusefilter" );
    $val_statut = 4 if ( $statut eq "bureaucrat" );
    $val_statut = 5 if ( $statut eq "developer" );
    return int(10*(1/(1+$ip+$scolaire))*(1+$val_statut)*(1.5*atan(sqrt($age/7))*(tanh($age-3)+1))*($n_contribs-$n_balises**2)/(1+$n_contribs*((1+$n_blocages)**2+$n_averts)));
}

# type="edit" rcid="390258" pageid="63778" revid="382448" old_revid="382443" user="Adoni273" oldlen="1262" newlen="1583" timestamp="2011-08-18T12:48:52Z" comment=""><tags />

# type="new" rcid="390257" pageid="63781" revid="382447" old_revid="0" user="Adoni273" oldlen="0" newlen="995" timestamp="2011-08-18T12:48:21Z" comment="Création : *Un &#039;&#039;&#039;satellite&#039;&#039;&#039; désigne un objet en [[orbite]] autour d&#039;un autre : **quand il n&#039;est pas d&#039;origine humaine, c&#039;est un [[satellite|&#039;&#039;&#039;satellite&#039;&#039;&#039; naturel]] (domaine de l&#039;[[astronomie]..."><tags />

# type="log" rcid="390249" pageid="63779" revid="0" old_revid="0" user="Adoni273" oldlen="0" newlen="0" timestamp="2011-08-18T10:57:19Z" comment="nom au pluriel " logid="242704" logtype="move" logaction="move"><move new_ns="102" new_title="Portail:Satellites naturels" /><tags />

sub analyseRCs {
    foreach (@_) {
	my ($type) = ( $_ =~ m/^type="(edit|new|log)/ );
	my ($user) = ( $_ =~ m/user="([^"]+)/ );
	if ( $type eq "edit" ) {
	    # my ($pageid,$revid,$old_revid)
	}
	elsif ( $type eq "new" ) {

	}
	else {

	}
    }
}
