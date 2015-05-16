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

class ItineraireController extends Controller
{
    public function indexAction()
    {
        $server = new \SoapServer(null, array('uri' => 'http://localhost/carto/web/app_dev.php/itineraire'));
        $server->setObject($this->get('itineraire_service'));

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        ob_start();
        $server->handle();
        $response->setContent(ob_get_clean());

        return $response;
    }

    public function getDifficultesAction(Request $request)
    {
      if ($request->isXMLHttpRequest()) 
      {
        $manager = $this->getDoctrine()->getManager();
        $repository = $manager->getRepository("SiteCartoBundle:Difficulteparcours");
        $diffs = $repository->findAll();
        $response = new Response(json_encode($diffs));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
      }

      return new Response('This is not ajax!', 400);
    }

    public function getStatusAction(Request $request)
    {
      if ($request->isXMLHttpRequest()) 
      {
        $manager = $this->getDoctrine()->getManager();
        $repository = $manager->getRepository("SiteCartoBundle:Status");
        $stats = $repository->findAll();
        $response = new Response(json_encode($stats));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
      }

      return new Response('This is not ajax!', 400);
    }

    public function getTypecheminAction(Request $request)
    {
      if ($request->isXMLHttpRequest()) 
      {
        $manager = $this->getDoctrine()->getManager();
        $repository = $manager->getRepository("SiteCartoBundle:Typechemin");
        $typechemin = $repository->findAll();
        $response = new Response(json_encode($typechemin));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
      }

      return new Response('This is not ajax!', 400);
    }

