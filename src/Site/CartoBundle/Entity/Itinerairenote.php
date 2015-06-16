<?php

namespace Site\CartoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * Itinerairenote
 *
 * @ORM\Table(name="itinerairenote", indexes={@ORM\Index(name="fk_utilisateur_has_itineraire_itineraire2_idx", columns={"itineraireidnote"}), @ORM\Index(name="fk_utilisateur_has_itineraire_utilisateur2_idx", columns={"utilisateuridnote"}), @ORM\Index(name="fk_utilisateur_has_itineraire_note1_idx", columns={"noteid"})})
 * @ORM\Entity
 */
class Itinerairenote implements JsonSerializable
{
    /**
     * @var \Utilisateur
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="Utilisateur")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="utilisateuridnote", referencedColumnName="id")
     * })
     */
    private $utilisateuridnote;

    /**
     * @var \Itineraire
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="Itineraire")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="itineraireidnote", referencedColumnName="id")
     * })
     */
    private $itineraireidnote;

    /**
     * @var \Note
     *
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="NONE")
     * @ORM\OneToOne(targetEntity="Note")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="noteid", referencedColumnName="id")
     * })
     */
    private $noteid;



    /**
     * Set utilisateuridnote
     *
     * @param \Site\CartoBundle\Entity\Utilisateur $utilisateuridnote
     * @return Itinerairenote
     */
    public function setUtilisateuridnote(\Site\CartoBundle\Entity\Utilisateur $utilisateuridnote)
    {
        $this->utilisateuridnote = $utilisateuridnote;

        return $this;
    }

    /**
     * Get utilisateuridnote
     *
     * @return \Site\CartoBundle\Entity\Utilisateur 
     */
    public function getUtilisateuridnote()
    {
        return $this->utilisateuridnote;
    }

    /**
     * Set itineraireidnote
     *
     * @param \Site\CartoBundle\Entity\Itineraire $itineraireidnote
     * @return Itinerairenote
     */
    public function setItineraireidnote(\Site\CartoBundle\Entity\Itineraire $itineraireidnote)
    {
        $this->itineraireidnote = $itineraireidnote;

        return $this;
    }

    /**
     * Get itineraireidnote
     *
     * @return \Site\CartoBundle\Entity\Itineraire 
     */
    public function getItineraireidnote()
    {
        return $this->itineraireidnote;
    }

    /**
     * Set noteid
     *
     * @param \Site\CartoBundle\Entity\Note $noteid
     * @return Itinerairenote
     */
    public function setNoteid(\Site\CartoBundle\Entity\Note $noteid)
    {
        $this->noteid = $noteid;

        return $this;
    }

    /**
     * Get noteid
     *
     * @return \Site\CartoBundle\Entity\Note 
     */
    public function getNoteid()
    {
        return $this->noteid;
    }
    
    public function jsonSerialize() {
        return array(
            'utilisateuridnote' => $this->getUtilisateuridnote(),
            'itineraireidnote'=> $this->getItineraireidnote(),
            'noteid' => $this->getNoteid()
        );
    }
}
