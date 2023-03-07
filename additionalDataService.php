<?php
error_reporting(E_ALL ^ E_NOTICE ^ E_WARNING);


function additionalDataService($input) {

    $return["meta"]["api"]["version"]           = "1.0";
    $return["meta"]["api"]["documentation"]     = "https://github.com/OpenParliamentTV/OpenParliamentTV-Additional-Data-Service";
    $return["meta"]["api"]["license"]["label"]  = "ODC Open Database License (ODbL) v1.0";
    $return["meta"]["api"]["license"]["link"]   = "https://opendatacommons.org/licenses/odbl/1-0/";
    $return["meta"]["requestStatus"]            = "error";
    //$return["links"]["self"]                    = "todo";

    $input["language"] = strtolower(!empty($input["language"]) ? $input["language"] : "de");
    $input["thumbWidth"] = strtolower(!empty($input["thumbWidth"]) ? $input["thumbWidth"] : "350");

    $context = stream_context_create(
        array(
            "http" => array(
                "header" => "User-Agent: Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36"
            )
        )
    );

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


        case "memberOfParliament":
        case "person":

            if ((empty($input["wikidataID"])) || (!preg_match("/(Q)\d+/i", $input["wikidataID"]))) {

                $return["meta"]["requestStatus"] = "error";
                $return["errors"][] = array("info"=>"wrong or missing parameter", "field"=>"wikidataID");
                return $return;

            }

            try {

                $wikidata = json_decode(file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&languages=".$input["language"]."&format=json&props=claims|labels|aliases|sitelinks/urls&ids=".$input["wikidataID"]),true);

            } catch (Exception $e) {

                $return["meta"]["requestStatus"] = "error";
                $return["errors"][] = $e;
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

            //Names
            $tmpWikidataPerson = json_decode(file_get_contents("https://www.wikidata.org/w/api.php?action=wbgetentities&languages=".$input["language"]."&format=json&props=info|aliases|labels|descriptions|datatype&ids=".$wikidata["entities"][$input["wikidataID"]]["claims"]["P735"][0]["mainsnak"]["datavalue"]["value"]["id"]."|".$wikidata["entities"][$input["wikidataID"]]["claims"]["P734"][0]["mainsnak"]["datavalue"]["value"]["id"]."|".$wikidata["entities"][$input["wikidataID"]]["claims"]["P512"][0]["mainsnak"]["datavalue"]["value"]["id"]."|".$wikidata["entities"][$input["wikidataID"]]["claims"]["P21"][0]["mainsnak"]["datavalue"]["value"]["id"]),true);

            //$return["data"]["tmp"] = "https://www.wikidata.org/w/api.php?action=wbgetentities&languages=".$input["language"]."&format=json&props=info|aliases|labels|descriptions|datatype&ids=".$wikidata["entities"][$input["wikidataID"]]["claims"]["P735"][0]["mainsnak"]["datavalue"]["value"]["id"]."|".$wikidata["entities"][$input["wikidataID"]]["claims"]["P734"][0]["mainsnak"]["datavalue"]["value"]["id"]."|".$wikidata["entities"][$input["wikidataID"]]["claims"]["P512"][0]["mainsnak"]["datavalue"]["value"]["id"]."|".$wikidata["entities"][$input["wikidataID"]]["claims"]["P21"][0]["mainsnak"]["datavalue"]["value"]["id"];

            $return["data"]["firstName"]    = $tmpWikidataPerson["entities"][$wikidata["entities"][$input["wikidataID"]]["claims"]["P735"][0]["mainsnak"]["datavalue"]["value"]["id"]]["labels"][$input["language"]]["value"];

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

                $wikipedia = json_decode(file_get_contents("https://".$input["language"].".wikipedia.org/api/rest_v1/page/summary/".urlencode($tmpWikipediaLabel)),true);

            } catch (Exception $e) {

                $return["meta"]["requestStatus"] = "error";
                $return["errors"][] = $e;
                return $return;

            }

            //$return["data"]["abstract2"] = "https://".$input["language"].".wikipedia.org/api/rest_v1/page/summary/".urlencode($tmpWikipediaLabel);
            $return["data"]["abstract"] = $wikipedia["extract"];

            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P21"][0]["mainsnak"]["datavalue"]["value"])) {

                if ($wikidata["entities"][$input["wikidataID"]]["claims"]["P21"][0]["mainsnak"]["datavalue"]["value"]["id"] == "Q6581097") {

                    $return["data"]["gender"] = "male";

                } elseif ($wikidata["entities"][$input["wikidataID"]]["claims"]["P21"][0]["mainsnak"]["datavalue"]["value"]["id"] == "Q6581072") {

                    $return["data"]["gender"] = "female";

                }
                //TODO: non-binary

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

                $return["data"]["socialMediaIDs"]["instagram"] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P2003"][0]["mainsnak"]["datavalue"]["value"];

            }

            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P2013"][0]["mainsnak"]["datavalue"]["value"])) {

                $return["data"]["socialMediaIDs"]["facebook"] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P2013"][0]["mainsnak"]["datavalue"]["value"];

            }

            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P2013"][0]["mainsnak"]["datavalue"]["value"])) {

                $return["data"]["socialMediaIDs"]["twitter"] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P2002"][0]["mainsnak"]["datavalue"]["value"];

            }

            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P4033"][0]["mainsnak"]["datavalue"]["value"])) {

                $return["data"]["socialMediaIDs"]["mastodon"] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P4033"][0]["mainsnak"]["datavalue"]["value"];

            }

            //TODO: Mastodon

            if (!empty($wikidata["entities"][$input["wikidataID"]]["claims"]["P5355"])) {

                $return["data"]["additionalInformation"]["abgeordnetenwatchID"] = $wikidata["entities"][$input["wikidataID"]]["claims"]["P5355"][0]["mainsnak"]["datavalue"]["value"];

            }

            require_once (__DIR__."/utilities/xmlParser.class.php");
            $tmpXML = new xmlParser2();
            $tmpXMLStr = file_get_contents("https://magnus-toolserver.toolforge.org/commonsapi.php?languages=de&thumbwidth=".$input["thumbWidth"]."&image=".urlencode($wikidata["entities"][$input["wikidataID"]]["claims"]["P18"][0]["mainsnak"]["datavalue"]["value"]), false, $context);
            //echo $tmpXMLStr;
            $tmpImage = $tmpXML->xml2array($tmpXMLStr);

            //$return["data"]["thumbnailURI2"] =  $tmpImage;
            $return["data"]["thumbnailURI"] =  $tmpImage["response"]["file"]["urls"]["thumbnail"];
            $return["data"]["thumbnailCreator"] =  $tmpImage["response"]["file"]["author"];
            $return["data"]["thumbnailLicense"] =  $tmpImage["response"]["licenses"]["license"]["name"];

            if ($input["type"] == "memberOfParliament") {

                if (!empty($return["data"]["additionalInformation"]["abgeordnetenwatchID"])) {

                    $tmpFactionInfos = json_decode(file_get_contents("https://www.abgeordnetenwatch.de/api/v2/candidacies-mandates?politician[entity.politician.id]=".$return["data"]["additionalInformation"]["abgeordnetenwatchID"]),true);
                    if (!empty($tmpFactionInfos["data"])) {
                        $return["data"]["factionLabel1"] = $tmpFactionInfos["data"][0]["fraction_membership"][0]["label"];
                        $return["data"]["factionLabel2"] = $tmpFactionInfos["data"][0]["fraction_membership"][0]["fraction"]["label"];
                    }

                }

            }

            $return["meta"]["requestStatus"] = "success";



        break;


    }

    return $return;


}
?>