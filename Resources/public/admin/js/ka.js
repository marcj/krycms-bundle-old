if (typeof ka == 'undefined') {
    window.ka = {};
}

ka.clipboard = {};
ka.settings = {};

ka.performance = false;
ka.streamParams = {};

ka.uploads = {};
ka._links = {};

PATH = _path;
PATH_WEB = PATH;

ka._ = function (p) {
    return t(p);
};

if (typeOf(ka.langs) != 'object') {
    this.langs = {};
}

/**
 * Prints all kind of stuff into console.log.
 * Detects if `console` exists and ignores the call
 * if not.
 *
 * @params {*}
 */
window.logger = ka.logger = function () {
    if (typeOf(console) != "undefined") {
        var args = arguments;
        if (args.length == 1) {
            args = args[0];
        }
        console.log(args);
    }
};

window.error = ka.error = function () {
    if (typeOf(console) != "undefined") {
        var args = arguments;
        if (args.length == 1) {
            args = args[0];
        }
        console.error(args);
    }
};

/**
 * Is true if the current browser has a mobile user agent.
 * @type {Boolean}
 */
ka.mobile = (false
    || navigator.userAgent.match(/Android/i)
    || navigator.userAgent.match(/iPhone/i)
    || navigator.userAgent.match(/webOS/i)
    || navigator.userAgent.match(/iPad/i)
    || navigator.userAgent.match(/iPod/i)
    || navigator.userAgent.match(/BlackBerry/i)
    || navigator.userAgent.match(/Windows Phone/i)
    );

/**
 * Opens the frontend in a new tab.
 */
ka.openFrontend = function () {
    if (top) {
        top.open(_path, '_blank');
    }
};

/**
 * @returns {ka.AdminInterface}
 */
ka.getAdminInterface = function() {
    return ka.adminInterface;
};

/**
 * Return a translated message pMsg with plural and context ability
 *
 * @param string pMsg     message id (msgid)
 * @param string pPlural  message id plural (msgid_plural)
 * @param int    pCount   the count for plural
 * @param string pContext the message id of the context (msgctxt)
 */
window._ = window.t = ka.t = function (pMsg, pPlural, pCount, pContext) {
    return ka._kml2html(ka.translate(pMsg, pPlural, pCount, pContext));
}

ka.translate = function (pMsg, pPlural, pCount, pContext) {
    if (!ka && parent) {
        ka = parent.ka;
    }
    if (ka && !ka.lang && parent && parent.ka) {
        ka.lang = parent.ka.lang;
    }
    var id = (!pContext) ? pMsg : pContext + "\004" + pMsg;

    if (ka.lang && ka.lang[id]) {
        if (typeOf(ka.lang[id]) == 'array') {
            if (pCount) {
                var fn = 'gettext_plural_fn_' + ka.lang['__lang'];
                var plural = window[fn](pCount) + 0;

                if (pCount && ka.lang[id][plural]) {
                    return ka.lang[id][plural].replace('%d', pCount);
                }
                else {
                    return ((pCount === null || pCount === false || pCount === 1) ? pMsg : pPlural);
                }
            } else {
                return ka.lang[id][0];
            }
        } else {
            return ka.lang[id];
        }
    } else {
        return ((!pCount || pCount === 1) && pCount !== 0) ? pMsg : pPlural;
    }
}

window.tf = ka.tf = function () {
    var args = Array.from(arguments);
    var text = args.shift();
    if (typeOf(text) != 'string') {
        throw 'First argument has to be a string.';
    }

    return text.sprintf.apply(text, args);
}

/**
 * Return a translated message $pMsg within a context $pContext
 *
 * @param string pContext the message id of the context
 * @param string pMsg     message id
 */
window.tc = ka.tc = function (pContext, pMsg) {
    return t(pMsg, null, null, pContext);
}

ka._kml2html = function (pRes) {

    var kml = ['ka:help'];
    if (pRes) {
        pRes = pRes.replace(/<ka:help\s+id="(.*)">(.*)<\/ka:help>/g,
            '<a href="javascript:;" onclick="ka.wm.open(\'admin/help\', {id: \'$1\'}); return false;">$2</a>');
    }
    return pRes;
}

ka.findWindow = function (pElement) {

    if (!typeOf(pElement)) {
        throw 'ka.findWindow(): pElement is not an element.';
    }

    var window = pElement.getParent('.kwindow-border');

    return window ? window.windowInstance : false;

}

ka.setLocalSetting = function(key, data) {
    localStorage.setItem(key, data);
}

ka.getLocalSetting = function(key) {
    return localStorage.getItem(key);
}

