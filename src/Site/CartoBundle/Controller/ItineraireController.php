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
use CrEOF\Spatial\PHP\Types\Geography\LineString;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class ItineraireController extends Controller {

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

    /* public function saveAction(Request $request) {
      if ($request->isXMLHttpRequest()) {
      $manager = $this->getDoctrine()->getManager();
      $repositoryDiff = $manager->getRepository("SiteCartoBundle:Difficulteparcours");
      $repositoryUser = $manager->getRepository("SiteCartoBundle:Utilisateur");
      $repositoryStatus = $manager->getRepository("SiteCartoBundle:Status");
      $repositoryTypechemin = $manager->getRepository("SiteCartoBundle:Typechemin");

      $trace = new Trace();
      $filename = uniqid('trace_', true) . '.gpx';
      $trace->setPath($filename);
      $diff = $repositoryDiff->find($request->request->get("difficulte", ""));
      $user = $repositoryUser->find($request->request->get("auteur", ""));
      $status = $repositoryStatus->find($request->request->get("status", ""));
      $typechemin = $repositoryTypechemin->find($request->request->get("typechemin", ""));

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
      $route->setSegment($segment);

      $json_obj = json_decode($request->request->get("points", ""), true);


      $manager->persist($route);
      $manager->persist($trace);
      $manager->persist($segment);

      $manager->flush();

      $this->saveGpx($route->getId(), $filename);

      $response = new Response(json_encode(array("result" => "success", "code" => 200, "jsonObject" => json_encode($route))));
      $response->headers->set('Content-Type', 'application/json');
      return $response;
      }
      return new Response('This is not ajax!', 400);
      } */

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
            $route->setSegment($segment);

            $json_obj = json_decode($request->request->get("points", ""), true);


            $manager->persist($route);
            $manager->persist($trace);
            $manager->persist($segment);
            $manager->flush();
            $this->saveGpx($route->getId(), $filename);
            $response = new Response(json_encode(array("result" => "success", "code" => 200, "jsonObject" => json_encode($route))));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        return new Response('This is not ajax!', 400);
    }

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

    public function saveGpx($id_itineraire, $filename) {
        $manager = $this->getDoctrine()->getManager();
        $itineraire = $manager->getRepository("SiteCartoBundle:Itineraire")->find($id_itineraire);
        $linestring = $itineraire->getSegment()->getTrace();
        $elevationString = $itineraire->getSegment()->getElevation();
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

    public function loadAction($id) {
        $repository = $this->getDoctrine()->getManager()->getRepository('SiteCartoBundle:Itineraire');
        $iti = $repository->find($id);
        $content = $this->get("templating")->render("SiteCartoBundle:Map:load.html.twig", array("itineraire" => $iti, "jsonObject" => json_encode($iti)));
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

    /*
     * Fonction de sauvegarde d'un segment
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
            $response = new Response(json_encode(array("result" => "success", "code" => 200)));
            $response->headers->set('Content-Type', 'application/json');
            return $response;
        }
        return new Response('This is not ajax!', 400);
    }
	
	public function loadSegmentAction(Request $request)
	{
		/*if($request->isXMLHttpRequest()) 
      {
			$northeast = $request->request->get("northeast","");
			$southwest = $request->request->get("southwest","");
      $northewest = $request->request->get("northewest","");
      $southeast = $request->request->get("southeast","");	

      $bounds = new Polygon([new LineString[new MySQLPoint($northeast["lng"], $northeast["lat"]),
                    new MySQLPoint($southwest["lng"], $southwest["lat"]),
                    new MySQLPoint($northewest["lng"], $northewest["lat"]),
                    new MySQLPoint($southeast["lng"], $southeast["lat"])]]);

			$query = $repository->createQueryBuilder('i')->where("MBRContains(:bounds, i.segment.pog1)")->setParameter('bounds', $bounds);
      $$listSegment = $query->getQuery()->getResult();*/
			//requete pour trouver les résultats 
			/*
			
			$repository = $this->entityManager->getRepository('SiteCartoBundle:Segment');
			$repositoryUser=$manager->getRepository("SiteCartoBundle:Utilisateur");
            $repositoryStatus=$manager->getRepository("SiteCartoBundle:Status");
            $repositoryTypechemin=$manager->getRepository("SiteCartoBundle:Typechemin");
			
            $query = $repository->createQueryBuilder('i')->where('i.nom LIKE :nom')->setParameter('nom', '%'.$nom.'%');
			//$query->andWhere('i.typechemin = :typechemin')->setParameter('typechemin', $typechemin);
            $listItiniraire = $query->getQuery()->getResult();
			
			//on renvoit le reste sous la forme d'une liste json -> traitement sur le map.js
			
            return json_encode(array("searchResults" => $listItiniraire));
			
			$response = new Response(json_encode(array("result" => "success","code" => 200)));
			return $response; 
		}
		return new Response('This is not ajax!', 400);*/		
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

    //fonction de recherche des itinéraires 
    public function searchAction(Request $request) {
        $clientSOAP = new \SoapClient(null, array(
            'uri' => "http://localhost/Carto/web/app_dev.php/itineraire",
            'location' => "http://localhost/Carto/web/app_dev.php/itineraire",
            'trace' => true,
            'exceptions' => true
        ));

        //Chargement de la liste des difficultés dans le select
        $responseDiff = $clientSOAP->__call('difficultelist', array());

        //Chargement de la liste des status dans le select
        $responseStat = $clientSOAP->__call('statuslist', array());

        //Chargement de la liste des types de chemin dans le select
        $responseType = $clientSOAP->__call('typecheminlist', array());

        if ($request->request->get("valid") == "ok") {
            //Appel du service de recherche
            $search = array();
            $search["nom"] = $request->request->get("nom");
            $search["typechemin"] = $request->request->get("typechemin");
            $search["longueur"] = $request->request->get("longueur");
            $search["datecrea"] = $request->request->get("datecrea");
            $search["difficulte"] = $request->request->get("difficulte");
            $search["status"] = $request->request->get("status");

            $response = $clientSOAP->__call('search', $search);

            $res_search = json_decode($response);
            $resDiff = json_decode($responseDiff);
            $resStat = json_decode($responseStat);
            $resType = json_decode($responseType);
            $content = $this->get("templating")->render("SiteCartoBundle:Itineraire:SearchItineraire.html.twig", array("resultats" => $res_search, "diffs" => $resDiff, "stats" => $resStat, "typechemin" => $resType, "list" => array()));
        } else {
            // Recupère la liste complète
            $response = $clientSOAP->__call('itilist', array());

            $res_list = json_decode($response);
            $resDiff = json_decode($responseDiff);
            $resStat = json_decode($responseStat);
            $resType = json_decode($responseType);
            $content = $this->get("templating")->render("SiteCartoBundle:Itineraire:SearchItineraire.html.twig", array("resultats" => array(), "diffs" => $resDiff, "stats" => $resStat, "typechemin" => $resType, "list" => $res_list));
        }

        return new Response($content);
    }

    public function getByIdAction($id) {
        //Appel du service de recherche
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

        $content = $this->get("templating")->render("SiteCartoBundle:Itiniraire:fiche_itineraire.html.twig", array("resultats" => $res, "jsonObject" => $response));
        return new Response($content);
    }

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

}
