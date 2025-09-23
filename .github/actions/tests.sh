#!/bin/bash

set -e

echo "Run cypress tests"
npx cypress run  --browser chrome --headed --config  '{"specPattern":["plugins/generic/iiifViewer/cypress/tests/functional/*.cy.js"]}'

echo "Validate  against erudit-style"