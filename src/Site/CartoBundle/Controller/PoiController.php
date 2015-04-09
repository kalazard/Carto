<?php

namespace Site\CartoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Site\CartoBundle\Entity\Poi;
use Site\CartoBundle\Entity\Coordonnees;
use Site\CartoBundle\Entity\Typelieu;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Validator\Constraints\DateTime;

class PoiController extends Controller 
{
    public function getPoiAction(Request $request)
    {
        if ($request->isXMLHttpRequest()) 
        {
            $listPoi = array();
                
                if(!empty($search))
                {
                    $repository = $this
                        ->getDoctrine()
                        ->getManager()
                        ->getRepository('SiteCartoBundle:Poi')
                    ;

                    $listPoi['titre'] = $repository->findBy(array('titre' => $poi));
                    
                    $listPoi['description'] = $repository->findBy(array('description' => $poi));

                }

                $return = array('success' => true, 'serverError' => false, 'resultats' => $listPoi);
                $response = new Response(json_encode($return));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
        }
    
          return new Response('This is not ajax!', 400);
    }

    //Récupération de la liste des pois
    public function getAllPoisAction(Request $request) {
      /*if ($request->isXMLHttpRequest()) 
      {*/
        $manager=$this->getDoctrine()->getManager();
        $repository = $manager->getRepository("SiteCartoBundle:Poi");
        $pois = $repository->findAll();
 
        $response = new Response(json_encode($pois));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
 /*     }

      return new Response('This is not ajax!', 400);*/
    }

    public function savePoiAction(Request $request)
    {
        if ($request->isXMLHttpRequest()) 
        {
            $manager=$this->getDoctrine()->getManager();

            $repositoryTypelieu=$manager->getRepository("SiteCartoBundle:Typelieu");
            
            $typelieu = $repositoryTypelieu->find($request->request->get("idLieu",1));

            $coord = new Coordonnees();
            $coord->setLongitude($request->request->get("lng",1));
            $coord->setLatitude($request->request->get("lat",1));
            $coord->setAltitude($request->request->get("alt",1));

            $poi = new Poi();
            $poi->setTitre($request->request->get("titre","montitre"));
            $poi->setDescription($request->request->get("description","madescription"));
            $poi->setCoordonnees($coord);
            $poi->setTypelieu($typelieu);

            $manager->persist($coord);
            $manager->persist($typelieu);
            $manager->persist($poi);
            $manager->flush();
            return new JsonResponse(array('data' => 'Poi Crée'),200);
        }
      
        return new Response('This is not ajax!', 400);
    }
}