<?php

namespace App\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\InvalidOptionsException;

/**
 * @Annotation
 */
class GeoCoordinate extends Constraint
{
    public $messageInvalidFormat =
        'The string "{{ string }}" is in an invalid format: it can only contain numbers, period and minus sign.';

    public $messageInvalidValue = 'Invalid {{ type }} value ({{ value }})';

    public $type = 'longitude';

    public function __construct($options = null)
    {
        parent::__construct($options);
        //custom options:
        if (is_null($options) or !array_key_exists('type', $options) or is_null($options['type'])) {
            $this->type = 'longitude';
        } elseif (($options['type'] == 'latitude') or ($options['type'] == 'longitude')) {
            $this->type = $options['type'];
        } else {
            throw new InvalidOptionsException(
                'Option type is invalid. Available values: longitude, latitude', $options
            );
        }
    }
}