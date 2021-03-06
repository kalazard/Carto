<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Site\CartoBundle\Controller;

use Site\CartoBundle\Entity\Utilisateur;
use Site\CartoBundle\Entity\Itineraire;
use Site\CartoBundle\Entity\Itinerairenote;
use Site\CartoBundle\Entity\Note;
use Site\CartoBundle\Entity\Role;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use DateTime;

class MemberController extends Controller {

    /**
     * Fonction de renvoyant les informations du profil d'un membre
     *
     * Cette méthode est appelée en ajax et ne requiert aucun paramètre : 
     * 
     * @return string 
     *
     * JSON permettant de définir si le mot de passe a été changé
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *     "id": id du membre,
     *     "prenom": prenom du membre,
     *     "nom": nom du membre,
     *     "email": email du membre,
     *     "tel": telephone du membre,
     *     "date": date de naissance du membre,
     *     "itiMoyenne": note Moyenne des itinéraires créés par le membre,
     *     "itineraires": itineraires créés par le membre,
     *     "favoris": itinéraires favoris du membre
     * }
     * </code>
     * 
     * Example en cas d'erreur dans la création :
     * 
     * <code>
     * {
     *     "success": false,
     *     "serverError": false,
     *     "message": "Message"
     * }
     * </code>
     * 
     * Example en cas d'erreur du serveur :
     * 
     * <code>
     * {
     *     "success": false,
     *     "serverError": true,
     *     "message": "Message"
     * }
     * </code>
     * 
     * 
     */
	public function profilAction()
	{
		$result = array();
		$user = $this->getUser();
		
		//on récupère les infos de l'utilisateur courant (et seulement courant)

		$id_courant = $user->getId();
		
		//si le membre est connecté
		if(isset($id_courant))
		{
			//on charge les infos de l'utilisateur courant.
			$manager = $this->getDoctrine()->getManager();
			$data = $manager->getRepository('SiteCartoBundle:Utilisateur')->findOneBy(array('id'=>$id_courant));	

			$result['id'] = $id_courant;
			$result['prenom'] = $data->getPrenom();
			$result['nom'] = $data->getNom();
			$result['email'] = $data->getEmail();
			$result['tel'] = $data->getTelephone();
			$result['date'] = $data->getDatenaissance();
			
			//on recherche tout les utilisateurs 
			$data = $manager->getRepository('SiteCartoBundle:Itineraire')->findBy(array('auteur'=>$id_courant));
                        $allItiNotes = array();                        
                        $userNote = array();
                        
                        foreach($data as $itiTmp)
                        {
                            $req = "SELECT (no.valeur) ";
                            $req .= "FROM SiteCartoBundle:Itinerairenote ino, SiteCartoBundle:Note no ";
                            $req.= "WHERE ino.itineraireidnote = ".$itiTmp->getId();
                            $req.= " AND ino.noteid = no.id";
                            $query = $manager->createQuery($req);
                            $res = $query->getScalarResult();
                            $allItiNotes[] = array_map('current', $res);

                            $req = "SELECT (no.valeur) ";
                            $req .= "FROM SiteCartoBundle:Itinerairenote ino, SiteCartoBundle:Note no ";
                            $req.= "WHERE ino.itineraireidnote = ".$itiTmp->getId();
                            $req.= " AND ino.utilisateuridnote = ".$id_courant;
                            $req.= " AND ino.noteid = no.id";
                            $query = $manager->createQuery($req);
                            $userNote[] = $query->getOneOrNullResult()[1];
                        }
                        
                        $result['userNotes'] =  $userNote;
                
                        foreach($allItiNotes as $calcMoy)
                        {
                            if(sizeof($calcMoy) > 0)
                            {
                                $result['itiMoyenne'][] = array_sum($calcMoy) / count($calcMoy);
                            }
                            else
                            {
                                $result['itiMoyenne'][] = -1;
                            }

                        }
                        
                       
                        
			//$result['itineraires '] = $data;
			//récupération des résultats 
			
			$compteur = 0;
			foreach($data as $var)
			{				
				$publique = "ouvert";
				//visiblité pas encore fait 
				
				//var_dump($var);
				
				//lien itinéraire (nom et id), type de chemin, difficulté, date de création, longueur du parcours, statut, visiblité, supprimer
				$result['itineraires'][$compteur]['id'] = $var->getId();
				$result['itineraires'][$compteur]['nom'] = $var->getNom();
				$result['itineraires'][$compteur]['type'] = $var->getTypeChemin()->getLabel();
				$result['itineraires'][$compteur]['difficulte'] = $var->getDifficulte()->getLabel();
				$result['itineraires'][$compteur]['date'] = $var->getDateCreation();
				$result['itineraires'][$compteur]['longueur'] = $var->getLongueur();
				$result['itineraires'][$compteur]['status'] = $var->getStatus()->getLabel();
				
				if(strcmp($var->getPublic(),"0") != 0)
				{
					$publique = "privé";
				}
				
				$result['itineraires'][$compteur]['public'] = $publique;
				$compteur++;
			}
			
			//var_dump($data[0]->getDateCreation());
			
			/*
			$iti = $this->forward('SiteCartoBundle:Itiniraire:getByAuteur', array('auteur'  => $id_courant));		
			$result['itineraires'] = json_decode($iti->getContent());
			*/		
			
			//retour
			
			$data = $manager->getRepository('SiteCartoBundle:Utilisateur')->findOneBy(array('id'=>$id_courant));
			$result['favoris'] = $data->getItineraireid();
                        $itiFavList = $result['favoris']->getValues();
                        
                        $modif1 = json_encode($itiFavList);
                        $modif2 = json_decode($modif1);
                        
                        foreach($modif2 as $iF)
                        {
                            $resItiFav->list[] = $iF;
                        }
                        
                        $itineraireService = $this->container->get('itineraire_service');
                        
                        if(isset($resItiFav) && sizeof($resItiFav)>0)
                        {
                            $itiService = $itineraireService->getNotes($resItiFav, $id_courant);
                        }
                        else
                        {
                            $itiService = json_encode(array("userNotes" => array(), "allNotes" => array()));
                        }
                        
                        $notes = json_decode($itiService, true);
                        $result['un'] = $notes['userNotes'];
                        
                        foreach($notes['allNotes'] as $calcMoy)
                        {
                            if(sizeof($calcMoy) > 0)
                            {
                                $result['an'][] = array_sum($calcMoy) / count($calcMoy);
                            }
                            else
                            {
                                $result['an'][] = -1;
                            }
                        }                    

			$content = $this->get("templating")->render("SiteCartoBundle:User:index.html.twig", $result);
			return new Response($content);
		}
		else
		{
			return new Response();
		}
	}
	
