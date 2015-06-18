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

    /**
     * Fonction de récupération de la liste des types de lieu
     *
     * Cette méthode est appelée en ajax et ne requiert aucun paramètre : 
     *
     * @return string 
     *
     * JSON contenant la liste des types de lieu
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *     "lieux": Liste de tous les types de lieu sérialisé
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
    public function getAllTypelieuAction(Request $request) {
        $manager=$this->getDoctrine()->getManager();
        $repository = $manager->getRepository("SiteCartoBundle:Typelieu");
        $lieux = $repository->findAll();
 
        $response = new Response(json_encode($lieux));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    /**
     * Fonction de récupération d'un type de lieu dans la base de données
     *
     * Cette méthode est appelée en ajax et requiert les paramètres suivants : 
     * 
     * <code>
     * id_user : id du type de lieu
     * </code>
     * 
     * @return string 
     *
     * JSON contenant les informations du type de lieu
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *     "typelieu": Objet type de lieu sérialisé
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

    /**
     * Fonction de récupération de la liste des types de lieu
     *
     * Cette méthode est appelée en ajax et ne requiert aucun paramètre : 
     *
     * @return Response
     *
     * JSON contenant la liste des types de lieu
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *     "listeTypelieu": Liste de tous les types de lieu sérialisé
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
  public function enregistreTypelieuAction()
  {   
        $manager=$this->getDoctrine()->getManager();
        $repository = $manager->getRepository("SiteCartoBundle:Typelieu");
        $listeTypelieu = $repository->findAll();

        $content = $this->get("templating")->render("SiteCartoBundle:Typelieu:saveTypelieu.html.twig", 
                                                array("listeTypelieu" => $listeTypelieu)
                                              );
        return new Response($content);
  }

    /**
     * Fonction de création d'un type de lieu
     *
     * Cette méthode est appelée en ajax et requiert les paramètres suivants : 
     * 
     * <code>
     * labelTypelieu : Label du type de lieu à créer 
     * $_FILES['icone'] : L'image à upload 
     * </code>
     * 
     * @return string 
     *
     * JSON permettant de définir si le type de lieu a été créé ou non
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *     "listeTypelieu": Liste de tous les types de lieu sérialisé
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
  public function submituploadIconeAction()
  { 
      $return_message = "";
      $code = 200;
      $target_dir ="../../Images/";
      //$target_dir ="C:/wamp/www/Images/";
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
            $iconeUpload->setPath("http://130.79.214.167/Images/".$icone_name);
            //$iconeUpload->setPath("http://localhost/Images/".$icone_name);

            $typelieuUpload = new Typelieu();
            $typelieuUpload->setLabel($labelTypelieu);
            $typelieuUpload->setIcone($iconeUpload);
            
            $manager->persist($iconeUpload);
            $manager->persist($typelieuUpload);
            $manager->flush();

            $manager=$this->getDoctrine()->getManager();
            $repository = $manager->getRepository("SiteCartoBundle:Typelieu");
            $listeTypelieu = $repository->findAll(); 

            $response = new Response($this->get("templating")->render("SiteCartoBundle:Typelieu:saveTypelieu.html.twig", array(
                                                'listeTypelieu' => $listeTypelieu
                                              )));
        }
      }
      else
      {
        $response->setStatusCode(500);
      }

    
    return $response;
  }

    /**
     * Fonction d'affichage de la modal pour éditer un type de lieu
     *
     * Cette méthode est appelée en ajax et requiert les paramètres suivants : 
     * 
     * <code>
     * idTypelieu : Id du type de lieu à modifier
     * </code>
     * 
     * @return string 
     *
     * JSON permettant de définir si le type de lieu a été créé ou non
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *     "typelieu": Le type de lieu à éditer sérialisé
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

    /**
     * Fonction de modification d'un type de lieu
     *
     * Cette méthode est appelée en ajax et requiert les paramètres suivants : 
     * 
     * <code>
     * labelTypelieu : Label du type de lieu à modifier
     * $_FILES['icone'] : L'image à upload 
     * </code>
     * 
     * @return string 
     *
     * JSON permettant de définir si le type de lieu a été modifié ou non
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *     "listeTypelieu": Liste de tous les types de lieu sérialisé
     * }
     * </code>
     * 
     * Example en cas d'erreur dans la modification :
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
  public function editTypelieuAction(Request $request)
    {
      $return_message = "";
      $code = 200;
      $target_dir ="../../Images/";
      //$target_dir ="C:/wamp/www/Images/";
      $upload = 1; 

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

        $manager=$this->getDoctrine()->getManager();
        $repository = $manager->getRepository("SiteCartoBundle:Typelieu");
        $listeTypelieu = $repository->findAll();

        return new Response($this->get("templating")->render("SiteCartoBundle:Typelieu:saveTypelieu.html.twig", array(
                                                'listeTypelieu' => $listeTypelieu
                                              )));
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
            $iconeUpload->setPath("http://130.79.214.167/Images/".$icone_name);
            //$iconeUpload->setPath("http://localhost/Images/".$icone_name);

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

            $manager=$this->getDoctrine()->getManager();
            $repository = $manager->getRepository("SiteCartoBundle:Typelieu");
            $listeTypelieu = $repository->findAll();

            $response = new Response($this->get("templating")->render("SiteCartoBundle:Typelieu:saveTypelieu.html.twig", array(
                                                'listeTypelieu' => $listeTypelieu
                                              )));
        }
      }
      else
      {
        $response->setStatusCode(500);
      }

    
    return $response;
    }

    /**
     * Fonction d'affichage de la modal pour supprimer un type de lieu
     *
     * Cette méthode est appelée en ajax et requiert les paramètres suivants : 
     * 
     * <code>
     * idTypelieu : Id du type de lieu à supprimer
     * </code>
     * 
     * @return string 
     *
     * JSON permettant de définir si le type de lieu a été supprimé ou non
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *     "typelieu": Le type de lieu à supprimer sérialisé
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
  public function afficheDeleteTypelieuAction(Request $request)
  {
      $idTypelieu = $request->request->get('idTypelieu', '');
      $formulaire = $this->get("templating")->render("SiteCartoBundle:Typelieu:deleteTypelieu.html.twig", array(
                                                                'idTypelieu' => $idTypelieu
                                                            ));

      return new Response($formulaire);
  }

    /**
     * Fonction de suppression d'un type de lieu
     *
     * Cette méthode est appelée en ajax et requiert les paramètres suivants : 
     * 
     * <code>
     *     "idTypelieu": Le type de lieu à supprimer sérialisé
     * </code>
     * 
     * @return Response 
     * 
     */
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