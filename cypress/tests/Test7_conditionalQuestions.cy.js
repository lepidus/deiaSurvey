const context = 'publicknowledge';
const contextUrl = `/index.php/${context}/en`;
const pluginRow = 'tr[id$="-row-deiasurveyplugin"]';
const blocksGrid = '#deiaQuestionBlockGridContainer';
const questionsGrid = '#deiaQuestionGridContainer:visible';
const questionEditor = '#deiaQuestionForm:visible';
const survey = '#deiaSurveyForm';
const preview = '#deiaQuestionBlockPreview';
const checkboxSource = 'Which regions apply?';
const radioSource = 'Do you live in Brazil?';
const dependentQuestion = 'Specify your region';
const checkboxOptions = ['North', 'South', 'Other'];
const invalidOrderMessage = 'A conditional question must follow the question it depends on.';

function openPluginSettings() {
	cy.visit(`${contextUrl}/management/settings/website#plugins`);
	cy.get(pluginRow).should('be.visible');
}

function openQuestionBlocks() {
	openPluginSettings();
	cy.get(pluginRow).find('a.show_extras').click();
	cy.get('a[id*="deiasurveyplugin-settings"]').click();
	cy.get(blocksGrid).should('be.visible');
}

// A manager who has not chosen a consent option is redirected away from settings.
function prepareManager() {
	cy.login('dbarnes', null, context);
	cy.visit(`${contextUrl}/user/profile`);
	cy.get('body').then(($page) => {
		if (!$page.find('a[name="deiaSurvey"]').length) {
			openPluginSettings();
			cy.get(pluginRow).find('input[type="checkbox"]').then(($enabled) => {
				if (!$enabled.prop('checked')) {
					cy.wrap($enabled).check();
				}
			});
			cy.get(pluginRow).find('input[type="checkbox"]').should('be.checked');
			cy.visit(`${contextUrl}/user/profile`);
		}
	});
	cy.get('body').then(($page) => {
		if ($page.find('a[name="deiaSurvey"]').length) {
			cy.get('a[name="deiaSurvey"]').click();
			cy.get(`${survey} input[name="deiaDataConsent"]`).then(($consent) => {
				if (!$consent.is(':checked')) {
					cy.get(`${survey} input[name="deiaDataConsent"][value="0"]`).check();
					saveProfile();
				}
			});
		}
	});
}

function makeBlock(title, questions) {
	return {title: {en: title}, description: {en: 'Conditional questions'}, questions};
}

function choiceQuestion(questionType, title, options) {
	return {
		questionType,
		questionText: {en: title},
		questionDescription: {en: 'Select an answer'},
		responseOptions: options.map((option) => ({optionText: {en: option}})),
	};
}

function conditionalTextQuestion(showWhen) {
	return {
		questionType: 'TYPE_TEXT_FIELD',
		questionText: {en: dependentQuestion},
		questionDescription: {en: 'Give the name of the region'},
		showWhen,
	};
}

function importBlock(block) {
	cy.get(blocksGrid).find('a.pkp_linkaction_importQuestionBlocks').click();
	cy.get('#deiaQuestionBlocksImportForm').should('be.visible').then(($form) => {
		const action = $form.attr('action');
		const csrfToken = $form.find('input[name="csrfToken"]').val();
		const fileContent = JSON.stringify({plugin: 'deiaSurvey', blocks: [block]});
		const uploadUrl = action.replace(/upload-question-blocks(?=\?|$)/, 'upload-question-blocks-file');

		// Use the real import endpoints without depending on Plupload's browser transport.
		cy.window().then(async (win) => {
			const formData = new win.FormData();
			formData.append('uploadedFile', new win.File([fileContent], 'conditional-questions.json', {
				type: 'application/json',
			}));
			const response = await win.fetch(uploadUrl, {method: 'POST', body: formData});
			return response.json();
		}).then((upload) => {
			expect(upload.status).to.equal(true);
			return cy.request({
				method: 'POST',
				url: action,
				form: true,
				body: {csrfToken, temporaryFileId: upload.temporaryFileId},
			});
		}).its('body.status').should('equal', true);
	});

	cy.reload();
	openQuestionBlocks();
	cy.contains(`${blocksGrid} tr.gridRow`, block.title.en).should('be.visible');
}

function openRowAction(grid, title, action) {
	cy.contains(`${grid} tr.gridRow`, title).find('a.show_extras').click();
	cy.contains(`${grid} tr.gridRow`, title).next().contains('a', action).click();
}

function openQuestions(title) {
	openRowAction(blocksGrid, title, 'Edit');
	cy.get('#editDeiaQuestionBlockTabs:visible').contains('a', 'Questions').click();
	cy.get(questionsGrid).should('be.visible');
}

function setBlockActive(title, active) {
	cy.contains(`${blocksGrid} tr.gridRow`, title).find('input[id^="select-cell"]').click();
	cy.contains('button:visible', 'OK').click();
	cy.contains(`${blocksGrid} tr.gridRow`, title).find('input[id^="select-cell"]')
		.should(active ? 'be.checked' : 'not.be.checked');
}

