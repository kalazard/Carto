<?php


namespace Site\CartoBundle\Services;

use Site\CartoBundle\Entity\Itineraire;
use \CrEOF\Spatial\PHP\Types\Geography\Point as MySQLPoint;

class ItineraireService
{
    protected $entityManager;
    
    public function __construct(\Doctrine\ORM\EntityManager $em)
    {
        $this->entityManager = $em;
    }
    
    public function itilist()
    {
        $itineraire = $this->entityManager->getRepository("SiteCartoBundle:Itineraire")->findBy(array('public' => 1));
        return json_encode(array("list" => $itineraire));
    }

    public function search($nom,$typechemin,$longueur,$datecrea,$difficulte,$status)
    {
        $params = array();
        $listItiniraire = array();

        $repository = $this->entityManager->getRepository('SiteCartoBundle:Itineraire');
        if($nom != null || $nom != "")
        {
            $query = $repository->createQueryBuilder('i')->where('i.nom LIKE :nom')->setParameter('nom', '%'.$nom.'%');
            if($typechemin != null){$query->andWhere('i.typechemin = :typechemin')->setParameter('typechemin', $typechemin);}
            if($longueur != null){$query->andWhere('i.longueur = :longueur')->setParameter('longueur', $longueur);}
            if($datecrea != null){$query->andWhere('i.datecreation = :datecrea')->setParameter('datecrea', new \Datetime($datecrea));}
            if($difficulte != null){$query->andWhere('i.difficulte = :difficulte')->setParameter('difficulte', $difficulte);}
            if($status != null){$query->andWhere('i.status = :status')->setParameter('status', $status);}
            $query->andWhere('i.public=1');
            $listItiniraire = $query->getQuery()->getResult();
            return json_encode(array("searchResults" => $listItiniraire));
        }
        else
        {
            if($typechemin != null){$params["typechemin"] = $typechemin;}
            if($longueur != null){$params["longueur"] = $longueur;}
            if($datecrea != null){$params["datecreation"] = $datecrea;}
            if($difficulte != null){$params["difficulte"] = $difficulte;}
            if($status != null){$params["status"] = $status;}
            $params["public"] = 1;
            $listItiniraire = $repository->findBy($params);
            return json_encode(array("searchResults" => $listItiniraire));
        }
    }

    /*public function save($nom,$typechemin,$denivelep,$denivelen,$difficulte,$longueur,$description,$numero,$auteur,$status,$points,$public)
    {
        $repositoryDiff=$this->entityManager->getRepository("SiteCartoBundle:Difficulteparcours");
        $repositoryUser=$this->entityManager->getRepository("SiteCartoBundle:Utilisateur");
        $repositoryStat=$this->entityManager->getRepository("SiteCartoBundle:Status");
        $repositoryType=$this->entityManager->getRepository("SiteCartoBundle:Typechemin");
        $repositorySegment=$this->entityManager->getRepository("SiteCartoBundle:Segment");

        $trace = new Trace();
        $filename = uniqid('trace_', true) . '.csv';
        $trace->setPath($filename);
        $diff = $repositoryDiff->find($difficulte);
        $stat = $repositoryStat->find($status);
        $type = $repositoryType->find($typechemin);

        $segment = new Segment();
        $pointArray = json_decode($points,true);
        $lsArray = [];
        $elevationString = "";
        $i = 1;
        foreach($pointArray as $pt)
        {
            array_push($lsArray,new MySQLPoint()->setX($pt["lng"])->setY($pt["lat"]));
            $elevationString = $elevationString . $pt["elevation"];
            if(++$i != count($pointArray))
            {
                $elevationString = $elevationString . ";";
            }
        }
        $segment->setTrace(new LineString()->setPoints($lsArray));
        $segment->setElevation($elevationString);
        $segment->setSens(0);
        $segment->setPog1($lsArray[0]);
        $segment->setPog2(end($lsArray));

        $route = new Itineraire();
        $route->setDate(new \DateTime('now'));
        $route->setLongueur($longueur);
        $route->setDeniveleplus($denivelep);
        $route->setDenivelemoins($denivelen);
        $route->setTrace($gpx);
        $route->setNom($nom);
        $route->setNumero($numero);
        $route->setTypechemin($type);
        $route->setDescription($description);
        $route->setDifficulte($diff);
        $route->setAuteur($user);
        $route->setStatus($stat);
        $route->setPublic($public);
        $route->setSegment($segment);

        $json_obj = json_decode($points);
        $fp = fopen('../../Traces/'.$filename, 'w');
        foreach ($json_obj as $fields) {
            fputcsv($fp, $fields);
        }
        fclose($fp);

        $this->entityManager->persist($route);
        $this->entityManager->persist($trace);
        $this->entityManager->persist($segment);
        $this->entityManager->flush();

        return json_encode(array("result" => "success","code" => 200));
    }*/

