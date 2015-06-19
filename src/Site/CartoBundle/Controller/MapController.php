<?php

namespace Site\CartoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Site\CartoBundle\Entity\Poi;
use Site\CartoBundle\Entity\Coordonnees;
use Site\CartoBundle\Entity\TypeLieu;
use Site\CartoBundle\Entity\Icone;
use Site\CartoBundle\Entity\Itineraire;
use Site\CartoBundle\Entity\Gpx;
use Site\CartoBundle\Entity\Segment;
use Site\CartoBundle\Entity\Trace;
use Site\CartoBundle\Entity\DifficulteParcours;
use CrEOF\Spatial\PHP\Types\Geography\Point as MySQLPoint;
use CrEOF\Spatial\PHP\Types\Geography\LineString;
use Site\CartoBundle\Entity\Point;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Validator\Constraints\DateTime;


class MapController extends Controller
{
    public function indexAction()
    {       
        $content = $this->get("templating")->render("SiteCartoBundle:Map:map.html.twig");        
        return new Response($content);
    }

    public function indexFrameAction()
    {       
        $content = $this->get("templating")->render("SiteCartoBundle:Map:mapFrame.html.twig");        
        return new Response($content);
    }

    public function createRouteAction(Request $request)
    {
      if ($request->isXMLHttpRequest()) 
      {
        $manager=$this->getDoctrine()->getManager();
        $repositoryDiff=$manager->getRepository("SiteCartoBundle:DifficulteParcours");

        $gpx = new Gpx();
        $gpx->setPath("test");
        $diff = $repositoryDiff->find($request->request->get("difficulte",""));

        $route = new Itiniraire();
        $route->setDate(new \DateTime('now'));
        $route->setLongueur($request->request->get("longueur","120"));
        $route->setDenivele($request->request->get("elevation","130"));
        $route->setItiniraire($gpx);
        $route->setNom($request->request->get("nom",""));
        $route->setNumero($request->request->get("numero",""));
        $route->setTypechemin($request->request->get("typechemin",""));
        $route->setCommentaire($request->request->get("commentaire",""));
        $route->setDifficulté($diff);

        $manager->persist($route);
        $manager->persist($gpx);
        $manager->flush();
        return new JsonResponse(array('data' => 'Itinéraire Crée'),200);
      }

      return new Response('This is not ajax!', 400);
    }
	
	public function uploadAction()
	{		
		$content = $this->get("templating")->render("SiteCartoBundle:Map:upload.html.twig");
		return new Response($content);
	}
	
	public function submitAction(Request $request)
	{	
		if ($request->isXMLHttpRequest()) 
		{		
			$return_message = "";
			$code = 200;

			$gpx_name = $_FILES['upl']['name'];
			$target_dir ="C:/wamp/www/uploads/";
			$target_file = $target_dir.$gpx_name;
			$upload = 1;
			$ext = $_FILES["upl"]["name"];
			$imageFileType = pathinfo($ext,PATHINFO_EXTENSION);
			
			// Check if file already exists
			if (file_exists($target_file)) 
			{
				$return_message .= " Le fichier existe déjà. ";
				$upload = 0;
			}
			
			// Check file size
			if ($_FILES["upl"]["size"] > 5000000) {
				$return_message .= " Le fichier est trop volumineux. La taille est limitée à 5000 Ko. ";
				$upload = 0;
			}
			
			if($imageFileType != "gpx") 
			{
				$return_message .= " Le format de fichier n'est pas valide.";
				$upload = 0;
			} 
			
			if($upload != 0)
			{
				if(move_uploaded_file($_FILES['upl']['tmp_name'], $target_file))
				{
					//si l'upload a réussi, on sauvegarde le chemin de destination dans la base de données (table Trace)
				$trace = new Trace();
				$trace->setPath($target_file);
				
				$em = $this->getDoctrine()->getManager();
				
				$repositoryDiff = $em->getRepository("SiteCartoBundle:Difficulteparcours");
				$repositoryUser = $em->getRepository("SiteCartoBundle:Utilisateur");
				$repositoryStatus = $em->getRepository("SiteCartoBundle:Status");
				$repositoryTypechemin = $em->getRepository("SiteCartoBundle:Typechemin");
				
				$diff = $repositoryDiff->find(1);
				$user = $repositoryUser->find( $this->getUser()->getId());
				$status = $repositoryStatus->find(1);
				$typechemin = $repositoryTypechemin->find(1);
					
				$segment = new Segment();
				$pointArray = simplexml_load_file($target_file);
				$lsArray = [];
				$elevationString = "";
				$i = 0;
				foreach ($pointArray->trk->trkseg->{'trkpt'} as $pt) {
					$newPoint = new MySQLPoint(floatval($pt->attributes()->lon), floatval($pt->attributes()->lat));
					array_push($lsArray, $newPoint);
					$elevationString = $elevationString . $pt->ele;
					if (++$i != count($pointArray)) {
						$elevationString = $elevationString . ";";
					}
				}
                $lsArray = $this->RamerDouglasPeucker($lsArray,0.0015);
				$ls = new LineString($lsArray);
				
				$pog1 = new Point();
				$coords1 = new Coordonnees();
				$coords1->setLatitude($pointArray->trk->trkseg->trkpt->attributes()->lat);
				$coords1->setLongitude($pointArray->trk->trkseg->trkpt->attributes()->lon);
				$coords1->setAltitude($pointArray->trk->trkseg->trkpt->ele);
				$pog1->setCoords($coords1);
				$pog1->setOrdre(1);
				$em->persist($coords1);
				$em->persist($pog1);

				$pog2 = new Point();
				$coords2 = new Coordonnees();
				$coords2->setLatitude($pointArray->trk->trkseg->trkpt->attributes()->lat);
				$coords2->setLongitude($pointArray->trk->trkseg->trkpt->attributes()->lon);
				$coords2->setAltitude($pointArray->trk->trkseg->trkpt->ele);
				$pog2->setCoords($coords2);
				$pog2->setOrdre(2);
				$em->persist($coords2);
				$em->persist($pog2);
				
				$segment->setTrace($ls);
				$segment->setElevation($elevationString);
				$segment->setSens(0);
				$segment->setPog1($pog1);
				$segment->setPog2($pog2);
					
				//sauvegarde dans la table itinéraire 
				$iti = new Itineraire();
				$iti->setDatecreation(new \DateTime('now'));
				$iti->setLongueur(0);
				$iti->setDeniveleplus(0);
				$iti->setDenivelemoins(0);
				$iti->setTrace($trace);
				$iti->setNom("upload");
				$iti->setNumero("10");
				$iti->setTypechemin($typechemin);
				$iti->setDescription("description");
				$iti->setDifficulte($diff);
				$iti->setAuteur($user);
				$iti->setStatus($status);
				$iti->setPublic(0);
				$iti->setSegment($segment);
				
				$em->persist($trace);
				$em->persist($iti);
				$em->persist($segment);
				$em->flush();
					
					
					//if(!empty($trace->getId()))
					//{
						$return_message = " Le fichier a correctement été importé";	
					//}	

					$response = new Response(json_encode(array("result" => $return_message,"code" => 200)));					
				}
			}
			else
			{
				$response = new Response(json_encode(array("result" => $return_message,"code" => 500)));
				$response->setStatusCode(500);
			}
		}
		else
		{
			$response = new Response();
		}
		
		return $response;
	}
	
