#! /usr/bin/perl -w

# -------------------------------------------------.
#  * filtres.pl                                    !
# Filtres du robot de patrouille LAURA en Perl     !
# Écrits en juillet 2011 par thilp                 !
#   (http://fr.vikidia.org/wiki/User:thilp)        !
# Disponibles sous licence GPL 3 avec conservation !
#   de cet encadré.                                !
# -------------------------------------------------'

sub motif {
# ajoute à $$refCommentaire_filtrage un motif passé en argument
    if ( $$refCommentaire_filtrage ) { $$refCommentaire_filtrage.= ', '.$_; }
    else { $$refCommentaire_filtrage = $_; }
}

sub filtrage {
# Réunit l’ensemble des filtres à appliquer
    my ($type,$ns,$titre,$ip,$oldlen,$newlen,$comment, $seuil_revert, $refScore,$refCommentaire_filtrage) = @_;

    my $texte = sans_accent(lectureArticle $titre);

    # filtrage sur la taille de l’article et son évolution
    my $variation_taille = ($newlen-$oldlen)/$oldlen*100;
    if ( $newlen <= 5 || ($newlen <= 60 && $variation_taille < -90) ) { $$refScore-= 90; motif("blanchiment probable"); }
    if ( $newlen >= 5000 && $variation_taille > 200 ) { $$refScore-= 40; motif("gros ajout de texte"); }
    
    # filtrage de base sur texte et commentaire d’édition
    my ($refHash_score,$refHash_comment) = makeFiltres $filtres;
    foreach my $regex (keys(%$refHash_score)) {
	if ( $comment =~ /$regex/ or $texte =~ /$regex/ ) {
	    motif $$refHash_comment{$regex};
	    $$refScore+= $$refHash_score{$regex};
	}
    }
}

sub sans_accent {
# Ôte les accents et autres caractères spéciaux d’une chaine de caractères
    my $t = $_;
    $t =~ s/[Œœ]/oe/;
    $t =~ s/&nbsp;| | |/ /;
    return ($t =~ tr/ÁáÀàÂâÄäĀāÇçÉéÈèÊêËëĒēÍíÌìÎîÏïĪīÓóÒòÔôÖöŌōŘřÚúÙùÛûÜüŪūÝýỲỳŶŷŸÿȲȳ/AaAaAaAaAaAaCcEeEeEeEeEeIiIiIiIiIiOoOoOoOoOoRrUuUuUuUuUuYyYyYyYyYy/);
}


# fonction prenant en argument une chaine de caractère ; la découpe en hashtable (clefs : motif ; valeurs : variation du score correspondante) et renvoie une référence vers cette hashtable.
sub makeFiltres {
    my @tab = split(/\n/,$_);
    my %hash_score; my %hash_comment;
    foreach my $ligne (@tab) {
	my ($val,$motif) = split(/ /,$ligne,2);
	my ($motif,$comment) = split(/ #\w/,$motif,2);
	$hash_score{'qr'.$motif} = $val;
	$hash_comment{'qr'.$motif} = $comment;
    }
    return (\%hash_score,\%hash_comment);
}
$filtres = 'R /\b[ck]onn*(?:e?s?|err?i+e*s?|a+r[dt]?s?|a+s+es?)?\b/i #vulgarité
R /\bsa+l+(?:o+p*e?(?:rie?)?|(?:au|o)[td]?[sx]?)\b/i #vulgarité
R /\bpu*t(?:e|a?in|1)s?\b/i #vulgarité
R /\b(?:e[mn])?m+e+r+d+e+s?\b/i #vulgarité
R /\b[fs]uck(?:er|ing)?\b/i #vulgarité
R /\bbit+e|teub|pin+e|dick|cock\b/i #vulgarité
R /\b(?:[sjmt]e|en) branl(er?|es?)\b/i #vulgarité
R /\bcouil+es?\b/i #vulgarité
R /\btarlouze?|pe?de?\b/i #vulgarité
-40 /\bmeuf\b/i #vulgarité
R /\ben[ck]ull?(?:ee?s?|er)\b/i #vulgarité
R /\bdegueu?(?:ll?(?:er?|ass?es?))?\b/i #vulgarité
-50 /\bna+ze\b/i #vulgarité
-70 /\b[tm]a+ *gueules?\b/i #vulgarité
-10 /[bcdfghjklmnpqrstvwxz]{4,}/i #suite de 4+ consonnes
-10 /[aeiou]{3,}/i #suite de 3+ voyelles
-10 /[A-Z]{7,}/ #suite de 7+ capitales
-10 /[bcdfghjklmnpqrstvwxz]{7,10}/ #passage sans voyelle
-20 /([A-Za-z@()[\]\'".,$!?;])\1{3,}/ #répétition 4+ fois d’un caractère
-30 /\'\'\'Texte gras\'\'\'|\'\'Texte italique\'\'|\[\[Titre du lien\]\]|\[http:\/\/www\.example\.com Titre du lien\]|== Texte de sous-titre ==|\[\[Fichier:Exemple\.jpg\]\]|\[\[Media:Exemple\.ogg\]\]|<math>Entrez votre formule ici<\/math>|<nowiki>Entrez le texte non formaté ici<\/nowiki># Element 1\n# Element 2\n# Element 3|\* Element A\n\* Element B\n\* Element C|<gallery>\nImage:M63\.jpg\|\[\[M63\]\]\nImage:La Gioconda\.jpg\|\[\[La Joconde\]\]\nImage:Truite arc-en-ciel\.png\|Une \[\[truite\]\]\n<\/gallery>|#REDIRECTION \[\[Nom de la destination\]\]|\[\[Catégorie:Nom de la catégorie\]\]/ #wikitexte non complété
-40 /\b\w+(?:@| ?[[(]?(?:at|arobase?)[\])] ?)\w+\.\w{2,4}\b/ #insertion d’adresse courriel
-40 /\b0\d(?:[. -]?\d{2}){4}\b/ #insertion de numéro de téléphone (fr)
-20 /\b(?:je|moi|mon|ma|mes)\b/i #pronom 1re personne
-50 /\b(?:bonjour|au ?revoir|salu|[sc]a ?va)\b/i #formule de politesse
-50 /(\w{4,})\1{3,} #motif répété 3+ fois
R /\b(?:thilp|arseniuredegallium|astirmays|gaas|galdrad|jsl?2lyon|macassar|moez|moipaulochon|punx|szyx)\b/i #insertion d’un pseudo vikidien
+10
';
