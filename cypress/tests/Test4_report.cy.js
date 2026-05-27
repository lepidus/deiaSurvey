describe('DEIA Survey - Report feature', function () {
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
        cy.contains('button', 'Generate Journal Report');
    });
    it('Site report should include question block headers', function () {
        cy.login('admin', 'admin', 'publicknowledge');
        cy.contains('.app__navItem', 'Reports').click();
        cy.contains('a', 'DEIA Survey Report').click();

        cy.contains('Generate Site Report')
            .should('have.attr', 'href')
            .then((reportUrl) => {
                cy.request(reportUrl).then((response) => {
                    expect(response.headers['content-type']).to.match(/text\/(csv|comma-separated-values)/);
                    expect(response.headers['content-disposition']).to.contain('site-deia-report');

                    const lines = response.body
                        .replace(/^\uFEFF/, '')
                        .trim()
                        .split(/\r?\n/);
                    const blockHeader = parseCsvLine(lines[0]);
                    const questionHeader = parseCsvLine(lines[1]);
                    const optionHeader = parseCsvLine(lines[2]);

                    expect(blockHeader[0]).to.be.oneOf(['Journal Name', 'Preprint Server Name']);
                    expect(blockHeader).to.include('SciELO Questions');
                    expect(questionHeader).to.include('Gender');
                    expect(optionHeader).to.include('Woman');
                    expect(optionHeader).to.include('Man');
                    expect(optionHeader).to.include('Non-binary or gender diverse');
                });
            });
    });
    it('Editors can only generate context report', function () {
        cy.login('dbarnes', null, 'publicknowledge');
        cy.contains('.app__navItem', 'Reports').click();
        cy.contains('a', 'DEIA Survey Report').click();

        cy.contains('Please, select which report you want to generate').should('not.exist');
        cy.contains('button', 'Generate Site Report').should('not.exist');

        cy.contains('button', 'Generate Journal Report');
    });
});
