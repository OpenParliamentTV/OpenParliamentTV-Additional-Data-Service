<?php
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);

require_once(__DIR__."/config.php");

function additionalDataService($input) {

    global $config;

    $return["meta"]["api"]["version"]           = "1.0";
    $return["meta"]["api"]["documentation"]     = "https://github.com/OpenParliamentTV/OpenParliamentTV-Additional-Data-Service";
    $return["meta"]["api"]["license"]["label"]  = "ODC Open Database License (ODbL) v1.0";
    $return["meta"]["api"]["license"]["link"]   = "https://opendatacommons.org/licenses/odbl/1-0/";
    $return["meta"]["requestStatus"]            = "error";
    //$return["links"]["self"]                    = "todo";

    if ($config["accessNeedsKey"] == true) {

        if (empty($input["key"])) {

            $return["meta"]["requestStatus"] = "error";
            $return["errors"][] = array("info"=>"key is needed but missing", "field"=>"key");
            return $return;

        } elseif (empty($config["keys"][$input["key"]]) || ($config["keys"][$input["key"]]["enabled"] != true)) {

            $return["meta"]["requestStatus"] = "error";
            $return["errors"][] = array("info"=>"key is wrong or disabled", "field"=>"key");
            return $return;

        }

    }

    $input["language"] = strtolower(!empty($input["language"]) ? $input["language"] : $config["thumb"]["defaultLanguage"]);
    $input["thumbWidth"] = strtolower(!empty($input["thumbWidth"]) ? $input["thumbWidth"] : $config["thumb"]["defaultWidth"]);



    $allowedTypes = array("memberOfParliament", "person", "organisation", "legalDocument", "officialDocument", "term");

    /**
     * Parameter validation
     */
    if ((empty($input["type"]) || (!in_array($input["type"], $allowedTypes)))) {

        $return["meta"]["requestStatus"] = "error";
        $return["errors"][] = array("info"=>"wrong or missing parameter", "field"=>"type");
        return $return;

    }

    switch ($input["type"]) {

        case "officialDocument":

            if (empty($input["id"]) && empty($input["dipID"])) {

                $return["meta"]["requestStatus"] = "error";
                $return["errors"][] = array("info"=>"wrong or missing parameter. id or dipID are required", "fields"=>"id,dipID");
                return $return;

            }

            if (!empty($input["id"]) && empty($input["dipID"])) {

                $tmpDoc = json_decode(file_get_contents($config["optvAPI"]."/document/".$input["id"]),true);

                if (empty($tmpDoc["data"]["attributes"]["additionalInformation"]["originID"])) {

                    $return["meta"]["requestStatus"] = "error";
                    $return["errors"][] = array("info"=>"original document id was not found on platform with internal optv id", "fields"=>"id");
                    return $return;

                }
                $input["dipID"] = $tmpDoc["data"]["attributes"]["additionalInformation"]["originID"];


            }

            try {

                $dip = json_decode(file_get_contents("https://search.dip.bundestag.de/api/v1/drucksache/".$input["dipID"]."?format=json&apikey=".$config["dip-key"]),true);

            } catch (Exception $e) {

                $return["meta"]["requestStatus"] = "error";
                $return["errors"][] = $e->getMessage();
                return $return;

            }

            if ($dip["code"] == "404") {

                $return["meta"]["requestStatus"] = "error";
                $return["errors"][] = array("info"=>"document not found", "code"=>"404");
                return $return;

            }

            if ($dip["code"] == "401") {

                $return["meta"]["requestStatus"] = "error";
                $return["errors"][] = array("info"=>$dip["message"], "code"=>"401");
                return $return;

            }

            $return["meta"]["requestStatus"] = "success";
            $return["data"]["id"] = $dip["id"];
            $return["data"]["label"] = $dip["titel"];
            $return["data"]["labelAlternative"] = array($dip["dokumentart"]." ".$dip["dokumentnummer"]);
            $return["data"]["type"] = "officialDocument";
            $return["data"]["sourceURI"] = $dip["fundstelle"]["pdf_url"];
            $return["data"]["additionalInformation"]["originID"] = $dip["id"];
            $return["data"]["additionalInformation"]["subType"] = $dip["drucksachetyp"];
            $return["data"]["additionalInformation"]["date"] = $dip["datum"];
            $return["data"]["additionalInformation"]["electoralPeriod"] = $dip["wahlperiode"];
            $return["data"]["additionalInformation"]["creator"] = $dip["fundstelle"]["urheber"];
            $return["data"]["additionalInformation"]["creator"] = array_merge(array(), $return["data"]["additionalInformation"]["creator"], $dip["autoren_anzeige"]);
            $return["data"]["additionalInformation"]["procedureIDs"] = $dip["vorgangsbezug"];

            $return["data"]["_sourceItem"] = $dip;


        break;
        case "organisation":
        case "legalDocument":
        case "term":

            if ((empty($input["wikidataID"])) || (!preg_match("/(Q)\d+/i", $input["wikidataID"]))) {

                $return["meta"]["requestStatus"] = "error";
                $return["errors"][] = array("info"=>"wrong or missing parameter", "field"=>"wikidataID");
                return $return;

            }

            $wikidataURL = "https://www.wikidata.org/w/api.php?action=wbgetentities&languages=".$input["language"]."&format=json&props=claims|labels|aliases|sitelinks/urls&ids=".$input["wikidataID"];
            //$return["data"]["tmpURL"] = $wikidataURL;
            $wikidata = json_decode(file_get_contents($wikidataURL),true);

            $return["data"]["type"]         = $input["type"];

            $return["data"]["id"]           = $wikidata["entities"][$input["wikidataID"]]["id"];

            $return["data"]["label"]        = $wikidata["entities"][$input["wikidataID"]]["labels"][$input["language"]]["value"];

            foreach ($wikidata["entities"][$input["wikidataID"]]["aliases"][$input["language"]] as $alias) {

                $return["data"]["labelAlternative"][] = $alias["value"];

            }

            $tmpWikipediaLabel = explode("wiki/",$wikidata["entities"][$input["wikidataID"]]["sitelinks"][$input["language"]."wiki"]["url"]);
            $tmpWikipediaLabel = array_pop($tmpWikipediaLabel);

            try {

                $wikipedia = json_decode(file_get_contents("https://".$input["language"].".wikipedia.org/api/rest_v1/page/summary/".str_replace("/", "%2F",$tmpWikipediaLabel)),true);
                //TODO: Check which chars has to be replaced. Umlauts seems to be okay. $return["data"]["abstract2"] ="https://".$input["language"].".wikipedia.org/api/rest_v1/page/summary/".str_replace("/", "%2F",$tmpWikipediaLabel);
            } catch (Exception $e) {

                $return["meta"]["requestStatus"] = "error";
                $return["errors"][] = $e->getMessage();
                return $return;

            }

            $return["data"]["abstract"] = $wikipedia["extract"];

            $return["data"]["websiteURI"] = ($wikidata["entities"][$input["wikidataID"]]["claims"]["P856"][0]["mainsnak"]["datavalue"]["value"] ?: "");

            if (empty($return["data"]["websiteURI"]) && $input["type"] == "legalDocument" && !empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P7677"][0]["mainsnak"]["datavalue"]["value"])) {

                $return["data"]["websiteURI"] =  "http://www.gesetze-im-internet.de/".$wikidata["entities"][$input["wikidataID"]]["claims"]["P7677"][0]["mainsnak"]["datavalue"]["value"]."/";

            } elseif (empty($return["data"]["websiteURI"]) && $input["type"] == "legalDocument" && !empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P9696"][0]["mainsnak"]["datavalue"]["value"])){
                $return["data"]["websiteURI"] =  "https://www.buzer.de/gesetz/".$wikidata["entities"][$input["wikidataID"]]["claims"]["P9696"][0]["mainsnak"]["datavalue"]["value"]."/";
            }

            $return["data"]["socialMediaIDs"] = array();

            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P2003"][0]["mainsnak"]["datavalue"]["value"])) {
                $preferredInstaKey = getPreferredArrayKey($wikidata["entities"][$input["wikidataID"]]["claims"]["P2003"]);
                $return["data"]["socialMediaIDs"]["instagram"] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P2003"][$preferredInstaKey]["mainsnak"]["datavalue"]["value"];

            }

            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P2013"][0]["mainsnak"]["datavalue"]["value"])) {
                $preferredFacebookKey = getPreferredArrayKey($wikidata["entities"][$input["wikidataID"]]["claims"]["P2013"]);
                $return["data"]["socialMediaIDs"]["facebook"] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P2013"][$preferredFacebookKey]["mainsnak"]["datavalue"]["value"];

            }

            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P2002"][0]["mainsnak"]["datavalue"]["value"])) {
                $preferredTwitterKey = getPreferredArrayKey($wikidata["entities"][$input["wikidataID"]]["claims"]["P2002"]);
                $return["data"]["socialMediaIDs"]["twitter"] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P2002"][$preferredTwitterKey]["mainsnak"]["datavalue"]["value"];

            }

            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P4033"][0]["mainsnak"]["datavalue"]["value"])) {
                $preferredMastodonKey = getPreferredArrayKey($wikidata["entities"][$input["wikidataID"]]["claims"]["P4033"]);
                $return["data"]["socialMediaIDs"]["mastodon"] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P4033"][$preferredMastodonKey]["mainsnak"]["datavalue"]["value"];

            }

            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P2397"][0]["mainsnak"]["datavalue"]["value"])) {
                $preferredYoutubeKey = getPreferredArrayKey($wikidata["entities"][$input["wikidataID"]]["claims"]["P2397"]);
                $return["data"]["socialMediaIDs"]["youtube"] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P2397"][$preferredYoutubeKey]["mainsnak"]["datavalue"]["value"];

            }

            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P6619"][0]["mainsnak"]["datavalue"]["value"])) {
                $preferredXingKey = getPreferredArrayKey($wikidata["entities"][$input["wikidataID"]]["claims"]["P6619"]);
                $return["data"]["socialMediaIDs"]["xing"] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P6619"][$preferredXingKey]["mainsnak"]["datavalue"]["value"];

            }

            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P6744"][0]["mainsnak"]["datavalue"]["value"])) {

                $return["data"]["socialMediaIDs"]["fragDenStaat"] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P6744"][0]["mainsnak"]["datavalue"]["value"];

            }

            $return["data"]["wikipedia"]["title"]   = $wikidata["entities"][$input["wikidataID"]]["sitelinks"][$input["language"]."wiki"]["title"];

            $return["data"]["wikipedia"]["url"]     = $wikidata["entities"][$input["wikidataID"]]["sitelinks"][$input["language"]."wiki"]["url"];

            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P154"])) {

                $preferredLogoKey = getPreferredArrayKey($wikidata["entities"][$input["wikidataID"]]["claims"]["P154"]);

                $tmpThumb = getThumbnailFromWikicommons($wikidata["entities"][$input["wikidataID"]]["claims"]["P154"][$preferredLogoKey]["mainsnak"]["datavalue"]["value"],$input["thumbWidth"]);
                $return["data"]["thumbnailURI"] =  $tmpThumb["data"]["thumbnailURI"];
                $return["data"]["thumbnailCreator"] =  $tmpThumb["data"]["thumbnailCreator"];
                $return["data"]["thumbnailLicense"] =  $tmpThumb["data"]["thumbnailLicense"];
            }

            /*if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P1454"][0]["mainsnak"]["datavalue"]["value"])) {

                $return["data"]["legalForm"] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P1454"][0]["mainsnak"]["datavalue"]["value"];

            }*/
            $return["meta"]["requestStatus"] = "success";


        break;




        case "memberOfParliament":
        case "person":

            if ((empty($input["wikidataID"])) || (!preg_match("/(Q)\d+/i", $input["wikidataID"]))) {

                $return["meta"]["requestStatus"] = "error";
                $return["errors"][] = array("info"=>"wrong or missing parameter", "field"=>"wikidataID");
                return $return;

            }

            try {

                $wikidataURL = "https://www.wikidata.org/w/api.php?action=wbgetentities&languages=".$input["language"]."&format=json&props=claims|labels|aliases|sitelinks/urls&ids=".$input["wikidataID"];
                //$return["data"]["tmpURL"] = $wikidataURL;
                $wikidata = json_decode(file_get_contents($wikidataURL),true);

            } catch (Exception $e) {

                $return["meta"]["requestStatus"] = "error";
                $return["errors"][] = $e->getMessage();
                return $return;

            }

            if (empty($wikidata)) {

                $return["meta"]["requestStatus"] = "error";
                $return["errors"][] = $wikidata["error"];
                return $return;

            }

            $return["data"]["type"]         = $input["type"];

            $return["data"]["id"]           = $wikidata["entities"][$input["wikidataID"]]["id"];

            $return["data"]["label"]        = $wikidata["entities"][$input["wikidataID"]]["labels"][$input["language"]]["value"];

            foreach ($wikidata["entities"][$input["wikidataID"]]["aliases"][$input["language"]] as $alias) {

                $return["data"]["labelAlternative"][] = $alias["value"];

            }

            $tmpWikidataPersonURL = "https://www.wikidata.org/w/api.php?action=wbgetentities&languages=".$input["language"]."&format=json&props=info|aliases|labels|descriptions|datatype&ids=";

            // givenName
            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P735"])) {

                $preferredFirstNameKey = getPreferredArrayKey($wikidata["entities"][$input["wikidataID"]]["claims"]["P735"]);

                $tmpWikiRequest[] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P735"][$preferredFirstNameKey]["mainsnak"]["datavalue"]["value"]["id"];
            }

            // name
            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P734"][0]["mainsnak"]["datavalue"]["value"]["id"])) {
                $tmpWikiRequest[] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P734"][0]["mainsnak"]["datavalue"]["value"]["id"];
            }

            // degree
            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P512"][0]["mainsnak"]["datavalue"]["value"]["id"])) {
                $tmpWikiRequest[] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P512"][0]["mainsnak"]["datavalue"]["value"]["id"];
            }

            // gender
            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P21"][0]["mainsnak"]["datavalue"]["value"]["id"])) {
                $tmpWikiRequest[] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P21"][0]["mainsnak"]["datavalue"]["value"]["id"];
            }

            // party
            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P102"])) {

                $preferredPartyKey = getPreferredArrayKey($wikidata["entities"][$input["wikidataID"]]["claims"]["P102"]);

                $tmpWikiRequest[] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P102"][$preferredPartyKey]["mainsnak"]["datavalue"]["value"]["id"];

            }

            $tmpWikidataPersonURL = $tmpWikidataPersonURL.implode("|",$tmpWikiRequest);



            $tmpWikidataPerson = json_decode(file_get_contents($tmpWikidataPersonURL),true);

            //$return["data"]["tmp"] = $tmpWikidataPersonURL;


            $return["data"]["firstName"]    = $tmpWikidataPerson["entities"][$wikidata["entities"][$input["wikidataID"]]["claims"]["P735"][$preferredFirstNameKey]["mainsnak"]["datavalue"]["value"]["id"]]["labels"][$input["language"]]["value"];

            $return["data"]["lastName"]     = $tmpWikidataPerson["entities"][$wikidata["entities"][$input["wikidataID"]]["claims"]["P734"][0]["mainsnak"]["datavalue"]["value"]["id"]]["labels"][$input["language"]]["value"];

            $return["data"]["degreeFull"]   = $tmpWikidataPerson["entities"][$wikidata["entities"][$input["wikidataID"]]["claims"]["P512"][0]["mainsnak"]["datavalue"]["value"]["id"]]["labels"][$input["language"]]["value"];

            $tmpDegree                      = explode(" ",$tmpWikidataPerson["entities"][$wikidata["entities"][$input["wikidataID"]]["claims"]["P512"][0]["mainsnak"]["datavalue"]["value"]["id"]]["aliases"][$input["language"]][0]["value"]);
            $return["data"]["degree"]       = reset($tmpDegree);

            //Will set it in the given language: $return["data"]["gender"]    = $tmpWikidataPerson["entities"][$wikidata["entities"][$input["wikidataID"]]["claims"]["P21"][0]["mainsnak"]["datavalue"]["value"]["id"]]["labels"][$input["language"]]["value"];

            $return["data"]["wikipedia"]["title"]   = $wikidata["entities"][$input["wikidataID"]]["sitelinks"][$input["language"]."wiki"]["title"];

            $return["data"]["wikipedia"]["url"]     = $wikidata["entities"][$input["wikidataID"]]["sitelinks"][$input["language"]."wiki"]["url"];

            $tmpWikipediaLabel = explode("wiki/",$wikidata["entities"][$input["wikidataID"]]["sitelinks"][$input["language"]."wiki"]["url"]);
            $tmpWikipediaLabel = array_pop($tmpWikipediaLabel);

            try {

                //$wikipedia = json_decode(file_get_contents("https://".$input["language"].".wikipedia.org/api/rest_v1/page/summary/".urlencode($tmpWikipediaLabel)),true);
                $wikipedia = json_decode(file_get_contents("https://".$input["language"].".wikipedia.org/api/rest_v1/page/summary/".str_replace("/", "%2F",$tmpWikipediaLabel)),true);

            } catch (Exception $e) {

                $return["meta"]["requestStatus"] = "error";
                $return["errors"][] = $e->getMessage();
                return $return;

            }

            //$return["data"]["abstract2"] = "https://".$input["language"].".wikipedia.org/api/rest_v1/page/summary/".urlencode($tmpWikipediaLabel);
            $return["data"]["abstract"] = $wikipedia["extract"];

            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P21"][0]["mainsnak"]["datavalue"]["value"])) {

                //Trans men are men.
                if (($wikidata["entities"][$input["wikidataID"]]["claims"]["P21"][0]["mainsnak"]["datavalue"]["value"]["id"] == "Q6581097") || ($wikidata["entities"][$input["wikidataID"]]["claims"]["P21"][0]["mainsnak"]["datavalue"]["value"]["id"] == "Q2449503")) {

                    $return["data"]["gender"] = "male";

                //Trans women are women.
                } elseif (($wikidata["entities"][$input["wikidataID"]]["claims"]["P21"][0]["mainsnak"]["datavalue"]["value"]["id"] == "Q6581072") || ($wikidata["entities"][$input["wikidataID"]]["claims"]["P21"][0]["mainsnak"]["datavalue"]["value"]["id"] == "Q1052281")) {

                    $return["data"]["gender"] = "female";

                } elseif ($wikidata["entities"][$input["wikidataID"]]["claims"]["P21"][0]["mainsnak"]["datavalue"]["value"]["id"] == "Q1097630") {
                    //TODO
                    $return["data"]["gender"] = "inter";

                } elseif ($wikidata["entities"][$input["wikidataID"]]["claims"]["P21"][0]["mainsnak"]["datavalue"]["value"]["id"] == "Q48270") {
                    //TODO
                    $return["data"]["gender"] = "non-binary";

                }


            }

            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P569"][0]["mainsnak"]["datavalue"]["value"])) {

                $return["data"]["birthDate"] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P569"][0]["mainsnak"]["datavalue"]["value"]["time"];

            }

            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P570"][0]["mainsnak"]["datavalue"]["value"])) {

                $return["data"]["deathDate"] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P570"][0]["mainsnak"]["datavalue"]["value"]["time"];

            }

            $return["data"]["websiteURI"] = ($wikidata["entities"][$input["wikidataID"]]["claims"]["P856"][0]["mainsnak"]["datavalue"]["value"] ?: "");

            $return["data"]["socialMediaIDs"] = array();

            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P2003"][0]["mainsnak"]["datavalue"]["value"])) {
                $preferredInstaKey = getPreferredArrayKey($wikidata["entities"][$input["wikidataID"]]["claims"]["P2003"]);
                $return["data"]["socialMediaIDs"]["instagram"] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P2003"][$preferredInstaKey]["mainsnak"]["datavalue"]["value"];

            }

            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P2013"][0]["mainsnak"]["datavalue"]["value"])) {
                $preferredFacebookKey = getPreferredArrayKey($wikidata["entities"][$input["wikidataID"]]["claims"]["P2013"]);
                $return["data"]["socialMediaIDs"]["facebook"] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P2013"][$preferredFacebookKey]["mainsnak"]["datavalue"]["value"];

            }

            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P2002"][0]["mainsnak"]["datavalue"]["value"])) {
                $preferredTwitterKey = getPreferredArrayKey($wikidata["entities"][$input["wikidataID"]]["claims"]["P2002"]);
                $return["data"]["socialMediaIDs"]["twitter"] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P2002"][$preferredTwitterKey]["mainsnak"]["datavalue"]["value"];

            }

            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P4033"][0]["mainsnak"]["datavalue"]["value"])) {
                $preferredMastodonKey = getPreferredArrayKey($wikidata["entities"][$input["wikidataID"]]["claims"]["P4033"]);
                $return["data"]["socialMediaIDs"]["mastodon"] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P4033"][$preferredMastodonKey]["mainsnak"]["datavalue"]["value"];

            }

            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P5355"])) {

                $return["data"]["additionalInformation"]["abgeordnetenwatchID"] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P5355"][0]["mainsnak"]["datavalue"]["value"];

            }

            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P2397"][0]["mainsnak"]["datavalue"]["value"])) {
                $preferredYoutubeKey = getPreferredArrayKey($wikidata["entities"][$input["wikidataID"]]["claims"]["P2397"]);
                $return["data"]["socialMediaIDs"]["youtube"] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P2397"][$preferredYoutubeKey]["mainsnak"]["datavalue"]["value"];

            }

            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P6619"][0]["mainsnak"]["datavalue"]["value"])) {
                $preferredXingKey = getPreferredArrayKey($wikidata["entities"][$input["wikidataID"]]["claims"]["P6619"]);
                $return["data"]["socialMediaIDs"]["xing"] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P6619"][$preferredXingKey]["mainsnak"]["datavalue"]["value"];

            }

            /*require_once (__DIR__."/utilities/xmlParser.class.php");
            $tmpXML = new xmlParser2();
            $tmpXMLStr = file_get_contents("https://magnus-toolserver.toolforge.org/commonsapi.php?languages=de&thumbwidth=".$input["thumbWidth"]."&image=".urlencode($wikidata["entities"][$input["wikidataID"]]["claims"]["P18"][0]["mainsnak"]["datavalue"]["value"]), false, $context);
            //echo $tmpXMLStr;
            $tmpImage = $tmpXML->xml2array($tmpXMLStr);

            //$return["data"]["thumbnailURI2"] =  $tmpImage;
            $return["data"]["thumbnailURI"] =  $tmpImage["response"]["file"]["urls"]["thumbnail"];
            $return["data"]["thumbnailCreator"] =  $tmpImage["response"]["file"]["author"];
            $return["data"]["thumbnailLicense"] =  $tmpImage["response"]["licenses"]["license"]["name"];
            */
            //if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P18"][0]["mainsnak"]["datavalue"]["value"])) {

            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P18"])) {

                $preferredImageKey = getPreferredArrayKey($wikidata["entities"][$input["wikidataID"]]["claims"]["P18"]);

                $tmpThumb = getThumbnailFromWikicommons($wikidata["entities"][$input["wikidataID"]]["claims"]["P18"][$preferredImageKey]["mainsnak"]["datavalue"]["value"],$input["thumbWidth"]);
                $return["data"]["thumbnailURI"] =  $tmpThumb["data"]["thumbnailURI"];
                $return["data"]["thumbnailCreator"] =  $tmpThumb["data"]["thumbnailCreator"];
                $return["data"]["thumbnailLicense"] =  $tmpThumb["data"]["thumbnailLicense"];
            }


            if ($input["type"] == "memberOfParliament") {


                //TODO: Maybe use the abgeordnetenwatch mapping to wikidata id?
                $return["data"]["partyID"] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P102"][$preferredPartyKey]["mainsnak"]["datavalue"]["value"]["id"];

                $return["data"]["party"] = $tmpWikidataPerson["entities"][$wikidata["entities"][$input["wikidataID"]]["claims"]["P102"][$preferredPartyKey]["mainsnak"]["datavalue"]["value"]["id"]]["labels"][$input["language"]]["value"];

                //$mapAbgeordnetenwatchPartyIDToWikidataID = json_decode(file_get_contents(__DIR__ . "/utilities/abgeordnetenwatchparty_to_wikidata.json"),true);

                if (!empty($return["data"]["additionalInformation"]["abgeordnetenwatchID"])) {

                    //$mapAbgeordnetenwatchPartyIDToWikidataID = json_decode(file_get_contents(__DIR__ . "/utilities/abgeordnetenwatchparty_to_wikidata.json"),true);

                    $tmpFactionInfos = json_decode(file_get_contents("https://www.abgeordnetenwatch.de/api/v2/candidacies-mandates?politician[entity.politician.id]=".$return["data"]["additionalInformation"]["abgeordnetenwatchID"]),true);
                    if (!empty($tmpFactionInfos["data"])) {
                        $return["data"]["factionLabel"] = $tmpFactionInfos["data"][0]["fraction_membership"][0]["label"];
                        //$return["data"]["factionLabel2"] = $tmpFactionInfos["data"][0]["fraction_membership"][0];
                        $return["data"]["factionWikidataID"] = getFactionWikidataIDFromString($return["data"]["factionLabel"]);
                    }

                }

            }

            $return["meta"]["requestStatus"] = "success";



        break;


    }

    return $return;


}