	/**
     * Fonction éditant les informations personnelles du profil d'un membre
     *
     * Cette méthode est appelée en ajax et ne requiert aucun paramètre : 
     * 
     * @return Response
     * 
     */
	public function profilSubmitAction(Request $request)
	{
		//on récupère les infos et on les stocke
		$prenom="";$nom="";$email="";$tel="";$date="";$licence="";
		
		$prenom = $request->request->get('Prenom','');
		$nom = $request->request->get('Nom','');
		$email = $request->request->get('Email','');
		$tel = $request->request->get('Tel');
		$date = $request->request->get('Date');
		
		//si les variables existent et passent les test, on les compare au infos de l'utilisateur
		
		//if(!empty($prenom) && !empty($nom) && !empty($email) && !empty($tel) && !empty($date) && !empty($licence))
		//{
			//on récupère l'id de l'utilisateur courant et on insère les valeurs sur son profil.
			$user = $this->getUser();
			$id = $user->getId();
			
			$manager = $this->getDoctrine()->getManager();
			$data = $manager->getRepository('SiteCartoBundle:Utilisateur')->findOneBy(array('id'=>$id)); 
		
			$data->setEmail($email);
			$data->setNom($nom);
			$data->setPrenom($prenom);
			$data->setDatenaissance(new \DateTime(str_replace("/", "-", $date)));
			$data->setTelephone($tel);
			
			$manager->flush();	
		//}

		return $this->redirect($this->generateUrl('site_carto_fiche'));
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
