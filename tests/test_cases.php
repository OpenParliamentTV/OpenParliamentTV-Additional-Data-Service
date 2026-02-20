<?php
return [
    ['type' => 'person',             'wikidataID' => 'Q567',    'language' => 'de'],
    ['type' => 'memberOfParliament', 'wikidataID' => 'Q567',    'language' => 'de'],
    ['type' => 'organisation',       'wikidataID' => 'Q49762',  'language' => 'de'],
    ['type' => 'term',               'wikidataID' => 'Q327389', 'language' => 'de'],
    ['type' => 'legalDocument',      'wikidataID' => 'Q105994', 'language' => 'de'],
    ['type' => 'officialDocument',   'dipID'      => '278452'],
    [],                                                      // error: no params
    ['type' => 'person', 'wikidataID' => 'INVALID'],         // error: bad ID
    ['type' => 'person', 'wikidataID' => 'Q99999999999'],    // error: non-existent
];
