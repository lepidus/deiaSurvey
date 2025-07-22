import '../support/commands.js';

describe('DEIA Survey - Multiple contexts', function () {
    let newContextData;

    before(function () {
        newContextData = {
            title: 'Opera House',
            initials: 'ohouse',
            abbrev: 'ohouse',
            contactName: 'John Doe',
            contactEmail: 'john.doe@operahouse.com',
            country: 'United Kingdom',
            path: 'operahouse',
            languages: ['en', 'fr_CA'],
            primaryLanguage: 'en'
        };
    });

    it('Creates new context', function () {
        let contextNounUpper = 'Journal';
        let contextNoun = 'journal';
        if (Cypress.env('contextTitles').en == 'Public Knowledge Preprint Server') {
            contextNounUpper = 'Server';
            contextNoun = 'preprint server';
        }

        cy.login('admin', 'admin');

        cy.get('.profile a[id^="pkpDropdown"]').click();
        cy.contains('a', 'Administration').click();

        cy.contains('h1', 'Administration');
        cy.contains('a', 'Hosted ' + contextNounUpper + 's').click();
        cy.contains('a', 'Create ' + contextNounUpper).click();
        cy.wait(1000);

        cy.get('input[name="name-en"]').type(newContextData.title, {delay: 0});
        cy.get('input[name="acronym-en"]').type(newContextData.initials, {delay: 0});
        cy.get('input[name="abbreviation-en"]').type(newContextData.abbrev, {delay: 0});
        cy.get('input[name="contactName"]').type(newContextData.contactName, {delay: 0});
        cy.get('input[name="contactEmail"]').type(newContextData.contactEmail, {delay: 0});
        cy.get('#context-country-control').select(newContextData.country);
        cy.get('input[name="urlPath"]').type(newContextData.path, {delay: 0});
        for (const lang of newContextData.languages) {
            cy.get(`input[name="supportedLocales"][value="${lang}"]`).check();
        }
        cy.get(`input[name="primaryLocale"][value="${newContextData.primaryLanguage}"]`).check();

        cy.contains('Enable this ' + contextNoun + ' to appear publicly').parent().within(() => {
            cy.get('input[name="enabled"]').check();
        });
        cy.contains('.pkpButton', 'Save').click();
        cy.wait(10000);

        cy.contains('h1', 'Settings Wizard');
        cy.get('#context-name-control-en').should('have.value', newContextData.title);
    });
    it('Users register to new context', function () {
        let contextNoun = 'journal';
        if (Cypress.env('contextTitles').en == 'Public Knowledge Preprint Server') {
            contextNoun = 'server';
        }

        cy.login('sberardo', null, 'publicknowledge');
        cy.contains('h1', 'Profile');
        cy.contains('a', 'Roles').click();
        cy.contains('Register with other ' + contextNoun).click();
        cy.contains('label', newContextData.title).parent().within(() => {
            cy.contains('label', 'Author').within(() => {
                cy.get('input').check();
            })
        });
        cy.get('button:visible:contains("Save")').click();
        cy.contains('Your changes have been saved');

        cy.login('dsokoloff', null, 'publicknowledge');
        cy.get('.app__headerActions button').eq(1).click();
        cy.contains('a', 'Edit Profile').click();
        cy.contains('h1', 'Profile');
        cy.contains('a', 'Roles').click();
        cy.contains('Register with other ' + contextNoun).click();
        cy.contains('label', newContextData.title).parent().within(() => {
            cy.contains('label', 'Author').within(() => {
                cy.get('input').check();
            })
        });
        cy.get('button:visible:contains("Save")').click();
        cy.contains('Your changes have been saved');
    });
    it('Admin enables DEIA Survey plugin in new context', function () {
		cy.login('admin', 'admin', newContextData.path);

		cy.contains('a', 'Website').click();

		cy.waitJQuery();
		cy.get('#plugins-button').click();

		cy.get('input[id^=select-cell-deiasurveyplugin]').check();
		cy.get('input[id^=select-cell-deiasurveyplugin]').should('be.checked');
	});
    it('Users who answered the survey are not blocked from using the application', function () {
        cy.login('dsokoloff', null, newContextData.path);
        cy.contains('h1', 'Submissions');
        cy.get('.app__headerActions button').eq(1).click();
        cy.contains('a', 'Edit Profile').click();
        cy.get('span:contains("We request that you fill in the DEIA survey")').should('not.exist');
        
        cy.login('sberardo', null, newContextData.path);
        cy.contains('h1', 'Profile');
        cy.contains('We request that you fill in the DEIA survey on the "DEIA Survey" tab of your profile page');
        cy.assertDefaultQuestionsDisplay('profilePage');
    });
});