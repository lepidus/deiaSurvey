function assertDefaultQuestionsDisplay() {
    cy.contains('a', 'Demographic Data').click();
    cy.contains('label', 'Gender');
    cy.contains('.description', 'With which gender do you most identify?');

    cy.contains('label', 'Ethnicity');
    cy.contains('.description', 'How would you identify yourself in terms of ethnicity?');

    cy.contains('label', 'Academic background');
    cy.contains('.description', 'Please tell us which academic institutions you have been involved with');

    cy.contains('label', 'Languages');
    cy.contains('.description', 'Which of these languages do you speak?');
    cy.contains('English');
    cy.contains('French');
    cy.contains('Hindi');
    cy.contains('Mandarin');
    cy.contains('Portuguese');
    cy.contains('Spanish');

    cy.contains('label', 'Nacionality');
    cy.contains('.description', 'Which continent are you from?');
    cy.contains('Africa');
    cy.contains('America');
    cy.contains('Asia');
    cy.contains('Europe');
    cy.contains('Oceania');

    cy.contains('label', 'Salary');
    cy.contains('.description', 'What range is your current salary in?');
    cy.contains('option', 'Less than a minimum wage');
    cy.contains('option', 'One to three minimum wages');
    cy.contains('option', 'Three to five minimum wages');
    cy.contains('option', 'More than five minimum wages');
}

function answerDefaultQuestions() {
    cy.get('input[id^="demographicResponses-en"]').eq(0).clear().type('Female');
    cy.get('input[id^="demographicResponses-en"]').eq(1).clear().type('Latin');
    cy.get('textarea[id^="demographicResponses-en"]').clear().type('University of São Paulo');
    cy.get('textarea[id^="demographicResponses-en"]').type('{enter}');
    cy.get('textarea[id^="demographicResponses-en"]').type('University of Minas Gerais');
    cy.contains('label', 'Academic background').click();
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

    cy.get('#demographicDataForm .submitFormButton').click();
    cy.wait(1000);
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

function assertResponsesToQuestionsInFrench() {
    cy.get('li label:contains("Anglais")').within(() => {
        cy.get('input').should('be.checked');
    });
    cy.contains('label', 'Espagnol').within(() => {
        cy.get('input').should('be.checked');
    });
    cy.contains('label', 'Amérique').within(() => {
        cy.get('input').should('be.checked');
    });
    cy.get('select[id^="demographicResponses"] option:selected').should('have.text', 'Trois à cinq salaires minimums');
}

describe('Demographic Data - Questions displaying', function () {
    it('Display of questions and request message at users profile page', function () {
        cy.login('dbarnes', null, 'publicknowledge');
        cy.get('.app__headerActions button').eq(1).click();
        cy.contains('a', 'Edit Profile').click();
        cy.contains('We request that you fill in the demographic data survey on the "Demographic Data" tab of your profile page');
        assertDefaultQuestionsDisplay();
        cy.logout();

        cy.login('dsokoloff', null, 'publicknowledge');
        cy.get('.app__headerActions button').eq(1).click();
        cy.contains('a', 'Edit Profile').click();
        cy.contains('We request that you fill in the demographic data survey on the "Demographic Data" tab of your profile page');
        assertDefaultQuestionsDisplay();
    });
    it('User can choose not to answer questions', function () {
        cy.login('dsokoloff', null, 'publicknowledge');
        cy.get('.app__headerActions button').eq(1).click();
        cy.contains('a', 'Edit Profile').click();
        cy.contains('a', 'Demographic Data').click();

        cy.contains('I consent to the processing of my Demographic Data');
        cy.contains('I do not consent to the processing of my Demographic Data');

        cy.get('input[name="demographicDataConsent"][value=0]').should('not.be.checked');
        cy.get('input[name="demographicDataConsent"][value=1]').should('not.be.checked');
        
        cy.get('input[name="demographicDataConsent"][value=0]').click();
        cy.get('#demographicDataForm .submitFormButton').click();
        cy.wait(1000);
        cy.reload();

        cy.contains('a', 'Demographic Data').click();
        cy.get('input[name="demographicDataConsent"][value=0]').should('be.checked');
    });
    it('Request message is not shown anymore', function () {
        cy.login('dsokoloff', null, 'publicknowledge');
        cy.get('.app__headerActions button').eq(1).click();
        cy.contains('a', 'Edit Profile').click();
        cy.get('span:contains("We request that you fill in the demographic data survey")').should('not.exist');
    });
    it('User chooses to answer questions', function () {
        cy.login('dsokoloff', null, 'publicknowledge');
        cy.get('.app__headerActions button').eq(1).click();
        cy.contains('a', 'Edit Profile').click();
        cy.contains('a', 'Demographic Data').click();

        cy.get('input[name="demographicDataConsent"][value=1]').click();
        answerDefaultQuestions();

        cy.reload();
        assertResponsesToDefaultQuestions();

        cy.get('.app__headerActions button').eq(1).click();
        cy.contains('.pkpDropdown__action', 'Français').click();
        cy.get('a[name="demographicData"]').click();
        assertResponsesToQuestionsInFrench();
    });
});