<?php

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

        $chunks = array_map(static function (string $token) : string {
            return trim(str_replace(['.'], '', $token));
        }, $chunks);

        $this->lastName   = $chunks[0] ?? '';
        $this->firstName  = $chunks[1] ?? '';
        $this->middleName = $chunks[2] ?? '';

        $this->lastName  = $translations['last_name'][$this->lastName] ?? str_replace(['і'], 'и', $this->lastName);
        $this->firstName = $translations['first_name'][$this->firstName] ?? str_replace(['і'], 'и', $this->firstName);
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
}
