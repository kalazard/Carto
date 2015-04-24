<?php

namespace Site\CartoBundle\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use \Site\CartoBundle\Entity\Utilisateur;

class CustomUserProvider implements UserProviderInterface {

    protected $entityManager;

    public function __construct(\Doctrine\ORM\EntityManager $em) {
        $this->entityManager = $em;
    }

    public function getCookieToken() {
        //Permet de récupérer l'id de l'utilisateur dans le cookie !        
        if (isset($_COOKIE["TrailAuthCookie"])) {
            $cookie = CustomCrypto::decrypt($_COOKIE["TrailAuthCookie"]);
            return intval(explode("/", $cookie)[0]);
        } else {
            return false;
        }
    }

    public function loadUserByUsername($userid) {

        // On récupère le membre dans la base de données si il existe
        $utilisateur = $this->entityManager->getRepository("SiteCartoBundle:Utilisateur")->find($userid);
        if ($utilisateur != null) {
            if (isset($_COOKIE["TrailAuthCookie"])) {
                $cookie = CustomCrypto::decrypt($_COOKIE["TrailAuthCookie"]);
                $email = explode("/", $cookie)[1]; //Permet d'avoir l'email de l'utilisateur stocké dans le cookie
                if ($email != $utilisateur->getEmail()) {
                    $utilisateur->setEmail($email);
                    $this->entityManager->flush();
                }
            }
        }
        return $utilisateur;
    }

    public function createNewUser($userid) {
        $cookie = CustomCrypto::decrypt($_COOKIE["TrailAuthCookie"]);
        $email = explode("/", $cookie)[1];
        $role_label = explode("/", $cookie)[2];
        $role = $this->entityManager->getRepository("SiteCartoBundle:Role")->findOneBy(array('label' => $role_label));
        $utilisateur = new Utilisateur();
        $utilisateur->setId($userid);
        $utilisateur->setEmail($email);
        $utilisateur->setRole($role);
        $this->entityManager->persist($utilisateur);
        $this->entityManager->flush();

        return $utilisateur;
    }

    public function refreshUser(UserInterface $user) {
        // this is used for storing authentication in the session
        // but in this example, the token is sent in each request,
        // so authentication can be stateless. Throwing this exception
        // is proper to make things stateless

        if (isset($_COOKIE["TrailAuthCookie"])) {
            return $user;
        } else {
            throw new UnsupportedUserException();
        }

        //throw new UnsupportedUserException();
    }

    public function supportsClass($class) {
        return 'Site\CartoBundle\Entity\Utilisateur' === $class;
    }

}
