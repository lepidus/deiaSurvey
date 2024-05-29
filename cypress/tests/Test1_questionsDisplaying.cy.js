function assertDefaultQuestionsDisplay() {
    cy.contains('a', 'Demographic Data').click();
    cy.contains('Gender');
    cy.contains('.description', 'With which gender do you most identify?');
    cy.contains('Ethnicity');
    cy.contains('.description', 'How would you identify yourself in terms of ethnicity?');
}


describe('Questions displaying', function () {
    it('Checks display of questions at users profile page', function () {
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
});