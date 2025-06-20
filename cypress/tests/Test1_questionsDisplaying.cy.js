function assertDefaultQuestionsDisplay() {
    cy.contains('a', 'DEIA Survey').click();
    
    cy.contains('label', 'Gender');
    cy.contains('.description', 'With which gender do you most identify? Please select one option:')
        .should('have.length', 1);
    cy.contains('label', 'Woman');
    cy.contains('label', 'Man');
    cy.contains('label', 'Non-binary or gender diverse');
    cy.contains('label', 'Prefer not to inform');

    cy.contains('label', 'Race');
    cy.contains('.description', 'How would you identify yourself in terms of race? Please select ALL the groups that apply to you:')
        .should('have.length', 1);
    cy.contains('label', 'Asian or Pacific Islander');
    cy.contains('label', 'Black');
    cy.contains('label', 'Hispanic or Latino/a/x');
    cy.contains('label', 'Indigenous (e.g. North American Indian Navajo, South American Indian Quechua, Aboriginal or Torres Strait Islander)');
    cy.contains('label', 'Middle Eastern or North African');
    cy.contains('label', 'White');
    cy.contains('label', 'Prefer not to inform');
    cy.contains('label', 'Self describe');

    cy.contains('label', 'Ethnicity');
    cy.contains('.description', "What are your ethnic origins or ancestry? Please select ALL the geographic areas from which your family's ancestors first originated:")
        .should('have.length', 1);
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
}

function answerDefaultQuestions() {
    cy.contains('label', 'Woman').within(() => {
        cy.get('input').check();
    });
    cy.contains('label', 'Self describe').eq(0).parent().parent().within(() => {
        cy.get('input[type="checkbox"]').check();
        cy.get('input[type="text"]').clear().type('Slavic');
    });
    cy.contains('label', 'Eastern Europe').within(() => {
        cy.get('input').check();
    });

    cy.get('#deiaSurveyForm .submitFormButton').click();
    cy.wait(1000);
}

function assertResponsesToDefaultQuestions() {
    cy.contains('a', 'DEIA Survey').click();
    cy.get('input[name="demographicDataConsent"][value=1]').should('be.checked');
    
    cy.contains('label', 'Woman').within(() => {
        cy.get('input').should('be.checked');
    });
    cy.contains('label', 'Self describe').eq(0).parent().parent().within(() => {
        cy.get('input[type="checkbox"]').should('be.checked');
        cy.get('input[type="text"]').should('have.value', 'Slavic');
    });
    cy.contains('label', 'Eastern Europe').within(() => {
        cy.get('input').should('be.checked');
    });
}

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
            if (Cypress.env('contextTitles').en == 'Public Knowledge Preprint Server'
                && userRole == 'reviewer'
            ) {
                continue;
            }

            cy.login(username, null, 'publicknowledge');
            cy.log('User ' + username);

            cy.contains('h1', 'Profile');
            cy.contains('We request that you fill in the DEIA survey on the "DEIA Survey" tab of your profile page');
            assertDefaultQuestionsDisplay();

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
        assertDefaultQuestionsDisplay();
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
        cy.login('dsokoloff', null, 'publicknowledge');
        cy.get('.app__headerActions button').eq(1).click();
        cy.contains('a', 'Edit Profile').click();
        cy.contains('a', 'DEIA Survey').click();

        cy.get('input[name="demographicDataConsent"][value=1]').click();
        answerDefaultQuestions();

        cy.reload();
        assertResponsesToDefaultQuestions();
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