<?php

namespace App\Entity;

use App\Repository\LogUserActionRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=LogUserActionRepository::class)
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
     * @ORM\Column(type="string", length=255)
     */
    private $username;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $doi_deposit_fix;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $doi_deposit_version;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $action;

    /**
     * @ORM\Column(type="datetime")
     */
    private $date;

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

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): self
    {
        $this->date = $date;

        return $this;
    }
}
