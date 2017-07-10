<?php
	include("../param/connect.php");
	include("../param/param.php");

	// récupération des paramètres de descripteurs
	if(isset($_GET['id'])){
		$w = " AND a.id_article = ".$_GET['id'];
		$desc = $_GET['titre'];
	 	$lien = "GetSpipArticleFullerene.php?id=".$_GET['id']; 
	}else{
		$w = " ";
		$desc = " ";
	 	$lien = "GetFullerene.php"; 
	}
	// récupération des paramètres de format
	if(isset($_GET['FontStyle_size'])){
		$FontStyle_size = $_GET['FontStyle_size'];
	}else{
		$FontStyle_size = "1";
	}

	// construction de la requête
	$sql = "SELECT d.id_document, d.fichier, d.largeur, d.hauteur, d.titre d_titre, a.titre a_titre
		FROM spip_documents d, spip_documents_articles da, spip_articles a
		WHERE d.id_document = da.id_document 
			AND a.id_article = da.id_article
			AND d.id_type = 1 
			AND	d.mode  =  'document' ".$w;
	
	// on envoie la requête 
	$req = mysql_query($sql) or die('Erreur SQL !<br>'.$sql.'<br>'.mysql_error());

	// récupère le nombre de document pour la fonction d'affichage aléatoire
	$totalRows = mysql_num_rows($req);
	
	header ("Content-type: model/vrml");
	//header ("Content-type: text/html");
?>
#VRML V2.0 utf8
NavigationInfo {
	headlight TRUE
	speed 1.0
	type "EXAMINE"
	visibilityLimit 0
}
PROTO Face [
	field 		MFInt32		CoorInd []  		#on defini le parametre de l'ordre des points d'une face
	exposedField 	MFVec3f		FacePoints []  		#on defini le parametre des coordonn3
	exposedField 	SFFloat		Transp 1		#on defini le parametre de transparence de l'image
	exposedField 	MFString	TextureUrl ""		#on defini le parametre de l'image de texture sur une face
	exposedField 	MFColor 	Couleurs []		#on defini le parametre des couleurs
	exposedField 	SFFloat		ColorTransp 0.5		#on defini le parametre de transparence des couleurs
	]
{
	Group {
		children [
			DEF i Shape { 
				geometry IndexedFaceSet {
					solid FALSE
					coord Coordinate {
						point IS FacePoints
					}
					coordIndex IS CoorInd
				}
				appearance Appearance {
					material Material {
						transparency IS Transp
					}
					texture ImageTexture {
						url IS TextureUrl
					}
				}
			}
			DEF c Shape { 
				geometry IndexedFaceSet {
					solid FALSE
					coord Coordinate {
						point IS FacePoints
					}
					coordIndex IS CoorInd
					color Color {
						color IS Couleurs
					}
					colorIndex IS CoorInd
					colorPerVertex TRUE
				}
				appearance Appearance {
					material Material {
						transparency IS ColorTransp
					}
				}
			}
		]
	}
} #fin du proto

