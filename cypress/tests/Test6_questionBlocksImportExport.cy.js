describe('DEIA Survey - Question blocks import and export', function () {
	const downloadedQuestionBlockPath = Cypress.config('downloadsFolder') + "/question-blocks.json";
	let questionBlock;

	before(function () {
		questionBlock = {
			title: 'Exportable funding DEIA questions',
			description: 'Questions about access to funding opportunities for export.',
			questions: [
				{
					text: 'Are you a scholarship recipient for export?',
					description: 'Select all export funding sources that apply.',
					options: [
						{
							text: 'Institutional scholarship for export',
							hasInputField: false
						},
						{
							text: 'Self describe export funding',
							hasInputField: true
						}
					]
				},
				{
					text: 'What export support do you need?',
					description: 'Describe the support that would help your export participation.'
				}
			]
		};
	});

	it('Creates a question block with questions to export', function () {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.get('nav').contains('Website').click({ force: true });
		cy.get('button[id="plugins-button"]').click();
		cy.get('tr[id*="deiasurveyplugin"] a.show_extras').click();
		cy.get('a[id*="deiasurveyplugin-settings"]').click();

		cy.get('a:contains("Create Question Block")').click();
		cy.waitJQuery();
		cy.wait(500);
		cy.get('form[id^="deiaQuestionBlockForm"] input[id^="title-en-"]').type(questionBlock.title, {delay: 0});
		cy.get('form[id^="deiaQuestionBlockForm"] textarea[id^="description-en-"]').type(questionBlock.description, {delay: 0});

		cy.get('form[id="deiaQuestionBlockForm"] button[id^="submitFormButton-"]').click({force: true});
		cy.contains('Your changes have been saved.');
		cy.waitJQuery();
		cy.wait(500);

		cy.get('span:contains("' + questionBlock.title + '")').prev('a.show_extras').click();
		cy.get('tr:contains("' + questionBlock.title + '")').next().contains('a', 'Edit').click();
		cy.get('#editDeiaQuestionBlockTabs a:contains("Questions")').click();

		questionBlock.questions.forEach((question) => {
			cy.contains('a', 'Create question').click();
			cy.waitJQuery();
			cy.wait(500);
			cy.get('form[id^="deiaQuestionForm"] input[id^="questionText-en-"]').type(question.text, {delay: 0});
			cy.get('form[id^="deiaQuestionForm"] textarea[id^="questionDescription-en-"]').type(question.description, {delay: 0});

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
		});

		cy.logout();
	});

	it('Exports the selected question block', function () {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.get('nav').contains('Website').click({ force: true });
		cy.get('button[id="plugins-button"]').click();
		cy.get('tr[id*="deiasurveyplugin"] a.show_extras').click();
		cy.get('a[id*="deiasurveyplugin-settings"]').click();

		const exportUrl = '/index.php/publicknowledge/$$$call$$$/plugins/generic/deia-survey/classes/controllers/grid/deia-question-block/deia-question-block-grid/export-selected-question-blocks'
		cy.intercept(exportUrl, {method: 'POST'},  (req) => {
			req.redirect('/');
		}).as('download');

		cy.get('#deiaQuestionBlockGridContainer a.pkp_linkaction_exportQuestionBlocks').click();
		cy.get(`tr:contains("${questionBlock.title}") input[name="selectedDeiaQuestionBlocks[]"]`).check();
		cy.get('#deiaQuestionBlockGridContainer .deia_export_finish_controls .saveButton').click();

		cy.wait('@download').its('request').then((req) => {
			cy.request(req).then((res) => {
				expect(res).to.have.property('status', 200);
				expect(res.headers).to.have.property('content-type', 'application/json');
				expect(res.body.plugin).to.equal('deiaSurvey');
				expect(res.body.blocks).to.have.length(1);
				expect(res.body.blocks[0].title.en).to.include(questionBlock.title);
				expect(res.body.blocks[0].description.en).to.include(questionBlock.description);
				expect(res.body.blocks[0].questions[0].questionType).to.equal('TYPE_CHECKBOXES');
				expect(res.body.blocks[0].questions[0].questionText.en).to.include(questionBlock.questions[0].text);
				expect(res.body.blocks[0].questions[0].questionDescription.en).to.include(questionBlock.questions[0].description);
				console.log('Exported Question Block:', res.body.blocks[0].questions[0]);
				expect(res.body.blocks[0].questions[0].responseOptions).to.have.length(2);
				expect(res.body.blocks[0].questions[0].responseOptions[0].optionText.en).to.include(questionBlock.questions[0].options[0].text);
				expect(res.body.blocks[0].questions[0].responseOptions[0].hasInputField).to.equal(false);
				expect(res.body.blocks[0].questions[0].responseOptions[1].optionText.en).to.include(questionBlock.questions[0].options[1].text);
				expect(res.body.blocks[0].questions[0].responseOptions[1].hasInputField).to.equal(true);
				expect(res.body.blocks[0].questions[1].questionType).to.equal('TYPE_TEXT_FIELD');
				expect(res.body.blocks[0].questions[1].questionText.en).to.include(questionBlock.questions[1].text);
				expect(res.body.blocks[0].questions[1].questionDescription.en).to.include(questionBlock.questions[1].description);
				cy.writeFile(downloadedQuestionBlockPath, res.body);
			});
		});

		cy.logout();
	});

	it('Imports a question block file', function () {
		cy.login('dbarnes', null, 'publicknowledge');

		cy.get('nav').contains('Website').click({ force: true });
		cy.get('button[id="plugins-button"]').click();
		cy.get('tr[id*="deiasurveyplugin"] a.show_extras').click();
		cy.get('a[id*="deiasurveyplugin-settings"]').click();

		cy.get('#deiaQuestionBlockGridContainer a.pkp_linkaction_importQuestionBlocks').click();
		cy.readFile(downloadedQuestionBlockPath).then(fileContent => {
			fileContent.blocks[0].title.en = "Imported funding DEIA questions";

			cy.wait(1000);
			cy.get('form#deiaQuestionBlocksImportForm input[type=file]').attachFile(
				{fileContent, filePath: downloadedQuestionBlockPath, mimeType: 'application/json'}
			);
		});
		cy.get('input[name="temporaryFileId"]', { timeout: 20000 }).invoke('val').should('not.be.empty');
		cy.get('form#deiaQuestionBlocksImportForm button[type="submit"]').click();
		cy.wait(500);

		cy.get('tr:contains("Imported funding DEIA questions")').should('exist');
		cy.get('tr:contains("Imported funding DEIA questions") input[id^="select-cell"]').should('not.be.checked');

		cy.get('span:contains("Imported funding DEIA questions")').prev('a.show_extras').click();
		cy.get('tr:contains("Imported funding DEIA questions")').next().contains('a', 'Edit').click();
		cy.get('#editDeiaQuestionBlockTabs a:contains("Questions")').click();
		cy.get('#deiaQuestionGridContainer').contains(questionBlock.questions[0].text);
		cy.get('#deiaQuestionGridContainer').contains(questionBlock.questions[1].text);

		cy.get('span:contains("' + questionBlock.questions[0].text + '")').prev('a.show_extras').click();
		cy.get('tr:contains("' + questionBlock.questions[0].text + '")').next().contains('a', 'Edit').click();
		cy.get('form[id^="deiaQuestionForm"] select[name="questionType"]').should('have.value', '4');
		cy.get('tr:contains("Institutional scholarship for export")').should('exist');
		cy.get('tr:contains("Self describe export funding")').should('exist');
		cy.get('tr:contains("Self describe export funding") input[name$="[hasInputField]"]').should('be.checked');
	});
});
