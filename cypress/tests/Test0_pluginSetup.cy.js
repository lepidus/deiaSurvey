describe('Plugin setup', function () {
    it('Enables Demographic Data plugin', function () {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.contains('a', 'Website').click();

		cy.waitJQuery();
		cy.get('#plugins-button').click();

		cy.get('input[id^=select-cell-demographicdataplugin]').check();
		cy.get('input[id^=select-cell-demographicdataplugin]').should('be.checked');
    });
});