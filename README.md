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

More info on **Wikidata** Property Mappings can be found [here](https://github.com/OpenParliamentTV/OpenParliamentTV-NEL/blob/main/src/optv_nel/wikidata/mappings.py): 
An example implementation for extracting the image attribution and license from **Wikimedia Commons** can be found [here](https://github.com/OpenParliamentTV/OpenParliamentTV-NEL/blob/main/src/optv_nel/wikimedia_commons/helpers.py).

#### Generic Data Fields
These fields will be **returned for any data item**. 

| Field | Type | Description | Examples | 
| :------------- | :---------- | :---------- | :---------- | 
| `type` | String | see **2. Data Types** | `memberOfParliament` |
| `id` | String | <**Wikidata** ID> | `Q567` |
| `label` | String | <**Wikidata** Label> | `Angela Merkel` |
| `labelAlternative` | Array | **Wikidata** Property "short name" or "aliases" | `UN` |
| `abstract` | String | Text Abstract from **Wikipedia** via MediaWiki API | `Bundeskanzlerin der Bundesrepublik Deutschland seit 2005` |
| `thumbnailURI` | String | **Wikidata** Property "image" or "logo image" (P18 or P154) | `https://upload.wikimedia.org/wikipedia/commons/thumb/b/bf/Angela_Merkel._Tallinn_Digital_Summit.jpg/174px-Angela_Merkel._Tallinn_Digital_Summit.jpg` |
| `thumbnailCreator` | String | Thumbnail Creator / Author / Attribution via **Wikimedia Commons** | `EU2017EE Estonian Presidency` |
| `thumbnailLicense` | String | Thumbnail License via **Wikimedia Commons** | `CC-BY 2.0 Generic` |
| `websiteURI` | String | **Wikidata** Property "official website" (P856) | `https://www.un.org/` |
| `embedURI` (SPECIAL CASE, TODO LATER!) | String | URI to be used for embedding inside the platform pages (eg. media details) | `https://embed.abgeordnetenwatch.de/profile/angela-merkel` |
| `socialMediaIDs` | Array | **Wikidata** Properties for social media handles "??" | `[{"label": "Instagram", "id": "bundeskanzlerin"}]` |
| `additionalInformation` | Object | eg. **Wikidata** Property "abgeordnetenwatch.de politician ID" | `{"abgeordnetenwatchID": "7643642"}` |

#### Additional Type-Specific Data Fields
These fields can be **returned in addition to Generic Data Fields**. 

**Type:** `memberOfParliament`
| Field | Type | Description | Examples | 
| :------------- | :---------- | :---------- | :---------- | 
| `birthDate` | String | **Wikidata** Property "date of birth" (P569) | `1954-07-17` |
| `party` | String | **Wikidata** Property "member of political party" (P102) | `Q49762` |
| `faction` | String | **Wikidata** Property "member of the German Bundestag > parliamentary group" | `Q1023134` |
| `gender` | String | **Wikidata** Property "sex or gender" (P512) | `female` |
| `firstName` | String | **Wikidata** Property "given name" (P735) | `Angela` |
| `lastName` | String | **Wikidata** Property "family name" (P734) | `Merkel` |
| `degree` | String | **Wikidata** Property "academic degree" (P512) | `Dr.` |


**Type:** `person`
| Field | Type | Description | Examples | 
| :------------- | :---------- | :---------- | :---------- | 
| `birthDate` | String | **Wikidata** Property "date of birth" (P569) | `1960-01-26` |
| `gender` | String | **Wikidata** Property "sex or gender" (P512) | `female` |
| `firstName` | String | **Wikidata** Property "given name" (P735) | `Inge` |
| `lastName` | String | **Wikidata** Property "family name" (P734) | `Deutschkron` |
| `degree` | String | **Wikidata** Property "academic degree" (P512) | `Dr.` |

