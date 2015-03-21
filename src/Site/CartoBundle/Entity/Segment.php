<?php

namespace Site\CartoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Segment
 *
 * @ORM\Table(name="segment", indexes={@ORM\Index(name="fk_segment_trace1_idx", columns={"trace"}), @ORM\Index(name="fk_segment_coordonnees1_idx", columns={"coordonnees_debut"}), @ORM\Index(name="fk_segment_coordonnees2_idx", columns={"coordonnees_fin"})})
 * @ORM\Entity
 */
class Segment
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
     * @var \Trace
     *
     * @ORM\ManyToOne(targetEntity="Trace")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="trace", referencedColumnName="id")
     * })
     */
    private $trace;

    /**
     * @var \Coordonnees
     *
     * @ORM\ManyToOne(targetEntity="Coordonnees")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="coordonnees_debut", referencedColumnName="id")
     * })
     */
    private $coordonneesDebut;

    /**
     * @var \Coordonnees
     *
     * @ORM\ManyToOne(targetEntity="Coordonnees")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="coordonnees_fin", referencedColumnName="id")
     * })
     */
    private $coordonneesFin;



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
     * Set trace
     *
     * @param \Site\CartoBundle\Entity\Trace $trace
     * @return Segment
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
     * Set coordonneesDebut
     *
     * @param \Site\CartoBundle\Entity\Coordonnees $coordonneesDebut
     * @return Segment
     */
    public function setCoordonneesDebut(\Site\CartoBundle\Entity\Coordonnees $coordonneesDebut = null)
    {
        $this->coordonneesDebut = $coordonneesDebut;

        return $this;
    }

    /**
     * Get coordonneesDebut
     *
     * @return \Site\CartoBundle\Entity\Coordonnees 
     */
    public function getCoordonneesDebut()
    {
        return $this->coordonneesDebut;
    }

    /**
     * Set coordonneesFin
     *
     * @param \Site\CartoBundle\Entity\Coordonnees $coordonneesFin
     * @return Segment
     */
    public function setCoordonneesFin(\Site\CartoBundle\Entity\Coordonnees $coordonneesFin = null)
    {
        $this->coordonneesFin = $coordonneesFin;

        return $this;
    }

    /**
     * Get coordonneesFin
     *
     * @return \Site\CartoBundle\Entity\Coordonnees 
     */
    public function getCoordonneesFin()
    {
        return $this->coordonneesFin;
    }
}
