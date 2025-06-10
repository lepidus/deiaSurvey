import '../support/commands.js';

function assertDefaultQuestionsDisplay(authorEmail) {
    cy.contains('The demographic data from this questionnaire will be associated with the e-mail address: ' + authorEmail);

    cy.contains('.questionTitle', 'Gender');
    cy.contains('.questionDescription', 'With which gender do you most identify? Please select one option:');
    cy.contains('label', 'Woman');
    cy.contains('label', 'Man');
    cy.contains('label', 'Non-binary or gender diverse');
    cy.contains('label', 'Prefer not to inform');

    cy.contains('.questionTitle', 'Race');
    cy.contains('.questionDescription', 'How would you identify yourself in terms of race? Please select ALL the groups that apply to you:');
    cy.contains('label', 'Asian or Pacific Islander');
    cy.contains('label', 'Black');
    cy.contains('label', 'Hispanic or Latino/a/x');
    cy.contains('label', 'Indigenous (e.g. North American Indian Navajo, South American Indian Quechua, Aboriginal or Torres Strait Islander)');
    cy.contains('label', 'Middle Eastern or North African');
    cy.contains('label', 'White');
    cy.contains('label', 'Prefer not to inform');
    cy.contains('label', 'Self describe');

    cy.contains('.questionTitle', 'Ethnicity');
    cy.contains('.questionDescription', "What are your ethnic origins or ancestry? Please select ALL the geographic areas from which your family's ancestors first originated:");
    cy.contains('label', 'Western Europe');
    cy.contains('label', 'Eastern Europe');
    cy.contains('label', 'North Africa');
    cy.contains('label', 'Sub-Saharan Africa');
    cy.contains('label', 'West Asia / Middle East');
    cy.contains('label', 'South and Southeast Asia');
    cy.contains('label', 'East and Central Asia');
    cy.contains('label', 'Pacific / Oceania');
    cy.contains('label', 'North America');
    cy.contains('label', 'Central America and Caribbean');
    cy.contains('label', 'South America');
    cy.contains('label', 'Prefer not to inform');
    cy.contains('label', 'Self describe');

    cy.contains('Demographic data is collected in accordance with this journal\'s privacy statement');
}

function answerDefaultQuestions() {
    cy.contains('label', 'Woman').within(() => {
        cy.get('input').check();
    });
    cy.contains('label', 'Black').within(() => {
        cy.get('input').check();
    });
    cy.contains('label', 'South America').within(() => {
        cy.get('input').check();
    });

    cy.contains('button', 'Save').click();
}

function assertResponsesOfExternalAuthor(authorEmail) {
    cy.contains('Showing demographic data associated with the e-mail address: ' + authorEmail);

    cy.contains('Woman');
    cy.contains('Black');
    cy.contains('South America');

    cy.contains('You can check you demographic data at any time by visiting this same address');
    cy.contains('By creating a new account in the system with this same e-mail address, your demographic data will automatically be associated with the new account');
}

function assertResponsesOfRegisteredUser() {
    cy.contains('a', 'Demographic Data').click();
    cy.get('input[name="demographicDataConsent"][value=1]').should('be.checked');

    cy.contains('label', 'Woman').within(() => {
        cy.get('input').should('be.checked');
    });
    cy.contains('label', 'Black').within(() => {
        cy.get('input').should('be.checked');
    });
    cy.contains('label', 'South America').within(() => {
        cy.get('input').should('be.checked');
    });
}

