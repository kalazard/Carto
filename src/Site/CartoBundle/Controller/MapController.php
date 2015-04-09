<?php

namespace Site\CartoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Site\CartoBundle\Entity\Poi;
use Site\CartoBundle\Entity\Coordonnees;
use Site\CartoBundle\Entity\TypeLieu;
use Site\CartoBundle\Entity\Icone;
use Site\CartoBundle\Entity\Itiniraire;
use Site\CartoBundle\Entity\Gpx;
use Site\CartoBundle\Entity\Trace;
use Site\CartoBundle\Entity\DifficulteParcours;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Validator\Constraints\DateTime;


class MapController extends Controller
{
    public function indexAction()
    {       
        $content = $this->get("templating")->render("SiteCartoBundle:Map:index.html.twig");        
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
			
			$response = new Response(json_encode(array("result" => $return_message,"code" => $code)));
			
			if($upload != 0)
			{
				if(move_uploaded_file($_FILES['upl']['tmp_name'], $target_file))
				{
					//si l'upload a réussi, on sauvegarde le chemin de destination dans la base de données (table Trace)
					$trace = new Trace();
					$trace->setPath($target_file);
					
					$em = $this->getDoctrine()->getManager();
					$em->persist($trace);
					$em->flush();
					
					//if(!empty($trace->getId()))
					//{
						$return_message = " Le fichier a correctement été importé";	
					//}				
				}
			}
			else
			{
				$response->setStatusCode(500);
			}
		}
		else
		{
			$response = new Response();
		}
		
		return $response;
	}
}

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

