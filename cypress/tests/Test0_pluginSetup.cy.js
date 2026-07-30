function assertTokenIsAbsent(response) {
	expect(response.status).to.eq(200);
	expect(JSON.stringify(response.body)).not.to.include('"deiaToken"');
}

describe('DEIA Survey - Plugin setup', function () {
	const pluginRowId = 'component-grid-settings-plugins-settingsplugingrid-category-generic-row-deiasurveyplugin';
	const orcidPluginRowId = 'component-grid-settings-plugins-settingsplugingrid-category-generic-row-orcidprofileplugin';
	
	it('Enables DEIA Survey plugin. Editor does not give consent', function () {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.contains('a', 'Website').click();

		cy.waitJQuery();
		cy.get('#plugins-button').click();
		cy.waitJQuery();

		cy.get('input[id^=select-cell-deiasurveyplugin]').check();
		cy.contains('The plugin "DEIA Survey" has been enabled', {timeout: 15000});
		cy.get('input[id^=select-cell-deiasurveyplugin]').should('be.checked');
		cy.reload();
		cy.request('/index.php/publicknowledge/api/v1/_submissions?offset=0&count=20').then(assertTokenIsAbsent);
		cy.request('/index.php/publicknowledge/api/v1/submissions?offset=0&count=20').then(assertTokenIsAbsent);

		cy.contains('h1', 'Profile');
		cy.contains('a', 'DEIA Survey').click();
		cy.get('input[name="deiaDataConsent"][value=0]').click();
        cy.get('#deiaSurveyForm .submitFormButton').click();
        cy.wait(1000);
	});
	it("Plugin uses ORCID plugin's settings by default", function () {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.contains('a', 'Website').click();

		cy.waitJQuery();
		cy.get('#plugins-button').click();
		cy.waitJQuery();

		cy.get('tr#' + orcidPluginRowId + ' a.show_extras').click();
		cy.get('a[id^=' + orcidPluginRowId + '-settings-button]').click();

		cy.get('#orcidProfileAPIPath').select('Public Sandbox');
		cy.get('input[name="orcidClientId"]').clear().type(Cypress.env('orcidClientId'), {delay: 0});
		cy.get('input[name="orcidClientSecret"]').clear().type(Cypress.env('orcidClientSecret'), {delay: 0});
		cy.get('#orcidProfileSettingsForm').then(($form) => {
			const saveUrl = $form.attr('action');
			expect(saveUrl).to.contain('orcidprofileplugin');
			expect(saveUrl).to.contain('save=1');
			cy.server();
			cy.route('POST', saveUrl).as('saveOrcidSettings');
		});
		cy.get('#orcidProfileSettingsForm button:contains("OK")').click();
		cy.get('.pkp_modal:has(#orcidProfileSettingsForm)').should('be.visible');
		cy.wait('@saveOrcidSettings').then((xhr) => {
			expect(xhr.status).to.equal(200);
			expect(xhr.response.body.status).to.equal(true);
			expect(xhr.response.body.content).to.equal('');
		});
		cy.get('.pkp_modal:has(#orcidProfileSettingsForm) .pkpModalCloseButton')
			.scrollIntoView()
			.should('be.visible')
			.click();
		cy.get('#orcidProfileSettingsForm').should('not.exist');
		cy.get('.pkp_modal:has(#orcidProfileSettingsForm)').should('not.exist');

		cy.get('tr#' + orcidPluginRowId).should('be.visible');
		cy.route('POST', '**/settings-plugin-grid/enable*').as('enableOrcidPlugin');
		cy.route('GET', '**/settings-plugin-grid/fetch-row*').as('refreshOrcidPluginRow');
		cy.get('input[id^=select-cell-orcidprofileplugin]')
			.scrollIntoView()
			.should('be.visible')
			.check();
		cy.wait('@enableOrcidPlugin').then((xhr) => {
			expect(xhr.status).to.equal(200);
			expect(xhr.response.body.status).to.equal(true);
		});
		cy.wait('@refreshOrcidPluginRow').its('status').should('equal', 200);
		cy.get('input[id^=select-cell-orcidprofileplugin]')
			.scrollIntoView()
			.should('be.visible')
			.should('be.checked');

		cy.get('tr#' + pluginRowId + ' a.show_extras').click();
		cy.get('a[id^=' + pluginRowId + '-settings-button]').click();
		cy.contains('Question Blocks');
		cy.get('#orcidAPIPath').should('not.exist');
		cy.get('input[name="orcidClientId"]').should('not.exist');
		cy.get('input[name="orcidClientSecret"]').should('not.exist');
	});
});
