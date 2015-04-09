<?php

namespace Site\CartoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Site\CartoBundle\Entity\Icone;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Validator\Constraints\DateTime;

class IconeController extends Controller
{
	//Récupération de la liste des icones
    public function getAllIconesAction(Request $request) {
      if ($request->isXMLHttpRequest()) 
      {
        $manager=$this->getDoctrine()->getManager();
        $repository = $manager->getRepository("SiteCartoBundle:Icone");
        $icones = $repository->findAll();
 
        $response = new Response(json_encode($icones));
                    $response->headers->set('Content-Type', 'application/json');
                    return $response;
      }

      return new Response('This is not ajax!', 400);
    }
}