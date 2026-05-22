import '../support/commands.js';

describe('DEIA Survey - Question blocks manager', function () {
	const pluginRowId = 'component-grid-settings-plugins-settingsplugingrid-category-generic-row-deiasurveyplugin';
	const defaultQuestionBlockTitle = 'SciELO Questions';
	const questionBlock = {
		title: 'Funding DEIA questions',
		editedTitle: 'Funding and access DEIA questions',
		description: 'Questions about access to funding opportunities.',
		editedDescription: 'Questions about access to funding and participation opportunities.',
		firstQuestion: {
			text: 'Are you a scholarship recipient?',
			description: 'Select all funding sources that apply.',
			options: ['Institutional scholarship', 'Self describe funding']
		},
		secondQuestion: {
			text: 'What support do you need?',
			description: 'Describe the support that would help your participation.'
		}
	};

	function openPluginSettings() {
		cy.contains('a', 'Website').click();
		cy.waitJQuery();
		cy.get('#plugins-button').click();
		cy.waitJQuery();

		cy.get('tr#' + pluginRowId + ' a.show_extras').click();
		cy.get('a[id^=' + pluginRowId + '-settings-button]').click();
		cy.get('#deiaQuestionBlockGridContainer').contains(defaultQuestionBlockTitle);
	}

	function closeModal() {
		cy.get('.pkp_modal_panel > .close').filter(':visible').last().click();
		cy.wait(500);
	}

	function saveActiveModalForm(formId) {
		cy.get(`form#${formId} button[id^="submitFormButton-"]`).click({force: true});
		cy.contains('Your changes have been saved.');
		cy.waitJQuery();
		cy.wait(500);
	}

	function visibleField(selector) {
		return cy.get(selector).filter(':visible').first();
	}

	function fillVisibleField(selector, value) {
		visibleField(selector)
			.click({force: true})
			.type('{selectall}{backspace}', {force: true})
			.type(value, {force: true});
	}

	function exactTextPattern(text) {
		const escapedText = text.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
		return new RegExp('^\\s*' + escapedText.replace(/\s+/g, '\\s+') + '\\s*$');
	}

	function rowWithText(text) {
		return cy.contains('tr .label', exactTextPattern(text)).closest('tr');
	}

	function showRowActions(title) {
		rowWithText(title).then(($row) => {
			if (!$row.next().is(':visible')) {
				cy.wrap($row).find('a.show_extras').click();
			}
		});
	}

	function openBlockForEditing(title) {
		showRowActions(title);
		rowWithText(title).next().should('be.visible').contains('Edit').click();
	}

	function openQuestionsTabForBlock(title) {
		openBlockForEditing(title);
		cy.get('.pkp_modal_panel:visible').contains('a', 'Questions').click();
		cy.get('#deiaQuestionGridContainer');
	}

	function createQuestionBlock(block) {
		cy.contains('a', 'Create Question Block').click();
		fillVisibleField('input[name^="title["]', block.title);
		fillVisibleField('textarea[name^="description["]', block.description);
		saveActiveModalForm('deiaQuestionBlockForm');
		closeModal();

		openPluginSettings();
		cy.get('#deiaQuestionBlockGridContainer').contains(block.title);
	}

	function editQuestionBlock(block) {
		openBlockForEditing(block.title);
		fillVisibleField('input[name^="title["]', block.editedTitle);
		fillVisibleField('textarea[name^="description["]', block.editedDescription);
		saveActiveModalForm('deiaQuestionBlockForm');

		closeModal();
		openPluginSettings();
		openQuestionsTabForBlock(block.editedTitle);
		cy.get('#deiaQuestionGridContainer').contains('No questions have been created in this block.');
	}

	function addResponseOption(option, hasInputField) {
		cy.contains('a', 'Add Item').click({force: true});
		cy.wait(500);
		fillVisibleField('input[name^="newRowId[responseOption]"]', option);

		if (hasInputField) {
			cy.get('input[name="newRowId[hasInputField]"]:last').check({force: true});
		}
	}

	function createQuestion(question, type, optionsWithInputs) {
		cy.contains('a', 'Create question').click();
		fillVisibleField('input[name^="questionText["]', question.text);
		fillVisibleField('textarea[name^="questionDescription["]', question.description);
		cy.get('select[name="questionType"]').invoke('val', type).trigger('change', {force: true});

		if (optionsWithInputs) {
			optionsWithInputs.forEach((option) => {
				addResponseOption(option.text, option.hasInputField);
			});
		}

		saveActiveModalForm('deiaQuestionForm');
		closeModal();
	}

	function moveRowBefore(rowText, beforeRowText) {
		rowWithText(rowText).then(($rowToMove) => {
			rowWithText(beforeRowText).then(($beforeRow) => {
				$rowToMove.insertBefore($beforeRow);
			});
		});
	}

	function saveGridOrder(gridContainerId) {
		cy.get(`#${gridContainerId} a.pkp_linkaction_orderItems`).click();
		cy.get(`#${gridContainerId} .order_finish_controls .saveButton`).click();
		cy.waitJQuery();
		cy.wait(500);
	}

	function assertRowOrder(firstText, secondText) {
		rowWithText(firstText).then(($firstRow) => {
			rowWithText(secondText).then(($secondRow) => {
				expect($firstRow.index()).to.be.lessThan($secondRow.index());
			});
		});
	}

	function setBlockStatus(title) {
		rowWithText(title).find('input[type="checkbox"]').filter(':visible').first().click({force: true});
		cy.get('div[aria-label="Confirm"] button:contains("OK")').click();
		cy.waitJQuery();
		cy.wait(500);
	}

	function assertCreatedQuestionsAreDisplayed(block) {
		cy.get('#deiaQuestionGridContainer').contains(block.firstQuestion.text);
		cy.get('#deiaQuestionGridContainer').contains(block.secondQuestion.text);
	}

	function assertCustomQuestionBlockIsDisplayed(block) {
		cy.contains('legend', block.editedTitle);
		cy.contains('.description', block.editedDescription);
		cy.contains('label', block.firstQuestion.text);
		cy.contains('.description', block.firstQuestion.description);
		cy.contains('label', block.firstQuestion.options[0]);
		cy.contains('label', block.firstQuestion.options[1])
			.parent()
			.parent()
			.find('input[type="text"]');
		cy.contains('label', block.secondQuestion.text);
		cy.contains('.description', block.secondQuestion.description);
		cy.contains('label', 'Gender').should('not.exist');
	}

	it('Creates, edits, orders and displays DEIA question blocks', function () {
		cy.login('dbarnes', null, 'publicknowledge');
		openPluginSettings();

		createQuestionBlock(questionBlock);
		editQuestionBlock(questionBlock);

		createQuestion(questionBlock.secondQuestion, '2');
		openQuestionsTabForBlock(questionBlock.editedTitle);
		createQuestion(questionBlock.firstQuestion, '4', [
			{text: questionBlock.firstQuestion.options[0], hasInputField: false},
			{text: questionBlock.firstQuestion.options[1], hasInputField: true}
		]);

		openQuestionsTabForBlock(questionBlock.editedTitle);
		assertCreatedQuestionsAreDisplayed(questionBlock);
		moveRowBefore(questionBlock.firstQuestion.text, questionBlock.secondQuestion.text);
		saveGridOrder('deiaQuestionGridContainer');
		assertRowOrder(questionBlock.firstQuestion.text, questionBlock.secondQuestion.text);

		closeModal();

		moveRowBefore(questionBlock.editedTitle, defaultQuestionBlockTitle);
		saveGridOrder('deiaQuestionBlockGridContainer');
		assertRowOrder(questionBlock.editedTitle, defaultQuestionBlockTitle);

		setBlockStatus(questionBlock.editedTitle);

		openBlockForEditing(questionBlock.editedTitle);
		cy.get('.pkp_modal_panel:visible').contains('a', 'Questions').click();
		cy.get('#deiaQuestionGridContainer').contains('Create question').should('not.exist');
		rowWithText(questionBlock.firstQuestion.text).find('a.show_extras').should('not.exist');
		closeModal();

		setBlockStatus(defaultQuestionBlockTitle);
		closeModal();

		cy.logout();
		cy.login('ccorino', null, 'publicknowledge');
		cy.contains('a', 'DEIA Survey').click();
		assertCustomQuestionBlockIsDisplayed(questionBlock);
	});
});
