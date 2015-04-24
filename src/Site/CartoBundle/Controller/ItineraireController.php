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

    public function getStatusAction(Request $request)
    {
      if ($request->isXMLHttpRequest()) 
      {
        $manager = $this->getDoctrine()->getManager();
        $repository = $manager->getRepository("SiteCartoBundle:Status");
        $stats = $repository->findAll();
        $response = new Response(json_encode($stats));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
      }

      return new Response('This is not ajax!', 400);
    }

    public function getTypecheminAction(Request $request)
    {
      if ($request->isXMLHttpRequest()) 
      {
        $manager = $this->getDoctrine()->getManager();
        $repository = $manager->getRepository("SiteCartoBundle:Typechemin");
        $typechemin = $repository->findAll();
        $response = new Response(json_encode($typechemin));
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
            $repositoryStatus=$manager->getRepository("SiteCartoBundle:Status");
            $repositoryTypechemin=$manager->getRepository("SiteCartoBundle:Typechemin");

            $trace = new Trace();
            $filename = uniqid('trace_', true) . '.csv';
            $trace->setPath($filename);
            $diff = $repositoryDiff->find($request->request->get("difficulte",""));
            $user = $repositoryUser->find($request->request->get("auteur",""));
            $status = $repositoryStatus->find($request->request->get("status",""));
            $typechemin = $repositoryTypechemin->find($request->request->get("typechemin",""));

            $route = new Itineraire();
            $route->setDatecreation(new \DateTime('now'));
            $route->setLongueur($request->request->get("longueur",""));
            $route->setDeniveleplus($request->request->get("denivelep",""));
            $route->setDenivelemoins($request->request->get("denivelen",""));
            $route->setTrace($trace);
            $route->setNom($request->request->get("nom",""));
            $route->setNumero($request->request->get("numero",""));
            $route->setTypechemin($typechemin);
            $route->setDescription($request->request->get("description",""));
            $route->setDifficulte($diff);
            $route->setAuteur($user);
            $route->setStatus($status);

            $json_obj = json_decode($request->request->get("points",""),true);
            $fp = fopen('../../Traces/'.$filename, 'w');
            $firstLineKeys = false;
            foreach ($json_obj as $line)
            {
                if (empty($firstLineKeys))
                {
                    $firstLineKeys = array_keys($line);
                    fputcsv($fp, $firstLineKeys);
                    $firstLineKeys = array_flip($firstLineKeys);
                }
                fputcsv($fp, array_merge($firstLineKeys, $line));
            }
            fclose($fp);

            $manager->persist($route);
            $manager->persist($trace);
            $manager->flush();
            $response = new Response(json_encode(array("result" => "success","code" => 200)));
            $response->headers->set('Content-Type', 'application/json');
            return $response;            
        }
        return new Response('This is not ajax!', 400);
    }

    public function loadAction($id)
    {
		$repository = $this->getDoctrine()->getManager()->getRepository('SiteCartoBundle:Itineraire');
		$iti = $repository->find($id);
		$content = $this->get("templating")->render("SiteCartoBundle:Map:load.html.twig",array("itineraire" => $iti,"jsonObject" => json_encode($iti)));
		return new Response($content);            
    }
	
	public function rechercheAction(Request $request)
	{
		//soit on affiche la page en chargeant toutes les données, soit on charge les données selon les paramètres 
	
		//on récupère la liste des paramètres choisi et on charge tout les itinéraires associés.
		$result = array();
		$search = array();		
		$search["nom"] = $request->request->get("nom");
		$search["typechemin"] = $request->request->get("typechemin");
		$search["longueur"] = $request->request->get("longueur");
		$search["datecrea"] = $request->request->get("datecrea");
		$search["difficulte"] = $request->request->get("difficulte");
		$search["status"] = $request->request->get("status");
		
		
		return $this->render('SiteCartoBundle:Itineraire:index.html.twig');
	}
}
