YUI.add('moodle-availability_wallet-form', function (Y, NAME) {

/**
 * JavaScript for form editing date conditions.
 *
 * @module moodle-availability_wallet-form
 */
M.availability_wallet = M.availability_wallet || {};

/**
 * @class M.availability_wallet.form
 * @extends M.core_availability.plugin
 */
M.availability_wallet.form = Y.Object(M.core_availability.plugin);

/**
 * Initialises this plugin.
 *
 * @method initInner
 */
M.availability_wallet.form.initInner = function() {
    // Does nothing.
};

M.availability_wallet.form.instId = 1;

M.availability_wallet.form.getNode = function(json) {
    "use strict";
    var html, root, node, id;

    id = 'cost' + M.availability_wallet.form.instId;
    M.availability_wallet.form.instId += 1;

    // Create HTML structure.
    html = '';
    html += '<label for="' + id + '">';
    html += M.util.get_string('fieldlabel', 'availability_wallet') + ' </label>';
    html += ' <input type="text" name="cost" id="' + id + '" step="0.01">';
    node = Y.Node.create('<span>' + html + '</span>');

    // Set initial values based on the value from the JSON data in Moodle
    // database. This will have values undefined if creating a new one.
    if (json.cost !== undefined) {
        node.one('input[name=cost]').set('value', json.cost);
    }

    // Add event handlers (first time only). You can do this any way you
    // like, but this pattern is used by the existing code.
    if (!M.availability_wallet.form.addedEvents) {
        M.availability_wallet.form.addedEvents = true;
        root = Y.one('.availability-field');
        root.delegate('change', function() {

            M.core_availability.form.update();
        }, '.availability_wallet input[name=cost]');
    }

    return node;
};

M.availability_wallet.form.fillValue = function(value, node) {
    "use strict";

    value.cost = parseFloat(node.one('input[name=cost]').get('value'));
};


M.availability_wallet.form.fillErrors = function(errors, node) {
    "use strict";
    var value = {};
    this.fillValue(value, node);

    if (value.cost === undefined || value.cost === '' || value.cost <= 0) {
        errors.push('availability_wallet:validnumber');
    }
};


}, '@VERSION@', {"requires": ["base", "node", "event", "moodle-core_availability-form"]});
