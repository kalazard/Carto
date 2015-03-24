<?php

namespace Site\TrailBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Site\TrailBundle\Entity\Poi;

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
                        ->getRepository('SiteTrailBundle:Poi')
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
}