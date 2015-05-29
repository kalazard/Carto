<?php

namespace Site\CartoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use JsonSerializable;

/**
 * Poi
 *
 * @ORM\Table(name="poi", indexes={@ORM\Index(name="fk_poi_typelieu1_idx", columns={"typelieu"}), @ORM\Index(name="fk_poi_coordonnees1_idx", columns={"coordonnees"}), @ORM\Index(name="fk_poi_image1_idx", columns={"image"})})
 * @ORM\Entity
 */
class Poi implements JsonSerializable
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
     * @ORM\Column(name="titre", type="string", length=255, nullable=true)
     */
    private $titre;

    /**
     * @var string
     *
     * @ORM\Column(name="description", type="string", length=255, nullable=true)
     */
    private $description;

    /**
     * @var \Typelieu
     *
     * @ORM\ManyToOne(targetEntity="Typelieu")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="typelieu", referencedColumnName="id")
     * })
     */
    private $typelieu;

    /**
     * @var \Coordonnees
     *
     * @ORM\ManyToOne(targetEntity="Coordonnees")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="coordonnees", referencedColumnName="id")
     * })
     */
    private $coordonnees;

    /**
     * @var \Image
     *
     * @ORM\ManyToOne(targetEntity="Image")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="image", referencedColumnName="id")
     * })
     */
    private $image;



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
     * Set titre
     *
     * @param string $titre
     * @return Poi
     */
    public function setTitre($titre)
    {
        $this->titre = $titre;

        return $this;
    }

    /**
     * Get titre
     *
     * @return string 
     */
    public function getTitre()
    {
        return $this->titre;
    }

    /**
     * Set description
     *
     * @param string $description
     * @return Poi
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
     * Set typelieu
     *
     * @param \Site\CartoBundle\Entity\Typelieu $typelieu
     * @return Poi
     */
    public function setTypelieu(\Site\CartoBundle\Entity\Typelieu $typelieu = null)
    {
        $this->typelieu = $typelieu;

        return $this;
    }

    /**
     * Get typelieu
     *
     * @return \Site\CartoBundle\Entity\Typelieu 
     */
    public function getTypelieu()
    {
        return $this->typelieu;
    }

    /**
     * Set coordonnees
     *
     * @param \Site\CartoBundle\Entity\Coordonnees $coordonnees
     * @return Poi
     */
    public function setCoordonnees(\Site\CartoBundle\Entity\Coordonnees $coordonnees = null)
    {
        $this->coordonnees = $coordonnees;

        return $this;
    }

    /**
     * Get coordonnees
     *
     * @return \Site\CartoBundle\Entity\Coordonnees 
     */
    public function getCoordonnees()
    {
        return $this->coordonnees;
    }

    /**
     * Set image
     *
     * @param \Site\CartoBundle\Entity\Image $image
     * @return Poi
     */
    public function setImage($image)
    {
        $this->image = $image;

        return $this;
    }

    /**
     * Get image
     *
     * @return \Site\CartoBundle\Entity\Image 
     */
    public function getImage()
    {
        return $this->image;
    }

        public function jsonSerialize() {
        return array(
            'id' => $this->getId(),
            'titre'=> $this->getTitre(),
            'description' => $this->getDescription(),
            'typelieu' => $this->getTypelieu(),
            'coordonnees' => $this->getCoordonnees(),
            'image' => $this->getImage(),
        );
    }
}