function question(scope, title) {
	return cy.contains(`${scope} [data-deia-question] label`, title).closest('[data-deia-question]');
}

function choice(scope, source, option) {
	return question(scope, source).contains('label', new RegExp(`^\\s*${option}\\s*$`))
		.find('input[type="radio"], input[type="checkbox"]');
}

function dependentField(scope) {
	return question(scope, dependentQuestion).find('input[type="text"][name$="[en]"]');
}

function openProfile() {
	cy.visit(`${contextUrl}/user/profile`);
	cy.get('a[name="deiaSurvey"]').click();
	cy.get(survey).should('be.visible');
}

function saveProfile() {
	cy.intercept('POST', '**/save-deia-data').as('saveAnswers');
	cy.get(survey).contains('button', 'Save').click();
	cy.wait('@saveAnswers').its('response.body.status').should('equal', true);
}

// Supply answers to pre-existing active blocks without assuming which other specs ran.
function completeOtherBlocks(title) {
	cy.get(`${survey} .deiaQuestionBlock`).each(($block) => {
		if ($block.children('legend').text().trim() === title) {
			return;
		}

		cy.wrap($block).find('[data-deia-question]').each(($question) => {
			if (!$question.is(':visible')) {
				return;
			}

			const $choice = $question.find('input[type="radio"]:enabled, input[type="checkbox"]:enabled').first();
			const $select = $question.find('select:enabled').first();
			const $text = $question.find('input[type="text"][required]:enabled:visible, textarea[required]:enabled:visible');
			if ($choice.length) {
				cy.wrap($choice).check();
			} else if ($select.length) {
				const value = $select.find('option:not(:disabled)').toArray().find((option) => option.value)?.value;
				if (value !== undefined) {
					cy.wrap($select).select(value);
				}
			}
			$text.each((index, input) => cy.wrap(input).type('Fixture response'));
		});
	});
}

