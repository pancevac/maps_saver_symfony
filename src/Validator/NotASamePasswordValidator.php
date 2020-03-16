<?php

namespace App\Validator;

use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class NotASamePasswordValidator extends ConstraintValidator
{
    /**
     * @var UserPasswordEncoderInterface
     */
    private $passwordEncoder;

    public function __construct(UserPasswordEncoderInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function validate($value, Constraint $constraint)
    {
        /* @var $constraint \App\Validator\NotASamePassword */

        if (null === $value || '' === $value) {
            return;
        }

        // check if new password match the old one
        $isSame = $this->passwordEncoder->isPasswordValid($constraint->user, $value);

        if ($isSame) {
            $this->context->buildViolation($constraint->message)
                ->setParameter('{{ value }}', $value)
                ->addViolation();
        }
    }
}
