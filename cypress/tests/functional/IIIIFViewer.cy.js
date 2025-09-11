/**
 * Integration tests for the IIIF Viewer plugin.
 */

import { Files } from '../files.js';

describe('IIIF Viewer tests', function () {
	var title = "IIIF Viewer Test Submission TEST";
	var issueTitle = 'Vol. 1 No. 2 (2014)';

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

	it('Enable The Plugin', function() {
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


	it('Create a submission', function () {

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
			cy.get('button#publication-button').click();
			cy.get('button#galleys-button').click();
			cy.get('a[id^="component-grid-articlegalleys-articlegalleygrid-addGalley-button-"]').click();
			cy.wait(400);

			cy.get('input[id^=label-]').type(file.name, {delay: 0});
			cy.get('form#articleGalleyForm button:contains("Save")').click();
			cy.get('#genreId').select('Article Text');
			cy.wait(250);

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
			// cy.get('a').contains('Preview').click();
			// cy.get('a').contains('JSON').click();
		}
		;

		cy.get('button#workflow-button').click();
		cy.get('a').contains('Accept and Skip Review').click();
		cy.get('button').contains('Record Decision').click();
		cy.get('a').contains('View Submission').click();
		cy.get('a').contains('Preview').click();

	});

	it('Check IIIF Viewer enabled in preview', function () {
		cy.login('admin', 'admin');
		cy.get('a').contains('admin').click();
		cy.get('a').contains('Dashboard').click();

		cy.contains('li', title).within(() => {
			cy.contains('a', 'View').click(); // select our test publication
		});

		cy.get('a').contains('Preview').click();
		//
		cy.url().then((currentUrl) => {
			Files.forEach((file) => {
				cy.contains('a', file.name).click();
				cy.wait(2000); //wait until ressource is loaded

				if(file.name.endsWith('.json')){
					assertMiradorIsLoaded();
				} else {
					assertOpenSeadragonLoaded();
				}
				cy.visit(currentUrl);
			});
		});
	});


	it('Check IIIF Viewer enabled', function () {
		cy.login('admin', 'admin');
		cy.get('a').contains('admin').click();
		cy.get('a').contains('Dashboard').click();

		cy.contains('li', title).within(() => {
			cy.contains('a', 'View').click(); // select our test publication
		});

		cy.get('a').contains('Send To Production').click();
		cy.get('button').contains('Record Decision').click();
		cy.get('a').contains('View Submission').click();

		cy.get('button').contains('Schedule For Publication').click();
		cy.get('button').contains('Issue').click();
		cy.get('button').contains('Assign to Issue').click();

		cy.get('div[role="dialog"]').within(() => { //publish
			cy.get('#assignToIssue-issueId-control').select(issueTitle);
			cy.get('button[label="Save"]').click();
			cy.get('button').contains('Publish').click();

			// cy.get('button').contains('Schedule For Publication').click();

		});

		cy.visit(`/index.php/publicknowledge/index`);

		cy.url().then((currentUrl) => {
			Files.forEach((file) => {
				cy.contains('a', file.name).click();
				cy.wait(2000); //wait until ressource is loaded

				if(file.name.endsWith('.json')){
					assertMiradorIsLoaded();
				} else {
					assertOpenSeadragonLoaded();
				}
				cy.visit(currentUrl);
			});
		});
	});
});
