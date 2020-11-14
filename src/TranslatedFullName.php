<?php
declare(strict_types=1);

namespace App;

class TranslatedFullName
{
    private $firstName;

    private $lastName;

    private $middleName;

    public function __construct(string $raw, array $translations)
    {
        $raw = str_replace('  ', ' ', $raw);

        $chunks = explode(' ', $raw);
        if (count($chunks) === 2 && substr_count($chunks[1], '.') > 1) {
            [$chunks[1], $chunks[2]] = explode('.', $chunks[1]);
        }

        $chunks = array_map(
            static fn(string $token) : string => trim(str_replace(['.'], '', $token)),
            $chunks
        );

        $this->lastName   = $chunks[0] ?? '';
        $this->firstName  = $chunks[1] ?? '';
        $this->middleName = $chunks[2] ?? '';

        $this->lastName  = $translations['last_name'][$this->lastName] ?? strtr($this->lastName,
                [
                    'ё' => 'е',
                ]
            );
        $this->firstName = $translations['first_name'][$this->firstName] ?? strtr(
                $this->firstName,
                ['ё' => 'е']
            );
    }

    public function firstName() : string
    {
        return $this->firstName;
    }

    public function lastName() : string
    {
        return $this->lastName;
    }

    public function middleName() : string
    {
        return $this->middleName;
    }

    public function toString() : string
    {
        return implode(' ', [$this->lastName, $this->firstName, $this->middleName]);
    }

    public function hasSameFirstNameFirstLetter(string $compare) : bool
    {
        return mb_substr($this->firstName, 0, 1) === mb_substr($compare, 0, 1);
    }
}
