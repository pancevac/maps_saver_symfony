<?php

namespace App\Validator;

use App\Entity\User;
use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class NotASamePassword extends Constraint
{
    /*
     * Any public properties become valid options for the annotation.
     * Then, use these in your validator class.
     */
    public $message = 'The new password is same as old password.';

    /**
     * @var User
     */
    public $user;

    public function __construct($options = null)
    {
        parent::__construct($options);

        $this->user = $options['user'];
    }
}
