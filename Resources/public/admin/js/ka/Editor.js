ka.Editor = new Class({

    Binds: ['onOver', 'onOut', 'contentMouseDown', 'contentSidebarMouseDown', 'checkChange'],
    Implements: [Options, Events],

    options: {
        node: {},
        id: '',
        standalone: false
    },

    container: null,

    initialize: function(pOptions, pContainer) {
        this.setOptions(pOptions);

        this.container = pContainer || document.documentElement;

        this.adjustAnchors();
        this.searchSlots();

        this.container.addEvent('click:relay(.ka-content)', this.click.bind(this));
        this.container.addEvent('click', this.click.bind(this));

        top.window.fireEvent('krynEditorLoaded', this);
    },

    click: function(e, element) {
        if (element) {
            e.stopPropagation();
            this.disableNextContainerClick = true;
            this.selectElement(element);
        } else if (!this.disableNextContainerClick) {
            this.deselect();
        } else {
            delete this.disableNextContainerClick;
        }
    },

    /**
     * @return {ka.Window}
     */
    getWin: function() {
        return this.getContentField().getWin();
    },

    selectElement: function(element) {
        this.getContentField().selectElement(element);
    },

    deselect: function() {
        this.getContentField().deselect();
    },

    getId: function() {
        return this.options.id;
    },

    getNode: function() {
        return this.options.node;
    },

    getNodeId: function() {
        return this.options.node.id;
    },

    getDomainId: function() {
        return this.options.node.domainId;
    },

    onOver: function(pEvent, pElement) {
        if (this.lastHoveredContentInstance) {
            this.lastHoveredContentInstance.onOut();
        }

        if (pElement.getDocument().body.hasClass('ka-editor-dragMode')) {
            return;
        }

        if (pElement && pElement.kaContentInstance) {
            pElement.kaContentInstance.onOver(pEvent);
            this.lastHoveredContentInstance = pElement.kaContentInstance;
        }
    },

    onOut: function(pEvent, pElement) {
        if (pElement && pElement.kaContentInstance) {
            pElement.kaContentInstance.onOut(pEvent);
            delete this.lastHoveredContentInstance;
        }
    },

    adjustAnchors: function() {
        var params = {};
        params._kryn_editor = 1;
        params._kryn_editor_id = this.options.id;

        var options = Object.clone(this.options);
        delete options.id;
        delete options.node;
        params._kryn_editor_options = options;

        params = Object.toQueryString(params);

        this.container.getElements('a').each(function(a) {
            if (a.href) {
                a.href = a.href + ((a.href.indexOf('?') > 0) ? '&' : '?') + params
            }
        }.bind(this));
    },

    /**
     *
     * @param {ka.ProgressWatchManager} saveManager
     * @returns {Array}
     */
    getValue: function(saveManager) {
        this.slots = this.container.getElements('.ka-slot');

        var contents = [];
        Array.each(this.slots, function(slot) {
            if (slot.kaSlotInstance) {
                contents = contents.concat(slot.kaSlotInstance.getContents());
            }
        });

        contents.each(function(content) {
            content.editorProgressWatch = saveManager.newProgressWatch({
            }, content);
        });

        contents.each(function(content) {
             content.getValue(content.editorProgressWatch);
        });

//        var resultManager = new ka.ProgressWatchManager({
//            onDone: function(progressWatch) {
//                contents = contents.concat(progressWatch.getValue());
//            },
//            onAllDone: function() {
//                saveManager.allDone(contents);
//            },
//            onAllProgress: function(progress) {
//                saveManager.allProgress(progress);
//            }
//        });
//
//        Array.each(this.slots, function(slot) {
//            if (slot.kaSlotInstance) {
//                slot.editorProgressWatch = resultManager.newProgressWatch();
//            }
//        });
//
//        Array.each(this.slots, function(slot) {
//            if (slot.kaSlotInstance) {
//                slot.kaSlotInstance.getValue(slot.editorProgressWatch);
//            }
//        });
//
//        return contents;
    },

    setValue: function(contents) {
        var boxContents = {};

        if ('array' === typeOf(contents)) {
            Array.each(contents, function(content) {
                if (!boxContents[content.boxId]) {
                    boxContents[content.boxId] = [];
                }

                boxContents[content.boxId].push(content);
            });
        }

        Array.each(this.slots, function(slot) {
            var instance = slot.kaSlotInstance;
            if (instance) {
                instance.setValue(boxContents[instance.getBoxId()] || null);
            }
        });
    },

    deactivateLinks: function() {
        this.container.addEvent('click:relay(a)', function(event){
            event.stop();
        });
    },

    setContentField: function(contentField) {
        this.contentField = contentField;
    },

    getContentField: function() {
        return this.contentField;
    },

    getUrl: function() {
        return _pathAdmin + 'admin/object/kryncms/node/' + this.options.node.id + '?_method=patch';
    },

    save: function() {
        if (this.lastSaveRq) {
            this.lastSaveRq.cancel();
        }

        var contents = this.getValue();

        this.lastSaveRq = new Request.JSON({url: this.getUrl(), onComplete: function(pResponse) {

        }.bind(this)}).post({content: contents});
    },

    highlightSlotsBubbles: function(pHighlight) {
        if (!pHighlight) {
            if (this.lastBubbles) {
                this.lastBubbles.invoke('destroy');
            }
            if (this.lastBubbleTimer) {
                clearInterval(this.lastBubbleTimer);
            }
            return;
        }

        this.lastBubbles = [];

        this.slots.each(function(slot) {

            var bubble = new Element('div', {
                'class': 'ka-editor-slot-infobubble',
                text: t('Drag and drop it here')
            }).inject(slot.getDocument().body);

            bubble.position({
                relativeTo: slot,
                position: 'centerTop',
                edge: 'centerBottom'
            });

            bubble.kaEditorOriginTop = bubble.getStyle('top').toInt() - 10;
            bubble.setStyle('top', bubble.kaEditorOriginTop);
            bubble.kaEditorIsOrigin = true;

            bubble.set('tween', {transition: Fx.Transitions.Quad.easeOut, duration: 1500});

            this.lastBubbles.push(bubble);

        }.bind(this));

        var delta = 8;

        var jump = function() {

            Array.each(this.lastBubbles, function(bubble) {

                if (bubble.kaEditorIsOrigin) {
                    bubble.tween('top', bubble.kaEditorOriginTop - delta);
                    bubble.kaEditorIsOrigin = false;
                } else {
                    bubble.tween('top', bubble.kaEditorOriginTop);
                    bubble.kaEditorIsOrigin = true;
                }

            });

        }.bind(this);

        jump();
        this.lastBubbleTimer = jump.periodical(1500, this);
    },

    highlightSave: function(pHighlight) {
        if (this.saveBtn) {
            if (!pHighlight && this.lastTimer) {
                clearInterval(this.lastTimer);
                delete this.lastTimer;
                this.saveBtn.tween('color', '#ffffff');
                return;
            } else if (this.lastTimer) {
                return;
            }

            this.timerIdx = 0;

            this.lastTimer = (function() {
                if (++this.timerIdx % 2) {
                    this.saveBtn.tween('color', '#2A8AEC');
                } else {
                    this.saveBtn.tween('color', '#ffffff');
                }
            }).periodical(500, this);
        }
    },

    setPreview: function(visible) {
        var currentHeight = this.container.getSize().y;
        var currentScroll = this.container.getScroll();
        this.container.setStyle('height', currentHeight);

        var progressWatchManager = new ka.ProgressWatchManager({
            allDone: function() {
                (function() {
                    this.container.setStyle('height');
                    this.container.scroll(currentScroll.x, currentScroll.y);
                }.bind(this)).delay(300);
            }.bind(this)
        });

        var watcher = {};

        Array.each(this.slots, function(slot, idx) {
            watcher[idx] = progressWatchManager.newProgressWatch();
        });

        Array.each(this.slots, function(slot, idx) {
            slot.kaSlotInstance.setPreview(visible, watcher[idx]);
        });
    },

    highlightSlots: function(pEnter) {
        if (pEnter) {
            this.slots.addClass('ka-slot-highlight');
        } else {
            this.slots.removeClass('ka-slot-highlight');
        }
    },

    searchSlots: function() {
        this.slots = this.container.getElements('.ka-slot');

        Array.each(this.slots, function(slot) {
            this.initSlot(slot);
        }.bind(this));
    },

    hasChanges: function() {
        this.slots = this.container.getElements('.ka-slot');

        var hasChanges = false;

        Array.each(this.slots, function(slot) {
            if (slot.kaSlotInstance) {
                hasChanges |= slot.kaSlotInstance.hasChanges();
            }
        });

        return hasChanges;
    },

    checkChange: function() {
        this.highlightSave(this.hasChanges());
    },

    initSlot: function(pDomSlot) {
        pDomSlot.slotInstance = new ka.Slot(pDomSlot, this.options, this);
        pDomSlot.slotInstance.addEvent('change', this.checkChange);
    }


});