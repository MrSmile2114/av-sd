<?php

namespace App\Tests\Validator;

use App\Validator\Constraints\GeoCoordinate;
use App\Validator\Constraints\GeoCoordinateValidator;
use Symfony\Component\Validator\Constraints\NotNull;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;
use Symfony\Component\Validator\Test\ConstraintValidatorTestCase;

use function MongoDB\BSON\toJSON;

class GeoCoordinateValidatorTest extends ConstraintValidatorTestCase
{

    public function testNullIsValid()
    {
        $this->validator->validate(null, new GeoCoordinate());
        $this->assertNoViolation();
    }

    public function testEmptyStringIsValid()
    {
        $this->validator->validate('', new GeoCoordinate());
        $this->assertNoViolation();
    }

    public function testExpectsStringCompatibleType()
    {
        $this->expectException('Symfony\Component\Validator\Exception\UnexpectedValueException');
        $this->validator->validate(new \stdClass(), new GeoCoordinate());
    }

    /**
     * @dataProvider getValidGeoCoordinate
     * @param $coordinate
     */
    public function testValidGeoCoordinate($coordinate)
    {
        $this->validator->validate($coordinate, new GeoCoordinate());
        $this->assertNoViolation();
    }

    /**
     * @dataProvider getInvalidGeoCoordinate
     * @param $coordinate
     */
    public function testInvalidGeoCoordinate($coordinate)
    {
        $this->validator->validate($coordinate, new GeoCoordinate());
        $this->assertCount(1, $this->context->getViolations());
    }

    /**
     * @dataProvider getUnreachableGeoCoordinate
     * @param $coordinate
     * @param $type
     */
    public function testUnreachableGeoCoordinate($coordinate, $type)
    {
        $this->validator->validate($coordinate, new GeoCoordinate(['type' => $type]));
        $this->assertCount(1, $this->context->getViolations());
    }

    public function testInvalidType()
    {
        $this->expectException(InvalidOptionsException::class);
        $this->validator->validate("55.6565", new GeoCoordinate(['type' => '123']));
    }

    public function testUnexpectedConstraint()
    {
        $this->expectException(UnexpectedTypeException::class);
        $this->validator->validate("55.4545", new NotNull());
    }

    /**
     * Data Providers
     */

    public function getValidGeoCoordinate()
    {
        return [
            ['-55.77868792'],
            ['37.58800507'],
            ['55'],
            ['-55.000000'],
            [-55.000],
            [55],
            [55.4545455],
            [-54.4554],
            [-54],
        ];
    }

    public function getInvalidGeoCoordinate()
    {
        return [
            ['-gf55.77868792'],
            ['f37.58800507'],
            ['55,4'],
            ['=55.000000'],
            ['55.00f000'],
            ['55.00000d'],
        ];
    }

    public function getUnreachableGeoCoordinate()
    {
        return [
            ['-555.77868792', null],
            ['99.77868792', 'latitude'],
            ['-99.77868792', 'latitude'],
            ['99.77868792', 'latitude'],
            ['181.77868792', 'longitude'],
        ];
    }

    protected function createValidator()
    {
        return new GeoCoordinateValidator();
    }
}