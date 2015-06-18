<?php

namespace Site\CartoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Site\CartoBundle\Entity\Icone;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\Validator\Constraints\DateTime;

class IconeController extends Controller
{
    /**
     * Fonction de récupération de la liste des icones
     *
     * Cette méthode est appelée en ajax et ne requiert aucun paramètre : 
     *
     * @return string 
     *
     * JSON contenant la liste des icones
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *     "icones": Liste de toutes les icones sérialisé
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
    public function getAllIconesAction(Request $request) {
      if ($request->isXMLHttpRequest()) 
      {
        $manager=$this->getDoctrine()->getManager();
        $repository = $manager->getRepository("SiteCartoBundle:Icone");
        $icones = $repository->findAll();
 
        $response = new Response(json_encode($icones));
                    $response->headers->set('Content-Type', 'application/json');
                    return $response;
      }

      return new Response('This is not ajax!', 400);
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