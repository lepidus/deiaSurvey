import '../support/commands.js';

function readCollectionEmail(expectedCount) {
    cy.waitJQuery();
    const mailpitUrl = Cypress.env('mailpitUrl') || 'http://localhost:8025';
    return cy.request({
        url: mailpitUrl + '/api/v1/search',
        qs: {query: 'to:susy.almeida@outlook.com subject:"Request for DEIA data collection"'},
        log: false
    }).then(({body}) => {
        expect(body.messages).to.have.length(expectedCount);
        return cy.request({url: mailpitUrl + '/api/v1/message/' + body.messages[0].ID, log: false});
    }).then(({body}) => body);
}

function getQuestionnaireUrl(email) {
    const document = new DOMParser().parseFromString(email.HTML, 'text/html');
    const link = Array.from(document.querySelectorAll('a'))
        .find(link => link.textContent.trim() === 'DEIA Survey');
    expect(link, 'external questionnaire link').not.to.equal(undefined);
    return link.getAttribute('href');
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
    cy.contains('Showing data associated with the e-mail address: ' + authorEmail);

    cy.contains('Woman');
    cy.contains('Black');
    cy.contains('South America');

    cy.contains('You can check your data at any time by visiting this same address');
    cy.contains('By creating a new account in the system with this same e-mail address, your data will automatically be associated with the new account');
}

