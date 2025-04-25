describe('Demographic Data - Plugin setup', function () {
    it('Editor does not give consent, in order to use the application', function () {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.contains('h1', 'Profile');
		cy.contains('a', 'Demographic Data').click();
		cy.get('input[name="demographicDataConsent"][value=0]').click();
        cy.get('#demographicDataForm .submitFormButton').click();
        cy.wait(1000);
	});
	it('Enables Demographic Data plugin', function () {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.contains('a', 'Website').click();

		cy.waitJQuery();
		cy.get('#plugins-button').click();

		cy.get('input[id^=select-cell-demographicdataplugin]').check();
		cy.get('input[id^=select-cell-demographicdataplugin]').should('be.checked');
    });
	it('Configures plugin', function() {
		const pluginRowId = 'component-grid-settings-plugins-settingsplugingrid-category-generic-row-demographicdataplugin';

		cy.login('dbarnes', null, 'publicknowledge');
		cy.contains('a', 'Website').click();

		cy.waitJQuery();
		cy.get('#plugins-button').click();
		cy.get('tr#' + pluginRowId + ' a.show_extras').click();
		cy.get('a[id^=' + pluginRowId + '-settings-button]').click();

		cy.get('#orcidAPIPath').select('Public Sandbox');
		cy.get('input[name="orcidClientId"]').clear().type(Cypress.env('orcidClientId'), {delay: 0});
		cy.get('input[name="orcidClientSecret"]').clear().type(Cypress.env('orcidClientSecret'), {delay: 0});

		cy.get('#demographicDataSettingsForm button:contains("OK")').click();
	});
});