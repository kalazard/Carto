<?php

namespace Site\CartoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * Utilisateur
 *
 * @ORM\Table(name="utilisateur", indexes={@ORM\Index(name="fk_utilisateur_role_idx", columns={"role"})})
 * @ORM\Entity
 */
class Utilisateur implements \Symfony\Component\Security\Core\User\UserInterface, \Serializable, JsonSerializable
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer", nullable=false)
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="IDENTITY")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", length=100, nullable=false)
     */
    private $email;

    /**
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=255, nullable=true)
     */
    private $nom;

    /**
     * @var string
     *
     * @ORM\Column(name="prenom", type="string", length=255, nullable=true)
     */
    private $prenom;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="datenaissance", type="date", nullable=true)
     */
    private $datenaissance;

    /**
     * @var string
     *
     * @ORM\Column(name="telephone", type="string", length=45, nullable=true)
     */
    private $telephone;

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
     * @ORM\ManyToMany(targetEntity="Itineraire", inversedBy="utilisateurid")
     * @ORM\JoinTable(name="utilisateurhasitineraire",
     *   joinColumns={
     *     @ORM\JoinColumn(name="utilisateurId", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="itineraireId", referencedColumnName="id")
     *   }
     * )
     */
    private $itineraireid;
    
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
        $this->itineraireid = new \Doctrine\Common\Collections\ArrayCollection();
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
     * Set nom
     *
     * @param string $nom
     * @return Utilisateur
     */
    public function setNom($nom)
    {
        $this->nom = $nom;

        return $this;
    }

    /**
     * Get nom
     *
     * @return string 
     */
    public function getNom()
    {
        return $this->nom;
    }

    /**
     * Set prenom
     *
     * @param string $prenom
     * @return Utilisateur
     */
    public function setPrenom($prenom)
    {
        $this->prenom = $prenom;

        return $this;
    }

    /**
     * Get prenom
     *
     * @return string 
     */
    public function getPrenom()
    {
        return $this->prenom;
    }
    
    public function getPassword() {
        
    }
    
    public function getSalt() {
        
    }
    
    public function eraseCredentials() {
        
    }
    
    public function getUsername() {
        return $this->email;
    }
    
    public function getRoles() {
        return $this->role->getRole();
    }

    /**
     * Set datenaissance
     *
     * @param \DateTime $datenaissance
     * @return Utilisateur
     */
    public function setDatenaissance($datenaissance)
    {
        $this->datenaissance = $datenaissance;

        return $this;
    }

    /**
     * Get datenaissance
     *
     * @return \DateTime 
     */
    public function getDatenaissance()
    {
        if($this->datenaissance == null)
        {
            return null;
        }
        else
        {
            return $this->datenaissance->format("d/m/Y");
        }
    }

    /**
     * Set telephone
     *
     * @param string $telephone
     * @return Utilisateur
     */
    public function setTelephone($telephone)
    {
        $this->telephone = $telephone;

        return $this;
    }

    /**
     * Get telephone
     *
     * @return string 
     */
    public function getTelephone()
    {
        return $this->telephone;
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
     * Add itineraireid
     *
     * @param \Site\CartoBundle\Entity\Itineraire $itineraireid
     * @return Utilisateur
     */
    public function addItineraireid(\Site\CartoBundle\Entity\Itineraire $itineraireid)
    {
        $this->itineraireid[] = $itineraireid;

        return $this;
    }

    /**
     * Remove itineraireid
     *
     * @param \Site\CartoBundle\Entity\Itineraire $itineraireid
     */
    public function removeItineraireid(\Site\CartoBundle\Entity\Itineraire $itineraireid)
    {
        $this->itineraireid->removeElement($itineraireid);
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

    /**
     * Get itineraireid
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getItineraireid()
    {
        return $this->itineraireid;
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

    public function jsonSerialize() {
        return array(
            'id' => $this->getId(),
            'email'=> $this->getEmail(),
            'nom' => $this->getNom(),
            'prenom' => $this->getPrenom(),
            'datenaissance' => $this->getDateNaissance(),
            'telephone' => $this->getTelephone(),
            'role' => $this->getRole()
        );
    }
}
