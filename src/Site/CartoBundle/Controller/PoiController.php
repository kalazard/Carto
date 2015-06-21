<?php

namespace Site\CartoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Site\CartoBundle\Entity\Poi;
use Site\CartoBundle\Entity\Coordonnees;
use Site\CartoBundle\Entity\Typelieu;
use Site\CartoBundle\Entity\Image;
use Site\CartoBundle\Entity\Icone;


use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Validator\Constraints\DateTime;

class PoiController extends Controller 
{
    public function getPoiAction(Request $request)
    {
        if ($request->isXMLHttpRequest()) 
        {
            $listPoi = array();
                
                if(!empty($search))
                {
                    $repository = $this
                        ->getDoctrine()
                        ->getManager()
                        ->getRepository('SiteCartoBundle:Poi')
                    ;

                    $listPoi['titre'] = $repository->findBy(array('titre' => $poi));
                    
                    $listPoi['description'] = $repository->findBy(array('description' => $poi));

                }

                $return = array('success' => true, 'serverError' => false, 'resultats' => $listPoi);
                $response = new Response(json_encode($return));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
        }
    
          return new Response('This is not ajax!', 400);
    }

    /**
     * Fonction de récupération de la liste des pois
     *
     * Cette méthode est appelée en ajax et ne requiert aucun paramètre : 
     *
     * @return string 
     *
     * JSON contenant la liste des pois
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *     "pois": Liste de tous les pois sérialisé
     * }
     * </code>
     * 
     * Example en cas d'erreur :
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
    public function getAllPoisAction(Request $request) {
      /*if ($request->isXMLHttpRequest()) 
      {*/
        $manager=$this->getDoctrine()->getManager();
        $repository = $manager->getRepository("SiteCartoBundle:Poi");
        $pois = $repository->findAll();
 
