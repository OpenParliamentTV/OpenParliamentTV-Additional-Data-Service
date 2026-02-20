# OpenParliamentTV Additional Data Service

A PHP REST API that enriches OpenParliamentTV platform data from Wikidata, Wikipedia, Wikimedia Commons, Abgeordnetenwatch, and the German parliament document database (DIP).

## Requirements

- PHP 8.1+
- No Composer required — plain `require_once` only

## Setup

```bash
cp config.sample.php config.php
# Edit config.php and fill in your API keys
```

### Configuration (`config.php`)

| Key | Description |
|-----|-------------|
| `$config["accessNeedsKey"]` | Set to `true` to require API key auth |
| `$config["keys"]` | Map of API keys (only needed when access control is on) |
| `$config["optvAPI"]` | OpenParliamentTV platform API base URL |
| `$config["dip-key"]` | DIP Bundestag API key — apply at [dip.bundestag.de](https://dip.bundestag.de/%C3%BCber-dip/hilfe/api) |
| `$config["thumb"]["defaultWidth"]` | Default thumbnail width in pixels (default: `300`) |
| `$config["thumb"]["defaultLanguage"]` | Default language code (default: `de`) |

## Deployment

Drop the repository root into your web root. `index.php` is the entry point and must remain at the root.

## API Reference

### Endpoint

```
GET /index.php
```

### Request Parameters

| Parameter | Required | Description | Example |
|-----------|----------|-------------|---------|
| `type` | Yes | Data type | `memberOfParliament`, `person`, `organisation`, `legalDocument`, `officialDocument`, `term` |
| `language` | No | Language code (default: `de`) | `de`, `en`, `fr` |
| `wikidataID` | Conditional | Wikidata Q-ID | `Q567` |
| `id` | Conditional | OPTV internal document ID | `12345` |
| `dipID` | Conditional | DIP Bundestag document ID | `278452` |
| `sourceURI` | Conditional | PDF source URL | `https://dserver.bundestag.de/btd/19/12345.pdf` |
| `parliament` | No | Parliament shortcode for faction mapping | `de` |
| `thumbWidth` | No | Thumbnail width in pixels (default: `300`) | `200` |
| `key` | Conditional | API key (if access control enabled) | `abc123` |

### Response Format

**Success:**
```json
{
  "meta": {
    "api": {
      "version": "1.0",
      "documentation": "https://github.com/OpenParliamentTV/OpenParliamentTV-Additional-Data-Service",
      "license": { "label": "ODC Open Database License (ODbL) v1.0", "link": "https://opendatacommons.org/licenses/odbl/1-0/" }
    },
    "requestStatus": "success"
  },
  "data": { ... }
}
```

**Error:**
```json
{
  "meta": { "api": { ... }, "requestStatus": "error" },
  "errors": [{ "info": "wrong or missing parameter", "field": "wikidataID" }]
}
```

### Data Types

#### `person` / `memberOfParliament`

Requires: `wikidataID`

```json
{
  "type": "memberOfParliament",
  "id": "Q567",
  "label": "Angela Merkel",
  "labelAlternative": ["Angie"],
  "firstName": "Angela",
  "lastName": "Merkel",
  "degree": "Dr.",
  "degreeFull": "Doktor der Naturwissenschaften",
  "gender": "female",
  "birthDate": "+1954-07-17T00:00:00Z",
  "deathDate": null,
  "abstract": "...",
  "websiteURI": "https://www.bundeskanzlerin.de",
  "thumbnailURI": "https://upload.wikimedia.org/...",
  "thumbnailCreator": "EU2017EE Estonian Presidency",
  "thumbnailLicense": "CC-BY 2.0",
  "socialMediaIDs": [{"label": "Instagram", "id": "bundeskanzlerin"}],
  "additionalInformation": {
    "abgeordnetenwatchID": "79137",
    "wikipedia": { "title": "Angela Merkel", "url": "https://de.wikipedia.org/wiki/Angela_Merkel" }
  },
  "partyID": "Q49762",
  "party": "Christlich Demokratische Union Deutschlands",
  "factionID": "Q1023134",
  "factionLabel": "CDU/CSU-Fraktion"
}
```

`partyID`, `party`, `factionID`, `factionLabel` are only present for `memberOfParliament`.

#### `organisation` / `term` / `legalDocument`

Requires: `wikidataID`

```json
{
  "type": "organisation",
  "id": "Q49762",
  "label": "CDU",
  "labelAlternative": ["Christlich Demokratische Union Deutschlands"],
  "abstract": "...",
  "websiteURI": "https://www.cdu.de",
  "thumbnailURI": "...",
  "thumbnailCreator": "...",
  "thumbnailLicense": "...",
  "socialMediaIDs": [...],
  "additionalInformation": {
    "wikipedia": { "title": "CDU", "url": "https://de.wikipedia.org/wiki/CDU" }
  }
}
```

`sourceURI` is added for `legalDocument` (built from Wikidata P7677 or P9696).

#### `officialDocument`

Requires: one of `dipID`, `id`, or `sourceURI`

```json
{
  "type": "officialDocument",
  "id": "278452",
  "label": "Drucksache 20/14748",
  "labelAlternative": ["..."],
  "sourceURI": "https://dserver.bundestag.de/btd/20/147/2014748.pdf",
  "additionalInformation": {
    "originID": "278452",
    "subType": "Beschlussempfehlung",
    "date": "2025-01-29",
    "electoralPeriod": 20,
    "creator": [...],
    "procedureIDs": [...]
  },
  "_sourceItem": { ... }
}
```

## Data Sources

| Source | Used for |
|--------|----------|
| [Wikidata REST API](https://www.wikidata.org/w/rest.php/wikibase/v1) | Primary entity data (person, org, term, legalDocument) |
| [Wikidata Action API](https://www.wikidata.org/w/api.php) | Batch label resolution (given name, family name, degree, party) |
| [Wikipedia REST API](https://en.wikipedia.org/api/rest_v1/) | Text abstracts |
| [Wikimedia Commons API](https://commons.wikimedia.org/w/api.php) | Thumbnail URLs, creator attribution, license |
| [Abgeordnetenwatch API](https://www.abgeordnetenwatch.de/api/v2/) | Bundestag faction membership |
| [DIP Bundestag API](https://search.dip.bundestag.de/api/v1/) | Official parliament documents |

## Directory Structure

```
/
├── index.php                        # Entry point
├── config.php                       # Local config (not in git)
├── config.sample.php                # Config template
├── src/
│   ├── Api/
│   │   ├── WikidataRestClient.php   # REST API client (primary entity fetches)
│   │   ├── WikidataActionClient.php # Action API client (batch label resolution)
│   │   ├── WikipediaClient.php
│   │   ├── WikimediaCommonsClient.php
│   │   ├── AbgeordnetenwatchClient.php
│   │   └── DipBundestagClient.php
│   ├── Handler/
│   │   ├── PersonHandler.php        # person / memberOfParliament
│   │   ├── OrganisationHandler.php  # organisation / term / legalDocument
│   │   └── OfficialDocumentHandler.php
│   ├── Response/
│   │   └── ApiResponse.php          # Response builder
│   └── Util/
│       ├── WikidataProperties.php   # Property ID constants + gender map
│       ├── StringHelper.php         # Creator/license string cleaning
│       └── FactionMapper.php        # Faction label → Wikidata ID
├── data/
│   ├── faction_to_wikidata_de.json
│   └── abgeordnetenwatch_party_to_wikidata.json
└── tests/
    ├── test_cases.php               # Shared test case definitions
    ├── capture_fixtures.php         # Capture API output snapshots
    └── compare_fixtures.php         # Compare output against snapshots
```

## Testing

```bash
# Start a local dev server from the repo root
php -S localhost:8080 index.php

# Capture current output as fixtures (do this before making changes)
php tests/capture_fixtures.php http://localhost:8080/

# After making changes, compare against saved fixtures
php tests/compare_fixtures.php http://localhost:8080/

# Quick manual tests
curl "http://localhost:8080/?type=person&wikidataID=Q567&language=de"
curl "http://localhost:8080/?type=memberOfParliament&wikidataID=Q567&language=de"
curl "http://localhost:8080/?type=organisation&wikidataID=Q49762&language=de"
curl "http://localhost:8080/?type=officialDocument&dipID=278452"
curl "http://localhost:8080/?type=legalDocument&wikidataID=Q105994&language=de"
```

## License

AGPL-3.0 — see [LICENSE](LICENSE)
