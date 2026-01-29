describe('DEIA Survey - Report feature', function () {
    it('Report should be visible only for admin user', function () {
        cy.login('admin', 'admin', 'publicknowledge');
        cy.contains('.app__navItem', 'Reports').click();
        cy.contains('a', 'DEIA Survey Report');
        cy.logout();

        cy.login('dbarnes', null, 'publicknowledge');
        cy.contains('.app__navItem', 'Reports').click();
        cy.contains('a', 'DEIA Survey Report').should('not.exist');
        cy.logout();
    });
});
