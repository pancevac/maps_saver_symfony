<?php

namespace App\Validator;

use App\Repository\TripRepository;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class UniqueTripValidator extends ConstraintValidator
{
    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var TripRepository
     */
    private $tripRepository;

    public function __construct(TokenStorageInterface $tokenStorage, TripRepository $tripRepository)
    {
        $this->tokenStorage = $tokenStorage;
        $this->tripRepository = $tripRepository;
    }

    public function validate($value, Constraint $constraint)
    {
        /* @var $constraint \App\Validator\UniqueTrip */

        if (null === $value || '' === $value) {
            return;
        }

        if (!$constraint->tripEntity) {
            // get executed when creating trip
            // validate that there is no saved trip with given name for authenticated user
            $duplicate = $this->tripRepository->findOneBy([
                'name' => $value,
                'user' => $this->tokenStorage->getToken()->getUser()
            ]);
        } else {
            // otherwise execute when updating trip
            // validate that there is no saved trip with given name for authenticated user
            // and apply this for all trips except updating
            $duplicate = $this->tripRepository->findOneExcept($constraint->tripEntity->getId(), [
                'name' => $value,
                'user' => $this->tokenStorage->getToken()->getUser()
            ]);
        }

        if (!$duplicate) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ value }}', $value)
            ->addViolation();
    }
}
