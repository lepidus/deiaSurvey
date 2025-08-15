Cypress.Commands.add('findSubmission', function(tab, title) {
	cy.get('#' + tab + '-button').click();
    cy.get('.listPanel__itemSubtitle:visible:contains("' + title + '")').first()
        .parent().parent().within(() => {
            cy.get('.pkpButton:contains("View")').click();
        });
});

Cypress.Commands.add('assertDefaultQuestionsDisplay', function(place, authorEmail = null) {
    let questionLabelEntity = 'label';
    let questionDescriptionEntity = '.description';
    
    if (place == 'profilePage') {
        cy.contains('a', 'DEIA Survey').click();
    } else if (place == 'questionnairePage') {
        cy.contains('The data from this questionnaire will be associated with the e-mail address: ' + authorEmail);
        questionLabelEntity = '.questionTitle';
        questionDescriptionEntity = '.questionDescription';
    }

    cy.contains(questionLabelEntity, 'Gender');
    cy.contains(questionDescriptionEntity, 'With which gender do you most identify? Please select one option:')
        .should('have.length', 1);
    cy.contains('label', 'Woman');
    cy.contains('label', 'Man');
    cy.contains('label', 'Non-binary or gender diverse');
    cy.contains('label', 'Prefer not to inform');

    cy.contains(questionLabelEntity, 'Race');
    cy.contains(questionDescriptionEntity, 'How would you identify yourself in terms of race? Please select ALL the groups that apply to you:')
        .should('have.length', 1);
    cy.contains('label', 'Asian or Pacific Islander');
    cy.contains('label', 'Black');
    cy.contains('label', 'Hispanic or Latino/a/x');
    cy.contains('label', 'Indigenous');
    cy.contains('label', 'Middle Eastern or North African');
    cy.contains('label', 'White');
    cy.contains('label', 'Prefer not to inform');
    cy.contains('label', 'Self describe');

    cy.contains(questionLabelEntity, 'Ethnicity');
    cy.contains(questionDescriptionEntity, "What are your ethnic origins or ancestry? Please select ALL the geographic areas from which your family's ancestors first originated:")
        .should('have.length', 1);
    cy.contains('label', 'Western Europe');
    cy.contains('label', 'Eastern Europe');
    cy.contains('label', 'North Africa');
    cy.contains('label', 'Sub-Saharan Africa');
    cy.contains('label', 'West Asia / Middle East');
    cy.contains('label', 'South and Southeast Asia');
    cy.contains('label', 'East and Central Asia');
    cy.contains('label', 'Pacific / Oceania');
    cy.contains('label', 'North America');
    cy.contains('label', 'Central America and Caribbean');
    cy.contains('label', 'South America');
    cy.contains('label', 'Prefer not to inform');
    cy.contains('label', 'Self describe');

    if (place == 'questionnairePage') {
        cy.contains('Data is collected in accordance with this journal\'s privacy statement');
    }
});

Cypress.Commands.add('answerDefaultQuestionsOnProfile', function(questions) {
    for (const question of questions) {
        cy.contains('label', question['title']).parent().within(() => {
            if (question['chosenOption'] == 'Self describe') {
                cy.contains('label', 'Self describe').parent().parent().within(() => {
                    cy.get('input[type="checkbox"]').check();
                    cy.get('input[type="text"]').clear().type(question['selfDescribeValue']);
                });
            } else {
                cy.contains('label', question['chosenOption']).within(() => {
                    cy.get('input').check();
                });
            }
        });
    }

    cy.get('#deiaSurveyForm .submitFormButton').click();
    cy.wait(1000);
});

Cypress.Commands.add('assertResponsesToDefaultQuestions', function(questions) {
    cy.contains('a', 'DEIA Survey').click();
    cy.get('input[name="demographicDataConsent"][value=1]').should('be.checked');
    
    for (const question of questions) {
        cy.contains('label', question['title']).parent().within(() => {
            if (question['chosenOption'] == 'Self describe') {
                cy.contains('label', 'Self describe').parent().parent().within(() => {
                    cy.get('input[type="checkbox"]').should('be.checked');
                    cy.get('input[type="text"]').should('have.value', question['selfDescribeValue']);
                });
            } else {
                cy.contains('label', question['chosenOption']).within(() => {
                    cy.get('input').should('be.checked');
                });
            }
        });
    }

    cy.get('#deiaSurveyForm .submitFormButton').click();
    cy.wait(1000);
});