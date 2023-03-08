<?php
/**
 * Get clean Wikimedia Commons creator
 *
 * @param string $creatorString
 * @return string
 */
function getCleanWikimediaCommonsCreator($creatorString) {
    
    $cleanCreatorString = "Wikimedia Commons";

    if (isset($creatorString) && $creatorString !== null && $creatorString !== "") {
        
        $vcardCreatorRegex = '/id=\"creator\">(<bdi>.+<\/bdi>)/';
        if (preg_match($vcardCreatorRegex, $creatorString, $matches)) {
            if (count($matches) > 0 )
            $creatorString = $matches[0];
        }

        $creatorString = preg_replace('/\\\n/', " ", $creatorString);
        $creatorString = preg_replace('/<(?!\/?a).*?>/', "", $creatorString);

        $cleanCreatorString = $creatorString;
        
    }

    return $cleanCreatorString;
}

/**
 * Get clean Wikimedia Commons license
 *
 * @param string $licenseString
 * @return string
 */
function getCleanWikimediaCommonsLicense($licenseString) {
    
    $cleanLicenseString = "CC-BY-SA";

    if (isset($licenseString) && $licenseString !== null && $licenseString !== "") {
        
        $cleanLicenseString = $licenseString;

    }

    return $cleanLicenseString;
}

/*
* Replaces special characters in a string with their "non-special" counterpart.
*
* Useful for friendly URLs.
*
* @access public
* @param string
* @return string
*/
function convertAccentsAndSpecialToNormal($string)
{
    $table = array(
        'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Ă' => 'A', 'Ā' => 'A', 'Ą' => 'A', 'Æ' => 'A', 'Ǽ' => 'A',
        'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'ă' => 'a', 'ā' => 'a', 'ą' => 'a', 'æ' => 'a', 'ǽ' => 'a',

        'Þ' => 'B', 'þ' => 'b', 'ß' => 's',

        'Ç' => 'C', 'Č' => 'C', 'Ć' => 'C', 'Ĉ' => 'C', 'Ċ' => 'C',
        'ç' => 'c', 'č' => 'c', 'ć' => 'c', 'ĉ' => 'c', 'ċ' => 'c',

        'Đ' => 'Dj', 'Ď' => 'D', 'Đ' => 'D',
        'đ' => 'dj', 'ď' => 'd',

        'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ĕ' => 'E', 'Ē' => 'E', 'Ę' => 'E', 'Ė' => 'E',
        'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ĕ' => 'e', 'ē' => 'e', 'ę' => 'e', 'ė' => 'e',

        'Ĝ' => 'G', 'Ğ' => 'G', 'Ǧ' => 'G', 'Ġ' => 'G', 'Ģ' => 'G',
        'ĝ' => 'g', 'ğ' => 'g', 'ǧ' => 'g', 'ġ' => 'g', 'ģ' => 'g',

        'Ĥ' => 'H', 'Ħ' => 'H',
        'ĥ' => 'h', 'ħ' => 'h',

        'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I', 'İ' => 'I', 'Ĩ' => 'I', 'Ī' => 'I', 'Ĭ' => 'I', 'Į' => 'I',
        'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i', 'į' => 'i', 'ĩ' => 'i', 'ī' => 'i', 'ĭ' => 'i', 'ı' => 'i',

        'Ĵ' => 'J',
        'ĵ' => 'j',

        'Ķ' => 'K',
        'ķ' => 'k', 'ĸ' => 'k',

        'Ĺ' => 'L', 'Ļ' => 'L', 'Ľ' => 'L', 'Ŀ' => 'L', 'Ł' => 'L',
        'ĺ' => 'l', 'ļ' => 'l', 'ľ' => 'l', 'ŀ' => 'l', 'ł' => 'l',

        'Ñ' => 'N', 'Ń' => 'N', 'Ň' => 'N', 'Ņ' => 'N', 'Ŋ' => 'N',
        'ñ' => 'n', 'ń' => 'n', 'ň' => 'n', 'ņ' => 'n', 'ŋ' => 'n', 'ŉ' => 'n',

        'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ø' => 'O', 'Ō' => 'O', 'Ŏ' => 'O', 'Ő' => 'O', 'Œ' => 'O',
        'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ø' => 'o', 'ō' => 'o', 'ŏ' => 'o', 'ő' => 'o', 'œ' => 'o', 'ð' => 'o',

        'Ŕ' => 'R', 'Ř' => 'R',
        'ŕ' => 'r', 'ř' => 'r', 'ŗ' => 'r',

        'Š' => 'S', 'Ŝ' => 'S', 'Ś' => 'S', 'Ş' => 'S',
        'š' => 's', 'ŝ' => 's', 'ś' => 's', 'ş' => 's',

        'Ŧ' => 'T', 'Ţ' => 'T', 'Ť' => 'T',
        'ŧ' => 't', 'ţ' => 't', 'ť' => 't',

        'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ũ' => 'U', 'Ū' => 'U', 'Ŭ' => 'U', 'Ů' => 'U', 'Ű' => 'U', 'Ų' => 'U',
        'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ũ' => 'u', 'ū' => 'u', 'ŭ' => 'u', 'ů' => 'u', 'ű' => 'u', 'ų' => 'u',

        'Ŵ' => 'W', 'Ẁ' => 'W', 'Ẃ' => 'W', 'Ẅ' => 'W',
        'ŵ' => 'w', 'ẁ' => 'w', 'ẃ' => 'w', 'ẅ' => 'w',

        'Ý' => 'Y', 'Ÿ' => 'Y', 'Ŷ' => 'Y',
        'ý' => 'y', 'ÿ' => 'y', 'ŷ' => 'y',

        'Ž' => 'Z', 'Ź' => 'Z', 'Ż' => 'Z', 'Ž' => 'Z',
        'ž' => 'z', 'ź' => 'z', 'ż' => 'z', 'ž' => 'z'
    );

    $string = strtr($string, $table);
// Currency symbols: £¤¥€  - we dont bother with them for now
    $string = preg_replace("/[^\x9\xA\xD\x20-\x7F]/u", "", $string);

    return $string;
}

?>