function getThumbnailFromWikicommons($imageName, $thumbWidth) {

    $context = stream_context_create(
        array(
            "http" => array(
                "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
            )
        )
    );

    require_once (__DIR__."/utilities/xmlParser.class.php");
    $tmpXML = new xmlParser2();
    $tmpXMLStr = file_get_contents("https://magnus-toolserver.toolforge.org/commonsapi.php?languages=de&thumbwidth=".$thumbWidth."&image=".urlencode($imageName), false, $context);
    //echo $tmpXMLStr;
    $tmpImage = $tmpXML->xml2array($tmpXMLStr);

    //$return["data"]["thumbnailURI2"] =  $tmpImage;
    $return["data"]["thumbnailURI"] =  $tmpImage["response"]["file"]["urls"]["thumbnail"];
    $return["data"]["thumbnailCreator"] =  $tmpImage["response"]["file"]["author"];
    $return["data"]["thumbnailLicense"] =  $tmpImage["response"]["licenses"]["license"]["name"];
    $return["data"]["all"] =  $tmpImage;
    return $return;


}

function getPreferredArrayKey($array) {

    $preferredKey = 0;
    foreach ($array as $tmpKey=>$tmpValue) {
        if ($tmpValue["rank"] == "preferred") {
            $preferredKey = $tmpKey;
            break;
        }
    }
    return $preferredKey;

}


function getFactionWikidataIDFromString($string, $parliament = "de") {

    $factions = json_decode(file_get_contents(__DIR__ . "/utilities/faction_to_wikidata_".$parliament.".json"),true);
    $string = preg_replace("/[^a-z\d ]/i", '', $string);

    foreach ($factions as $factionLabel=>$wikidataID) {

        $factionLabel = preg_replace("/[^a-z\d ]/i", '', $factionLabel);

        if (preg_match("~".$factionLabel."~",$string) || preg_match("~".$string."~",$factionLabel)) {
            return $wikidataID;
        }
    }

}
?>