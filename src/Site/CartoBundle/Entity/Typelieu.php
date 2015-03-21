<?php

namespace Site\CartoBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Typelieu
 *
 * @ORM\Table(name="typelieu", indexes={@ORM\Index(name="fk_typelieu_icone1_idx", columns={"icone"})})
 * @ORM\Entity
 */
class Typelieu
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
     * @ORM\Column(name="label", type="string", length=255, nullable=false)
     */
    private $label;

    /**
     * @var \Icone
     *
     * @ORM\ManyToOne(targetEntity="Icone")
     * @ORM\JoinColumns({
     *   @ORM\JoinColumn(name="icone", referencedColumnName="id")
     * })
     */
    private $icone;



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
     * Set label
     *
     * @param string $label
     * @return Typelieu
     */
    public function setLabel($label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * Get label
     *
     * @return string 
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * Set icone
     *
     * @param \Site\CartoBundle\Entity\Icone $icone
     * @return Typelieu
     */
    public function setIcone(\Site\CartoBundle\Entity\Icone $icone = null)
    {
        $this->icone = $icone;

        return $this;
    }

    /**
     * Get icone
     *
     * @return \Site\CartoBundle\Entity\Icone 
     */
    public function getIcone()
    {
        return $this->icone;
    }
}
