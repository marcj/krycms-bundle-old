ka.DashboardWidgets.Load = new Class({
    Extends: ka.DashboardWidgets.Base,

    streamPath: 'admin/load',

    create: function () {
        this.header = new Element('h3', {
            text: ka.tc('dashboardWidget.load', 'Load')
        })
            .inject(this.main);
        this.toElement().addClass('ka-Dashboard-widget-grid-2');

        this.load = new Element('div', {
            style: 'padding: 5px; text-align: center',
            text: '...'
        }).inject(this.main);

        this.speedmeter1 = new ka.ui.Speedmeter(this.main, {
            title: 'CPU %'
        });

        this.speedmeterRamPlaceholder = new ka.ui.Speedmeter(this.main, {
            title: 'RAM'
        });
    },

    update: function (value) {
        this.speedmeter1.setValue(value.cpu);

        if (value.ram.size !== this.lastRamSize) {
            this.speedmeterRamPlaceholder.destroy();
            this.speedmeterRam = new ka.ui.Speedmeter(this.main, {
                title: 'RAM',
                label: 'MB',
                max: Math.round(value.ram.size / 1024)
            });
            this.lastRamSize = value.ram.size;
        }

        if (this.speedmeterRam) {
            this.speedmeterRam.setValue(Math.round(value.ram.used / 1024));
        }

        var val1 = (value.load[0] || 0).toFixed(2);
        var val2 = (value.load[1] || 0).toFixed(2);
        var val3 = (value.load[2] || 0).toFixed(2);

        this.load.set('text', val1 + ' / ' + val2 + ' / ' + val3);
    }
});