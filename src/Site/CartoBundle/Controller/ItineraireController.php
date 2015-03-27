<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
namespace Site\CartoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;

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
}
