import '../support/commands.js';

function assertDefaultQuestionsDisplay(authorEmail) {
    cy.contains('The demographic data from this questionnaire will be associated with the e-mail address: ' + authorEmail);
    
    cy.contains('.questionTitle', 'Gender');
    cy.contains('With which gender do you most identify?');

    cy.contains('.questionTitle', 'Ethnicity');
    cy.contains('How would you identify yourself in terms of ethnicity?');

    cy.contains('.questionTitle', 'Academic background');
    cy.contains('Please tell us which academic institutions you have been involved with');

    cy.contains('.questionTitle', 'Languages');
    cy.contains('Which of these languages do you speak?');
    cy.contains('label', 'English');
    cy.contains('French');
    cy.contains('Hindi');
    cy.contains('Mandarin');
    cy.contains('Portuguese');
    cy.contains('Spanish');

    cy.contains('.questionTitle', 'Nacionality');
    cy.contains('Which continent are you from?');
    cy.contains('Africa');
    cy.contains('America');
    cy.contains('Asia');
    cy.contains('Europe');
    cy.contains('Oceania');

    cy.contains('.questionTitle', 'Salary');
    cy.contains('What range is your current salary in?');
    cy.contains('option', 'Less than a minimum wage');
    cy.contains('option', 'One to three minimum wages');
    cy.contains('option', 'Three to five minimum wages');
    cy.contains('option', 'More than five minimum wages');
}

function answerDefaultQuestions() {
    cy.get('input[id^="demographicResponses"]').eq(0).type('Female');
    cy.get('input[id^="demographicResponses"]').eq(1).type('Latin');
    cy.get('textarea[id^="demographicResponses"]').type('University of São Paulo');
    cy.get('textarea[id^="demographicResponses"]').type('{enter}');
    cy.get('textarea[id^="demographicResponses"]').type('University of Minas Gerais');
    cy.contains('label', 'English').within(() => {
        cy.get('input').check();
    });
    cy.contains('label', 'Spanish').within(() => {
        cy.get('input').check();
    });
    cy.contains('label', 'America').within(() => {
        cy.get('input').check();
    });
    cy.get('select[id^="demographicResponses"]').select('Three to five minimum wages');

    cy.contains('button', 'Save').click();
}

function assertResponsesToDefaultQuestions() {
    cy.contains('a', 'Demographic Data').click();
    cy.get('input[name="demographicDataConsent"][value=1]').should('be.checked');
    
    cy.get('input[id^="demographicResponses-en"]').eq(0).should('have.value', 'Female');
    cy.get('input[id^="demographicResponses-en"]').eq(1).should('have.value', 'Latin');
    cy.get('textarea[id^="demographicResponses-en"]').invoke('val').should('include', 'University of São Paulo');
    cy.get('textarea[id^="demographicResponses-en"]').invoke('val').should('include', 'University of Minas Gerais');
    cy.contains('label', 'English').within(() => {
        cy.get('input').should('be.checked');
    });
    cy.contains('label', 'Spanish').within(() => {
        cy.get('input').should('be.checked');
    });
    cy.contains('label', 'America').within(() => {
        cy.get('input').should('be.checked');
    });
    cy.get('select[id^="demographicResponses"] option:selected').should('have.text', 'Three to five minimum wages');
}

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

    it('Creation of new submission', function() {
        cy.login('ckwantes', null, 'publicknowledge');
        
        cy.get('div#myQueue a:contains("New Submission")').click();
        beginSubmission(firstSubmissionData);
        detailsStep(firstSubmissionData);
        cy.uploadSubmissionFiles([{
			'file': 'dummy.pdf',
			'fileName': 'dummy.pdf',
			'mimeType': 'application/pdf',
			'genre': 'Article Text'
		}]);
        cy.contains('button', 'Continue').click();
        contributorsStep(firstSubmissionData);
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
        cy.findSubmission('myQueue', firstSubmissionData.title);

        cy.get('#workflow-button').click();
            
        cy.clickDecision('Send for Review');
        cy.contains('button', 'Skip this email').click();
        cy.contains('button', 'Record Decision').click();
        cy.get('a.pkpButton').contains('View Submission').click();
        cy.assignReviewer('Julie Janssen');
        
        cy.clickDecision('Accept Submission');
        cy.recordDecisionAcceptSubmission(['Catherine Kwantes'], [], []);
    });
    it('Access email to collect data from contributors without registration', function () {
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
        cy.get('.text-view').within(() => {
            cy.get('a').eq(1).should('have.attr', 'href').then((href) => {
                cy.visit(href);
            });
        });

        assertDefaultQuestionsDisplay('susy.almeida@outlook.com');

        cy.url().then(url => {
            cy.visit(url + 'breakToken');
        });
        cy.contains('Demographic Questionnaire');
        cy.contains('Only the author can access this page');
    });
    it('Contributor without registration answers demographic questionnaire', function () {
        cy.visit('localhost:8025');
        cy.get('b:contains("Request for demographic data collection")').click();

        cy.get('#nav-tab button:contains("Text")').click();
        cy.get('.text-view').within(() => {
            cy.get('a').eq(1).should('have.attr', 'href').then((href) => {
                cy.visit(href);
            });
        });

        answerDefaultQuestions();

        cy.contains('Thanks for answering our demographic questionnaire');
    });
    it('Contributor access questionnaire again', function () {
        cy.visit('localhost:8025');
        cy.get('b:contains("Request for demographic data collection")').click();

        cy.get('#nav-tab button:contains("Text")').click();
        cy.get('.text-view').within(() => {
            cy.get('a').eq(1).should('have.attr', 'href').then((href) => {
                cy.visit(href);
            });
        });

        cy.contains('You already answered the demographic questionnaire');
    });
    it('New submission is created and accepted with same contributor', function () {
        cy.login('ckwantes', null, 'publicknowledge');
        
        cy.get('div#myQueue a:contains("New Submission")').click();
        beginSubmission(secondSubmissionData);
        detailsStep(secondSubmissionData);
        cy.uploadSubmissionFiles([{
			'file': 'dummy.pdf',
			'fileName': 'dummy.pdf',
			'mimeType': 'application/pdf',
			'genre': 'Article Text'
		}]);
        cy.contains('button', 'Continue').click();
        contributorsStep(secondSubmissionData);
        cy.contains('button', 'Continue').click();
        cy.wait(1000);

        cy.contains('button', 'Submit').click();
        cy.get('.modal__panel:visible').within(() => {
            cy.contains('button', 'Submit').click();
        });
        cy.waitJQuery();
        cy.contains('h1', 'Submission complete');
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
    });
    it('E-mail for demographic data collection is not sent again', function () {
        cy.visit('localhost:8025');
        cy.get('b:contains("Request for demographic data collection")').should('have.length', 1);
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

        assertResponsesToDefaultQuestions();
    });
});