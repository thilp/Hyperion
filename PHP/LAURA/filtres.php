<?php
	/* Ce fichier définit les filtres utilisés par LAURA et son attribution du score. La fonction demande un texte (string $cont) et, en option,
	*  un niveau de profondeur (par défaut, 0) et la mise en forme des motifs (activée par défaut) ; elle renvoie un tableau (array $retour)
	*  tel que :
	*  - $retour[0] est un booléen (false = revert ; true = conserver) ;
	*  - $retour[1] est un entier relatif représentant le score de la modification ou la chaîne "revert immédiat";
	*  - $retour[2] est une chaîne de caractères contenant les motifs justifiant le score. */
	
	global $tabParamètres;
	if ($tabParamètres['filtres additionnels'] == 'oui') { // Importation du filtre additionnel
		include_once('filtre_additionnel.php');}
	
function quelsMotifs($tabMotifs) { // Met le tableau des motifs sous la forme d’une chaîne de caractères
	$motifs = '';
	foreach($tabMotifs as $motif => $nbre) {
		if ($nbre != 0) {
			if ($motifs == '') {
				$motifs = $motif.' ('.$nbre.')';}
			else {
				$motifs = $motifs.', '.$motif.' ('.$nbre.')';}}}
	return $motifs;
}
// Définition des fonctions annexes
function score($modif, $motif) {
	global $score, $motifs;;
	$score+= $modif;
	if (array_key_exists($motif, $motifs)) {
		$motifs[$motif]+=1; }
	else {
		$motifs[$motif] = 1; }}

