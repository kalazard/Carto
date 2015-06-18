<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Site\CartoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Site\CartoBundle\Entity\Trace;
use Site\CartoBundle\Entity\Segment;
use Site\CartoBundle\Entity\Point;
use Site\CartoBundle\Entity\Coordonnees;
use CrEOF\Spatial\PHP\Types\Geography\Point as MySQLPoint;
use CrEOF\Spatial\PHP\Types\Geography\LineString;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;

class SegmentController extends Controller
{
    public function indexAction()
    {
        $server = new \SoapServer(null, array('uri' => 'http://localhost/carto/web/app_dev.php/itineraire'));
        $server->setObject($this->get('segment_service'));

        $response = new Response();
        $response->headers->set('Content-Type', 'application/json');

        ob_start();
        $server->handle();
        $response->setContent(ob_get_clean());

        return $response;
    }

    public function updateAction(Request $request)
    {
        if ($request->isXMLHttpRequest()) 
        {
            $manager = $this->getDoctrine()->getManager();
            $repository=$manager->getRepository("SiteCartoBundle:Segment");
            $repositorypt=$manager->getRepository("SiteCartoBundle:Point");
            $repositorycoord=$manager->getRepository("SiteCartoBundle:Coordonnees");

            $segment = $repository->find($request->request->get("id",""));
            $pointArray = json_decode($request->request->get("points",""),true);
            $lsArray = [];
            $elevationString = "";
            $i = 0;
            var_dump($pointArray);
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

            $pog1 = $segment->getPog1();
            $coords1 = $pog1->getcoords();
            $coords1->setLatitude($pointArray[0]["lat"]);
            $coords1->setLongitude($pointArray[0]["lng"]);
            $coords1->setAltitude($pointArray[0]["elevation"]);
            $manager->persist($coords1);
            $manager->persist($pog1);

            $pog2 = $segment->getPog2();
            $coords2 = $pog2->getcoords();
            $coords2->setLatitude($pointArray[count($pointArray) - 1]["lat"]);
            $coords2->setLongitude($pointArray[count($pointArray) - 1]["lng"]);
            $coords2->setAltitude($pointArray[count($pointArray) - 1]["elevation"]);
            $manager->persist($coords2);
            $manager->persist($pog2);

            $segment->setTrace($ls);
            $segment->setElevation($elevationString);
            $segment->setSens(0);
            $manager->persist($segment);
            $manager->flush();

            $response = new Response(json_encode(array("result" => "success","code" => 200,)));
            $response->headers->set('Content-Type', 'application/json');
            return $response;            
        }
        return new Response('This is not ajax!', 400);
    } 
	
