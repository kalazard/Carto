<?php

namespace Site\CartoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * Segment
 *
 * @ORM\Table(name="segment", indexes={@ORM\Index(name="fk_segment_trace1_idx", columns={"pog1"}), @ORM\Index(name="fk_segment_coordonnees1_idx", columns={"pog2"})})
 * @ORM\Entity
 */
class Segment implements JsonSerializable
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
     * @ORM\Column(name="sens", type="integer", nullable=false)
     */
    private $sens;

    /**
     * @var linestring
     *
     * @ORM\Column(name="trace", type="linestring", nullable=false)
     */
    private $trace;

    /**
     * @var string
     *
     * @ORM\Column(name="elevation", type="text", nullable=false)
     */
    private $elevation;

    /**
     * @var \Point
     *
     * @ORM\ManyToOne(targetEntity="Point")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="pog1", referencedColumnName="id")
     * })
     */
    private $pog1;

    /**
     * @var \Point
     *
     * @ORM\ManyToOne(targetEntity="Point")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="pog2", referencedColumnName="id")
     * })
     */
    private $pog2;

    /**
     * @var \Doctrine\Common\Collections\Collection
     *
     * @ORM\ManyToMany(targetEntity="Point", mappedBy="idsegment")
     */
    //private $idpop;

    /**
     * Constructor
     */
    /*public function __construct()
    {
        $this->idpop = new \Doctrine\Common\Collections\ArrayCollection();
    }*/


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
     * Set sens
     *
     * @param integer $sens
     * @return Segment
     */
    public function setSens($sens)
    {
        $this->sens = $sens;

        return $this;
    }

    /**
     * Get sens
     *
     * @return integer 
     */
    public function getSens()
    {
        return $this->sens;
    }

    /**
     * Set trace
     *
     * @param linestring $trace
     * @return Segment
     */
    public function setTrace($trace)
    {
        $this->trace = $trace;

        return $this;
    }

    /**
     * Get trace
     *
     * @return linestring 
     */
    public function getTrace()
    {
        return $this->trace;
    }

    /**
     * Set elevation
     *
     * @param string $elevation
     * @return Segment
     */
    public function setElevation($elevation)
    {
        $this->elevation = $elevation;

        return $this;
    }

    /**
     * Get elevation
     *
     * @return string 
     */
    public function getElevation()
    {
        return $this->elevation;
    }

    /**
     * Set pog1
     *
     * @param \Site\CartoBundle\Entity\Point $pog1
     * @return Segment
     */
    public function setPog1(\Site\CartoBundle\Entity\Point $pog1 = null)
    {
        $this->pog1 = $pog1;

        return $this;
    }

    /**
     * Get pog1
     *
     * @return \Site\CartoBundle\Entity\Point 
     */
    public function getPog1()
    {
        return $this->pog1;
    }

    /**
     * Set pog2
     *
     * @param \Site\CartoBundle\Entity\Point $pog2
     * @return Segment
     */
    public function setPog2(\Site\CartoBundle\Entity\Point $pog2 = null)
    {
        $this->pog2 = $pog2;

        return $this;
    }

    /**
     * Get pog2
     *
     * @return \Site\CartoBundle\Entity\Point 
     */
    public function getPog2()
    {
        return $this->pog2;
    }

    /**
     * Add idpop
     *
     * @param \Site\CartoBundle\Entity\Point $idpop
     * @return Segment
     */
    /*public function addIdpop(\Site\CartoBundle\Entity\Point $idpop)
    {
        $this->idpop[] = $idpop;

        return $this;
    }*/

    /**
     * Remove idpop
     *
     * @param \Site\CartoBundle\Entity\Point $idpop
     */
   /* public function removeIdpop(\Site\CartoBundle\Entity\Point $idpop)
    {
        $this->idpop->removeElement($idpop);
    }*/

    /**
     * Get idpop
     *
     * @return \Doctrine\Common\Collections\Collection 
     */
    /*public function getIdpop()
    {
        return $this->idpop;
    }*/

    public function jsonSerialize() {
        return array(
            'id' => $this->getId(),
            'sens'=> $this->getSens(),
            'trace' => $this->getTrace()->__toString(),
            'elevation' => $this->getElevation(),
            'pog1' => $this->getPog1(),
            'pog2' => $this->getPog2()
        );
    }
}