		//fonction de chargement des tronçons dans une zone précise
	public function loadSegmentAction(Request $request)
	{
		if ($request->isXMLHttpRequest()) 
		{
			//calcul de la section à charger
		
			//requete pour charger les données en fonction des coordonnés passé en paramètre.
			
			//coordonées des deux pogs 
			
			
			//la requete renvoit seulement les POG/POP afin d'alleger le transfert de données
			$response = new Response(json_encode(array("result" => $return_message,"code" => 500)));
			$response = new Response();
		}
		else 
		{
			$response = new Response();
		}
	}
	
	/*public function saveSegmentAction(Request $request)
	{
		//on récupère les paramètres dans le request 

		//on sauvegarde les données dans la base
		
		//on renvoit un message de validation
		
		//sauvegarde dans la controller itinéraire
		
	}
	*/
	//prévoir une deuxieme fonction pour charger un tronçon précis et d'obtenir tout ses points (edition)
	
	//fonction de modification d'un segment 
	//séparation d'un segment en deux autre tronçon suivant le points d'insertion.a

    public function perpendicularDistance($ptX, $ptY, $l1x, $l1y, $l2x, $l2y)
    {
        $result = 0;
        if ($l2x == $l1x)
        {
            //vertical lines - treat this case specially to avoid divide by zero
            $result = abs($ptX - $l2x);
        }
        else
        {
            $slope = (($l2y-$l1y) / ($l2x-$l1x));
            $passThroughY = (0-$l1x)*$slope + $l1y;
            $result = (abs(($slope * $ptX) - $ptY + $passThroughY)) / (sqrt($slope*$slope + 1));
        }
        return $result;
    }

    public function RamerDouglasPeucker($pointList, $epsilon)
    {
        // Find the point with the maximum distance
        $dmax = 0;
        $index = 0;
        $totalPoints = count($pointList);
        for ($i = 1; $i < ($totalPoints - 1); $i++)
        {
            $d = $this->perpendicularDistance($pointList[$i]->getX(), $pointList[$i]->getY(),
                                       $pointList[0]->getX(), $pointList[0]->getY(),
                                       $pointList[$totalPoints-1]->getX(), $pointList[$totalPoints-1]->getY());
                   
            if ($d > $dmax)
            {
                $index = $i;
                $dmax = $d;
            }
        }

        $resultList = array();
        
        // If max distance is greater than epsilon, recursively simplify
        if ($dmax >= $epsilon)
        {
            // Recursive call
            $recResults1 = $this->RamerDouglasPeucker(array_slice($pointList, 0, $index + 1), $epsilon);
            $recResults2 = $this->RamerDouglasPeucker(array_slice($pointList, $index, $totalPoints - $index), $epsilon);

            // Build the result list
            $resultList = array_merge(array_slice($recResults1, 0, count($recResults1) - 1),
                                      array_slice($recResults2, 0, count($recResults2)));
        }
        else
        {
            $resultList = array($pointList[0], $pointList[$totalPoints-1]);
        }
        // Return the result
        return $resultList;
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



/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

