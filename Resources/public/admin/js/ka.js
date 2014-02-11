window.ka = window.ka || {};

ka.clipboard = {};
ka.settings = {};

ka.performance = false;
ka.streamParams = {};

ka.langs = ka.langs || {};
_path = _path || location.pathname.dirname();

/**
 * Is true if the current browser has a mobile user agent.
 * @type {Boolean}
 */
ka.mobile = navigator.userAgent.match(/Android/i) || navigator.userAgent.match(/iPhone/i) || navigator.userAgent.match(/webOS/i) || navigator.userAgent.match(/iPad/i) || navigator.userAgent.match(/iPod/i) || navigator.userAgent.match(/BlackBerry/i) || navigator.userAgent.match(/Windows Phone/i);

/**
 * Alias for ka.t().
 *
 * @param {String} p
 * @returns {String}
 */
ka._ = function(p) {
    return t(p);
};

window.logger = function(){
    if ('undefined' !== typeof console) {
        console.error.apply(console, arguments);
    }
};

/**
 * Opens the frontend in a new tab.
 */
ka.openFrontend = function() {
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
 * Return a translated message with plural and context ability
 * with additional replacement of kml2html.
 *
 * @param {String} message Message id (msgid)
 * @param {String} plural  Message id plural (msgid_plural)
 * @param {Number} count   the count for plural
 * @param {String} context the message id of the context (msgctxt)
 *
 * @return {String}
 */
window._ = window.t = ka.t = function(message, plural, count, context) {
    return ka._kml2html(ka.translate(message, plural, count, context));
};

/**
 * Return a translated message with plural and context ability.
 *
 * @param {String} message Message id (msgid)
 * @param {String} plural  Message id plural (msgid_plural)
 * @param {Number} count   the count for plural
 * @param {String} context the message id of the context (msgctxt)
 *
 * @return {String}
 */
ka.translate = function(message, plural, count, context) {
    if (!ka && parent) {
        ka = parent.ka;
    }
    if (ka && !ka.lang && parent && parent.ka) {
        ka.lang = parent.ka.lang;
    }
    var id = (!context) ? message : context + "\004" + message;

    if (ka.lang && ka.lang[id]) {
        if (typeOf(ka.lang[id]) == 'array') {
            if (count) {
                var fn = 'gettext_plural_fn_' + ka.lang['__lang'];
                var plural = window[fn](count) + 0;

                if (count && ka.lang[id][plural]) {
                    return ka.lang[id][plural].replace('%d', count);
                } else {
                    return ((count === null || count === false || count === 1) ? message : plural);
                }
            } else {
                return ka.lang[id][0];
            }
        } else {
            return ka.lang[id];
        }
    } else {
        return ((!count || count === 1) && count !== 0) ? message : plural;
    }
};

/**
 * sprintf for translations.
 *
 * @return {String}
 */
window.tf = ka.tf = function() {
    var args = Array.from(arguments);
    var text = args.shift();
    if (typeOf(text) != 'string') {
        throw 'First argument has to be a string.';
    }

    return text.sprintf.apply(text, args);
};

/**
 * Return a translated message within a context.
 *
 * @param {String} context the message id of the context
 * @param {String} message message id
 */
window.tc = ka.tc = function(context, message) {
    return t(message, null, null, context);
};

/**
 * Replaces some own <ka:> elements with correct html.
 *
 * @param {String} message
 *
 * @returns {String}
 * @private
 */
ka._kml2html = function(message) {

    var kml = ['ka:help'];
    if (message) {
        message = message.replace(/<ka:help\s+id="(.*)">(.*)<\/ka:help>/g, '<a href="javascript:;" onclick="ka.wm.open(\'admin/help\', {id: \'$1\'}); return false;">$2</a>');
    }
    return message;
};

ka.entrypoint = {

    open: function(path, options, inline, dependWindowId) {
        var entryPoint = ka.entrypoint.get(path);

        if (!entryPoint) {
            throw 'Can not be found entryPoint: ' + path;
            return false;
        }

        if (['custom', 'iframe', 'list', 'edit', 'add', 'combine'].contains(entryPoint.type)) {
            ka.wm.open(path, options, dependWindowId, inline);
        } else if (entryPoint.type == 'function') {
            ka.entrypoint.exec(entryPoint, options);
        }
    },

    getRelative: function(current, entryPoint) {

        if (typeOf(entryPoint) != 'string' || !entryPoint) {
            return current;
        }

        if (entryPoint.substr(0, 1) == '/') {
            return entryPoint;
        }

        current = current + '';
        if (current.substr(current.length - 1, 1) != '/') {
            current += '/';
        }

        return current + entryPoint;

    },

    //executes a entry point from type function
    exec: function(entryPoint, options) {

        if (entryPoint.functionType == 'global') {
            if (window[entryPoint.functionName]) {
                window[entryPoint.functionName](options);
            }
        } else if (entryPoint.functionType == 'code') {
            eval(entryPoint.functionCode);
        }

    },

    get: function(path) {
        if (typeOf(path) != 'string') {
            return;
        }

        var splitted = path.split('/');
        var extension = splitted[0];

        splitted.shift();

        var code = splitted.join('/');

        var config, notFound = false, item;
        path = [];

        config = ka.getConfig(extension);

        if (!config) {
            throw 'Config not found for module ' + extension;
        }

        var tempEntry = config.entryPoints[splitted.shift()]
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
 * Replaces all <, >, & and " with html so you can use it in safely innerHTML.
 *
 * @param {String}   value
 * @returns {string} Safe for innerHTML usage.
 */
ka.htmlEntities = function(value) {
    if ('null' === typeOf(value)) return '';
    if ('array' === typeOf(value)) {
        Array.each(value, function(v, k) {
            value[k] = ka.htmlEntities(v);
        });
        return value;
    }
    if ('object' === typeOf(value)) {
        Object.each(value, function(v, k) {
            value[k] = ka.htmlEntities(v);
        });
        return value;
    }
    if ('element' === typeOf(value)) {
        return value;
    }
    return String(value).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
};

/**
 * Creates a new information bubble on the right corner.
 *
 * @param {String} title
 * @param {String} text
 * @param {String} duration
 *
 * @returns {Element}
 */
ka.newBubble = function(title, text, duration) {
    return ka.adminInterface.getHelpSystem().newBubble(title, text, duration);
};

/**
 * Adds a prefix to the keys of pFields.
 * Good to group some values of fields of ka.FieldForm.
 *
 * Example:
 *
 *   fields = {
 *      field1: {type: 'text', label: 'Field 1'},
 *      field2: {type: 'checkbox', label: 'Field 2'}
 *   }
 *
 *   prefix = 'options'
 *
 *   fields will be changed to:
 *   {
 *      'options[field1]': {type: 'text', label: 'Field 1'},
 *      'options[field2]': {type: 'checkbox', label: 'Field 2'}
 *   }
 *
 * @param {Array} fields Reference to object.
 * @param {String} prefix
 */
ka.addFieldKeyPrefix = function(fields, prefix) {
    Object.each(fields, function(field, key) {
        fields[prefix + '[' + key + ']'] = field;
        delete fields[key];
        if (fields.children) {
            ka.addFieldKeyPrefix(field.children, prefix);
        }
    });
};

/**
 * Resolve path notations and returns the appropriate class.
 *
 * @param {String} classPath
 * @return {Class|Function}
 */
ka.getClass = function(classPath) {
    classPath = classPath.replace('[\'', '.');
    classPath = classPath.replace('\']', '.');

    if (classPath.indexOf('.') > 0) {
        var path = classPath.split('.');
        var clazz = null;
        Array.each(path, function(item) {
            clazz = clazz ? clazz[item] : window[item];
        });
        return clazz;
    }

    return window[classPath];
};

/**
 * Encodes a value from url usage.
 * If Array, it encodes the whole array an implodes it with comma.
 * If Object, it encodes the whole object and implodes the <key>=<value> pairs with a comma.
 *
 * @param {String} value
 *
 * @return {String}
 */
ka.urlEncode = function(value) {
	var result;
    if (typeOf(value) == 'string') {
        return encodeURIComponent(value).replace(/\%2F/g, '%252F'); //fix apache default setting
    } else if (typeOf(value) == 'array') {
        result = '';
        Array.each(value, function(item) {
            result += ka.urlEncode(item) + ',';
        });
        return result.substr(0, result.length - 1);
    } else if (typeOf(value) == 'object') {
        result = '';
        Array.each(value, function(item, key) {
            result += key + '=' + ka.urlEncode(item) + ',';
        });
        return result.substr(0, result.length - 1);
    }

    return value;
};

/**
 * Decodes a value for url usage.
 *
 * @param {String} value
 *
 * @return {String}
 */
ka.urlDecode = function(value) {
    if (typeOf(value) != 'string') {
        return value;
    }

    try {
        return decodeURIComponent(value.replace(/%25252F/g, '%2F'));
    } catch (e) {
        return value;
    }
};

/**
 * Normalizes a objectKey.
 *
 * @param {String} objectKey
 *
 * @returns {String|Null}
 */
ka.normalizeObjectKey = function(objectKey) {
    objectKey = objectKey.replace('\\', '/').replace('.', '/').replace(':', '/');
    var bundleName = objectKey.split('/')[0].toLowerCase().replace(/bundle$/, '');
    var objectName = objectKey.split('/')[1];

    if (!bundleName || !objectName) {
        return null;
    }

    return bundleName + '/' + objectName.lcfirst();
};

/**
 * Normalizes a entryPoint path.
 *
 * Example
 *
 *   KrynCmsBundle/entry/point/path
 *   => kryncms/entry/point/path
 *
 *
 * @param {String} path
 *
 * @returns {String}
 */
ka.normalizeEntryPointPath = function(path) {
    var slash = path.indexOf('/');

    return ka.getShortBundleName(path.substr(0, slash)) + path.substr(slash);
};

/**
 * Returns a absolute path.
 * If path begins with # it returns path
 * if path is not a string it returns path
 * if path contains http:// on the beginning it returns path
 *
 * @param {String} path
 *
 * @return {String}
 */
ka.mediaPath = function(path) {

    if (typeOf(path) != 'string') {
        return path;
    }

    if (path.substr(0, 1) == '#') {
        return path;
    }

    if (path.substr(0, 1) == '/') {
        return _path + path.substr(1);
    } else if (path.substr(0, 7) == 'http://') {
        return path;
    } else {
        return _path + '' + path;
    }
};

/**
 * Returns a list of the primary keys.
 *
 * @param {String} objectKey
 *
 * @return {Array}
 */
ka.getObjectPrimaryList = function(objectKey) {
    var def = ka.getObjectDefinition(objectKey);

    var res = [];
    Object.each(def.fields, function(field, key) {
        if (field.primaryKey) {
            res.push(key);
        }
    });

    return res;
};

/**
 * Returns the primaryKey name.
 *
 * @param {String} objectKey
 *
 * @returns {String}
 */
ka.getObjectPrimaryKey = function(objectKey) {
    var pks = ka.getObjectPrimaryList(objectKey);
    return pks[0];
};

/**
 * Return only the primary key values of a object.
 *
 * @param {String} objectKey
 * @param {Object} item Always a object with the primary key => value pairs.
 *
 * @return {Object}
 */
ka.getObjectPk = function(objectKey, item) {
    var pks = ka.getObjectPrimaryList(objectKey);
    var result = {};
    Array.each(pks, function(pk) {
        result[pk] = item[pk];
    });
    return result;
};

/**
 * Return the internal representation (id) of object primary keys.
 *
 * If the object has a composite primaryKey it all values are joined together
 * separated with a slash character. All primary parts are urlEncoded: urlEncode(<pk1>)/urlEncode(<pk2>)
 *
 * @param {String} objectKey
 * @param {Object} item
 *
 * @return {String}
 */
ka.getObjectId = function(objectKey, item) {
    var pks = ka.getObjectPrimaryList(objectKey);

    if (1 < pks.length) {
        var values = [];
        Array.each(pks, function(pk) {
            values = ka.urlEncode(item[pk]);
        });
        return values.join('/');
    }

    return item[pks[0]];
};

/**
 * Returns the id part of a object url (object://<objectName>/<id>).
 *
 * If you need the full uri, use ka.getObjectUrl.
 *
 * @param {String} objectKey
 * @param {Array}  item
 *
 * @return {String} already urlEncoded
 */
ka.getObjectUrlId = function(objectKey, item) {
    var id = ka.getObjectId(objectKey, item);
    return ka.hasCompositePk(objectKey) ? id : ka.urlEncode(id);
};

/**
 * Returns the correct escaped id part of the object url (object://<objectName>/<id>).
 *
 * @param {String} objectKey
 * @param {String} id String from ka.getObjectId or ka.getObjectIdFromUrl e.g.
 */
ka.getObjectUrlIdFromId = function(objectKey, id) {
    return ka.hasCompositePk(objectKey) ? id : ka.urlEncode(id);
};

/**
 * Returns true if objectKey as more than one primary key.
 *
 * @param {String} objectKey
 * @returns {boolean}
 */
ka.hasCompositePk = function(objectKey) {
    return 1 < ka.getObjectPrimaryList(objectKey).length;
};

/**
 * Just converts arguments into a new string :
 *
 *    object://<objectKey>/<id>
 *
 *
 * @param {String} objectKey
 * @param {String} id        Has to be urlEncoded (use ka.urlEncode or ka.getObjectUrlId)
 * @return {String}
 */
ka.getObjectUrl = function(objectKey, id) {
    return 'object://' + ka.normalizeObjectKey(objectKey) + '/' + id;
};

/**
 * This just cut off object://<objectName>/ and returns the raw primary key part.
 *
 * @param {String} url
 *
 * @return {String}
 */
ka.getCroppedObjectId = function(url) {
    if ('string' !== typeOf(url)) {
        return url;
    }

    if (url.indexOf('object://') == 0) {
        url = url.substr(9);
    }

    var idx = url.indexOf('/'); //cut of bundleName
    url = -1 === idx ? url : url.substr(idx + 1);

    idx = url.indexOf('/'); //cut of objectName
    url = -1 === idx ? url : url.substr(idx + 1);

    return url;
};

/**
 * This just cut anything but the full raw objectKey.
 *
 * Example:
 *
 *    kryn/file/3 => kryn/file
 *
 * @param {String} url Internal url
 *
 * @return {String} the objectKey
 */
ka.getCroppedObjectKey = function(url) {
    if ('string' !== typeOf(url)) {
        return url;
    }

    if (url.indexOf('object://') == 0) {
        url = url.substr(9);
    }

    var idx = url.indexOf('/'); //till bundleName/
    var nextPart = url.substr(idx + 1); // now we have <objectKey>/<id>

    var lastIdx = nextPart.indexOf('/'); //till objectKey/

    return -1 === lastIdx ? url : url.substr(0, idx + lastIdx + 1);
};

/**
 * Return the internal representation (id) of a internal object url.
 *
 * Examples:
 *
 *  url = object://kryncms/user/1
 *  => 1
 *
 *  url = object://kryncms/file/%2Fadmin%2Fimages%2Fhi.jpg
 *  => /admin/images/hi.jpg
 *
 *  url = object://kryncms/test/pk1/pk2
 *  => pk1/pk2
 *
 * @param {String} url
 *
 * @return {String}
 */
ka.getObjectIdFromUrl = function(url) {
    var pks = ka.getObjectPrimaryList(ka.getCroppedObjectKey(url));

    var pkString = ka.getCroppedObjectId(url);

    if (1 < pks.length) {
        return pkString; //already correct formatted
    }

    return ka.urlDecode(pkString);
};

/**
 * Returns the object label, based on a label field or label template (defined
 * in the object definition).
 * This function calls perhaps the REST API to get all information.
 * If you already have an item object, you should probably use ka.getObjectLabelByItem();
 *
 * You can call this function really fast consecutively, since it queues all and fires
 * only one REST API call that receives all items at once per object key.(at least after 50ms of the last call).
 *
 * @param {String} uri
 * @param {Function} callback the callback function.
 *
 */
ka.getObjectLabel = function(uri, callback) {
    var objectKey = ka.normalizeObjectKey(ka.getCroppedObjectKey(uri));
    var pkString = ka.getCroppedObjectId(uri);
    var normalizedUrl = 'object://' + objectKey + '/' + pkString;

    if (ka.getObjectLabelBusy[objectKey]) {
        ka.getObjectLabel.delay(10, ka.getObjectLabel, [normalizedUrl, callback]);
        return;
    }

    if (ka.getObjectLabelQTimer[objectKey]) {
        clearTimeout(ka.getObjectLabelQTimer[objectKey]);
    }

    if (!ka.getObjectLabelQ[objectKey]) {
        ka.getObjectLabelQ[objectKey] = {};
    }

    if (!ka.getObjectLabelQ[objectKey][normalizedUrl]) {
        ka.getObjectLabelQ[objectKey][normalizedUrl] = [];
    }

    ka.getObjectLabelQ[objectKey][normalizedUrl].push(callback);

    ka.getObjectLabelQTimer[objectKey] = (function() {

        ka.getObjectLabelBusy = true;

        var uri = 'object://' + ka.normalizeObjectKey(objectKey) + '/';
        Object.each(ka.getObjectLabelQ[objectKey], function(cbs, requestedUri) {
            uri += ka.getCroppedObjectId(requestedUri) + '/';
        });
        if (uri.substr(-1) == '/') {
            uri = uri.substr(0, uri.length - 1);
        }

        new Request.JSON({url: _pathAdmin + 'admin/objects',
            noCache: 1, noErrorReporting: true,
            onComplete: function(pResponse) {
                var result, fullId, cb;

                Object.each(pResponse.data, function(item, pk) {
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
                Object.each(ka.getObjectLabelQ[objectKey], function(cbs) {
                    cbs.each(function(cb) {
                        cb.attempt(false);
                    });
                });

                ka.getObjectLabelBusy[objectKey] = false;
                ka.getObjectLabelQ[objectKey] = {};

            }}).get({url: uri, returnKeyAsRequested: 1});

    }).delay(50);
};

ka.getObjectLabelQ = {};
ka.getObjectLabelBusy = {};
ka.getObjectLabelQTimer = {};

/**
 * Returns the object label, based on a label field or label template (defined
 * in the object definition).
 *
 * @param {String} objectKey
 * @param {Object} item
 * @param {String} mode         'default', 'field' or 'tree'. Default is 'default'
 * @param {Object} [overwriteDefinition] overwrite definitions stored in the objectKey
 *
 * @return {String}
 */
ka.getObjectLabelByItem = function(objectKey, item, mode, overwriteDefinition) {

    var definition = ka.getObjectDefinition(objectKey);
    if (!definition) {
        throw 'Definition not found ' + objectKey;
    }

    var template = definition.treeTemplate ? definition.treeTemplate : definition.labelTemplate;
    var label = definition.treeLabel ? definition.treeLabel : definition.labelField;

    if (overwriteDefinition) {
        ['fieldTemplate', 'fieldLabel', 'treeTemplate', 'treeLabel'].each(function(map) {
            if (typeOf(overwriteDefinition[map]) !== 'null') {
                definition[map] = overwriteDefinition[map];
            }
        });
    }

    /* field ui */
    if (mode == 'field' && definition.fieldTemplate) {
        template = definition.fieldTemplate;
    }

    if (mode == 'field' && definition.fieldLabel) {
        label = definition.fieldLabel;
    }

    /* tree */
    if (mode == 'tree' && definition.treeTemplate) {
        template = definition.treeTemplate;
    }

    if (mode == 'tree' && definition.treeLabel) {
        label = definition.treeLabel;
    }

    if (!template) {
        //we only have an label field, so return it
        return mowla.fetch('{label}', {label: item[label]});
    }

    return mowla.fetch(template, item);
};

/**
 * Returns all labels for a object item.
 *
 * @param {Object}  fields  The array of fields definition, that defines /how/ you want to show the data. limited range of 'type' usage.
 * @param {Object}  item
 * @param {String}  objectKey
 * @param {Boolean} [relationsAsArray] Relations would be returned as arrays/origin or as string(default).
 *
 * @return {Object}
 */
ka.getObjectLabels = function(fields, item, objectKey, relationsAsArray) {

    var data = item, dataKey;
    Object.each(fields, function(field, fieldId) {
        dataKey = fieldId;
        if (relationsAsArray && dataKey.indexOf('.') > 0) {
            dataKey = dataKey.split('.')[0];
        }

        data[dataKey] = ka.getObjectFieldLabel(item, field, fieldId, objectKey, relationsAsArray);
    }.bind(this));

    return data;
};

/**
 * Returns a single label for a field of a object item.
 *
 * @param {Object} value
 * @param {Object} field The array of fields definition, that defines /how/ you want to show the data. limited range of 'type' usage.
 * @param {String} fieldId
 * @param {String} objectKey
 * @param {Boolean} [relationsAsArray]
 *
 * @return {String} Safe HTML. Escaped with ka.htmlEntities()
 */
ka.getObjectFieldLabel = function(value, field, fieldId, objectKey, relationsAsArray) {

    var oriFields = ka.getObjectDefinition(objectKey);
    if (!oriFields) {
        throw 'Object not found ' + objectKey;
    }

    var oriFieldId = fieldId;
    if (typeOf(fieldId) == 'string' && fieldId.indexOf('.') > 0) {
        oriFieldId = fieldId.split('.')[0];
    }

    oriFields = oriFields['fields'];
    var oriField = oriFields[oriFieldId];

    var showAsField = Object.clone(field || oriField);
    if (!showAsField.type) {
        Object.each(oriField, function(v, i) {
            if (!showAsField[i]) {
                showAsField[i] = v;
            }
        });
    }

    value = Object.clone(value);

    if (showAsField.type == 'predefined') {
        if (ka.getObjectDefinition(showAsField.object)) {
            showAsField = ka.getObjectDefinition(showAsField.object).fields[showAsField.field];
        }
    }

    showAsField.type = showAsField.type || 'text';
    if (oriField) {
        oriField.type = oriField.type || 'text';
    }

    var clazz = showAsField.type.ucfirst();
    if (!ka.LabelTypes[clazz]) {
        clazz = 'Text';
    }

    if (relationsAsArray) {
        showAsField.options = showAsField.options || {};
        showAsField.options.relationsAsArray = true;
    }

    var labelType = new ka.LabelTypes[clazz](oriField, showAsField, fieldId, objectKey);

    return labelType.render(value);
};

/**
 * Returns the module title of the given module key.
 *
 * @param {String} key
 *
 * @return {String|null} Null if the module does not exist/its not activated.
 */
ka.getBundleTitle = function(key) {
    var config = ka.getConfig(key);
    if (!config) {
        return key;
    }

    return config.label || config.name;
};

/**
 *
 * @param {Number} bytes
 * @returns {String}
 */
ka.bytesToSize = function(bytes) {
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
ka.dateTime = function(seconds) {
    var date = new Date(seconds * 1000);
    var nowSeconds = new Date().getTime();
    var diffForThisWeek = 3600 * 24 * 7;

    var format = '%d. %B %Y, %H:%M';
    if (nowSeconds - date < diffForThisWeek) {
        //include full day name if date is within current week.
        format = '%a., ' + format;
    }

    return date.format(format);
};

/**
 * Returns a domain object.
 *
 * @param {Number} id
 * @returns {Object}
 */
ka.getDomain = function(id) {
    var result = null;
    ka.settings.domains.each(function(domain) {
        if (domain.id == id) {
            result = domain;
        }
    });
    return result;
};

/**
 * Loads all settings from the backend.
 *
 * @param {Array} keyLimitation
 * @param {Function} callback
 */
ka.loadSettings = function(keyLimitation, callback) {
    ka.adminInterface.loadSettings(keyLimitation, callback);
};

/**
 * Returns the bundle configuration array.
 *
 * @param {String} bundleName
 * @returns {Object}
 */
ka.getConfig = function(bundleName) {
    if (!bundleName) return;
    return ka.settings.configs[bundleName] || ka.settings.configs[bundleName.toLowerCase()] || ka.settings.configsAlias[bundleName] || ka.settings.configsAlias[bundleName.toLowerCase()];
};

/**
 * Returns the short bundleName.
 *
 * @param {String} bundleName
 *
 * @returns {string}
 */
ka.getShortBundleName = function(bundleName) {
    return ka.getBundleName(bundleName).toLowerCase().replace(/bundle$/, '');
};

/**
 * Returns the bundle name.
 *
 * Kryn\CmsBundle\KrynCmsBundle => KrynCmsBundle
 *
 * @param {String} bundleClass
 * @return {String} returns only the base bundle name
 */
ka.getBundleName = function(bundleClass) {
	var split = bundleClass.split('\\');
	return split[split.length -1];
};

/**
 * Loads the main menu.
 */
ka.loadMenu = function() {
    ka.adminInterface.loadMenu();
};

/**
 * Sets the current language and reloads all messages.
 *
 * @param {String} languageCode
 */
ka.loadLanguage = function(languageCode) {
    if (!languageCode) {
        languageCode = 'en';
    }
    window._session.lang = languageCode;

    Cookie.write('kryn_language', languageCode);

    Asset.javascript(_pathAdmin + 'admin/ui/language-plural?lang=' + languageCode);

    new Request.JSON({url: _pathAdmin + 'admin/ui/language?lang=' + languageCode, async: false, noCache: 1, onComplete: function(pResponse) {
        ka.lang = pResponse.data;
        Locale.define('en-US', 'Date', ka.lang);
    }}).get();
};

/**
 * Register a new stream and starts probably the stream process.
 *
 * @param {String}   path
 * @param {Function} callback
 */
ka.registerStream = function(path, callback) {
    if (!ka.streamRegistered[path]) {
        ka.streamRegistered[path] = [];
    }
    ka.streamRegistered[path].push(callback);
    ka.loadStream();
};

ka.streamRegistered = {};

/**
 * Register a callback to a stream path. If no stream is remaining the stream process is stopped.
 *
 * @param {String}   path
 * @param {Function} callback
 */
ka.deRegisterStream = function(path, callback) {
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
};

/**
 * The stream loader loop.
 */
ka.loadStream = function() {
    if (ka._lastStreamId) {
        clearTimeout(ka._lastStreamId);
    }

    ka.streamParams.streams = [];
    Object.each(ka.streamRegistered, function(cbs, path) {
        if (0 !== cbs.length) {
            ka.streamParams.streams.push(path);
        }
    });

    if (0 === ka.streamParams.streams.length) {
        return;
    }

    ka._lastStreamId = (function() {
        if (window._session.userId > 0) {
            new Request.JSON({url: _pathAdmin + 'admin/stream', noCache: 1, onComplete: function(res) {
                if (res) {
                    if (res.error) {
                        ka.newBubble(t('Stream error'), res.error + ': ' + res.message);
                    } else {
                        window.fireEvent('stream', res.data);
                        Object.each(ka.streamRegistered, function(cbs, path) {
                            Array.each(cbs, function(cb) {
                                cb(res.data[path], res.data);
                            });
                        });
                    }
                }
                ka._lastStreamId = ka.loadStream.delay(2 * 1000);
            }}).get(ka.streamParams);
        }
    }).delay(50);
};

/**
 * Returns the current value in the clipboard of the interface (not browser)
 *
 * @returns {Object} {type: {String}, value: {Mixed}}
 */
ka.getClipboard = function() {
    return ka.clipboard;
};

/**
 * Sets the current clipboard of the interface (not browser)
 *
 * @param {String} title
 * @param {String} type
 * @param {Mixed}  value
 */
ka.setClipboard = function(title, type, value) {
    ka.clipboard = { type: type, value: value };
    window.fireEvent('clipboard');
};

/**
 * Checks if current clipboard has the given type.
 *
 * @param {string} type
 *
 * @returns {Boolean}
 */
ka.isClipboard = function(type) {
    return ka.getClipboard() && type === ka.getClipboard().type;
};

/**
 * Clears the clipboard.
 */
ka.clearClipboard = function() {
    ka.clipboard = {};
};

ka.closeDialogsBodys = [];

/**
 * Closed current dialog.
 */
ka.closeDialog = function() {

    var killedOne = false;
    Array.each(ka.closeDialogsBodys, function(body) {
        if (killedOne) {
            return;
        }

        var last = document.body.getLast('.ka-dialog-overlay');
        if (last) {
            killedOne = true;
            last.close();
        }
    });
};

/**
 * Positions options.element near options.target with settings of options.primary or options.secondary.
 *
 * @param {Object} options {element: {Element}, target: {Element}, primary: {Object}, secondary: {Object}}
 *
 * @returns {Element}
 */
ka.openDialog = function(options) {
    if (!options.element || !options.element.getParent) {
        throw 'Got no element.';
    }

    var target = document.body;

    if (options.target && options.target.getWindow()) {
        target = options.target.getWindow().document.body;
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
    }).addEvent('click',function(e) {
            ka.closeDialog();
            e.stopPropagation();
            this.fireEvent('close');
            if (options.onClose) {
                options.onClose();
            }
        }).inject(target);

    autoPositionLastOverlay.close = function() {
        if (autoPositionLastOverlay) {
            autoPositionLastOverlay.destroy();
            autoPositionLastOverlay = null;
        }
    };

    options.element.setStyle('z-index', 201001);

    var size = options.target.getWindow().getScrollSize();

    autoPositionLastOverlay.setStyles({
        width: size.x,
        height: size.y
    });

    ka.autoPositionLastItem = options.element;

    options.element.inject(target);

    if (!options.offset) {
        options.offset = {};
    }

    if (!options.primary) {
        options.primary = {
            'position': 'bottomRight',
            'edge': 'upperRight',
            offset: options.offset
        }
    }

    if (!options.secondary) {
        options.secondary = {
            'position': 'upperRight',
            'edge': 'bottomRight',
            offset: options.offset
        }
    }

    var updatePosition = function() {
        options.primary.relativeTo = options.target;
        options.secondary.relativeTo = options.target;

        options.element.position(options.primary);

        var pos = options.element.getPosition();
        var size = options.element.getSize();

        var bsize = options.element.getParent().getSize();
        var bscroll = options.element.getParent().getScroll();
        var height;

        options.element.setStyle('height', '');

        options.minHeight = options.element.getSize().y;

        if (size.y + pos.y > bsize.y + bscroll.y) {
            height = bsize.y - pos.y - 10;
        }

        if (height) {
            if (options.minHeight && height < options.minHeight) {
                var currentTop = options.element.getStyle('top').toInt();
                var offsetY = (options.offset ? options.offset.y : 0) || 0;
                options.element.setStyle('top', currentTop - options.element.getSize().y - options.target.getSize().y + 1 + (offsetY * -1));
                //item.element.position(item.secondary);
            } else {
                options.element.setStyle('height', height);
            }
        }
    };

    updatePosition();
    autoPositionLastOverlay.updatePosition = updatePosition;

    return autoPositionLastOverlay;
};

/**
 * Returns the object definition as object.
 *
 * @param {String} objectKey
 *
 * @returns {Object}
 */
ka.getObjectDefinition = function(objectKey) {
    if (typeOf(objectKey) != 'string') {
        throw 'objectKey is not a string: ' + objectKey;
    }

    objectKey = ka.normalizeObjectKey(objectKey);

    var module = ("" + objectKey.split('/')[0]).toLowerCase();
    var name = objectKey.split('/')[1].toLowerCase();

    if (ka.getConfig(module) && ka.getConfig(module)['objects'][name]) {
        var config = ka.getConfig(module)['objects'][name];
        config._key = objectKey;
        return config;
    }
};

/**
 * Returns the default caching definition for ka.Fields.
 *
 * @returns {Object}
 */
ka.getFieldCaching = function() {
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
};

/**
 * Quotes string to be used in a regEx.
 *
 * @param string
 *
 * @returns {String}
 */
ka.pregQuote = function(string) {
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

    return (string + '').replace(/([\\\.\+\*\?\[\^\]\$\(\)\{\}\=\!\<\>\|\:])/g, "\\$1");
};

/**
 * Generates little noise at element background.
 *
 * @param {Element} element
 * @param {Number} opacity
 */
ka.generateNoise = function(element, opacity) {
    if (!"getContent" in document.createElement('canvas')) {
        return false;
    }

    var canvas = document.createElement("canvas")
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
};
