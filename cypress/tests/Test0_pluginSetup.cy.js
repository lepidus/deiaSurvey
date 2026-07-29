describe('DEIA Survey - Plugin setup', function () {
	const pluginRowId = 'component-grid-settings-plugins-settingsplugingrid-category-generic-row-deiasurveyplugin';
	const orcidPluginRowId = 'component-grid-settings-plugins-settingsplugingrid-category-generic-row-orcidprofileplugin';
	
	it('Enables DEIA Survey plugin. Editor does not give consent', function () {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.contains('a', 'Website').click();

		cy.waitJQuery();
		cy.get('#plugins-button').click();

		cy.get('input[id^=select-cell-deiasurveyplugin]').check();
		cy.contains('The plugin "DEIA Survey" has been enabled', {timeout: 15000});
		cy.get('input[id^=select-cell-deiasurveyplugin]').should('be.checked');
		cy.reload();

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

		cy.get('input[id^=select-cell-orcidprofileplugin]').check();
		cy.get('input[id^=select-cell-orcidprofileplugin]').should('be.checked');
		cy.reload();

		cy.get('#plugins-button').click();
		cy.get('tr#' + orcidPluginRowId + ' a.show_extras').click();
		cy.get('a[id^=' + orcidPluginRowId + '-settings-button]').click();

		cy.get('#orcidProfileAPIPath').select('Public Sandbox');
		cy.get('input[name="orcidClientId"]').clear().type(Cypress.env('orcidClientId'), {delay: 0});
		cy.get('input[name="orcidClientSecret"]').clear().type(Cypress.env('orcidClientSecret'), {delay: 0});
		cy.get('#orcidProfileSettingsForm').then(($form) => {
			const saveUrl = $form.attr('action');
			expect(saveUrl).to.contain('orcidprofileplugin');
			cy.intercept('POST', saveUrl).as('saveOrcidSettings');
		});
		cy.get('#orcidProfileSettingsForm button:contains("OK")').click();
		cy.wait('@saveOrcidSettings').then(({response}) => {
			expect(response.statusCode).to.equal(200);
			expect(response.body.status).to.equal(true);
			expect(response.body.content).to.equal('');
		});
		cy.get('body').then(($body) => {
			const $close = $body.find('.pkp_modal:has(#orcidProfileSettingsForm) .pkpModalCloseButton');
			if ($close.length) {
				cy.wrap($close).scrollIntoView().should('be.visible').click();
			}
		});
		cy.get('#orcidProfileSettingsForm').should('not.exist');
		cy.get('.pkp_modal:has(#orcidProfileSettingsForm)').should('not.exist');

		cy.get('input[id^=select-cell-orcidprofileplugin]')
			.scrollIntoView()
			.should('be.visible')
			.check();
		cy.get('input[id^=select-cell-orcidprofileplugin]').should('be.checked');

		cy.get('tr#' + pluginRowId + ' a.show_extras').click();
		cy.get('a[id^=' + pluginRowId + '-settings-button]').click();
		cy.contains('Question Blocks');
		cy.get('#orcidAPIPath').should('not.exist');
		cy.get('input[name="orcidClientId"]').should('not.exist');
		cy.get('input[name="orcidClientSecret"]').should('not.exist');
	});
});
