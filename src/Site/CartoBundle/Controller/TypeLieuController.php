<?php
namespace Site\CartoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Site\CartoBundle\Entity\Typelieu;
use Site\CartoBundle\Entity\Icone;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Validator\Constraints\DateTime;

class TypelieuController extends Controller
{
    public function indexAction()
    {       
        $manager=$this->getDoctrine()->getManager();
        $repository = $manager->getRepository("SiteCartoBundle:Typelieu");
        $listeTypelieu = $repository->findAll();     

        $content = $this->get("templating")->render("SiteCartoBundle:Typelieu:saveTypelieu.html.twig", 
                                                array("listeTypelieu" => $listeTypelieu)
                                              );
        return new Response($content);
    }

        //Récupération de la liste des types de lieu
  /*  public function getAllTypelieuAction(Request $request) {
        $manager=$this->getDoctrine()->getManager();
        $repository = $manager->getRepository("SiteCartoBundle:Typelieu");
        $lieux = $repository->findAll();
 
        $response = new Response(json_encode($lieux));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }*/

    public function getTypelieuByIdAction()
    {
        $manager = $this->getDoctrine()->getManager();
        $repository = $manager->getRepository("SiteCartoBundle:Typelieu");
        $id = $request->request->get('idTypelieu');
        $typelieu = $repository->findOneById($id);

        $response = new Response(json_encode($typelieu));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

  public function saveTypelieuAction()
  {   
        $manager=$this->getDoctrine()->getManager();
        $repository = $manager->getRepository("SiteCartoBundle:Typelieu");
        $listeTypelieu = $repository->findAll();     

        $content = $this->get("templating")->render("SiteCartoBundle:Typelieu:saveTypelieu.html.twig", 
                                                array("listeTypelieu" => $listeTypelieu)
                                              );
        return new Response($content);
  }
  
  public function submituploadIconeAction()
  { 
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

            $response = new Response($this->render( $this->generateUrl('site_carto_saveTypelieu')));
        }
      }
      else
      {
        $response->setStatusCode(500);
      }

    
    return $response;
  }

    public function afficheEditTypelieuAction(Request $request)
  {
      $idTypelieu = $request->request->get('idTypelieu', '');
      $manager=$this->getDoctrine()->getManager();

      //On récupère l'objet typelieu
      $repository=$manager->getRepository("SiteCartoBundle:Typelieu");        
      $typelieu = $repository->findOneById($idTypelieu);

      $formulaire = $this->get("templating")->render("SiteCartoBundle:Typelieu:editTypelieu.html.twig", array(
                                                                'typelieu' => $typelieu
                                                            ));

      return new Response($formulaire);
  }

  public function editTypelieuAction(Request $request)
    {
      $return_message = "";
      $code = 200;
      $target_dir ="C:/wamp/www/Images/";
      $upload = 1;
      var_dump($_FILES);
      var_dump($_REQUEST);
      if (!isset($_POST["label"]) )
      {
        $upload=0;
        $return_message .= " Veuillez définir un nom à votre type de lieu, svp. ";
      }
      else if($_FILES["icone"]["size"] == 0 )
      {
        $upload=0;

        $idTypelieu = $request->request->get('typelieuid', '');
        $labelTypelieu = $request->request->get('label', '');
        $manager=$this->getDoctrine()->getManager();

        //On récupère l'objet typelieu
        $repository=$manager->getRepository("SiteCartoBundle:Typelieu");        
        $typelieuUpload = $repository->findOneById($idTypelieu);
        
        $typelieuUpload->setLabel($labelTypelieu);

        $manager->persist($typelieuUpload);
        $manager->flush();

        return new Response($this->render( $this->generateUrl('site_carto_saveTypelieu')));
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

            $idTypelieu = $request->request->get('typelieuid', '');
            $manager=$this->getDoctrine()->getManager();

            //On récupère l'objet typelieu
            $repository=$manager->getRepository("SiteCartoBundle:Typelieu");        
            $typelieuUpload = $repository->findOneById($idTypelieu);

            $typelieuUpload->setLabel($labelTypelieu);
            $typelieuUpload->setIcone($iconeUpload);
            
            $manager->persist($iconeUpload);
            $manager->persist($typelieuUpload);
            $manager->flush();

            $response = new Response($this->render( $this->generateUrl('site_carto_saveTypelieu')));
        }
      }
      else
      {
        $response->setStatusCode(500);
      }

    
    return $response;
    }

  public function afficheDeleteTypelieuAction(Request $request)
  {
      $idTypelieu = $request->request->get('idTypelieu', '');
      $formulaire = $this->get("templating")->render("SiteCartoBundle:Typelieu:deleteTypelieu.html.twig", array(
                                                                'idTypelieu' => $idTypelieu
                                                            ));

      return new Response($formulaire);
  }

  public function deleteTypelieuAction(Request $request)
    {
        /*if($request->isXmlHttpRequest() && $this->getUser()->getRole()->getId() == 1)
        {*/
            $idTypelieu = $request->request->get('idTypelieu', '');
            $manager=$this->getDoctrine()->getManager();

            //On récupère l'objet typelieu
            $repository=$manager->getRepository("SiteCartoBundle:Typelieu");        
            $typelieu = $repository->findOneById($idTypelieu);

            $icone = $typelieu->getIcone();


            //Suppression de l'entité typelieu
            $manager->remove($typelieu);

            $manager->remove($icone);       

            $manager->flush();

            $request->getSession()->getFlashBag()->add('notice', 'Type de lieu supprimé');

            return new Response();
            //return $this->redirect($this->generateUrl('site_carto_saveTypelieu'));
        /*}
        else
        {
            throw new NotFoundHttpException('Impossible de trouver la page demandée');
        }*/
    }
}