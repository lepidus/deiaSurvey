import '../support/commands.js';

function assertDisabledFields() {
    cy.contains('label', 'Woman').within(() => {
        cy.get('input').should('be.disabled');
    });
    cy.contains('label', 'Self describe').within(() => {
        cy.get('input').should('be.disabled');
    });
    cy.contains('label', 'Eastern Europe').within(() => {
        cy.get('input').should('be.disabled');
    });
}

describe('DEIA Survey - Questions displaying', function () {
    it('Display of questions for users just after login. Fullfilling is mandatory.', function () {
        let usersWithMandatoryFilling = {
            'rvaca': 'manager',
            'sberardo': 'editor/moderator',
            'agallego': 'reviewer',
            'dsokoloff': 'author'
        };

        for (let username in usersWithMandatoryFilling) {
            let userRole = usersWithMandatoryFilling[username];
            if (Cypress.env('contextTitles').en_US === 'Public Knowledge Preprint Server'
                && userRole == 'reviewer'
            ) {
                continue;
            }

            cy.login(username, null, 'publicknowledge');
            cy.log('User ' + username);

            cy.contains('h1', 'Profile');
            cy.contains('We request that you fill in the DEIA survey on the "DEIA Survey" tab of your profile page');
            cy.assertDefaultQuestionsDisplay('profilePage');

            if (userRole == 'manager') {
                cy.contains('.app__navItem', 'Submissions').click();
                cy.contains('h1', 'Profile');
                cy.contains('.app__navItem', 'Workflow').click();
                cy.contains('h1', 'Profile');
                cy.contains('.app__navItem', 'Website').click();
            } else if (userRole == 'editor/moderator') {
                cy.contains('.app__navItem', 'Submissions').click();
                cy.contains('h1', 'Profile');
                cy.contains('.app__navItem', 'Editorial Activity').click();
            } else {
                cy.contains('a', 'Back to Submissions').click();
            }
            cy.contains('h1', 'Profile');
            cy.logout();
        }
    });
    it('Answering of questions is not mandatory for the site admin', function () {
        cy.login('admin', 'admin', 'publicknowledge');
        cy.contains('h1', 'Submissions');
        cy.get('.app__headerActions button').eq(1).click();
        cy.contains('a', 'Edit Profile').click();

        cy.contains('We request that you fill in the DEIA survey on the "DEIA Survey" tab of your profile page');
        cy.assertDefaultQuestionsDisplay('profilePage');
    });
    it('User can choose not to answer questions', function () {
        cy.login('dsokoloff', null, 'publicknowledge');
        cy.contains('a', 'DEIA Survey').click();

        cy.contains('I consent to the processing of my data');
        cy.contains('I do not consent to the processing of my data');
        cy.contains('You can change your consent option at any time.');
        cy.contains('If you withdraw a previously given consent, your data will be deleted');

        cy.get('input[name="demographicDataConsent"][value=0]').should('not.be.checked');
        cy.get('input[name="demographicDataConsent"][value=1]').should('not.be.checked');
        
        cy.get('input[name="demographicDataConsent"][value=0]').click();
        cy.get('#deiaSurveyForm .submitFormButton').click();
        cy.wait(1000);
        cy.reload();

        cy.contains('a', 'DEIA Survey').click();
        cy.get('input[name="demographicDataConsent"][value=0]').should('be.checked');
        assertDisabledFields();
    });
    it('Request message is not shown anymore. Author can now access other parts of the application.', function () {
        cy.login('dsokoloff', null, 'publicknowledge');
        cy.get('.app__headerActions button').eq(1).click();
        cy.contains('a', 'Edit Profile').click();
        cy.get('span:contains("We request that you fill in the DEIA survey")').should('not.exist');
    });
    it('User chooses to answer questions', function () {
        let userAnswers = [
            {'title': 'Gender', 'chosenOption': 'Woman'},
            {'title': 'Race', 'chosenOption': 'Self describe', 'selfDescribeValue': 'Slavic'},
            {'title': 'Ethnicity', 'chosenOption': 'Eastern Europe'},
        ];
        
        cy.login('dsokoloff', null, 'publicknowledge');
        cy.get('.app__headerActions button').eq(1).click();
        cy.contains('a', 'Edit Profile').click();
        cy.contains('a', 'DEIA Survey').click();

        cy.get('input[name="demographicDataConsent"][value=1]').click();
        cy.answerDefaultQuestionsOnProfile(userAnswers);

        cy.reload();
        cy.assertResponsesToDefaultQuestions(userAnswers);
    });
    it('User removes consent, leading to data deletion', function () {
        cy.login('dsokoloff', null, 'publicknowledge');
        cy.get('.app__headerActions button').eq(1).click();
        cy.contains('a', 'Edit Profile').click();
        cy.contains('a', 'DEIA Survey').click();

        cy.get('input[name="demographicDataConsent"][value=0]').click();
        cy.get('#deiaSurveyForm .submitFormButton').click();
        cy.wait(1000);
        cy.reload();

        cy.contains('a', 'DEIA Survey').click();
        cy.contains('label', 'Woman').within(() => {
            cy.get('input').should('not.be.checked');
        });
        cy.contains('label', 'Self describe').eq(0).parent().parent().within(() => {
            cy.get('input[type="checkbox"]').should('not.be.checked');
            cy.get('input[type="text"]').should('have.value', '');
        });
        cy.contains('label', 'Eastern Europe').within(() => {
            cy.get('input').should('not.be.checked');
        });
    });
});