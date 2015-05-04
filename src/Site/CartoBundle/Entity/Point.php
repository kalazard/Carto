<?php

namespace Site\CartoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * Poi
 *
 * @ORM\Table(name="point", indexes={@ORM\Index(name="fk_point_coords_idx", columns={"coords"})})
 * @ORM\Entity
 */
class Point implements JsonSerializable
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
     * @var integer
     *
     * @ORM\Column(name="ordre", type="integer", nullable=true)
     */
    private $ordre;

    /**
     * @var \Coordonnees
     *
     * @ORM\ManyToOne(targetEntity="Coordonnees")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="coords", referencedColumnName="id")
     * })
     */
    private $coords;



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
     * Set ordre
     *
     * @param integer $ordre
     * @return Point
     */
    public function setOrdre($ordre)
    {
        $this->ordre = $ordre;

        return $this;
    }

    /**
     * Get ordre
     *
     * @return integer 
     */
    public function getOrdre()
    {
        return $this->ordre;
    }

    /**
     * Set coords
     *
     * @param \Site\CartoBundle\Entity\Coordonnees $coords
     * @return Point
     */
    public function setCoords(\Site\CartoBundle\Entity\Coordonnees $coords = null)
    {
        $this->coords = $coords;

        return $this;
    }

    /**
     * Get coords
     *
     * @return \Site\CartoBundle\Entity\Coordonnees 
     */
    public function getCoords()
    {
        return $this->coords;
    }

        public function jsonSerialize() {
        return array(
            'id' => $this->getId(),
            'ordre'=> $this->getOrdre(),
            'coordonnees' => $this->getCoords()
        );
    }
}
