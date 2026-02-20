<?php

class WikidataProperties
{
    // Person
    const GIVEN_NAME    = 'P735';
    const FAMILY_NAME   = 'P734';
    const BIRTH_DATE    = 'P569';
    const DEATH_DATE    = 'P570';
    const GENDER        = 'P21';
    const DEGREE        = 'P512';
    const IMAGE         = 'P18';
    const WEBSITE       = 'P856';
    const PARTY         = 'P102';

    // Organisation
    const LOGO          = 'P154';
    const SHORT_NAME    = 'P1813';

    // Social media
    const INSTAGRAM     = 'P2003';
    const FACEBOOK      = 'P2013';
    const TWITTER       = 'P2002';
    const MASTODON      = 'P4033';
    const YOUTUBE       = 'P2397';
    const XING          = 'P6619';

    // External IDs
    const ABGEORDNETENWATCH   = 'P5355';
    const FRAGDENSTAAT        = 'P6744';
    const GESETZE_IM_INTERNET = 'P7677';
    const BUZER               = 'P9696';

    // Gender Q-IDs
    const GENDER_MALE         = 'Q6581097';
    const GENDER_TRANS_MALE   = 'Q2449503';
    const GENDER_FEMALE       = 'Q6581072';
    const GENDER_TRANS_FEMALE = 'Q1052281';
    const GENDER_INTERSEX     = 'Q1097630';
    const GENDER_NON_BINARY   = 'Q48270';

    const GENDER_MAP = [
        self::GENDER_MALE         => 'male',
        self::GENDER_TRANS_MALE   => 'male',
        self::GENDER_FEMALE       => 'female',
        self::GENDER_TRANS_FEMALE => 'female',
        self::GENDER_INTERSEX     => 'inter',   // keep as "inter" per decision #1
        self::GENDER_NON_BINARY   => 'non-binary',
    ];
}
