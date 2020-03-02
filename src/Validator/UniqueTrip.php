<?php

namespace App\Validator;

use App\Entity\Trip;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class UniqueTrip extends Constraint
{
    /*
     * Any public properties become valid options for the annotation.
     * Then, use these in your validator class.
     */
    public $message = 'The trip name: "{{ value }}" is already used.';

    /**
     * When Trip entity is passed, that means we expect updating method (PUT).
     *
     * @var Trip|null
     */
    public $tripEntity = null;

    public function __construct($options = null)
    {
        parent::__construct(null);

        $this->tripEntity = $options['trip_entity'];
    }


}
