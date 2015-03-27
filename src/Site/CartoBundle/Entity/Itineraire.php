<?php

namespace Site\CartoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Itineraire
 *
 * @ORM\Table(name="itineraire", indexes={@ORM\Index(name="fk_itineraire_auteur_idx", columns={"auteur"}), @ORM\Index(name="fk_itineraire_diff_idx", columns={"difficulte"}), @ORM\Index(name="fk_itineraire_trace_idx", columns={"trace"})})
 * @ORM\Entity
 */
class Itineraire implements \JsonSerializable
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
     * @var \DateTime
     *
     * @ORM\Column(name="datecreation", type="date", nullable=false)
     */
    private $datecreation;

    /**
     * @var float
     *
     * @ORM\Column(name="longueur", type="float", precision=10, scale=0, nullable=false)
     */
    private $longueur;

    /**
     * @var float
     *
     * @ORM\Column(name="deniveleplus", type="float", precision=10, scale=0, nullable=false)
     */
    private $deniveleplus;

    /**
     * @var float
     *
     * @ORM\Column(name="denivelemoins", type="float", precision=10, scale=0, nullable=false)
     */
    private $denivelemoins;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=false)
     */
    private $description;

    /**
     * @var string
     *
     * @ORM\Column(name="nom", type="string", length=255, nullable=false)
     */
    private $nom;

    /**
     * @var integer
     *
     * @ORM\Column(name="numero", type="integer", nullable=false)
     */
    private $numero;

    /**
     * @var string
     *
     * @ORM\Column(name="typechemin", type="string", length=255, nullable=false)
     */
    private $typechemin;

    /**
     * @var string
     *
     * @ORM\Column(name="status", type="string", length=255, nullable=false)
     */
    private $status;

    /**
     * @var \Utilisateur
     *
     * @ORM\ManyToOne(targetEntity="Utilisateur")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="auteur", referencedColumnName="id")
     * })
     */
    private $auteur;

    /**
     * @var \Difficulteparcours
     *
     * @ORM\ManyToOne(targetEntity="Difficulteparcours")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="difficulte", referencedColumnName="id")
     * })
     */
    private $difficulte;

    /**
     * @var \Trace
     *
     * @ORM\ManyToOne(targetEntity="Trace")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="trace", referencedColumnName="id")
     * })
     */
    private $trace;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Utilisateur", inversedBy="itinerairenote")
     * @ORM\JoinTable(name="note",
     *   joinColumns={
     *     @ORM\JoinColumn(name="itinerairenote", referencedColumnName="id")
     *   },
     *   inverseJoinColumns={
     *     @ORM\JoinColumn(name="utilisateurnote", referencedColumnName="id")
     *   }
     * )
     */
    private $utilisateurnote;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->utilisateurnote = new \Doctrine\Common\Collections\ArrayCollection();
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

    /**
     * Set datecreation
     *
     * @param \DateTime $datecreation
     * @return Itineraire
     */
    public function setDatecreation($datecreation)
    {
        $this->datecreation = $datecreation;

        return $this;
    }

    /**
     * Get datecreation
     *
     * @return \DateTime 
     */
    public function getDatecreation()
    {
        return $this->datecreation;
    }

    /**
     * Set longueur
     *
     * @param float $longueur
     * @return Itineraire
     */
    public function setLongueur($longueur)
    {
        $this->longueur = $longueur;

        return $this;
    }

    /**
     * Get longueur
     *
     * @return float 
     */
    public function getLongueur()
    {
        return $this->longueur;
    }

    /**
     * Set deniveleplus
     *
     * @param float $deniveleplus
     * @return Itineraire
     */
    public function setDeniveleplus($deniveleplus)
    {
        $this->deniveleplus = $deniveleplus;

        return $this;
    }

    /**
     * Get deniveleplus
     *
     * @return float 
     */
    public function getDeniveleplus()
    {
        return $this->deniveleplus;
    }

    /**
     * Set denivelemoins
     *
     * @param float $denivelemoins
     * @return Itineraire
     */
    public function setDenivelemoins($denivelemoins)
    {
        $this->denivelemoins = $denivelemoins;

        return $this;
    }

    /**
     * Get denivelemoins
     *
     * @return float 
     */
    public function getDenivelemoins()
    {
        return $this->denivelemoins;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Itineraire
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * Get description
     *
     * @return string 
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Set nom
     *
     * @param string $nom
     * @return Itineraire
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
     * Set numero
     *
     * @param integer $numero
     * @return Itineraire
     */
    public function setNumero($numero)
    {
        $this->numero = $numero;

        return $this;
    }

    /**
     * Get numero
     *
     * @return integer 
     */
    public function getNumero()
    {
        return $this->numero;
    }

    /**
     * Set typechemin
     *
     * @param string $typechemin
     * @return Itineraire
     */
    public function setTypechemin($typechemin)
    {
        $this->typechemin = $typechemin;

        return $this;
    }

    /**
     * Get typechemin
     *
     * @return string 
     */
    public function getTypechemin()
    {
        return $this->typechemin;
    }

    /**
     * Set status
     *
     * @param string $status
     * @return Itineraire
     */
    public function setStatus($status)
    {
        $this->status = $status;

        return $this;
    }

    /**
     * Get status
     *
     * @return string 
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * Set auteur
     *
     * @param \Site\CartoBundle\Entity\Utilisateur $auteur
     * @return Itineraire
     */
    public function setAuteur(\Site\CartoBundle\Entity\Utilisateur $auteur = null)
    {
        $this->auteur = $auteur;

        return $this;
    }

    /**
     * Get auteur
     *
     * @return \Site\CartoBundle\Entity\Utilisateur 
     */
    public function getAuteur()
    {
        return $this->auteur;
    }

    /**
     * Set difficulte
     *
     * @param \Site\CartoBundle\Entity\Difficulteparcours $difficulte
     * @return Itineraire
     */
    public function setDifficulte(\Site\CartoBundle\Entity\Difficulteparcours $difficulte = null)
    {
        $this->difficulte = $difficulte;

        return $this;
    }

    /**
     * Get difficulte
     *
     * @return \Site\CartoBundle\Entity\Difficulteparcours 
     */
    public function getDifficulte()
    {
        return $this->difficulte;
    }

    /**
     * Set trace
     *
     * @param \Site\CartoBundle\Entity\Trace $trace
     * @return Itineraire
     */
    public function setTrace(\Site\CartoBundle\Entity\Trace $trace = null)
    {
        $this->trace = $trace;

        return $this;
    }

    /**
     * Get trace
     *
     * @return \Site\CartoBundle\Entity\Trace 
     */
    public function getTrace()
    {
        return $this->trace;
    }

    /**
     * Add utilisateurnote
     *
     * @param \Site\CartoBundle\Entity\Utilisateur $utilisateurnote
     * @return Itineraire
     */
    public function addUtilisateurnote(\Site\CartoBundle\Entity\Utilisateur $utilisateurnote)
    {
        $this->utilisateurnote[] = $utilisateurnote;

        return $this;
    }

    /**
     * Remove utilisateurnote
     *
     * @param \Site\CartoBundle\Entity\Utilisateur $utilisateurnote
     */
    public function removeUtilisateurnote(\Site\CartoBundle\Entity\Utilisateur $utilisateurnote)
    {
        $this->utilisateurnote->removeElement($utilisateurnote);
    }

    /**
     * Get utilisateurnote
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    public function getUtilisateurnote()
    {
        return $this->utilisateurnote;
    }

    public function jsonSerialize() {
        return array(
            'id' => $this->id,
            'nom'=> $this->getNom(),
        );
    }

}
