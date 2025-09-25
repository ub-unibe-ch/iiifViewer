echo "Run cypress tests"
npx cypress run --headless --config '{"specPattern":["plugins/generic/iiifViewer/cypress/tests/functional/*.cy.js"]}'

echo "Validate  against erudit-style"
