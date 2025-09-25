echo "Run cypress tests"
npx cypress run --browser electron --headless -- --disable-gpu --disable-software-rasterizer \
  --config '{"specPattern":["plugins/generic/iiifViewer/cypress/tests/functional/*.cy.js"]}'

echo "Validate  against erudit-style"