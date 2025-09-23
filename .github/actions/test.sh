#!/bin/bash

set -e

echo "Run cypress tests"
npx cypress run  --headless --browser chrome  --config  '{"specPattern":["plugins/generic/iiifViewer/cypress/tests/functional/*.cy.js"]}'
