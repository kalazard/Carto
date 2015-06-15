<?php

namespace Site\CartoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * Note
 *
 * @ORM\Table(name="note")
 * @ORM\Entity
 */
class Note implements JsonSerializable
{
    /**
     * @var \Itineraire
     *
     * @ORM\ManyToOne(targetEntity="Itineraire")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="itineraire", referencedColumnName="id")
     */
    private $itineraire;

    /**
     * @var \Utilisateur
     *
     * @ORM\ManyToOne(targetEntity="Utilisateur")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="utilisateur", referencedColumnName="id")
     */
    private $utilisateur;
    
    /**
     * @var decimal
     *
     * @ORM\Column(name="note", type="decimal", nullable=false)
     */
    private $note;

    /**
     * Set itineraire
     *
     * @param \Itineraire $itineraire
     * @return \Itineraire
     */
    public function setItineraire($itineraire)
    {
        $this->itineraire = $itineraire;

        return $this;
    }

    /**
     * Get itineraire
     *
     * @return \Itineraire 
     */
    public function getItineraire()
    {
        return $this->itineraire;
    }
    
    /**
     * Set utilisateur
     *
     * @param \Utilisateur $utilisateur
     * @return \Utilisateur
     */
    public function setUtilisateur($utilisateur)
    {
        $this->utilisateur = $utilisateur;

        return $this;
    }

    /**
     * Get utilisateur
     *
     * @return \Utilisateur 
     */
    public function getUtilisateur()
    {
        return $this->utilisateur;
    }
    
    /**
     * Set note
     *
     * @param decimal $note
     * @return decimal
     */
    public function setNote($note)
    {
        $this->note = $note;

        return $this;
    }

    /**
     * Get note
     *
     * @return decimal 
     */
    public function getNote()
    {
        return $this->note;
    }

    public function jsonSerialize() {
        return array(
            'itineraire' => $this->getItineraire(),
            'utilisateur'=> $this->getUtilisateur(),
            'note'=> $this->getLabel()
        );
    }

}
