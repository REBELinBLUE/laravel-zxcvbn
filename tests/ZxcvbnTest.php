<?php

namespace REBELinBLUE\Zxcvbn\Tests;

use Illuminate\Validation\Factory;
use Orchestra\Testbench\TestCase;
use REBELinBLUE\Zxcvbn\ZxcvbnFacade as Zxcvbn;
use REBELinBLUE\Zxcvbn\ZxcvbnServiceProvider;

class ZxcvbnTest extends TestCase
{
    /** @var Factory */
    private $factory;

    public function setUp()
    {
        parent::setUp();

        $this->factory = $this->app->make('validator');
    }

    protected function getPackageProviders($app)
    {
        return [
            ZxcvbnServiceProvider::class,
        ];
    }

    protected function getPackageAliases($app)
    {
        return [
            'Zxcvbn' => Zxcvbn::class
        ];
    }

    public function testFacadeCallsCorrectClass()
    {
        $result = Zxcvbn::passwordStrength('testing');

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('match_sequence', $result);
    }

    public function testItFailsOnGuessablePassword()
    {
        $data = ['password' => 'P@$$w0rd'];

        $validator = $this->factory->make($data, [
            'password' => 'zxcvbn:3',
        ]);

        $this->assertFalse($validator->passes());
    }

    public function testItPassesOnSecurePassword()
    {
        $data = ['password' => 'ebpHCVgFk@bZaj+NM4nppYrM'];

        $validator = $this->factory->make($data, [
            'password' => 'zxcvbn:3',
        ]);

        $this->assertTrue($validator->passes());
    }

    /**
     * @dataProvider validationDataProvider
     */
    public function testItSetsTheCorrectErrorOnFailure($value, $expected)
    {
        $translator = $this->app->make('translator');

        $data = ['password' => $value];

        $validator = $this->factory->make($data, [
            'password' => 'zxcvbn:3',
        ]);

        $this->assertFalse($validator->passes());

        $this->assertSame(
            $translator->get('zxcvbn::validation.' . $expected),
            $validator->errors()->first()
        );
    }

    public function validationDataProvider()
    {
        return [
            ['test123456', 'common'],
            ['poiuytghjkl', 'spatial_with_turns'],
            ['poiuyt`', 'straight_spatial'],
            ['98761234', 'sequence'],
            ['30/09/1983', 'dates'],
            ['StephenBall', 'names'],
            ['aaaaaaaaa', 'repeat'],
            ['password', 'top_10'],
            ['trustno1', 'top_100'],
            ['drowssap', 'very_common'],
            ['P4$$w0rd', 'predictable'],
            //['crkuw297', 'Adding a series of digits does not improve security'],
            [date('Y'), 'years']
        ];
    }
}