ka.entrypoint = {

    open: function (pEntrypoint, pOptions, pSource, pInline, pDependWindowId) {
        var entrypoint = ka.entrypoint.get(pEntrypoint);

        if (!entrypoint) {
            throw 'Can not be found entrypoint: ' + pEntrypoint;
            return false;
        }

        if (['custom', 'iframe', 'list', 'edit', 'add', 'combine'].contains(entrypoint.type)) {
            ka.wm.open(pEntrypoint, pOptions, pDependWindowId, pInline, pSource);
        } else if (entrypoint.type == 'function') {
            ka.entrypoint.exec(entrypoint, pOptions, pSource);
        }
    },

    getRelative: function (pCurrent, pEntryPoint) {

        if (typeOf(pEntryPoint) != 'string' || !pEntryPoint) {
            return pCurrent;
        }

        if (pEntryPoint.substr(0, 1) == '/') {
            return pEntryPoint;
        }

        var current = pCurrent + '';
        if (current.substr(current.length - 1, 1) != '/') {
            current += '/';
        }

        return current + pEntryPoint;

    },

    //executes a entry point from type function
    exec: function (pEntrypoint, pOptions, pSource) {

        if (pEntrypoint.functionType == 'global') {
            if (window[pEntrypoint.functionName]) {
                window[pEntrypoint.functionName](pOptions);
            }
        } else if (pEntrypoint.functionType == 'code') {
            eval(pEntrypoint.functionCode);
        }

    },

    get: function (path) {
        if (typeOf(path) != 'string') {
            return;
        }

        var splitted = path.split('/');
        var extension = splitted[0];

        splitted.shift();

        var code = splitted.join('/');

        var tempEntry = false;

        var path = [], config, notFound = false, item;

        config = ka.getConfig(extension);

        if (!config) {
            throw 'Config not found for module ' + extension;
        }

        tempEntry = config.entryPoints[splitted.shift()]
        if (!tempEntry) {
            return null;
        }
        path.push(tempEntry['label']);

        while (item = splitted.shift()) {
            if (tempEntry.children && tempEntry.children[item]) {
                tempEntry = tempEntry.children[item];
                path.push(tempEntry['label']);
            } else {
                notFound = true;
                break;
            }
        }
        ;

        if (notFound) {
            return null;
        }

        tempEntry._path = path;
        tempEntry._module = extension;
        tempEntry._code = code;

        return tempEntry;
    }

};

/**
 *
 * @param {String} value
 * @returns {string} Safe for innerHTML usage.
 */