        $response = new Response(json_encode($pois));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
 /*     }

      return new Response('This is not ajax!', 400);*/
    }

    /**
     * Fonction de création d'un poi
     *
     * Cette méthode est appelée en ajax et requiert les paramètres suivants : 
     * 
     * <code>
     * latitude : Label du poi à créer 
     * longitude : L'image à upload 
     * titre : titre du poi
     * description : Description du poi
     * </code>
     * 
     * @return string 
     *
     * JSON permettant de définir si le poi a été créé ou non
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *      "idPoi" : Id du poi à créer 
     *      "path" : Le chemin de l'image du poi 
     *
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
    public function savePoiAction(Request $request)
    {
        //if ($request->isXMLHttpRequest()) 
        //{
            $manager=$this->getDoctrine()->getManager();

            $repositoryTypelieu=$manager->getRepository("SiteCartoBundle:Typelieu");
            
            $typelieu = $repositoryTypelieu->find($request->request->get("idLieu",1));

            $coord = new Coordonnees();
            $coord->setLongitude($request->request->get("lng",1));
            $coord->setLatitude($request->request->get("lat",1));
            $coord->setAltitude($request->request->get("alt",1));

            $poi = new Poi();
            $poi->setTitre($request->request->get("titre","Aucun titre disponible"));
            $poi->setDescription($request->request->get("description","Aucune description disponible"));
            $poi->setCoordonnees($coord);
            $poi->setTypelieu($typelieu);

            $manager->persist($coord);
            $manager->persist($typelieu);
            $manager->persist($poi);
            $manager->flush();
            return new JsonResponse(array('message' => 'Poi Crée', "idPoi" => $poi->getId(), "path" => $typelieu->getIcone()->getPath()),200);
        //}
      
        //return new Response('This is not ajax!', 400);
    }

    /**
     * Fonction d'affichage de la modal pour supprimer un poi
     *
     * Cette méthode est appelée en ajax et requiert les paramètres suivants : 
     * 
     * <code>
     * idPoi : Id du poi à supprimer
     * </code>
     * 
     * @return string 
     *
     * JSON permettant de définir si le poi a été supprimé ou non
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *     "poi": Le poi à supprimer sérialisé
     * }
     * </code>
     * 
     * Example en cas d'erreur :
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
    public function afficheDeletePoiAction(Request $request)
    {
        $idPoi = $request->request->get('idPoi', '');
        $formulaire = $this->get("templating")->render("SiteCartoBundle:Poi:deletePoi.html.twig", array(
                                                                'idPoi' => $idPoi
                                                            ));

        return new Response($formulaire);
    }

    /**
     * Fonction de suppression d'un poi
     *
     * Cette méthode est appelée en ajax et requiert les paramètres suivants : 
     * 
     * <code>
     *     "idPoi": Le poi à supprimer sérialisé
     * </code>
     * 
     * @return Response 
     * 
     */
    public function deletePoiAction(Request $request)
    {
        /*if($request->isXmlHttpRequest() && $this->getUser()->getRole()->getId() == 1)
        {*/
            $idPoi = $request->request->get('idPoi', '');
            $manager=$this->getDoctrine()->getManager();

            //On récupère l'objet poi
            $repository=$manager->getRepository("SiteCartoBundle:Poi");        
            $poi = $repository->findOneById($idPoi);

            $image = $poi->getImage();

            //Suppression de l'entité poi
            $manager->remove($poi);

            if($image != null)
            {
                $manager->remove($image);
            }       

            $manager->flush();

            $request->getSession()->getFlashBag()->add('notice', 'Poi supprimé');

            return new Response();
        /*}
        else
        {
            throw new NotFoundHttpException('Impossible de trouver la page demandée');
        }*/
    }

    /**
     * Fonction d'affichage de la modal pour éditer un poi
     *
     * Cette méthode est appelée en ajax et requiert les paramètres suivants : 
     * 
     * <code>
     * idPoi : Id du poi à modifier
     * </code>
     * 
     * @return string 
     *
     * JSON permettant de définir si le poi a été modifier ou non
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *     "idPoi" : Id du poi à éditer sérialisé
     *     "poi": Le poi à éditer sérialisé
     * }
     * </code>
     * 
     * Example en cas d'erreur :
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
    public function afficheEditPoiAction(Request $request)
    {
      $idPoi = $request->request->get('idPoi', '');
      $manager=$this->getDoctrine()->getManager();

      //On récupère l'objet poi
      $repository=$manager->getRepository("SiteCartoBundle:Poi");        
      $poi= $repository->findOneById($idPoi);

      $formulaire = $this->get("templating")->render("SiteCartoBundle:Poi:editPoi.html.twig", array(
                                                                'idPoi' => $idPoi,
                                                                'poi' => $poi
                                                            ));

      return new Response($formulaire);
    }

    /**
     * Fonction d'affichage de la modal pour éditer un poi
     *
     * Cette méthode est appelée en ajax et requiert les paramètres suivants : 
     * 
     * <code>
     * idPoi : Id du poi à modifier
     * </code>
     * 
     * @return string 
     *
     * JSON permettant de définir si le poi a été modifié ou non
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *     "poi": Le poi à éditer sérialisé
     * }
     * </code>
     * 
     * Example en cas d'erreur :
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
    public function editPoiAction(Request $request)
    {

        $idPoi = $request->request->get('idPoi', '');
        
        $titrePoi = $request->request->get('titre', 'modiftest');
        $descriptionPoi = $request->request->get('descriptionPoi', 'modiftest');
        $manager=$this->getDoctrine()->getManager();

        //On récupère l'objet typelieu
        $repository=$manager->getRepository("SiteCartoBundle:Poi");        
        $poi = $repository->findOneById($idPoi);
        
        $poi->setTitre($titrePoi);
        $poi->setDescription($descriptionPoi);

        $manager->persist($poi);
        $manager->flush();

        if($poi->getImage() != null)
        {
            $pathImagePoi = $poi->getImage()->getPath();
        }
        else
        {
            $pathImagePoi = null;
        }
        
        $response = new JsonResponse(array('message' => 'Poi modifié', "idPoi" => $poi->getId(), "titrePoi" => $poi->getTitre(), "descriptionPoi" => $poi->getDescription(), "path" => $poi->getTypelieu()->getIcone()->getPath(), "latPoi" => $poi->getCoordonnees()->getLatitude(), "lngPoi" => $poi->getCoordonnees()->getLongitude(), "pathImagePoi" => $pathImagePoi),200); 
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

	/**
     * Fonction de sauvegarde d'un POI avec une image 
     * 
     * <code>
     * $_FILES : Image uploadée par le client
	 * lng : longitude
	 * lat : latitude 
	 * alt : altitude
	 * titre : titre du POI
	 * description : descirption du POI
	 * 
     * </code>
     * 
     * @return string 
     *
     * JSON permettant de définir si le poi a été modifié ou non
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *     "poi": Le poi à éditer sérialisé
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
    public function savePoiWithPictureAction(Request $request){
        //Récupération de la photo
        //Sauvegarde du fichier   
        //$target_dir = "C:/testUp/";
        $target_dir = "/var/www/uploads/";
        $target_file = $target_dir . basename($_FILES["fichier"]["name"]);
        $uploadOk = 1;
        $imageFileType = pathinfo($target_file,PATHINFO_EXTENSION);
        
        // Check if image file is a actual image or fake image
        if(isset($_POST["submit"])) {
            $check = getimagesize($_FILES["fichier"]["tmp_name"]);
            if($check !== false) {
                //echo "File is an image - " . $check["mime"] . ".";
                $uploadOk = 1;
            } else {
                //echo "Le fichier n'est pas une image.";
                $uploadOk = 0;
            }
        }
        
        //On vérifie la taille du fichier
        if ($_FILES["fichier"]["size"] > 5000000) {
            //echo "L'image est trop volomineuse.";
            $uploadOk = 0;
        }
        
        //Autorisation de certaines extensions de fichier
        if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
        && $imageFileType != "gif" ) {
            //echo "Seules les extensions JPG, JPEG, PNG & GIF sont autorisées.";
            $uploadOk = 0;
        }
        
        //On vérifie qu'il n'y a pas eu d'erreurs lors de l'upload
        if ($uploadOk == 0)
        {
            //echo "Il y a eu un problème lors de l'envoi du fichier.";
        }
        else
        {
            $date = new \DateTime;
            $fileName = "image".date_format($date, 'U').".".$imageFileType;
            $newFile = $target_dir.$fileName;
                
            if (move_uploaded_file($_FILES["fichier"]["tmp_name"], $newFile)) {                
                $manager = $this->getDoctrine()->getManager();
                $repository = $manager->getRepository("SiteCartoBundle:Image");
                $newImage = new Image();
                $newImage->setPath("http://130.79.214.167/uploads/".$fileName);
                $manager->persist($newImage);
                $manager->flush();  
                $manager=$this->getDoctrine()->getManager();

                $repositoryTypelieu=$manager->getRepository("SiteCartoBundle:Typelieu");
                
                $typelieu = $repositoryTypelieu->find($request->request->get("idLieu",1));

                $coord = new Coordonnees();
                $coord->setLongitude($request->request->get("lng",1));
                $coord->setLatitude($request->request->get("lat",1));
                $coord->setAltitude($request->request->get("alt",1));

                $poi = new Poi();
                $poi->setTitre($request->request->get("titre","Aucun titre disponible"));
                $poi->setDescription($request->request->get("description","Aucune description disponible"));
                $poi->setCoordonnees($coord);
                $poi->setTypelieu($typelieu);
                $poi->setImage($manager->getRepository("SiteCartoBundle:Image")->find($newImage->getId()));
                $manager->persist($coord);
                $manager->persist($typelieu);
                $manager->persist($poi);
                $manager->flush();
                return new JsonResponse(array('message' => 'Poi Crée',"path" => $typelieu->getIcone()->getPath()),200);              
            } else {
                return new JsonResponse(array('message' => "Probleme lors de l'upload du fichier"),500);
            }
        }
    }
	
	/**
     * Fonction de tests des droits utilisateur.
     * 
	 * Il faut être connecté.
	 *
     * <code>
	 *  pas de paramètre
     * </code>
     * 
     * @return None
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
		   "page Access": true
     * }
     * </code>
     * 
     * Example en cas d'erreur du serveur :
     * 
     * <code>
     * {
     *     "success": false,
     *     "serverError": true,
     *     "message": createNotFoundException
     * }
     * </code>
     * 
     * 
     */
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
