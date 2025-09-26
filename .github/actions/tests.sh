#!/bin/bash
set -e

echo "Run cypress tests"
npx cypress run --config  '{"specPattern":["plugins/generic/iiifViewer/cypress/tests/functional/*.cy.js"]}'

echo "Done"