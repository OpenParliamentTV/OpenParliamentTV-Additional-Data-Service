## OpenParliamentTV-Additional-Data-Service: API (DRAFT)

### 1. Endpoints

| Endpoint| Description | 
| :------------- | :---------- | 
| `/getAdditionalData` | Returns additional data objects based on either a **wikidataID** or a **documentID** |
___
#### Input / Parameters
| Parameter | Required | Possible Values | 
| :------------- | :---------- | :---------- | 
| `type` | yes | see **2. Data Types**  |
| `language` | yes | **Language Shortcode**, see [SHORTCODES](https://github.com/OpenParliamentTV/OpenParliamentTV-Architecture/blob/main/SHORTCODES.md) |
| `wikidataID` | no | <any string> |
| `documentID` | no | <any string> |
| `parliament` | no | **Parliament Shortcode** to route parliament specific cases, see [SHORTCODES](https://github.com/OpenParliamentTV/OpenParliamentTV-Architecture/blob/main/SHORTCODES.md) |

#### Data Output 
Responses **MUST** include the following properties for any request (GET **and** POST):

```yaml
{
  "meta": {
    "api": {
      "version": "1.0",
      "documentation": "https://example.com",
      "license": {
        "label": "ODC Open Database License (ODbL) v1.0",
        "link": "https://opendatacommons.org/licenses/odbl/1-0/"
      }
    },
    "requestStatus": "success" // OR "error"
  },
  "data": [], // {} OR []
  "errors": [], // EITHER "data" OR "errors"
  "links": {
    "self": "https://example.com/getAdditionalData?type=person&language=de&wikidataID=Q567" // request URL
  }
}
```

**Successful** requests **MUST** include the following properties:

```yaml
{
  "meta": {
    "api": {
      "version": "1.0",
      "documentation": "https://example.com",
      "license": {
        "label": "ODC Open Database License (ODbL) v1.0",
        "link": "https://opendatacommons.org/licenses/odbl/1-0/"
      }
    },
    "requestStatus": "success"
  },
  "data": {},
  "links": {
    "self": "https://example.com/getAdditionalData?type=person&language=de&wikidataID=Q567" // request URL
  }
}
```

**Errors** **MUST** include the following properties:

```yaml
{
  "meta": {
    "api": {
      "version": "1.0",
      "documentation": "https://example.com",
      "license": {
        "label": "ODC Open Database License (ODbL) v1.0",
        "link": "https://opendatacommons.org/licenses/odbl/1-0/"
      }
    },
    "requestStatus": "error"
  },
  "errors": [
    {
      "meta": {
        "domSelector": "" // optional
      },
      "status": "422", // HTTP Status   
      "code": "4", 
      "title":  "Invalid Attribute",
      "detail": "wikidataID must contain at least three characters."
    }
  ],
  "links": {
    "self": "https://example.com/getAdditionalData?type=person&language=de&wikidataID=Q5" // request URL
  }
}
```

| status | code | text | 
| :------------- | :---------- | :---------- | 
| `success` | 1 | Entity or document successfully found. |
| `error` | 2 | No entity or document found. |
| `error` | 3 | Parameters missing |

For more info on the **data** object, see **3. Data Items**
___
### 2. Data Types

| Type| Description | Example | 
| :------------- | :---------- |  :---------- | 
| `memberOfParliament` | special case for members of a given parliament (in which we also have to derive the party and faction membership) | Angela Merkel |
| `person` | any other person | Joe Biden |
| `organisation` | any organisation (broadly defined as body in which a group of people organise themselves) | parties (SPD), factions (Bundestagsfraktion DIE LINKE), NGOs (Greenpeace, Sea Watch), companies (Biontech), initiatives (Fridays for Future), international organisations (WTO, UN), official bodies (EU Parliament, European Commission), legislative bodies (Bundesverfassungsgericht, Supreme Court) |
| `legalDocument` | laws and legislative texts | Netzwerkdurchsetzungsgesetz, AGG, Grundgesetz |
| `officialDocument` | parliament specific documents | Drucksache 19/2, § 1 Absatz 3 der Geschäftsordnung |
| `term` | anything else which is not a person, organisation or document | Netzpolitik, Digitalpolitik, Richtlinienkompetenz, Subsidiaritätsprinzip, Europäischer Stabilitätsmechanismus, ESM, Entschliessungsantrag, Breitbandausbau, Operation MINUSMA |

***Still to be defined: PLACES***

___
### 3. Data Items
#### Generic Data Fields
These fields will be **returned for any data item**. 

| Field | Description | Examples | 
| :------------- | :---------- | :---------- | 
| `type` | see **2. Data Types** | `memberOfParliament` |
| `id` | <Wikidata ID> | `Q567` |
| `label` | <Wikidata Label> | `Angela Merkel` |
| `labelAlternative` | Wikidata Property "short name" or "aliases" | `UN` |
| `abstract` | Text Abstract | `Bundeskanzlerin der Bundesrepublik Deutschland seit 2005` |
| `thumbnailURI` | Thumbnail Source URL | `https://upload.wikimedia.org/wikipedia/commons/thumb/b/bf/Angela_Merkel._Tallinn_Digital_Summit.jpg/174px-Angela_Merkel._Tallinn_Digital_Summit.jpg` |
| `thumbnailCreator` | Thumbnail Creator / Author / Attribution | `EU2017EE Estonian Presidency` |
| `thumbnailLicense` | Thumbnail License | `CC-BY 2.0 Generic` |
| `websiteURI` | Wikidata Property "official website" | `https://www.un.org/` |
| `embedURI` (SPECIAL CASE, TODO LATER!) | URI to be used for embedding inside the platform pages (eg. media details) | `https://embed.abgeordnetenwatch.de/profile/angela-merkel` |
| `socialMediaIDs` | Wikidata Property "??" | `[{"label": "Instagram", "id": "bundeskanzlerin"}]` |
| `additionalInformation` | eg. Wikidata Property "abgeordnetenwatch.de politician ID" | `{"abgeordnetenwatchID": "7643642"}` |

#### Additional Type-Specific Data Fields
These fields can be **returned in addition to Generic Data Fields**. 

**Type:** `memberOfParliament`
| Field | Description | Examples | 
| :------------- | :---------- | :---------- | 
| `birthDate` | Wikidata Property "date of birth" | `1954-07-17` |
| `party` | Wikidata Property "member of political party" | `Q49762` |
| `faction` | Wikidata Property "member of the German Bundestag > parliamentary group" | `Q1023134` |
| `gender` | Wikidata Property "sex or gender"  (non-binary) | `female` |
| `firstName` | Wikidata Property "given name" | `Angela` |
| `lastName` | Wikidata Property "family name" | `Merkel` |
| `degree` | Wikidata Property "academic degree" | `Dr.` |


**Type:** `person`
| Field | Description | Examples | 
| :------------- | :---------- | :---------- | 
| `birthDate` | Wikidata Property "date of birth" | `1960-01-26` |
| `gender` | Wikidata Property "sex or gender" (non-binary) | `female` |
| `firstName` | Wikidata Property "given name" | `Inge` |
| `lastName` | Wikidata Property "family name" | `Deutschkron` |
| `degree` | Wikidata Property "academic degree" | `Dr.` |

