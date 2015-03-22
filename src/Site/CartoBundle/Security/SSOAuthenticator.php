<?php
namespace Site\CartoBundle\Security;
use Symfony\Component\Security\Core\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\Exception\BadCredentialsException;
use \Site\CartoBundle\Entity\Utilisateur;
class SSOAuthenticator implements SimplePreAuthenticatorInterface
{
    protected $userProvider;
    protected $em;

    public function __construct(CustomUserProvider $userProvider)
    {
        $this->userProvider = $userProvider;
        
    }

    public function createToken(Request $request, $providerKey)
    {
        
        return new PreAuthenticatedToken("",null,$providerKey,array());
    }

    public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
    {
        
        //Récupère l'id de l'utilisateur dans le cookie
        $userid = $this->userProvider->getCookieToken();
        
        //Si le cookie n'existe pas !
        if (!$userid) {
            return new \Symfony\Component\Security\Core\Authentication\Token\AnonymousToken($providerKey,"anon.");
        }
        
        //On récupère l'utilisateur dans la base de données
        $utilisateur = new Utilisateur();
        $utilisateur = $this->userProvider->loadUserByUsername($userid);
        if($utilisateur == null)
        {
            return new \Symfony\Component\Security\Core\Authentication\Token\AnonymousToken($providerKey,"anon.");
        }
        //IMPORTANT 
        $token = new UsernamePasswordToken(
            $utilisateur,
            null,
            $providerKey,
            $utilisateur->getRoles()
        );
        
        return $token;
    }

    public function supportsToken(TokenInterface $token, $providerKey)
    {
        return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
    }
}