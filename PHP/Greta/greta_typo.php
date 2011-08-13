<?php
// Fonctions utilisées par la correction typographique
// Fichier appelé par greta_calcul.php (connait donc déjà edit.php)

// Fonctions annexes **************************************

function corr($expratio,$rempl,&$texte){
  // Remplace $expratio par $rempl
  // Utilise des preg_replace si $expratio commence par ~
  // Sinon, utilise str_replace (plus rapide)
  if(substr($expratio,0,1) == '~'){
    // utilise preg_replace
    $texte = preg_replace($expratio,$rempl,$texte);
  }
  else{
    // utilise str_replace
    $texte = str_replace($expratio,$rempl,$texte);
  }
}

// Fonctions principales **********************************

function correction_orthographique_locale(&$t){
  // Expressions figées
  corr(' en terme de ',' en termes de ',$t);
  corr('quelques temps','quelque temps',$t);
  corr(' fort intérieur',' for intérieur',$t);
  
  // Adverbes
  corr('~différ[ea]mm?ent~','différemment',$t);
  corr('~suffis[ea]mm?ent~','suffisamment',$t);
  corr('~appar[ea]mm?ent~','apparemment',$t);
  corr('~vaill[ea]mm?ent~','vaillamment',$t);
  corr('~const[ae]mm?ent~','constamment',$t);
  corr('~bruy[ae]mm?ent~','bruyamment',$t);
  corr('~courr?[ae]mm?ent~','couramment',$t);
  corr('~fréqu[ae]mm?ent~','fréquemment',$t);
  corr('~nott?[ae]mm?ent~','notamment',$t);
  
  // Anglicismes
  corr('adept','adepte',$t);
  corr('aggress','agress',$t);
  corr('traffic','trafic',$t);
  corr('emission','émission',$t);
  corr('evident','évident',$t);
  corr(' license',' licence',$t);
  corr('reunion','réunion',$t);
  corr('connection','connexion',$t);
  corr('language','langage',$t);
  corr('addresse','adresse',$t);
  corr('hazard','hasard',$t);
  
  // Œ/œ
  corr('choeur','chœur',$t);
  corr('coeur','cœur',$t);
  corr('moeurs','mœurs',$t);
  corr('oeuf','œuf',$t);
  corr('oeil','œil',$t);
  corr('oeuvre','œuvre',$t);
  corr('Goering','Göring',$t);
  corr('Gœbbels','Goebbels',$t);
  corr('Koenig','König',$t);
  corr('soeur','sœur',$t);
  corr('foetus','fœtus',$t);
  corr('Gœthe','Goethe',$t);
  corr('noeud','nœud',$t);
  corr('oedème','œudème',$t);
  corr('oedip','œdip',$t);
  corr('Oedipe','Œdipe',$t);
  corr('oenolog','œnolog',$t);
  corr('oesophage','œsophage',$t);
  corr('voeu','vœu',$t);
  corr('lœss','loess',$t);
  
  // Confusion masculin/féminin
  corr(' un espèce',' une espèce',$t);
  corr(' fait parti ',' fait partie ',$t);
  
  // Mots masculins finissant par « -ée »
  corr('apogé ','apogée ',$t);
  
  // Divers
  corr('~\b(p|P)ar ?app?ort?\b~','$1ar rapport',$t);
  corr('~quelque[ \-]?soit~','quel que soit',$t);
  corr('~quelque[ \-]?soient~','quels que soient',$t);
  corr('~\b([qQ])u?atres\b~i','$1uatre',$t);
  corr(' esiste',' existe',$t);
  corr('disfonction','dysfonction',$t);
  corr('rédition','reddition',$t);
  corr('rédibitoire','rédhibitoire',$t);
  corr('~\bplusieure?(?!s)\b~','plusieurs',$t);
  corr('~\b([jJ])aponnais(e?s?)\b~','$1aponais$2',$t);
  corr('parcequ','parce qu',$t);
  corr(' autours',' autour',$t);
  corr('chappelle','chapelle',$t);
  corr('répis','répit',$t);
  corr('bloquage','blocage',$t);
  corr('acoll','accol',$t);
  corr('~succint(e?s?)~','succinct$1',$t);
  corr('cauchemard','cauchemar',$t);
  corr('dilemne','dilemme',$t);
  corr('parmis','parmi',$t);
  corr('millieu','milieu',$t);
  corr('~\b[eé]th?ymologi~','étymologi',$t);
  corr('~\blitt?[eéèê]ratt?ure\s~','littérature',$t);
  corr('mirroir','miroir',$t);
  corr('mourrir','mourir',$t);
  corr('~sensé(e?s?) (?=être|faire|avoir)~','censé$1',$t);
  corr('occurence','occurrence',$t);
  corr('~succ[eéè]dée?s?~','succédé',$t);
  corr('vraissemblance','vraisemblance',$t);
  corr('abbréviation','abréviation',$t);
  corr('lattitude','latitude',$t);
  corr('~\b(r?)app?ell(?!e)~','$1appel',$t);
  corr('paralèle','parallèle',$t);
  corr('~\b(main|sou)tien[td]\b~','$1tien',$t);
  corr('~\btranquill?itée?\b~','tranquillité',$t);
  corr('criticable','critiquable',$t);
  corr('~\bcourt? d\'eau\b~','cours d’eau',$t);
  corr('~\b(la|en|cette) partis?\b~','$1 partie',$t);
}

