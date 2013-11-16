ka.ContentAbstract = new Class({
    Extends: ka.FieldAbstract,

    /**
     * The reference to the current (parent) ka.Content instance.
     *
     * @type {ka.Field}
     */
    contentInstance: null,

    /**
     * @returns {ka.Editor}
     */
    getEditor: function() {
        return this.getContentInstance().getEditor();
    },

    /**
     * @returns {ka.Slot}
     */
    getSlot: function() {
        return this.getContentInstance().getSlot();
    },

    /**
     * Use this method to create your field layout.
     * Please do not the constructor for this job.
     *
     * Inject your elements to this.fieldInstance.fieldPanel.
     */
    createLayout: function () {
        /* Override it to your needs */
    },

    selected: function(inspectorContainer) {
        //your field got selected
    },

    deselected: function() {
        //your field got deselected
    },

    getContentInstance: function () {
        return this.getParentInstance();
    }
});