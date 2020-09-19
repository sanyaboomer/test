<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;

/**
 * @Annotation
 */
class IsCleanText extends Constraint
{
    public $message = 'the value {{ string }} contains html elements';

    public function validatedBy()
    {
        return \get_class($this).'Validator';
    }
}