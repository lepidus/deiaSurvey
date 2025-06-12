describe('Demographic Data - Plugin setup', function () {
	const pluginRowId = 'component-grid-settings-plugins-settingsplugingrid-category-generic-row-demographicdataplugin';
	const orcidPluginRowId = 'component-grid-settings-plugins-settingsplugingrid-category-generic-row-orcidprofileplugin';
	
	it('Enables Demographic Data plugin. Editor does not give consent', function () {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.contains('a', 'Website').click();

		cy.waitJQuery();
		cy.get('#plugins-button').click();

		cy.get('input[id^=select-cell-demographicdataplugin]').check();
		cy.get('input[id^=select-cell-demographicdataplugin]').should('be.checked');
		cy.reload();

		cy.contains('h1', 'Profile');
		cy.contains('a', 'Demographic Data').click();
		cy.get('input[name="demographicDataConsent"][value=0]').click();
        cy.get('#demographicDataForm .submitFormButton').click();
        cy.wait(1000);
	});
	it("Plugin uses ORCID plugin's settings by default", function () {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.contains('a', 'Website').click();

		cy.waitJQuery();
		cy.get('#plugins-button').click();

		cy.get('input[id^=select-cell-orcidprofileplugin]').check();
		cy.get('input[id^=select-cell-orcidprofileplugin]').should('be.checked');
		cy.reload();

		cy.get('#plugins-button').click();
		cy.get('tr#' + orcidPluginRowId + ' a.show_extras').click();
		cy.get('a[id^=' + orcidPluginRowId + '-settings-button]').click();

		cy.get('#orcidProfileAPIPath').select('Public Sandbox');
		cy.get('input[name="orcidClientId"]').clear().type(Cypress.env('orcidClientId'), {delay: 0});
		cy.get('input[name="orcidClientSecret"]').clear().type(Cypress.env('orcidClientSecret'), {delay: 0});
		cy.get('#orcidProfileSettingsForm button:contains("OK")').click();
		cy.wait(1000);

		cy.get('input[id^=select-cell-orcidprofileplugin]').check();
		cy.get('input[id^=select-cell-orcidprofileplugin]').should('be.checked');

		cy.get('tr#' + pluginRowId + ' a.show_extras').click();
		cy.get('a[id^=' + pluginRowId + '-settings-button]').click();
		cy.contains("The plugin is using the credentials entered in the ORCID Profile plugin settings. If you wish to use other credentials for this plugin, use the fields below");
	});
	it("Adds ORCID credentials to own plugin settings", function() {
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
		cy.wait(1000);

		cy.get('a[id^=' + pluginRowId + '-settings-button]').click();
		cy.contains("This plugin is using the credentials below");
	});
});