function correction_typographique_locale(&$t){
  // MAJUSCULES/MINUSCULES
  // Mois
  corr(' Janvier',' janvier',$t);
  corr(' Février',' février',$t);
  corr('~(?<=en|\d|\{\{er\}\}|er|pendant|début|fin|\.) Mars\b~','mars',$t);
  corr('~(?<!er|\{\{er\}\}|\.)\bAvril\b~',' avril',$t);
  corr('~(?<!er|\{\{er\}\}|8|\.)\bMai\b~','mai',$t);
  corr(' Juin',' juin',$t);
  corr('~(?<!14|\.)\bJuillet~','juillet',$t);
  corr(' Août',' août',$t);
  corr('~(?<!11|\.)\bSeptembre~','septembre',$t);
  corr(' Octobre',' octobre',$t);
  corr('~(?<!11|\.)\bNovembre~','novembre',$t);
  corr(' Décembre',' décembre',$t);
  // Jours
  corr('~(?<!\.)\bLundi\b~','lundi',$t);
  corr('~(?<!\.)\bMardi(?! [Gg]ras)\b~','mardi',$t);
  corr('~(?<!\.)\bMercredi\b~','mercredi',$t);
  corr('~(?<!\.)\bJeudi(?! [nN]oir)\b~','jeudi',$t);
  corr('~(?<!\.)\bVendredi\b~','vendredi',$t);
  corr('~(?<!\.)\bSamedi\b~','samedi',$t);
  corr('~(?<!\.)\bDimanche\b~','dimanche',$t);
  // Institutions, pays, etc.
  corr('Académie Française','Académie française',$t);
  corr('~(Amérique|Afrique|Asie|Europe|Océanie) Centrale~','$1 centrale',$t);
  corr('~(Afrique|Amérique|Europe|Asie|Océanie) du nord~','$1 du Nord',$t);
  corr('~(Afrique|Amérique|Europe|Asie|Océanie) du sud~','$1 du Sud',$t);
  corr('~(Afrique|Amérique|Europe|Asie|Océanie) de l[\'’]est~','$1 de l’Est',$t);
  corr('~(Afrique|Amérique|Europe|Asie|Océanie) de l[\'’]ouest~','$1 de l’Ouest',$t);
  corr('Asie du Sud-est','Asie du Sud-Est',$t);
  corr('~[eE]mpire [oO]ttoman~','Empire ottoman',$t);
  corr('~[mM]ass?achuss?ett?s?~','Massachusetts',$t);
  corr('New-York','New York',$t);
  corr('Premier Ministre','Premier ministre',$t);
  corr('Premiers Ministres','Premiers ministres',$t);
  corr('province du Québec','province de Québec',$t);
  corr('~[rR][eéèê]publique [tT]ch[eéêè]que~','République tchèque',$t);
  corr('Révolution Française','Révolution française',$t);
  corr('~saint[ \-]empire[ \-]romain[ \-]germanique~i','Saint Empire romain germanique',$t);
  corr('tiers Monde','tiers monde',$t);
  
  // ACCENTS
  // Aigus
  corr('~\b(?<=a|été|est|fut|serait) cr[éeè]e(e?s?)\b~',' créé$1',$t);
  corr('~\b(?:etats[\- ]unis?|usa)\b~i','États-Unis',$t);
  corr('~\b[eéè]l[eéè]ctr(on)?ique(s?)\b~','électr$1ique$2',$t);
  corr('~[eéè]l[eéè]ctricit[eéè]e?\b~','électricité',$t);
  corr('~[eéè]l[eéè]ctrifi[eéè](e?s?)\b~','électrifié$1',$t);
  corr('Eglise','Église',$t);
  corr('Egypte','Égypte',$t);
  corr('Ecosse','Écosse',$t);
  corr('Etat','État',$t);
  corr('~\b(m|p|fr)[eé]re(s?)\b~','$1ère$2',$t);
  corr('~po[eèêë]sie~','poésie',$t);
  corr('~\b(d|D)[éeèê][cç][éeèê]s?\b~','$1écès',$t);
  corr('~\b(d|D)[éeèê][cç][éeèê]dé(e?s?)\b~','$1écédé$2',$t);
  corr('~\b(d|D)[éeè]v[eèé]ll?opp?~','$1évelopp',$t);
  corr(' ecole',' école',$t);
  corr(' heros',' héros',$t);
  corr(' eco',' éco',$t);
  corr(' Eco',' Éco',$t);
  corr('ecrivain','écrivain',$t);
  corr(' emirat',' émirat',$t);
  corr(' Emirat',' Émirat',$t);
  corr(' equateur',' équateur',$t);
  corr(' Equateur',' Équateur',$t);
  corr('~[eéèê]v[eéèê]nement~','évènement',$t); // ortho 1990+
  corr(' evid',' évid',$t);
  corr(' Evid',' Évid',$t);
  corr('~n[eéèê](c|ç|ss?)[eéèê]ss?~','nécess',$t);
  corr('~pok[eéè]mons?~i','Pokémon',$t);
  corr('~\bt[eéèê]l[eéèê]scope?\b~','télescope',$t);
  corr('Clémenceau','Clemenceau',$t);
  corr('éxéc','exéc',$t);
  corr('~int[eéèê]rr?[eéèê]ss?ant?~','intéressant',$t);
  corr('~\bacc[eèê]d~','accéd',$t);
  corr('~\bacc[eéê]s?\b~','accès',$t);
  corr('~\b[eèé]l[eèé]vé(e?s?)\b~','élevé$1',$t);
  // Trémas
  corr(' héroine ',' héroïne ',$t);
  corr(' aigue ',' aigüe ',$t);
  corr('Taiwan','Taïwan',$t);
  corr('ambigue','ambigüe',$t);
  corr('ambiguité','ambigüité',$t);
  // Circonflexes
  corr('Age','Âge',$t);
  corr(' age',' âge',$t);
  corr('symptome','symptôme',$t);
  corr('syndrôme','syndrome',$t);
  corr('binome','binôme',$t);
  corr('polynome','polynôme',$t);
  corr('~(?<=aussi|plu|bien|si|\W)tot\b~','tôt',$t);
  corr('~\bdû(e|s|es)\b~','du$1',$t);
  corr('débacle','débâcle',$t);
  corr('~\bMacon\b~','Mâcon',$t);
  corr(' ame ',' âme ',$t);
  corr('~\B([^u])atre(s?)\b~','$1âtre$2',$t);
  corr(' etre',' être',$t);
  corr(' Etre',' Être',$t);
  corr('lache','lâche',$t);
  corr(' meme',' même',$t);
  corr('égoût','égout',$t);
  corr('~\bint[eéèê]r[eéèê]t~','intérêt',$t);
  corr('~\bint[eéèê]r[eéèê]ss?~','intéress',$t);
  // Graves
  corr('~ tr[eé]s\b~','très',$t);
  corr("~(?<=')[àa] ?p(oste)?riori(?!')~","''a p$1riori''",$t);
  corr('~\ba partir\b~','à partir',$t);
  corr('A partir','À partir',$t);
  corr('deça','deçà',$t);
  corr('~\bapr[eé]s\b~','après',$t);
  corr('~p[éeê]lerinage~','pèlerinage',$t);
  corr('~\bp(?:œ|o[éeëê])(t)e~','poè$1e',$t);
  corr('~contrep[éeèê]te?rie~','contrepèterie',$t);
  corr('~^A(?= )|(?<=\. )A(?= )~','À',$t);
  corr('~\b(r|R)[éeê]gle~','$1ègle',$t);
  
  // CÉDILLES
  corr('~(?<!\w|\.)ca\b~','ça',$t);
  corr('~\bCa\b~','Ça',$t);
  corr('facade','façade',$t);
  corr(' facon',' façon',$t);
  corr('francais','français',$t);
  corr('garcon','garçon',$t);
  corr('glacon','glaçon',$t);
  
  // LIAISONS
  // Apostrophes
  corr('~\b([sS])i il(s?)\b~iU','$1’il$2',$t);
  corr('~\ba[\'’-]?t[\'’-]?(il|elle|on)\b~','a-t-$1',$t);
  corr('~\b(est|ont|avai(?:en)?t|étai(?:en)?t|serai(?:en)?t|sont|seront)[\'-’](ils?|elles?|on)\b~','$1-$2',$t);
  corr('\B[\'’] \B','’',$t);
  // Traits d’union
  corr('~(?<=[a-zA-Z]) -(?=[a-zA-Z])|(?<=[a-zA-Z])- (?=[a-zA-Z])~','-',$t);
  corr('~\bc[\'’\- ]est[\- ]à[\- ]dire\b~','c’est-à-dire',$t);
  corr('~quelques ?uns~','quelques-uns',$t);
  corr('~\bau dessous\b~','au-dessous',$t);
  corr('~\bau dessus\b~','au-dessus',$t);
  corr(' en-dessous',' en dessous',$t);
  corr('au delà','au-delà',$t);
  corr('là bas','là-bas',$t);
  corr('jusque là','jusque-là',$t);
  corr('Moyen-Âge','Moyen Âge',$t);
  corr('plateforme','plate-forme',$t);
  corr('vis à vis','vis-à-vis',$t);
  corr('Royaume Uni','Royaume-Uni',$t);
  corr('~\b(elle|lui) mêmes?\b~','$1-même',$t);
  corr('~(elles|eux) mêmes?~','$1-mêmes',$t);
  corr('~(?<=un |l\'|l’)après midi~','après-midi',$t);
  corr('~(?<=ant |dans |l\'|l’)[eE]ntre[ \-][dD]eux[ \-][gG]uerres?\b~','Entre-deux-guerres',$t);
  // Traits d’union dans les nombres
  corr('quatre vingt','quatre-vingt',$t);
  corr('~\b(soixante|quatre-vingt) dix\b~','$1-dix',$t);
  corr('~\b(vingt|trente|quarante|cinquante|soixante)[ \-]et[ \-](un|onze)s?\b~','$1-et-$2',$t);
  corr('~\b(dix|vingt|trente|quarante|cinquante|soixante) (deux|trois|quatre|cinq|six|sept|huit|neuf)\b~','$1-$2',$t);
  corr('~\b(deux|trois|quatre|cinq|six|sept|huit|neuf) cents?\b~','$1-cents',$t);
  corr('~\b(deux|trois|quatre|cinq|six|sept|huit|neuf) milles?\b~','$1-mille',$t);
  corr('~\b(soixante|quatre-vingt) (onze|douze|treize|quatorze|quinze|seize)\b~','$1-$2',$t);
  corr('~\b(\d{3,4}) ?[\-] ?(\d{3,4})\b~','$1–$2',$t);
  
  // ABRÉVIATIONS
  // Titres
  corr('Mme','M{{me}}',$t);
  corr('Mlle','M{{exp|lle}}',$t);
  corr('Me ','M{{e}}',$t);
  corr('Dr ','D{{exp|r}}',$t);
  // Abréviations courantes nécessitant un point
  corr('~\bp (?=\d)~','p. ',$t);
  corr('~\betc(…|\.\.\.)?(?= |,|\))~','etc.',$t);
  // Numéraux
  corr('~\b([IVX\d]+)(?:e|è)(?:me?)?(?! siècle)\b~','$1{{e}}',$t);
  corr('~\b([I1])er\b~','$1{{er}}',$t);
  corr('~\b([I1])(?:e|è)(re)\b~','$1{{re}}',$t);
  
  // PONCTUATION
  // Correction HTML
  corr('&nbsp;',' ',$t);
  // Ponctuation basse : espacement
  corr('~(?<!\.)\.\.\.+~','…',$t);
  corr('..','.',$t);
  corr(' etc…',' etc.',$t);
  corr('~(?<=\w) etc.~',', etc.',$t);
  corr('~,(?!\d| )~',', ',$t);
  corr("~…(?![?!, )\n])~",'… ',$t);
  corr(' ,',',',$t);
  corr('~(?<!,) …~','…',$t);
  corr(', …',', etc.',$t);
  corr("~\.(?![a-z0-9]| |\n|\)|<)~",'. ',$t);
  corr("~(?<=\.|:|…) +\n~","\n",$t);
  corr(' .','.',$t);
  // Ponctuation haute
  corr("~(?<!\[[Cc]atégorie|\[wp|\[en|\[nl|\[es|\n)([!;:])~",' $1',$t);
  corr('~([!;:])(?=\w)~','$1 ',$t);
  corr('~(?<!\w)\?(?=\w)~','? ',$t); // ? peut appartenir à une URL (1/2)
  corr('~(?<=\w)\?(?!\w)~',' ?',$t); // ? peut appartenir à une URL (2/2)
  corr('~((?:ht|f)tps?) :~','$1:',$t); // Contre-correction pour les URL
  // Ponctuation symétrique
  corr('~(?<=\w) ?([\[\(]) ?(?=\w)~',' $1',$t);
  corr('~(?<=\w) ?([\]\)]) ?(?=\w)~','$1 ',$t);
  corr('~ "([^"]+)"(?=[ .,\)])~',' « $1 »',$t);
  
  // MEDIAWIKI
  // {{formatnum:x}}
  corr('~(?<=\d) (?=\d)~','',$t);
  corr('~(?<=\s)(\d{5,100})\b~','{{formatnum:$1}}',$t);
  // {{s|xx}}
  corr('~\b([XIV]+) ?(?:[eéè]?(?:me)?|\{\{e\}\}) s(?:i[èeéê]cle|\.)\b~','{{s|$1}}',$t);
  corr('~\bI ?[eé]r? s(?:i[èeé]cle|\.)~','{{s|I|er}}',$t);
  // Avant/après Jésus-Christ
  corr('~av(ant|\.)? (?:J[éeè]sus?[ \-]Ch?rist?|J\.?[ \-]C\.?)~i','{{avjc}}',$t);
  corr('~apr?([èeé]s?|\.)? (?:J[éeè]sus?[ \-]Ch?rist?|J\.?[ \-]C\.?)~i','{{apjc}}',$t);
  corr('{{avjc}}.','{{avjc}}',$t);
  corr('{{apjc}}.','{{apjc}}',$t);
  // Divers
  corr('<br />',"\n",$t);
}

