import '../support/commands.js';

function assertDefaultQuestionsDisplay(authorEmail) {
    cy.contains('The data from this questionnaire will be associated with the e-mail address: ' + authorEmail);

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

    cy.contains('Data is collected in accordance with this journal\'s privacy statement');
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

function beginSubmission(submissionData) {
    cy.get('input[name="locale"][value="en"]').click();
    cy.setTinyMceContent('startSubmission-title-control', submissionData.title);
    
    if (Cypress.env('contextTitles').en == 'Journal of Public Knowledge') {
        cy.get('input[name="sectionId"][value="1"]').click();
    }
    
    cy.get('input[name="submissionRequirements"]').check();
    cy.get('input[name="privacyConsent"]').check();
    cy.contains('button', 'Begin Submission').click();
}

function detailsStep(submissionData) {
    cy.setTinyMceContent('titleAbstract-abstract-control-en', submissionData.abstract);
    submissionData.keywords.forEach(keyword => {
        cy.get('#titleAbstract-keywords-control-en').type(keyword, {delay: 0});
        cy.wait(500);
        cy.get('#titleAbstract-keywords-control-en').type('{enter}', {delay: 0});
    });
    cy.contains('button', 'Continue').click();
}

function contributorsStep(submissionData) {
    submissionData.contributors.forEach(authorData => {
        cy.contains('button', 'Add Contributor').click();
        cy.get('input[name="givenName-en"]').type(authorData.given, {delay: 0});
        cy.get('input[name="familyName-en"]').type(authorData.family, {delay: 0});
        cy.get('input[name="email"]').type(authorData.email, {delay: 0});
        cy.get('select[name="country"]').select(authorData.country);
        
        cy.get('.modal__panel:contains("Add Contributor")').find('button').contains('Save').click();
        cy.waitJQuery();
    });

    cy.contains('button', 'Continue').click();
}

function createNewSubmission(submissionData) {
    cy.get('div#myQueue a:contains("New Submission")').click();
    beginSubmission(submissionData);
    detailsStep(submissionData);
    
    if (Cypress.env('contextTitles').en == 'Journal of Public Knowledge') {
        cy.uploadSubmissionFiles([{
            'file': 'dummy.pdf',
            'fileName': 'dummy.pdf',
            'mimeType': 'application/pdf',
            'genre': 'Article Text'
        }]);
    } else if (Cypress.env('contextTitles').en == 'Public Knowledge Preprint Server') {
        cy.addSubmissionGalleys([{
            'file': 'dummy.pdf',
            'fileName': 'dummy.pdf',
            'mimeType': 'application/pdf',
            'genre': 'Preprint Text'
        }]);
    }

    cy.contains('button', 'Continue').click();
    contributorsStep(submissionData);
    cy.contains('button', 'Continue').click();
    cy.wait(1000);

    cy.contains('button', 'Submit').click();
    cy.get('.modal__panel:visible').within(() => {
        cy.contains('button', 'Submit').click();
    });
    cy.waitJQuery();
    cy.contains('h1', 'Submission complete');
}

describe('DEIA Survey - External contributors data collecting', function() {
    let firstSubmissionData;
    let secondSubmissionData;

    before(function() {
        firstSubmissionData = {
            title: "Test scenarios to automobile vehicles",
			abstract: 'Description of test scenarios for cars, motorcycles and other vehicles',
			keywords: ['plugin', 'testing'],
            contributors: [
                {
                    'given': 'Susanna',
                    'family': 'Almeida',
                    'email': 'susy.almeida@outlook.com',
                    'country': 'Brazil'
                }
            ]
		};

        secondSubmissionData = {
            title: "Advancements in tests of automobile vehicles",
			abstract: 'New improvements on tests of cars, motorcycles and other vehicles',
			keywords: ['plugin', 'testing'],
            contributors: [
                {
                    'given': 'Susanna',
                    'family': 'Almeida',
                    'email': 'susy.almeida@outlook.com',
                    'country': 'Brazil'
                }
            ]
		};
    });

    it('Creates new submission for collection of DEIA data from external authors', function() {
        cy.login('ckwantes', null, 'publicknowledge');

        cy.contains('a', 'DEIA Survey').click();
        cy.get('input[name="demographicDataConsent"][value=0]').click();
        cy.get('#deiaSurveyForm .submitFormButton').click();
        cy.wait(1000);
        cy.contains('Back to Submissions').click();

        createNewSubmission(firstSubmissionData);

        if (Cypress.env('contextTitles').en == 'Journal of Public Knowledge') {
            cy.logout();
            cy.login('dbarnes', null, 'publicknowledge');
            cy.findSubmission('myQueue', firstSubmissionData.title);

            cy.get('#workflow-button').click();

            cy.clickDecision('Send for Review');
            cy.contains('button', 'Skip this email').click();
            cy.contains('button', 'Record Decision').click();
            cy.get('a.pkpButton').contains('View Submission').click();
            cy.assignReviewer('Julie Janssen');

            cy.clickDecision('Accept Submission');
            cy.recordDecisionAcceptSubmission(['Catherine Kwantes'], [], []);
        }
    });
    it('Access email to collect data from contributors without registration', function () {
        cy.visit('localhost:8025');
        
        cy.get('b:contains("Request for DEIA data collection")').should('have.length', 1);
        cy.contains('b', 'Request for DEIA data collection')
            .parent().parent().parent()
            .within((node) => {
                cy.contains('susy.almeida@outlook.com');
            });
        cy.get('b:contains("Request for DEIA data collection")').click();

        cy.get('#nav-tab button:contains("Text")').click();

        cy.contains('In order to improve our publication, we collect DEIA (Diversity, Equity, Inclusion, and Accessibility) data from the authors of our submissions through an online questionnaire');
        cy.contains('If you do not wish to register, we recommend that you access the following address:');
        cy.contains("If you don't have an ORCID record, you can fill in the questionnaire at the following address:");
        cy.get('.text-view').within(() => {
            cy.get('a').eq(1).should('have.attr', 'href').then((href) => {
                cy.visit(href);
            });
        });

        assertDefaultQuestionsDisplay('susy.almeida@outlook.com');

        cy.url().then(url => {
            cy.visit(url + 'breakToken');
        });
        cy.contains('DEIA Survey');
        cy.contains('Only the author can access this page');
    });
    it('Contributor without registration answers DEIA survey', function () {
        cy.visit('localhost:8025');
        cy.get('b:contains("Request for DEIA data collection")').click();

        cy.get('#nav-tab button:contains("Text")').click();
        cy.get('.text-view').within(() => {
            cy.get('a').eq(1).should('have.attr', 'href').then((href) => {
                cy.visit(href);
            });
        });

        answerDefaultQuestions();

        cy.contains('Thanks for answering our DEIA Survey');
        cy.contains('a', 'Check my answers').click();

        assertResponsesOfExternalAuthor('susy.almeida@outlook.com');
    });
    it('New submission is created and accepted with same contributor', function () {
        cy.login('ckwantes', null, 'publicknowledge');
        
        createNewSubmission(secondSubmissionData);
        
        if (Cypress.env('contextTitles').en == 'Journal of Public Knowledge') {
            cy.logout();
            cy.login('dbarnes', null, 'publicknowledge');
            cy.findSubmission('myQueue', secondSubmissionData.title);

            cy.get('#workflow-button').click();

            cy.clickDecision('Send for Review');
            cy.contains('button', 'Skip this email').click();
            cy.contains('button', 'Record Decision').click();
            cy.get('a.pkpButton').contains('View Submission').click();
            cy.assignReviewer('Julie Janssen');
            
            cy.clickDecision('Accept Submission');
            cy.recordDecisionAcceptSubmission(['Catherine Kwantes'], [], []);
        }
    });
    it('E-mail for DEIA data collection is not sent again', function () {
        cy.visit('localhost:8025');
        cy.get('b:contains("Request for DEIA data collection")').should('have.length', 1);
    });
    it('Contributor without registration deletes his own DEIA data', function () {
        cy.visit('localhost:8025');
        cy.get('b:contains("Request for DEIA data collection")').click();

        cy.get('#nav-tab button:contains("Text")').click();
        cy.get('.text-view').within(() => {
            cy.get('a').eq(1).should('have.attr', 'href').then((href) => {
                cy.visit(href);
            });
        });

        cy.contains('a', 'Delete my data').click();

        cy.contains('DEIA data deletion');
        cy.contains('Are you sure you want to delete your data? This action cannot be undone.');
        cy.contains('Delete my data').click();

        cy.contains('Your data has been deleted');
    });
    it('Editor reaccepts/posts new submission', function () {
        cy.login('dbarnes', null, 'publicknowledge');
        cy.findSubmission('active', secondSubmissionData.title);

        if (Cypress.env('contextTitles').en == 'Journal of Public Knowledge') {
            cy.get('#workflow-button').click();
            cy.clickDecision('Cancel Copyediting');
            cy.contains('button', 'Skip this email').click();
            cy.contains('button', 'Record Decision').click();
            cy.get('a.pkpButton').contains('View Submission').click();
            
            cy.clickDecision('Accept Submission');
            cy.recordDecisionAcceptSubmission(['Catherine Kwantes'], [], []);
        }

        if (Cypress.env('contextTitles').en == 'Public Knowledge Preprint Server') {
            cy.get('#publication-button').click();
            cy.get('#publication button:contains("Post")').click();
            cy.get('.pkp_modal_panel button:contains("Post")').click();
            cy.wait(1000);
            cy.contains('.pkpPublication__statusPublished', 'Posted');
        }
    });
    it('Contributor answers DEIA survey on new submission', function () {
        cy.visit('localhost:8025');
        cy.get('b:contains("Request for DEIA data collection")').eq(0).click();

        cy.get('#nav-tab button:contains("Text")').click();
        cy.get('.text-view').within(() => {
            cy.get('a').eq(1).should('have.attr', 'href').then((href) => {
                cy.visit(href);
            });
        });

        answerDefaultQuestions();
        cy.contains('Thanks for answering our DEIA Survey');
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
        cy.contains('a', 'DEIA Survey').click();

        assertResponsesOfRegisteredUser();
    });
});