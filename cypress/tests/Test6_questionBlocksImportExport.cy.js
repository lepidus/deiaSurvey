import '../support/commands.js';

describe('DEIA Survey - Question blocks import and export', function () {
    const pluginRowId = 'component-grid-settings-plugins-settingsplugingrid-category-generic-row-deiasurveyplugin';
    const defaultQuestionBlockTitle = 'SciELO Questions';
    const exportUrl = '/index.php/publicknowledge/$$$call$$$/plugins/generic/deia-survey/classes/controllers/grid/deia-question-block/deia-question-block-grid/export-selected-question-blocks';
    const uploadFileUrl = '/index.php/publicknowledge/$$$call$$$/plugins/generic/deia-survey/classes/controllers/grid/deia-question-block/deia-question-block-grid/upload-question-blocks-file';
    const importUrl = '/index.php/publicknowledge/$$$call$$$/plugins/generic/deia-survey/classes/controllers/grid/deia-question-block/deia-question-block-grid/upload-question-blocks';
    const questionBlock = {
        title: 'Exportable funding DEIA questions',
        importedTitle: 'Imported exportable funding DEIA questions',
        description: 'Questions about access to funding opportunities for export.',
        importedDescription: 'Imported questions about access to funding and participation opportunities.',
        firstQuestion: {
            text: 'Are you a scholarship recipient for export?',
            description: 'Select all export funding sources that apply.',
            options: ['Institutional scholarship for export', 'Self describe export funding']
        },
        secondQuestion: {
            text: 'What export support do you need?',
            description: 'Describe the support that would help your export participation.'
        }
    };
    let exportedPayload;
    let importPayload;
    let importedExportPayload;

    function openPluginSettings() {
        cy.visit('/index.php/publicknowledge/management/settings/website#plugins');
        cy.get('#plugins-button').click();
        cy.wait(1000);

        cy.get('input[id^=select-cell-deiasurveyplugin]').then(($checkbox) => {
            if (!$checkbox.is(':checked')) {
                cy.wrap($checkbox).check();
                cy.contains('The plugin "DEIA Survey" has been enabled', {timeout: 15000});
                cy.reload();
                cy.contains('h1', 'Profile');
                cy.contains('a', 'DEIA Survey').click();
                cy.get('input[name="deiaDataConsent"][value=0]').click();
                cy.get('#deiaSurveyForm .submitFormButton').click();
                cy.wait(1000);
                cy.visit('/index.php/publicknowledge/management/settings/website#plugins');
                cy.get('#plugins-button').click();
            }
        });

        cy.scrollTo('bottom');
        cy.wait(1000);

        cy.get('tr#' + pluginRowId + ' a.show_extras').click();
        cy.get('a[id^=' + pluginRowId + '-settings-button]').click();
        cy.get('#deiaQuestionBlockGridContainer').contains(defaultQuestionBlockTitle);
    }

    function closeModal() {
        cy.get('body').then(($body) => {
            const $closeButton = $body.find('.pkp_modal_panel > .close:visible').last();

            if ($closeButton.length) {
                cy.wrap($closeButton).click();
                cy.wait(500);
            }
        });
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
        cy.contains('a', 'Questions').click();
        cy.get('#deiaQuestionGridContainer');
    }

    function createQuestionBlock(block) {
        cy.contains('a', 'Create Question Block').click();
        fillVisibleField('input[name^="title["]', block.title);
        fillVisibleField('textarea[name^="description["]', block.description);
        saveActiveModalForm('deiaQuestionBlockForm');
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

    function localizedValues(localizedData) {
        return Object.values(localizedData || {});
    }

    function setLocalizedData(localizedData, value) {
        Object.keys(localizedData || {}).forEach((locale) => {
            localizedData[locale] = value;
        });
    }

    function parseExportedQuestionBlocks(responseBody) {
        return typeof responseBody === 'string' ? JSON.parse(responseBody) : responseBody;
    }

    function exportQuestionBlock(title) {
        return rowWithText(title).invoke('attr', 'id').then((rowId) => {
            const blockId = rowId.match(/-row-(.+)$/)[1];

            return cy.get('input[name="csrfToken"]').first().invoke('val').then((csrfToken) => (
                cy.request({
                    method: 'POST',
                    url: exportUrl,
                    form: true,
                    body: {
                        csrfToken,
                        'selectedDeiaQuestionBlocks[]': blockId
                    }
                }).then((response) => parseExportedQuestionBlocks(response.body))
            ));
        });
    }

    function importQuestionBlocks(payload) {
        cy.window().then((win) => {
            const file = new win.File(
                [JSON.stringify(payload, null, 2)],
                'deia-question-blocks-import.json',
                {type: 'application/json'}
            );
            const formData = new win.FormData();
            formData.append('uploadedFile', file);

            return win.fetch(uploadFileUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin'
            }).then((response) => response.json());
        }).then((uploadResponse) => {
            expect(uploadResponse.status).to.be.true;
            expect(uploadResponse.temporaryFileId).not.to.be.undefined;

            return cy.get('input[name="csrfToken"]').first().invoke('val').then((csrfToken) => (
                cy.request({
                    method: 'POST',
                    url: importUrl,
                    form: true,
                    body: {
                        csrfToken,
                        temporaryFileId: uploadResponse.temporaryFileId
                    }
                }).then((importResponse) => {
                    expect(importResponse.status).to.equal(200);
                })
            ));
        });
    }

    function createImportPayloadFromExport(payload, block) {
        const newPayload = JSON.parse(JSON.stringify(payload));

        setLocalizedData(newPayload.blocks[0].title, block.importedTitle);
        setLocalizedData(newPayload.blocks[0].description, block.importedDescription);

        return newPayload;
    }

    function getExportedQuestions(payload) {
        return Object.values(payload.blocks[0].questions || {});
    }

    function findExportedQuestion(payload, questionText) {
        return getExportedQuestions(payload).find((question) => (
            localizedValues(question.questionText).includes(questionText)
        ));
    }

    function assertExportedBlockMetadata(payload, blockTitle, blockDescription) {
        expect(payload.schemaVersion).to.equal('1.0');
        expect(payload.plugin).to.equal('deiaSurvey');
        expect(payload.blocks).to.have.length(1);
        expect(localizedValues(payload.blocks[0].title)).to.include(blockTitle);
        expect(localizedValues(payload.blocks[0].description)).to.include(blockDescription);
    }

    function assertExportedQuestions(payload, block) {
        const questions = getExportedQuestions(payload);
        const firstQuestion = findExportedQuestion(payload, block.firstQuestion.text);
        const secondQuestion = findExportedQuestion(payload, block.secondQuestion.text);

        expect(questions).to.have.length(2);
        expect(firstQuestion).not.to.be.undefined;
        expect(localizedValues(firstQuestion.questionDescription)).to.include(block.firstQuestion.description);
        expect(secondQuestion).not.to.be.undefined;
        expect(localizedValues(secondQuestion.questionDescription)).to.include(block.secondQuestion.description);
    }

    function assertExportedResponseOptions(payload, block) {
        const firstQuestion = findExportedQuestion(payload, block.firstQuestion.text);
        const secondQuestion = findExportedQuestion(payload, block.secondQuestion.text);
        const firstQuestionOptions = Object.values(firstQuestion.responseOptions || {});
        const secondQuestionOptions = Object.values(secondQuestion.responseOptions || {});

        expect(firstQuestionOptions).to.have.length(2);
        expect(firstQuestionOptions.some((option) => (
            localizedValues(option.optionText).includes(block.firstQuestion.options[0]) && !option.hasInputField
        ))).to.be.true;
        expect(firstQuestionOptions.some((option) => (
            localizedValues(option.optionText).includes(block.firstQuestion.options[1]) && option.hasInputField
        ))).to.be.true;
        expect(secondQuestionOptions).to.have.length(0);
    }

    function assertCreatedQuestionsAreDisplayed(block) {
        cy.get('#deiaQuestionGridContainer').contains(block.firstQuestion.text);
        cy.get('#deiaQuestionGridContainer').contains(block.secondQuestion.text);
    }

    it('Creates a question block with questions to export', function () {
        cy.login('dbarnes', null, 'publicknowledge');
        openPluginSettings();

        createQuestionBlock(questionBlock);
        cy.get('#deiaQuestionBlockGridContainer').contains(questionBlock.title);

        openQuestionsTabForBlock(questionBlock.title);
        createQuestion(questionBlock.secondQuestion, '2');
        openQuestionsTabForBlock(questionBlock.title);
        createQuestion(questionBlock.firstQuestion, '4', [
            {text: questionBlock.firstQuestion.options[0], hasInputField: false},
            {text: questionBlock.firstQuestion.options[1], hasInputField: true}
        ]);
        openQuestionsTabForBlock(questionBlock.title);
        assertCreatedQuestionsAreDisplayed(questionBlock);
        closeModal();
        closeModal();
    });

    it('Exports the selected question block', function () {
        cy.login('dbarnes', null, 'publicknowledge');
        openPluginSettings();

        exportQuestionBlock(questionBlock.title).then((payload) => {
            exportedPayload = payload;
            importPayload = createImportPayloadFromExport(payload, questionBlock);

            assertExportedBlockMetadata(payload, questionBlock.title, questionBlock.description);
        });
        closeModal();
    });

    it('Exports question texts', function () {
        expect(exportedPayload).not.to.be.undefined;

        assertExportedQuestions(exportedPayload, questionBlock);
    });

    it('Exports response options', function () {
        expect(exportedPayload).not.to.be.undefined;

        assertExportedResponseOptions(exportedPayload, questionBlock);
    });

    it('Imports a question block file', function () {
        expect(importPayload).not.to.be.undefined;

        cy.login('dbarnes', null, 'publicknowledge');
        openPluginSettings();
        importQuestionBlocks(importPayload);
        closeModal();
        closeModal();
    });

    it('Displays imported questions in the imported block', function () {
        cy.login('dbarnes', null, 'publicknowledge');
        openPluginSettings();
        openQuestionsTabForBlock(questionBlock.importedTitle);
        assertCreatedQuestionsAreDisplayed(questionBlock);
        closeModal();
        closeModal();
    });

    it('Re-exports the imported question block', function () {
        cy.login('dbarnes', null, 'publicknowledge');
        openPluginSettings();

        exportQuestionBlock(questionBlock.importedTitle).then((payload) => {
            importedExportPayload = payload;
        });
        closeModal();
    });

    it('Re-exports imported question block metadata', function () {
        expect(importedExportPayload).not.to.be.undefined;

        assertExportedBlockMetadata(
            importedExportPayload,
            questionBlock.importedTitle,
            questionBlock.importedDescription
        );
    });

    it('Re-exports imported question texts', function () {
        expect(importedExportPayload).not.to.be.undefined;

        assertExportedQuestions(importedExportPayload, questionBlock);
    });

    it('Re-exports imported response options', function () {
        expect(importedExportPayload).not.to.be.undefined;

        assertExportedResponseOptions(importedExportPayload, questionBlock);
    });
});
