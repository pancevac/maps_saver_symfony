<?php


namespace App\Utils;


interface UserEmailActivationInterface
{
    /**
     * Get user status (activated/disabled).
     *
     * @return bool|null
     */
    public function getActive(): ?bool;

    /**
     * Set user status (enabled/disabled).
     *
     * @param bool $active
     * @return $this
     */
    public function setActive(bool $active): self;

    /**
     * Get user confirmation token for email activation.
     *
     * @return string|null
     */
    public function getConfirmationToken(): ?string;

    /**
     * Set user confirmation token for email activation.
     *
     * @param string|null $confirmationToken
     * @return $this
     */
    public function setConfirmationToken(?string $confirmationToken): self;
}