PROTO FaceAncre [
	field	MFInt32		CoorInd []	#on defini le parametre de l'ordre des points d'une face
	exposedField MFString	FaceUrl ""	#on defini le parametre du lien hypertexte
	exposedField MFVec3f	FacePoints []	#on defini le parametre des coordonnees
	exposedField SFFloat	Transp 1	#on defini le parametre de transparence de l'image
	exposedField MFString	TextureUrl ""	#on defini le parametre de l'image de texture sur une face
	exposedField MFColor	Couleurs []	#on defini le parametre des couleurs
	exposedField SFFloat	ColorTransp 0.5	#on defini le parametre de transparence des couleurs
	exposedField SFBool	actifI FALSE	#on defini l'activation du timer des images
	exposedField SFBool	actifC FALSE	#on defini l'activation du timer des couleurs
	]
{
	DEF Ancre Anchor{			#le premier noeud defini le proto	
		url IS FaceUrl
		# Voici le paramètre qui permet de donner la frame
		parameter [ "" ]
		description "VAS Y"
		children [
			DEF F Face {
				TextureUrl	IS TextureUrl
				FacePoints	IS FacePoints 
				CoorInd		IS CoorInd
				Transp		IS Transp
				Couleurs	IS Couleurs
				ColorTransp	IS ColorTransp
			}
		]
	}

	DEF tempsIma TimeSensor {
		cycleInterval 0.01
		loop TRUE
		enabled IS actifI
	}

	DEF tempsCol TimeSensor {
		cycleInterval 2
		loop TRUE
		enabled IS actifC
	}

	DEF select Script {
		field 		MFString arrIma [
<?php
	// création du tableau des images avec une image par défaut
	while($data = mysql_fetch_assoc($req)) 
	{ 
		print("\"".$repFrag.$data['fichier']."\" ,\n");
	}
	print("\"".$repFrag.$imaDef."\" \n");
?>
			]
		field 		MFString arrlienpath [
<?php
	if ($totalRows>0){
		// déplace le curseur au début
		mysql_data_seek ($req, 0); 
		// création du tableau des liens attaché aux images
		while($data = mysql_fetch_assoc($req)) 
		{ 
			print("\""."frmEvalIma.php?img=".$repFrag.$data['fichier']."&larg=".$data['largeur']."&haut=".$data['hauteur']."&titre=".$data['d_titre']."&id=".$data['id_document']."\" ,\n");
		}
	}
	print("\"\" ,\n");
	// libère le curseur
	mysql_free_result ($req);
?>
			]
		field		MFColor		arrCol [0 0 1, 1 1 1, 0 1 1, 0 0 1, 1 0 0, 1 1 0, 1 0 1, 0 0 0]
		field		SFInt32		choix 0
		field		SFInt32		choixlien 0
		field		SFNode		tI USE tempsIma
		eventIn		SFTime		newimage
		eventIn		SFTime		newcolor
		eventIn		SFTime		stopimage
		eventOut	MFString	winpath
		eventOut	MFString	lienpath
		eventOut	SFString	winDesc
		eventOut	MFColor	couleurs
		eventOut	MFInt32		indcoul
	url [
		"javascript:
		function newimage(){
			choix=Math.floor(<?php print($totalRows); ?>*Math.random());
			choixlien=Math.floor(<?php print($totalRows); ?>*Math.random());
			winpath[0]=arrIma[choix];
			lienpath[0] = arrlienpath[choix] ;
			winDesc = arrlienpath[choixlien];
		}
		function newcolor(){
			var a;
			for (a=0; a <  7; a++) {
				couleurs[a]=arrCol[Math.floor(7*Math.random())];
			}
		}
		function stopimage(){
			tI.enabled = FALSE;
		}
	"]
}

ROUTE tempsIma.cycleTime	TO select.newimage 
ROUTE select.winpath		TO F.TextureUrl
ROUTE select.lienpath		TO Ancre.url
ROUTE select.winDesc		TO Ancre.description
ROUTE tempsIma.cycleTime	TO select.stopimage 

ROUTE tempsCol.cycleTime	TO select.newcolor 
ROUTE select.couleurs		TO F.Couleurs

} #fin du proto
	
	DEF Fullerene Transform {
		rotation		0 0 0 0
		center			0 0 0
		scaleOrientation	0 0 0 0
		translation		0 0 0
		bboxCenter		0 0 0
		bboxSize		0 10 10
		children [
			Transform {                                    
				translation 0 4 0
				children [
					Anchor{	
						url ["<?php print($lien); ?>"]
						# Voici le paramètre qui permet de donner la frame
						parameter [ "" ]
						description "<?php print("Affiche la planête ".$desc); ?>"
						children [
							Shape {
								geometry Text {                     
									string ["<?php print($totalRows." ".$desc); ?>" ]
									fontStyle FontStyle {           
											family "SERIF"   #fonte
											style  "BOLD"    #style 
											size <?php print($FontStyle_size); ?>   #taille
											spacing 1.0      #espacement
											justify "BEGIN"  #positionne le centre
											horizontal TRUE  #horizontal ou non
											leftToRight TRUE #gauche a droite
										}
								}
							}
						]
					}
				]
			} 
			DEF Obj1 FaceAncre {
							FaceUrl ""       
					    		FacePoints [0.023,   -0.07494,   -3.55242,   
				-1.34628,   0.32285,   -3.27364,   
				-2.23068,   -0.58428,   -2.70132,   
				-1.78597,   -1.93008,   -2.3821,   
				-0.47591,   -2.31071,   -2.64897,   
				0.44851,   -1.36265,   -3.24697,   
				] 
							CoorInd [0, 1, 2, 3, 4, 5]
							Couleurs [0 1 1 ,0 0 0 ,1 0 1 ,1 0 1 ,1 0 0 ,1 1 0 ,]
						}
				DEF Obj2 FaceAncre {
							FaceUrl ""       
					    		FacePoints [0.023,   -0.07494,   -3.55242,   
				-1.34628,   0.32285,   -3.27364,   
				-1.32963,   1.70406,   -2.82302,   
				0.04997,   2.15987,   -2.82339,   
				0.88593,   1.06039,   -3.27422,   
				] 
							CoorInd [0, 1, 2, 3, 4]
							Couleurs [0 1 1 ,0 1 0 ,0 1 0 ,0 1 0 ,0 1 1 ,]
						}
				DEF Obj3 FaceAncre {
							FaceUrl ""       
					    		FacePoints [0.023,   -0.07494,   -3.55242,   
				0.88593,   1.06039,   -3.27422,   
				2.13705,   0.85879,   -2.70269,   
				2.58182,   -0.48717,   -2.38366,   
				1.75612,   -1.57334,   -2.64974,   
				0.44851,   -1.36265,   -3.24697,   
				] 
							CoorInd [0, 1, 2, 3, 4, 5]
							Couleurs [1 1 0 ,1 0 0 ,1 0 0 ,1 1 1 ,0 0 0 ,0 0 1 ,]
						}
				DEF Obj4 FaceAncre {
							FaceUrl ""       
					    		FacePoints [2.58182,   -0.48717,   -2.38366,   
				3.32838,   -0.43039,   -1.13854,   
				3.21693,   -1.46205,   -0.21326,   
				2.35389,   -2.59709,   -0.49129,   
				1.63971,   -2.65148,   -1.68271,   
				1.75612,   -1.57334,   -2.64974,   
				] 
							CoorInd [0, 1, 2, 3, 4, 5]
							Couleurs [0 1 1 ,1 1 0 ,0 1 0 ,1 0 1 ,1 1 0 ,1 1 0 ,]
						}
				DEF Obj5 FaceAncre {
							FaceUrl ""       
					    		FacePoints [2.58182,   -0.48717,   -2.38366,   
				3.32838,   -0.43039,   -1.13854,   
				3.34529,   0.95071,   -0.68805,   
				2.60899,   1.74742,   -1.65466,   
				2.13705,   0.85879,   -2.70269,   
				] 
							CoorInd [0, 1, 2, 3, 4]
							Couleurs [1 0 0 ,1 1 0 ,1 1 1 ,0 0 1 ,0 0 1 ,]
						}
				DEF Obj6 FaceAncre {
							FaceUrl ""       
					    		FacePoints [1.75612,   -1.57334,   -2.64974,   
				1.63971,   -2.65148,   -1.68271,   
				0.26019,   -3.10713,   -1.68211,   
				-0.47591,   -2.31071,   -2.64897,   
				0.44851,   -1.36265,   -3.24697,   
				] 
							CoorInd [0, 1, 2, 3, 4]
							Couleurs [0 0 1 ,1 0 1 ,1 1 0 ,0 1 0 ,1 1 1 ,]
						}
				DEF Obj7 FaceAncre {
							FaceUrl ""       
					    		FacePoints [0.88593,   1.06039,   -3.27422,   
				0.04997,   2.15987,   -2.82339,   
				0.50149,   3.01012,   -1.82049,   
				1.80927,   2.79941,   -1.22329,   
				2.60899,   1.74742,   -1.65466,   
				2.13705,   0.85879,   -2.70269,   
				] 
							CoorInd [0, 1, 2, 3, 4, 5]
							Couleurs [1 1 0 ,0 1 1 ,1 1 1 ,1 1 0 ,1 0 0 ,1 1 1 ,]
						}
				DEF Obj8 FaceAncre {
							FaceUrl ""       
					    		FacePoints [1.80927,   2.79941,   -1.22329,   
				1.70997,   3.10198,   0.19425,   
				2.41456,   2.33964,   1.11912,   
				3.25025,   1.24013,   0.66829,   
				3.34529,   0.95071,   -0.68805,   
				2.60899,   1.74742,   -1.65466,   
				] 
							CoorInd [0, 1, 2, 3, 4, 5]
							Couleurs [0 0 1 ,0 1 0 ,0 0 1 ,1 1 1 ,1 0 0 ,1 0 0 ,]
						}
				DEF Obj9 FaceAncre {
							FaceUrl ""       
					    		FacePoints [1.80927,   2.79941,   -1.22329,   
				1.70997,   3.10198,   0.19425,   
				0.34088,   3.49999,   0.47317,   
				-0.40603,   3.44313,   -0.77188,   
				0.50149,   3.01012,   -1.82049,   
				] 
							CoorInd [0, 1, 2, 3, 4]
							Couleurs [1 0 1 ,0 0 1 ,0 0 0 ,1 1 0 ,0 1 0 ,]
						}
				DEF Obj10 FaceAncre {
							FaceUrl ""       
					    		FacePoints [0.04997,   2.15987,   -2.82339,   
				-1.32963,   1.70406,   -2.82302,   
				-2.19799,   2.11835,   -1.81962,   
				-1.72599,   3.00716,   -0.77147,   
				-0.40603,   3.44313,   -0.77188,   
				0.50149,   3.01012,   -1.82049,   
				] 
							CoorInd [0, 1, 2, 3, 4, 5]
							Couleurs [1 0 1 ,0 1 1 ,0 1 1 ,1 1 1 ,0 0 0 ,1 0 0 ,]
						}
				DEF Obj11 FaceAncre {
							FaceUrl ""       
					    		FacePoints [-1.72599,   3.00716,   -0.77147,   
				-2.35845,   2.60849,   0.47424,   
				-1.64375,   2.66299,   1.66555,   
				-0.26422,   3.11848,   1.66508,   
				0.34088,   3.49999,   0.47317,   
				-0.40603,   3.44313,   -0.77188,   
				] 
							CoorInd [0, 1, 2, 3, 4, 5]
							Couleurs [0 0 0 ,0 1 1 ,0 0 0 ,1 1 0 ,1 0 0 ,0 1 1 ,]
						}
				DEF Obj12 FaceAncre {
							FaceUrl ""       
					    		FacePoints [-1.72599,   3.00716,   -0.77147,   
				-2.35845,   2.60849,   0.47424,   
				-3.22155,   1.47344,   0.19603,   
				-3.1223,   1.17037,   -1.22153,   
				-2.19799,   2.11835,   -1.81962,   
				] 
							CoorInd [0, 1, 2, 3, 4]
							Couleurs [1 0 1 ,0 0 0 ,1 0 0 ,1 1 0 ,1 0 0 ,]
						}
				DEF Obj13 FaceAncre {
							FaceUrl ""       
					    		FacePoints [-1.32963,   1.70406,   -2.82302,   
				-1.34628,   0.32285,   -3.27364,   
				-2.23068,   -0.58428,   -2.70132,   
				-3.13838,   -0.15109,   -1.65266,   
				-3.1223,   1.17037,   -1.22153,   
				-2.19799,   2.11835,   -1.81962,   
				] 
							CoorInd [0, 1, 2, 3, 4, 5]
							Couleurs [1 1 1 ,0 0 1 ,0 0 1 ,0 0 0 ,1 0 0 ,1 1 0 ,]
						}
				DEF Obj14 FaceAncre {
							FaceUrl ""       
					    		FacePoints [-3.13838,   -0.15109,   -1.65266,   
				-3.25455,   -1.22898,   -0.68546,   
				-3.34959,   -0.93889,   0.67091,   
				-3.33263,   0.44215,   1.12147,   
				-3.22155,   1.47344,   0.19603,   
				-3.1223,   1.17037,   -1.22153,   
				] 
							CoorInd [0, 1, 2, 3, 4, 5]
							Couleurs [1 1 0 ,1 1 1 ,1 1 1 ,1 1 1 ,0 1 0 ,0 0 1 ,]
						}
				DEF Obj15 FaceAncre {
							FaceUrl ""       
					    		FacePoints [-3.13838,   -0.15109,   -1.65266,   
				-3.25455,   -1.22898,   -0.68546,   
				-2.41884,   -2.3285,   -1.13637,   
				-1.78597,   -1.93008,   -2.3821,   
				-2.23068,   -0.58428,   -2.70132,   
				] 
							CoorInd [0, 1, 2, 3, 4]
							Couleurs [1 1 0 ,1 0 0 ,0 0 1 ,0 1 1 ,0 1 1 ,]
						}
				DEF Obj16 FaceAncre {
							FaceUrl ""       
					    		FacePoints [-0.47591,   -2.31071,   -2.64897,   
				0.26019,   -3.10713,   -1.68211,   
				-0.34546,   -3.48836,   -0.49018,   
				-1.71453,   -3.09035,   -0.21119,   
				-2.41884,   -2.3285,   -1.13637,   
				-1.78597,   -1.93008,   -2.3821,   
				] 
							CoorInd [0, 1, 2, 3, 4, 5]
							Couleurs [1 1 0 ,0 0 1 ,0 0 0 ,1 0 0 ,0 0 1 ,1 0 0 ,]
						}
				DEF Obj17 FaceAncre {
							FaceUrl ""       
					    		FacePoints [0.26019,   -3.10713,   -1.68211,   
				-0.34546,   -3.48836,   -0.49018,   
				0.40093,   -3.4315,   0.75507,   
				1.72091,   -2.99576,   0.75444,   
				2.35389,   -2.59709,   -0.49129,   
				1.63971,   -2.65148,   -1.68271,   
				] 
							CoorInd [0, 1, 2, 3, 4, 5]
							Couleurs [1 0 1 ,1 1 1 ,0 1 1 ,0 0 0 ,0 0 0 ,0 0 0 ,]
						}
				DEF Obj18 FaceAncre {
							FaceUrl ""       
					    		FacePoints [2.35389,   -2.59709,   -0.49129,   
				3.21693,   -1.46205,   -0.21326,   
				3.11765,   -1.15956,   1.2044,   
				2.193,   -2.10729,   1.80256,   
				1.72091,   -2.99576,   0.75444,   
				] 
							CoorInd [0, 1, 2, 3, 4]
							Couleurs [0 0 1 ,1 0 0 ,1 0 1 ,0 0 1 ,1 0 1 ,]
						}
				DEF Obj19 FaceAncre {
							FaceUrl ""       
					    		FacePoints [-0.34546,   -3.48836,   -0.49018,   
				-1.71453,   -3.09035,   -0.21119,   
				-1.81414,   -2.78732,   1.20648,   
				-0.50667,   -2.99822,   1.8038,   
				0.40093,   -3.4315,   0.75507,   
				] 
							CoorInd [0, 1, 2, 3, 4]
							Couleurs [1 1 1 ,0 1 1 ,1 1 0 ,0 1 1 ,1 0 0 ,]
						}
				DEF Obj20 FaceAncre {
							FaceUrl ""       
					    		FacePoints [0.40093,   -3.4315,   0.75507,   
				-0.50667,   -2.99822,   1.8038,   
				-0.05497,   -2.14818,   2.80677,   
				1.32481,   -1.69291,   2.80612,   
				2.193,   -2.10729,   1.80256,   
				1.72091,   -2.99576,   0.75444,   
				] 
							CoorInd [0, 1, 2, 3, 4, 5]
							Couleurs [0 1 1 ,1 1 1 ,0 0 1 ,1 0 1 ,0 0 1 ,0 1 0 ,]
						}
				DEF Obj21 FaceAncre {
							FaceUrl ""       
					    		FacePoints [1.32481,   -1.69291,   2.80612,   
				1.34213,   -0.31185,   3.25682,   
				2.22673,   0.59492,   2.6842,   
				3.13408,   0.16181,   1.63544,   
				3.11765,   -1.15956,   1.2044,   
				2.193,   -2.10729,   1.80256,   
				] 
							CoorInd [0, 1, 2, 3, 4, 5]
							Couleurs [1 1 1 ,1 1 1 ,0 1 0 ,1 0 0 ,1 0 1 ,0 1 0 ,]
						}
				DEF Obj22 FaceAncre {
							FaceUrl ""       
					    		FacePoints [1.32481,   -1.69291,   2.80612,   
				1.34213,   -0.31185,   3.25682,   
				-0.027,   0.08654,   3.53589,   
				-0.89047,   -1.04841,   3.25775,   
				-0.05497,   -2.14818,   2.80677,   
				] 
							CoorInd [0, 1, 2, 3, 4]
							Couleurs [0 0 0 ,0 1 0 ,0 1 0 ,1 0 0 ,0 0 1 ,]
						}
				DEF Obj23 FaceAncre {
							FaceUrl ""       
					    		FacePoints [-3.25455,   -1.22898,   -0.68546,   
				-3.34959,   -0.93889,   0.67091,   
				-2.6136,   -1.73524,   1.63792,   
				-1.81414,   -2.78732,   1.20648,   
				-1.71453,   -3.09035,   -0.21119,   
				-2.41884,   -2.3285,   -1.13637,   
				] 
							CoorInd [0, 1, 2, 3, 4, 5]
							Couleurs [1 1 1 ,0 0 1 ,1 1 1 ,1 0 1 ,1 1 0 ,1 0 1 ,]
						}
				DEF Obj24 FaceAncre {
							FaceUrl ""       
					    		FacePoints [-3.34959,   -0.93889,   0.67091,   
				-3.33263,   0.44215,   1.12147,   
				-2.58587,   0.4993,   2.36675,   
				-2.1415,   -0.8465,   2.68609,   
				-2.6136,   -1.73524,   1.63792,   
				] 
							CoorInd [0, 1, 2, 3, 4]
							Couleurs [1 0 1 ,0 1 0 ,1 1 0 ,1 1 1 ,0 0 1 ,]
						}
				DEF Obj25 FaceAncre {
							FaceUrl ""       
					    		FacePoints [-2.6136,   -1.73524,   1.63792,   
				-2.1415,   -0.8465,   2.68609,   
				-0.89047,   -1.04841,   3.25775,   
				-0.05497,   -2.14818,   2.80677,   
				-0.50667,   -2.99822,   1.8038,   
				-1.81414,   -2.78732,   1.20648,   
				] 
							CoorInd [0, 1, 2, 3, 4, 5]
							Couleurs [1 1 0 ,1 0 0 ,0 1 1 ,1 1 0 ,1 0 1 ,0 1 0 ,]
						}
				DEF Obj26 FaceAncre {
							FaceUrl ""       
					    		FacePoints [-2.35845,   2.60849,   0.47424,   
				-1.64375,   2.66299,   1.66555,   
				-1.75993,   1.58519,   2.63288,   
				-2.58587,   0.4993,   2.36675,   
				-3.33263,   0.44215,   1.12147,   
				-3.22155,   1.47344,   0.19603,   
				] 
							CoorInd [0, 1, 2, 3, 4, 5]
							Couleurs [0 0 0 ,0 1 1 ,0 1 1 ,1 0 1 ,0 0 1 ,0 1 1 ,]
						}
				DEF Obj27 FaceAncre {
							FaceUrl ""       
					    		FacePoints [-1.64375,   2.66299,   1.66555,   
				-0.26422,   3.11848,   1.66508,   
				0.47229,   2.32191,   2.63187,   
				-0.45219,   1.37427,   3.23023,   
				-1.75993,   1.58519,   2.63288,   
				] 
							CoorInd [0, 1, 2, 3, 4]
							Couleurs [1 0 1 ,0 1 1 ,0 0 1 ,1 0 0 ,1 1 0 ,]
						}
				DEF Obj28 FaceAncre {
							FaceUrl ""       
					    		FacePoints [-1.75993,   1.58519,   2.63288,   
				-0.45219,   1.37427,   3.23023,   
				-0.027,   0.08654,   3.53589,   
				-0.89047,   -1.04841,   3.25775,   
				-2.1415,   -0.8465,   2.68609,   
				-2.58587,   0.4993,   2.36675,   
				] 
							CoorInd [0, 1, 2, 3, 4, 5]
							Couleurs [1 1 1 ,0 0 1 ,1 1 1 ,1 0 0 ,1 0 0 ,0 0 0 ,]
						}
				DEF Obj29 FaceAncre {
							FaceUrl ""       
					    		FacePoints [1.70997,   3.10198,   0.19425,   
				2.41456,   2.33964,   1.11912,   
				1.78217,   1.94095,   2.36496,   
				0.47229,   2.32191,   2.63187,   
				-0.26422,   3.11848,   1.66508,   
				0.34088,   3.49999,   0.47317,   
				] 
							CoorInd [0, 1, 2, 3, 4, 5]
							Couleurs [0 1 0 ,1 0 1 ,0 0 0 ,1 0 0 ,1 0 0 ,1 1 1 ,]
						}
				DEF Obj30 FaceAncre {
							FaceUrl ""       
					    		FacePoints [2.41456,   2.33964,   1.11912,   
				3.25025,   1.24013,   0.66829,   
				3.13408,   0.16181,   1.63544,   
				2.22673,   0.59492,   2.6842,   
				1.78217,   1.94095,   2.36496,   
				] 
							CoorInd [0, 1, 2, 3, 4]
							Couleurs [0 1 1 ,0 0 1 ,1 1 1 ,1 0 0 ,0 1 1 ,]
						}
				DEF Obj31 FaceAncre {
							FaceUrl ""       
					    		FacePoints [1.78217,   1.94095,   2.36496,   
				2.22673,   0.59492,   2.6842,   
				1.34213,   -0.31185,   3.25682,   
				-0.027,   0.08654,   3.53589,   
				-0.45219,   1.37427,   3.23023,   
				0.47229,   2.32191,   2.63187,   
				] 
							CoorInd [0, 1, 2, 3, 4, 5]
							Couleurs [0 1 1 ,0 0 0 ,1 0 1 ,0 0 0 ,0 1 0 ,0 0 1 ,]
						}
				DEF Obj32 FaceAncre {
							FaceUrl ""       
					    		FacePoints [3.32838,   -0.43039,   -1.13854,   
				3.21693,   -1.46205,   -0.21326,   
				3.11765,   -1.15956,   1.2044,   
				3.13408,   0.16181,   1.63544,   
				3.25025,   1.24013,   0.66829,   
				3.34529,   0.95071,   -0.68805,   
				] 
							CoorInd [0, 1, 2, 3, 4, 5]
							Couleurs [1 1 1 ,1 1 1 ,0 1 0 ,1 1 0 ,1 0 0 ,1 0 1 ,]
						}
			
	]
}


DEF temps TimeSensor {
	cycleInterval 10
	loop TRUE
}

DEF chemin OrientationInterpolator {
	key		[0.0,0.33,0.66,1.0]
	keyValue	[ 0 1 0 0,0 1 0 2.09,0 1 0 4.19,0 1 0 0 ]
}

DEF ChangeIma TimeSensor {
	cycleInterval 1
	loop TRUE
}

DEF ActiveSelect Script {
	directOutput TRUE
	field		SFInt32		choix	0
		
			field		SFNode		F1 USE Obj1	
			field		SFNode		F2 USE Obj2	
			field		SFNode		F3 USE Obj3	
			field		SFNode		F4 USE Obj4	
			field		SFNode		F5 USE Obj5	
			field		SFNode		F6 USE Obj6	
			field		SFNode		F7 USE Obj7	
			field		SFNode		F8 USE Obj8	
			field		SFNode		F9 USE Obj9	
			field		SFNode		F10 USE Obj10	
			field		SFNode		F11 USE Obj11	
			field		SFNode		F12 USE Obj12	
			field		SFNode		F13 USE Obj13	
			field		SFNode		F14 USE Obj14	
			field		SFNode		F15 USE Obj15	
			field		SFNode		F16 USE Obj16	
			field		SFNode		F17 USE Obj17	
			field		SFNode		F18 USE Obj18	
			field		SFNode		F19 USE Obj19	
			field		SFNode		F20 USE Obj20	
			field		SFNode		F21 USE Obj21	
			field		SFNode		F22 USE Obj22	
			field		SFNode		F23 USE Obj23	
			field		SFNode		F24 USE Obj24	
			field		SFNode		F25 USE Obj25	
			field		SFNode		F26 USE Obj26	
			field		SFNode		F27 USE Obj27	
			field		SFNode		F28 USE Obj28	
			field		SFNode		F29 USE Obj29	
			field		SFNode		F30 USE Obj30	
			field		SFNode		F31 USE Obj31	
			field		SFNode		F32 USE Obj32
	eventIn		SFTime		activeimag
	eventIn		SFTime		activecolo
	eventOut	SFBool		active
	url [
		"javascript:
		function activeimag(){
			choix=Math.floor(32*Math.random());
				if (choix==1 ) {
				F1.actifI = TRUE;
				F1.actifC = TRUE;
				F1.Transp = 0;
				F1.ColorTransp = 1;
			}
					if (choix==2 ) {
				F2.actifI = TRUE;
				F2.actifC = TRUE;
				F2.Transp = 0;
				F2.ColorTransp = 1;
			}
					if (choix==3 ) {
				F3.actifI = TRUE;
				F3.actifC = TRUE;
				F3.Transp = 0;
				F3.ColorTransp = 1;
			}
					if (choix==4 ) {
				F4.actifI = TRUE;
				F4.actifC = TRUE;
				F4.Transp = 0;
				F4.ColorTransp = 1;
			}
					if (choix==5 ) {
				F5.actifI = TRUE;
				F5.actifC = TRUE;
				F5.Transp = 0;
				F5.ColorTransp = 1;
			}
					if (choix==6 ) {
				F6.actifI = TRUE;
				F6.actifC = TRUE;
				F6.Transp = 0;
				F6.ColorTransp = 1;
			}
					if (choix==7 ) {
				F7.actifI = TRUE;
				F7.actifC = TRUE;
				F7.Transp = 0;
				F7.ColorTransp = 1;
			}
					if (choix==8 ) {
				F8.actifI = TRUE;
				F8.actifC = TRUE;
				F8.Transp = 0;
				F8.ColorTransp = 1;
			}
					if (choix==9 ) {
				F9.actifI = TRUE;
				F9.actifC = TRUE;
				F9.Transp = 0;
				F9.ColorTransp = 1;
			}
					if (choix==10 ) {
				F10.actifI = TRUE;
				F10.actifC = TRUE;
				F10.Transp = 0;
				F10.ColorTransp = 1;
			}
					if (choix==11 ) {
				F11.actifI = TRUE;
				F11.actifC = TRUE;
				F11.Transp = 0;
				F11.ColorTransp = 1;
			}
					if (choix==12 ) {
				F12.actifI = TRUE;
				F12.actifC = TRUE;
				F12.Transp = 0;
				F12.ColorTransp = 1;
			}
					if (choix==13 ) {
				F13.actifI = TRUE;
				F13.actifC = TRUE;
				F13.Transp = 0;
				F13.ColorTransp = 1;
			}
					if (choix==14 ) {
				F14.actifI = TRUE;
				F14.actifC = TRUE;
				F14.Transp = 0;
				F14.ColorTransp = 1;
			}
					if (choix==15 ) {
				F15.actifI = TRUE;
				F15.actifC = TRUE;
				F15.Transp = 0;
				F15.ColorTransp = 1;
			}
					if (choix==16 ) {
				F16.actifI = TRUE;
				F16.actifC = TRUE;
				F16.Transp = 0;
				F16.ColorTransp = 1;
			}
					if (choix==17 ) {
				F17.actifI = TRUE;
				F17.actifC = TRUE;
				F17.Transp = 0;
				F17.ColorTransp = 1;
			}
					if (choix==18 ) {
				F18.actifI = TRUE;
				F18.actifC = TRUE;
				F18.Transp = 0;
				F18.ColorTransp = 1;
			}
					if (choix==19 ) {
				F19.actifI = TRUE;
				F19.actifC = TRUE;
				F19.Transp = 0;
				F19.ColorTransp = 1;
			}
					if (choix==20 ) {
				F20.actifI = TRUE;
				F20.actifC = TRUE;
				F20.Transp = 0;
				F20.ColorTransp = 1;
			}
					if (choix==21 ) {
				F21.actifI = TRUE;
				F21.actifC = TRUE;
				F21.Transp = 0;
				F21.ColorTransp = 1;
			}
					if (choix==22 ) {
				F22.actifI = TRUE;
				F22.actifC = TRUE;
				F22.Transp = 0;
				F22.ColorTransp = 1;
			}
					if (choix==23 ) {
				F23.actifI = TRUE;
				F23.actifC = TRUE;
				F23.Transp = 0;
				F23.ColorTransp = 1;
			}
					if (choix==24 ) {
				F24.actifI = TRUE;
				F24.actifC = TRUE;
				F24.Transp = 0;
				F24.ColorTransp = 1;
			}
					if (choix==25 ) {
				F25.actifI = TRUE;
				F25.actifC = TRUE;
				F25.Transp = 0;
				F25.ColorTransp = 1;
			}
					if (choix==26 ) {
				F26.actifI = TRUE;
				F26.actifC = TRUE;
				F26.Transp = 0;
				F26.ColorTransp = 1;
			}
					if (choix==27 ) {
				F27.actifI = TRUE;
				F27.actifC = TRUE;
				F27.Transp = 0;
				F27.ColorTransp = 1;
			}
					if (choix==28 ) {
				F28.actifI = TRUE;
				F28.actifC = TRUE;
				F28.Transp = 0;
				F28.ColorTransp = 1;
			}
					if (choix==29 ) {
				F29.actifI = TRUE;
				F29.actifC = TRUE;
				F29.Transp = 0;
				F29.ColorTransp = 1;
			}
					if (choix==30 ) {
				F30.actifI = TRUE;
				F30.actifC = TRUE;
				F30.Transp = 0;
				F30.ColorTransp = 1;
			}
					if (choix==31 ) {
				F31.actifI = TRUE;
				F31.actifC = TRUE;
				F31.Transp = 0;
				F31.ColorTransp = 1;
			}
					if (choix==32 ) {
				F32.actifI = TRUE;
				F32.actifC = TRUE;
				F32.Transp = 0;
				F32.ColorTransp = 1;
			}
		
		}
	"]
}

#rotation continue
#ROUTE temps.fraction_changed	TO chemin.set_fraction 
#ROUTE chemin.value_changed	TO Fullerene.set_rotation
#changement d'image
ROUTE ChangeIma.cycleTime	TO ActiveSelect.activeimag
			
