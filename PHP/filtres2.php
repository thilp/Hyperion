<?php
  $tabFiltreVulgarite = array ( // la correspondance d’un de ces motifs entraîne systématiquement la révocation de la version filtrée
    '~\b[ck]onn*(?:e?s?|err?i+e*s?|a+r[dt]?s?|a+s+es?)?\b~i',
    '~\bsa+l+(?:o+p*e?(?:rie?)?|(?:au|o)[td]?[sx]?)\b~i',
    '~\bpu*t(?:e|a?in|1)s?\b~i',
    '~\b(?:e[mn])?m+e+r+d+[eé]+s?\b~i',
    '~\b[fs]uck(?:er|ing)?\b~',
    '~\bbit+e|teub|pin+e|dick|cock\b~i',
    '~\b(?:[sjmt]e|en) branl(er?|és?)\b~i',
    '~\bcouil+es?\b~i',
    '~\btarlouze?|p[eé]?d[eé]?\b~i',
    '~\bmeuf\b~i',
    '~\ben[ck]ull?(?:ée?s?|er)\b~i',
    '~\bna+ze\b~i',
    '~\b[tm]a gueule\b~i',
    '~\bd[eéè]gueu?(?:ll?(?:é|er?|ass?es?))?\b~i'
  );
  
  $tabFiltreBacasable = array (
    '~[bcdfghjklmnpqrstvwxz]{4,}~i', // plus de 4 consonnes à la suite
    '~[aeiouy]{3,}~i', // plus de 3 voyelles à la suite
    '~qy|dl|[bcfghjklmnpqrstvxz]x|[bcdfghjklmnpstxz]w|cf|cj|hh~i', // suite de lettres jamais présente en français, anglais et allemand
    '~\b([a-zçéè@()[]\'"A-Z.,$!?;:])\1{5,}\b~', // répétition du même caractère plus de 6 fois
    '~\b[A-Z0-9]{7,}\b~', // plus de 7 capitales ou chiffres à la suite
    '~\b[a-zéèêçà]+[A-Z]~', // capitale à l’intérieur d’un mot
  );
  
  $tabFiltreAntivandalisme = array (
    '~(\w{4,})\1{2,}~', // motif de plus de 4 lettres répété plus de trois fois à la suite
    '~\bgiratina|gaas|szyx|thilp|laura\b~i', // termes utilisés pour certains vandalismes
  );
  
  $tabFiltreInfosperso = array (
    '~\b\w+@\w+\.\w{2,4}\b~', // adresse courriel
    '~\bwww\.facebook\.com/\w+\b~i', // page Facebook
  );
  
  $tabFiltreCaricatures = array (
    '~\bmagistra(?:le?|ux?)|merveilleu(?:x|ses?)?|magnifiques?|splendides?|sublimes?|trop? b(?:e?au[sx]?|os?|iens?)|g[eé]nia(?:le?s?|ux?)|[eé]patante?s?|formidables?|parfaite?s?\b~i',
    '~\bmoche|hideu(?:x|se?s?)?|d[éèe]go[uûù]tant?e?s?|horr?ibles?|infect|r[eéè]pugnant?e?s?\b~i',
  );
  
  $tabFiltreReferencesinacceptables = array (
    '~\bfdesouche\.com\b~i',
    '~\bdefrancisation\.com\b~i',
    '~\bfromageplus\.wordpress\.com\b~i',
    '~\bislamisation\.fr\b~i',
    '~\bpolemia\.com\b~i',
    '~\bfr\.novopress\.info\b~i',
    '~\bsynthesenationale\.hautetfort\.com\b~i',
    '~\bfrontnational\.com\b~i',
    '~\blesalonbeige\.blogs\.com\b~i',
    '~\bradiocourtoisie\.net\b~i',
    '~\blibertepolitique\.com\b~i',
    '~\bbloc-identitaire\.com\b~i',
  );
?>
