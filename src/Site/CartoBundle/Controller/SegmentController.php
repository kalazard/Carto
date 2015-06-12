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
            var_dump($pointArray);
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