describe('DEIA Survey - Conditional questions', function () {
	beforeEach(function () {
		this.title = `Conditional questions ${Date.now()}-${Cypress._.uniqueId()}`;
		this.blocksToRemove = [];
		this.withdrawRespondentConsent = false;
		prepareManager();
		openQuestionBlocks();
		cy.then(() => this.blocksToRemove.push(this.title));
	});

	afterEach(function () {
		if (!this.blocksToRemove.length) {
			return;
		}
		if (this.withdrawRespondentConsent) {
			openProfile();
			cy.get(`${survey} input[name="deiaDataConsent"][value="0"]`).check();
			saveProfile();
		}
		cy.logout();
		cy.login('dbarnes', null, context);
		openQuestionBlocks();
		this.blocksToRemove.forEach((title) => {
			cy.get(blocksGrid).then(($grid) => {
				const row = $grid.find('tr.gridRow').toArray().find((item) => item.textContent.includes(title));
				if (!row) {
					return;
				}
				if (Cypress.$(row).find('input[id^="select-cell"]').is(':checked')) {
					setBlockActive(title, false);
				}
				openRowAction(blocksGrid, title, 'Delete');
				cy.contains('button:visible', 'OK').click();
				cy.contains(`${blocksGrid} tr.gridRow`, title).should('not.exist');
			});
		});
	});

	it('configures a single-choice condition and shows the question in the block preview', function () {
		importBlock(makeBlock(this.title, [
			choiceQuestion('TYPE_RADIO_BUTTONS', radioSource, ['Yes', 'No']),
			conditionalTextQuestion(),
		]));
		openQuestions(this.title);
		openRowAction(questionsGrid, dependentQuestion, 'Edit');

		cy.get(questionEditor).within(() => {
			cy.get('input[name="questionVisibility"][value="always"]').should('be.checked');
			cy.get('#showWhenQuestion').should('not.be.visible');
			cy.get('#deiaConditionEnabled').check();
			cy.get('#showWhenOption').should('be.disabled');
			cy.get('#showWhenQuestion').select(radioSource);
			cy.get('#showWhenOption').select('Yes');
			cy.contains('button', 'Save').click();
		});
		cy.get(questionEditor).should('not.exist');
		openRowAction(questionsGrid, dependentQuestion, 'Edit');
		cy.get(questionEditor).within(() => {
			cy.get('#showWhenQuestion option:selected').should('have.text', radioSource);
			cy.get('#showWhenOption option:selected').should('have.text', 'Yes');
		});

		cy.reload();
		openQuestionBlocks();
		openRowAction(blocksGrid, this.title, 'Preview');
		dependentField(preview).should('not.be.visible').and('be.disabled');
		choice(preview, radioSource, 'Yes').check();
		dependentField(preview).should('be.visible').and('have.prop', 'required', true);
		choice(preview, radioSource, 'No').check();
		dependentField(preview).should('not.be.visible').and('be.disabled');
	});

	it('keeps multiple-choice conditions when exporting and importing a block again', function () {
		importBlock(makeBlock(this.title, [
			choiceQuestion('TYPE_CHECKBOXES', checkboxSource, checkboxOptions),
			conditionalTextQuestion([0, 2]),
		]));
		cy.intercept('POST', '**/export-selected-question-blocks').as('exportBlock');
		cy.get(blocksGrid).find('a.pkp_linkaction_exportQuestionBlocks').click();
		cy.contains(`${blocksGrid} tr.gridRow`, this.title).find('input[name="selectedDeiaQuestionBlocks[]"]').check();
		cy.get(blocksGrid).find('.deia_export_finish_controls .saveButton').click();

		const importedTitle = `${this.title} copy`;
		this.blocksToRemove.unshift(importedTitle);
		cy.wait('@exportBlock').its('response').then((response) => {
			expect(response.statusCode).to.equal(200);
			const exported = typeof response.body === 'string' ? JSON.parse(response.body) : response.body;
			expect(exported.blocks).to.have.length(1);
			expect(exported.blocks[0].questions[1].showWhen).to.deep.equal([0, 2]);
			importBlock({...exported.blocks[0], title: {en: importedTitle}});
		});

		openRowAction(blocksGrid, importedTitle, 'Preview');
		choice(preview, checkboxSource, 'North').check();
		dependentField(preview).should('not.be.visible');
		choice(preview, checkboxSource, 'Other').check();
		dependentField(preview).should('be.visible');
		choice(preview, checkboxSource, 'Other').uncheck();
		dependentField(preview).should('not.be.visible');
	});

	it('rejects moving a dependent question before its source with a PKP notification', function () {
		importBlock(makeBlock(this.title, [
			choiceQuestion('TYPE_CHECKBOXES', checkboxSource, checkboxOptions),
			conditionalTextQuestion([0, 2]),
		]));
		openQuestions(this.title);
		cy.get(questionsGrid).find('a.pkp_linkaction_orderItems').click();
		cy.contains(`${questionsGrid} tr.ordering`, checkboxSource).then(($target) => {
			const target = $target[0].getBoundingClientRect();
			cy.contains(`${questionsGrid} tr.ordering`, dependentQuestion).then(($source) => {
				const source = $source[0].getBoundingClientRect();
				const x = source.left + 20;
				const y = source.top + source.height / 2;
				const {scrollX, scrollY} = $source[0].ownerDocument.defaultView;
				const pointerAt = (clientY) => ({
					which: 1, clientX: x, clientY, pageX: x + scrollX, pageY: clientY + scrollY,
				});
				cy.wrap($source).trigger('mousedown', pointerAt(y));
				cy.get(questionsGrid).trigger('mousemove', pointerAt(y - 10));
				cy.get(questionsGrid).trigger('mousemove', pointerAt(target.top + 4));
				cy.get(questionsGrid).trigger('mouseup', pointerAt(target.top + 4));
			});
		});
		cy.get(questionsGrid).find('tr.gridRow').first().should('contain.text', dependentQuestion);

		cy.window().then((win) => cy.stub(win, 'alert').as('orderingAlert'));
		cy.intercept('POST', '**/save-sequence*').as('saveOrder');
		cy.get(questionsGrid).find('.order_finish_controls .saveButton').click();
		cy.wait('@saveOrder');
		cy.contains('.pkpNotification', invalidOrderMessage).should('exist');
		cy.get('@orderingAlert').should('not.have.been.called');
		cy.get(questionsGrid).find('tr.gridRow').first().should('contain.text', checkboxSource);
	});

	it('removes a saved profile answer when its checkbox is deselected', function () {
		importBlock(makeBlock(this.title, [
			choiceQuestion('TYPE_CHECKBOXES', checkboxSource, checkboxOptions),
			conditionalTextQuestion([0, 2]),
		]));
		setBlockActive(this.title, true);
		cy.logout();
		const username = `deia${Date.now()}`;
		cy.register({
			username,
			email: `${username}@example.test`,
			givenName: 'DEIA',
			familyName: 'Conditional',
			affiliation: 'Test institution',
			country: 'Brazil',
		});

		openProfile();
		cy.then(() => { this.withdrawRespondentConsent = true; });
		cy.get(`${survey} input[name="deiaDataConsent"][value="1"]`).check();
		dependentField(survey).should('not.be.visible');
		completeOtherBlocks(this.title);
		choice(survey, checkboxSource, 'North').check();
		dependentField(survey).should('not.be.visible');
		choice(survey, checkboxSource, 'Other').check();
		dependentField(survey).should('be.visible').and('have.prop', 'required', true);
		dependentField(survey).type('Central West');
		saveProfile();
		openProfile();
		choice(survey, checkboxSource, 'Other').should('be.checked');
		dependentField(survey).should('have.value', 'Central West');

		choice(survey, checkboxSource, 'Other').uncheck();
		dependentField(survey).should('not.be.visible').and('be.disabled');
		saveProfile();
		openProfile();
		choice(survey, checkboxSource, 'North').should('be.checked');
		choice(survey, checkboxSource, 'Other').check();
		dependentField(survey).should('have.value', '');
	});
});
