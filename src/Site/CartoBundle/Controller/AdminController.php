<?php namespace Site\CartoBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Site\CartoBundle\Entity\Permission;
use Site\CartoBundle\Entity\Membre;
use Site\CartoBundle\Entity\Role;
use Site\CartoBundle\Entity\News;
use \DateTime;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;


class AdminController extends Controller
{
    // Liste des liens d'admin
    public function indexAction()
    {
		$this->testDeDroits('Administration');
		
		$content = $this->get("templating")->render("SiteCartoBundle:Admin:index.html.twig"); 
        return new Response($content);
    }
	
	// Gestion des permissions
	public function aclAction(Request $request)
	{
		$this->testDeDroits('Administration');
	
		// Récupère le manager de doctrine
		$manager = $this->getDoctrine()->getManager();
		
		$repository_permissions = $manager->getRepository("SiteCartoBundle:Permission");
		$repository_roles = $manager->getRepository("SiteCartoBundle:Role");
		
		$permissions = $repository_permissions->findAll();
		$roles = $repository_roles->findAll();
		
		// Quand on post le formulaire
		if ($request->isMethod('post'))
		{
			// Récupère les données postées
			$params = $request->request->all();
			
			foreach($permissions as $permission)
			{
				foreach($permission->getRole() as $role)
				{
					$permission->removeRole($role);
					
					$manager->persist($permission);
					$manager->flush();
				}
			}
			
			foreach($roles as $role)
			{
				foreach($role->getPermission() as $permission)
				{
					$role->removePermission($permission);
					
					$manager->persist($role);
					$manager->flush();
				}
			}
			
			foreach($params as $key => $value)
			{
				$param_exploded = explode('-', $key);
				$permission_id = $param_exploded[1];
				$role_id = $param_exploded[2];
				$permission = $repository_permissions->findOneBy(array('id' => $permission_id));
				$role = $repository_roles->findOneBy(array('id' => $role_id));
				$permission->addRole($role);
				$role->addPermission($permission);
				
				$manager->flush();
			}
			
			return $this->redirect($this->generateUrl('site_carto_acl'));
		}
		
		$content = $this->get("templating")->render("SiteCartoBundle:Admin:acl.html.twig", array(
			'permissions' => $permissions,
			'roles' => $roles
		)); 
        return new Response($content);
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