function newSubmission(data) {
	if (!('files' in data)) data.files = [{
		'file': 'dummy.pdf',
		'fileName': data.title + '.pdf',
		'fileTitle': data.title,
		'genre': Cypress.env('defaultGenre')
	}];
	if (!('keywords' in data)) data.keywords = [];
	if (!('additionalAuthors' in data)) data.additionalAuthors = [];
	if ('additionalFiles' in data) {
		data.files = data.files.concat(data.additionalFiles);
	}

	cy.get('a:contains("Make a New Submission"), div#myQueue a:contains("New Submission")').click();

    // === Submission Step 1 ===
    if (Cypress.env('contextTitles').en_US == 'Journal of Public Knowledge' && 'section' in data) {
        cy.get('select[id="sectionId"],select[id="seriesId"]').select(data.section);
    }
	cy.get('input[id^="checklist-"]').click({multiple: true});
	cy.get('input[id=privacyConsent]').click();
	if ('submitterRole' in data) {
		cy.get('input[name=userGroupId]').parent().contains(data.submitterRole).click();
	}
	cy.get('button.submitFormButton').click();

	// === Submission Step 2 ===
    if (Cypress.env('contextTitles').en_US == 'Public Knowledge Preprint Server') {
        data.files.forEach(file => {
			cy.get('a:contains("Add galley")').click();
			cy.wait(2000);
			cy.get('div.pkp_modal_panel').then($modalDiv => {
				cy.wait(3000);
				if ($modalDiv.find('div.header:contains("Create New Galley")').length) {
					cy.get('div.pkp_modal_panel input[id^="label-"]').type('PDF', {delay: 0});
					cy.get('div.pkp_modal_panel button:contains("Save")').click();
					cy.wait(2000);
				}
			});
			cy.get('select[id=genreId]').select(file.genre);
			cy.fixture(file.file, 'base64').then(fileContent => {
				cy.get('input[type=file]').upload(
					{fileContent, 'fileName': file.fileName, 'mimeType': 'application/pdf', 'encoding': 'base64'}
				);
			});
			cy.get('button').contains('Continue').click();
			cy.wait(2000);
			for (const field in file.metadata) {
				cy.get('input[id^="' + Cypress.$.escapeSelector(field) + '"]:visible,textarea[id^="' + Cypress.$.escapeSelector(field) + '"]').type(file.metadata[field], {delay: 0});
				cy.get('input[id^="language"').click({force: true});
			}
			cy.get('button').contains('Continue').click();
			cy.get('button').contains('Complete').click();
		});
    } else {
        cy.get('button:contains("Add File")');

        const allowException = function(error, runnable) {
            return false;
        }
        cy.on('uncaught:exception', allowException);

        const primaryFileGenres = ['Article Text', 'Book Manuscript', 'Chapter Manuscript'];
        data.files.forEach(file => {
            cy.fixture(file.file, 'base64').then(fileContent => {
                cy.get('input[type=file]').upload(
                    {fileContent, 'fileName': file.fileName, 'mimeType': 'application/pdf', 'encoding': 'base64'}
                );
                var $row = cy.get('a:contains("' + file.fileName + '")').parents('.listPanel__item');
                if (primaryFileGenres.includes(file.genre)) {
                    $row.get('button:contains("' + file.genre + '")').last().click();
                    $row.get('span:contains("' + file.genre + '")');
                } else {
                    $row.get('button:contains("Other")').last().click();
                    cy.get('#submission-files-container .modal label:contains("' + file.genre + '")').click();
                    cy.get('#submission-files-container .modal button:contains("Save")').click();
                }
                $row.get('button:contains("What kind of file is this?")').should('not.exist');
            });
        });
    }

	cy.location('search')
		.then(search => {
			data.id = parseInt(search.split('=')[1], 10);
		});

	cy.get('button').contains('Save and continue').click();

	// === Submission Step 3 ===
	cy.get('input[id^="title-en_US-"').type(data.title, {delay: 0});
	cy.get('label').contains('Title').click(); // Close multilingual popover
	cy.get('textarea[id^="abstract-en_US"]').then(node => {
		cy.setTinyMceContent(node.attr('id'), data.abstract);
	});
	cy.get('ul[id^="en_US-keywords-"]').then(node => {
		data.keywords.forEach(keyword => {
			node.tagit('createTag', keyword);
		});
	});
	data.additionalAuthors.forEach(author => {
		if (!('role' in author)) author.role = 'Author';
		cy.get('a[id^="component-grid-users-author-authorgrid-addAuthor-button-"]').click();
		cy.wait(250);
		cy.get('input[id^="givenName-en_US-"]').type(author.givenName, {delay: 0});
		cy.get('input[id^="familyName-en_US-"]').type(author.familyName, {delay: 0});
		cy.get('select[id=country]').select(author.country);
		cy.get('input[id^="email"]').type(author.email, {delay: 0});
		if ('affiliation' in author) cy.get('input[id^="affiliation-en_US-"]').type(author.affiliation, {delay: 0});
		cy.get('label').contains(author.role).click();
		cy.get('form#editAuthor').find('button:contains("Save")').click();
		cy.get('div[id^="component-grid-users-author-authorgrid-"] span.label:contains("' + Cypress.$.escapeSelector(author.givenName + ' ' + author.familyName) + '")');
	});
	cy.waitJQuery();
	cy.get('form[id=submitStep3Form]').find('button').contains('Save and continue').click();

	// === Submission Step 4 ===
	cy.waitJQuery();
	cy.get('form[id=submitStep4Form]').find('button').contains('Finish Submission').click();
	cy.get('button.pkpModalConfirmButton').click();
	cy.waitJQuery();
	cy.get('h2:contains("Submission complete")');
}

