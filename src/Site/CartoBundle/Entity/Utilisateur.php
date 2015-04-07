<?php

namespace Site\CartoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Utilisateur
 *
 * @ORM\Table(name="utilisateur", indexes={@ORM\Index(name="fk_utilisateur_role_idx", columns={"role"})})
 * @ORM\Entity
 */
class Utilisateur implements \Symfony\Component\Security\Core\User\UserInterface, \Serializable
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * 
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=100, nullable=false)
     */
    private $email;

    /**
     * @var \Role
     *
     * @ORM\ManyToOne(targetEntity="Role")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="role", referencedColumnName="id")
     * })
     */
    private $role;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Itineraire", mappedBy="utilisateurnote")
     */
    private $itinerairenote;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->itinerairenote = new \Doctrine\Common\Collections\ArrayCollection();
    }


    /**
     * Get id
     *
     * @return integer 
     */
    public function getId()
    {
        return $this->id;
    }
    
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Set email
     *
     * @param string $email
     * @return Utilisateur
     */
    public function setEmail($email)
    {
        $this->email = $email;

        return $this;
    }

    /**
     * Get email
     *
     * @return string 
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * Set role
     *
     * @param \Site\CartoBundle\Entity\Role $role
     * @return Utilisateur
     */
    public function setRole(\Site\CartoBundle\Entity\Role $role = null)
    {
        $this->role = $role;

        return $this;
    }

    /**
     * Get role
     *
     * @return \Site\CartoBundle\Entity\Role 
     */
    public function getRole()
    {
        return $this->role;
    }

    /**
     * Add itinerairenote
     *
     * @param \Site\CartoBundle\Entity\Itineraire $itinerairenote
     * @return Utilisateur
     */
    public function addItinerairenote(\Site\CartoBundle\Entity\Itineraire $itinerairenote)
    {
        $this->itinerairenote[] = $itinerairenote;

        return $this;
    }

    /**
     * Remove itinerairenote
     *
     * @param \Site\CartoBundle\Entity\Itineraire $itinerairenote
     */
    public function removeItinerairenote(\Site\CartoBundle\Entity\Itineraire $itinerairenote)
    {
        $this->itinerairenote->removeElement($itinerairenote);
    }

    /**
     * Get itinerairenote
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getItinerairenote()
    {
        return $this->itinerairenote;
    }

    public function eraseCredentials() {
        
    }

    public function getPassword() {
        
    }

    public function getRoles() {
        return $this->role->getRole();
    }

    public function getSalt() {
        
    }

    public function getUsername() {
        return $this->email;
    }

    public function serialize() {
        return serialize(array(
            $this->id,
            $this->email,
            $this->role
            
            // see section on salt below
            // $this->salt,
        ));
    }

    public function unserialize($serialized) {
        list (
            $this->id,
            $this->email,
            $this->role
            // see section on salt below
            // $this->salt
        ) = unserialize($serialized);
    }

}
