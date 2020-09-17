<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class IsNumber extends Constraint
{
    public $message = 'the value {{ string }} is not a number';

    public function validatedBy()
    {
        return \get_class($this).'Validator';
    }
}