describe('Demographic Data - External contributors data collecting', function() {
    let firstSubmissionData;
    let secondSubmissionData;
    before(function() {
        firstSubmissionData = {
            section: 'Articles',
            title: "Test scenarios to automobile vehicles",
			abstract: 'Description of test scenarios for cars, motorcycles and other vehicles',
			keywords: ['plugin', 'testing'],
            additionalAuthors: [
                {
                    'givenName': 'Susanna',
                    'familyName': 'Almeida',
                    'email': 'susy.almeida@outlook.com',
                    'country': 'Brazil'
                }
            ]
		};
        secondSubmissionData = {
            section: 'Articles',
            title: "Advancements in tests of automobile vehicles",
			abstract: 'New improvements on tests of cars, motorcycles and other vehicles',
			keywords: ['plugin', 'testing'],
            contributors: [
                {
                    'givenName': 'Susanna',
                    'familyName': 'Almeida',
                    'email': 'susy.almeida@outlook.com',
                    'country': 'Brazil'
                }
            ]
		};
    });

    it('Creates new submission for collection of demographic data from external authors', function() {
        cy.login('ckwantes', null, 'publicknowledge');
        
        cy.contains('a', 'Demographic Data').click();
        cy.get('input[name="demographicDataConsent"][value=0]').click();
        cy.get('#demographicDataForm .submitFormButton').click();
        cy.wait(1000);

        cy.contains('Back to Submissions').click();
        newSubmission(firstSubmissionData);

        if (Cypress.env('contextTitles').en_US == 'Journal of Public Knowledge') {
            cy.logout();
            cy.findSubmissionAsEditor('dbarnes', null, 'Kwantes');
            cy.sendToReview();
            cy.assignReviewer('Julie Janssen');
            cy.recordEditorialDecision('Accept Submission');
        }
    });

    it('Email was sent to contributors without registration', function () {
        cy.visit('localhost:8025');

        cy.get('b:contains("Request for demographic data collection")').should('have.length', 1);
        cy.contains('b', 'Request for demographic data collection')
            .parent().parent().parent()
            .within((node) => {
                cy.contains('susy.almeida@outlook.com');
            });
        cy.get('b:contains("Request for demographic data collection")').click();

        cy.get('#nav-tab button:contains("Text")').click();

        cy.contains('In order to improve our publication, we collect demographic data from the authors of our submissions through an online questionnaire');
        cy.contains('If you do not wish to register, we recommend that you access the following address:');
        cy.contains("If you don't have an ORCID record, you can fill in the questionnaire at the following address:");

        cy.get('#nav-html-tab').click();
        cy.get('#preview-html').then($iframe => {
            let iframeDocument = $iframe.contents().find('body');

            cy.wrap(iframeDocument)
                .find('a')
                .eq(1)
                .contains('Demographic Questionnaire')
                .invoke('attr', 'target', '_self')
                .invoke('attr', 'href').then(href => {
                    cy.writeFile('cypress/fixtures/data.json', { url: href })
                });
        });
    });

    it('Acess questionnaire from contributors without registration', function () {
        cy.readFile('cypress/fixtures/data.json').then((data) => {
            cy.visit(data.url);
        });

        assertDefaultQuestionsDisplay('susy.almeida@outlook.com');

        cy.url().then(url => {
            cy.visit(url + 'breakToken');
        });
        cy.contains('Demographic Questionnaire');
        cy.contains('Only the author can access this page');
    });

    it('Contributor without registration answers demographic questionnaire', function () {
        cy.readFile('cypress/fixtures/data.json').then((data) => {
            cy.visit(data.url);
        });

        answerDefaultQuestions();

        cy.contains('Thanks for answering our demographic questionnaire');
        cy.contains('a', 'Check my answers').click();

        assertResponsesOfExternalAuthor('susy.almeida@outlook.com');
    });

    it('New submission is created with same contributor', function () {
        cy.login('ckwantes', null, 'publicknowledge');

        newSubmission(secondSubmissionData);

        if (Cypress.env('contextTitles').en_US == 'Journal of Public Knowledge') {
            cy.logout();
            cy.findSubmissionAsEditor('dbarnes', null, 'Kwantes');
            cy.sendToReview();
            cy.assignReviewer('Julie Janssen');
            cy.recordEditorialDecision('Accept Submission');
        }
    });

    it('E-mail for demographic data collection is not sent again', function () {
        cy.visit('localhost:8025');
        cy.get('b:contains("Request for demographic data collection")').should('have.length', 1);
    });

    it('Contributor without registration deletes his own demographic data', function () {
        cy.readFile('cypress/fixtures/data.json').then((data) => {
            cy.visit(data.url);
        });

        cy.contains('a', 'Delete my demographic data').click();

        cy.contains('Demographic data deletion');
        cy.contains('Are you sure you want to delete your demographic data? This action cannot be undone.');
        cy.contains('Delete my demographic data').click();

        cy.contains('Your demographic data has been deleted');
    });

    it('Editor goes back and accepts submission again', function () {
        cy.findSubmissionAsEditor('dbarnes', null, 'Kwantes');

        cy.get('.ui-tabs-anchor:contains("Review")').click();
        cy.get('.pkp_workflow_change_decision').click();
        cy.recordEditorialDecision('Accept Submission');
    });

    it('New data collection email was sent on new submission', function() {
        cy.visit('localhost:8025');
        cy.get('b:contains("Request for demographic data collection")').should('have.length', 2);
        cy.get('b:contains("Request for demographic data collection")').eq(0).click();

        cy.get('#nav-html-tab').click();
        cy.get('#preview-html').then($iframe => {
            let iframeDocument = $iframe.contents().find('body');

            cy.wrap(iframeDocument)
                .find('a')
                .eq(1)
                .contains('Demographic Questionnaire')
                .invoke('attr', 'target', '_self')
                .invoke('attr', 'href').then(href => {
                    cy.writeFile('cypress/fixtures/data.json', { url: href })
                });
        });
    });

    it('Contributor answers demographic questionnaire on new submission', function () {
        cy.readFile('cypress/fixtures/data.json').then((data) => {
            cy.visit(data.url);
        });

        answerDefaultQuestions();
        cy.contains('Thanks for answering our demographic questionnaire');
    });

    it('Responses reference is migrated when author registers', function () {
        cy.register({
            'username': 'susyalmeida',
            'givenName': 'Susanna',
            'familyName': 'Almeida',
            'email': 'susy.almeida@outlook.com',
            'affiliation': 'Universidade Federal de Santa Catarina',
            'country': 'Brazil'
        });

        cy.contains('a', 'Edit My Profile').click();
        cy.contains('a', 'Demographic Data').click();

        assertResponsesOfRegisteredUser();
    });
});