	public function updateMultipleAction(Request $request)
    {
        if ($request->isXMLHttpRequest()) 
        {	
			
            $manager = $this->getDoctrine()->getManager();
            $repository=$manager->getRepository("SiteCartoBundle:Segment");
            $repositorypt=$manager->getRepository("SiteCartoBundle:Point");
            $repositorycoord=$manager->getRepository("SiteCartoBundle:Coordonnees");
			
            $pointsArray = json_decode($request->request->get("points",""),true);
            $lsArray = [];
            $elevationString = "";
            $i = 0;
			
			//on lit toutes les points passé en paramètre  
			
			foreach($pointsArray as $poly)
			{	
				$segment = $repository->find($poly['id']);
				
				foreach($poly["points"] as $point)
				{
					$newPoint = new MySQLPoint(floatval($point["lng"]),floatval($point["lat"]));
					array_push($lsArray,$newPoint);
					
					//pour l'instant pb d'élevation 
					//$elevationString = $elevationString . $point["elevation"];
					
					$elevationString = $elevationString . "1";
					if(++$i != count($pointsArray))
					{
						$elevationString = $elevationString . ";";
					}
				}
				
				$ls = new LineString($lsArray);

				$pog1 = $segment->getPog1();
				$coords1 = $pog1->getcoords();
				$coords1->setLatitude($poly["points"][0]["lat"]);
				$coords1->setLongitude($poly["points"][0]["lng"]);
				//	pb elevation
				//	$coords1->setAltitude($poly["points"][0]["elevation"]);4
				$coords1->setAltitude("1");
				$manager->persist($coords1);
				$manager->persist($pog1);

				$pog2 = $segment->getPog2();
				$coords2 = $pog2->getcoords();
				$coords2->setLatitude($poly["points"][count($poly["points"]) - 1]["lat"]);
				$coords2->setLongitude($poly["points"][count($poly["points"]) - 1]["lng"]);
				//
				//$coords2->setAltitude($poly["points"][count($poly["points"]) - 1]["elevation"]);
				$coords2->setAltitude("1");
				$manager->persist($coords2);
				$manager->persist($pog2);

				$segment->setTrace($ls);
				$segment->setElevation($elevationString);
				$segment->setSens(0);
				$manager->persist($segment);
				$manager->flush();
			}
				
            $response = new Response(json_encode(array("result" => "success","code" => 200,)));
            $response->headers->set('Content-Type', 'application/json');
            return $response;            
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
//fonction pour supprimer un segment et le remplacer par d'autre
	public function ReplaceSegmentAction(Request $request)
	{
		if ($request->isXMLHttpRequest()) 
        {	
		
			$response = new Response(json_encode(array("result" => "success","code" => 200,)));
            $response->headers->set('Content-Type', 'application/json');
            return $response;            
        }
        return new Response('This is not ajax!', 400);
	}
	
	//fonction pour supprimer un tronçon
	
	public function DeleteTroncAction(Request $request)
	{
		if ($request->isXMLHttpRequest()) 
        {	
			//boucle foreach pour supprimer les tronçons
			
			$ids = $request->request->get("idTron","");
			
			$em = $this->getDoctrine()->getEntityManager();
			
			foreach($ids as $var)
			{
				$seg = $em->getRepository('SiteCartoBundle:Segment')->find($var);

				if (!$seg) {
					throw $this->createNotFoundException('no segment found for id :'.$var);
				}

				$em->remove($seg);
				$em->flush();
			}	
			
			$response = new Response(json_encode(array("result" => "success","code" => 200,)));
            $response->headers->set('Content-Type', 'application/json');
            return $response;            
        }
        return new Response('This is not ajax!', 400);
	}
	
	//fonction pour supprimer un segment d'un tronçon
	
	public function DeleteSegFromTroncAction()
	{
		if ($request->isXMLHttpRequest()) 
        {	
			//on récupère le premier nuage de point
			$tid = $request->request->get("tid","");
			$pts1 = $request->request->get("pts1","");
			$pts2 = $request->request->get("pts2","");
			
			$em = $this->getDoctrine()->getEntityManager();
			
			//on récupère l'id du premier itinéraire (on le garde)
			$seg = $em->getRepository('SiteCartoBundle:Segment')->find($tid);
			
			//modification de la route existante et modification du deuxième pog
			
			$lsArray = [];
            $elevationString = "";
            $i = 0;
            foreach ($pts1 as $pt) {
                $newPoint = new MySQLPoint(floatval($pt["lng"]), floatval($pt["lat"]));
                array_push($lsArray, $newPoint);
                $elevationString = $elevationString . $pt["elevation"];
                if (++$i != count($pointArray)) {
                    $elevationString = $elevationString . ";";
                }
            }
            $ls = new LineString($lsArray);
			
			//plus bon il faut prendre le dernier point du nuage
			
			/*
			$pog2 = new Point();
            $coords2 = new Coordonnees();
            $coords2->setLatitude($pog["lat"]);
            $coords2->setLongitude($pog["lng"]);
            $coords2->setAltitude($pog["elevation"]);
            $pog2->setCoords($coords2);
            $pog2->setOrdre(2);
            $manager->persist($coords2);
            $manager->persist($pog2);
			*/
			
			//puis on crée un nouveau segment avec le script de base. (seule diff c'est qu'on prend les points de la variable pts2)
			
			 $trace = new Trace();
            $filename = uniqid('trace_', true) . '.csv';
            $trace->setPath($filename);

            $segment = new Segment();

            $lsArray = [];
            $elevationString = "";
            $i = 0;
            foreach ($pts2 as $pt) {
                $newPoint = new MySQLPoint(floatval($pt["lng"]), floatval($pt["lat"]));
                array_push($lsArray, $newPoint);
                $elevationString = $elevationString . $pt["elevation"];
                if (++$i != count($pointArray)) {
                    $elevationString = $elevationString . ";";
                }
            }
            $ls = new LineString($lsArray);

			//pareil, prendre le premier point du nuage
			
            $pog1 = new Point();
            $coords1 = new Coordonnees();
            $coords1->setLatitude($pog["lat"]);
            $coords1->setLongitude($pog[0]["lng"]);
            $coords1->setAltitude($pog[0]["elevation"]);
            $pog1->setCoords($coords1);
            $pog1->setOrdre(1);
            $manager->persist($coords1);
            $manager->persist($pog1);

            $pog2 = new Point();
            $coords2 = new Coordonnees();
            $coords2->setLatitude($pts2[count($pointArray) - 1]["lat"]);
            $coords2->setLongitude($pts2[count($pointArray) - 1]["lng"]);
            $coords2->setAltitude($pts2[count($pointArray) - 1]["elevation"]);
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
			
			//on retourne 200 si le code a fonctionner
		
			$response = new Response(json_encode(array("result" => "success","code" => 200,)));
            $response->headers->set('Content-Type', 'application/json');
            return $response;            
        }
        return new Response('This is not ajax!', 400);
	}
	
	public function AddPOGTronAction(Request $request)
	{
		if ($request->isXMLHttpRequest()) 
        {	
			//on récupère le premier nuage de point
			$tid = $request->request->get("tid","");
			$pts1 = $request->request->get("pts1","");
			$pts2 = $request->request->get("pts2","");
			$pog = $request->request->get("pog","");
			
			$em = $this->getDoctrine()->getEntityManager();
			
			//on récupère l'id du premier itinéraire (on le garde)
			$seg = $em->getRepository('SiteCartoBundle:Segment')->find($tid);
			
			//modification de la route existante et modification du deuxième pog
			
			$lsArray = [];
            $elevationString = "";
            $i = 0;
            foreach ($pts1 as $pt) {
                $newPoint = new MySQLPoint(floatval($pt["lng"]), floatval($pt["lat"]));
                array_push($lsArray, $newPoint);
                $elevationString = $elevationString . $pt["elevation"];
                if (++$i != count($pointArray)) {
                    $elevationString = $elevationString . ";";
                }
            }
            $ls = new LineString($lsArray);
			
			//plus bon il faut prendre le dernier point du nuage
			
			/*
			$pog2 = new Point();
            $coords2 = new Coordonnees();
            $coords2->setLatitude($pog["lat"]);
            $coords2->setLongitude($pog["lng"]);
            $coords2->setAltitude($pog["elevation"]);
            $pog2->setCoords($coords2);
            $pog2->setOrdre(2);
            $manager->persist($coords2);
            $manager->persist($pog2);
			*/
			
			//puis on crée un nouveau segment avec le script de base. (seule diff c'est qu'on prend les points de la variable pts2)
			
			 $trace = new Trace();
            $filename = uniqid('trace_', true) . '.csv';
            $trace->setPath($filename);

            $segment = new Segment();

            $lsArray = [];
            $elevationString = "";
            $i = 0;
            foreach ($pts2 as $pt) {
                $newPoint = new MySQLPoint(floatval($pt["lng"]), floatval($pt["lat"]));
                array_push($lsArray, $newPoint);
                $elevationString = $elevationString . $pt["elevation"];
                if (++$i != count($pointArray)) {
                    $elevationString = $elevationString . ";";
                }
            }
            $ls = new LineString($lsArray);

			//pareil, prendre le premier point du nuage
			
            $pog1 = new Point();
            $coords1 = new Coordonnees();
            $coords1->setLatitude($pog["lat"]);
            $coords1->setLongitude($pog[0]["lng"]);
            $coords1->setAltitude($pog[0]["elevation"]);
            $pog1->setCoords($coords1);
            $pog1->setOrdre(1);
            $manager->persist($coords1);
            $manager->persist($pog1);

            $pog2 = new Point();
            $coords2 = new Coordonnees();
            $coords2->setLatitude($pts2[count($pointArray) - 1]["lat"]);
            $coords2->setLongitude($pts2[count($pointArray) - 1]["lng"]);
            $coords2->setAltitude($pts2[count($pointArray) - 1]["elevation"]);
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
			
			//on retourne 200 si le code a fonctionner
		
			$response = new Response(json_encode(array("result" => "success","code" => 200,)));
            $response->headers->set('Content-Type', 'application/json');
            return $response;            
        }
        return new Response('This is not ajax!', 400);
	}
	
	// sauvegarde de plusieurs segments dans la base de données
	function saveMultipleAction(Request $request)
	{
		if($request->isXMLHttpRequest()) 
        {
			//on récupère le tableau slice et on crée toutes les polylines présentes.			
			$tab = json_decode($request->request->get("tab", ""), true);
			
			//structure du machin : $tab[pos de collision][points à save dans la bdd]
			foreach($tab as $points)
			{			
				$manager = $this->getDoctrine()->getManager();

				$segment = new Segment();
				
				//penser à gérer l'élévation !!!!!!!!
				
				$lsArray = [];
				$elevationString = "";
				$i = 0;
				
				if(is_array($points))
				{
					foreach ($points as $pt) {
						$newPoint = new MySQLPoint(floatval($pt["lng"]), floatval($pt["lat"]));
						array_push($lsArray, $newPoint);
						$elevationString = $elevationString . "1";
						if (++$i != count($points)) {
							$elevationString = $elevationString . ";";
						}
					}
				}
				
				$ls = new LineString($lsArray);

				$pog1 = new Point();
				$coords1 = new Coordonnees();
				$coords1->setLatitude($points[0]["lat"]);
				$coords1->setLongitude($points[0]["lng"]);
				$coords1->setAltitude("1");
				$pog1->setCoords($coords1);
				$pog1->setOrdre(1);
				$manager->persist($coords1);
				$manager->persist($pog1);

				$pog2 = new Point();
				$coords2 = new Coordonnees();
				$coords2->setLatitude($points[count($points) - 1]["lat"]);
				$coords2->setLongitude($points[count($points) - 1]["lng"]);
				$coords2->setAltitude("1");
				$pog2->setCoords($coords2);
				$pog2->setOrdre(2);
				$manager->persist($coords2);
				$manager->persist($pog2);

				$segment->setTrace($ls);
				$segment->setElevation($elevationString);
				$segment->setSens(0);
				$segment->setPog1($pog1);
				$segment->setPog2($pog2);

				$manager->persist($segment);
				$manager->flush();
			
			}
			
			$response = new Response(json_encode(array("result" => "success","code" => 200,)));
            $response->headers->set('Content-Type', 'application/json');
            return $response;            
        }
        return new Response('This is not ajax!', 400);
	}
}
