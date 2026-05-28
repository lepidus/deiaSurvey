describe('DEIA Survey - Question blocks manager', function () {
	let questionBlock;

	before(function() {
		questionBlock = {
			title: 'Funding DEIA questions',
			editedTitle: 'Funding and access DEIA questions',
			description: 'Questions about access to funding opportunities.',
			editedDescription: 'Questions about access to funding and participation opportunities.',
			questions: [
				{
					text: 'Are you a scholarship recipient?',
					description: 'Select all funding sources that apply.',
					options: [
						{
							text: 'Institutional scholarship',
							hasInputField: false
						},
						{
							text: 'Self describe funding',
							hasInputField: true
						}
					]
				},
				{
					text: 'What support do you need?',
					description: 'Describe the support that would help your participation.'
				}
			]
		};
	});

	it('Creates DEIA question blocks', function () {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.get('nav').contains('Website').click({ force: true });
		cy.get('button[id="plugins-button"]').click();
		cy.get('tr[id*="deiasurveyplugin"] a.show_extras').click();
		cy.get('a[id*="deiasurveyplugin-settings"]').click();

		cy.get('a:contains("Create Question Block")').click();
		cy.waitJQuery();
		cy.wait(500);

		cy.get('form[id^="deiaQuestionBlockForm"] input[id^="title-en"]').type(questionBlock.title, {delay: 0});
		cy.get('form[id^="deiaQuestionBlockForm"] textarea[id^="description-en"]').type(questionBlock.description, {delay: 0});

		cy.get('form[id="deiaQuestionBlockForm"] button[id^="submitFormButton-"]').click({force: true});
		cy.contains('Your changes have been saved.');
		cy.waitJQuery();
		cy.wait(500);

		cy.get('#deiaQuestionBlockGridContainer').contains(questionBlock.title);

		cy.logout();
	});

	it('Edits DEIA question blocks', function () {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.get('nav').contains('Website').click({ force: true });
		cy.get('button[id="plugins-button"]').click();
		cy.get('tr[id*="deiasurveyplugin"] a.show_extras').click();
		cy.get('a[id*="deiasurveyplugin-settings"]').click();

		cy.get('span:contains("' + questionBlock.title + '")').prev('a.show_extras').click();
		cy.get('tr:contains("' + questionBlock.title + '")').next().contains('a', 'Edit').click();
		cy.waitJQuery();
		cy.wait(500);
		cy.get('form[id^="deiaQuestionBlockForm"] input[id^="title-en"]').clear().type(questionBlock.editedTitle, {delay: 0});
		cy.get('form[id^="deiaQuestionBlockForm"] textarea[id^="description-en"]').clear().type(questionBlock.editedDescription, {delay: 0});

		cy.get('form[id="deiaQuestionBlockForm"] button[id^="submitFormButton-"]').click({force: true});
		cy.contains('Your changes have been saved.');
		cy.waitJQuery();
		cy.wait(500);

		cy.get('#deiaQuestionBlockGridContainer').contains(questionBlock.editedTitle);

		cy.logout();
	});

	it('Creates DEIA question questions', function () {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.get('nav').contains('Website').click({ force: true });
		cy.get('button[id="plugins-button"]').click();
		cy.get('tr[id*="deiasurveyplugin"] a.show_extras').click();
		cy.get('a[id*="deiasurveyplugin-settings"]').click();

		cy.get('span:contains("' + questionBlock.editedTitle + '")').prev('a.show_extras').click();
		cy.get('tr:contains("' + questionBlock.editedTitle + '")').next().contains('a', 'Edit').click();
		cy.get('#editDeiaQuestionBlockTabs a:contains("Questions")').click();

		questionBlock.questions.forEach((question) => {
			cy.contains('a', 'Create question').click();
			cy.waitJQuery();
			cy.wait(500);
			cy.get('form[id^="deiaQuestionForm"] input[id^="questionText-en"]').type(question.text, {delay: 0});
			cy.get('form[id^="deiaQuestionForm"] textarea[id^="questionDescription-en"]').type(question.description, {delay: 0});

			if (question.options) {
				cy.get('form[id^="deiaQuestionForm"] select[name="questionType"]').select('Checkboxes (you can choose one or more)', {force: true});
				question.options.forEach((option, index) => {
					cy.contains('a', 'Add Item').click({force: true});
					cy.wait(500);
					cy.get(`input[id*="newRowId-en"]`).eq(index).type(option.text);

					if (option.hasInputField) {
						cy.get('input[name="newRowId[hasInputField]"]:last').check({force: true});
					}
				});
			} else {
				cy.get('form[id^="deiaQuestionForm"] select[name="questionType"]').select('Single line text box', {force: true});
			}

			cy.get('form[id="deiaQuestionForm"] button[id^="submitFormButton-"]').click({force: true});
			cy.contains('Your changes have been saved.');
			cy.waitJQuery();
			cy.wait(500);

			cy.get('#deiaQuestionGridContainer').contains(question.text);
		});

		cy.logout();
	});

	it('Displays only active DEIA question blocks to users', function () {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.get('nav').contains('Website').click({ force: true });
		cy.get('button[id="plugins-button"]').click();
		cy.get('tr[id*="deiasurveyplugin"] a.show_extras').click();
		cy.get('a[id*="deiasurveyplugin-settings"]').click();

		cy.get('tr:contains("SciELO Questions") input[id^="select-cell"]').click();
		cy.get('button:contains("OK")').click();
		cy.waitJQuery();
		cy.wait(500);

		cy.get('tr:contains("' + questionBlock.editedTitle + '") input[id^="select-cell"]').click();
		cy.get('button:contains("OK")').click();
		cy.waitJQuery();
		cy.wait(500);

		cy.logout();

		cy.login('ccorino', null, 'publicknowledge');
		cy.contains('a', 'DEIA Survey').click();

		cy.contains('legend', 'SciELO Questions').should('not.exist');
		cy.contains('label', 'Gender').should('not.exist');
		cy.contains('label', 'Race').should('not.exist');
		cy.contains('label', 'Ethnicity').should('not.exist');

		cy.contains('legend', questionBlock.editedTitle);
		cy.contains('label.description', questionBlock.editedDescription);
		questionBlock.questions.forEach((question) => {
			cy.contains('label', question.text);
			cy.contains('label.description', question.description);
			if (question.options) {
				question.options.forEach((option) => {
					cy.contains('label', option.text);
					cy.contains('label', option.text).children('input[type="checkbox"]');
					if (option.hasInputField) {
						cy.contains('label', option.text).parent().parent().find('input[type="text"]');
					}
				});
			}
		});

		cy.logout();
	});

	it('Orders DEIA question blocks and questions', function () {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.get('nav').contains('Website').click({ force: true });
		cy.get('button[id="plugins-button"]').click();
		cy.get('tr[id*="deiasurveyplugin"] a.show_extras').click();
		cy.get('a[id*="deiasurveyplugin-settings"]').click();

		cy.get('tr:contains("SciELO Questions") input[id^="select-cell"]').click();
		cy.get('button:contains("OK")').click();
		cy.waitJQuery();
		cy.wait(500);

		cy.get('tr:contains("' + questionBlock.editedTitle + '")').then(($row) => {
			cy.get('tr:contains("SciELO Questions")').then(($defaultRow) => {
				$row.insertBefore($defaultRow);
			});
		});

		cy.get('#deiaQuestionBlockGridContainer a.pkp_linkaction_orderItems').click();
		cy.get('#deiaQuestionBlockGridContainer .order_finish_controls .saveButton').click();
		cy.waitJQuery();
		cy.wait(500);

		cy.get('tr:contains("' + questionBlock.editedTitle + '")').then(($firstRow) => {
			cy.get('tr:contains("SciELO Questions")').then(($secondRow) => {
				expect($firstRow.index()).to.be.lessThan($secondRow.index());
			});
		});

		cy.logout();

		cy.login('ccorino', null, 'publicknowledge');
		cy.contains('a', 'DEIA Survey').click();

		cy.get('fieldset:contains("' + questionBlock.editedTitle + '")').then(($firstRow) => {
			cy.get('fieldset:contains("SciELO Questions")').then(($secondRow) => {
				expect($firstRow.index()).to.be.lessThan($secondRow.index());
			});
		});

		cy.logout();
	});
});
