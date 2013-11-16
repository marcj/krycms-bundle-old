ka.Layout = new Class({

    Implements: [Events, Options],

    options: {

        /**
         * Defines how the layout should look like.
         *
         * Structure:
         *  [
         *     { //row 1
         *       height: 30%,
         *       columns: [150, null],
         *     },
         *     { //row 2
         *       height: 70%,
         *       columns: [null],
         *     }
         *  ]
         *
         * @var {Array}
         */
        layout: [],

        /**
         * Enabled the cell rendering as it would be if all rows were in only one table.
         * (Means: each column are horizontal arranged under above)
         *
         * What is possible with deactivated grid layout?
         *
         *  Each row gets his own table element, that means:
         *
         * +--+----------+
         * |C |  Cell 2  |
         * |1 |          |
         * +--+--+-------+
         * |     |  Ce   |
         * |  C3 |  ll   |
         * |     |   4   |
         * +-------------+
         * @var {Boolean}
         */
        gridLayout: true,

        /**
         * True to fullscreen this layout. (position absolute, width: 100%, height 100%)
         *
         * @var {Boolean}
         */
        fixed: true,

        /**
         *
         * Structure:
         *
         * [
         *    [row, column, direction],
         *    ...
         * ]
         *
         * Example:
         *
         * [
         *    [1,1, 'right'] //mean a splitter between cell(1,1) and the right one.
         * ]
         *
         * @var {Array}
         */
        splitter: [],

        /**
         * Connects the width of two cells together, so that when the left ones gets resized
         * it adjust the width to the equal width of the other cell as well.
         *
         * Structure
         * [
         *    [ [row, column], [row, column] ],
         *    or
         *    [ [row, column], DOMElement ]
         *    or
         *    [ DOMElement, [row, column] ]
         * ]
         *
         * @var {Array}
         */
        connections: []
    },

    main: null,
    body: null,

    container: null,

    cells: [],

    initialize: function (pContainer, pOptions) {

        this.setOptions(pOptions);
        this.container = pContainer;

        this.main = new Element('div', {
            'class': 'ka-Layout-main'
        }).inject(pContainer);

        if (this.options.fixed) {
            this.main.addClass('ka-Layout-main-fixed');
        }

        this.renderLayout();

        this.createResizer();
        this.mapConnections();

    },

    getTable: function () {
        return document.id(this.getVertical().getTable());
    },

    destroy: function () {
        this.main.destroy();
    },

    mapConnections: function () {
        Array.each(this.options.connections, function (connection) {
            this.connectCells(connection[0], connection[1]);
        }.bind(this));
    },

    connectCells: function (pCell1, pCell2) {
        if (typeOf(pCell1) == 'array') {
            pCell1 = this.getCell(pCell1[0], pCell1[1]);
        }
        if (typeOf(pCell2) == 'array') {
            pCell2 = this.getCell(pCell2[0], pCell2[1]);
        }

        if (pCell2.get('tag') != 'td') {
            pCell2 = pCell2.getParent('td');
        }

        pCell1.addEvent('resize', function () {
            pCell2.setStyle('width', pCell1.getStyle('width'));
        }.bind(this));

        pCell1.fireEvent('resize');
    },

    createResizer: function () {
        Array.each(this.options.splitter, function (resize) {
            if ('array' === typeOf(resize)) {
                new ka.LayoutSplitter(this.getCell(resize[0], resize[1]), resize[2]);
            } else {
                console.log('wrong format of `splitter`. Should be a array in a array. [ [1, 2, \'right\']]');
            }
        }.bind(this));
    },

    getCell: function (pRow, pColumn) {
        var row, cell;
        if (row = this.getVertical().getHorizontal(pRow)) {
            if (cell = row.getColumn(pColumn)) {
                return cell;
            } else {
                throw 'Column ' + pColumn + ' in row ' + pRow + ' does not exist.';
            }
        } else {
            throw 'Row ' + pRow + ' does not exist.';
        }
    },

    getTd: function(pRow, pColumn) {
        var row, cell;
        if (row = this.getVertical().getHorizontal(pRow)) {
            if (cell = row.getTd(pColumn)) {
                return cell;
            } else {
                throw 'Column ' + pColumn + ' in row ' + pRow + ' does not exist.';
            }
        } else {
            throw 'Row ' + pRow + ' does not exist.';
        }
    },

    getRow: function (row) {
        return this.getVertical().getRow(row);
    },

    toElement: function () {
        return this.main;
    },

    setVertical: function (pVertical) {
        this.vertical = pVertical;
    },

    getVertical: function () {
        return this.vertical;
    },

    getMain: function () {
        return this.main;
    },

    renderLayout: function () {
        if (!this.options.layout || !this.options.layout.length) {
            return;
        }

        if (!this.getVertical()) {
            this.setVertical(new ka.LayoutVertical(this, {rows: [], gridLayout: this.options.gridLayout}));
        }

        var horizontal;

        Array.each(this.options.layout, function (row) {
            if (row.columns && row.columns.length > 0) {
                horizontal = new ka.LayoutHorizontal(this.getVertical(), {
                    columns: row.columns,
                    height: row.height
                });
            }
        }.bind(this));
    }
});