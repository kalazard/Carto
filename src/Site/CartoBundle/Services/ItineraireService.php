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
    
    public function itilist()
    {
        $itineraire = $this->entityManager->getRepository("SiteCartoBundle:Itineraire")->findAll();
        return json_encode(array("list" => $itineraire));
    }

    public function search($nom,$typechemin,$denivelep,$denivelen,$difficulte)
    {
        //var_dump($search);
        $params = array();
        $listItiniraire = array();
            //on utilise un findBy pour r�cup�rer la liste des utilisateurs on fonction des donn�es de l'utilisateur
                $repository = $this->entityManager->getRepository('SiteCartoBundle:Itineraire');

                if($nom != null){$params["nom"] = $nom;}
                if($typechemin != null){$params["typechemin"] = $typechemin;}
                if($denivelep != null){$params["deniveleplus"] = $denivelep;}
                if($denivelen != null){$params["denivelemoins"] = $denivelen;}
                if($difficulte != null){$params["difficulte"] = $difficulte;}


                $listItiniraire = $repository->findBy($params);
                
                return json_encode(array("searchResults" => $listItiniraire));
        return json_encode(array());
    }

    public function save()
    {
        $itineraire = $this->entityManager->getRepository("SiteCartoBundle:Itineraire")->findAll();
        return json_encode(array("list" => $itineraire));
    }

    public function difficultelist()
    {
        $diff = $this->entityManager->getRepository("SiteCartoBundle:Difficulteparcours")->findAll();
        return json_encode(array("difficultes" => $diff));
    }
}
 
