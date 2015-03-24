<?php

namespace Site\TrailBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Site\TrailBundle\Entity\TypeLieu;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Validator\Constraints\DateTime;

class TypeLieuController extends Controller
{
	//Récupération de la liste des lieux
    public function getAllLieuxAction(Request $request) {
      if ($request->isXMLHttpRequest()) 
      {
        $manager=$this->getDoctrine()->getManager();
        $repository = $manager->getRepository("SiteTrailBundle:TypeLieu");
        $lieux = $repository->findAll();
 
        $response = new Response(json_encode($lieux));
                    $response->headers->set('Content-Type', 'application/json');
                    return $response;
      }

      return new Response('This is not ajax!', 400);
    }
}