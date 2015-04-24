<?php

namespace Site\CartoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Site\CartoBundle\Entity\Utilisateur;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
//use Symfony\Component\HttpFoundation\JsonResponse;
//use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
//use Symfony\Component\Validator\Constraints\DateTime;


class UserController extends Controller
{
    public function indexAction()
    {   
		$result = array();
		$user = $this->getUser();
		
		$id_courant = $user->getId();
		//on charge les infos de l'utilisateur courant.
		$manager = $this->getDoctrine()->getManager();
		$data = $manager->getRepository('SiteCartoBundle:Utilisateur')->findOneBy(array('id'=>$id_courant));	

		
		$result['id'] = $id_courant;
		//$result['prenom'] = $data->getPrenom();
		//$result['nom'] = $data->getNom();
		$result['email'] = $data->getEmail();
		//$result['tel'] = $data->getTelephone();
		//$result['date'] = $data->getDatenaissance();
		//$result['licence'] = $data->getLicence();		
		
        $content = $this->get("templating")->render("SiteCartoBundle:Utilisateur:index.html.twig",$result);        
        return new Response($content);
    }
}

