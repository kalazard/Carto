<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Site\CartoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Site\CartoBundle\Entity\Itineraire;
use Site\CartoBundle\Entity\Trace;
use Site\CartoBundle\Entity\Segment;
use Site\CartoBundle\Entity\Point;
use Site\CartoBundle\Entity\Coordonnees;
use CrEOF\Spatial\PHP\Types\Geography\Point as MySQLPoint;
use CrEOF\Spatial\PHP\Types\Geography\Polygon;
use CrEOF\Spatial\PHP\Types\Geography\LineString;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ItineraireController extends Controller {

    /**
     * Fonction de création du serveur SOAP
     *
     *
     * @return Response
     *
     */
    public function indexAction() {
        $server = new \SoapServer(null, array('uri' => 'http://localhost/carto/web/app_dev.php/itineraire'));
        $server->setObject($this->get('itineraire_service'));

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        ob_start();
        $server->handle();
        $response->setContent(ob_get_clean());

        return $response;
    }

    /**
     * Fonction de récupération des difficultés
     *
     * Cette méthode est appelée en ajax
     *
     * @return string
     *
     * JSON de la liste des difficultés
     *
     */
    public function getDifficultesAction(Request $request) {
        if ($request->isXMLHttpRequest()) {
            $manager = $this->getDoctrine()->getManager();
            $repository = $manager->getRepository("SiteCartoBundle:Difficulteparcours");
            $diffs = $repository->findAll();
            $response = new Response(json_encode($diffs));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        return new Response('This is not ajax!', 400);
    }

    /**
     * Fonction de récupération des status
     *
     * Cette méthode est appelée en ajax
     *
     * @return string
     *
     * JSON de la liste des status
     *
     */
    public function getStatusAction(Request $request) {
        if ($request->isXMLHttpRequest()) {
            $manager = $this->getDoctrine()->getManager();
            $repository = $manager->getRepository("SiteCartoBundle:Status");
            $stats = $repository->findAll();
            $response = new Response(json_encode($stats));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        return new Response('This is not ajax!', 400);
    }

    /**
     * Fonction de récupération des types de chemin
     *
     * Cette méthode est appelée en ajax
     *
     * @return string
     *
     * JSON de la liste des types de chemin
     *
     */
    public function getTypecheminAction(Request $request) {
        if ($request->isXMLHttpRequest()) {
            $manager = $this->getDoctrine()->getManager();
            $repository = $manager->getRepository("SiteCartoBundle:Typechemin");
            $typechemin = $repository->findAll();
            $response = new Response(json_encode($typechemin));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }

        return new Response('This is not ajax!', 400);
    }

    /**
     * Fonction de sauvegarde des itinéraires
     *
     * Cette méthode est appelée en ajax
     *
     * @return string
     *
     * JSON contenant l'itinéraire sauvegardé
     *
     */
    public function saveAction(Request $request) {
        if ($request->isXMLHttpRequest()) {
            $manager = $this->getDoctrine()->getManager();
            $repositoryDiff = $manager->getRepository("SiteCartoBundle:Difficulteparcours");
            $repositoryUser = $manager->getRepository("SiteCartoBundle:Utilisateur");
            $repositoryStatus = $manager->getRepository("SiteCartoBundle:Status");
            $repositoryTypechemin = $manager->getRepository("SiteCartoBundle:Typechemin");

            $trace = new Trace();
            $filename = uniqid('trace_', true) . '.gpx';
            $trace->setPath('/Traces/'.$filename);
            $diff = $repositoryDiff->find($request->request->get("difficulte", ""));
            $user = $repositoryUser->find($request->request->get("auteur", ""));
            $status = $repositoryStatus->find($request->request->get("status", ""));
            $typechemin = $repositoryTypechemin->find($request->request->get("typechemin", ""));

            $pointArray = json_decode($request->request->get("points", ""), true);
            $lsArray = [];
            $elevationString = "";
            $i = 0;
            foreach ($pointArray as $pt) {
                $newPoint = new MySQLPoint(floatval($pt["lng"]), floatval($pt["lat"]));
                array_push($lsArray, $newPoint);
                $elevationString = $elevationString . $pt["elevation"];
                if (++$i != count($pointArray)) {
                    $elevationString = $elevationString . ";";
                }
            }
            $ls = new LineString($lsArray);

            $route = new Itineraire();
            $route->setDatecreation(new \DateTime('now'));
            $route->setLongueur($request->request->get("longueur", ""));
            $route->setDeniveleplus($request->request->get("denivelep", ""));
            $route->setDenivelemoins($request->request->get("denivelen", ""));
            $route->setTrace($trace);
            $route->setNom($request->request->get("nom", ""));
            $route->setNumero($request->request->get("numero", ""));
            $route->setTypechemin($typechemin);
            $route->setDescription($request->request->get("description", ""));
            $route->setDifficulte($diff);
            $route->setAuteur($user);
            $route->setStatus($status);
            $route->setPublic($request->request->get("public", ""));
            $route->setSegment($ls);
            $route->setElevation($elevationString);

            $json_obj = json_decode($request->request->get("points", ""), true);


            $manager->persist($route);
            $manager->persist($trace);
            $manager->flush();
            $this->saveGpx($route->getId(), $filename);
            $response = new Response(json_encode(array("result" => "success", "code" => 200, "jsonObject" => json_encode($route))));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        return new Response('This is not ajax!', 400);
    }
	
	 /**
     * Fonction du téléchargement du fichier GPX correposndant à l'itinéraire affiché.
     *
     * Cette méthode est appelée depuis la vue fiche_itinéraire
     *
     * @return string 
     *
     * Nom du fichier sauvegardé
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *     "filename": nom du fichier GPX
     * }
     * </code>
     * 
     * Example en cas d'erreur du serveur :
     * 
     * <code>
     * {
     *     "success": false,
     *     "serverError": true,
     *     "message": "Message"
     * }
     * </code>
     * 
     * 
     */

    public function downloadGPXAction($id) {


        $filename = uniqid('trace_', true) . '.gpx';
        $itineraire = $this->saveGpx($id, $filename);
        $chemin = "../.."; // emplacement ../../Traces/filename
        $fichier = $itineraire->getTrace()->getPath();
        $response = new Response();
        $response->setContent(file_get_contents($chemin . $fichier));
        $response->headers->set('Content-Type', 'application/force-download'); // modification du content-type pour forcer le téléchargement (sinon le navigateur internet essaie d'afficher le document)
        $response->headers->set('Content-disposition', 'filename=' . $itineraire->getNom().'.gpx');

        return $response;
    }
	
	/**
     * Fonction de sauvegarde d'un fichier GPX 
     *
     * Cette méthode est appelée depuis la méthode downloadGPXAction
     *
     * @return string 
     *
     * Url du GPX voulu
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *     "filename": nom du fichier GPX
     * }
     * </code>
     * 
     * Example en cas d'erreur du serveur :
     * 
     * <code>
     * {
     *     "success": false,
     *     "serverError": true,
     *     "message": "Message"
     * }
     * </code>
     * 
     * 
     */

    public function saveGpx($id_itineraire, $filename) {
        $manager = $this->getDoctrine()->getManager();
        $itineraire = $manager->getRepository("SiteCartoBundle:Itineraire")->find($id_itineraire);
        $linestring = $itineraire->getSegment();
        $elevationString = $itineraire->getElevation();
        //Contient un tableau de points séparés par des espaces
        $points = explode(",", $linestring);
        //Tableau qui contient les élévations de chaque points
        $elevation = explode(";", $elevationString);
        //On céer une structure XML jésus            
        $dom = new \DOMDocument();
        //$fp = fopen(, 'w');
        $racine = $dom->createElement("gpx");
        $track = $dom->createElement('trk');
        $trackseg = $dom->createElement('trkseg');
        //Il va falloir générer les points un par un à partir de la linestring
        for ($i = 0; $i < count($points); $i++) {
            $longitude = explode(" ", $points[$i])[0];
            $latitude = explode(" ", $points[$i])[1];
            $current_elevation = $elevation[$i];

            //On créé l'élement TRACKPOINT
            $trackpoint = $dom->createElement("trkpt");
            $trackpoint->setAttribute("lat", $latitude);
            $trackpoint->setAttribute("lon", $longitude);

            //On ajoute l'élément élévation
            $elevationgpx = $dom->createElement("ele");
            //Creation du texte contenu par la balise elevation
            $elevationTextNode = $dom->createTextNode($current_elevation);
            $elevationgpx->appendChild($elevationTextNode);
            $trackpoint->appendChild($elevationgpx);
            $trackseg->appendChild($trackpoint);
        }
        $track->appendChild($trackseg);
        $racine->appendChild($track);
        $dom->appendChild($racine);
        $dom->save('../../Traces/' . $filename);
        $itineraire->getTrace()->setPath('/Traces/' . $filename);

        $manager->flush();

        //On return l'url l'itineraire
        return $itineraire;
    }

    /**
     * Fonction de chargement d'un itinéraire
     *
     * Cette méthode est appelée en ajax
     *
     * @return View
     *
     *
     */
    public function loadAction($id) {
        $repository = $this->getDoctrine()->getManager()->getRepository('SiteCartoBundle:Itineraire');
        $iti = $repository->find($id);
        $content = $this->get("templating")->render("SiteCartoBundle:Map:loadFrame.html.twig", array("itineraire" => $iti, "jsonObject" => json_encode($iti)));
        return new Response($content);
    }

     /**
     * Fonction de chargement d'un itinéraire
     *
     * Cette méthode est appelée en ajax
     *
     * @return View
     *
     *
     */
    public function loadFrameAction($id) {
        $repository = $this->getDoctrine()->getManager()->getRepository('SiteCartoBundle:Itineraire');
        $iti = $repository->find($id);
        $content = $this->get("templating")->render("SiteCartoBundle:Map:loadFrame.html.twig", array("itineraire" => $iti, "jsonObject" => json_encode($iti)));
        return new Response($content);
    }

    public function rechercheAction(Request $request) {
        //soit on affiche la page en chargeant toutes les données, soit on charge les données selon les paramètres 
        //on récupère la liste des paramètres choisi et on charge tout les itinéraires associés.
        $result = array();
        $search = array();
        $search["nom"] = $request->request->get("nom");
        $search["typechemin"] = $request->request->get("typechemin");
        $search["longueur"] = $request->request->get("longueur");
        $search["datecrea"] = $request->request->get("datecrea");
        $search["difficulte"] = $request->request->get("difficulte");
        $search["status"] = $request->request->get("status");


        return $this->render('SiteCartoBundle:Itineraire:index.html.twig');
    }

    /**
     * Fonction de sauvegarde d'un segment
     *
     * Cette méthode est appelée en ajax
     *
     * @return string
     *
     * JSON contenant l'id du segment crée
     *
     */

    public function saveSegmentAction(Request $request) {
        if ($request->isXMLHttpRequest()) {
            $manager = $this->getDoctrine()->getManager();

            $trace = new Trace();
            $filename = uniqid('trace_', true) . '.csv';
            $trace->setPath($filename);

            $segment = new Segment();

            $pointArray = json_decode($request->request->get("points", ""), true);

            $lsArray = [];
            $elevationString = "";
            $i = 0;
            foreach ($pointArray as $pt) {
                $newPoint = new MySQLPoint(floatval($pt["lng"]), floatval($pt["lat"]));
                array_push($lsArray, $newPoint);
                $elevationString = $elevationString . $pt["elevation"];
                if (++$i != count($pointArray)) {
                    $elevationString = $elevationString . ";";
                }
            }
            $ls = new LineString($lsArray);

            $pog1 = new Point();
            $coords1 = new Coordonnees();
            $coords1->setLatitude($pointArray[0]["lat"]);
            $coords1->setLongitude($pointArray[0]["lng"]);
            $coords1->setAltitude($pointArray[0]["elevation"]);
            $pog1->setCoords($coords1);
            $pog1->setOrdre(1);
            $manager->persist($coords1);
            $manager->persist($pog1);

            $pog2 = new Point();
            $coords2 = new Coordonnees();
            $coords2->setLatitude($pointArray[count($pointArray) - 1]["lat"]);
            $coords2->setLongitude($pointArray[count($pointArray) - 1]["lng"]);
            $coords2->setAltitude($pointArray[count($pointArray) - 1]["elevation"]);
            $pog2->setCoords($coords2);
            $pog2->setOrdre(2);
            $manager->persist($coords2);
            $manager->persist($pog2);

            $segment->setTrace($ls);
            $segment->setElevation($elevationString);
            $segment->setSens(0);
            $segment->setPog1($pog1);
            $segment->setPog2($pog2);

            $json_obj = json_decode($request->request->get("points", ""), true);
            $fp = fopen('../../Traces/' . $filename, 'w');
            $firstLineKeys = false;
            foreach ($json_obj as $line) {
                if (empty($firstLineKeys)) {
                    $firstLineKeys = array_keys($line);
                    fputcsv($fp, $firstLineKeys);
                    $firstLineKeys = array_flip($firstLineKeys);
                }
                fputcsv($fp, array_merge($firstLineKeys, $line));
            }
            fclose($fp);

            $manager->persist($trace);
            $manager->persist($segment);
            $manager->flush();
            $response = new Response(json_encode(array("result" => "success", "code" => 200,"id" => $segment->getId())));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        return new Response('This is not ajax!', 400);
    }

    /**
     * Fonction de chargement des segments dans la bounding box de la carte
     *
     * Cette méthode est appelée en ajax
     *
     * @return string
     *
     * JSON de la liste des segments
     *
     */
	public function loadSegmentAction(Request $request)
	{
		if($request->isXMLHttpRequest()) 
        {
            $manager = $this->getDoctrine()->getManager();
            $repository = $manager->getRepository('SiteCartoBundle:Segment');
            $northeast = json_decode($request->request->get("northeast",""),true);
            $southwest = json_decode($request->request->get("southwest",""),true);
            $northwest = json_decode($request->request->get("northwest",""),true);
            $southeast = json_decode($request->request->get("southeast",""),true);

            $bounds = new Polygon(array(new LineString(array(
                                        new MySQLPoint($northeast["lng"], $northeast["lat"]),
                                        new MySQLPoint($southeast["lng"], $southeast["lat"]),
                                        new MySQLPoint($southwest["lng"], $southwest["lat"]),
                                        new MySQLPoint($northwest["lng"], $northwest["lat"]),
                                        new MySQLPoint($northeast["lng"], $northeast["lat"])
                                    ))));

            $res = $repository->findAll();


			//requete pour trouver les résultats
            $req = "SELECT MBRContains(GeomFromText(:bounds), GeomFromText(:pt)), MBRContains(GeomFromText(:bounds), GeomFromText(:pt2)) FROM SiteCartoBundle:Segment s WHERE s.id = :segid";
            $query = $manager->createQuery($req);
            $query->setParameter('bounds', $bounds,'point');
            foreach($res as $key => $seg)
            {
                $query->setParameter('pt', new MySQLPoint($seg->getPog1()->getCoords()->getLongitude(),$seg->getPog1()->getCoords()->getLatitude()), 'point');
                $query->setParameter('pt2', new MySQLPoint($seg->getPog2()->getCoords()->getLongitude(),$seg->getPog2()->getCoords()->getLatitude()), 'point');
                $query->setParameter('segid', $seg->getId());
                $val = $query->getResult();
                if($val[0][1] != '1' && $val[0][2] != '1')
                {
                    unset($res[$key]);
                }

            }

			//on renvoit le reste sous la forme d'une liste json -> traitement sur le map.js
            return new Response(json_encode(array("searchResults" => $res)));
		}
		return new Response('This is not ajax!', 400);		
	}


    public function getSegmentByIdAction(Request $request) {
        //on récupère l'id de l'itinéraire à charger 
        //on charge ses données
        //on renvoit le tout

        $id = $request->get('id');

        //on charge les infos de l'utilisateur courant.
        $manager = $this->getDoctrine()->getManager();
        //on recherche l'itinéraire associé à son id
        $data = $manager->getRepository('SiteCartoBundle:Itineraire')->findOneBy(array('id' => $id));

        //lien itinéraire (nom et id), type de chemin, difficulté, date de création, longueur du parcours, statut, visiblité, supprimer
        $result['id'] = $data->getId();
        $result['auteur_id'] = $data->getAuteur()->getId();
        $result['nom'] = $data->getNom();
        $result['numero'] = $data->getNumero();
        $result['type'] = $data->getTypeChemin()->getLabel();
        $result['type_id'] = $data->getTypeChemin()->getId();
        $result['difficulte'] = $data->getDifficulte()->getLabel();
        $result['difficulte_id'] = $data->getDifficulte()->getId();
        $result['date'] = $data->getDateCreation();
        $result['longueur'] = $data->getLongueur();
        $result['deniv_plus'] = $data->getDenivelePlus();
        $result['deniv_moins'] = $data->getDeniveleMoins();
        $result['status'] = $data->getStatus()->getLabel();
        $result['status_id'] = $data->getStatus()->getId();
        $result['description'] = $data->getDescription();
        $result['auteur'] = $data->getAuteur()->getEmail();

        $public = "privé";
        if ($data->getPublic() == 1) {
            $public = "publique";
        }

        $result['public'] = $public;

        $content = $this->get("templating")->render("SiteCartoBundle:Itineraire:fiche_itineraire.html.twig", $result);
        return new Response($content);
    }

    /**
     * Fonction de récupération des informations de formulaire pour les itinéraires
     *
     * Cette méthode est appelée en ajax
     *
     * @return Response
     *
     */
    public function getFormDataAction(Request $request) {
        //si c'est un appel AJAX
        if ($request->isXMLHttpRequest()) {
            //Chargement de la liste des difficultés dans le select
            $manager = $this->getDoctrine()->getManager();
            $repository = $manager->getRepository("SiteCartoBundle:Difficulteparcours");
            $diffs = $repository->findAll();

            $result = array();
            $result['difficulte'] = array();
            $compteur = 0;
            foreach ($diffs as $var) {
                $result['difficulte'][$compteur]['niveau'] = $var->getNiveau();
                $result['difficulte'][$compteur]['texte'] = $var->getLabel();

                $compteur++;
            }

            //Chargement de la liste des status dans le select
            $repository = $manager->getRepository("SiteCartoBundle:Status");
            $diffs = $repository->findAll();

            $result['status'] = array();
            $compteur = 0;
            foreach ($diffs as $var) {
                $result['status'][$compteur]['texte'] = $var->getLabel();
                $result['status'][$compteur]['id'] = $var->getId();

                $compteur++;
            }

            //Chargement de la liste des types de chemin dans le select
            $repository = $manager->getRepository("SiteCartoBundle:Typechemin");
            $diffs = $repository->findAll();

            $result['type'] = array();
            $compteur = 0;
            foreach ($diffs as $var) {
                $result['type'][$compteur]['texte'] = $var->getLabel();
                $result['type'][$compteur]['id'] = $var->getId();

                $compteur++;
            }

            $data = json_encode($result);

            return new Response($data);
        }
        return new Response('This is not ajax!', 400);
    }

    /**
     * Fonction de mise à jour d'un itinéraire
     *
     * Cette méthode est appelée en ajax
     *
     *
     * @return Response
     *
     *
     */
    public function updateItiAction(Request $request) {
        if ($request->isXMLHttpRequest()) {
            $params = array();
            $params["nom"] = $request->request->get("nom");
            $params["typechemin"] = $request->request->get("typechemin");
            $params["difficulte"] = $request->request->get("difficulte");
            $params["description"] = $request->request->get("description");
            $params["numero"] = $request->request->get("numero");
            $params["auteur"] = $request->request->get("auteur");
            $params["status"] = $request->request->get("status");
            $params["public"] = $request->request->get("public");
            $params["id"] = $request->request->get("id");

            $manager = $this->getDoctrine()->getManager();

            $repositoryDiff = $manager->getRepository("SiteCartoBundle:Difficulteparcours");
            $repositoryStat = $manager->getRepository("SiteCartoBundle:Status");
            $repositoryType = $manager->getRepository("SiteCartoBundle:Typechemin");
            $repositoryIti = $manager->getRepository("SiteCartoBundle:Itineraire");

            $diff = $repositoryDiff->find($params["difficulte"]);
            $stat = $repositoryStat->find($params["status"]);
            $type = $repositoryType->find($params["typechemin"]);

            $route = $repositoryIti->findBy(array('id' => $params["id"]));
            $route[0]->setNom($params["nom"]);
            $route[0]->setNumero($params["numero"]);
            $route[0]->setTypechemin($type);
            $route[0]->setDescription($params["description"]);
            $route[0]->setDifficulte($diff);
            $route[0]->setStatus($stat);
            $route[0]->setPublic($params["public"]);

            $manager->persist($route[0]);
            $manager->flush();

            return new Response("success");
        }
        return new Response('This is not ajax!', 400);
    }

    /**
     * Fonction de recherche d'un itinéraire
     *
     *
     *
     * @return View
     */
    public function searchAction(Request $request) {
        $clientSOAP = new \SoapClient(null, array(
            'uri' => "http://localhost/Carto/web/app_dev.php/itineraire",
            'location' => "http://localhost/Carto/web/app_dev.php/itineraire",
            'trace' => true,
            'exceptions' => true
        ));
        
        $user = $this->getUser();
        $id_courant = $user->getId();

        $manager = $this->getDoctrine()->getManager();

        $user = $this->getUser();
        //on récupère les infos de l'utilisateur courant (et seulement courant)
        $id_courant = $user->getId();

        //Chargement de la liste des difficultés dans le select
        $responseDiff = $clientSOAP->__call('difficultelist', array());

        //Chargement de la liste des status dans le select
        $responseStat = $clientSOAP->__call('statuslist', array());

        //Chargement de la liste des types de chemin dans le select
        $responseType = $clientSOAP->__call('typecheminlist', array());

        if ($request->request->get("valid") == "ok") {
            //Appel du service de recherche
            $search = array();
            $search["id"] = $request->request->get("id");
            $search["nom"] = $request->request->get("nom");
            $search["typechemin"] = $request->request->get("typechemin");
            $search["longueur"] = $request->request->get("longueur");
            $search["datecrea"] = $request->request->get("datecrea");
            $search["difficulte"] = $request->request->get("difficulte");
            $search["status"] = $request->request->get("status");

            $response = $clientSOAP->__call('search', $search);

            $data = $manager->getRepository('SiteCartoBundle:Utilisateur')->findOneBy(array('id'=>$id_courant));
            $result = array();
            $result = $data->getItineraireid();

            $res_search = json_decode($response);
            $resDiff = json_decode($responseDiff);
            $resStat = json_decode($responseStat);
            $resType = json_decode($responseType);
            $content = $this->get("templating")->render("SiteCartoBundle:Itineraire:SearchItineraire.html.twig", array("resultats" => $res_search, "diffs" => $resDiff, "stats" => $resStat, "typechemin" => $resType, "list" => array(), "favoris" => $result));
        } else {
            // Recupère la liste complète
            $response = $clientSOAP->__call('itilist', array());

            $data = $manager->getRepository('SiteCartoBundle:Utilisateur')->findOneBy(array('id'=>$id_courant));
            $result = array();
            $result = $data->getItineraireid();

            $res_list = json_decode($response);
            $resDiff = json_decode($responseDiff);
            $resStat = json_decode($responseStat);
            $resType = json_decode($responseType);            
            $itineraireService = $this->container->get('itineraire_service');
            $itiService = $itineraireService->getNotes($res_list, $id_courant);
            $notes = json_decode($itiService, true);
            $itiMoyenne = array();
            foreach($notes['allNotes'] as $calcMoy)
            {
                if(sizeof($calcMoy) > 0)
                {
                    $itiMoyenne[] = array_sum($calcMoy) / count($calcMoy);
                }
                else
                {
                    $itiMoyenne[] = -1;
                }

            }
            $content = $this->get("templating")->render("SiteCartoBundle:Itineraire:SearchItineraire.html.twig",array("resultats" => array(),"diffs" => $resDiff,"stats" => $resStat,"typechemin" => $resType,"list" => $res_list, "itiMoyenne" => $itiMoyenne, "favoris" => $result));
        }

        return new Response($content);
    }

    /**
     * Fonction de récupération d'un itinéraire par l'id
     *
     *
     *
     * @return Response
     *
     */
    public function getByIdAction($id) {
        //Appel du service de recherche
        $user = $this->getUser();
        $id_courant = $user->getId();
        $search = array();
        $search["id"] = $id;

        $clientSOAP = new \SoapClient(null, array(
            'uri' => "http://localhost/Carto/web/app_dev.php/itineraire",
            'location' => "http://localhost/Carto/web/app_dev.php/itineraire",
            'trace' => true,
            'exceptions' => true
        ));

        $response = $clientSOAP->__call('getById', $search);

        $res = json_decode($response);
        
        $res->list[] = $res->searchResults;
        $itineraireService = $this->container->get('itineraire_service');
        $itiService = $itineraireService->getNotes($res, $id_courant);
        $notes = json_decode($itiService, true);
        $userNotes = $notes['userNotes'];
        $itiMoyenne = array();
        foreach($notes['allNotes'] as $calcMoy)
        {
            if(sizeof($calcMoy) > 0)
            {
                $itiMoyenne[] = array_sum($calcMoy) / count($calcMoy);
            }
            else
            {
                $itiMoyenne[] = -1;
            }
        }

        $manager=$this->getDoctrine()->getManager();

        $data = $manager->getRepository('SiteCartoBundle:Utilisateur')->findOneBy(array('id'=>$id_courant));
        $result['favoris'] = $data->getItineraireid();

        $content = $this->get("templating")->render("SiteCartoBundle:Itineraire:fiche_itineraire.html.twig", array("resultats" => $res,
                                                                                                                    "jsonObject" => $response,
                                                                                                                    "userNotes" => $userNotes,
                                                                                                                    "idUser" => $id_courant,
                                                                                                                    "itiMoyenne" => $itiMoyenne,
                                                                                                                    "result" => $result));
        return new Response($content);
    }

    /**
     * Fonction de suppression d'un itinéraire
     *
     * Cette méthode est appelée en ajax et requiert les paramètres suivants :
     *
     *
     * @return Response
     *
     *
     */
    public function deleteAction(Request $request) {
        if ($request->isXMLHttpRequest()) {
            //Appel du service de sauvegarde
            $params = array();
            $params["id"] = $request->request->get("id");

            $clientSOAP = new \SoapClient(null, array(
                'uri' => "http://localhost/Carto/web/app_dev.php/itineraire",
                'location' => "http://localhost/Carto/web/app_dev.php/itineraire",
                'trace' => true,
                'exceptions' => true
            ));

            $response = $clientSOAP->__call('delete', $params);
            $res = json_decode($response);
            return new Response(json_encode(array("result" => "success", "code" => 200)));
        }
        return new Response('This is not ajax!', 400);
    }
    
    public function noteItineraireFormAction(Request $request)
    {        
        if($request->isXmlHttpRequest())
        {
            $itineraireService = $this->container->get('itineraire_service');
            $listeNote = json_decode($itineraireService->getAllNotes());
            $idUser = $this->getUser()->getId();
            $idItineraire = $request->request->get('idIti', '1');
            
            //$search = array();		
            //$search["id"] = $idItineraire;   
            
            $itiService = $itineraireService->getById($idItineraire);
            $listeIti = json_decode($itiService); 
            
            $listeIti->list[] = $listeIti->searchResults;            
            $n = json_decode($itineraireService->getNotes($listeIti, $idUser));
            $maNote = $n->userNotes[0];
            
            $formulaire = $this->get("templating")->render("SiteCartoBundle:Itineraire:NoteItineraire.html.twig", array(    "maNote" => $maNote,
                                                                                                                            "listeNote" => $listeNote,
                                                                                                                            "idIti" => $idItineraire
                                                          ));

            return new Response($formulaire);
        }
        else
        {
            throw new NotFoundHttpException('Impossible de trouver la page demandée');
        }
    }
    
    public function noteItineraireAction(Request $request)
    {
        $idUser = $this->getUser()->getId();
        $idItineraire = $request->request->get('idIti', '');
        $note = $request->request->get('note', '');

        $itineraireService = $this->container->get('itineraire_service');
        $noterIti = $itineraireService->noterItineraire($idUser, $idItineraire, $note);
        $reponse = json_decode($noterIti); 
        return $this->redirect($this->generateUrl('site_carto_getByIditineraire', array("id" => $idItineraire)));
    } 

    public function deletefavoriAction(Request $request) {
        if ($request->isXMLHttpRequest()) {
            //Appel du service de sauvegarde
            $params = array();
            $params["iditi"] = $request->request->get("iditi");
            $params["iduser"] = $request->request->get("iduser");

            $clientSOAP = new \SoapClient(null, array(
                'uri' => "http://localhost/Carto/web/app_dev.php/itineraire",
                'location' => "http://localhost/Carto/web/app_dev.php/itineraire",
                'trace' => true,
                'exceptions' => true
            ));

            $response = $clientSOAP->__call('deletefavori', $params);
            $res = json_decode($response);
            return new Response(json_encode(array("result" => "success", "code" => 200)));
        }
        return new Response('This is not ajax!', 400);
    }

        public function addfavoriAction(Request $request) {
        if ($request->isXMLHttpRequest()) {
            //Appel du service de sauvegarde
            $params = array();
            $params["iditi"] = $request->request->get("iditi");
            $params["iduser"] = $request->request->get("iduser");

            $clientSOAP = new \SoapClient(null, array(
                'uri' => "http://localhost/Carto/web/app_dev.php/itineraire",
                'location' => "http://localhost/Carto/web/app_dev.php/itineraire",
                'trace' => true,
                'exceptions' => true
            ));

            $response = $clientSOAP->__call('addfavori', $params);
            $res = json_decode($response);
            return new Response(json_encode(array("result" => "success", "code" => 200)));
        }
        return new Response('This is not ajax!', 400);
    }
	
	public function testDeDroits($permission)
	{
		$manager = $this->getDoctrine()->getManager();
		
		$repository_permissions = $manager->getRepository("SiteCartoBundle:Permission");
		
		$permissions = $repository_permissions->findOneBy(array('label' => $permission));

		if(Count($permissions->getRole()) != 0)
		{
			$list_role = array();
			foreach($permissions->getRole() as $role)
			{
				array_push($list_role, 'ROLE_'.$role->getLabel());
			}
			
			// Test l'accès de l'utilisateur
			if(!$this->isGranted($list_role))
			{
				throw $this->createNotFoundException("Vous n'avez pas acces a cette page");
			}
		}
	}
}
