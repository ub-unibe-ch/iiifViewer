#!/bin/bash

set -e

# Add python libraries
#sudo apt update
#sudo apt install build-essential zlib1g-dev libncurses5-dev libgdbm-dev libnss3-dev libssl-dev libsqlite3-dev libreadline-dev libffi-dev wget libbz2-dev
#sudo add-apt-repository ppa:deadsnakes/ppa -y
#sudo apt install python3.7
#sudo apt-get install python3-lxml xmlstarlet
#pip install lxml
#
#
#git clone -b ${APP_BRANCH} https://github.com/pkp/jatsTemplate plugins/generic/jatsTemplate
#php lib/pkp/tools/installPluginVersion.php plugins/generic/jatsTemplate/version.xml
#php lib/pkp/tools/installPluginVersion.php plugins/oaiMetadataFormats/oaiJats/version.xml

echo "Run cypress tests"
npx cypress run --config  '{"specPattern":["plugins/generic/iiifViewer/cypress/tests/functional/*.cy.js"]}'

echo "Validate  against erudit-style"