function assertDefaultQuestionsDisplay() {
    cy.contains('a', 'Demographic Data').click();
    cy.contains('Gender');
    cy.contains('.description', 'With which gender do you most identify?');
    cy.contains('Ethnicity');
    cy.contains('.description', 'How would you identify yourself in terms of ethnicity?');
}

describe('Questions displaying', function () {
    it('Display of questions at users profile page', function () {
        cy.login('dbarnes', null, 'publicknowledge');
        cy.get('.app__headerActions button').eq(1).click();
        cy.contains('a', 'Edit Profile').click();
        assertDefaultQuestionsDisplay();
        cy.logout();

        cy.login('dsokoloff', null, 'publicknowledge');
        cy.get('.app__headerActions button').eq(1).click();
        cy.contains('a', 'Edit Profile').click();
        assertDefaultQuestionsDisplay();
    });
    it('User can choose not to answer questions', function () {
        cy.login('dsokoloff', null, 'publicknowledge');
        cy.get('.app__headerActions button').eq(1).click();
        cy.contains('a', 'Edit Profile').click();
        cy.contains('a', 'Demographic Data').click();

        cy.contains('I agree to the processing of my Demographic Data');
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
    //User answer questions
});