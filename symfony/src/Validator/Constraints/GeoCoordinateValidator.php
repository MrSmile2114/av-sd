<?php


namespace App\Validator\Constraints;


use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Exception\UnexpectedValueException;

final class GeoCoordinateValidator extends ConstraintValidator
{

    /**
     * @inheritDoc
     */
    public function validate($value, Constraint $constraint)
    {
        if (!$constraint instanceof GeoCoordinate) {
            throw new UnexpectedTypeException($constraint, GeoCoordinate::class);
        }

        // custom constraints should ignore null and empty values to allow
        // other constraints (NotBlank, NotNull, etc.) take care of that
        if (null === $value || '' === $value) {
            return;
        }

        if (!is_string($value) and !is_float($value) and !is_int($value)) {
            // throw this exception if your validator cannot handle the passed type so that it can be marked as invalid
            throw new UnexpectedValueException($value, 'string|float|int');
        }

        if (is_string($value) and !is_numeric($value)) {
            $this->context->buildViolation($constraint->messageInvalidFormat)
                ->setParameter('{{ string }}', $value)
                ->addViolation();
        }

        if (
            (($constraint->type == 'longitude') and (abs($value) > 180))
            or (($constraint->type == 'latitude') and (abs($value) > 90))
        ) {
            $this->context->buildViolation($constraint->messageInvalidValue)
                ->setParameter('{{ value }}', $value)
                ->setParameter('{{ type }}', $constraint->type)
                ->addViolation();
        }
    }
}