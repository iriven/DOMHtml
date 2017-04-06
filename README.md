# IRIVEN FRANCE: DOMHtml

classe PHP permettant de manipuler de façon dynamique des fichiers html ,en exploitant uniquement la puissance des bibliothèques DOMDocument et DOMXpath.
(pas de regex).

la finalité de ce projet est de rendre disponible toutes les fonctionalités nececessaires à la manipulation online
des templates php(site web) sans faire appel aux expressions regulieres, ni au javascript.
nous vous encourageons donc à contribuer activement au developpement de ce projet, soit à travers vos retours d'experiences utilisateurs, soit en nous signalant déventuels bugs.   

----------------------------------------------------------------------------- 

* Author: Alfred TCHONDJO 
* Date: 2014-09-22

-----------------------------------------------------------------------------

Revisions
									
* G1R0C0 : 	Creation du projet le 22/09/2014 (AT)
* G1R0C1 : 	Amelioration du code et ajout de nouvelles fonctionnlités le 02/04/2017 (AT)	


-----------------------------------------------------------------------------	

#Les Methodes publiques
-	public function appendCdata($content, $nodeName, $options=[]) 