    public function update($nom,$typechemin,$difficulte,$description,$numero,$auteur,$status,$public,$id)
    {
        $repositoryDiff=$this->entityManager->getRepository("SiteCartoBundle:Difficulteparcours");
        $repositoryStat=$this->entityManager->getRepository("SiteCartoBundle:Status");
        $repositoryType=$this->entityManager->getRepository("SiteCartoBundle:Typechemin");
        $repositoryIti=$this->entityManager->getRepository("SiteCartoBundle:Itineraire");

        $diff = $repositoryDiff->find($difficulte);
        $stat = $repositoryStat->find($status);
        $type = $repositoryType->find($typechemin);

        $route = $repositoryIti->findBy(array('id' => $id));
        $route[0]->setNom($nom);
        $route[0]->setNumero($numero);
        $route[0]->setTypechemin($type);
        $route[0]->setDescription($description);
        $route[0]->setDifficulte($diff);
        $route[0]->setStatus($stat);
        $route[0]->setPublic($public);

        $this->entityManager->persist($route[0]);
        $this->entityManager->flush();

        return json_encode(array("result" => "success","code" => 200));
    }

    public function delete($id)
    {
        $repositoryIti=$this->entityManager->getRepository("SiteCartoBundle:Itineraire");

        $route = $repositoryIti->findBy(array('id' => $id));

        $this->entityManager->remove($route[0]);
        $this->entityManager->flush();

        return json_encode(array("result" => "success","code" => 200));
    }

    public function getById($id)
    {
        $repository = $this->entityManager->getRepository('SiteCartoBundle:Itineraire');
        return json_encode(array("searchResults" => $repository->find($id)));
        
    }

    public function getByUser($user)
    {
        $repository = $this->entityManager->getRepository('SiteCartoBundle:Itineraire');
        return json_encode(array("list" => $repository->findBy(array('auteur' => $user))));
        
    }
    
    public function getNotesIti($listeIti, $idUser)
    {
        $repository = $this->entityManager->getRepository('SiteCartoBundle:Utilisateur');
        $user = $repository->findBy(array('id' => $idUser));
        
        $notesUtilisateur = array();
        $notesAll = array();
        $repository = $this->entityManager->getRepository('SiteCartoBundle:Note');
        $testIti = $repository->findBy(array('itineraire' => $iti, 'utilisateur' => $user));
        
        foreach($listeIti->list as $iti)
        {
            //$notesUtilisateur[] = $repository->findBy(array('itineraire' => $iti, 'utilisateur' => $user));
            //$notesAll[] = $repository->findBy(array('itineraire' => $iti));
        }
        /*
        return json_encode($notes);*/
        return json_encode($testIti);
    }

    public function difficultelist()
    {
        $diff = $this->entityManager->getRepository("SiteCartoBundle:Difficulteparcours")->findAll();
        return json_encode(array("difficultes" => $diff));
    }

    public function statuslist()
    {
        $stat = $this->entityManager->getRepository("SiteCartoBundle:Status")->findAll();
        return json_encode(array("status" => $stat));
    }

    public function typecheminlist()
    {
        $stat = $this->entityManager->getRepository("SiteCartoBundle:Typechemin")->findAll();
        return json_encode(array("typechemin" => $stat));
    }
}
 