    public function saveAction(Request $request)
    {
        if ($request->isXMLHttpRequest()) 
        {
            $manager = $this->getDoctrine()->getManager();
            $repositoryDiff=$manager->getRepository("SiteCartoBundle:Difficulteparcours");
            $repositoryUser=$manager->getRepository("SiteCartoBundle:Utilisateur");
            $repositoryStatus=$manager->getRepository("SiteCartoBundle:Status");
            $repositoryTypechemin=$manager->getRepository("SiteCartoBundle:Typechemin");

            $trace = new Trace();
            $filename = uniqid('trace_', true) . '.csv';
            $trace->setPath($filename);
            $diff = $repositoryDiff->find($request->request->get("difficulte",""));
            $user = $repositoryUser->find($request->request->get("auteur",""));
            $status = $repositoryStatus->find($request->request->get("status",""));
            $typechemin = $repositoryTypechemin->find($request->request->get("typechemin",""));

            $segment = new Segment();
            $pointArray = json_decode($request->request->get("points",""),true);
            $lsArray = [];
            $elevationString = "";
            $i = 0;
            foreach($pointArray as $pt)
            {
                $newPoint = new MySQLPoint(floatval($pt["lng"]),floatval($pt["lat"]));
                array_push($lsArray,$newPoint);
                $elevationString = $elevationString . $pt["elevation"];
                if(++$i != count($pointArray))
                {
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
            $route->setLongueur($request->request->get("longueur",""));
            $route->setDeniveleplus($request->request->get("denivelep",""));
            $route->setDenivelemoins($request->request->get("denivelen",""));
            $route->setTrace($trace);
            $route->setNom($request->request->get("nom",""));
            $route->setNumero($request->request->get("numero",""));
            $route->setTypechemin($typechemin);
            $route->setDescription($request->request->get("description",""));
            $route->setDifficulte($diff);
            $route->setAuteur($user);
            $route->setStatus($status);
            $route->setPublic($request->request->get("public",""));
            $route->setSegment($segment);

            $json_obj = json_decode($request->request->get("points",""),true);
            $fp = fopen('../../Traces/'.$filename, 'w');
            $firstLineKeys = false;
            foreach ($json_obj as $line)
            {
                if (empty($firstLineKeys))
                {
                    $firstLineKeys = array_keys($line);
                    fputcsv($fp, $firstLineKeys);
                    $firstLineKeys = array_flip($firstLineKeys);
                }
                fputcsv($fp, array_merge($firstLineKeys, $line));
            }
            fclose($fp);

           // $manager->persist($route);
            $manager->persist($trace);
            $manager->persist($segment);
            $manager->flush();
            $response = new Response(json_encode(array("result" => "success","code" => 200,"jsonObject" => json_encode($iti))));
            $response->headers->set('Content-Type', 'application/json');
            return $response;            
        }
        return new Response('This is not ajax!', 400);
    }

    public function loadAction($id)
    {
		$repository = $this->getDoctrine()->getManager()->getRepository('SiteCartoBundle:Itineraire');
		$iti = $repository->find($id);
		$content = $this->get("templating")->render("SiteCartoBundle:Map:load.html.twig",array("itineraire" => $iti,"jsonObject" => json_encode($iti)));
		return new Response($content);            
    }
	
	public function rechercheAction(Request $request)
	{
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
	 
	public function saveSegmentAction(Request $request)
    {
        if ($request->isXMLHttpRequest()) 
        {
            $manager = $this->getDoctrine()->getManager();
            $repositoryDiff=$manager->getRepository("SiteCartoBundle:Difficulteparcours");
            $repositoryUser=$manager->getRepository("SiteCartoBundle:Utilisateur");
            $repositoryStatus=$manager->getRepository("SiteCartoBundle:Status");
            $repositoryTypechemin=$manager->getRepository("SiteCartoBundle:Typechemin");

            $trace = new Trace();
            $filename = uniqid('trace_', true) . '.csv';
            $trace->setPath($filename);
            $diff = $repositoryDiff->find($request->request->get("difficulte",""));
            $user = $repositoryUser->find($request->request->get("auteur",""));
            $status = $repositoryStatus->find($request->request->get("status",""));
            $typechemin = $repositoryTypechemin->find($request->request->get("typechemin",""));

            $segment = new Segment();
            $pointArray = json_decode($request->request->get("points",""),true);
            $lsArray = [];
            $elevationString = "";
            $i = 0;
            foreach($pointArray as $pt)
            {
                $newPoint = new MySQLPoint(floatval($pt["lng"]),floatval($pt["lat"]));
                array_push($lsArray,$newPoint);
                $elevationString = $elevationString . $pt["elevation"];
                if(++$i != count($pointArray))
                {
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
            $route->setLongueur($request->request->get("longueur",""));
            $route->setDeniveleplus($request->request->get("denivelep",""));
            $route->setDenivelemoins($request->request->get("denivelen",""));
            $route->setTrace($trace);
            $route->setNom($request->request->get("nom",""));
            $route->setNumero($request->request->get("numero",""));
            $route->setTypechemin($typechemin);
            $route->setDescription($request->request->get("description",""));
            $route->setDifficulte($diff);
            $route->setAuteur($user);
            $route->setStatus($status);
            $route->setPublic($request->request->get("public",""));
            $route->setSegment($segment);

            $json_obj = json_decode($request->request->get("points",""),true);
            $fp = fopen('../../Traces/'.$filename, 'w');
            $firstLineKeys = false;
            foreach ($json_obj as $line)
            {
                if (empty($firstLineKeys))
                {
                    $firstLineKeys = array_keys($line);
                    fputcsv($fp, $firstLineKeys);
                    $firstLineKeys = array_flip($firstLineKeys);
                }
                fputcsv($fp, array_merge($firstLineKeys, $line));
            }
            fclose($fp);

            $manager->persist($route);
            $manager->persist($trace);
            $manager->persist($segment);
            $manager->flush();
            $response = new Response(json_encode(array("result" => "success","code" => 200)));
            $response->headers->set('Content-Type', 'application/json');
            return $response;            
        }
        return new Response('This is not ajax!', 400);
    }
	
	public function loadSegmentAction(Request $request)
	{
		if($request->isXMLHttpRequest()) 
        {
			$north = $request->request->get("north","");
			$south = $request->request->get("south","");
			
			//on calcul les deux autres points pour former un rectangle de la taille de l'écran 			
			
			//requete pour trouver les résultats 
			
			$response = new Response(json_encode(array("result" => "success","code" => 200)));
			return $response; 
		}
		return new Response('This is not ajax!', 400);		
	}
	
	public function getSegmentByIdAction(Request $request)
	{
		//on récupère l'id de l'itinéraire à charger 
		
		//on charge ses données
		
		//on renvoit le tout
		
		$id = $request->get('id');
		
		//on charge les infos de l'utilisateur courant.
		$manager = $this->getDoctrine()->getManager();
		//on recherche l'itinéraire associé à son id
		$data = $manager->getRepository('SiteCartoBundle:Itineraire')->findOneBy(array('id'=>$id)); 
			
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
		if($data->getPublic() == 1)
		{
			$public = "publique";
		}
		
		$result['public'] = $public;
		
		$content = $this->get("templating")->render("SiteCartoBundle:Itineraire:fiche_itineraire.html.twig",$result);
		return new Response($content);
	}
	
	 public function getFormDataAction(Request $request)
    {	
    	//si c'est un appel AJAX
		if($request->isXMLHttpRequest()) 
        {
			//Chargement de la liste des difficultés dans le select
			$manager = $this->getDoctrine()->getManager();
			$repository = $manager->getRepository("SiteCartoBundle:Difficulteparcours");
			$diffs = $repository->findAll();
			
			$result = array();
			$result['difficulte'] = array();
			$compteur = 0;
			foreach($diffs as $var)
			{
				$result['difficulte'][$compteur]['niveau'] = $var->getNiveau();
				$result['difficulte'][$compteur]['texte'] = $var->getLabel();
				
				$compteur++;
			}
		
			//Chargement de la liste des status dans le select
			$repository = $manager->getRepository("SiteCartoBundle:Status");
			$diffs = $repository->findAll();
			
			$result['status'] = array();
			$compteur = 0;
			foreach($diffs as $var)
			{
				$result['status'][$compteur]['texte'] = $var->getLabel();
				$result['status'][$compteur]['id'] = $var->getId();
				
				$compteur++;
			}			

			//Chargement de la liste des types de chemin dans le select
			$repository = $manager->getRepository("SiteCartoBundle:Typechemin");
			$diffs = $repository->findAll();
			
			$result['type'] = array();
			$compteur = 0;
			foreach($diffs as $var)
			{
				$result['type'][$compteur]['texte'] = $var->getLabel();
				$result['type'][$compteur]['id'] = $var->getId();
				
				$compteur++;
			}
			
			$data = json_encode($result);
		  
			return new Response($data);
		}
		return new Response('This is not ajax!', 400);	
    }
	
	public function updateItiAction(Request $request)
	{
		if($request->isXMLHttpRequest()) 
        {	
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
		
			$repositoryDiff=$manager->getRepository("SiteCartoBundle:Difficulteparcours");
			$repositoryStat=$manager->getRepository("SiteCartoBundle:Status");
			$repositoryType=$manager->getRepository("SiteCartoBundle:Typechemin");
			$repositoryIti=$manager->getRepository("SiteCartoBundle:Itineraire");

			$diff = $repositoryDiff->find($params["difficulte"]);
			$stat = $repositoryStat->find($params["status"]);
			$type = $repositoryType->find($params["typechemin"]);

			$route = $repositoryIti->findBy(array('id' => $params["id"]));
			$route[0]->setNom($params["nom"]);
			$route[0]->setNumero($params["numero"] );
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
	public function searchAction(Request $request)
	{
		$clientSOAP = new \SoapClient(null, array(
                    'uri' => "http://localhost/Carto/web/app_dev.php/itineraire",
                    'location' => "http://localhost/Carto/web/app_dev.php/itineraire",
                    'trace' => true,
                    'exceptions' => true
                ));

		//Chargement de la liste des difficultés dans le select
        $responseDiff = $clientSOAP->__call('difficultelist',array());

        //Chargement de la liste des status dans le select
        $responseStat = $clientSOAP->__call('statuslist',array());

        //Chargement de la liste des types de chemin dans le select
        $responseType = $clientSOAP->__call('typecheminlist',array());
		
        if($request->request->get("valid") == "ok")
        {
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
			$content = $this->get("templating")->render("SiteCartoBundle:Itineraire:SearchItineraire.html.twig",array("resultats" => $res_search,"diffs" => $resDiff,"stats" => $resStat,"typechemin" => $resType, "list" => array()));
        }
		else
		{
			// Recupère la liste complète
			$response = $clientSOAP->__call('itilist', array());
		
			$res_list = json_decode($response);
			$resDiff = json_decode($responseDiff);
			$resStat = json_decode($responseStat);
			$resType = json_decode($responseType);
			$content = $this->get("templating")->render("SiteCartoBundle:Itineraire:SearchItineraire.html.twig",array("resultats" => array(),"diffs" => $resDiff,"stats" => $resStat,"typechemin" => $resType,"list" => $res_list));
		}

		return new Response($content);
	}
	
	public function getByIdAction($id)
	{
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
			
			$content = $this->get("templating")->render("SiteTrailBundle:Itiniraire:FicheItineraire.html.twig",array("resultats" => $res,"jsonObject" => $response));
			return new Response($content);
	}

	 
}
