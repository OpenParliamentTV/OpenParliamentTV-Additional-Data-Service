<?php

class StringHelper
{
    public static function cleanCreator(string $creatorString): string
    {
        $cleanCreatorString = 'Wikimedia Commons';

        if ($creatorString === '') {
            return $cleanCreatorString;
        }

        $vcardCreatorRegex = '/id="creator">(<bdi>.+<\/bdi>)/';
        if (preg_match($vcardCreatorRegex, $creatorString, $matches)) {
            if (count($matches) > 0) {
                $creatorString = $matches[0];
            }
        }

        $creatorString = preg_replace('/\\\n/', ' ', $creatorString);
        $creatorString = preg_replace('/<(?!\/?a).*?>/', '', $creatorString);

        $cleanCreatorString = $creatorString;

        preg_match('/(<a href=".*wikimedia.*User:.*<\/a>)/Um', $cleanCreatorString, $matches);
        if (!empty($matches[1])) {
            $cleanCreatorString = $matches[1];
        } else {
            preg_match('/(<a href=".*wikimedia.*Creator:.*<\/a>)/Um', $cleanCreatorString, $matches);
            if (!empty($matches[1])) {
                $cleanCreatorString = $matches[1];
            }
        }

        return $cleanCreatorString;
    }

    public static function cleanLicense(string $licenseString): string
    {
        if ($licenseString === '') {
            return 'CC-BY-SA';
        }

        return $licenseString;
    }
}