function filtres($cont, $niveau=0, $mef=true) {
	// Définition des variables
	$score = 0;
	$motifs = array();
	
// Filtres
	
	// Revert automatique
	if (preg_match('#^.{0,20}$#', $cont)) { // Blanchiment
		return (array(false, 'revert immédiat', 'blanchiment'));}
	if (preg_match('#\b(.*\W)?[kc]o+n+(e|(a+(r+[dts]*)|(s+e*s*))|(er+i+e*s?))?(\W.*)?\b#i', $cont)
	or preg_match('#m+e+r+d+#i', $cont)	or preg_match('#[fs]u+c?k#i', $cont) or preg_match('#foutre#i', $cont)
	or preg_match('#[^é]branle#i', $cont) or preg_match('#[^\w\-]ni+c?[kq][eér]*\W#i', $cont)
	or preg_match('#\Wshi+t\W#i', $cont) or preg_match('#\W(teub|bite)\W#i', $cont) or preg_match('#\Wcock\W#i', $cont)
	or preg_match('#\Wwtf\W#i', $cont) or preg_match('#\Wcouilles?\W#i', $cont)	or preg_match('#\Wstupides?\W#i', $cont)
	or preg_match('#\Wtarlou[zs]e\W#i', $cont) or preg_match('#\Wmeuf+\W#i', $cont)	or preg_match('#abruti#i', $cont)
	or preg_match('#\Wimb[éeê]cile?\W#i', $cont) or preg_match('#viagra#i', $cont) or preg_match('#[^\(un\)] ?bais[eé][er]?s?#i', $cont)
	or preg_match('#\b(.*\W)?chi(ée?|er?|an?t?)(\W.*)?\b#i', $cont) or preg_match('#pu*t+(a*i+n|1|e)#i', $cont)
	or preg_match('#\Wna+ze?\W#i', $cont) or preg_match('#\Wosef\W#i', $cont) or preg_match('#\b(.*\W)?p[eé]*d[eé]*(\W.*)?\b#i', $cont)
	or preg_match('#\b(.*\W)?sa+l+(o+(p+(a+r+d?e?|e+))?|a+u+[dtxs]?|ig(o|au)[dtxs]?)(\W.*)?\b#i', $cont)
	or preg_match('#\b(.*\W)?([ea]n)?[ck]u+l+[eésr]*(\W.*)?\b#i', $cont) or preg_match('#LAURARÉAGIT!#', $cont)) { // Vulgarité
		return (array(false, 'revert immédiat', 'vulgarité'));}
	if (preg_match('#thilp|laura#i', $cont) and $niveau > 0) { // mention de thilp/LAURA
		return (array(false, 'revert immédiat', 'mention de LAURA ou thilp'));}		
	// Typographie	
	$tabFiltreTypo0 = array(
		'suite de 7 capitales/chiffres' => array('#[A-Z0-9]{7,}#', -10),
		'mot de 26 lettres' => array('#\w{26,}#i', -10),
		'suite de 5 non-lettres' => array('#\W{5,}#', -8),
		'majuscule ou chiffre dAns un m0t' => array('#[a-z][A-Z0-9]#', -10));
	// Erreurs wiki
	$tabFiltreWiki0 = array(
		'lien Fichier vide' => array('#\[\[(Fichier|Image):\(Exemple\.jpg\)?\]\]#', -10),
		'vikilien vide' => array('#\[\[(Titre du lien)?\]\]#', -10),
		'catégorie vide' => array('#\[\[Catégorie:(nom de la catégorie)?\]\]#', -10),
		'italique vide' => array("#''Texte italique''#", -13),
		'gras vide' => array("#'''Texte gras'''#", -12));
	$tabFiltreWiki2 = array_merge(array('défaut de wiki en fin d\'article' => array('#[^\]}](\b)*$#', -5)),$tabFiltreWiki0);
	// Première personne et assimilés
	$tabFiltreJe0 = array(
		'"coucou" etc.' => array('#\Wcoucou|ki+ko+u*\W#i', -20),
		'"chéri" etc.' => array('#\Wch[ée]rie?s?\W#i', -15),
		'lol' => array('#\W(l+o+l+|(m+|pt)d+r+)\W#i', -30),
		'première personne' => array('#\Wje|[tm]wa|chui|(m[ae]s?|mon)\W#i', -15));
	// Mots improbables ou douteux
	$tabFiltreAmbigu0 = array(
		'insulte possible A' => array('#\W(sale?|(l[ea]s?|une?) gros*e?s?|ga+[yi]+|pourr?i(e|t+e)?s?|pue|nu+ll?)\W#i', -10),
		'insulte possible B' => array("#\W(mo+che|b[eêéè]+te|vas? ?[st][e'’ ]*f[aeè]i*r+e*|gueule|bande?)\W|tagueule#i", -20),
		'insulte possible C' => array('#\W(b[aâ]+t+[aâ]+r+[dt]?|tap[eêèé]t+e|bouff?on)\W#i', -30),
		'langage familier A' => array("#\W(gars|[çcs]a va|ouais?|fout|ah bon|n[ ’']?i[mn]po+rte (kw?(ou?)?[ia]|quoi))\W#i", -15),
		'langage familier B' => array('#\W(kif+(er?|é|ent)?s?|yo+|yeah|crot+e|ca+ca+|(bla+)+|(wesh|ou([èeé]|ai)[sc][sc]?h))\W#i', -20),
		'anglicisme douteux' => array('#\W(big|mother|omg|best)\W#i', -20),
		'expression douteuse' => array('#\Wils? [éeè]t(ai[st]?|éè) une foi[xes]?\W#i', -10),
		'langage SMS' => array("#\W([vn]ou|sai|tro|c [^'’]|pa|dla|([a-z]+[0-9]+)+)\W#i", -15),
		'adresse au lecteur' => array("#\W(vous? [êeéè]tes?|t[u'’]? (est?|ais?|soi[ts]?)|[^nre] ?sa?lu?t)\W#i", -20),
		'cri de supporter' => array("#\W([aA]+(ll?e+[rz]|LL?E+[RZ])|vi+v[ea]*) (les?|la|l[ ’']|[A-Z])#i", -20));
	$tabFiltreAmbigu2 = array_merge(array(
		'terme inattendu' => array('#\W(horr?ible|d[eé]dicace)\W#i', -10),
		'propos douteux sur Vikidia' => array("#\W(fr\.)?vikidia(\.fr|\.com|\.org)? [cç]([ ’']est?|a)\W#i", -15)), $tabFiltreAmbigu0);
	// Chaînes de caractères douteuses
	$tabFiltreChaine0 = array(
		'7 caractères sans voyelle' => array('#[^aeiouy]{7,}#i', -15),
		'suite de 3 "x"' => array('#x{3,}#i', -20),
		'suite de 5 points' => array('#\.{5,}#', -10),
		'émoticône texte' => array('#\^\^|\W([:X]-?[D\(\)P@O]|-_-)\Wi#', -15),
		'suite de 4 voyelles' => array('#[aeiouy]{4,}#i', -15));
	$tabFiltreChaine2 = array_merge(array('4 espaces ou plus' => array('# {4,}#', -8)),$tabFiltreChaine0);
	// Points positifs
	$tabFiltrePositif0 = array(
		'majuscule en début de ligne' => array('#^[A-Z]|\n[A-Z]#', +1),
		'lien interne' => array('#\[\[[^\]]+\]\]#', +2),
		'titre de section' => array('#\b={2,5} ?.{3,} ?={2,5}\b#', +5),
		'insertion de modèle' => array('#\{\{[^\}]+\}\}#', +5),
		'balise <math>' => array('#<math>.+</math>#', +5),
		'référence' => array('#<ref>.+</ref>#', +5),
		'guillemets français' => array('#«[   ]?[^»]+[   ]?»#', +2),
		'catégorie' => array('#\[\[[Cc]atégorie:#', +5),
		'italique' => array("#[^']''[^']+''[^']#", +3),
		'gras' => array("#[^']'''[^']+'''[^']#", +2));
	
	switch ($niveau) { // Choix du tableau selon le niveau de profondeur
		case 0:
			$filtre = array_merge($tabFiltreTypo0,$tabFiltreWiki0,$tabFiltreJe0,$tabFiltreAmbigu0,$tabFiltreChaine0,$tabFiltrePositif0);
			break;
		case 1:
			$filtre = array_merge($tabFiltreTypo0,$tabFiltreWiki0,$tabFiltreJe0,$tabFiltreAmbigu0,$tabFiltreChaine0,$tabFiltrePositif0);
			break;
		case 2:
			$filtre = array_merge($tabFiltreTypo0,$tabFiltreWiki2,$tabFiltreJe0,$tabFiltreAmbigu2,$tabFiltreChaine2,$tabFiltrePositif0);
			break;}
	
	foreach($filtre as $motif => $param) { // Filtrage
		preg_match_all($param[0],$cont,$occurences);
		$nbre = count($occurences[0]);
		$score = $score + $param[1]*$nbre;
		$motifs[$motif] = $nbre;}
	if ($mef) { // Mise sous forme de chaîne du tableau des motifs
		$motifs = quelsMotifs($motifs);}
	if ($score > -19) {
		return array(true,$score,$motifs);}
	else {
		return array(false,$score,$motifs);}
}

/* Les espaces de nom :
<option value="" selected="selected">Tous</option>
<option value="0">(Principal)</option>
<option value="1">Discussion</option>
<option value="2">Utilisateur</option>
<option value="3">Discussion utilisateur</option>
<option value="4">Vikidia</option>
<option value="5">Discussion Vikidia</option>
<option value="6">Fichier</option>
<option value="7">Discussion fichier</option>
<option value="8">MediaWiki</option>
<option value="9">Discussion MediaWiki</option>
<option value="10">Modèle</option>
<option value="11">Discussion modèle</option>
<option value="12">Aide</option>
<option value="13">Discussion aide</option>
<option value="14">Catégorie</option>
<option value="15">Discussion catégorie</option>
<option value="100">Projet</option>
<option value="101">Discussion Projet</option>
<option value="102">Portail</option>
<option value="103">Discussion Portail</option>
<option value="104">Quiz</option>
<option value="105">Discussion Quiz</option> */


?>