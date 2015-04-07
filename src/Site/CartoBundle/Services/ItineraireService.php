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

    public function search($nom,$typechemin,$denivelep,$denivelen,$datecrea,$difficulte)
    {
        $params = array();
        $listItiniraire = array();

        $repository = $this->entityManager->getRepository('SiteCartoBundle:Itineraire');
        if($nom != null || $nom != "")
        {
            $query = $repository->createQueryBuilder('i')->where('i.nom LIKE :nom')->setParameter('nom', '%'.$nom.'%');
            if($typechemin != null){$repository->andWhere('i.typechemin LIKE :typechemin')->setParameter('typechemin', '%'.$typechemin.'%');}
            if($denivelep != null){$repository->andWhere('i.deniveleplus = :denivelep')->setParameter('denivelep', $denivelep);}
            if($denivelen != null){$repository->andWhere('i.denivelemoins = :denivelen')->setParameter('denivelen', $denivelen);}
            if($datecrea != null){$repository->andWhere('i.datecreation = :datecrea')->setParameter('datecrea', new \Datetime($datecrea));}
            if($difficulte != null){$repository->andWhere('i.difficulte = :difficulte')->setParameter('difficulte', $difficulte);}
            $listItiniraire = $query->getQuery()->getResult();
            return json_encode(array("searchResults" => $listItiniraire));
        }
        else
        {
            if($typechemin != null){$params["typechemin"] = $typechemin;}
            if($denivelep != null){$params["deniveleplus"] = $denivelep;}
            if($denivelen != null){$params["denivelemoins"] = $denivelen;}
            if($denivelen != null){$params["datecreation"] = $datecrea;}
            if($difficulte != null){$params["difficulte"] = $difficulte;}
            $listItiniraire = $repository->findBy($params);
            return json_encode(array("searchResults" => $listItiniraire));
        }
    }

    public function save($nom,$typechemin,$denivelep,$denivelen,$difficulte,$longueur,$description,$numero,$auteur,$status,$points)
    {
        $repositoryDiff=$this->entityManager->getRepository("SiteCartoBundle:Difficulteparcours");
        $repositoryUser=$this->entityManager->getRepository("SiteCartoBundle:Utilisateur");

        $trace = new Trace();
        $filename = uniqid('trace_', true) . '.csv';
        $trace->setPath($filename);
        $diff = $repositoryDiff->find($difficulte);
        $user = $repositoryUser->find($auteur);

        $route = new Itineraire();
        $route->setDate(new \DateTime('now'));
        $route->setLongueur($longueur);
        $route->setDeniveleplus($denivelep);
        $route->setDenivelemoins($denivelen);
        $route->setTrace($gpx);
        $route->setNom($nom);
        $route->setNumero($numero);
        $route->setTypechemin($typechemin);
        $route->setDescription($description);
        $route->setDifficulte($diff);
        $route->setAuteur($user);
        $route->setStatus($status);

        $json_obj = json_decode($points);
        $fp = fopen('../../Traces/'.$filename, 'w');
        foreach ($json_obj as $fields) {
            fputcsv($fp, $fields);
        }
        fclose($fp);

        $this->entityManager->persist($route);
        $this->entityManager->persist($trace);
        $this->entityManager->flush();

        return json_encode(array("result" => "success","code" => 200));
    }

    public function getById($id)
    {
        $repository = $this->entityManager->getRepository('SiteCartoBundle:Itineraire');
        return json_encode(array("searchResults" => $repository->find($id)));
        
    }

    public function difficultelist()
    {
        $diff = $this->entityManager->getRepository("SiteCartoBundle:Difficulteparcours")->findAll();
        return json_encode(array("difficultes" => $diff));
    }
}
 
