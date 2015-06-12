<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

namespace Site\CartoBundle\Controller;

use Site\CartoBundle\Entity\Utilisateur;
use Site\CartoBundle\Entity\Itineraire;
use Site\CartoBundle\Entity\Role;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use DateTime;

class MemberController extends Controller {

	//Affichage de la fiche membre
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

			$content = $this->get("templating")->render("SiteCartoBundle:User:index.html.twig", $result);
			return new Response($content);
		}
		else
		{
			return new Response();
		}
	}
	
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

}
