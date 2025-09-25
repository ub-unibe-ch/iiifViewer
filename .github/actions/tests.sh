echo "Run cypress tests"

export TERM=xterm
npx cypress run --no-sandbox --config   '{"specPattern":["plugins/generic/iiifViewer/cypress/tests/functional/*.cy.js"]}'

echo "Validate  against erudit-style"