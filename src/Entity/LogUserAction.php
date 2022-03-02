<?php

namespace App\Entity;

use App\Repository\LogUserActionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LogUserActionRepository::class)
 * @ORM\Table(name="log_user_action", uniqueConstraints={@ORM\UniqueConstraint(name="idx_log_unique", columns={"username","doi_deposit_fix","doi_deposit_version"})})
 *
 */
class LogUserAction
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $doi_deposit_fix;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $doi_deposit_version;

    /**
     * @ORM\Column(type="string", length=50)
     */
    private $action;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created_date;

    /**
     * @ORM\Column(type="datetime")
     */
    private $updated_date;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     */
    private $zenodo_title;



    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUsername(): ?string
    {
        return $this->username;
    }

    public function setUsername(string $username): self
    {
        $this->username = $username;

        return $this;
    }

    public function getDoiDepositFix(): ?string
    {
        return $this->doi_deposit_fix;
    }

    public function setDoiDepositFix(string $doi_deposit_fix): self
    {
        $this->doi_deposit_fix = $doi_deposit_fix;

        return $this;
    }

    public function getDoiDepositVersion(): ?string
    {
        return $this->doi_deposit_version;
    }

    public function setDoiDepositVersion(string $doi_deposit_version): self
    {
        $this->doi_deposit_version = $doi_deposit_version;

        return $this;
    }

    public function getAction(): ?string
    {
        return $this->action;
    }

    public function setAction(string $action): self
    {
        $this->action = $action;

        return $this;
    }

    public function getCreatedDate(): ?\DateTimeInterface
    {
        return $this->created_date;
    }

    public function setCreatedDate(\DateTimeInterface $created_date): self
    {
        $this->created_date = $created_date;

        return $this;
    }

    public function getUpdatedDate(): ?\DateTimeInterface
    {
        return $this->updated_date;
    }

    public function setUpdatedDate(\DateTimeInterface $updated_date): self
    {
        $this->updated_date = $updated_date;

        return $this;
    }

    public function getZenodoTitle(): ?string
    {
        return $this->zenodo_title;
    }

    public function setZenodoTitle(?string $zenodo_title): self
    {
        $this->zenodo_title = $zenodo_title;

        return $this;
    }

}
