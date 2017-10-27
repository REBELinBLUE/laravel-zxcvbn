<?php

namespace REBELinBLUE\Zxcvbn;

use Illuminate\Translation\Translator;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use ZxcvbnPhp\Zxcvbn;

/**
 * Class for validating passwords using Dropbox's Zxcvbn library.
 */
class ZxcvbnValidator
{
    const DEFAULT_MINIMUM_STRENGTH = 3;

    /** @var array|null */
    private $result;

    /** @var int */
    private $strength = 0;

    /** @var Translator */
    private $translator;

    public function __construct(Translator $translator)
    {
        $this->translator = $translator;
    }

    public function validate(...$args)
    {
        /** @var string $value */
        $value = trim($args[1]);

        /** @var array $parameters */
        $parameters = $args[2] ? $args[2] : [];

        /** @var \Illuminate\Validation\Validator $validator */
        $validator = $args[3];

        $desiredScore = $this->getDesiredScore($parameters);
        $otherInput = $this->getAdditionalInput($validator, $parameters);

        $zxcvbn = new Zxcvbn();
        $strength = $zxcvbn->passwordStrength($value, $otherInput);

        $this->strength = $strength['score'];
        $this->result = $strength;

        if ($strength['score'] >= $desiredScore) {
            return true;
        }

        $validator->setCustomMessages([
            'zxcvbn' => $this->translator->get('zxcvbn::validation.' . $this->getFeedbackTranslation())
        ]);

        return false;
    }

    private function getDesiredScore(array $parameters = [])
    {
        $desiredScore = isset($parameters[0]) ? $parameters[0] : self::DEFAULT_MINIMUM_STRENGTH;

        if ($desiredScore < 0 || $desiredScore > 4 || !ctype_digit($desiredScore)) {
            throw new InvalidArgumentException('The required password score must be between 0 and 4');
        }

        return $desiredScore;
    }

    private function getAdditionalInput(Validator $validator, array $parameters = [])
    {
        $input = $validator->getData();

        $otherInput = [];
        foreach (array_slice($parameters, 1) as $attribute) {
            if (isset($input[$attribute])) {
                $otherInput[] = $input[$attribute];
            }
        }

        return $otherInput;
    }

    private function getFeedbackTranslation()
    {
        $isOnlyMatch = count($this->result['match_sequence']) === 1;

        $longestMatch = new \stdClass();
        $longestMatch->token = '';

        foreach ($this->result['match_sequence'] as $match) {
            if (strlen($match->token) > strlen($longestMatch->token)) {
                $longestMatch = $match;
            }
        }

        return $this->getMatchFeedback($longestMatch, $isOnlyMatch);
    }

    private function getMatchFeedback($match, $isOnlyMatch)
    {
        $pattern = strtolower($match->pattern);
        $strategy = 'get' . ucfirst($pattern) . 'Warning';

        if (method_exists($this, $strategy)) {
            return $this->$strategy($match, $isOnlyMatch);
        }

        // ['digits', 'year', 'date', 'repeat', 'sequence']
        return strtolower($pattern);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function getDictionaryWarning($match, $isOnlyMatch)
    {
        $warning = 'common'; // $match->dictionaryName == 'english'
        if ($match->dictionaryName === 'passwords') {
            $warning = $this->getPasswordWarning($match, $isOnlyMatch);
        } elseif (in_array($match->dictionaryName, ['surnames', 'male_names', 'female_names'])) {
            $warning = 'names';
        } elseif ($match->dictionaryName === 'user_inputs') {
            $warning = 'reused';
        }

        if (isset($match->l33t)) {
            $warning = 'predictable';
        }

        return $warning;
    }

    private function getPasswordWarning($match, $isOnlyMatch)
    {
        $warning = 'common';
        if ($isOnlyMatch && !isset($match->l33t) && !isset($match->reversed)) {
            $warning = 'very_common';

            if ($match->rank <= 10) {
                $warning = 'top_10';
            } elseif ($match->rank <= 100) {
                $warning = 'top_100';
            }
        }

        return $warning;
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function getSpatialWarning($match)
    {
        if ($match->turns === 1) {
            return 'straight_spatial';
        }

        return 'spatial_with_turns';
    }
}
