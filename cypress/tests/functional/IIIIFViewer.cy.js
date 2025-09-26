/**
 * Integration tests for the IIIF Viewer plugin.
 */

import {Files} from '../files.js';

describe('IIIF Viewer tests', function () {
    var title = "IIIF Viewer Test Submission TEST";

    var submission = {
        section: 'Articles',
        sectionId: 1,
        title: title,
        abstract: 'Test Submission to check if IIIF Viewers are working.',
        subtitle: '',
        authors: ["IIIF Viewer Test Author"],
        submitterRole: 'Journal manager',
        files: [
            {
                'file': 'dummy.pdf',
                'fileName': 'IIIFViewerTest.pdf',
                'mimeType': 'application/pdf',
                'genre': Cypress.env('defaultGenre')
            }
        ],
        identifiers: {
            pageNumber: '71-98',
        },
        urlPath: 'testing-iiif-viewer-submission-' + Cypress._.uniqueId(Date.now().toString()),
        licenceUrl: 'https://creativecommons.org/licenses/by/4.0/',
        publishIssueSections: [
            'Articles'
        ],
        galleys: [
            {
                label: 'PDF',
                genre: 'Article Text',
                mimeType: 'application/pdf',
            }
        ],
    };

    function assertOpenSeadragonLoaded() {
        // Check the DOM container
        cy.get('#openseadragon1 .openseadragon-container')
            .should('exist')
            .and('be.visible');

        //  Check the JS library is loaded
        cy.window().then(win => {
            expect(win.OpenSeadragon, 'OpenSeadragon library').to.exist;
        });

        //  Check the viewer instance was created
        cy.window().then(win => {
            expect(win.viewer, 'OpenSeadragon viewer instance').to.exist;
        });
    };

    function assertMiradorIsLoaded() {
        // Check the DOM container
        cy.get('#my-mirador')
            .should('exist')
            .and('be.visible');

        // Check the JS library is loaded
        cy.window().then(win => {
            expect(win.Mirador, 'Mirador library').to.exist;
        });

    };

    it('Enable The Plugin', function () {
        cy.login('admin', 'admin', 'publicknowledge');

        cy.get('nav').contains('Settings').click();
        // Ensure submenu item click despite animation
        cy.get('nav').contains('Website').click({force: true});
        cy.get('button[id="plugins-button"]').click();

        cy.get('input[id^="select-cell-iiifviewerplugin-enabled"]').then(($checkbox) => {
            if (!$checkbox.prop('checked')) {
                cy.wrap($checkbox).click();
            }
        });

        cy.get('input[id^="select-cell-iiifviewerplugin-enabled"]').should('be.checked');

    });


    it('Create a submission and check if IIIF Viewer is enabled first part', function () {
        console.log("TEST LOG1: creating submission");
        // Login as admin
        cy.login('admin', 'admin');
        cy.get('a').contains('admin').click();
        cy.get('a').contains('Dashboard').click();

        // Create a new submission
        cy.getCsrfToken();
        cy.window()
            .then(() => {
                return cy.createSubmissionWithApi(submission, this.csrfToken);
            })
            .then(xhr => {
                return cy.submitSubmissionWithApi(submission.id, this.csrfToken);
            })
            .then(xhr => {
                cy.visit('/index.php/publicknowledge/workflow/index/' + submission.id + '/1');
            });
    });

    it('Create a submission and check if IIIF Viewer is enabled with galleys', function () {
        // Login as admin
        cy.login('admin', 'admin');
        cy.get('a').contains('admin').click();
        cy.get('a').contains('Dashboard').click();

        // Create a new submission
        cy.getCsrfToken();
        cy.window()
            .then(() => {
                return cy.createSubmissionWithApi(submission, this.csrfToken);
            })
            .then(xhr => {
                return cy.submitSubmissionWithApi(submission.id, this.csrfToken);
            })
            .then(xhr => {
                cy.visit('/index.php/publicknowledge/workflow/index/' + submission.id + '/1');
            });

        //Add galleys
        for (var i = 0; i < Files.length; i++) {
            let file = Files[i];
            cy.openWorkflowMenu('Galleys')
            cy.get('button:contains("Add galley")').click();
            cy.waitJQuery();

            cy.get('input[id^=label-]').type(file.name, {delay: 0});
            cy.get('form#articleGalleyForm button:contains("Save")').click();
            cy.get('#genreId').select('Research Results');
            cy.waitJQuery();

            cy.readFile(file.path, null)
                .then((fileContent) => {
                    // Ensure fileContent is a correct string (JSON should already be valid)
                    var testFixture = Cypress.Buffer.from(fileContent);

                    // Upload the file using selectFile
                    cy.get('div[id^="fileUploadWizard"] input[type=file]')
                        .selectFile({
                            contents: testFixture, // JSON content
                            fileName: file.name, // Correct file name
                        }, {force: true}); // Force for hidden file inputs
                });

            cy.get('button').contains('Continue').click();
            cy.get('button').contains('Continue').click();
            cy.get('button').contains('Complete').click();
        };

    });


    it('Create a submission and check if IIIF Viewer is enabled in preview', function () {
        console.log("TEST LOG1: creating submission");
        // Login as admin
        cy.login('admin', 'admin');
        cy.get('a').contains('admin').click();
        cy.get('a').contains('Dashboard').click();

        // Create a new submission
        cy.getCsrfToken();
        cy.window()
            .then(() => {
                return cy.createSubmissionWithApi(submission, this.csrfToken);
            })
            .then(xhr => {
                return cy.submitSubmissionWithApi(submission.id, this.csrfToken);
            })
            .then(xhr => {
                cy.visit('/index.php/publicknowledge/workflow/index/' + submission.id + '/1');
            });

        //Add galleys
        for (var i = 0; i < Files.length; i++) {
            let file = Files[i];
            cy.openWorkflowMenu('Galleys')
            cy.get('button:contains("Add galley")').click();
            cy.waitJQuery();

            cy.get('input[id^=label-]').type(file.name, {delay: 0});
            cy.get('form#articleGalleyForm button:contains("Save")').click();
            cy.get('#genreId').select('Research Results');
            cy.waitJQuery();

            cy.readFile(file.path, null)
                .then((fileContent) => {
                    // Ensure fileContent is a correct string (JSON should already be valid)
                    var testFixture = Cypress.Buffer.from(fileContent);

                    // Upload the file using selectFile
                    cy.get('div[id^="fileUploadWizard"] input[type=file]')
                        .selectFile({
                            contents: testFixture, // JSON content
                            fileName: file.name, // Correct file name
                        }, {force: true}); // Force for hidden file inputs
                });


            cy.get('button').contains('Continue').click();
            cy.get('button').contains('Continue').click();
            cy.get('button').contains('Complete').click();
        };

        cy.openWorkflowMenu('Submission')
        cy.get('button').contains('Accept and Skip Review').click();
        cy.get('button').contains('Record Decision').click();
        cy.get('a').contains('View Submission').click();
        cy.get('button').contains('Preview').click();
        cy.waitJQuery();


        cy.log("TEST LOG5")

        //check plugin is workin in preview
        cy.url().then((currentUrl) => {
            Files.forEach((file) => {
                cy.contains('a', file.name).click();
                cy.wait(2000); //wait until ressource is loaded

                if (file.name.endsWith('.json')) {
                    if (file.name === 'NotAManifest.json') {
                        cy.get('#my-mirador')
                            .should('not.exist')
                    } else {
                        assertMiradorIsLoaded();
                    }
                } else {
                    assertOpenSeadragonLoaded();
                }
                cy.visit(currentUrl);
            });
        });

    });


    it('Check IIIF Viewer enabled when submitted', function () {
        cy.login('admin', 'admin'); // submission.id

        cy.visit('/index.php/publicknowledge/workflow/access/' + submission.id);
        cy.publish('1', 'Vol. 1 No. 2 (2014)');

        Files.forEach((file) => {
            cy.visit('/index.php/publicknowledge/article/view/' + submission.id);
            cy.contains('a', file.name).click();
            cy.wait(2000); //wait until ressource is loaded

            if (file.name.endsWith('.json')) {
                assertMiradorIsLoaded();

            } else {
                assertOpenSeadragonLoaded();
            }
        });
    });
});
