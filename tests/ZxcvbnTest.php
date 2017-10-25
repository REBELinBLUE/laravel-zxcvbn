<?php

namespace REBELinBLUE\Zxcvbn\Tests;

use Illuminate\Validation\Factory;
use InvalidArgumentException;
use Orchestra\Testbench\TestCase;
use REBELinBLUE\Zxcvbn\ZxcvbnFacade as Zxcvbn;
use REBELinBLUE\Zxcvbn\ZxcvbnServiceProvider;
use ZxcvbnPhp\Zxcvbn as ZxcvbnPhp;

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

    public function testBinding()
    {
        $byName = $this->app->make('zxcvbn');
        $byClass = $this->app->make(ZxcvbnPhp::class);

        $this->assertInstanceOf(ZxcvbnPhp::class, $byName);
        $this->assertInstanceOf(ZxcvbnPhp::class, $byClass);
    }

    public function testFacadeCallsCorrectClass()
    {
        $result = Zxcvbn::passwordStrength('testing');

        $this->assertArrayHasKey('score', $result);
        $this->assertArrayHasKey('match_sequence', $result);
    }

    /**
     * @dataProvider scoreDataProvider
     */
    public function testItThrowsAnExceptionWhenConfiguredScoreIsInvalid($score)
    {
        $this->expectException(InvalidArgumentException::class);

        $data = ['password' => 'P@$$w0rd'];

        $validator = $this->factory->make($data, [
            'password' => 'zxcvbn:' . $score,
        ]);

        $validator->valid();
    }

    public function scoreDataProvider()
    {
        return [
            [-1],
            [5],
            ['invalid-score'],
        ];
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
            ['test123456', 'common'],               // Common
            ['poiuytghjkl', 'spatial_with_turns'],  // Simple keyboard pattern
            ['poiuyt`', 'straight_spatial'],        // Straight row of keys
            ['98761234', 'sequence'],               // Sequence of characters
            ['30/09/1983', 'dates'],                // Date
            ['StephenBall', 'names'],               // Name
            ['aaaaaaaaa', 'repeat'],                // Repeating characters
            ['password', 'top_10'],                 // Top 10 password
            ['trustno1', 'top_100'],                // Top 100 password
            ['drowssap', 'very_common'],            // Simple reversal of one of the top passwords
            ['P4$$w0rd', 'predictable'],            // Predictable "l33t" substitutions
            ['seriously', 'common'],                // Dictionary word
            [date('Y'), 'years']                    // Recent year
            // ['crkuw297', 'digits'],
        ];
    }
}
