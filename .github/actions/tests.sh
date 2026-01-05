#!/bin/bash
<<<<<<< HEAD
=======

>>>>>>> 2e072f89f1c634b8dcb2f9b302e398855807b5e5
set -e

echo "Run cypress tests"
npx cypress run --config  '{"specPattern":["plugins/generic/iiifViewer/cypress/tests/functional/*.cy.js"]}'

echo "Done"
