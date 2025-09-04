/**
 * Integration tests for the IIIF Viewer plugin.
 */
describe('IIIF Viewer tests', function () {
	var title = "IIIF Viewer Test Submission TEST";

	//Files for test galleys
	var Files = [
		{
			name: "seadragon.png",
			path: 'plugins/generic/iiifViewer/cypress/tests/data/seadragon.png',
			type: "image/png",
		},
		{
			name: "mongolica.json",
			path: 'plugins/generic/iiifViewer/cypress/tests/data/mongolica.json',
			type: "text/json",
		},
	];

	var submission = {
		sectionId: 1,
		title: title,
		abstract: 'Test Submission to check if IIIF Viewers are working.',
		subtitle: '',
		authors: ["IIIF Viewer Test Author"],
		submitterRole: 'Journal manager',
		chapters: [{title: "test chapter", contributors: []}],
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

	it('Create And Validate IIIF Files', function () {

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


		cy.get('button#workflow-button').click();
		cy.get('a').contains('Accept and Skip Review').click();
		cy.get('button').contains('Record Decision').click();
		cy.get('a').contains('View Submission').click();

		cy.get('button#publication-button').click();
		cy.get('button#publicationFormats-button').click();


		//Add Publication Formats that contain the files
		for (var i = 0; i < Files.length; i++) {
			let file = Files[i];

			cy.get('a[id^="component-grid-catalogentry-publicationformatgrid-addFormat-button-"]').click();
			cy.wait(400);
			cy.get('input[id^=name-en-]').type(file.name, {delay: 0});
			cy.get('button:contains("OK")').click();

			cy.wait(250);

			cy.get('tbody a').filter(':contains("Change File")').last().click();

			cy.get('#genreId').select("Figure");

			cy.readFile(file.path, null).then((fileContent) => {
				let testFixture = Cypress.Buffer.from(fileContent);
				cy.get('div[id^="fileUploadWizard"] input[type=file]')
					.selectFile({
							contents: testFixture,
							fileName: file.name,
						},
						{force: true} // needed if the input is hidden
					);
			});

			cy.contains('button', 'Continue').click();
			cy.contains('button', 'Continue').click();
			cy.contains('button', 'Complete').click();
		}
		;

		cy.get('a:contains("Not Available")').then(($links) => {
			for (let i = 0; i < $links.length; i++) {
				cy.contains('a', 'Not Available').first().click();
				cy.contains('button', 'OK').click();
			}
		});
		cy.get('a:contains("Awaiting Approval")').then(($links) => {
			for (let i = 0; i < $links.length; i++) {
				cy.contains('a', 'Awaiting Approval').first().click();
				cy.contains('button', 'OK').click();
			}
		});
		cy.get('a:contains("Set Terms")').then(($links) => {
			for (let i = 0; i < $links.length; i++) {
				cy.contains('a', 'Set Terms').first().click();
				cy.contains('label', 'Open Access').click();
				cy.wait(500);
				cy.get('.pkp_modal_panel').within(() => {
					cy.contains('label', 'Open Access').click();
					cy.wait(500);
					cy.contains('button[name="submitFormButton"]', 'Save').click();
				});
			}
		});

		//Add a file to the chapter
		cy.get('button#chapters-button').click();
		cy.contains('a', 'test chapter').click();

		cy.contains('.pkp_modal_panel label', Files[0].name).click();
		cy.contains('.pkp_modal_panel button', 'Save').should('be.visible').click();


		// Wait for modal to disappear and notifications to flush
		cy.get('.pkp_modal_panel').should('not.exist');
		cy.flushNotifications();

		//publish
		cy.get('.pkp_modal_panel .editor-class').should('exist');
		cy.contains('.pkp_modal_panel button', 'Publish').click();
		cy.get('.pkp_modal_panel button').contains('Publish').click();
		cy.get('.pkpHeader__actions a').contains('View').click();

		// Check the files are displayed with the IIIF viewer
		cy.url().then((currentUrl) => {
			Files.forEach((file) => {
				cy.contains('a', file.name).click();
				cy.wait(2000); //wait until ressource is loaded

				if (file.name.endsWith('.json')) {
					assertMiradorIsLoaded();
				} else {
					assertOpenSeadragonLoaded();
				}
				cy.visit(currentUrl);
			});
		});
	});
});
