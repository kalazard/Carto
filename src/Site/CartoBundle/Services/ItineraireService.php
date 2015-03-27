<?php


namespace Site\CartoBundle\Services;

use Site\CartoBundle\Entity\Itineraire;

class ItineraireService
{
    protected $entityManager;
    
    public function __construct(\Doctrine\ORM\EntityManager $em)
    {
        $this->entityManager = $em;
    }
    
    public function zizitede()
    {
        $itineraire = $this->entityManager->getRepository("SiteCartoBundle:Itineraire")->findAll();
        return json_encode(array("zizi" => $itineraire));
    }
}
 