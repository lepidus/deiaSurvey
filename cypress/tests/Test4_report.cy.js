function getNowDateAndHour() {
    let now = new Date().toISOString();
    const charactersToRemove = ['-', ':', 'T'];
    let nowFormatted = '';

    for (let i = 0; i < now.length; i++) {
        let shouldRemove = false;
        
        for (let j = 0; j < charactersToRemove.length; j++) {
            if (now[i] === charactersToRemove[j]) {
                shouldRemove = true;
                break;
            }
        }

        if (!shouldRemove) {
            nowFormatted += now[i];
        }
    }

    return (nowFormatted.split('.')[0]);
}

describe('DEIA Survey - Report feature', function () {
    let contextName;
    
    function parseCsvLine(line) {
        const values = [];
        let value = '';
        let insideQuotes = false;

        for (let index = 0; index < line.length; index++) {
            const character = line[index];
            const nextCharacter = line[index + 1];

            if (character === '"' && insideQuotes && nextCharacter === '"') {
                value += '"';
                index++;
            } else if (character === '"') {
                insideQuotes = !insideQuotes;
            } else if (character === ',' && !insideQuotes) {
                values.push(value);
                value = '';
            } else {
                value += character;
            }
        }

        values.push(value);
        return values;
    }

    before(() => {
        if (Cypress.env('contextTitles').en_US === 'Journal of Public Knowledge') {
            contextName = 'Journal';
        } else {
            contextName = 'Preprint Server';
        }
    });

    afterEach(() => {
        cy.logout();
    });

    it('All reports should be visible for admin user', function () {
        cy.login('admin', 'admin', 'publicknowledge');
        cy.contains('.app__navItem', 'Reports').click();
        cy.contains('a', 'DEIA Survey Report').click();

        cy.contains('DEIA Survey Report');
        cy.contains('Please, select which report you want to generate');

        cy.contains('button', 'Generate Site Report');
        cy.contains('button', 'Generate ' + contextName + ' Report');
    });

    it('Editors can only generate context report', function () {
        cy.login('dbarnes', null, 'publicknowledge');
        cy.contains('.app__navItem', 'Reports').click();
        cy.contains('a', 'DEIA Survey Report').click();

        cy.contains('Please, select which report you want to generate').should('not.exist');
        cy.contains('button', 'Generate Site Report').should('not.exist');

        cy.contains('button', 'Generate ' + contextName + ' Report');
    });
});
