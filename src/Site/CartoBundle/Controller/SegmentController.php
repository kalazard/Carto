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
	/**
     * Fonction de création du serveur SOAP. En relation avec la classe segment_service
     *
	 * Fonction non utilisée
     *
     * @return Response
     *
     */
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

	/**
     * Fonction de mise à jour d'un segment
     *
	 * Cette fonction est appellée en Ajax. 
     *
     * @return Response
     *
     */
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
	
	/**
     * Fonction de mise à jour de plusieurs segments 
     *
	 * Cette fonction est appellée en Ajax. 
     *
     * @return Response
     *
     */
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
					if(++$i != count($poly["points"]))
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
			
			$lsArray = [];
			$elevationString = "";
				
            $response = new Response(json_encode(array("result" => "success","code" => 200,)));
            $response->headers->set('Content-Type', 'application/json');
            return $response;            
        }
        return new Response('This is not ajax!', 400);
    } 
	
	/**
     * Fonction de vérification des droits pour l'utilisateur courant. 
     *
	 * Cette fonction est utilisé dans les autres fonctions du controleur.
     *
     * @return none
     *
     */
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
	
	/**
     * Fonction de suppression d'un tronçon
     *
	 * Cette fonction est appellée en Ajax
     *
     * @return reponse
     *
     */	
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
	
	/**
     * Fonction de sauvegarde de plusieurs segment en base de données
     *
	 * Cette fonction est appellée en Ajax
     *
     * @return reponse
     *
     */	
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
