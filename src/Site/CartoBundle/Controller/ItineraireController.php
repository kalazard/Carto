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

    public function saveAction(Request $request)
    {
        if ($request->isXMLHttpRequest()) 
        {
            $manager = $this->getDoctrine()->getManager();
            $repositoryDiff=$manager->getRepository("SiteCartoBundle:Difficulteparcours");
            $repositoryUser=$manager->getRepository("SiteCartoBundle:Utilisateur");

            $trace = new Trace();
            $trace->setPath("test");
            $diff = $repositoryDiff->find($request->request->get("difficulte",""));
            $user = $repositoryUser->find($request->request->get("auteur",""));

            $route = new Itineraire();
            $route->setDatecreation(new \DateTime('now'));
            $route->setLongueur($request->request->get("longueur",""));
            $route->setDeniveleplus($request->request->get("denivelep",""));
            $route->setDenivelemoins($request->request->get("denivelen",""));
            $route->setTrace($trace);
            $route->setNom($request->request->get("nom",""));
            $route->setNumero($request->request->get("numero",""));
            $route->setTypechemin($request->request->get("typechemin",""));
            $route->setDescription($request->request->get("description",""));
            $route->setDifficulte($diff);
            $route->setAuteur($user);
            $route->setStatus($request->request->get("status",""));

            $manager->persist($route);
            $manager->persist($trace);
            $manager->flush();
            $response = new Response(json_encode(array("result" => "success","code" => 200)));
            $response->headers->set('Content-Type', 'application/json');
            return $response;            
        }
        return new Response('This is not ajax!', 400);
    }
}
