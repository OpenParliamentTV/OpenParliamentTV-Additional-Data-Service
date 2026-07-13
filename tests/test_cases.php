<?php
return [
    // DE — existing baseline cases
    ['type' => 'person',             'parliament' => 'DE', 'wikidataID' => 'Q567',    'language' => 'de'],
    ['type' => 'memberOfParliament', 'parliament' => 'DE', 'wikidataID' => 'Q567',    'language' => 'de'],
    ['type' => 'organisation',       'parliament' => 'DE', 'wikidataID' => 'Q49762',  'language' => 'de'],
    ['type' => 'term',               'parliament' => 'DE', 'wikidataID' => 'Q327389', 'language' => 'de'],
    ['type' => 'legalDocument',      'parliament' => 'DE', 'wikidataID' => 'Q105994', 'language' => 'de'],
    ['type' => 'officialDocument',   'parliament' => 'DE', 'dipID'      => '278960'],
    ['type' => 'officialDocument',   'parliament' => 'DE', 'documentID' => '278960'],
    ['type' => 'officialDocument',   'parliament' => 'DE', 'documentNumbers' => '19/1,19/2'],                 // batch lookup

    // SE — Stage B
    ['type' => 'person',             'parliament' => 'SE', 'wikidataID' => 'Q911372', 'language' => 'sv'],  // Jan Björklund
    ['type' => 'memberOfParliament', 'parliament' => 'SE', 'wikidataID' => 'Q911372', 'language' => 'sv'],
    ['type' => 'organisation',       'parliament' => 'SE', 'wikidataID' => 'Q207590', 'language' => 'sv'],  // Riksdag
    ['type' => 'officialDocument',   'parliament' => 'SE', 'documentID' => 'HD024141'],
    ['type' => 'memberOfParliament', 'parliament' => 'SE', 'wikidataID' => 'Q937',    'language' => 'sv'],  // no P1214 — graceful degradation

    // AT — Nationalrat faction via parlament.gv.at
    ['type' => 'memberOfParliament', 'parliament' => 'AT', 'wikidataID' => 'Q85433',  'language' => 'de'],  // Doris Bures — current SPÖ MP
    ['type' => 'memberOfParliament', 'parliament' => 'AT', 'wikidataID' => 'Q937',    'language' => 'de'],  // no P2280 — graceful degradation

    // Error cases
    [],                                                                                                      // missing type
    ['type' => 'person',           'parliament' => 'DE', 'wikidataID' => 'INVALID'],                         // bad ID format
    ['type' => 'person',           'parliament' => 'DE', 'wikidataID' => 'Q99999999999'],                    // non-existent entity
    ['type' => 'person',                                'wikidataID' => 'Q567'],                             // omitted parliament → DE default
    ['type' => 'person',           'parliament' => 'XX', 'wikidataID' => 'Q567'],                            // unknown parliament
    ['type' => 'officialDocument', 'parliament' => 'SE', 'documentID' => 'NONEXISTENT'],                     // SE doc not found
    ['type' => 'officialDocument', 'parliament' => 'SE', 'documentNumbers' => '19/1'],                       // batch unsupported for SE
    ['type' => 'officialDocument', 'parliament' => 'DE', 'documentNumbers' => '19/1,abc'],                   // invalid number token
];
