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
use Exception;
use SoapClient;
use Site\CartoBundle\Security\CustomCrypto;
use DateTime;
class UserController extends Controller {
    //plus utilisé pour afficher le profil, voir le controleur profilAction
    public function indexAction() {
        $content = $this->get("templating")->render("SiteCartoBundle:User:index.html.twig");
        return new Response($content);
    }
    public function getRoleMapAction(Request $request)
    {
        $user= $this->getUser();
    
        $role = $user->getRole()->getId();
        return new Response(json_encode(array("role" => $role, "code" => 200)));
    }
        /**
     * Fonction de création d'un utilisateur
     *
     * Cette méthode est appelée en ajax et requiert les paramètres suivants : 
     * 
     * <code>
     * email : Email de l'utilisateur à créer 
     * nom : Nom de l'utilisateur à créer 
     * prenom : Prénom de l'utilisateur à créer 
     * datenaissance : Date de naissance de l'utilisateur à créer 
     * telephone : Téléphone de l'utilisateur à créer 
     * licence : Url du site de la licence de l'utilisateur à créer
     * </code>
     * 
     * @return string 
     *
     * JSON permettant de définir si l'utilisateur a été créé ou non
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *     "message": "Message"
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
    public function createAction() {
        //On récupère la requete courrante
        $request = $this->getRequest();
        //On regarde qu'il s'agit bien d'une requête AJAX
        if ($request->isXmlHttpRequest()) {
            try {
                if (!$this->isCsrfTokenValid('default', $request->get('_csrf_token'))) {
                    throw new Exception("CSRF TOKEN ATTAK", 500);
                }
                //On récupère le manager de Doctrine
                $manager = $this->getDoctrine()->getManager();
                //On récupère la variable email qui servira aussi de nom d'utilisateur
                $email = $request->request->get('email');
                //On récupère le nom du membre
                $nom = $request->request->get('nom');
                //On récupère le prénom de l'utilisateur
                $prenom = $request->request->get('prenom');
                //On récupère la date de naissance de l'utilisateur
                $datenaissance = $request->request->get('datenaissance');
                //On récupère le numéro de téléphone de l'utilisateur
                $telephone = $request->request->get('telephone');
                //Le role sera celui qu'aura spécifié l'administrateur donc on récupère ce paramètre de la requête
                $role_base = $request->request->get('role');
                
                if($role_base == "")
                {
                    $role_base = 2; //C'est un utilisateur
                }
                else
                {
                   $role_base = intval($role_base); 
                }
                //On fait des vérifications pour voir que les informations saisies sont valide
                //Si l'email est vide
                if ($email == "") {
                    //success = false car l'opération de création à échoué, serverError = false car ce n'est pas uen erreure côté serveur 
                    $return = array('success' => false, 'serverError' => false, 'message' => "L'email ne doit pas être vide");
                    $response = new Response(json_encode($return));
                    $response->headers->set('Content-Type', 'application/json');
                    return $response;
                }
                //Si l'email n'es pas un email
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    //success = false car l'opération de création à échoué, serverError = false car ce n'est pas uen erreure côté serveur 
                    $return = array('success' => false, 'serverError' => false, 'message' => "L'email n'a pas un format valide");
                    $response = new Response(json_encode($return));
                    $response->headers->set('Content-Type', 'application/json');
                    return $response;
                }
                //Si l'email n'existe pas déjà dans la base de données
                $userWithEmail = $manager->getRepository('SiteCartoBundle:Utilisateur')->findOneBy(array('email' => $email));
                if (!is_null($userWithEmail)) {
                    //success = false car l'opération de création à échoué, serverError = false car ce n'est pas uen erreure côté serveur 
                    $return = array('success' => false, 'serverError' => false, 'message' => "Cet email est déjà utilisé");
                    $response = new Response(json_encode($return));
                    $response->headers->set('Content-Type', 'application/json');
                    return $response;
                }
                //Si le nom est vide
                if ($nom == "") {
                    //success = false car l'opération de création à échoué, serverError = false car ce n'est pas uen erreure côté serveur
                    //$return = array('success' => false, 'serverError' => false, 'message' => "Le nom ne doît pas être vide");
                    //$response = new Response(json_encode($return));
                    //$response->headers->set('Content-Type', 'application/json');
                    //return $response;
                    $nom = null;
                }
                //Si la date de naissance est vide
                if ($datenaissance == "") {
                    //success = false car l'opération de création à échoué, serverError = false car ce n'est pas uen erreure côté serveur
                    //$return = array('success' => false, 'serverError' => false, 'message' => "La date de naissance ne doît pas être vide");
                    //$response = new Response(json_encode($return));
                    //$response->headers->set('Content-Type', 'application/json');
                    //return $response;
                    $datenaissance = null;
                }
                //Si le prenom est vide
                if ($prenom == "") {
                    //success = false car l'opération de création à échoué, serverError = false car ce n'est pas uen erreure côté serveur
                    //$return = array('success' => false, 'serverError' => false, 'message' => "Le prénom ne doît pas être vide");
                    //$response = new Response(json_encode($return));
                    //$response->headers->set('Content-Type', 'application/json');
                    //return $response;
                    $prenom = null;
                }
                //Si le telephone est vide
                if ($telephone == "") {
                    //success = false car l'opération de création à échoué, serverError = false car ce n'est pas uen erreure côté serveur
                    //$return = array('success' => false, 'serverError' => false, 'message' => "Le telephone ne doît pas être vide");
                    //$response = new Response(json_encode($return));
                    //$response->headers->set('Content-Type', 'application/json');
                    //return $response;
                    $telephone = null;
                }
                if ($datenaissance != "") {
                    $datenaissance = DateTime::createFromFormat('d/m/Y', $datenaissance);
                    $date_errors = DateTime::getLastErrors();
                    if ($date_errors['warning_count'] + $date_errors['error_count'] > 0) {
                        $return = array('success' => false, 'serverError' => false, 'message' => "Le format de la date est invalide");
                        $response = new Response(json_encode($return));
                        $response->headers->set('Content-Type', 'application/json');
                        return $response;
                    }
                }
                // On crée l'utilisateur vide
                $user = new Utilisateur();
                //On créé un nouveau role vide
                $role = new Role();
                //On récupère le depot role
                $repository = $manager->getRepository("SiteCartoBundle:Role");
                //On récupère le role spécifié dans la base de données
                $role = $repository->find($role_base);
                //Si le role est null
                if (is_null($role)) {
                    //success = false car l'opération de création à échoué, serverError = false car ce n'est pas uen erreure côté serveur 
                    $return = array('success' => false, 'serverError' => false, 'message' => "Le role spécifié est introuvable");
                    $response = new Response(json_encode($return));
                    $response->headers->set('Content-Type', 'application/json');
                    return $response;
                }
                $user->setEmail($email);
                $user->setNom($nom);
                $user->setPrenom($prenom);
                $user->setDatenaissance($datenaissance);
                $user->setTelephone($telephone);                
                $user->setRole($role);
                // On persite l'utilisateur
                //On génère un nouveau mot de passe
                $password = md5(uniqid('', true));
                //Ensuite on essaye de se connecter avec le webservice
                $clientSOAP = new SoapClient(null, array(
                    'uri' => $this->container->getParameter("auth_server_host"),
                    'location' => $this->container->getParameter("auth_server_host"),
                    'trace' => 1,
                    'exceptions' => 1
                ));
                //On appel la méthode du webservice qui permet de se connecter
                $response = $clientSOAP->__call('createUser', array('username' => CustomCrypto::encrypt($email), 'password' => CustomCrypto::encrypt($password), 'server' => CustomCrypto::encrypt($_SERVER['SERVER_ADDR'])));
                //L'utilisateur n'existe pas dans la base de données ou les identifiants sont incorrects
                if ($response['error'] == true) {
                    $return = array('success' => false, 'serverError' => false, 'message' => $response['message']);
                    $response = new Response(json_encode($return));
                    $response->headers->set('Content-Type', 'application/json');
                    return $response;
                }
                $user->setId(CustomCrypto::decrypt($response['id_user']));
                $manager->persist($user);
                //On déclenche l'enregistrement dans la base de données
                $manager->flush();
                //On envoie le mot de passe généré à l'utilisateur
                $message = \Swift_Message::newInstance()
                        ->setSubject('Création de compte ')
                        ->setFrom('noreply.trail@gmail.com')
                        ->setTo($user->getEmail())
                        ->setBody("Vos identifiants pour vous connecter : \n login = " . $user->getEmail() . "\n mot de passe = " . $password);
                $this->get('mailer')->send($message);
                //Tout s'est déroulé correctement
                $return = array('success' => true, 'serverError' => false, 'message' => "L'utilisateur est inscrit");
                $response = new Response(json_encode($return));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            } catch (Exception $e) {
                $return = array('success' => false, 'serverError' => true, 'message' => $e->getMessage());
                $response = new Response(json_encode($return));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }
        } else {
            //La requête n'es pas une requête ajax, on envoie une erreur
            throw new NotFoundHttpException('Impossible de trouver la page demandée');
        }
    }
    /**
     * Fonction de chargement des roles
     *
     * Cette méthode est appelée en ajax et ne requiert aucuns paramètres 
     * 
     * @return string 
     *
     * JSON contenant la liste des roles de la base de données
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *     "roles": role
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
    public function loadRolesAction() {
        //On récupère la requête courrante
        $request = $this->getRequest();
        //On regarde qu'il s'agit bien d'une requête ajax
        if ($request->isXmlHttpRequest()) {
            try {
                //On vérifie que l'utilisateur courant est boen administrateur
                if ($this->get('security.context')->isGranted('ROLE_Administrateur')) {
                    //On récupère le manager de Doctrine
                    $manager = $this->getDoctrine()->getManager();
                    //On récupère le dépôt role
                    $repository = $manager->getRepository("SiteCartoBundle:Role");
                    //On récupère tous les rôles
                    $roles = $repository->findAll();
                    $return = array('success' => true, 'serverError' => false, 'roles' => $roles);
                    $response = new Response(json_encode($return));
                    $response->headers->set('Content-Type', 'application/json');
                    return $response;
                } else {
                    //L'utilisateur actuellement connecté n'es pas adminstrateur, on ne renvoie donc rien
                    $return = array('success' => false, 'serverError' => false);
                    $response = new Response(json_encode($return));
                    $response->headers->set('Content-Type', 'application/json');
                    return $response;
                }
            } catch (Exception $e) {
                //Il y a une erreur côté serveur
                $return = array('success' => false, 'serverError' => true, 'message' => $e->getMessage());
                $response = new Response(json_encode($return));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }
        } else {
            //La requête n'es pas une requête ajax, on envoie une erreur
            throw new NotFoundHttpException('Impossible de trouver la page demandée');
        }
    }
         /**
     * Fonction de récupération de l'état de l'utilisateur (activé / désactivé)
     *
     * Cette méthode est appelée en ajax et requiert les paramètres suivants : 
     * 
     * <code>
     * id_user : id de l'utilisateur
     * </code>
     * 
     * @return string 
     *
     * JSON contenant l'état d'activation de l'utilisateur
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *     "actif": int
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
    public function getUserActivationAction() {
        //Permet de récupérer dans le webservice si l'utilisateur passé en paramètre existe ou non
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            try {
                //Seul l'administrateur peut récupérer les informations  d'un utilisateur
                if ($this->get('security.context')->isGranted('ROLE_Administrateur')) {
                    //On récupère l'id de l'utilisateur que l'on souhaite rechercher
                    $id = $request->request->get('id_user');
                    //On récupère le manager de doctrine
                    $clientSOAP = new SoapClient(null, array(
                        'uri' => $this->container->getParameter("auth_server_host"),
                        'location' => $this->container->getParameter("auth_server_host"),
                        'trace' => 1,
                        'exceptions' => 1
                    ));
                    //On appel la méthode du webservice qui permet de se connecter
                    $response = $clientSOAP->__call('getUserActivation', array('id' => CustomCrypto::encrypt($id), 'server' => CustomCrypto::encrypt($_SERVER['SERVER_ADDR'])));
                    //Si il y a une erreur
                    if ($response['error'] == true) {
                        $return = array('success' => false, 'serverError' => false, 'message' => $response['message']);
                        $response = new Response(json_encode($return));
                        $response->headers->set('Content-Type', 'application/json');
                        return $response;
                    }
                    $return = array('success' => true, 'serverError' => false, 'actif' => $response['actif']);
                    $response = new Response(json_encode($return));
                    $response->headers->set('Content-Type', 'application/json');
                    return $response;
                } else {
                    //L'utilisateur n'est pas un administrateur
                    $return = array('success' => false, 'serverError' => false, 'message' => "Vous n'avez pas le droit de récupérer cette information");
                    $response = new Response(json_encode($return));
                    $response->headers->set('Content-Type', 'application/json');
                    return $response;
                }
            } catch (Exception $e) {
                //Il y a eu une erreur côté serveur
                $return = array('success' => false, 'serverError' => true, 'message' => $e->getMessage());
                $response = new Response(json_encode($return));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }
        } else {
            //La requête n'es pas une requête ajax, on envoie une erreur
            throw new NotFoundHttpException('Impossible de trouver la page demandée');
        }
    }
        /**
     * Fonction de récupération de tous les utilisateurs de la base de données
     *
     * Cette méthode est appelée en ajax et ne requiert aucuns paramètres : 
     * 
     * @return string 
     *
     * JSON contenant une liste de tous les utilisateurs
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *     "users": Liste d'objet utilisateurs,
     *     "visibilite": récupère le rôle de l'utilisateur connecté pour savoir si il est admin ou non
     *     "actifs": Tableau qui pour chaque utilisateur renvoyé contiendra son état d'activation
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
    public function getAllUsersAction() {
        //On récupère la requête courrante
        $request = $this->getRequest();
        //On regarde qu'il s'agit bien d'une requête ajax
        if ($request->isXmlHttpRequest()) {
           // try {
                //On vérifie que l'utilisateur courant est bien administrateur ou membre
                if ($this->get('security.context')->isGranted('ROLE_Administrateur')) {
                    //On récupère le manager de Doctrine
                    $manager = $this->getDoctrine()->getManager();
                    //On récupère le dépôt utilisateur
                    $repository = $manager->getRepository("SiteCartoBundle:Utilisateur");
                    //On récupère tous les utilisateurs
                    $users = $repository->findAll();
                    $actifs = array();
                    foreach ($users as $value) {
                        $clientSOAP = new SoapClient(null, array(
                            'uri' => $this->container->getParameter("auth_server_host"),
                            'location' => $this->container->getParameter("auth_server_host"),
                            'trace' => 1,
                            'exceptions' => 1
                        ));
                        //On appel la méthode du webservice qui permet de se connecter
                        $response = $clientSOAP->__call('getUserActivation', array('id' => CustomCrypto::encrypt($value->getId()), 'server' => CustomCrypto::encrypt($_SERVER['SERVER_ADDR'])));
                        //Si il y a une erreur
                        if ($response['error'] == true) {
                            $return = array('success' => false, 'serverError' => true, 'message' => $response['message']);
                            $response = new Response(json_encode($return));
                            $response->headers->set('Content-Type', 'application/json');
                            return $response;
                        }
                        $actifs[] = $response['actif'];
                    }
                    $visibilite = $this->get('security.context')->isGranted('ROLE_Administrateur');
                    $return = array('success' => true, 'serverError' => false, 'users' => $users, 'visibilite' => $visibilite, 'actif' => $actifs);
                    $response = new Response(json_encode($return));
                    $response->headers->set('Content-Type', 'application/json');
                    return $response;
                } else {
                    //L'utilisateur actuellement connecté n'es pas adminstrateur, on ne renvoie donc rien
                    $return = array('success' => false, 'serverError' => false);
                    $response = new Response(json_encode($return));
                    $response->headers->set('Content-Type', 'application/json');
                    return $response;
                }
            /*} catch (Exception $e) {
                //Il y a une erreur côté serveur
                $return = array('success' => false, 'serverError' => true, 'message' => $e->getMessage());
                $response = new Response(json_encode($return));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }*/
        } else {
            //La requête n'es pas une requête ajax, on envoie une erreur
            throw new NotFoundHttpException('Impossible de trouver la page demandée');
        }
    }
    /**
     * Fonction d'activation ou de désactivation de l'utilisateur
     *
     * Cette méthode est appelée en ajax et requiert les paramètres suivants : 
     * 
     * <code>
     * id_user : id de l'utilisateur
     * activation: int en fonction de l'état que l'on veut donner à l'utilisateur
     * </code>
     * 
     * @return string 
     *
     * JSON contenant le succès de l'opération
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *     "message": "message"
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
    public function deleteAction() {
        //Seul l'administrateur peut supprimer un utilisateur
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            try {
                if ($this->get('security.context')->isGranted('ROLE_Administrateur')) {
                    $id = $request->request->get('id_user');
                    $activation = $request->request->get('activation');
                    $manager = $this->getDoctrine()->getManager();    //On récupère le manager de doctrine
                    $repository = $manager->getRepository("SiteCartoBundle:Utilisateur");
                    $usertodelete = $repository->find($id);
                    if (is_null($usertodelete)) {
                        $return = array('success' => false, 'serverError' => false, 'message' => "L'utilisateur spécifié n'existe plus dans la base de données");
                        $response = new Response(json_encode($return));
                        $response->headers->set('Content-Type', 'application/json');
                        return $response;
                    }
                    //On désactive l'utilisateur sur le service d'authentification
                    //Ensuite on essaye de se connecter avec le webservice
                    $clientSOAP = new SoapClient(null, array(
                        'uri' => $this->container->getParameter("auth_server_host"),
                        'location' => $this->container->getParameter("auth_server_host"),
                        'trace' => 1,
                        'exceptions' => 1
                    ));
                    //On appel la méthode du webservice qui permet de modifier l'état de l'utilisateur
                    $response = $clientSOAP->__call('updateUserActivation', array('id' => CustomCrypto::encrypt($usertodelete->getId()), 'activation' => CustomCrypto::encrypt($activation), 'server' => CustomCrypto::encrypt($_SERVER['SERVER_ADDR'])));
                    //L'utilisateur n'existe pas dans la base de données du serveur d'authentification
                    if ($response['error'] == true) {
                        $return = array('success' => false, 'serverError' => false, 'message' => $response['message']);
                        $response = new Response(json_encode($return));
                        $response->headers->set('Content-Type', 'application/json');
                        return $response;
                    }
                    //L'utilisateur a bien été supprimé
                if($activation == 0)
                {
                    $messagea = "L'utilisateur a bien été désactivé";
                }
                else
                {
                     $messagea = "L'utilisateur a bien été activé";
                }
                $return = array('success' => true, 'serverError' => false, 'message' => $messagea);
                $response = new Response(json_encode($return));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
                } else {
                    //L'utilisateur n'es pas un administrateur
                    $return = array('success' => false, 'serverError' => false, 'message' => "Vous n'avez pas le droit de désactiver un utilisateur");
                    $response = new Response(json_encode($return));
                    $response->headers->set('Content-Type', 'application/json');
                    return $response;
                }
            } catch (Exception $e) {
                //Il y a une erreur côté serveur
                $return = array('success' => false, 'serverError' => true, 'message' => $e->getMessage());
                $response = new Response(json_encode($return));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }
        } else {
            //La requête n'es pas une requête ajax, on envoie une erreur
            throw new NotFoundHttpException('Impossible de trouver la page demandée');
        }
    }
    
        /**
     * Fonction de réinitialisation du mot de passe de l'utilisateur
     *
     * Cette méthode est appelée en ajax et ne requiert aucuns paramètres.
     * Un email avec un nouveau mot de passe est envoyé à l'adresse mail de l'utilisateur.
     * 
     * @return string 
     *
     * JSON contenant un message.
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *     "message": "Message"
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
    public function resetPasswordAction()
    {
       $request = $this->getRequest();
        //On regarde qu'il s'agit bien d'une requête ajax
        if ($request->isXmlHttpRequest()) {
            try {
                    $manager = $this->getDoctrine()->getManager();
                    //On récupère le nouveau mot de passe
                    $newpassword = md5(uniqid('', true));
                    //On récupère l'email de l'utilisateur qui veut reste son mot de passe
                    $email = $request->request->get('email', '');
                    //On récupère son id
                    //findOneBy(array('alias' => "le-trail"))
                    if ($email == "") {
                        $return = array('success' => false, 'serverError' => false, 'message' => "Veuillez remplir le formulaire");
                        $response = new Response(json_encode($return));
                        $response->headers->set('Content-Type', 'application/json');
                        return $response;
                    }
                    $user = $manager->getRepository("SiteCartoBundle:Utilisateur")->findOneBy(array('email' => $email));
                    if ($user == null) {
                        $return = array('success' => false, 'serverError' => false, 'message' => "Aucun compte enregistré pour cette adresse mail");
                        $response = new Response(json_encode($return));
                        $response->headers->set('Content-Type', 'application/json');
                        return $response;
                    }
                    $id = $user->getId();
                    
                    
                    $clientSOAP = new SoapClient(null, array(
                        'uri' => $this->container->getParameter("auth_server_host"),
                        'location' => $this->container->getParameter("auth_server_host"),
                        'trace' => 1,
                        'exceptions' => 1
                    ));
                    //On appel la méthode du webservice qui permet de modifier l'état de l'utilisateur
                    $response = $clientSOAP->__call('changePassword', array('id' => CustomCrypto::encrypt($id), 'newpassword' => CustomCrypto::encrypt($newpassword), 'server' => CustomCrypto::encrypt($_SERVER['SERVER_ADDR'])));
                    //L'utilisateur n'existe pas dans la base de données du serveur d'authentification
                    if ($response['error'] == true) {
                        $return = array('success' => false, 'serverError' => false, 'message' => $response['message']);
                        $response = new Response(json_encode($return));
                        $response->headers->set('Content-Type', 'application/json');
                        return $response;
                    }
                    $message = \Swift_Message::newInstance()
                        ->setSubject('Nouveau mot de passe')
                        ->setFrom('noreply.trail@gmail.com')
                        ->setTo($email)
                        ->setBody("Vos identifiants pour vous connecter : \n login = " . $user->getEmail() . "\n mot de passe = " . $newpassword);
                $this->get('mailer')->send($message);
                    $return = array('success' => true, 'serverError' => false);
                    $response = new Response(json_encode($return));
                    $response->headers->set('Content-Type', 'application/json');
                    return $response;
                
            } catch (Exception $e) {
                //Il y a une erreur côté serveur
                $return = array('success' => false, 'serverError' => true, 'message' => $e->getMessage());
                $response = new Response(json_encode($return));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }
        } else {
            //La requête n'es pas une requête ajax, on envoie une erreur
            throw new NotFoundHttpException('Impossible de trouver la page demandée');
        }
    }
        /**
     * Fonction de récupération des informations d'un utilisateur dans la base de données
     *
     * Cette méthode est appelée en ajax et requiert les paramètres suivants : 
     * 
     * <code>
     * id_user : id de l'utilisateur
     * </code>
     * 
     * @return string 
     *
     * JSON contenant les informations de l'utilisateur
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *     "user": Objet membre sérailisé
     *     "role": Objet role de l'utilisateur sérialisé
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
    public function getUserAction() {
        $request = $this->getRequest();
        if ($request->isXmlHttpRequest()) {
            try {
                //Seul l'administrateur peut récupérer les informations  d'un utilisateur
                if ($this->get('security.context')->isGranted('ROLE_Administrateur')) {
                    //On récupère l'id de l'utilisateur que l'on souhaite rechercher
                    $id = $request->request->get('id_user');
                    //On récupère le manager de doctrine
                    $manager = $this->getDoctrine()->getManager();
                    $repository = $manager->getRepository("SiteCartoBundle:Utilisateur");
                    //On récupère l'utilisateur à l'aide de l'id passé en paramètre à la requête
                    $user = $repository->find($id);
                    //Si l'utilisateur n'existe plus dans la base de données
                    if (is_null($user)) {
                        $return = array('success' => false, 'serverError' => false, 'message' => "L'utilisateur spécifié n'existe plus dans la base de données");
                        $response = new Response(json_encode($return));
                        $response->headers->set('Content-Type', 'application/json');
                        return $response;
                    }
                    //On récupère l'id du role de l'utilisateur
                    $repository = $manager->getRepository("SiteCartoBundle:Role");
                    $role = $repository->find($user->getRole()->getId());
                    $return = array('success' => true, 'serverError' => false, 'user' => $user, 'role' => $role);
                    $response = new Response(json_encode($return));
                    $response->headers->set('Content-Type', 'application/json');
                    return $response;
                } else {
                    //L'utilisateur n'est pas un administrateur
                    $return = array('success' => false, 'serverError' => false, 'message' => "Vous n'avez pas le droit de modifier un utilisateur");
                    $response = new Response(json_encode($return));
                    $response->headers->set('Content-Type', 'application/json');
                    return $response;
                }
            } catch (Exception $e) {
                //Il y a eu une erreur côté serveur
                $return = array('success' => false, 'serverError' => true, 'message' => $e->getMessage());
                $response = new Response(json_encode($return));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }
        } else {
            //La requête n'es pas une requête ajax, on envoie une erreur
            throw new NotFoundHttpException('Impossible de trouver la page demandée');
        }
    }
    
    /**
     * Fonction de mise à jour d'un utilisateur
     *
     * Cette méthode est appelée en ajax et requiert les paramètres suivants : 
     * 
     * <code>
     * id_user : id de l'utilisateur à modifier
     * emailUpdate : Email de l'utilisateur à modifier 
     * nomUpdate : Nom de l'utilisateur à modifier 
     * prenomUpdate : Prénom de l'utilisateur à modifier 
     * datenaissanceUpdate : Date de naissance de l'utilisateur à modifier 
     * telephoneUpdate : Téléphone de l'utilisateur à modifier 
     * licenceUpdate : Url du site de la licence de l'utilisateur à modifier
     * </code>
     * 
     * @return string 
     *
     * JSON permettant de définir si l'utilisateur a été modifié ou non
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *     "message": "Message"
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
    public function updateUserAction() {
        $request = $this->getRequest();  //On récupère la requete courrante
        if ($request->isXmlHttpRequest()) {    //On regarde qu'il s'agit bien d'une requête AJAX
            try {
                //Seul l'administrateur peut mettre à jour un utilisateur
                if ($this->get('security.context')->isGranted('ROLE_Administrateur')) {
                    //On récupère l'identifiant de l'utilisateur à mettre à jour
                    $userToUpdate = intval($request->request->get('id_user'));
                    //On récupère son email
                    $email = $request->request->get('emailUpdate');
                    //On récupère son prenom
                    $nom = $request->request->get('nomUpdate');
                    //On récupère son prenom
                    $prenom = $request->request->get('prenomUpdate');
                    //On récupère sa date de naissance
                    $datenaissance = $request->request->get('datenaissanceUpdate');
                    //On récupère son telephone
                    $telephone = $request->request->get('telephoneUpdate');
                    //On récupère son role
                    $roleupdate = intval($request->request->get('roleUpdate'));
                    //On récupère le manager de Doctrine
                    $manager = $this->getDoctrine()->getManager();
                    //On récupère le depot role
                    $repository = $manager->getRepository("SiteCartoBundle:Role");
                    // On récupère l'utilisateur a mettre a jour
                    $user = $manager->getRepository("SiteCartoBundle:Utilisateur")->find($userToUpdate);
                    //Si l'utilisateur n'existe plus dans la base de données
                    if (is_null($user)) {
                        $return = array('success' => false, 'serverError' => false, 'message' => "L'utilisateur spécifié n'existe plus dans la base de données");
                        $response = new Response(json_encode($return));
                        $response->headers->set('Content-Type', 'application/json');
                        return $response;
                    }
                    //On récupère le role spécifié pour la mise à jour dans la base de données
                    $role = $repository->find($roleupdate);
                    if (is_null($role)) {
                        $return = array('success' => false, 'serverError' => false, 'message' => "Le rôle spécifié n'existe plus dans la base de données");
                        $response = new Response(json_encode($return));
                        $response->headers->set('Content-Type', 'application/json');
                        return $response;
                    }
                    //On fait des vérifications pour voir que les informations saisies sont valide
                    //Si l'email est vide
                    if ($email == "") {
                        //success = false car l'opération de création à échoué, serverError = false car ce n'est pas uen erreure côté serveur 
                        $return = array('success' => false, 'serverError' => false, 'message' => "L'email ne doit pas être vide");
                        $response = new Response(json_encode($return));
                        $response->headers->set('Content-Type', 'application/json');
                        return $response;
                    }
                    //Si l'email n'es pas un email
                    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        //success = false car l'opération de création à échoué, serverError = false car ce n'est pas uen erreure côté serveur 
                        $return = array('success' => false, 'serverError' => false, 'message' => "L'email n'a pas un format valide");
                        $response = new Response(json_encode($return));
                        $response->headers->set('Content-Type', 'application/json');
                        return $response;
                    }
                    //Si l'email n'existe pas déjà dans la base de données (pour un utilisateur différent de celui que l'on met à jour
                    $userWithEmail = $manager->getRepository('SiteCartoBundle:Utilisateur')->findOneBy(array('email' => $email));
                    if (!is_null($userWithEmail) && $userWithEmail->getId() != $user->getId()) {
                        //success = false car l'opération de création à échoué, serverError = false car ce n'est pas uen erreure côté serveur 
                        $return = array('success' => false, 'serverError' => false, 'message' => "Cet email est déjà utilisé");
                        $response = new Response(json_encode($return));
                        $response->headers->set('Content-Type', 'application/json');
                        return $response;
                    }
                    //Si le role est null
                    if (is_null($role)) {
                        //success = false car l'opération de création à échoué, serverError = false car ce n'est pas uen erreure côté serveur 
                        $return = array('success' => false, 'serverError' => false, 'message' => "Le role spécifié est introuvable");
                        $response = new Response(json_encode($return));
                        $response->headers->set('Content-Type', 'application/json');
                        return $response;
                    }
                    //Si le nom est vide
                    if ($nom == "") {
                        //success = false car l'opération de création à échoué, serverError = false car ce n'est pas uen erreure côté serveur 
                        /* $return = array('success' => false, 'serverError' => false, 'message' => "Le nom ne doît pas être vide");
                          $response = new Response(json_encode($return));
                          $response->headers->set('Content-Type', 'application/json');
                          return $response; */
                        $nom = null;
                    }
                    //Si la date de naissance est vide
                    if ($datenaissance == "") {
                        //success = false car l'opération de création à échoué, serverError = false car ce n'est pas uen erreure côté serveur 
                        /* $return = array('success' => false, 'serverError' => false, 'message' => "La date de naissance ne doît pas être vide");
                          $response = new Response(json_encode($return));
                          $response->headers->set('Content-Type', 'application/json');
                          return $response; */
                        $datenaissance = null;
                    }
                    //Si le prenom est vide
                    if ($prenom == "") {
                        //success = false car l'opération de création à échoué, serverError = false car ce n'est pas uen erreure côté serveur 
                        /* $return = array('success' => false, 'serverError' => false, 'message' => "Le prénom ne doît pas être vide");
                          $response = new Response(json_encode($return));
                          $response->headers->set('Content-Type', 'application/json');
                          return $response; */
                        $prenom = null;
                    }
                    //Si le telephone est vide
                    if ($telephone == "") {
                        //success = false car l'opération de création à échoué, serverError = false car ce n'est pas uen erreure côté serveur 
                        /* $return = array('success' => false, 'serverError' => false, 'message' => "Le telephone ne doît pas être vide");
                          $response = new Response(json_encode($return));
                          $response->headers->set('Content-Type', 'application/json');
                          return $response; */
                        $telephone = null;
                    }
                    if ($datenaissance != null) {
                        $datenaissance = DateTime::createFromFormat('d/m/Y', $datenaissance);
                        $date_errors = DateTime::getLastErrors();
                        if ($date_errors['warning_count'] + $date_errors['error_count'] > 0) {
                            $return = array('success' => false, 'serverError' => false, 'message' => "Le format de la date est invalide");
                            $response = new Response(json_encode($return));
                            $response->headers->set('Content-Type', 'application/json');
                            return $response;
                        }
                    }
                    $user->setEmail($email);
                    $user->setNom($nom);
                    $user->setPrenom($prenom);
                    $user->setDatenaissance($datenaissance);
                    $user->setTelephone($telephone);
                    // On définit le rôle de l'utilisateur (récupéré dans la base de donnée)
                    $user->setRole($role);
                    //On déclenche l'enregistrement dans la base de données
                    $manager->flush();
                    //On ajoute les modifications dans le serveur d'authentification (juste l'email)
                    $clientSOAP = new SoapClient(null, array(
                        'uri' => $this->container->getParameter("auth_server_host"),
                        'location' => $this->container->getParameter("auth_server_host"),
                        'trace' => 1,
                        'exceptions' => 1
                    ));
                    //On appel la méthode du webservice qui permet de modifier l'état de l'utilisateur
                    $response = $clientSOAP->__call('updateUser', array('id' => CustomCrypto::encrypt($user->getId()), 'email' => CustomCrypto::encrypt($user->getEmail()), 'server' => CustomCrypto::encrypt($_SERVER['SERVER_ADDR'])));
                    //L'utilisateur n'existe pas dans la base de données du serveur d'authentification
                    if ($response['error'] == true) {
                        $return = array('success' => false, 'serverError' => false, 'message' => $response['message']);
                        $response = new Response(json_encode($return));
                        $response->headers->set('Content-Type', 'application/json');
                        return $response;
                    }
                    //Tout s'est déroulé correctement
                    $return = array('success' => true, 'serverError' => false, 'message' => "L'utilisateur a été mis à jour");
                    $response = new Response(json_encode($return));
                    $response->headers->set('Content-Type', 'application/json');
                    return $response;
                }
            } catch (Exception $e) {
                $return = array('success' => false, 'serverError' => true, 'message' => $e->getMessage());
                $response = new Response(json_encode($return));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }
        } else {
            //La requête n'es pas une requête ajax, on envoie une erreur
            throw new NotFoundHttpException('Impossible de trouver la page demandée');
        }
    }
        /**
     * Fonction de connexion de l'utilisateur
     *
     * Cette méthode est appelée en ajax et requiert les paramètres suivants : 
     * 
     * <code>
     * _csrf_token : token csrf généré
     * _email : email de l'utilisateur à connecter
     * _password : mot de passe de l'utilisateur à connecter
     * </code>
     * 
     * @return string 
     *
     * JSON permettant de définir si l'utilisateur est connecté ou non
     *
     * Example en cas de succès :
     * 
     * <code>
     * {
     *     "success": true,
     *     "serverError": false,
     *     "message": "Message"
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
    public function logInAction() {
        $request = $this->getRequest();
        //On regarde qu'il s'agit bien d'une requête ajax
        if ($request->isXmlHttpRequest()) {
            try {
                if (!$this->isCsrfTokenValid('default', $request->get('_csrf_token'))) {
                    throw new Exception("CSRF TOKEN ATTAK MAGGLE", 500);
                }
                //On récupère l'email
                $email = $request->request->get('_email');
                //On récupère son mot de passe
                $password = $request->request->get('_password');
                if ($email == "" || $password == "") {
                    $return = array('success' => false, 'serverError' => false, 'message' => "Le nom d'utilisateur ou le mot de passe ne doivent pas être vide");
                    $response = new Response(json_encode($return));
                    $response->headers->set('Content-Type', 'application/json');
                    return $response;
                }
                //Ensuite on essaye de se connecter avec le webservice
                $clientSOAP = new SoapClient(null, array(
                    'uri' => $this->container->getParameter("auth_server_host"),
                    'location' => $this->container->getParameter("auth_server_host"),
                    'trace' => true,
                    'exceptions' => true
                ));
                //On appel la méthode du webservice qui permet de se connecter
                $response = $clientSOAP->__call('logUserIn', array('username' => CustomCrypto::encrypt($email), 'password' => CustomCrypto::encrypt($password), 'server' => CustomCrypto::encrypt($_SERVER['SERVER_ADDR'])));
                //L'utilisateur n'existe pas dans la base de données ou les identifiants sont incorrects
                if ($response['connected'] == false) {
                    $return = array('success' => false, 'serverError' => false, 'message' => $response['message']);
                    $response = new Response(json_encode($return));
                    $response->headers->set('Content-Type', 'application/json');
                    return $response;
                }
                //Le webservice possède bien un compte d'utilisateur pour les informations saisies.
                //Il faut donc vérifier si l'utilisateur existe dans la base de données de ce site
                $manager = $this->getDoctrine()->getManager();
                // On récupère le membre dans la base de données si il existe
                $userid = CustomCrypto::decrypt($response['userid']);
                $membre = $manager->getRepository("SiteCartoBundle:Utilisateur")->find($userid);
                //Si l'utilisateur n'existe pas dans notre base de données
                if (is_null($membre)) {
                    $return = array('success' => false, 'serverError' => false, 'message' => "Existe pas dans la bdd");
                    $response = new Response(json_encode($return));
                    $response->headers->set('Content-Type', 'application/json');
                    return $response;
                }
                //L'utilisateur existe dans notre base de données et il est connecté, on créé le cookie d'authentification
                setcookie($this->container->getParameter("carto_auth_cookie"), CustomCrypto::encrypt($membre->getId() . "/" . $membre->getEmail() . "/" . $membre->getRole()->getLabel()), 0, '/');
                $return = array('success' => true, 'serverError' => false);
                $response = new Response(json_encode($return));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            } catch (Exception $e) {
                //Il y a une erreur côté serveur
                $return = array('success' => false, 'serverError' => true, 'message' => $e->getMessage());
                $response = new Response(json_encode($return));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }
        } else {
            //La requête n'es pas une requête ajax, on envoie une erreur
            throw new NotFoundHttpException('Impossible de trouver la page demandée');
        }
    }
    /**
     * Fonction de déconnexion de l'utilisateur
     *
     * Cette méthode est appelée en GET et ne requiert aucuns paramètres : 
     * 
     * @return View 
     *
     * Redirige sur la page d'acceuil 
     * 
     */
    public function logOutAction() {
        $this->get('security.token_storage')->setToken(null);
        $this->get('request')->getSession()->invalidate();
        $response = new RedirectResponse($this->generateUrl('site_carto_homepage'));
        $response->headers->clearCookie($this->container->getParameter("carto_auth_cookie"));
        $response->headers->clearCookie("TrailAuthCookie");
        return $response;
    }
        /**
     * Fonction de changement du mot de passe de l'utilisateur
     *
     * Cette méthode est appelée en ajax et requiert les paramètres suivants : 
     * 
     * <code>
     * oldpassword : ancien mot de passe de l'utilisateur
     * newpassword : le nouveau mot de passe
     * </code>
     *
     * On vérifie que l'ancien mot de passe est juste, ensuite on change le mot de passe.
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
     *     "message": "Message"
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
    public function changePasswordAction() {
        $request = $this->getRequest();
        //On regarde qu'il s'agit bien d'une requête ajax
        if ($request->isXmlHttpRequest()) {
            try {
                if ($this->get('security.context')->isGranted('IS_AUTHENTICATED_FULLY')) {
                    //On récupère l'ancien mot de passe
                    $oldpassword = $request->request->get('oldpassword');
                    //On récupère le nouveau mot de passe
                    $newpassword = $request->request->get('newpassword');
                    //On récupère l'email de l'utilisateur actuel
                    $email = $this->getUser()->getEmail();
                    //On récupère son id
                    $id = $this->getUser()->getId();
                    
                    if ($oldpassword == "" || $newpassword == "") {
                        $return = array('success' => false, 'serverError' => false, 'message' => "Veuillez remplir le formulaire");
                        $response = new Response(json_encode($return));
                        $response->headers->set('Content-Type', 'application/json');
                        return $response;
                    }
                    //Ensuite on essaye de se connecter avec le webservice
                    $clientSOAP = new SoapClient(null, array(
                        'uri' => $this->container->getParameter("auth_server_host"),
                        'location' => $this->container->getParameter("auth_server_host"),
                        'trace' => true,
                        'exceptions' => true
                    ));
                    //On appel la méthode du webservice qui permet de se connecter
                    $response = $clientSOAP->__call('logUserIn', array('username' => CustomCrypto::encrypt($email), 'password' => CustomCrypto::encrypt($oldpassword), 'server' => CustomCrypto::encrypt($_SERVER['SERVER_ADDR'])));
                    //L'utilisateur n'existe pas dans la base de données ou les identifiants sont incorrects
                    if ($response['connected'] == false) {
                        $return = array('success' => false, 'serverError' => false, 'message' => "Le mot de passe renseigné est invalide");
                        $response = new Response(json_encode($return));
                        $response->headers->set('Content-Type', 'application/json');
                        return $response;
                    }
                    //Le webservice possède bien un compte d'utilisateur pour les informations saisies.
                    //Maintenant il faut changer le mot de passe
                    
                    $clientSOAP = new SoapClient(null, array(
                        'uri' => $this->container->getParameter("auth_server_host"),
                        'location' => $this->container->getParameter("auth_server_host"),
                        'trace' => 1,
                        'exceptions' => 1
                    ));
                    //On appel la méthode du webservice qui permet de modifier l'état de l'utilisateur
                    $response = $clientSOAP->__call('changePassword', array('id' => CustomCrypto::encrypt($id), 'newpassword' => CustomCrypto::encrypt($newpassword), 'server' => CustomCrypto::encrypt($_SERVER['SERVER_ADDR'])));
                    //L'utilisateur n'existe pas dans la base de données du serveur d'authentification
                    if ($response['error'] == true) {
                        $return = array('success' => false, 'serverError' => false, 'message' => $response['message']);
                        $response = new Response(json_encode($return));
                        $response->headers->set('Content-Type', 'application/json');
                        return $response;
                    }
                    
                    $return = array('success' => true, 'serverError' => false);
                    $response = new Response(json_encode($return));
                    $response->headers->set('Content-Type', 'application/json');
                    return $response;
                }
            } catch (Exception $e) {
                //Il y a une erreur côté serveur
                $return = array('success' => false, 'serverError' => true, 'message' => $e->getMessage());
                $response = new Response(json_encode($return));
                $response->headers->set('Content-Type', 'application/json');
                return $response;
            }
        } else {
            //La requête n'es pas une requête ajax, on envoie une erreur
            throw new NotFoundHttpException('Impossible de trouver la page demandée');
        }
    }
    /**
     * Fonction permettant d'afficher l'annuaire
     *
     * Cette méthode est appelée en GET et ne requiert aucuns paramètres.
     * 
     * @return View 
     *
     * Redirige sur la page de l'annuaire
     * 
     * 
     */
    public function annuaireAction() {
        $content = $this->get("templating")->render("SiteCartoBundle:User:annuaire.html.twig");
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