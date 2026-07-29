function assertTokenIsAbsent(response) {
	expect(response.status).to.eq(200);
	expect(JSON.stringify(response.body)).not.to.include('"deiaToken"');
}

describe('DEIA Survey - Plugin setup', function () {	
	it('Enables DEIA Survey plugin. Editor does not give consent', function () {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.visit('/index.php/publicknowledge/en/management/settings/website#plugins');
    	cy.get('#plugins-button').click();

		cy.get('input[id^=select-cell-deiasurveyplugin]').then($checkbox => {
			if (!$checkbox.is(':checked')) {
				cy.wrap($checkbox).check();
			}
		});
		cy.get('input[id^=select-cell-deiasurveyplugin]').should('be.checked');
		cy.request('/index.php/publicknowledge/api/v1/_submissions?offset=0&count=20').then(assertTokenIsAbsent);
		cy.request('/index.php/publicknowledge/api/v1/submissions?offset=0&count=20').then(assertTokenIsAbsent);
		cy.visit('/index.php/publicknowledge/en/user/profile');

		cy.contains('h1', 'Profile');
		cy.contains('a', 'DEIA Survey').click();
		cy.get('input[name="deiaDataConsent"][value=0]').click();
        cy.get('#deiaSurveyForm .submitFormButton').click();
        cy.wait(1000);
	});
});
