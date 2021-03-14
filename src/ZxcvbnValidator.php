<?php

namespace REBELinBLUE\Zxcvbn;

use Illuminate\Translation\Translator;
use Illuminate\Validation\Validator;
use InvalidArgumentException;
use stdClass;
use ZxcvbnPhp\Matchers\BaseMatch;
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

    public function validate(...$args): bool
    {
        $value = trim($args[1]);

        /** @var array $parameters */
        $parameters = $args[2] ? $args[2] : [];

        /** @var Validator $validator */
        $validator = $args[3];

        $desiredScore = $this->getDesiredScore($parameters);
        $otherInput   = $this->getAdditionalInput($validator, $parameters);

        $zxcvbn   = new Zxcvbn();
        $strength = $zxcvbn->passwordStrength($value, $otherInput);

        $this->strength = $strength['score'];
        $this->result   = $strength;

        if ($strength['score'] >= $desiredScore) {
            return true;
        }

        $validator->setCustomMessages([
            'zxcvbn' => $this->translator->get('zxcvbn::validation.' . $this->getFeedbackTranslation()),
        ]);

        return false;
    }

    private function getDesiredScore(array $parameters = []): int
    {
        $desiredScore = isset($parameters[0]) ? $parameters[0] : self::DEFAULT_MINIMUM_STRENGTH;

        if ($desiredScore < 0 || $desiredScore > 4 || !ctype_digit($desiredScore)) {
            throw new InvalidArgumentException('The required password score must be between 0 and 4');
        }

        return $desiredScore;
    }

    private function getAdditionalInput(Validator $validator, array $parameters = []): array
    {
        $input      = $validator->getData();
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
        $isOnlyMatch = count($this->result['sequence']) === 1;

        $longestMatch        = new stdClass();
        $longestMatch->token = '';

        foreach ($this->result['sequence'] as $match) {
            if (strlen($match->token) > strlen($longestMatch->token)) {
                $longestMatch = $match;
            }
        }

        return $this->getMatchFeedback($longestMatch, $isOnlyMatch);
    }

    private function getMatchFeedback(BaseMatch $match, $isOnlyMatch)
    {
        $pattern  = mb_strtolower($match->pattern);
        $strategy = 'get' . ucfirst($pattern) . 'Warning';
        if (method_exists($this, $strategy)) {
            return $this->$strategy($match, $isOnlyMatch);
        }

        // ['digits', 'year', 'date', 'repeat', 'sequence']
        return mb_strtolower($pattern);
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function getDictionaryWarning(BaseMatch $match, bool $isOnlyMatch): string
    {
        if ($match->dictionaryName === 'passwords') {
            return $this->getPasswordWarning($match, $isOnlyMatch);
        }

        if (in_array($match->dictionaryName, ['surnames', 'male_names', 'female_names'], true)) {
            return 'names';
        }

        if ($match->dictionaryName === 'user_inputs') {
            return 'reused';
        }

        return 'common';
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function getRegexWarning(BaseMatch $match): string
    {
        $warning = 'year';

        if ($match->regexName === 'recent_year') {
            return 'year';
        }

        return $warning;
    }

    private function getPasswordWarning(BaseMatch $match, bool $isOnlyMatch): string
    {
        if (!$isOnlyMatch) {
            return 'suggestion';
        }

        if ($match->l33t) {
            return 'predictable';
        }

        if (isset($match->reversed) && $match->reversed === true && $match->rank <= 100) {
            return 'very_common';
        }

        if ($match->rank <= 10) {
            return 'top_10';
        }

        if ($match->rank <= 100) {
            return 'top_100';
        }

        return 'common';
    }

    /**
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function getSpatialWarning(BaseMatch $match): string
    {
        if ($match->turns === 1) {
            return 'straight_spatial';
        }

        return 'spatial_with_turns';
    }
}
