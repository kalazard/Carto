<?php


namespace Site\CartoBundle\Services;

use Site\CartoBundle\Entity\Itineraire;
use Site\CartoBundle\Entity\Itinerairenote;
use Site\CartoBundle\Entity\Note;
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

    public function deletefavori($iditi, $iduser)
    {
        $repositoryIti=$this->entityManager->getRepository("SiteCartoBundle:Itineraire");
        $iti = $repositoryIti->findOneBy(array('id' => $iditi));

        $repositoryUser=$this->entityManager->getRepository("SiteCartoBundle:Utilisateur");
        $user = $repositoryUser->findOneBy(array('id' => $iduser));

        $iti->removeUtilisateurid($user);
        $user->removeItineraireid($iti);

        $this->entityManager->flush();

        return json_encode(array("result" => "success","code" => 200));
    }

    public function addfavori($iditi, $iduser)
    {
        $repositoryIti=$this->entityManager->getRepository("SiteCartoBundle:Itineraire");
        $iti = $repositoryIti->findOneBy(array('id' => $iditi));

        $repositoryUser=$this->entityManager->getRepository("SiteCartoBundle:Utilisateur");
        $user = $repositoryUser->findOneBy(array('id' => $iduser));

        $iti->addUtilisateurid($user);
        $user->addItineraireid($iti);

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
    
    public function getNotes($listeIti, $idUser)
    {         
        $repository = $this->entityManager->getRepository('SiteCartoBundle:Utilisateur');
        $user = $repository->findOneBy(array('id' => $idUser));
        $repository = $this->entityManager->getRepository('SiteCartoBundle:Itineraire');
        $listeItiObj = array();
        
        foreach($listeIti->list as $itiTmp)
        {
            $listeItiObj[] = $iti = $repository->findOneBy(array('id' => $itiTmp->id));          
        }
        
        $repository = $this->entityManager->getRepository('SiteCartoBundle:Note');
        $notes = $repository->findAll();
        $repository = $this->entityManager->getRepository('SiteCartoBundle:Itinerairenote');
        $em = $this->entityManager;
        
        foreach($listeItiObj as $itiTmp)
        {
            $req = "SELECT (no.valeur) ";
            $req .= "FROM SiteCartoBundle:Itinerairenote ino, SiteCartoBundle:Note no ";
            $req.= "WHERE ino.itineraireidnote = ".$itiTmp->getId();
            $req.= " AND ino.noteid = no.id";
            $query = $em->createQuery($req);
            $result = $query->getScalarResult();
            $allItiNotes[] = array_map('current', $result);
            
            $req = "SELECT (no.valeur) ";
            $req .= "FROM SiteCartoBundle:Itinerairenote ino, SiteCartoBundle:Note no ";
            $req.= "WHERE ino.itineraireidnote = ".$itiTmp->getId();
            $req.= " AND ino.utilisateuridnote = ".$idUser;
            $req.= " AND ino.noteid = no.id";
            $query = $em->createQuery($req);
            $userNote[] = $query->getOneOrNullResult()[1];
        }
        
        return json_encode(array("userNotes" => $userNote, "allNotes" =>$allItiNotes));
    }
    
    public function getAllNotes()
    {  
        $repository = $this->entityManager->getRepository('SiteCartoBundle:Note');
        return json_encode($repository->findAll());
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
    
    public function noterItineraire($idUser, $idItineraire, $note)
    {
        $repository = $this->entityManager->getRepository('SiteCartoBundle:Note');
        $objNote = $repository->findOneBy(array('valeur' => $note));
        
        $repository = $this->entityManager->getRepository('SiteCartoBundle:Itinerairenote');
        $res = $repository->findOneBy(array('utilisateuridnote' => $idUser, 'itineraireidnote' => $idItineraire));
        
        if(isset($res) && $res != null)
        {
            $res->setNoteid($objNote);
        }
        else
        {
            $maNote = new Itinerairenote();
            $repository = $this->entityManager->getRepository('SiteCartoBundle:Utilisateur');
            $maNote->setUtilisateuridnote($repository->findOneBy(array('id' => $idUser)));
            $repository = $this->entityManager->getRepository('SiteCartoBundle:Itineraire');
            $maNote->setItineraireidnote($repository->findOneBy(array('id' => $idItineraire)));
            $maNote->setNoteid($objNote);
            $this->entityManager->persist($maNote); 
        }
        
        $this->entityManager->flush();
    }
}
 
