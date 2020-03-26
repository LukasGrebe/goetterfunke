Götterfunke
==============

Google Analytics Code as Configuration

Setup:

1. Run ```composer install``` to install Dependancies
1. Follow the instructions to [Create a Service Account](https://developers.google.com/api-client-library/php/auth/service-accounts#creatinganaccount)
2. Download the JSON credentials to ```./credentials/Goetterfunke.json```
3. Add the Service Account Email to your Analytics Account with the necessary rights

An Active CD is any string that does not include the substring "inactive"

https://<your Confluence>/rest/masterdetail/1.0/detailssummary/lines?cql=label%3D<your ga_custom_dimension label>&spaceKey=<your confluence Space Key>&pageSize=300&pageIndex=0&headings=Scope,Status,Index

save as confluence .json
using https://stedolan.github.io/jq/
`jq '.detailLines | map({"name": .title, "scope": (.details[0] | gsub("<[^>]+>";"")),"status": (.details[1] | gsub("<[^>]+>";"")), "index": ((.details[2] | gsub("<[^>]+>";""))|tonumber)}) | sort_by(.index)' < confluence.json > target.json`

