<?php
	$doc= new \Iriven\DOMHtml();
	/* cas d'un fichier distant  ou non */
	$doc->loadXHTML($html='http://www.conforama.fr/service/catalogues');
	
	//pour recupere l'encodage de la page encours
	echo $doc->encoding;
	
	//recuperation du contenu de la balise meta->description
	echo $doc->getAttributeValue($attribute='content',$tag='meta',$option = array('name'=>'description','offset'=>'0')));
	
	//pour ajouter du contenu html à la suite de notre document
	$doc->appendHTML($content='ceci est mon super long contenu html',array('offset'=>'0'));
	
	//pour ajouter du contenu html dans notre document en ciblant une balise prticuliere
	//(ici, la 1er balise "div" ayant un attribut "class" dont la valeur est "main")
	$doc->appendHTML($content='ceci est mon super long contenu html',array('offset'=>'0','parent'=>'div','class'=>' main'));
	
	//test si au moins une balise de notre document possède un attribut "class"
	echo $doc->elementHasAttribute('class',$options=array());
	//comme précédemment vous pouvez filtre la (ou les ) balises concernée(s) grace aux options
	
	//replacer toutes les occurences d'une balise du document par une autre, tout en conservant leurs attribut et leur contenu
	// remplacer les "<p>" par des <div>
	echo $doc->renameElement($tagName='p',$newname='div',$options=array());
	
	//afficher le document
	echo $doc->saveHTML();

/*cas d'une chaine de caractere*/
	$doc= new \Iriven\DOMHtml();
	$doc->loadXHTML($html); //avec $html fourni ci dessous
//le procedé reste le même. vous pouvez reprendre les exemples precedents avec la chaine ci-dessous;

$html ='
<!DOCTYPE HTML>
<html>
    <head>
    <meta charset="UTF-8">
    <meta http-equiv="content-type" content="text/html; charset=UTF-8">
   
    <meta name="description" content="Lot&#x20;de&#x20;2&#x20;shortys&#x20;en&#x20;coton&#x3a;&#x20;Shortys&#x20;en&#x20;jersey&#x20;de&#x20;coton&#x20;biologique&#x20;avec&#x20;fond&#x20;doubl&eacute;.&#x20;">
    <meta name="viewport" content="width=1000px">
    <meta property="og:title" content="Lot&#x20;de&#x20;2&#x20;shortys&#x20;en&#x20;coton">
	<meta property="og:description" content="Lot&#x20;de&#x20;2&#x20;shortys&#x20;en&#x20;coton&#x3a;&#x20;Shortys&#x20;en&#x20;jersey&#x20;de&#x20;coton&#x20;biologique&#x20;avec&#x20;fond&#x20;doubl&eacute;.&#x20;">
	<meta property="og:site_name" content="H&amp;M">
	<meta property="og:type" content="website">
		
	<meta property="og:url" content="http://www2.hm.com/fr_fr/productpage.0224008001.html">
	<meta property="fb:app_id" content="1433700643510498" >
    
 	 <meta name="keywords" content="Lot&#x20;de&#x20;2&#x20;shortys&#x20;en&#x20;coton">
    <script type="text/javascript" src="/dtagent55_np3_5560.js" data-dtconfig="rid=RID_783266132|rpid=168843414|domain=hm.com|tp=500,50,0"></script><link href="/etc/designs/hm.css" rel="stylesheet" type="text/css">

<script type="text/javascript" src="/etc/designs/hm/clientlibs/shared/head.min.js"></script>

<link rel="stylesheet" href="/etc/designs/hm/clientlibs/desktop/modern.min.css" type="text/css">

<link rel="stylesheet" href="/etc/designs/hm/clientlibs/shared.min.css" type="text/css">
<link rel="stylesheet" href="/etc/designs/hm/clientlibs/desktop.min.css" type="text/css">
    <link rel="icon" type="image/vnd.microsoft.icon" href="/etc/designs/hm/favicon.ico">
    <link rel="shortcut icon" type="image/vnd.microsoft.icon" href="/etc/designs/hm/favicon.ico">
        <link rel="canonical" href="/fr_fr/productpage.0224008001.html"/>
       <title>Lot de 2 shortys en coton | H&amp;M</title>
             <script type="text/javascript" src="//libs.coremetrics.com/eluminate.js"></script>
<script type="text/javascript">cmSetClientID("90406168", false, "msp.hm.com", "hm.com")</script>
    <div class="parbase pagegoogletag pageview">uiadfuifz eqiuhzeuifg
 </div>
</head>
    <body>
    <div name="user10" style="display:none" class="header-global"/><!-- Header -->
    <div class="wrapper"><div class="testglobal"/>';
