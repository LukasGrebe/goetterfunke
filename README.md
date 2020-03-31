Götterfunke
==============

Automate some Google Analytics (GA) Management Tasks

Currently supports GA Custom Dimensions:

- Read out GA CD Configuration as JSON
- Update GA CD Configuration from JSON

## Usage

- `php goetterfunke.php listProperties` to list <account id> <property id> <property name>. Copy-Paste a pair of account & property ID as params to the following commands.
- `php goetterfunke.php getCDsJSON <account id> <property id>` to output a property's custom dimension configuration as JSON. (Use `jq 'map({index,name,scope,active})'` to remove unnecessary properties - see [jq](https://stedolan.github.io/jq/))
- `php goetterfunke.php setCDs <account id> <property id> <config.json>` to update the custom dimension configuration to a the given config json. The JSON Content Structure must match the output of `getCDsJSON`


## Setup Götterfunke

1. Download source or clone repository.
1. Run `composer install` to install Dependancies
1. Setup Google API oAuth:

    1. place the `auth.html` on your webserver or use `https://lukas.grebe.me/goetterfunke/auth/`
    1. Follow the instructions to [Setup oAuth](https://support.google.com/cloud/answer/6158849?hl=en) - create `web application` type OAuth 2.0 Client Credentials, and setup the URL of `auth.php`
    2. place the generated credentials json file in `/credentials/`
    3. update `goetterfunke.php` with the name of your Credentials file and location of your auth.php copy in lin 5 `$client = getClient('<your json>','<your auth.php>');`

## Working with Custom Dimension JSON
### Quick Start
1. get the current configuration from a Property using `php goetterfunke.php getCDsJSON <account id> <property id> | jq 'map({index,name,scope,active})' > current.json`
2. Modify and save `current.json`
3. update any property setup with `setCDs <account id> <property id> current.json`

### Working with confluence

If you are using Confluences [Page Properties Macro](https://confluence.atlassian.com/doc/page-properties-macro-184550024.html) you can

_This assumes your Confluence Page Name is the name of your Custom Dimension as to be set in Analytics and have the Page Keys `Scope`,`Index`,`Status` where any Status value that does not include `inactive` will be set as __Active__ in Google Analytics._

1. read out page properties as JSON via the API `https://<your Confluence>/rest/masterdetail/1.0/detailssummary/lines?cql=label%3D<your ga_custom_dimension Page label>&spaceKey=<your confluence Space Key>&pageSize=300&pageIndex=0&headings=Scope,Status,Index`
2. transform the JSON to be usable with Götterfunke: `jq '.detailLines | map({"name": .title, "scope": (.details[0] | gsub("<[^>]+>";"")),"active": (.details[1] | gsub("<[^>]+>";"") | contains("inactive") | not), "index": ((.details[2] | gsub("<[^>]+>";""))|tonumber)}) | sort_by(.index)' < confluence.json > target-from-confluence.json`
3. set CDs with `php goetterfunke.php setCDs <account id> <property id> target-from-confluence.json`

## Note

- feel free to get in touch via Github Issues or [twitter](https://twitter.com/LukasGrebe)
- This code is SUPER scrappy and has lots of smell and other issues. Works for me 🤷‍♂️ - see above
