<?php

namespace Site\CartoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Site\CartoBundle\Entity\Poi;
use Site\CartoBundle\Entity\Coordonnees;
use Site\CartoBundle\Entity\Typelieu;
use Site\CartoBundle\Entity\Image;


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

    //Récupération de la liste des pois
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
            return new JsonResponse(array('message' => 'Poi Crée',"path" => $typelieu->getIcone()->getPath()),200);
        //}
      
        //return new Response('This is not ajax!', 400);
    }

    public function afficheDeletePoiAction(Request $request)
    {
        $idPoi = $request->request->get('idPoi', '');
        $formulaire = $this->get("templating")->render("SiteCartoBundle:Poi:deletePoi.html.twig", array(
                                                                'idPoi' => $idPoi
                                                            ));

        return new Response($formulaire);
    }

    public function deletePoiAction(Request $request)
    {
        /*if($request->isXmlHttpRequest() && $this->getUser()->getRole()->getId() == 1)
        {*/
            $idPoi = $request->request->get('idPoi', '');
            $manager=$this->getDoctrine()->getManager();

            //On récupère l'objet poi
            $repository=$manager->getRepository("SiteCartoBundle:Poi");        
            $poi = $repository->findOneById($idPoi);
            var_dump($poi);

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

    public function afficheEditPoiAction(Request $request)
    {
      $idPoi = $request->request->get('idPoi', '');
      $manager=$this->getDoctrine()->getManager();

      //On récupère l'objet poi
      $repository=$manager->getRepository("SiteCartoBundle:Poi");        
      $poi= $repository->findOneById($idPoi);

      $formulaire = $this->get("templating")->render("SiteCartoBundle:Poi:editPoi.html.twig", array(
                                                                'poi' => $poi
                                                            ));

      return new Response($formulaire);
    }

    public function editPoiAction(Request $request)
    {
        $idPoi = $request->request->get('poiid', '');
        $titrePoi = $request->request->get('titre', '');
        $descriptionPoi = $request->request->get('description', '');
        $manager=$this->getDoctrine()->getManager();

        //On récupère l'objet typelieu
        $repository=$manager->getRepository("SiteCartoBundle:Poi");        
        $poi = $repository->findOneById($idPoi);
        
        $poi->setTitre($titrePoi);
        $poi->setDescription($descriptionPoi);

        $manager->persist($poi);
        $manager->flush();

        $request->getSession()->getFlashBag()->add('notice', 'Poi modifié');
        return new Response();
    }

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

}
