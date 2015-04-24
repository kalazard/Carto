<?php

namespace Site\CartoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Site\CartoBundle\Entity\Typelieu;
use Site\CartoBundle\Entity\Icone;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Validator\Constraints\DateTime;

class TypelieuController extends Controller
{
    public function indexAction()
    {       
        $content = $this->get("templating")->render("SiteCartoBundle:Typelieu:index.html.twig");        
        return new Response($content);
    }

	//Récupération de la liste des lieux
    public function getAllLieuxAction(Request $request) {
      if ($request->isXMLHttpRequest()) 
      {
        $manager=$this->getDoctrine()->getManager();
        $repository = $manager->getRepository("SiteCartoBundle:Typelieu");
        $lieux = $repository->findAll();
 
        $response = new Response(json_encode($lieux));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
      }

      return new Response('This is not ajax!', 400);
    }

  public function uploadIconeAction()
  {   
    $content = $this->get("templating")->render("SiteCartoBundle:Typelieu:uploadIcone.html.twig");
    return new Response($content);
  }
  
  public function submituploadIconeAction()
  { 
    /*if ($request->isXMLHttpRequest())
    {   */
      $return_message = "";
      $code = 200;
      $target_dir ="C:/wamp/www/Images/";
      $upload = 1;

      if (!isset($_POST["label"]) )
      {
        $upload=0;
        $return_message .= " Veuillez définir un nom à votre type de lieu, svp. ";
      }
      else if(!isset($_FILES['icone']))
      {
        $upload=0;
        $return_message .= " Veuillez définir un fichier à uploader, svp. ";
      }
      else
      {
        $labelTypelieu = $_POST["label"];
        $icone_name = $_FILES['icone']['name'];
        $imageFileType = pathinfo($icone_name,PATHINFO_EXTENSION);
        $icone_name = new \DateTime( 'now' );
        $icone_name = date_format($icone_name,'U').".".$imageFileType;
        $target_file = $target_dir.$icone_name;
      }
      
      // Check file size
      if ($_FILES["icone"]["size"] > 5000000) {
        $return_message .= " Le fichier est trop volumineux. La taille est limitée à 5000 Ko. ";
        $upload = 0;
      }
      if($imageFileType != "png" && $imageFileType != "PNG" && $imageFileType != "jpg" && $imageFileType != "JPG" && $imageFileType != "jpeg") 
      {
        $return_message .= " Le format de fichier n'est pas valide.";
        $upload = 0;
      } 
      
      $response = new Response(json_encode(array( "result" => $return_message,"code" => $code)));
      
      if($upload != 0)
      {
        if(move_uploaded_file($_FILES['icone']['tmp_name'], $target_file))
        {
            $return_message = " Le fichier a correctement été importé";

            $manager=$this->getDoctrine()->getManager();

            $repositoryTypelieu=$manager->getRepository("SiteCartoBundle:Typelieu");

            $iconeUpload = new Icone();
            $iconeUpload->setPath($icone_name);

            $typelieuUpload = new Typelieu();
            $typelieuUpload->setLabel($labelTypelieu);
            $typelieuUpload->setIcone($iconeUpload);
            
            $manager->persist($iconeUpload);
            $manager->persist($typelieuUpload);
            $manager->flush();

            return new JsonResponse(array('data' => 'Typelieu Crée'),200);
                   
        }
      }
      else
      {
        $response->setStatusCode(500);
      }
    /*}
    else
    {
      return new JsonResponse(array('data' => 'Typelieu crée'),200);
    }*/
    
    return $response;
  }
}