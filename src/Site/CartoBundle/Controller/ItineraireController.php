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

            $manager->persist($route);
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
}
