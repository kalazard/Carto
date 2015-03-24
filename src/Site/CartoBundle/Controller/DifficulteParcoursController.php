<?php

namespace Site\TrailBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Site\TrailBundle\Entity\DifficulteParcours;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Validator\Constraints\DateTime;


class DifficulteParcoursController extends Controller
{
    public function indexAction()
    {
        return new Response('Nothing to see :D', 400);
    }

    public function getDifficultesAction(Request $request)
    {
      if ($request->isXMLHttpRequest()) 
      {
        $manager=$this->getDoctrine()->getManager();
        $repository = $manager->getRepository("SiteTrailBundle:DifficulteParcours");
        $diffs = $repository->findAll();
        //return new JsonResponse(array('data' => 'Itinéraire Crée'),200);
        //return 
        $response = new Response(json_encode($diffs));
                    $response->headers->set('Content-Type', 'application/json');
                    return $response;
        //return new Response($diffs);
      }

      return new Response('This is not ajax!', 400);
    }

}

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