ka.htmlEntities = function (value) {
    if ('null' === typeOf(value)) return '';
    if ('array' === typeOf(value)) {
        Array.each(value, function(v, k){
            value[k] = ka.htmlEntities(v);
        });
        return value;
    }
    if ('object' === typeOf(value)) {
        Object.each(value, function(v, k){
            value[k] = ka.htmlEntities(v);
        });
        return value;
    }
    if ('element' === typeOf(value)) {
        return value;
    }
    return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

ka.newBubble = function (pTitle, pText, pDuration) {
    return ka.adminInterface.getHelpSystem().newBubble(pTitle, pText, pDuration);
}

/**
 * Adds a prefix to the keys of pFields.
 * Good to group some values of fields of ka.FieldForm.
 *
 * Example:
 *
 *   pFields = {
 *      field1: {type: 'text', label: 'Field 1'},
 *      field2: {type: 'checkbox', label: 'Field 2'}
 *   }
 *
 *   pPrefix = 'options'
 *
 *   pFields will be changed to:
 *   {
 *      'options[field1]': {type: 'text', label: 'Field 1'},
 *      'options[field2]': {type: 'checkbox', label: 'Field 2'}
 *   }
 *
 * @param {Array} pFields Reference to object.
 * @param {String} pPrefix
 */
ka.addFieldKeyPrefix = function (pFields, pPrefix) {
    Object.each(pFields, function (field, key) {
        pFields[pPrefix + '[' + key + ']'] = field;
        delete pFields[key];
        if (pFields.children) {
            ka.addFieldKeyPrefix(field.children, pPrefix);
        }
    });
}

/**
 * Resolve path notations and returns the appropriate class.
 *
 * @param {String} pClassPath
 * @return {Class|Function}
 */
ka.getClass = function (pClassPath) {
    pClassPath = pClassPath.replace('[\'', '.');
    pClassPath = pClassPath.replace('\']', '.');

    if (pClassPath.indexOf('.') > 0) {
        var path = pClassPath.split('.');
        var clazz = null;
        Array.each(path, function (item) {
            clazz = clazz ? clazz[item] : window[item];
        });
        return clazz;
    }

    return window[pClassPath];
}

/**
 * Encodes a value from url usage.
 * If Array, it encodes the whole array an implodes it with comma.
 * If Object, it encodes the while object an implodes the <key>=<value> pairs with a comma.
 *
 * @param {String} pValue
 * @return {STring}
 */
ka.urlEncode = function (pValue) {

    if (typeOf(pValue) == 'string') {
        return encodeURIComponent(pValue).replace(/\%2F/g, '%252F'); //fix apache default setting
    } else if (typeOf(pValue) == 'array') {
        var result = '';
        Array.each(pValue, function (item) {
            result += ka.urlEncode(item) + ',';
        });
        return result.substr(0, result.length - 1);
    } else if (typeOf(pValue) == 'object') {
        var result = '';
        Array.each(pValue, function (item, key) {
            result += key + '=' + ka.urlEncode(item) + ',';
        });
        return result.substr(0, result.length - 1);
    }

    return pValue;

}

/**
 * Decodes a value for url usage.
 * @param {String} pValue
 * @return {String}
 */
ka.urlDecode = function (pValue) {
    if (typeOf(pValue) != 'string') {
        return pValue;
    }

    try {
        return decodeURIComponent(pValue.replace(/%25252F/g, '%2F'));
    } catch (e) {
        return pValue;
    }
}

ka.normalizeObjectKey = function (objectKey) {
    objectKey = objectKey.replace('\\', '/').replace('.', '/').replace(':', '/').toLowerCase().replace('bundle/', '/');
    var bundleName = objectKey.split('/')[0];
    var objectName = objectKey.split('/')[1];

    if (!bundleName || !objectName) {
        throw tf('objectKey `%s` is not a valid object idefntifier (bundlename/objectName)', objectKey);
    }

    return bundleName+'/'+objectName.lcfirst();
}

/**
 * Returns a absolute path.
 * If pPath begins with # it returns pPath
 * if pPath is not a string it returns pPath
 * if pPath contains http:// on the beginning it returns pPath
 *
 * @param {String} pPath
 * @return {String}
 */
ka.mediaPath = function (pPath) {

    if (typeOf(pPath) != 'string') {
        return pPath;
    }

    if (pPath.substr(0, 1) == '#') {
        return pPath;
    }

    if (pPath.substr(0, 1) == '/') {
        return _path + pPath.substr(1);
    } else if (pPath.substr(0, 7) == 'http://') {
        return pPath;
    } else {
        return _path + '' + pPath;
    }

}

/**
 * Returns a list of the primary keys if pObjectKey.
 *
 * @param {String} pObjectKey
 * @return {Array}
 */
ka.getObjectPrimaryList = function (pObjectKey) {
    var def = ka.getObjectDefinition(pObjectKey);

    var res = [];
    Object.each(def.fields, function (field, key) {
        if (field.primaryKey) {
            res.push(key);
        }
    });

    return res;
}

/**
 * Return only the primary keys of pItem as object.
 *
 * @param {String} pObjectKey
 * @param {Object} pItem Always a object with the primary key => value pairs.
 *
 * @return {Object}
 */
ka.getObjectPk = function (pObjectKey, pItem) {
    var pks = ka.getObjectPrimaryList(pObjectKey);
    var result = {};
    Array.each(pks, function (pk) {
        result[pk] = pItem[pk];
    });
    return result;
}

/**
 * This just cut off object://<objectName>/ and returns the raw primary key part.
 *
 * @param {String} uri Internal uri
 * @return {String}
 */
ka.getCroppedObjectId = function (uri) {
    if ('string' !== typeOf(uri)) {
        return uri;
    }

    if (uri.indexOf('object://') == 0) {
        uri = uri.substr(9);
    }

    var idx = uri.indexOf('/'); //cut of bundleName
    uri = -1 === idx ? uri : uri.substr(idx + 1);

    var idx = uri.indexOf('/'); //cut of objectName
    uri = -1 === idx ? uri : uri.substr(idx + 1);

    return uri;
}

/**
 * Returns the id of an object item for the usage in urls (internal uri's) - urlencoded.
 * If you need the full uri, you ka.getObjectUrl
 *
 * @param {String} pObjectKey
 * @param {Array}  pItem
 * @return {String} urlencoded internal uri part of the id.
 */
ka.getObjectUrlId = function (pObjectKey, pItem) {
    var pks = ka.getObjectPrimaryList(pObjectKey);

    if (pks.length == 0) {
        throw pObjectKey + ' does not have primary keys.';
    }

    var urlId = '';
    if (pks.length == 1 && typeOf(pItem) != 'object') {
        return ka.urlEncode(pItem) + '';
    } else {
        var allNull = false;
        Array.each(pks, function (pk) {
            allNull |= null === ka.urlEncode(pItem[pk]);
            urlId += ka.urlEncode(pItem[pk]) + ',';
        });
        if (allNull) return null;
        return urlId.substr(0, urlId.length - 1);
    }

}

/**
 * Just convert the arguments into a new string :
 *    object://<pObjectKey>/<pId>
 *
 *
 * @param {String} pObjectKey
 * @param {String} pId Has to be urlencoded (use ka.urlEncode())
 * @return {String}
 */
ka.getObjectUrl = function (pObjectKey, pId) {
    return 'object://' + ka.normalizeObjectKey(pObjectKey) + '/' + pId;
}

/**
 * Returns the object key (not id) from an object uri.
 *
 * @param url
 */
ka.getObjectKey = function (url) {
    if (typeOf(url) != 'string') {
        throw 'url is not a string';
    }

    if (url.indexOf('object://') == 0) {
        url = url.substr(9);
    }

    var idx = url.indexOf('/');
    if (idx == -1) {
        return '';
    }

    idx = idx + url.substr(idx + 1).indexOf('/');
    return ka.normalizeObjectKey(url.substr(0, idx + 1));
}

/**
 * Returns the PK of an object from a internal object url as object.
 *
 * Examples:
 *
 *  pUrl = object://user/1
 *  => {id: 1}
 *
 *  pUrl = object://user/1/3
 *  => [{id: 1}, {id: 3}]
 *
 *  pUrl = object://file/%2Fadmin%2Fimages%2Fhi.jpg
 *  => {path: /admin/images/hi.jpg}
 *
 * @param  {String} pUrl   object://user/1
 * @return {String|Object}  If we have only one pk, it returns a string, otherwise an array.
 */
ka.getObjectId = function (pUrl) {
    if (typeOf(pUrl) != 'string') {
        return pUrl;
    }
    var res = [];

    if (pUrl.indexOf('object://') != -1) {
        var id = pUrl.substr(10 + pUrl.substr('object://'.length).indexOf('/'));
    } else if (pUrl.indexOf('/') != -1) {
        var id = pUrl.substr(pUrl.indexOf('/') + 1);
    } else {
        var id = pUrl;
    }

    var objectKey = ka.getObjectKey(pUrl);
    var objectUri = ka.getCroppedObjectId(pUrl);

    var pks = ka.getObjectPrimaryList(objectKey);

    var keys = objectUri.split('/');

    if (keys.length > 1) {
        var result = [];
        Array.each(keys, function (key) {
            var pk = {};
            Array.each(key.split(','), function (id, pos) {
                pk[pks[pos]] = ka.urlDecode(id);
            });
            result.push(pk);
        });
        return result;
    } else {
        var result = {};

        Array.each(objectUri.split(','), function (id, pos) {
            result[pks[pos]] = ka.urlDecode(id);
        });

        return result;
    }
}

/**
 * Returns the object label, based on a label field or label template (defined
 * in the object definition).
 * This function calls perhaps the REST API to get all information.
 * If you already have an item object, you should probably use ka.getObjectLabelByItem();
 *
 * You can call this function really fast consecutively, since it queues all and fires
 * only one REST API call that receives all items at once per object key.(at least after 50ms of the last call).
 *
 * @param {String} pUri
 * @param {Function} pCb the callback function.
 *
 */
ka.getObjectLabel = function (pUri, pCb) {
    var objectKey = ka.normalizeObjectKey(ka.getObjectKey(pUri));
    var pkString = ka.getCroppedObjectId(pUri);
    var uri = 'object://' + objectKey + '/' + pkString;

    if (ka.getObjectLabelBusy[objectKey]) {
        ka.getObjectLabel.delay(10, ka.getObjectLabel, [uri, pCb]);
        return;
    }

    if (ka.getObjectLabelQTimer[objectKey]) {
        clearTimeout(ka.getObjectLabelQTimer[objectKey]);
    }

    if (!ka.getObjectLabelQ[objectKey]) {
        ka.getObjectLabelQ[objectKey] = {};
    }

    if (!ka.getObjectLabelQ[objectKey][uri]) {
        ka.getObjectLabelQ[objectKey][uri] = [];
    }

    ka.getObjectLabelQ[objectKey][uri].push(pCb);

    ka.getObjectLabelQTimer[objectKey] = (function () {

        ka.getObjectLabelBusy = true;

        var uri = 'object://' + ka.urlEncode(ka.normalizeObjectKey(objectKey)) + '/';
        Object.each(ka.getObjectLabelQ[objectKey], function (cbs, requestedUri) {
            uri += ka.getCroppedObjectId(requestedUri) + '/';
        });
        if (uri.substr(-1) == '/') {
            uri = uri.substr(0, uri.length - 1);
        }

        new Request.JSON({url: _pathAdmin + 'admin/objects',
            noCache: 1, noErrorReporting: true,
            onComplete: function (pResponse) {
                var result, fullId, cb;

                Object.each(pResponse.data, function (item, pk) {
                    if (item === null) {
                        return;
                    }

                    fullId = 'object://' + objectKey + '/' + pk;
                    result = ka.getObjectLabelByItem(objectKey, item);

                    if (ka.getObjectLabelQ[objectKey][fullId]) {
                        while ((cb = ka.getObjectLabelQ[objectKey][fullId].pop())) {
                            cb(result, item);
                        }
                    }

                });

                //call the callback of invalid requests with false argument.
                Object.each(ka.getObjectLabelQ[objectKey], function (cbs) {
                    cbs.each(function (cb) {
                        cb.attempt(false);
                    });
                });

                ka.getObjectLabelBusy[objectKey] = false;
                ka.getObjectLabelQ[objectKey] = {};

            }}).get({url: uri, returnKeyAsRequested: 1});

    }).delay(30);

};

ka.getObjectLabelQ = {};
ka.getObjectLabelBusy = {};
ka.getObjectLabelQTimer = {};

/**
 * Returns the object label, based on a label field or label template (defined
 * in the object definition).
 *
 * @param {String} pObjectKey
 * @param {Object} pItem
 * @param {String} pMode 'default', 'field' or 'tree'. Default is 'default'
 * @param {Object} pDefinition overwrite definitions stored in the pObjectKey
 * @return {String}
 */
ka.getObjectLabelByItem = function (pObjectKey, pItem, pMode, pDefinition) {

    var definition = ka.getObjectDefinition(pObjectKey);
    if (!definition) {
        throw 'Definition not found ' + pObjectKey;
    }

    var template = (pDefinition && pDefinition.labelTemplate) ? pDefinition.labelTemplate : definition.labelTemplate;
    var label = (pDefinition && pDefinition.labelField) ? pDefinition.labelField : definition.labelField;

    if (pDefinition) {
        ['fieldTemplate', 'fieldLabel', 'treeTemplate', 'treeLabel'].each(function (map) {
            if (typeOf(pDefinition[map]) !== 'null') {
                definition[map] = pDefinition[map];
            }
        });
    }

    /* field ui */
    if (pMode == 'field' && definition.fieldTemplate) {
        template = definition.fieldTemplate;
    }

    if (pMode == 'field' && definition.fieldLabel) {
        label = definition.fieldLabel;
    }

    /* tree */
    if (pMode == 'tree' && definition.treeTemplate) {
        template = definition.treeTemplate;
    }

    if (pMode == 'tree' && definition.treeLabel) {
        label = definition.treeLabel;
    }

    if (!template) {
        //we only have an label field, so return it
        return mowla.fetch('{label}', {label: pItem[label]});
    }

    return mowla.fetch(template, pItem);
}

/**
 * Returns all labels for a object item.
 *
 * @param {Object}  pFields  The array of fields definition, that defines /how/ you want to show the data. limited range of 'type' usage.
 * @param {Object}  pItem
 * @param {String} pObjectKey
 * @param {Boolean} pRelationsAsArray Relations would be returned as arrays/origin or as string(default).
 *
 * @return {Object}
 */
ka.getObjectLabels = function (pFields, pItem, pObjectKey, pRelationsAsArray) {

    var data = pItem, dataKey;
    Object.each(pFields, function (field, fieldId) {
        dataKey = fieldId;
        if (pRelationsAsArray && dataKey.indexOf('.') > 0) {
            dataKey = dataKey.split('.')[0];
        }

        data[dataKey] = ka.getObjectFieldLabel(pItem, field, fieldId, pObjectKey, pRelationsAsArray);
    }.bind(this));

    return data;
}

/**
 * Returns a single label for a field of a object item.
 *
 * @param {Object} pValue
 * @param {Object} pField The array of fields definition, that defines /how/ you want to show the data. limited range of 'type' usage.
 * @param {String} pFieldId
 * @param {String} pObjectKey
 * @param {Boolean} pRelationsAsArray
 *
 * @return {String} Safe HTML. Escapted with ka.htmlEntities()
 */
ka.getObjectFieldLabel = function (pValue, pField, pFieldId, pObjectKey, pRelationsAsArray) {
    var fields = ka.getObjectDefinition(pObjectKey);
    if (!fields) {
        throw 'Object not found ' + pObjectKey;
    }

    var fieldId = pFieldId;
    if (typeOf(pFieldId) == 'string' && pFieldId.indexOf('.') > 0) {
        fieldId = pFieldId.split('.')[0];
    }

    fields = fields['fields'];
    var field = fields[fieldId];

    var showAsField = Object.clone(pField || field);
    if (!showAsField.type) {
        Object.each(field, function (v, i) {
            if (!showAsField[i]) {
                showAsField[i] = v;
            }
        });
    }

    pValue = Object.clone(pValue);

    if (showAsField.type == 'predefined') {
        if (ka.getObjectDefinition(showAsField.object)) {
            showAsField = ka.getObjectDefinition(showAsField.object).fields[showAsField.field];
        }
    }

    showAsField.type = showAsField.type || 'text';
    if (field) {
        field.type = field.type || 'text';
    }

    var clazz = showAsField.type.ucfirst();
    if (!ka.LabelTypes[clazz]) {
        clazz = 'Text';
    }

    if (pRelationsAsArray) {
        showAsField.options = showAsField.options || {};
        showAsField.options.relationsAsArray = true;
    }

    var labelType = new ka.LabelTypes[clazz](field, showAsField, pFieldId, pObjectKey);

    return labelType.render(pValue);
}

/**
 * Returns the module title of the given module key.
 *
 * @param {String} pKey
 * @return {String} Or false, if the module does not exist/its not activated.
 */
ka.getExtensionTitle = function (pKey) {
    var config = ka.getBundleConfig(pKey);
    if (!config) {
        return null;
    }

    return config.label || config.name;
}

ka.getBundleConfig = function(bundle) {
    var result;
    bundle = bundle.toLowerCase();
    Object.each(ka.settings.configs, function(config, key) {
        if (result) return;
        if (key.toLowerCase() == bundle || config.name.toLowerCase() == bundle || config['class'].toLowerCase() == bundle) {
            result = config;
        }
    });
    return result;
}

ka.tryLock = function (pWin, pKey, pForce) {
    if (!pForce) {

        new Request.JSON({url: _pathAdmin + 'admin/backend/tryLock', noCache: 1, onComplete: function (res) {

            if (!res.locked) {
                ka.lockNotPossible(pWin, res);
            }

        }}).get({key: pKey, force: pForce ? 1 : 0});

    } else {
        ka.lockContent(pKey);
    }
}

ka.alreadyLocked = function (pWin, pResult) {

    pWin._alert(t('Currently, a other user has this content open.'));

}

/**
 *
 * @param {Number} bytes
 * @returns {String}
 */
ka.bytesToSize = function (bytes) {
    var sizes = ['Bytes', 'KiB', 'MiB', 'GiB', 'TiB', 'PiB'];
    if (!bytes) {
        return '0 Bytes';
    }
    var i = parseInt(Math.floor(Math.log(bytes) / Math.log(1024)));
    if (i == 0) {
        return (bytes / Math.pow(1024, i)) + ' ' + sizes[i];
    }
    return (bytes / Math.pow(1024, i)).toFixed(1) + ' ' + sizes[i];
};

/**
 *
 * @param {Number} seconds
 *
 * @return {String}
 */
ka.dateTime = function (seconds) {
    var date = new Date(seconds * 1000);
    var nowSeconds = new Date().getTime();
    var diffForThisWeek = 3600 * 24 * 7;

    var format = '%d. %B %Y, %H:%M';
    if (nowSeconds - date < diffForThisWeek) {
        //include full day name if date is within current week.
        format = '%a., ' + format;
    }

    return date.format(format);
}

ka.getDomain = function (pRsn) {
    var result = [];
    ka.settings.domains.each(function (domain) {
        if (domain.id == pRsn) {
            result = domain;
        }
    })
    return result;
}

ka.loadSettings = function (keyLimitation, cb) {
    ka.adminInterface.loadSettings(keyLimitation, cb);
}

/**
 * Returns the bundle configuration array.
 *
 * @param {String} bundleName
 * @returns {Array}
 */
ka.getConfig = function(bundleName) {
    if (!bundleName) return;
    return ka.settings.configs[bundleName]
        || ka.settings.configs[bundleName.toLowerCase()]
        || ka.settings.configsAlias[bundleName]
        || ka.settings.configsAlias[bundleName.toLowerCase()];
}

ka.getShortBundleName = function(bundleName) {
    return bundleName.toLowerCase().replace(/bundle$/, '');
};

ka.loadMenu = function () {
    ka.adminInterface.loadMenu();
}

ka.loadLanguage = function (pLang) {
    if (!pLang) {
        pLang = 'en';
    }
    window._session.lang = pLang;

    Cookie.write('kryn_language', pLang);

    Asset.javascript(_pathAdmin + 'admin/ui/language-plural?lang=' + pLang);

    new Request.JSON({url: _pathAdmin + 'admin/ui/language?lang=' +
        pLang, async: false, noCache: 1, onComplete: function (pResponse) {
        ka.lang = pResponse.data;
        Locale.define('en-US', 'Date', ka.lang);
    }}).get();

}

ka.saveUserSettings = function () {
    if (ka.lastSaveUserSettings) {
        ka.lastSaveUserSettings.cancel();
    }

    ka.settings.user = new Hash(ka.settings.user);

    ka.lastSaveUserSettings =
        new Request.JSON({url: _pathAdmin + 'admin/backend/user-settings', noCache: 1, onComplete: function (res) {
        }}).post({ settings: JSON.encode(ka.settings.user) });
}

ka.resetWindows = function () {
    ka.settings.user['windows'] = new Hash();
    ka.saveUserSettings();
    ka.wm.resizeAll();
}

ka.addStreamParam = function (pKey, pVal) {
    ka.streamParams[pKey] = pVal;
}

ka.removeStreamParam = function (pKey) {
    delete ka.streamParams[pKey];
}

/**
 *
 * @param path
 * @param callback
 */
ka.registerStream = function (path, callback) {
    if (!ka.streamRegistered[path]) {
        ka.streamRegistered[path] = [];
    }
    ka.streamRegistered[path].push(callback);
    ka.loadStream();
}

ka.streamRegistered = {};
/**
 * Register a callback to a stream path.
 *
 * @param {String}   path
 * @param {Function} callback
 */
ka.deRegisterStream = function (path, callback) {
    if (!ka.streamRegistered[path]) {
        return;
    }
    if (callback) {
        var index = ka.streamRegistered[path].indexOf(callback);
        if (-1 !== index) {
            ka.streamRegistered[path].splice(index, 1);
        }
    } else {
        delete ka.streamRegistered[path];
    }
    ka.loadStream();
}

/**
 * The stream loader loop.
 */
ka.loadStream = function () {
    if (ka._lastStreamId) {
        clearTimeout(ka._lastStreamId);
    }

    ka.streamParams.streams = [];
    Object.each(ka.streamRegistered, function (cbs, path) {
        if (0 !== cbs.length) {
            ka.streamParams.streams.push(path);
        }
    });

    if (0 === ka.streamParams.streams.length) {
        return;
    }

    ka._lastStreamId = (function () {
        if (window._session.userId > 0) {
            new Request.JSON({url: _pathAdmin + 'admin/stream', noCache: 1, onComplete: function (res) {
                if (res) {
                    if (res.error) {
                        ka.newBubble(t('Stream error'), res.error + ': ' + res.message);
                    } else {
                        window.fireEvent('stream', res.data);
                        Object.each(ka.streamRegistered, function (cbs, path) {
                            Array.each(cbs, function (cb) {
                                cb(res.data[path], res.data);
                            });
                        });
                    }
                }
                ka._lastStreamId = ka.loadStream.delay(2 * 1000);
            }}).get(ka.streamParams);
        }
    }).delay(50);
}

/**
 *
 * @returns {Object} {type: , value: }
 */
ka.getClipboard = function () {
    return ka.clipboard;
}

ka.setClipboard = function (pTitle, pType, pValue) {
    ka.clipboard = { type: pType, value: pValue };
}

ka.clearClipboard = function () {
    ka.clipboard = {};
}

ka.closeDialogsBodys = [];

ka.closeDialog = function () {

    var killedOne = false;
    Array.each(ka.closeDialogsBodys, function (body) {
        if (killedOne) {
            return;
        }

        var last = document.body.getLast('.ka-dialog-overlay');
        if (last) {
            killedOne = true;
            last.close();
        }
    });
}

ka.openDialog = function (item) {
    if (!item.element || !item.element.getParent) {
        throw 'Got no element.';
    }

    var target = document.body;

    if (item.target && item.target.getWindow()) {
        target = item.target.getWindow().document.body;
    }

    if (!ka.closeDialogsBodys.contains(target)) {
        ka.closeDialogsBodys.push(target);
    }

    var autoPositionLastOverlay = new Element('div', {
        'class': 'ka-dialog-overlay',
        style: 'position: absolute; left:0px; top: 0px; right:0px; bottom:0px;background-color: white; z-index: 201000;',
        styles: {
            opacity: 0.001
        }
    }).addEvent('click',function (e) {
        ka.closeDialog();
        e.stopPropagation();
        this.fireEvent('close');
        if (item.onClose) {
            item.onClose();
        }
    }).inject(target);

    autoPositionLastOverlay.close = function () {
        autoPositionLastOverlay.destroy();
        delete autoPositionLastOverlay;
    };

    item.element.setStyle('z-index', 201001);

    var size = item.target.getWindow().getScrollSize();

    autoPositionLastOverlay.setStyles({
        width: size.x,
        height: size.y
    });

    ka.autoPositionLastItem = item.element;

    item.element.inject(target);

    if (!item.offset) {
        item.offset = {};
    }

    if (!item.primary) {
        item.primary = {
            'position': 'bottomRight',
            'edge': 'upperRight',
            offset: item.offset
        }
    }

    if (!item.secondary) {
        item.secondary = {
            'position': 'upperRight',
            'edge': 'bottomRight',
            offset: item.offset
        }
    }

    var updatePosition = function() {
        item.primary.relativeTo = item.target;
        item.secondary.relativeTo = item.target;

        item.element.position(item.primary);

        var pos = item.element.getPosition();
        var size = item.element.getSize();

        var bsize = item.element.getParent().getSize();
        var bscroll = item.element.getParent().getScroll();
        var height;

        item.element.setStyle('height', '');

        item.minHeight = item.element.getSize().y;

        if (size.y + pos.y > bsize.y + bscroll.y) {
            height = bsize.y - pos.y - 10;
        }

        if (height) {
            if (item.minHeight && height < item.minHeight) {
                var currentTop = item.element.getStyle('top').toInt();
                var offsetY = (item.offset ? item.offset.y : 0) || 0;
                item.element.setStyle('top',
                    currentTop - item.element.getSize().y - item.target.getSize().y + 1 + (offsetY*-1)
                );
                //item.element.position(item.secondary);
            } else {
                item.element.setStyle('height', height);
            }
        }
    };

    updatePosition();
    autoPositionLastOverlay.updatePosition = updatePosition;

    return autoPositionLastOverlay;
}

ka.getPrimariesForObject = function (pObjectKey) {

    var definition = ka.getObjectDefinition(pObjectKey);

    var result = {};

    if (!definition) {
        logger('Can not found object definition for object "' + pObjectKey + '"');
        return;
    }

    Object.each(definition.fields, function (field, fieldKey) {

        if (field.primaryKey) {
            result[fieldKey] = Object.clone(field);
        }

    });

    return result;
}

ka.getPrimaryListForObject = function (pObjectKey) {

    var definition = ka.getObjectDefinition(pObjectKey);

    var result = [];

    if (!definition) {
        logger('Can not found object definition for object "' + pObjectKey + '"');
        return;
    }

    Object.each(definition.fields, function (field, fieldKey) {

        if (field.primaryKey) {
            result.push(fieldKey);
        }

    });

    return result;
}

/**
 * Returns the object definition as array.
 *
 * @param pObjectKey
 * @returns {Object}
 */
ka.getObjectDefinition = function (pObjectKey) {
    if (typeOf(pObjectKey) != 'string') {
        throw 'pObjectKey is not a string: ' + pObjectKey;
    }

    pObjectKey = ka.normalizeObjectKey(pObjectKey);

    var module = ("" + pObjectKey.split('/')[0]).toLowerCase();
    var name = pObjectKey.split('/')[1].toLowerCase();

    if (ka.getConfig(module) && ka.getConfig(module)['objects'][name]) {
        var config = ka.getConfig(module)['objects'][name];
        config._key = pObjectKey;
        return config;
    }
}

ka.getFieldCaching = function () {
    return {
        'cache_type': {
            label: _('Cache storage'),
            type: 'select',
            items: {
                'memcached': _('Memcached'),
                'redis': _('Redis'),
                'apc': _('APC'),
                'files': _('Files')
            },
            'depends': {
                'cache_params[servers]': {
                    needValue: ['memcached', 'redis'],
                    'label': 'Servers',
                    'type': 'array',
                    startWith: 1,
                    'width': 310,
                    'columns': [
                        {'label': _('IP')},
                        {'label': _('Port'), width: 50}
                    ],
                    'fields': {
                        ip: {
                            type: 'text',
                            width: '95%',
                            empty: false
                        },
                        port: {
                            type: 'number',
                            width: 50,
                            empty: false
                        }
                    }
                },
                'cache_params[files_path]': {
                    needValue: 'files',
                    type: 'text',
                    label: 'Caching directory',
                    'default': 'cache/object/'
                }
            }
        }
    }
}

ka.renderLayoutElements = function (pDom, pClassObj) {

    var layoutBoxes = {};

    pDom.getWindow().$$('.kryn_layout_content, .kryn_layout_slot').each(function (item) {

        var options = {};
        if (item.get('params')) {
            var options = JSON.decode(item.get('params'));
        }

        if (item.hasClass('kryn_layout_slot')) {
            layoutBoxes[ options.id ] = new ka.LayoutBox(item, options, pClassObj);
        } //options.name, this.win, options.css, options['default'], this, options );
        else {
            layoutBoxes[ options.id ] = new ka.ContentBox(item, options, pClassObj);
        }

    });

    return layoutBoxes;
}

ka.pregQuote = function (str) {
    // http://kevin.vanzonneveld.net
    // +   original by: booeyOH
    // +   improved by: Ates Goral (http://magnetiq.com)
    // +   improved by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
    // +   bugfixed by: Onno Marsman
    // *     example 1: preg_quote("$40");
    // *     returns 1: '\$40'
    // *     example 2: preg_quote("*RRRING* Hello?");
    // *     returns 2: '\*RRRING\* Hello\?'
    // *     example 3: preg_quote("\\.+*?[^]$(){}=!<>|:");
    // *     returns 3: '\\\.\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:'

    return (str + '').replace(/([\\\.\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:])/g, "\\$1");
}

initWysiwyg = function (pElement, pOptions) {

    var options = {
        extraClass: 'SilkTheme',
        //flyingToolbar: true,
        dimensions: {
            x: '100%'
        },
        actions: 'bold italic underline strikethrough | formatBlock justifyleft justifycenter justifyright justifyfull | insertunorderedlist insertorderedlist indent outdent | undo redo | tableadd | createlink unlink | image | toggleview'
    };

    if (pOptions) {
        options = Object.append(options, pOptions);
    }

    return new MooEditable(document.id(pElement), options);
}

/**
 *
 * @param {Element} element
 * @param {Number} opacity
 */
ka.generateNoise = function (element, opacity) {
    if (!"getContent" in document.createElement('canvas')) {
        return false;
    }

    var
        canvas = document.createElement("canvas")
        , c2d = canvas.getContext("2d")
        , x
        , y
        , r
        , g
        , b
        , opacity = opacity || .2;

    canvas.width = canvas.height = 100;

    for (x = 0; x < canvas.width; x++) {
        for (y = 0; y < canvas.height; y++) {
            r = Math.floor(Math.random() * 80);
            g = Math.floor(Math.random() * 80);
            b = Math.floor(Math.random() * 80);

            c2d.fillStyle = "rgba(" + r + "," + g + "," + b + "," + opacity + ")";
            c2d.fillRect(x, y, 1, 1);
        }
    }

    element.style.backgroundImage = "url(" + canvas.toDataURL("image/png") + ")";
}
