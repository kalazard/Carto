<?php

namespace Site\CartoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class DefaultController extends Controller
{
    public function indexAction()
    {
		return $this->render('SiteCartoBundle:Accueil:index.html.twig');
    }
	
	public function homeAction($name)
    {
		return $this->render('SiteCartoBundle:Default:index.html.twig', array('name' => $name));
    }
}
