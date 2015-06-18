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
     * @ORM\Column(name="valeur", type="decimal", precision=2, scale=1, nullable=false)
     */
    private $valeur;



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
     * Set valeur
     *
     * @param string $valeur
     * @return Note
     */
    public function setValeur($valeur)
    {
        $this->valeur = $valeur;

        return $this;
    }

    /**
     * Get valeur
     *
     * @return string 
     */
    public function getValeur()
    {
        return $this->valeur;
    }
    
    public function jsonSerialize() {
        return array(
            'id' => $this->getId(),
            'valeur' => $this->getValeur()
        );
    }
}