function correction_typographique_globale(&$t){
  // Listes à puces et numérotées
  corr("~(?<=\n)- ?(?=\w)~",'* ',$t); // Conversion en liste à puces MW
  corr("~(?<=\n[*#])(?=\w)~",' ',$t); // Puces et numéros non collées au texte
  corr("~(?<=\n\*[^*#])([^;\n]*)[\.,: ]*(?=\n\*)~U",'$1 ;',$t); // Ponctuation inter-puces
  corr("~(?<=\n\*[^*#])([^.\n]*)(?=\n[^*])~U",'$1.',$t); // Ponctuation de fin de liste à puces
  corr("~(?<=\n#[^*#])([^.\n]*)(?=\n)~U",'$1.',$t); // Ponctuation inter-liste numérotée
  
  // Unités
  corr('~\b(km|m|cm|mm)s?[ \-]carrés?\b~','$1²',$t);
  
  // ESPACEMENTS
  // Unités de mesure
  corr('~(?<=\d)(%|cm|m|mm|km|min|s|l|h|g|kg|mg|j|K|N|w)(?=[ \.,…{])~i',' $1',$t);
  
  // Contre-corrections diverses
  corr('~(?<=]|})([:;?!])~',' $1',$t);
  corr('….','…',$t);
  corr('  ',' ',$t);
}

function correction_syntaxique_locale(&$t){
  corr('~\bvoire? m[eéèê]me\b~','voire',$t);
  corr('~\bpuis ensuite\b~','puis',$t);
  corr('réduire au maximum','réduire au minimum',$t);
  corr(', et ',' et ',$t);
  corr('~(?<=\bmais|\boù|\bet|\bdonc|\bor|\bni|\bcar), (?=il|elle|ils|elles|nous|vous|on|je|tu|le|la|un|une|de|des|l\'|l’|[A-Z]|d\'|d’)~',' ',$t);
}

function correction_syntaxique_globale(&$t){
  
}

function correction_fautes_frappe_locale(&$t){
  corr(' aps ',' pas ',$t);
  corr(' dnas ',' dans ',$t);
  corr('~\bect\b~','etc',$t);
  corr('acceuil','accueil',$t);
  corr('~\bets\b~','est',$t);
  corr('égaement','également',$t);
  corr('récopense','récompense',$t);
  corr('rencpntre','rencontre',$t);
  corr('Allemange','Allemagne',$t);
  corr(' utils',' utilis',$t);
}
?>
