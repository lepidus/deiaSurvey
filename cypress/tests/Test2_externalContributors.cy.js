import '../support/commands.js';

function beginSubmission(submissionData) {
    cy.get('input[name="locale"][value="en"]').click();
    cy.setTinyMceContent('startSubmission-title-control', submissionData.title);
    
    if (Cypress.env('contextTitles').en !== 'Public Knowledge Preprint Server') {
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

describe('Demographic Data - External contributors data collecting', function() {
    let submissionData;
    
    before(function() {
        submissionData = {
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
    });

    it('Creation of new submission', function() {
        cy.login('ckwantes', null, 'publicknowledge');
        
        cy.get('div#myQueue a:contains("New Submission")').click();
        beginSubmission(submissionData);
        detailsStep(submissionData);
        cy.uploadSubmissionFiles([{
			'file': 'dummy.pdf',
			'fileName': 'dummy.pdf',
			'mimeType': 'application/pdf',
			'genre': 'Article Text'
		}]);
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
    });
    it('Editor accepts submission', function () {
        cy.login('dbarnes', null, 'publicknowledge');
        cy.findSubmission('myQueue', submissionData.title);

        cy.get('#workflow-button').click();
            
        cy.clickDecision('Send for Review');
        cy.contains('button', 'Skip this email').click();
        cy.contains('button', 'Record Decision').click();
        cy.get('a.pkpButton').contains('View Submission').click();
        cy.assignReviewer('Julie Janssen');
        
        cy.clickDecision('Accept Submission');
        cy.recordDecisionAcceptSubmission(['Catherine Kwantes'], [], []);
    });
    it('Access email collect data from contributors without registration', function () {
        cy.visit('localhost:8025');
        
        cy.get('b:contains("Request for demographic data collection")').should('have.length', 1);
        cy.contains('b', 'Request for demographic data collection')
            .parent().parent().parent()
            .within((node) => {
                cy.contains('susy.almeida@outlook.com');
            });
        cy.get('b:contains("Request for demographic data collection")').click();

        cy.contains('In order to improve our publication, we collect demographic data from the authors of our submissions through an online questionnaire');
        cy.contains('If you do not wish to register, you can fill in the questionnaire by accessing the following address:');
        cy.contains('a', 'Demographic questionnaire').click();

        cy.contains('Gender');
        cy.contains('With which gender do you most identify?');
        cy.contains('Ethnicity');
        cy.contains('How would you identify yourself in terms of ethnicity?');
    });
});