/*
YUI 3.8.0 (build 5744)
Copyright 2012 Yahoo! Inc. All rights reserved.
Licensed under the BSD License.
http://yuilibrary.com/license/
*/
if (typeof _yuitest_coverage == "undefined"){
    _yuitest_coverage = {};
    _yuitest_coverline = function(src, line){
        var coverage = _yuitest_coverage[src];
        if (!coverage.lines[line]){
            coverage.calledLines++;
        }
        coverage.lines[line]++;
    };
    _yuitest_coverfunc = function(src, name, line){
        var coverage = _yuitest_coverage[src],
            funcId = name + ":" + line;
        if (!coverage.functions[funcId]){
            coverage.calledFunctions++;
        }
        coverage.functions[funcId]++;
    };
}
_yuitest_coverage["build/dataschema-text/dataschema-text.js"] = {
    lines: {},
    functions: {},
    coveredLines: 0,
    calledLines: 0,
    coveredFunctions: 0,
    calledFunctions: 0,
    path: "build/dataschema-text/dataschema-text.js",
    code: []
};
_yuitest_coverage["build/dataschema-text/dataschema-text.js"].code=["YUI.add('dataschema-text', function (Y, NAME) {","","/**"," * Provides a DataSchema implementation which can be used to work with"," * delimited text data."," *"," * @module dataschema"," * @submodule dataschema-text"," */","","/**","Provides a DataSchema implementation which can be used to work with","delimited text data.","","See the `apply` method for usage.","","@class DataSchema.Text","@extends DataSchema.Base","@static","**/","","var Lang = Y.Lang,","    isString = Lang.isString,","    isUndef  = Lang.isUndefined,","","    SchemaText = {","","        ////////////////////////////////////////////////////////////////////////","        //","        // DataSchema.Text static methods","        //","        ////////////////////////////////////////////////////////////////////////","        /**","        Applies a schema to a string of delimited data, returning a normalized","        object with results in the `results` property. The `meta` property of","        the response object is present for consistency, but is assigned an","        empty object.  If the input data is absent or not a string, an `error`","        property will be added.","","        Use _schema.resultDelimiter_ and _schema.fieldDelimiter_ to instruct","        `apply` how to split up the string into an array of data arrays for","        processing.","","        Use _schema.resultFields_ to specify the keys in the generated result","        objects in `response.results`. The key:value pairs will be assigned","        in the order of the _schema.resultFields_ array, assuming the values","        in the data records are defined in the same order.","","        _schema.resultFields_ field identifiers are objects with the following","        properties:","","          * `key`   : <strong>(required)</strong> The property name you want","                the data value assigned to in the result object (String)","          * `parser`: A function or the name of a function on `Y.Parsers` used","                to convert the input value into a normalized type.  Parser","                functions are passed the value as input and are expected to","                return a value.","","        If no value parsing is needed, you can use just the desired property","        name string as the field identifier instead of an object (see example","        below).","","        @example","            // Process simple csv","            var schema = {","                    resultDelimiter: \"\\n\",","                    fieldDelimiter: \",\",","                    resultFields: [ 'fruit', 'color' ]","                },","                data = \"Banana,yellow\\nOrange,orange\\nEggplant,purple\";","","            var response = Y.DataSchema.Text.apply(schema, data);","","            // response.results[0] is { fruit: \"Banana\", color: \"yellow\" }","","","            // Use parsers","            schema.resultFields = [","                {","                    key: 'fruit',","                    parser: function (val) { return val.toUpperCase(); }","                },","                'color' // mix and match objects and strings","            ];","","            response = Y.DataSchema.Text.apply(schema, data);","","            // response.results[0] is { fruit: \"BANANA\", color: \"yellow\" }","         ","        @method apply","        @param {Object} schema Schema to apply.  Supported configuration","            properties are:","          @param {String} schema.resultDelimiter Character or character","              sequence that marks the end of one record and the start of","              another.","          @param {String} [schema.fieldDelimiter] Character or character","              sequence that marks the end of a field and the start of","              another within the same record.","          @param {Array} [schema.resultFields] Field identifiers to","              assign values in the response records. See above for details.","        @param {String} data Text data.","        @return {Object} An Object with properties `results` and `meta`","        @static","        **/","        apply: function(schema, data) {","            var data_in = data,","                data_out = { results: [], meta: {} };","","            if (isString(data) && schema && isString(schema.resultDelimiter)) {","                // Parse results data","                data_out = SchemaText._parseResults.call(this, schema, data_in, data_out);","            } else {","                data_out.error = new Error(\"Text schema parse failure\");","            }","","            return data_out;","        },","","        /**","         * Schema-parsed list of results from full data","         *","         * @method _parseResults","         * @param schema {Array} Schema to parse against.","         * @param text_in {String} Text to parse.","         * @param data_out {Object} In-progress parsed data to update.","         * @return {Object} Parsed data object.","         * @static","         * @protected","         */","        _parseResults: function(schema, text_in, data_out) {","            var resultDelim = schema.resultDelimiter,","                fieldDelim  = isString(schema.fieldDelimiter) &&","                                schema.fieldDelimiter,","                fields      = schema.resultFields || [],","                results     = [],","                parse       = Y.DataSchema.Base.parse,","                results_in, fields_in, result, item,","                field, key, value, i, j;","","            // Delete final delimiter at end of string if there","            if (text_in.slice(-resultDelim.length) === resultDelim) {","                text_in = text_in.slice(0, -resultDelim.length);","            }","","            // Split into results","            results_in = text_in.split(schema.resultDelimiter);","","            if (fieldDelim) {","                for (i = results_in.length - 1; i >= 0; --i) {","                    result = {};","                    item = results_in[i];","","                    fields_in = item.split(schema.fieldDelimiter);","","                    for (j = fields.length - 1; j >= 0; --j) {","                        field = fields[j];","                        key = (!isUndef(field.key)) ? field.key : field;","                        // FIXME: unless the key is an array index, this test","                        // for fields_in[key] is useless.","                        value = (!isUndef(fields_in[key])) ?","                                    fields_in[key] :","                                    fields_in[j];","","                        result[key] = parse.call(this, value, field);","                    }","","                    results[i] = result;","                }","            } else {","                results = results_in;","            }","","            data_out.results = results;","","            return data_out;","        }","    };","","Y.DataSchema.Text = Y.mix(SchemaText, Y.DataSchema.Base);","","","}, '3.8.0', {\"requires\": [\"dataschema-base\"]});"];
_yuitest_coverage["build/dataschema-text/dataschema-text.js"].lines = {"1":0,"22":0,"106":0,"109":0,"111":0,"113":0,"116":0,"131":0,"141":0,"142":0,"146":0,"148":0,"149":0,"150":0,"151":0,"153":0,"155":0,"156":0,"157":0,"160":0,"164":0,"167":0,"170":0,"173":0,"175":0,"179":0};
_yuitest_coverage["build/dataschema-text/dataschema-text.js"].functions = {"apply:105":0,"_parseResults:130":0,"(anonymous 1):1":0};
_yuitest_coverage["build/dataschema-text/dataschema-text.js"].coveredLines = 26;
_yuitest_coverage["build/dataschema-text/dataschema-text.js"].coveredFunctions = 3;
_yuitest_coverline("build/dataschema-text/dataschema-text.js", 1);
YUI.add('dataschema-text', function (Y, NAME) {

/**
 * Provides a DataSchema implementation which can be used to work with
 * delimited text data.
 *
 * @module dataschema
 * @submodule dataschema-text
 */

/**
Provides a DataSchema implementation which can be used to work with
delimited text data.

See the `apply` method for usage.

@class DataSchema.Text
@extends DataSchema.Base
@static
**/

_yuitest_coverfunc("build/dataschema-text/dataschema-text.js", "(anonymous 1)", 1);
_yuitest_coverline("build/dataschema-text/dataschema-text.js", 22);
var Lang = Y.Lang,
    isString = Lang.isString,
    isUndef  = Lang.isUndefined,

    SchemaText = {

        ////////////////////////////////////////////////////////////////////////
        //
        // DataSchema.Text static methods
        //
        ////////////////////////////////////////////////////////////////////////
        /**
        Applies a schema to a string of delimited data, returning a normalized
        object with results in the `results` property. The `meta` property of
        the response object is present for consistency, but is assigned an
        empty object.  If the input data is absent or not a string, an `error`
        property will be added.

        Use _schema.resultDelimiter_ and _schema.fieldDelimiter_ to instruct
        `apply` how to split up the string into an array of data arrays for
        processing.

        Use _schema.resultFields_ to specify the keys in the generated result
        objects in `response.results`. The key:value pairs will be assigned
        in the order of the _schema.resultFields_ array, assuming the values
        in the data records are defined in the same order.

        _schema.resultFields_ field identifiers are objects with the following
        properties:

          * `key`   : <strong>(required)</strong> The property name you want
                the data value assigned to in the result object (String)
          * `parser`: A function or the name of a function on `Y.Parsers` used
                to convert the input value into a normalized type.  Parser
                functions are passed the value as input and are expected to
                return a value.

        If no value parsing is needed, you can use just the desired property
        name string as the field identifier instead of an object (see example
        below).

        @example
            // Process simple csv
            var schema = {
                    resultDelimiter: "\n",
                    fieldDelimiter: ",",
                    resultFields: [ 'fruit', 'color' ]
                },
                data = "Banana,yellow\nOrange,orange\nEggplant,purple";

            var response = Y.DataSchema.Text.apply(schema, data);

            // response.results[0] is { fruit: "Banana", color: "yellow" }


            // Use parsers
            schema.resultFields = [
                {
                    key: 'fruit',
                    parser: function (val) { return val.toUpperCase(); }
                },
                'color' // mix and match objects and strings
            ];

            response = Y.DataSchema.Text.apply(schema, data);

            // response.results[0] is { fruit: "BANANA", color: "yellow" }
         
        @method apply
        @param {Object} schema Schema to apply.  Supported configuration
            properties are:
          @param {String} schema.resultDelimiter Character or character
              sequence that marks the end of one record and the start of
              another.
          @param {String} [schema.fieldDelimiter] Character or character
              sequence that marks the end of a field and the start of
              another within the same record.
          @param {Array} [schema.resultFields] Field identifiers to
              assign values in the response records. See above for details.
        @param {String} data Text data.
        @return {Object} An Object with properties `results` and `meta`
        @static
        **/
        apply: function(schema, data) {
            _yuitest_coverfunc("build/dataschema-text/dataschema-text.js", "apply", 105);
_yuitest_coverline("build/dataschema-text/dataschema-text.js", 106);
var data_in = data,
                data_out = { results: [], meta: {} };

            _yuitest_coverline("build/dataschema-text/dataschema-text.js", 109);
if (isString(data) && schema && isString(schema.resultDelimiter)) {
                // Parse results data
                _yuitest_coverline("build/dataschema-text/dataschema-text.js", 111);
data_out = SchemaText._parseResults.call(this, schema, data_in, data_out);
            } else {
                _yuitest_coverline("build/dataschema-text/dataschema-text.js", 113);
data_out.error = new Error("Text schema parse failure");
            }

            _yuitest_coverline("build/dataschema-text/dataschema-text.js", 116);
return data_out;
        },

        /**
         * Schema-parsed list of results from full data
         *
         * @method _parseResults
         * @param schema {Array} Schema to parse against.
         * @param text_in {String} Text to parse.
         * @param data_out {Object} In-progress parsed data to update.
         * @return {Object} Parsed data object.
         * @static
         * @protected
         */
        _parseResults: function(schema, text_in, data_out) {
            _yuitest_coverfunc("build/dataschema-text/dataschema-text.js", "_parseResults", 130);
_yuitest_coverline("build/dataschema-text/dataschema-text.js", 131);
var resultDelim = schema.resultDelimiter,
                fieldDelim  = isString(schema.fieldDelimiter) &&
                                schema.fieldDelimiter,
                fields      = schema.resultFields || [],
                results     = [],
                parse       = Y.DataSchema.Base.parse,
                results_in, fields_in, result, item,
                field, key, value, i, j;

            // Delete final delimiter at end of string if there
            _yuitest_coverline("build/dataschema-text/dataschema-text.js", 141);
if (text_in.slice(-resultDelim.length) === resultDelim) {
                _yuitest_coverline("build/dataschema-text/dataschema-text.js", 142);
text_in = text_in.slice(0, -resultDelim.length);
            }

            // Split into results
            _yuitest_coverline("build/dataschema-text/dataschema-text.js", 146);
results_in = text_in.split(schema.resultDelimiter);

            _yuitest_coverline("build/dataschema-text/dataschema-text.js", 148);
if (fieldDelim) {
                _yuitest_coverline("build/dataschema-text/dataschema-text.js", 149);
for (i = results_in.length - 1; i >= 0; --i) {
                    _yuitest_coverline("build/dataschema-text/dataschema-text.js", 150);
result = {};
                    _yuitest_coverline("build/dataschema-text/dataschema-text.js", 151);
item = results_in[i];

                    _yuitest_coverline("build/dataschema-text/dataschema-text.js", 153);
fields_in = item.split(schema.fieldDelimiter);

                    _yuitest_coverline("build/dataschema-text/dataschema-text.js", 155);
for (j = fields.length - 1; j >= 0; --j) {
                        _yuitest_coverline("build/dataschema-text/dataschema-text.js", 156);
field = fields[j];
                        _yuitest_coverline("build/dataschema-text/dataschema-text.js", 157);
key = (!isUndef(field.key)) ? field.key : field;
                        // FIXME: unless the key is an array index, this test
                        // for fields_in[key] is useless.
                        _yuitest_coverline("build/dataschema-text/dataschema-text.js", 160);
value = (!isUndef(fields_in[key])) ?
                                    fields_in[key] :
                                    fields_in[j];

                        _yuitest_coverline("build/dataschema-text/dataschema-text.js", 164);
result[key] = parse.call(this, value, field);
                    }

                    _yuitest_coverline("build/dataschema-text/dataschema-text.js", 167);
results[i] = result;
                }
            } else {
                _yuitest_coverline("build/dataschema-text/dataschema-text.js", 170);
results = results_in;
            }

            _yuitest_coverline("build/dataschema-text/dataschema-text.js", 173);
data_out.results = results;

            _yuitest_coverline("build/dataschema-text/dataschema-text.js", 175);
return data_out;
        }
    };

_yuitest_coverline("build/dataschema-text/dataschema-text.js", 179);
Y.DataSchema.Text = Y.mix(SchemaText, Y.DataSchema.Base);


}, '3.8.0', {"requires": ["dataschema-base"]});