function assertResponsesOfRegisteredUser() {
    cy.contains('a', 'DEIA Survey').click();
    cy.get('input[name="deiaDataConsent"][value=1]').should('be.checked');

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
	cy.get('input[name="privacyConsent"]').click();
	if ('submitterRole' in data) {
		cy.get('input[name=userGroupId]').parent().contains(data.submitterRole).click();
	}
	cy.get('button.submitFormButton').click();

	// === Submission Step 2 ===
    if (Cypress.env('contextTitles').en_US == 'Public Knowledge Preprint Server') {
        data.files.forEach(file => {
			cy.get('a:contains("Add galley")').click();
			cy.waitJQuery();
			cy.get('div.pkp_modal_panel').then($modalDiv => {
				cy.waitJQuery();
				if ($modalDiv.find('div.header:contains("Create New Galley")').length) {
					cy.get('div.pkp_modal_panel input[id^="label-"]').type('PDF', {delay: 0});
					cy.get('div.pkp_modal_panel button:contains("Save")').click();
					cy.waitJQuery();
				}
			});
			cy.get('select[id=genreId]').select(file.genre);
			cy.fixture(file.file, 'base64').then(fileContent => {
				cy.get('input[type=file]').upload(
					{fileContent, 'fileName': file.fileName, 'mimeType': 'application/pdf', 'encoding': 'base64'}
				);
			});
			cy.get('button').contains('Continue').click();
			cy.waitJQuery();
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
        // The native modal takes focus after its opening animation.
        cy.get('.pkp_modal:visible').should('have.focus');
        cy.get('form#editAuthor').within(() => {
            cy.get('input[id^="givenName-en_US-"]').type(author.givenName, {delay: 0});
            cy.get('input[id^="familyName-en_US-"]').type(author.familyName, {delay: 0});
            cy.get('select[id=country]').select(author.country);
            cy.get('input[id^="email"]').type(author.email, {delay: 0});
            if ('affiliation' in author) cy.get('input[id^="affiliation-en_US-"]').type(author.affiliation, {delay: 0});
            cy.get('label').contains(author.role).click();
            cy.contains('button', 'Save').click();
        });
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

describe('DEIA Survey - External contributors data collecting', function() {
    let firstSubmissionData;
    let secondSubmissionData;
    it('Associates external responses only after the registered author confirms the email', function () {
        let questionnaireUrl;
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
            additionalAuthors: [
                {
                    'givenName': 'Susanna',
                    'familyName': 'Almeida',
                    'email': 'susy.almeida@outlook.com',
                    'country': 'Brazil'
                }
            ]
		};

        // Create and accept a submission with an external contributor.
        cy.logout();
        cy.login('ckwantes', null, 'publicknowledge');

        cy.contains('a', 'DEIA Survey').click();
        cy.get('input[name="deiaDataConsent"][value=0]').click();
        cy.get('#deiaSurveyForm .submitFormButton').click();
        cy.waitJQuery();

        cy.contains('Back to Submissions').click();
        newSubmission(firstSubmissionData);

        if (Cypress.env('contextTitles').en_US == 'Journal of Public Knowledge') {
            cy.logout();
            cy.findSubmissionAsEditor('dbarnes', null, 'Kwantes');
            cy.sendToReview();
            cy.assignReviewer('Julie Janssen');
            cy.recordEditorialDecision('Accept Submission');
        }

        // Check the actual email without navigating away from the application.
        readCollectionEmail(1).then(email => {
            const text = email.Text.replace(/\s+/g, ' ');
            expect(text.includes('we collect DEIA (Diversity, Equity, Inclusion, and Accessibility) data')).to.equal(true);
            expect(text.includes('If you do not wish to register')).to.equal(true);
            expect(text.includes("If you don't have an ORCID record")).to.equal(true);
            questionnaireUrl = getQuestionnaireUrl(email);
            cy.visit(questionnaireUrl, {log: false});
        });

        cy.assertDefaultQuestionsDisplay('questionnairePage', 'susy.almeida@outlook.com');

        cy.url().then(url => {
            cy.visit(url + 'breakToken');
        });
        cy.location('pathname').should('match', /\/user\/authorizationDenied$/);
        cy.contains('Access denied.').should('be.visible');
        cy.get('#deiaSurveyForm').should('not.exist');

        // Contributor without registration answers DEIA survey.
        cy.then(() => cy.visit(questionnaireUrl, {log: false}));

        answerDefaultQuestions();

        cy.contains('Thanks for answering our DEIA Survey');
        cy.contains('a', 'Check my answers').click();

        assertResponsesOfExternalAuthor('susy.almeida@outlook.com');

        // New submission is created with same contributor.
        cy.logout();
        cy.login('ckwantes', null, 'publicknowledge');

        newSubmission(secondSubmissionData);

        if (Cypress.env('contextTitles').en_US == 'Journal of Public Knowledge') {
            cy.logout();
            cy.findSubmissionAsEditor('dbarnes', null, 'Kwantes');
            cy.sendToReview();
            cy.assignReviewer('Julie Janssen');
            cy.recordEditorialDecision('Accept Submission');
        }

        // No duplicate collection email is sent for the same contributor.
        readCollectionEmail(1);

        // Contributor without registration deletes his own data.
        cy.then(() => cy.visit(questionnaireUrl, {log: false}));

        cy.contains('a', 'Delete my data').click();

        cy.contains('DEIA data deletion');
        cy.contains('Are you sure you want to delete your data? This action cannot be undone.');
        cy.contains('Delete my data').click();

        cy.contains('Your data has been deleted');

        // Editor reaccepts or posts new submission.
        cy.logout();
        cy.findSubmissionAsEditor('dbarnes', null, 'Kwantes');

        if (Cypress.env('contextTitles').en_US == 'Journal of Public Knowledge') {
            cy.get('.ui-tabs-anchor:contains("Review")').click();
            cy.get('.pkp_workflow_change_decision').click();
            cy.recordEditorialDecision('Accept Submission');
        }

        if (Cypress.env('contextTitles').en_US == 'Public Knowledge Preprint Server') {
            cy.get('#publication-button').click();
            cy.get('#publication button:contains("Post")').click();
            cy.get('.pkp_modal_panel button:contains("Post")').click();
            cy.waitJQuery();
            cy.contains('.pkpPublication__statusPublished', 'Posted');
        }

        // After deletion, the new invitation allows the contributor to answer again.
        readCollectionEmail(2).then(email => {
            questionnaireUrl = getQuestionnaireUrl(email);
            cy.visit(questionnaireUrl, {log: false});
        });

        answerDefaultQuestions();
        cy.contains('Thanks for answering our DEIA Survey');

        // Register the contributor and confirm ownership before associating responses.
        cy.logout();
        cy.register({
            'username': 'susyalmeida',
            'givenName': 'Susanna',
            'familyName': 'Almeida',
            'email': 'susy.almeida@outlook.com',
            'affiliation': 'Universidade Federal de Santa Catarina',
            'country': 'Brazil'
        });

        cy.contains('a', 'Edit My Profile').click();
        cy.contains('a', 'DEIA Survey').click();

        cy.get('input[name="deiaDataConsent"][value=1]').should('not.be.checked');
        cy.contains('label', 'Woman').find('input').should('not.be.checked');

        // Request proof of email ownership and follow the actual message link.
        cy.get('#deiaEmailValidationForm').contains('button', 'Request association').click();
        cy.contains('We sent a link to your email.').should('be.visible');
        const mailpitUrl = Cypress.env('mailpitUrl') || 'http://localhost:8025';
        cy.request({
            url: mailpitUrl + '/api/v1/search',
            qs: {query: 'to:susy.almeida@outlook.com subject:"Confirm the association"'},
            log: false
        }).then(({body}) => {
            expect(body.messages).to.have.length(1);
            return cy.request({url: mailpitUrl + '/api/v1/message/' + body.messages[0].ID, log: false});
        }).then(({body}) => {
            const email = new DOMParser().parseFromString(body.HTML, 'text/html');
            const confirmationLink = email.querySelector('a[href*="deiaAssociation"]');
            expect(confirmationLink, 'email association link').not.to.equal(null);
            cy.visit(confirmationLink.getAttribute('href'), {log: false});
        });

        // Opening the link displays a confirmation; association happens on submit.
        cy.contains('button', 'Confirm and associate responses').click();
        cy.contains('Previous DEIA Survey responses have been associated with your account.').should('be.visible');
        cy.visit('/index.php/publicknowledge/user/profile');
        assertResponsesOfRegisteredUser();
    });
});
