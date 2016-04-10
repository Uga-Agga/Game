/*
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 *
 *
 * This compound file may be composed of several subfiles by different authors.
 * The particular authors, copyright information, disclaimers and alternative
 * licenses for the subfiles are indicated in separate headers.
 *
 */
/*

Jappix - An open social platform
These are the origin JS script for Jappix

-------------------------------------------------

License: dual-licensed under AGPL and MPLv2
Author: Valérian Saliou

*/

// Bundle
var Origin = (function () {

    /**
     * Alias of this
     * @private
     */
    var self = {};


    /**
     * Checks if the URL passed has the same origin than Jappix itself
     * @public
     * @param {string} url
     * @return {undefined}
     */
    self.isSame = function(url) {

        /* Source: http://stackoverflow.com/questions/9404793/check-if-same-origin-policy-applies */

        try {
            var loc = window.location,
            a = document.createElement('a');

            a.href = url;

            return (!a.hostname || (a.hostname == loc.hostname))    &&
                   (!a.port     || (a.port == loc.port))            &&
                   (!a.protocol || (a.protocol == loc.protocol));
        } catch(e) {
            Console.error('Origin.isSame', e);
        }

    };


    /**
     * Return class scope
     */
    return self;

})();

var JappixOrigin = Origin;// jXHR.js (JSON-P XHR)
// v0.1 (c) Kyle Simpson
// License: MIT
// modified by gueron Jonathan to work with strophe lib
// for http://www.iadvize.com

(function(global){
    var SETTIMEOUT = global.setTimeout, // for better compression
        doc = global.document,
        callback_counter = 0;

    global.jXHR = function() {
        var script_url,
            script_loaded,
            jsonp_callback,
            scriptElem,
            publicAPI = null;

        function removeScript() { try { scriptElem.parentNode.removeChild(scriptElem); } catch (err) { } }

        function reset() {
            script_loaded = false;
            script_url = "";
            removeScript();
            scriptElem = null;
            fireReadyStateChange(0);
        }

        function ThrowError(msg) {
            try {
                publicAPI.onerror.call(publicAPI,msg,script_url);
            } catch (err) {
                //throw new Error(msg);
            }
        }

        function handleScriptLoad() {
            if ((this.readyState && this.readyState!=="complete" && this.readyState!=="loaded") || script_loaded) { return; }
            this.onload = this.onreadystatechange = null; // prevent memory leak
            script_loaded = true;
            if (publicAPI.readyState !== 4) ThrowError("handleScriptLoad: Script failed to load ["+script_url+"].");
            removeScript();
        }

        function parseXMLString(xmlStr) {
            var xmlDoc = null;
            if(window.DOMParser) {
                var parser = new DOMParser();
                xmlDoc = parser.parseFromString(xmlStr,"text/xml");
            }
            else {
                xmlDoc = new ActiveXObject("Microsoft.XMLDOM");
                xmlDoc.async="false";
                xmlDoc.loadXML(xmlStr);
            }
            return xmlDoc;
        }

        function fireReadyStateChange(rs,args) {

            args = args || [];
            publicAPI.readyState = rs;
            if (rs == 4) {
                publicAPI.responseText = args[0].reply;
                publicAPI.responseXML = parseXMLString(args[0].reply);
            }
            if (typeof publicAPI.onreadystatechange === "function") publicAPI.onreadystatechange.apply(publicAPI,args);
        }

        publicAPI = {
            onerror:null,
            onreadystatechange:null,
            readyState:0,
            status:200,
            responseBody: null,
            responseText: null,
            responseXML: null,
            open:function(method,url){
                reset();
                var internal_callback = "cb"+(callback_counter++);
                (function(icb){
                    global.jXHR[icb] = function() {
                        try { fireReadyStateChange.call(publicAPI,4,arguments); }
                        catch(err) {
                            publicAPI.readyState = -1;
                            ThrowError("Script failed to run ["+script_url+"].");
                        }
                        global.jXHR[icb] = null;
                    };
                })(internal_callback);
                script_url = url + '?callback=?jXHR&data=';
                script_url = script_url.replace(/=\?jXHR/,"=jXHR."+internal_callback);
                fireReadyStateChange(1);
            },
            send:function(data){
                script_url = script_url + encodeURIComponent(data);
                SETTIMEOUT(function(){
                    scriptElem = doc.createElement("script");
                    scriptElem.setAttribute("type","text/javascript");
                    scriptElem.onload = scriptElem.onreadystatechange = function(){handleScriptLoad.call(scriptElem);};
                    scriptElem.setAttribute("src",script_url);
                    doc.getElementsByTagName("head")[0].appendChild(scriptElem);
                },0);
                fireReadyStateChange(2);
            },
            abort:function(){},
            setRequestHeader:function(){}, // noop
            getResponseHeader:function(){return "";}, // basically noop
            getAllResponseHeaders:function(){return [];} // ditto
        };

        reset();

        return publicAPI;
    };
})(window);
// License: PD

// This code was written by Tyler Akins and has been placed in the
// public domain.  It would be nice if you left this header intact.
// Base64 code from Tyler Akins -- http://rumkin.com

var Base64 = (function () {
    var keyStr = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";

    var obj = {
        /**
         * Encodes a string in base64
         * @param {String} input The string to encode in base64.
         */
        encode: function (input) {
            var output = "";
            var chr1, chr2, chr3;
            var enc1, enc2, enc3, enc4;
            var i = 0;

            do {
                chr1 = input.charCodeAt(i++);
                chr2 = input.charCodeAt(i++);
                chr3 = input.charCodeAt(i++);

                enc1 = chr1 >> 2;
                enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
                enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
                enc4 = chr3 & 63;

                if (isNaN(chr2)) {
                    enc3 = enc4 = 64;
                } else if (isNaN(chr3)) {
                    enc4 = 64;
                }

                output = output + keyStr.charAt(enc1) + keyStr.charAt(enc2) +
                    keyStr.charAt(enc3) + keyStr.charAt(enc4);
            } while (i < input.length);

            return output;
        },

        /**
         * Decodes a base64 string.
         * @param {String} input The string to decode.
         */
        decode: function (input) {
            var output = "";
            var chr1, chr2, chr3;
            var enc1, enc2, enc3, enc4;
            var i = 0;

            // remove all characters that are not A-Z, a-z, 0-9, +, /, or =
            input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");

            do {
                enc1 = keyStr.indexOf(input.charAt(i++));
                enc2 = keyStr.indexOf(input.charAt(i++));
                enc3 = keyStr.indexOf(input.charAt(i++));
                enc4 = keyStr.indexOf(input.charAt(i++));

                chr1 = (enc1 << 2) | (enc2 >> 4);
                chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
                chr3 = ((enc3 & 3) << 6) | enc4;

                output = output + String.fromCharCode(chr1);

                if (enc3 != 64) {
                    output = output + String.fromCharCode(chr2);
                }
                if (enc4 != 64) {
                    output = output + String.fromCharCode(chr3);
                }
            } while (i < input.length);

            return output;
        }
    };

    return obj;
})();/*

Jappix - An open social platform
This is the JSJaC library for Jappix (from trunk)

-------------------------------------------------

Licenses: Mozilla Public License version 1.1, GNU GPL, AGPL
Authors: Stefan Strigler, Valérian Saliou, Zash, Maranda

*/

/**
 * @fileoverview Magic dependency loading. Taken from script.aculo.us
 * and modified to break it.
 * @author Stefan Strigler steve@zeank.in-berlin.de
 * @version 1.3
 */

var JSJaC = {
  Version: '1.3',
  bind: function(fn, obj, optArg) {
    return function(arg) {
      return fn.apply(obj, [arg, optArg]);
    };
  }
};



/* Copyright 2006 Erik Arvidsson
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you
 * may not use this file except in compliance with the License.  You
 * may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or
 * implied.  See the License for the specific language governing
 * permissions and limitations under the License.
 */

/**
 * @fileoverview Wrapper to make working with XmlHttpRequest and the
 * DOM more convenient (cross browser compliance).
 * this code is taken from
 * http://webfx.eae.net/dhtml/xmlextras/xmlextras.html
 * @author Stefan Strigler steve@zeank.in-berlin.de
 * @version 1.3
 */

/**
 * XmlHttp factory
 * @private
 */
function XmlHttp() {}

/**
 * creates a cross browser compliant XmlHttpRequest object
 */
XmlHttp.create = function () {
  try {
    // Are we cross-domain?
    if(!BOSH_SAME_ORIGIN) {
      // Able to use CORS?
      if (window.XMLHttpRequest) {
        var req = new XMLHttpRequest();

        if (req.withCredentials !== undefined)
          return req;
      }

      // Fallback on JSONP
      return new jXHR();
    }
    // Might be local-domain?
    if (window.XMLHttpRequest) {
      var req = new XMLHttpRequest();

      // some versions of Moz do not support the readyState property
      // and the onreadystate event so we patch it!
      if (req.readyState == null) {
  req.readyState = 1;
  req.addEventListener("load", function () {
             req.readyState = 4;
             if (typeof req.onreadystatechange == "function")
         req.onreadystatechange();
           }, false);
      }

      return req;
    }
    if (window.ActiveXObject) {
      return new ActiveXObject(XmlHttp.getPrefix() + ".XmlHttp");
    }
  }
  catch (ex) {}
  // fell through
  throw new Error("Your browser does not support XmlHttp objects");
};

/**
 * used to find the Automation server name
 * @private
 */
XmlHttp.getPrefix = function() {
  if (XmlHttp.prefix) // I know what you did last summer
    return XmlHttp.prefix;

  var prefixes = ["MSXML2", "Microsoft", "MSXML", "MSXML3"];
  var o;
  for (var i = 0; i < prefixes.length; i++) {
    try {
      // try to create the objects
      o = new ActiveXObject(prefixes[i] + ".XmlHttp");
      return XmlHttp.prefix = prefixes[i];
    }
    catch (ex) {};
  }

  throw new Error("Could not find an installed XML parser");
};


/**
 * XmlDocument factory
 * @private
 */
function XmlDocument() {}

XmlDocument.create = function (name,ns) {
  name = name || 'foo';
  ns = ns || '';

  try {
    var doc;
    // DOM2
    if (document.implementation && document.implementation.createDocument) {
      doc = document.implementation.createDocument(ns, name, null);
      // some versions of Moz do not support the readyState property
      // and the onreadystate event so we patch it!
      if (doc.readyState == null) {
        doc.readyState = 1;
        doc.addEventListener("load", function () {
             doc.readyState = 4;
             if (typeof doc.onreadystatechange == "function")
               doc.onreadystatechange();
        }, false);
      }
    } else if (window.ActiveXObject) {
      doc = new ActiveXObject(XmlDocument.getPrefix() + ".DomDocument");
    }

    if (!doc.documentElement || doc.documentElement.tagName != name ||
        (doc.documentElement.namespaceURI &&
         doc.documentElement.namespaceURI != ns)) {
          try {
            if (ns != '')
              doc.appendChild(doc.createElement(name)).
                setAttribute('xmlns',ns);
            else
              doc.appendChild(doc.createElement(name));
          } catch (dex) {
            doc = document.implementation.createDocument(ns,name,null);

            if (doc.documentElement == null)
              doc.appendChild(doc.createElement(name));

             // fix buggy opera 8.5x
            if (ns != '' &&
                doc.documentElement.getAttribute('xmlns') != ns) {
              doc.documentElement.setAttribute('xmlns',ns);
            }
          }
        }

    return doc;
  }
  catch (ex) { }
  throw new Error("Your browser does not support XmlDocument objects");
};

/**
 * used to find the Automation server name
 * @private
 */
XmlDocument.getPrefix = function() {
  if (XmlDocument.prefix)
    return XmlDocument.prefix;

  var prefixes = ["MSXML2", "Microsoft", "MSXML", "MSXML3"];
  var o;
  for (var i = 0; i < prefixes.length; i++) {
    try {
      // try to create the objects
      o = new ActiveXObject(prefixes[i] + ".DomDocument");
      return XmlDocument.prefix = prefixes[i];
    }
    catch (ex) {};
  }

  throw new Error("Could not find an installed XML parser");
};


// Create the loadXML method
if (typeof(Document) != 'undefined' && window.DOMParser) {

  /**
   * XMLDocument did not extend the Document interface in some
   * versions of Mozilla.
   * @private
   */
  Document.prototype.loadXML = function (s) {

    // parse the string to a new doc
    var doc2 = (new DOMParser()).parseFromString(s, "text/xml");

    // remove all initial children
    while (this.hasChildNodes())
      this.removeChild(this.lastChild);

    // insert and import nodes
    for (var i = 0; i < doc2.childNodes.length; i++) {
      this.appendChild(this.importNode(doc2.childNodes[i], true));
    }
  };
 }

// Create xml getter for Mozilla
if (window.XMLSerializer &&
    window.Node && Node.prototype && Node.prototype.__defineGetter__) {

  /**
   * xml getter
   *
   * This serializes the DOM tree to an XML String
   *
   * Usage: var sXml = oNode.xml
   * @deprecated
   * @private
   */
  // XMLDocument did not extend the Document interface in some versions
  // of Mozilla. Extend both!
  XMLDocument.prototype.__defineGetter__("xml", function () {
                                           return (new XMLSerializer()).serializeToString(this);
                                         });
  /**
   * xml getter
   *
   * This serializes the DOM tree to an XML String
   *
   * Usage: var sXml = oNode.xml
   * @deprecated
   * @private
   */
  Document.prototype.__defineGetter__("xml", function () {
                                        return (new XMLSerializer()).serializeToString(this);
                                      });

  /**
   * xml getter
   *
   * This serializes the DOM tree to an XML String
   *
   * Usage: var sXml = oNode.xml
   * @deprecated
   * @private
   */
  Node.prototype.__defineGetter__("xml", function () {
                                    return (new XMLSerializer()).serializeToString(this);
                                  });
 }


/**
 * @fileoverview Collection of functions to make live easier
 * @author Stefan Strigler
 * @version 1.3
 */

/**
 * Convert special chars to HTML entities
 * @addon
 * @return The string with chars encoded for HTML
 * @type String
 */
String.prototype.htmlEnc = function() {
  if(!this)
    return this;

  return this.replace(/&(?!(amp|apos|gt|lt|quot);)/g,"&amp;")
             .replace(/</g,"&lt;")
             .replace(/>/g,"&gt;")
             .replace(/\'/g,"&apos;")
             .replace(/\"/g,"&quot;")
             .replace(/\n/g,"<br />");
};

/**
 * Convert HTML entities to special chars
 * @addon
 * @return The normal string
 * @type String
 */
String.prototype.revertHtmlEnc = function() {
  if(!this)
    return this;

  var str = this.replace(/&amp;/gi,'&');
  str = str.replace(/&lt;/gi,'<');
  str = str.replace(/&gt;/gi,'>');
  str = str.replace(/&apos;/gi,'\'');
  str = str.replace(/&quot;/gi,'\"');
  str = str.replace(/<br( )?(\/)?>/gi,'\n');
  return str;
};

/**
 * Converts from jabber timestamps to JavaScript Date objects
 * @addon
 * @param {String} ts A string representing a jabber datetime timestamp as
 * defined by {@link http://www.xmpp.org/extensions/xep-0082.html XEP-0082}
 * @return A javascript Date object corresponding to the jabber DateTime given
 * @type Date
 */
Date.jab2date = function(ts) {
  // Timestamp
  if(!isNaN(ts))
    return new Date(ts * 1000);

  // Get the UTC date
  var date = new Date(Date.UTC(ts.substr(0,4),ts.substr(5,2)-1,ts.substr(8,2),ts.substr(11,2),ts.substr(14,2),ts.substr(17,2)));

  if (ts.substr(ts.length-6,1) != 'Z') { // there's an offset
    var date_offset = date.getTimezoneOffset() * 60 * 1000;
    var offset = new Date();
    offset.setTime(0);
    offset.setUTCHours(ts.substr(ts.length-5,2));
    offset.setUTCMinutes(ts.substr(ts.length-2,2));
    if (ts.substr(ts.length-6,1) == '+')
      date.setTime(date.getTime() + offset.getTime() + date_offset);
    else if (ts.substr(ts.length-6,1) == '-')
      date.setTime(date.getTime() - offset.getTime() + date_offset);
  }
  return date;
};

/**
 * Takes a timestamp in the form of 2004-08-13T12:07:04+02:00 as argument
 * and converts it to some sort of humane readable format
 * @addon
 */
Date.hrTime = function(ts) {
  return Date.jab2date(ts).toLocaleString();
};


 /**
  * Current timestamp.
  * @return Seconds since 1.1.1970.
  * @type int
  */
 if (!Date.now) {
     Date.now = function() { return new Date().getTime(); }
 }

 /**
 * somewhat opposit to {@link #hrTime}
 * expects a javascript Date object as parameter and returns a jabber
 * date string conforming to
 * {@link http://www.xmpp.org/extensions/xep-0082.html XEP-0082}
 * @see #hrTime
 * @return The corresponding jabber DateTime string
 * @type String
 */
Date.prototype.jabberDate = function() {
  var padZero = function(i) {
    if (i < 10) return "0" + i;
    return i;
  };

  var jDate = this.getUTCFullYear() + "-";
  jDate += padZero(this.getUTCMonth()+1) + "-";
  jDate += padZero(this.getUTCDate()) + "T";
  jDate += padZero(this.getUTCHours()) + ":";
  jDate += padZero(this.getUTCMinutes()) + ":";
  jDate += padZero(this.getUTCSeconds()) + "Z";

  return jDate;
};

/**
 * Determines the maximum of two given numbers
 * @addon
 * @param {Number} A a number
 * @param {Number} B another number
 * @return the maximum of A and B
 * @type Number
 */
Number.max = function(A, B) {
  return (A > B)? A : B;
};

Number.min = function(A, B) {
  return (A < B)? A : B;
};


/* Copyright (c) 1998 - 2007, Paul Johnston & Contributors
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * Redistributions of source code must retain the above copyright
 * notice, this list of conditions and the following
 * disclaimer. Redistributions in binary form must reproduce the above
 * copyright notice, this list of conditions and the following
 * disclaimer in the documentation and/or other materials provided
 * with the distribution.
 *
 * Neither the name of the author nor the names of its contributors
 * may be used to endorse or promote products derived from this
 * software without specific prior written permission.
 *
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION)
 * HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT,
 * STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED
 * OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 */

/**
 * @fileoverview Collection of MD5 and SHA1 hashing and encoding
 * methods.
 * @author Stefan Strigler steve@zeank.in-berlin.de
 */


/*
 * A JavaScript implementation of the Secure Hash Algorithm, SHA-1, as defined
 * in FIPS PUB 180-1
 * Version 2.1a Copyright Paul Johnston 2000 - 2002.
 * Other contributors: Greg Holt, Andrew Kepert, Ydnar, Lostinet
 * Distributed under the BSD License
 * See http://pajhome.org.uk/crypt/md5 for details.
 */

/*
 * Configurable variables. You may need to tweak these to be compatible with
 * the server-side, but the defaults work in most cases.
 */
var hexcase = 0;  /* hex output format. 0 - lowercase; 1 - uppercase        */
var b64pad  = "="; /* base-64 pad character. "=" for strict RFC compliance   */
var chrsz   = 8;  /* bits per input character. 8 - ASCII; 16 - Unicode      */

/*
 * These are the functions you'll usually want to call
 * They take string arguments and return either hex or base-64 encoded strings
 */
function hex_sha1(s){return binb2hex(core_sha1(str2binb(s),s.length * chrsz));}
function b64_sha1(s){return binb2b64(core_sha1(str2binb(s),s.length * chrsz));}
function str_sha1(s){return binb2str(core_sha1(str2binb(s),s.length * chrsz));}
function hex_hmac_sha1(key, data){ return binb2hex(core_hmac_sha1(key, data));}
function b64_hmac_sha1(key, data){ return binb2b64(core_hmac_sha1(key, data));}
function str_hmac_sha1(key, data){ return binb2str(core_hmac_sha1(key, data));}

/*
 * Perform a simple self-test to see if the VM is working
 */
function sha1_vm_test()
{
  return hex_sha1("abc") == "a9993e364706816aba3e25717850c26c9cd0d89d";
}

/*
 * Calculate the SHA-1 of an array of big-endian words, and a bit length
 */
function core_sha1(x, len)
{
  /* append padding */
  x[len >> 5] |= 0x80 << (24 - len % 32);
  x[((len + 64 >> 9) << 4) + 15] = len;

  var w = new Array(80);
  var a =  1732584193;
  var b = -271733879;
  var c = -1732584194;
  var d =  271733878;
  var e = -1009589776;

  var i, j, t, olda, oldb, oldc, oldd, olde;
  for (i = 0; i < x.length; i += 16)
  {
    olda = a;
    oldb = b;
    oldc = c;
    oldd = d;
    olde = e;

    for (j = 0; j < 80; j++)
    {
      if (j < 16) { w[j] = x[i + j]; }
      else { w[j] = rol(w[j-3] ^ w[j-8] ^ w[j-14] ^ w[j-16], 1); }
      t = safe_add(safe_add(rol(a, 5), sha1_ft(j, b, c, d)),
                       safe_add(safe_add(e, w[j]), sha1_kt(j)));
      e = d;
      d = c;
      c = rol(b, 30);
      b = a;
      a = t;
    }

    a = safe_add(a, olda);
    b = safe_add(b, oldb);
    c = safe_add(c, oldc);
    d = safe_add(d, oldd);
    e = safe_add(e, olde);
  }
  return [a, b, c, d, e];
}

/*
 * Perform the appropriate triplet combination function for the current
 * iteration
 */
function sha1_ft(t, b, c, d)
{
  if (t < 20) { return (b & c) | ((~b) & d); }
  if (t < 40) { return b ^ c ^ d; }
  if (t < 60) { return (b & c) | (b & d) | (c & d); }
  return b ^ c ^ d;
}

/*
 * Determine the appropriate additive constant for the current iteration
 */
function sha1_kt(t)
{
  return (t < 20) ?  1518500249 : (t < 40) ?  1859775393 :
         (t < 60) ? -1894007588 : -899497514;
}

/*
 * Calculate the HMAC-SHA1 of a key and some data
 */
function core_hmac_sha1(key, data)
{
  var bkey = str2binb(key);
  if (bkey.length > 16) { bkey = core_sha1(bkey, key.length * chrsz); }

  var ipad = new Array(16), opad = new Array(16);
  for (var i = 0; i < 16; i++)
  {
    ipad[i] = bkey[i] ^ 0x36363636;
    opad[i] = bkey[i] ^ 0x5C5C5C5C;
  }

  var hash = core_sha1(ipad.concat(str2binb(data)), 512 + data.length * chrsz);
  return core_sha1(opad.concat(hash), 512 + 160);
}

/*
 * Add integers, wrapping at 2^32. This uses 16-bit operations internally
 * to work around bugs in some JS interpreters.
 */
function safe_add(x, y)
{
  var lsw = (x & 0xFFFF) + (y & 0xFFFF);
  var msw = (x >> 16) + (y >> 16) + (lsw >> 16);
  return (msw << 16) | (lsw & 0xFFFF);
}

/*
 * Bitwise rotate a 32-bit number to the left.
 */
function rol(num, cnt)
{
  return (num << cnt) | (num >>> (32 - cnt));
}

/*
 * Convert an 8-bit or 16-bit string to an array of big-endian words
 * In 8-bit function, characters >255 have their hi-byte silently ignored.
 */
function str2binb(str)
{
  var bin = [];
  var mask = (1 << chrsz) - 1;
  for (var i = 0; i < str.length * chrsz; i += chrsz)
  {
    bin[i>>5] |= (str.charCodeAt(i / chrsz) & mask) << (32 - chrsz - i%32);
  }
  return bin;
}

/*
 * Convert an array of big-endian words to a string
 */
function binb2str(bin)
{
  var str = "";
  var mask = (1 << chrsz) - 1;
  for (var i = 0; i < bin.length * 32; i += chrsz)
  {
    str += String.fromCharCode((bin[i>>5] >>> (32 - chrsz - i%32)) & mask);
  }
  return str;
}

/*
 * Convert an array of big-endian words to a hex string.
 */
function binb2hex(binarray)
{
  var hex_tab = hexcase ? "0123456789ABCDEF" : "0123456789abcdef";
  var str = "";
  for (var i = 0; i < binarray.length * 4; i++)
  {
    str += hex_tab.charAt((binarray[i>>2] >> ((3 - i%4)*8+4)) & 0xF) +
           hex_tab.charAt((binarray[i>>2] >> ((3 - i%4)*8  )) & 0xF);
  }
  return str;
}

/*
 * Convert an array of big-endian words to a base-64 string
 */
function binb2b64(binarray)
{
  var tab = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
  var str = "";
  var triplet, j;
  for (var i = 0; i < binarray.length * 4; i += 3)
  {
    triplet = (((binarray[i   >> 2] >> 8 * (3 -  i   %4)) & 0xFF) << 16) |
              (((binarray[i+1 >> 2] >> 8 * (3 - (i+1)%4)) & 0xFF) << 8 ) |
               ((binarray[i+2 >> 2] >> 8 * (3 - (i+2)%4)) & 0xFF);
    for (j = 0; j < 4; j++)
    {
      if (i * 8 + j * 6 > binarray.length * 32) { str += b64pad; }
      else { str += tab.charAt((triplet >> 6*(3-j)) & 0x3F); }
    }
  }
  return str;
}


/*
 * A JavaScript implementation of the RSA Data Security, Inc. MD5 Message
 * Digest Algorithm, as defined in RFC 1321.
 * Version 2.2 Copyright (C) Paul Johnston 1999 - 2009
 * Other contributors: Greg Holt, Andrew Kepert, Ydnar, Lostinet
 * Distributed under the BSD License
 * See http://pajhome.org.uk/crypt/md5 for more info.
 */

/*
 * These are the functions you'll usually want to call
 * They take string arguments and return either hex or base-64 encoded strings
 */
function hex_md5(s)    { return rstr2hex(rstr_md5(str2rstr_utf8(s))); }
function b64_md5(s)    { return rstr2b64(rstr_md5(str2rstr_utf8(s))); }
function any_md5(s, e) { return rstr2any(rstr_md5(str2rstr_utf8(s)), e); }
function hex_hmac_md5(k, d)
  { return rstr2hex(rstr_hmac_md5(str2rstr_utf8(k), str2rstr_utf8(d))); }
function b64_hmac_md5(k, d)
  { return rstr2b64(rstr_hmac_md5(str2rstr_utf8(k), str2rstr_utf8(d))); }
function any_hmac_md5(k, d, e)
  { return rstr2any(rstr_hmac_md5(str2rstr_utf8(k), str2rstr_utf8(d)), e); }

/*
 * Perform a simple self-test to see if the VM is working
 */
function md5_vm_test()
{
  return hex_md5("abc").toLowerCase() == "900150983cd24fb0d6963f7d28e17f72";
}

/*
 * Calculate the MD5 of a raw string
 */
function rstr_md5(s)
{
  return binl2rstr(binl_md5(rstr2binl(s), s.length * 8));
}

/*
 * Calculate the HMAC-MD5, of a key and some data (raw strings)
 */
function rstr_hmac_md5(key, data)
{
  var bkey = rstr2binl(key);
  if(bkey.length > 16) bkey = binl_md5(bkey, key.length * 8);

  var ipad = Array(16), opad = Array(16);
  for(var i = 0; i < 16; i++)
  {
    ipad[i] = bkey[i] ^ 0x36363636;
    opad[i] = bkey[i] ^ 0x5C5C5C5C;
  }

  var hash = binl_md5(ipad.concat(rstr2binl(data)), 512 + data.length * 8);
  return binl2rstr(binl_md5(opad.concat(hash), 512 + 128));
}

/*
 * Convert a raw string to a hex string
 */
function rstr2hex(input)
{
  try { hexcase } catch(e) { hexcase=0; }
  var hex_tab = hexcase ? "0123456789ABCDEF" : "0123456789abcdef";
  var output = "";
  var x;
  for(var i = 0; i < input.length; i++)
  {
    x = input.charCodeAt(i);
    output += hex_tab.charAt((x >>> 4) & 0x0F)
           +  hex_tab.charAt( x        & 0x0F);
  }
  return output;
}

/*
 * Convert a raw string to a base-64 string
 */
function rstr2b64(input)
{
  try { b64pad } catch(e) { b64pad=''; }
  var tab = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/";
  var output = "";
  var len = input.length;
  for(var i = 0; i < len; i += 3)
  {
    var triplet = (input.charCodeAt(i) << 16)
                | (i + 1 < len ? input.charCodeAt(i+1) << 8 : 0)
                | (i + 2 < len ? input.charCodeAt(i+2)      : 0);
    for(var j = 0; j < 4; j++)
    {
      if(i * 8 + j * 6 > input.length * 8) output += b64pad;
      else output += tab.charAt((triplet >>> 6*(3-j)) & 0x3F);
    }
  }
  return output;
}

/*
 * Convert a raw string to an arbitrary string encoding
 */
function rstr2any(input, encoding)
{
  var divisor = encoding.length;
  var i, j, q, x, quotient;

  /* Convert to an array of 16-bit big-endian values, forming the dividend */
  var dividend = Array(Math.ceil(input.length / 2));
  for(i = 0; i < dividend.length; i++)
  {
    dividend[i] = (input.charCodeAt(i * 2) << 8) | input.charCodeAt(i * 2 + 1);
  }

  /*
   * Repeatedly perform a long division. The binary array forms the dividend,
   * the length of the encoding is the divisor. Once computed, the quotient
   * forms the dividend for the next step. All remainders are stored for later
   * use.
   */
  var full_length = Math.ceil(input.length * 8 /
                                    (Math.log(encoding.length) / Math.log(2)));
  var remainders = Array(full_length);
  for(j = 0; j < full_length; j++)
  {
    quotient = Array();
    x = 0;
    for(i = 0; i < dividend.length; i++)
    {
      x = (x << 16) + dividend[i];
      q = Math.floor(x / divisor);
      x -= q * divisor;
      if(quotient.length > 0 || q > 0)
        quotient[quotient.length] = q;
    }
    remainders[j] = x;
    dividend = quotient;
  }

  /* Convert the remainders to the output string */
  var output = "";
  for(i = remainders.length - 1; i >= 0; i--)
    output += encoding.charAt(remainders[i]);

  return output;
}

/*
 * Encode a string as utf-8.
 * For efficiency, this assumes the input is valid utf-16.
 */
function str2rstr_utf8(input)
{
  var output = "";
  var i = -1;
  var x, y;

  while(++i < input.length)
  {
    /* Decode utf-16 surrogate pairs */
    x = input.charCodeAt(i);
    y = i + 1 < input.length ? input.charCodeAt(i + 1) : 0;
    if(0xD800 <= x && x <= 0xDBFF && 0xDC00 <= y && y <= 0xDFFF)
    {
      x = 0x10000 + ((x & 0x03FF) << 10) + (y & 0x03FF);
      i++;
    }

    /* Encode output as utf-8 */
    if(x <= 0x7F)
      output += String.fromCharCode(x);
    else if(x <= 0x7FF)
      output += String.fromCharCode(0xC0 | ((x >>> 6 ) & 0x1F),
                                    0x80 | ( x         & 0x3F));
    else if(x <= 0xFFFF)
      output += String.fromCharCode(0xE0 | ((x >>> 12) & 0x0F),
                                    0x80 | ((x >>> 6 ) & 0x3F),
                                    0x80 | ( x         & 0x3F));
    else if(x <= 0x1FFFFF)
      output += String.fromCharCode(0xF0 | ((x >>> 18) & 0x07),
                                    0x80 | ((x >>> 12) & 0x3F),
                                    0x80 | ((x >>> 6 ) & 0x3F),
                                    0x80 | ( x         & 0x3F));
  }
  return output;
}

/*
 * Encode a string as utf-16
 */
function str2rstr_utf16le(input)
{
  var output = "";
  for(var i = 0; i < input.length; i++)
    output += String.fromCharCode( input.charCodeAt(i)        & 0xFF,
                                  (input.charCodeAt(i) >>> 8) & 0xFF);
  return output;
}

function str2rstr_utf16be(input)
{
  var output = "";
  for(var i = 0; i < input.length; i++)
    output += String.fromCharCode((input.charCodeAt(i) >>> 8) & 0xFF,
                                   input.charCodeAt(i)        & 0xFF);
  return output;
}

/*
 * Convert a raw string to an array of little-endian words
 * Characters >255 have their high-byte silently ignored.
 */
function rstr2binl(input)
{
  var output = Array(input.length >> 2);
  for(var i = 0; i < output.length; i++)
    output[i] = 0;
  for(var i = 0; i < input.length * 8; i += 8)
    output[i>>5] |= (input.charCodeAt(i / 8) & 0xFF) << (i%32);
  return output;
}

/*
 * Convert an array of little-endian words to a string
 */
function binl2rstr(input)
{
  var output = "";
  for(var i = 0; i < input.length * 32; i += 8)
    output += String.fromCharCode((input[i>>5] >>> (i % 32)) & 0xFF);
  return output;
}

/*
 * Calculate the MD5 of an array of little-endian words, and a bit length.
 */
function binl_md5(x, len)
{
  /* append padding */
  x[len >> 5] |= 0x80 << ((len) % 32);
  x[(((len + 64) >>> 9) << 4) + 14] = len;

  var a =  1732584193;
  var b = -271733879;
  var c = -1732584194;
  var d =  271733878;

  for(var i = 0; i < x.length; i += 16)
  {
    var olda = a;
    var oldb = b;
    var oldc = c;
    var oldd = d;

    a = md5_ff(a, b, c, d, x[i+ 0], 7 , -680876936);
    d = md5_ff(d, a, b, c, x[i+ 1], 12, -389564586);
    c = md5_ff(c, d, a, b, x[i+ 2], 17,  606105819);
    b = md5_ff(b, c, d, a, x[i+ 3], 22, -1044525330);
    a = md5_ff(a, b, c, d, x[i+ 4], 7 , -176418897);
    d = md5_ff(d, a, b, c, x[i+ 5], 12,  1200080426);
    c = md5_ff(c, d, a, b, x[i+ 6], 17, -1473231341);
    b = md5_ff(b, c, d, a, x[i+ 7], 22, -45705983);
    a = md5_ff(a, b, c, d, x[i+ 8], 7 ,  1770035416);
    d = md5_ff(d, a, b, c, x[i+ 9], 12, -1958414417);
    c = md5_ff(c, d, a, b, x[i+10], 17, -42063);
    b = md5_ff(b, c, d, a, x[i+11], 22, -1990404162);
    a = md5_ff(a, b, c, d, x[i+12], 7 ,  1804603682);
    d = md5_ff(d, a, b, c, x[i+13], 12, -40341101);
    c = md5_ff(c, d, a, b, x[i+14], 17, -1502002290);
    b = md5_ff(b, c, d, a, x[i+15], 22,  1236535329);

    a = md5_gg(a, b, c, d, x[i+ 1], 5 , -165796510);
    d = md5_gg(d, a, b, c, x[i+ 6], 9 , -1069501632);
    c = md5_gg(c, d, a, b, x[i+11], 14,  643717713);
    b = md5_gg(b, c, d, a, x[i+ 0], 20, -373897302);
    a = md5_gg(a, b, c, d, x[i+ 5], 5 , -701558691);
    d = md5_gg(d, a, b, c, x[i+10], 9 ,  38016083);
    c = md5_gg(c, d, a, b, x[i+15], 14, -660478335);
    b = md5_gg(b, c, d, a, x[i+ 4], 20, -405537848);
    a = md5_gg(a, b, c, d, x[i+ 9], 5 ,  568446438);
    d = md5_gg(d, a, b, c, x[i+14], 9 , -1019803690);
    c = md5_gg(c, d, a, b, x[i+ 3], 14, -187363961);
    b = md5_gg(b, c, d, a, x[i+ 8], 20,  1163531501);
    a = md5_gg(a, b, c, d, x[i+13], 5 , -1444681467);
    d = md5_gg(d, a, b, c, x[i+ 2], 9 , -51403784);
    c = md5_gg(c, d, a, b, x[i+ 7], 14,  1735328473);
    b = md5_gg(b, c, d, a, x[i+12], 20, -1926607734);

    a = md5_hh(a, b, c, d, x[i+ 5], 4 , -378558);
    d = md5_hh(d, a, b, c, x[i+ 8], 11, -2022574463);
    c = md5_hh(c, d, a, b, x[i+11], 16,  1839030562);
    b = md5_hh(b, c, d, a, x[i+14], 23, -35309556);
    a = md5_hh(a, b, c, d, x[i+ 1], 4 , -1530992060);
    d = md5_hh(d, a, b, c, x[i+ 4], 11,  1272893353);
    c = md5_hh(c, d, a, b, x[i+ 7], 16, -155497632);
    b = md5_hh(b, c, d, a, x[i+10], 23, -1094730640);
    a = md5_hh(a, b, c, d, x[i+13], 4 ,  681279174);
    d = md5_hh(d, a, b, c, x[i+ 0], 11, -358537222);
    c = md5_hh(c, d, a, b, x[i+ 3], 16, -722521979);
    b = md5_hh(b, c, d, a, x[i+ 6], 23,  76029189);
    a = md5_hh(a, b, c, d, x[i+ 9], 4 , -640364487);
    d = md5_hh(d, a, b, c, x[i+12], 11, -421815835);
    c = md5_hh(c, d, a, b, x[i+15], 16,  530742520);
    b = md5_hh(b, c, d, a, x[i+ 2], 23, -995338651);

    a = md5_ii(a, b, c, d, x[i+ 0], 6 , -198630844);
    d = md5_ii(d, a, b, c, x[i+ 7], 10,  1126891415);
    c = md5_ii(c, d, a, b, x[i+14], 15, -1416354905);
    b = md5_ii(b, c, d, a, x[i+ 5], 21, -57434055);
    a = md5_ii(a, b, c, d, x[i+12], 6 ,  1700485571);
    d = md5_ii(d, a, b, c, x[i+ 3], 10, -1894986606);
    c = md5_ii(c, d, a, b, x[i+10], 15, -1051523);
    b = md5_ii(b, c, d, a, x[i+ 1], 21, -2054922799);
    a = md5_ii(a, b, c, d, x[i+ 8], 6 ,  1873313359);
    d = md5_ii(d, a, b, c, x[i+15], 10, -30611744);
    c = md5_ii(c, d, a, b, x[i+ 6], 15, -1560198380);
    b = md5_ii(b, c, d, a, x[i+13], 21,  1309151649);
    a = md5_ii(a, b, c, d, x[i+ 4], 6 , -145523070);
    d = md5_ii(d, a, b, c, x[i+11], 10, -1120210379);
    c = md5_ii(c, d, a, b, x[i+ 2], 15,  718787259);
    b = md5_ii(b, c, d, a, x[i+ 9], 21, -343485551);

    a = safe_add(a, olda);
    b = safe_add(b, oldb);
    c = safe_add(c, oldc);
    d = safe_add(d, oldd);
  }
  return Array(a, b, c, d);
}

/*
 * These functions implement the four basic operations the algorithm uses.
 */
function md5_cmn(q, a, b, x, s, t)
{
  return safe_add(bit_rol(safe_add(safe_add(a, q), safe_add(x, t)), s),b);
}
function md5_ff(a, b, c, d, x, s, t)
{
  return md5_cmn((b & c) | ((~b) & d), a, b, x, s, t);
}
function md5_gg(a, b, c, d, x, s, t)
{
  return md5_cmn((b & d) | (c & (~d)), a, b, x, s, t);
}
function md5_hh(a, b, c, d, x, s, t)
{
  return md5_cmn(b ^ c ^ d, a, b, x, s, t);
}
function md5_ii(a, b, c, d, x, s, t)
{
  return md5_cmn(c ^ (b | (~d)), a, b, x, s, t);
}

/*
 * Add integers, wrapping at 2^32. This uses 16-bit operations internally
 * to work around bugs in some JS interpreters.
 */
function safe_add(x, y)
{
  var lsw = (x & 0xFFFF) + (y & 0xFFFF);
  var msw = (x >> 16) + (y >> 16) + (lsw >> 16);
  return (msw << 16) | (lsw & 0xFFFF);
}

/*
 * Bitwise rotate a 32-bit number to the left.
 */
function bit_rol(num, cnt)
{
  return (num << cnt) | (num >>> (32 - cnt));
}


/* #############################################################################
   UTF-8 Decoder and Encoder
   base64 Encoder and Decoder
   written by Tobias Kieslich, justdreams
   Contact: tobias@justdreams.de        http://www.justdreams.de/
   ############################################################################# */

// returns an array of byterepresenting dezimal numbers which represent the
// plaintext in an UTF-8 encoded version. Expects a string.
// This function includes an exception management for those nasty browsers like
// NN401, which returns negative decimal numbers for chars>128. I hate it!!
// This handling is unfortunately limited to the user's charset. Anyway, it works
// in most of the cases! Special signs with an unicode>256 return numbers, which
// can not be converted to the actual unicode and so not to the valid utf-8
// representation. Anyway, this function does always return values which can not
// misinterpretd by RC4 or base64 en- or decoding, because every value is >0 and
// <255!!
// Arrays are faster and easier to handle in b64 encoding or encrypting....
function utf8t2d(t)
{
  t = t.replace(/\r\n/g,"\n");
  var d=new Array; var test=String.fromCharCode(237);
  if (test.charCodeAt(0) < 0)
    for(var n=0; n<t.length; n++)
      {
        var c=t.charCodeAt(n);
        if (c>0)
          d[d.length]= c;
        else {
          d[d.length]= (((256+c)>>6)|192);
          d[d.length]= (((256+c)&63)|128);}
      }
  else
    for(var n=0; n<t.length; n++)
      {
        var c=t.charCodeAt(n);
        // all the signs of asci => 1byte
        if (c<128)
          d[d.length]= c;
        // all the signs between 127 and 2047 => 2byte
        else if((c>127) && (c<2048)) {
          d[d.length]= ((c>>6)|192);
          d[d.length]= ((c&63)|128);}
        // all the signs between 2048 and 66536 => 3byte
        else {
          d[d.length]= ((c>>12)|224);
          d[d.length]= (((c>>6)&63)|128);
          d[d.length]= ((c&63)|128);}
      }
  return d;
}

// returns plaintext from an array of bytesrepresenting dezimal numbers, which
// represent an UTF-8 encoded text; browser which does not understand unicode
// like NN401 will show "?"-signs instead
// expects an array of byterepresenting decimals; returns a string
function utf8d2t(d)
{
  var r=new Array; var i=0;
  while(i<d.length)
    {
      if (d[i]<128) {
        r[r.length]= String.fromCharCode(d[i]); i++;}
      else if((d[i]>191) && (d[i]<224)) {
        r[r.length]= String.fromCharCode(((d[i]&31)<<6) | (d[i+1]&63)); i+=2;}
      else {
        r[r.length]= String.fromCharCode(((d[i]&15)<<12) | ((d[i+1]&63)<<6) | (d[i+2]&63)); i+=3;}
    }
  return r.join("");
}

// included in <body onload="b64arrays"> it creates two arrays which makes base64
// en- and decoding faster
// this speed is noticeable especially when coding larger texts (>5k or so)
function b64arrays() {
  var b64s='ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/';
  b64 = new Array();f64 =new Array();
  for (var i=0; i<b64s.length ;i++) {
    b64[i] = b64s.charAt(i);
    f64[b64s.charAt(i)] = i;
  }
}

// creates a base64 encoded text out of an array of byerepresenting dezimals
// it is really base64 :) this makes serversided handling easier
// expects an array; returns a string
function b64d2t(d) {
  var r=new Array; var i=0; var dl=d.length;
  // this is for the padding
  if ((dl%3) == 1) {
    d[d.length] = 0; d[d.length] = 0;}
  if ((dl%3) == 2)
    d[d.length] = 0;
  // from here conversion
  while (i<d.length)
    {
      r[r.length] = b64[d[i]>>2];
      r[r.length] = b64[((d[i]&3)<<4) | (d[i+1]>>4)];
      r[r.length] = b64[((d[i+1]&15)<<2) | (d[i+2]>>6)];
      r[r.length] = b64[d[i+2]&63];
      i+=3;
    }
  // this is again for the padding
  if ((dl%3) == 1)
    r[r.length-1] = r[r.length-2] = "=";
  if ((dl%3) == 2)
    r[r.length-1] = "=";
  // we join the array to return a textstring
  var t=r.join("");
  return t;
}

// returns array of byterepresenting numbers created of an base64 encoded text
// it is still the slowest function in this modul; I hope I can make it faster
// expects string; returns an array
function b64t2d(t) {
  var d=new Array; var i=0;
  // here we fix this CRLF sequenz created by MS-OS; arrrgh!!!
  t=t.replace(/\n|\r/g,""); t=t.replace(/=/g,"");
  while (i<t.length)
    {
      d[d.length] = (f64[t.charAt(i)]<<2) | (f64[t.charAt(i+1)]>>4);
      d[d.length] = (((f64[t.charAt(i+1)]&15)<<4) | (f64[t.charAt(i+2)]>>2));
      d[d.length] = (((f64[t.charAt(i+2)]&3)<<6) | (f64[t.charAt(i+3)]));
      i+=4;
    }
  if (t.length%4 == 2)
    d = d.slice(0, d.length-2);
  if (t.length%4 == 3)
    d = d.slice(0, d.length-1);
  return d;
}

if (typeof(atob) == 'undefined' || typeof(btoa) == 'undefined')
  b64arrays();

if (typeof(atob) == 'undefined') {
  b64decode = function(s) {
    return utf8d2t(b64t2d(s));
  }
} else {
  b64decode = function(s) {
    return decodeURIComponent(escape(atob(s)));
  }
}

if (typeof(btoa) == 'undefined') {
  b64encode = function(s) {
    return b64d2t(utf8t2d(s));
  }
} else {
  b64encode = function(s) {
    return btoa(unescape(encodeURIComponent(s)));
  }
}

function cnonce(size) {
  var tab = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
  var cnonce = '';
  for (var i=0; i<size; i++) {
    cnonce += tab.charAt(Math.round(Math.random(new Date().getTime())*(tab.length-1)));
  }
  return cnonce;
}


JSJAC_HAVEKEYS = true;          // whether to use keys
JSJAC_NKEYS    = 16;            // number of keys to generate

JSJAC_INACTIVITY = 300;         // qnd hack to make suspend/resume
                                    // work more smoothly with polling
JSJAC_ERR_COUNT = 10;           // number of retries in case of connection
                                    // errors

JSJAC_ALLOW_PLAIN = true;       // whether to allow plaintext logins

JSJAC_CHECKQUEUEINTERVAL = 100;   // msecs to poll send queue
JSJAC_CHECKINQUEUEINTERVAL = 100; // msecs to poll incoming queue
JSJAC_TIMERVAL = 2000;          // default polling interval

JSJAC_ALLOW_PLAIN = true;       // whether to allow plaintext logins
JSJAC_ALLOW_SCRAM = false;      // allow usage of SCRAM-SHA-1 authentication; please note that it is quite slow so it is disable by default

JSJAC_RETRYDELAY = 5000;        // msecs to wait before trying next
                                // request after error

JSJAC_REGID_TIMEOUT = 20000;    // time in msec until registered
                                // callbacks for ids timeout

/* Options specific to HTTP Binding (BOSH) */
JSJACHBC_MAX_HOLD = 1;          // default for number of connctions
                                // held by connection manager

JSJACHBC_MAX_WAIT = 20;         // default 'wait' param - how long an
                                // idle connection should be held by
                                // connection manager

JSJACHBC_BOSH_VERSION  = "1.6";
JSJACHBC_USE_BOSH_VER  = true;

JSJACHBC_MAXPAUSE = 20;        // how long a suspend/resume cycle may take

/*** END CONFIG ***/


/* Copyright (c) 2005-2007 Sam Stephenson
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use, copy,
 * modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
 * BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

/*
  json.js
  taken from prototype.js, made static
*/
function JSJaCJSON() {}
JSJaCJSON.toString = function (obj) {
  var m = {
    '\b': '\\b',
    '\t': '\\t',
    '\n': '\\n',
    '\f': '\\f',
    '\r': '\\r',
    '"' : '\\"',
    '\\': '\\\\'
  },
  s = {
    array: function (x) {
      var a = ['['], b, f, i, l = x.length, v;
      for (i = 0; i < l; i += 1) {
        v = x[i];
        f = s[typeof v];
        if (f) {
    try {
            v = f(v);
            if (typeof v == 'string') {
              if (b) {
                a[a.length] = ',';
              }
              a[a.length] = v;
              b = true;
            }
    } catch(e) {
    }
        }
      }
      a[a.length] = ']';
      return a.join('');
    },
    'boolean': function (x) {
      return String(x);
    },
    'null': function (x) {
      return "null";
    },
    number: function (x) {
      return isFinite(x) ? String(x) : 'null';
    },
    object: function (x) {
      if (x) {
        if (x instanceof Array) {
          return s.array(x);
        }
        var a = ['{'], b, f, i, v;
        for (i in x) {
          if (x.hasOwnProperty(i)) {
            v = x[i];
            f = s[typeof v];
            if (f) {
        try {
                v = f(v);
                if (typeof v == 'string') {
                  if (b) {
                    a[a.length] = ',';
                  }
                  a.push(s.string(i), ':', v);
                  b = true;
                }
        } catch(e) {
        }
            }
          }
        }

        a[a.length] = '}';
        return a.join('');
      }
      return 'null';
    },
    string: function (x) {
      if (/["\\\x00-\x1f]/.test(x)) {
                    x = x.replace(/([\x00-\x1f\\"])/g, function(a, b) {
          var c = m[b];
          if (c) {
            return c;
          }
          c = b.charCodeAt();
          return '\\u00' +
          Math.floor(c / 16).toString(16) +
          (c % 16).toString(16);
        });
  }
  return '"' + x + '"';
}
  };

switch (typeof(obj)) {
 case 'object':
   return s.object(obj);
 case 'array':
   return s.array(obj);

 }
};

JSJaCJSON.parse = function (str) {
  try {
    return !(/[^,:{}\[\]0-9.\-+Eaeflnr-u \n\r\t]/.test(
                                                       str.replace(/"(\\.|[^"\\])*"/g, ''))) &&
            eval('(' + str + ')');
    } catch (e) {
        return false;
    }
};


/**
 * @fileoverview This file contains all things that make life easier when
 * dealing with JIDs
 * @author Stefan Strigler
 * @version 1.3
 */

/**
 * list of forbidden chars for nodenames
 * @private
 */
var JSJACJID_FORBIDDEN = ['"',' ','&','\'','/',':','<','>','@'];

/**
 * Creates a new JSJaCJID object
 * @class JSJaCJID models xmpp jid objects
 * @constructor
 * @param {Object} jid jid may be either of type String or a JID represented
 * by JSON with fields 'node', 'domain' and 'resource'
 * @throws JSJaCJIDInvalidException Thrown if jid is not valid
 * @return a new JSJaCJID object
 */
function JSJaCJID(jid) {
  /**
   *@private
   */
  this._node = '';
  /**
   *@private
   */
  this._domain = '';
  /**
   *@private
   */
  this._resource = '';

  if (typeof(jid) == 'string') {
    if (jid.indexOf('@') != -1) {
        this.setNode(jid.substring(0,jid.indexOf('@')));
        jid = jid.substring(jid.indexOf('@')+1);
    }
    if (jid.indexOf('/') != -1) {
      this.setResource(jid.substring(jid.indexOf('/')+1));
      jid = jid.substring(0,jid.indexOf('/'));
    }
    this.setDomain(jid);
  } else {
    this.setNode(jid.node);
    this.setDomain(jid.domain);
    this.setResource(jid.resource);
  }
}


/**
 * Gets the node part of the jid
 * @return A string representing the node name
 * @type String
 */
JSJaCJID.prototype.getNode = function() { return this._node; };

/**
 * Gets the domain part of the jid
 * @return A string representing the domain name
 * @type String
 */
JSJaCJID.prototype.getDomain = function() { return this._domain; };

/**
 * Gets the resource part of the jid
 * @return A string representing the resource
 * @type String
 */
JSJaCJID.prototype.getResource = function() { return this._resource; };


/**
 * Sets the node part of the jid
 * @param {String} node Name of the node
 * @throws JSJaCJIDInvalidException Thrown if node name contains invalid chars
 * @return This object
 * @type JSJaCJID
 */
JSJaCJID.prototype.setNode = function(node) {
  JSJaCJID._checkNodeName(node);
  this._node = node || '';
  return this;
};

/**
 * Sets the domain part of the jid
 * @param {String} domain Name of the domain
 * @throws JSJaCJIDInvalidException Thrown if domain name contains invalid
 * chars or is empty
 * @return This object
 * @type JSJaCJID
 */
JSJaCJID.prototype.setDomain = function(domain) {
  if (!domain || domain == '')
    throw new JSJaCJIDInvalidException("domain name missing");
  // chars forbidden for a node are not allowed in domain names
  // anyway, so let's check
  JSJaCJID._checkNodeName(domain);
  this._domain = domain;
  return this;
};

/**
 * Sets the resource part of the jid
 * @param {String} resource Name of the resource
 * @return This object
 * @type JSJaCJID
 */
JSJaCJID.prototype.setResource = function(resource) {
  this._resource = resource || '';
  return this;
};

/**
 * The string representation of the full jid
 * @return A string representing the jid
 * @type String
 */
JSJaCJID.prototype.toString = function() {
  var jid = '';
  if (this.getNode() && this.getNode() != '')
    jid = this.getNode() + '@';
  jid += this.getDomain(); // we always have a domain
  if (this.getResource() && this.getResource() != "")
    jid += '/' + this.getResource();
  return jid;
};

/**
 * Removes the resource part of the jid
 * @return This object
 * @type JSJaCJID
 */
JSJaCJID.prototype.removeResource = function() {
  return this.setResource();
};

/**
 * creates a copy of this JSJaCJID object
 * @return A copy of this
 * @type JSJaCJID
 */
JSJaCJID.prototype.clone = function() {
  return new JSJaCJID(this.toString());
};

/**
 * Compares two jids if they belong to the same entity (i.e. w/o resource)
 * @param {String} jid a jid as string or JSJaCJID object
 * @return 'true' if jid is same entity as this
 * @type Boolean
 */
JSJaCJID.prototype.isEntity = function(jid) {
  if (typeof jid == 'string')
    jid = (new JSJaCJID(jid));
  jid.removeResource();
  return (this.clone().removeResource().toString() === jid.toString());
};

/**
 * Check if node name is valid
 * @private
 * @param {String} node A name for a node
 * @throws JSJaCJIDInvalidException Thrown if name for node is not allowed
 */
JSJaCJID._checkNodeName = function(nodeprep) {
    if (!nodeprep || nodeprep == '')
      return;
    for (var i=0; i< JSJACJID_FORBIDDEN.length; i++) {
      if (nodeprep.indexOf(JSJACJID_FORBIDDEN[i]) != -1) {
        throw new JSJaCJIDInvalidException("forbidden char in nodename: "+JSJACJID_FORBIDDEN[i]);
      }
    }
};

/**
 * Creates a new Exception of type JSJaCJIDInvalidException
 * @class Exception to indicate invalid values for a jid
 * @constructor
 * @param {String} message The message associated with this Exception
 */
function JSJaCJIDInvalidException(message) {
  /**
   * The exceptions associated message
   * @type String
   */
  this.message = message;
  /**
   * The name of the exception
   * @type String
   */
  this.name = "JSJaCJIDInvalidException";
}


/* Copyright (c) 2005 Thomas Fuchs (http://script.aculo.us, http://mir.aculo.us)
 *
 * Permission is hereby granted, free of charge, to any person
 * obtaining a copy of this software and associated documentation
 * files (the "Software"), to deal in the Software without
 * restriction, including without limitation the rights to use, copy,
 * modify, merge, publish, distribute, sublicense, and/or sell copies
 * of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be
 * included in all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
 * EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF
 * MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
 * NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS
 * BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN
 * ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
 * CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 */

/**
 * @private
 * This code is taken from {@link
 * http://wiki.script.aculo.us/scriptaculous/show/Builder
 * script.aculo.us' Dom Builder} and has been modified to suit our
 * needs.<br/>
 * The original parts of the code do have the following
 * copyright and license notice:<br/>
 * Copyright (c) 2005, 2006 Thomas Fuchs (http://script.aculo.us,
 * http://mir.acu lo.us) <br/>
 * script.aculo.us is freely distributable under the terms of an
 * MIT-style license.<br>
 * For details, see the script.aculo.us web site:
 * http://script.aculo.us/<br>
 */
var JSJaCBuilder = {
  /**
   * @private
   */
  buildNode: function(doc, elementName) {

    var element, ns = arguments[4];

    // attributes (or text)
    if(arguments[2])
      if(JSJaCBuilder._isStringOrNumber(arguments[2]) ||
         (arguments[2] instanceof Array)) {
        element = this._createElement(doc, elementName, ns);
        JSJaCBuilder._children(doc, element, arguments[2]);
      } else {
        ns = arguments[2]['xmlns'] || ns;
        element = this._createElement(doc, elementName, ns);
        for(attr in arguments[2]) {
          if (arguments[2].hasOwnProperty(attr) && attr != 'xmlns')
            element.setAttribute(attr, arguments[2][attr]);
        }
      }
    else
      element = this._createElement(doc, elementName, ns);
    // text, or array of children
    if(arguments[3])
      JSJaCBuilder._children(doc, element, arguments[3], ns);

    return element;
  },

  _createElement: function(doc, elementName, ns) {
    try {
      if (ns)
        return doc.createElementNS(ns, elementName);
    } catch (ex) { }

    var el = doc.createElement(elementName);

    if (ns)
      el.setAttribute("xmlns", ns);

    return el;
  },

  /**
   * @private
   */
  _text: function(doc, text) {
    return doc.createTextNode(text);
  },

  /**
   * @private
   */
  _children: function(doc, element, children, ns) {
    if(typeof children=='object') { // array can hold nodes and text
      for (var i in children) {
        if (children.hasOwnProperty(i)) {
          var e = children[i];
          if (typeof e=='object') {
            if (e instanceof Array) {
              var node = JSJaCBuilder.buildNode(doc, e[0], e[1], e[2], ns);
              element.appendChild(node);
            } else {
              element.appendChild(e);
            }
          } else {
            if(JSJaCBuilder._isStringOrNumber(e)) {
              element.appendChild(JSJaCBuilder._text(doc, e));
            }
          }
        }
      }
    } else {
      if(JSJaCBuilder._isStringOrNumber(children)) {
        element.appendChild(JSJaCBuilder._text(doc, children));
      }
    }
  },

  _attributes: function(attributes) {
    var attrs = [];
    for(attribute in attributes)
      if (attributes.hasOwnProperty(attribute))
        attrs.push(attribute +
          '="' + attributes[attribute].toString().htmlEnc() + '"');
    return attrs.join(" ");
  },

  _isStringOrNumber: function(param) {
    return(typeof param=='string' || typeof param=='number');
  }
};


/**
 * @fileoverview Contains all Jabber/XMPP packet related classes.
 * @author Stefan Strigler steve@zeank.in-berlin.de
 * @version 1.3
 */

var JSJACPACKET_USE_XMLNS = true;

/**
 * Creates a new packet with given root tag name (for internal use)
 * @class Somewhat abstract base class for all kinds of specialised packets
 * @param {String} name The root tag name of the packet
 * (i.e. one of 'message', 'iq' or 'presence')
 */
function JSJaCPacket(name) {
  /**
   * @private
   */
  this.name = name;

  if (typeof(JSJACPACKET_USE_XMLNS) != 'undefined' && JSJACPACKET_USE_XMLNS)
    /**
     * @private
     */
    this.doc = XmlDocument.create(name,'jabber:client');
  else
    /**
     * @private
     */
    this.doc = XmlDocument.create(name,'');
}

/**
 * Gets the type (name of root element) of this packet, i.e. one of
 * 'presence', 'message' or 'iq'
 * @return the top level tag name
 * @type String
 */
JSJaCPacket.prototype.pType = function() { return this.name; };

/**
 * Gets the associated Document for this packet.
 * @type {@link http://www.w3.org/TR/2000/REC-DOM-Level-2-Core-20001113/core.html#i-Document Document}
 */
JSJaCPacket.prototype.getDoc = function() {
  return this.doc;
};
/**
 * Gets the root node of this packet
 * @type {@link http://www.w3.org/TR/2000/REC-DOM-Level-2-Core-20001113/core.html#ID-1950641247 Node}
 */
JSJaCPacket.prototype.getNode = function() {
  if (this.getDoc() && this.getDoc().documentElement)
    return this.getDoc().documentElement;
  else
    return null;
};

/**
 * Sets the 'to' attribute of the root node of this packet
 * @param {String} to
 * @type JSJaCPacket
 */
JSJaCPacket.prototype.setTo = function(to) {
  if (!to || to == '')
    this.getNode().removeAttribute('to');
  else if (typeof(to) == 'string')
    this.getNode().setAttribute('to',to);
  else
    this.getNode().setAttribute('to',to.toString());
  return this;
};
/**
 * Sets the 'from' attribute of the root node of this
 * packet. Usually this is not needed as the server will take care
 * of this automatically.
 * @type JSJaCPacket
 */
JSJaCPacket.prototype.setFrom = function(from) {
  if (!from || from == '')
    this.getNode().removeAttribute('from');
  else if (typeof(from) == 'string')
    this.getNode().setAttribute('from',from);
  else
    this.getNode().setAttribute('from',from.toString());
  return this;
};
/**
 * Sets 'id' attribute of the root node of this packet.
 * @param {String} id The id of the packet.
 * @type JSJaCPacket
 */
JSJaCPacket.prototype.setID = function(id) {
  if (!id || id == '')
    this.getNode().removeAttribute('id');
  else
    this.getNode().setAttribute('id',id);
  return this;
};
/**
 * Sets the 'type' attribute of the root node of this packet.
 * @param {String} type The type of the packet.
 * @type JSJaCPacket
 */
JSJaCPacket.prototype.setType = function(type) {
  if (!type || type == '')
    this.getNode().removeAttribute('type');
  else
    this.getNode().setAttribute('type',type);
  return this;
};
/**
 * Sets 'xml:lang' for this packet
 * @param {String} xmllang The xml:lang of the packet.
 * @type JSJaCPacket
 */
JSJaCPacket.prototype.setXMLLang = function(xmllang) {
  // Fix IE bug with xml:lang attribute

  // Also due to issues with both BD and jQuery being used, employ a simple regexp since the detection
  // here is very limited.
  if (navigator.appVersion.match(/^.*MSIE (\d)/) || navigator.appVersion.match(/Trident/))
    return this;
  if (!xmllang || xmllang == '')
    this.getNode().removeAttribute('xml:lang');
  else
    this.getNode().setAttribute('xml:lang',xmllang);
  return this;
};

/**
 * Gets the 'to' attribute of this packet
 * @type String
 */
JSJaCPacket.prototype.getTo = function() {
  return this.getNode().getAttribute('to');
};
/**
 * Gets the 'from' attribute of this packet.
 * @type String
 */
JSJaCPacket.prototype.getFrom = function() {
  return this.getNode().getAttribute('from');
};
/**
 * Gets the 'to' attribute of this packet as a JSJaCJID object
 * @type JSJaCJID
 */
JSJaCPacket.prototype.getToJID = function() {
  return new JSJaCJID(this.getTo());
};
/**
 * Gets the 'from' attribute of this packet as a JSJaCJID object
 * @type JSJaCJID
 */
JSJaCPacket.prototype.getFromJID = function() {
  return new JSJaCJID(this.getFrom());
};
/**
 * Gets the 'id' of this packet
 * @type String
 */
JSJaCPacket.prototype.getID = function() {
  return this.getNode().getAttribute('id');
};
/**
 * Gets the 'type' of this packet
 * @type String
 */
JSJaCPacket.prototype.getType = function() {
  return this.getNode().getAttribute('type');
};
/**
 * Gets the 'xml:lang' of this packet
 * @type String
 */
JSJaCPacket.prototype.getXMLLang = function() {
  return this.getNode().getAttribute('xml:lang');
};
/**
 * Gets the 'xmlns' (xml namespace) of the root node of this packet
 * @type String
 */
JSJaCPacket.prototype.getXMLNS = function() {
  return this.getNode().namespaceURI || this.getNode().getAttribute('xmlns');
};

/**
 * Gets a child element of this packet. If no params given returns first child.
 * @param {String} name Tagname of child to retrieve. Use '*' to match any tag. [optional]
 * @param {String} ns   Namespace of child. Use '*' to match any ns.[optional]
 * @return The child node, null if none found
 * @type {@link http://www.w3.org/TR/2000/REC-DOM-Level-2-Core-20001113/core.html#ID-1950641247 Node}
 */
JSJaCPacket.prototype.getChild = function(name, ns) {
  if (!this.getNode()) {
    return null;
  }

  name = name || '*';
  ns = ns || '*';

  if (this.getNode().getElementsByTagNameNS) {
    return this.getNode().getElementsByTagNameNS(ns, name).item(0);
  }

  // fallback
  var nodes = this.getNode().getElementsByTagName(name);
  if (ns != '*') {
    for (var i=0; i<nodes.length; i++) {
      if (nodes.item(i).namespaceURI == ns || nodes.item(i).getAttribute('xmlns') == ns) {
        return nodes.item(i);
      }
    }
  } else {
    return nodes.item(0);
  }
  return null; // nothing found
};

/**
 * Gets the node value of a child element of this packet.
 * @param {String} name Tagname of child to retrieve.
 * @param {String} ns   Namespace of child
 * @return The value of the child node, empty string if none found
 * @type String
 */
JSJaCPacket.prototype.getChildVal = function(name, ns) {
  var node = this.getChild(name, ns);
  var ret = '';
  if (node && node.hasChildNodes()) {
    // concatenate all values from childNodes
    for (var i=0; i<node.childNodes.length; i++)
      if (node.childNodes.item(i).nodeValue)
        ret += node.childNodes.item(i).nodeValue;
  }
  return ret;
};

/**
 * Returns a copy of this node
 * @return a copy of this node
 * @type JSJaCPacket
 */
JSJaCPacket.prototype.clone = function() {
  return JSJaCPacket.wrapNode(this.getNode());
};

/**
 * Checks if packet is of type 'error'
 * @return 'true' if this packet is of type 'error', 'false' otherwise
 * @type boolean
 */
JSJaCPacket.prototype.isError = function() {
  return (this.getType() == 'error');
};

/**
 * Returns an error condition reply according to {@link http://www.xmpp.org/extensions/xep-0086.html XEP-0086}. Creates a clone of the calling packet with senders and recipient exchanged and error stanza appended.
 * @param {STANZA_ERROR} stanza_error an error stanza containing error cody, type and condition of the error to be indicated
 * @return an error reply packet
 * @type JSJaCPacket
 */
JSJaCPacket.prototype.errorReply = function(stanza_error) {
  var rPacket = this.clone();
  rPacket.setTo(this.getFrom());
  rPacket.setFrom();
  rPacket.setType('error');

  rPacket.appendNode('error',
                     {code: stanza_error.code, type: stanza_error.type},
                     [[stanza_error.cond]]);

  return rPacket;
};

/**
 * Returns a string representation of the raw xml content of this packet.
 * @type String
 */
JSJaCPacket.prototype.xml = typeof XMLSerializer != 'undefined' ?
function() {
  var r = (new XMLSerializer()).serializeToString(this.getNode());
  if (typeof(r) == 'undefined')
    r = (new XMLSerializer()).serializeToString(this.doc); // oldschool
  return r
} :
function() {// IE
  return this.getDoc().xml
};


// PRIVATE METHODS DOWN HERE

/**
 * Gets an attribute of the root element
 * @private
 */
JSJaCPacket.prototype._getAttribute = function(attr) {
  return this.getNode().getAttribute(attr);
};


if (document.ELEMENT_NODE == null) {
  document.ELEMENT_NODE = 1;
  document.ATTRIBUTE_NODE = 2;
  document.TEXT_NODE = 3;
  document.CDATA_SECTION_NODE = 4;
  document.ENTITY_REFERENCE_NODE = 5;
  document.ENTITY_NODE = 6;
  document.PROCESSING_INSTRUCTION_NODE = 7;
  document.COMMENT_NODE = 8;
  document.DOCUMENT_NODE = 9;
  document.DOCUMENT_TYPE_NODE = 10;
  document.DOCUMENT_FRAGMENT_NODE = 11;
  document.NOTATION_NODE = 12;
}

/**
 * import node into this packets document
 * @private
 */
JSJaCPacket.prototype._importNode = function(node, allChildren) {
  switch (node.nodeType) {
  case document.ELEMENT_NODE:

  if (this.getDoc().createElementNS) {
    var newNode = this.getDoc().createElementNS(node.namespaceURI, node.nodeName);
  } else {
    var newNode = this.getDoc().createElement(node.nodeName);
  }

  /* does the node have any attributes to add? */
  if (node.attributes && node.attributes.length > 0)
    for (var i = 0, il = node.attributes.length;i < il; i++) {
      var attr = node.attributes.item(i);
      if (attr.nodeName == 'xmlns' && newNode.getAttribute('xmlns') != null ) continue;
      if (newNode.setAttributeNS && attr.namespaceURI) {
        newNode.setAttributeNS(attr.namespaceURI,
                               attr.nodeName,
                               attr.nodeValue);
      } else {
        newNode.setAttribute(attr.nodeName,
                             attr.nodeValue);
      }
    }
  /* are we going after children too, and does the node have any? */
  if (allChildren && node.childNodes && node.childNodes.length > 0) {
    for (var i = 0, il = node.childNodes.length; i < il; i++) {
      newNode.appendChild(this._importNode(node.childNodes.item(i), allChildren));
    }
  }
  return newNode;
  break;
  case document.TEXT_NODE:
  case document.CDATA_SECTION_NODE:
  case document.COMMENT_NODE:
  return this.getDoc().createTextNode(node.nodeValue);
  break;
  }
};

/**
 * Set node value of a child node
 * @private
 */
JSJaCPacket.prototype._setChildNode = function(nodeName, nodeValue) {
  var aNode = this.getChild(nodeName);
  var tNode = this.getDoc().createTextNode(nodeValue);
  if (aNode)
    try {
      aNode.replaceChild(tNode,aNode.firstChild);
    } catch (e) { }
  else {
    try {
      aNode = this.getDoc().createElementNS(this.getNode().namespaceURI,
                                            nodeName);
    } catch (ex) {
      aNode = this.getDoc().createElement(nodeName)
    }
    this.getNode().appendChild(aNode);
    aNode.appendChild(tNode);
  }
  return aNode;
};

/**
 * Builds a node using {@link
 * http://wiki.script.aculo.us/scriptaculous/show/Builder
 * script.aculo.us' Dom Builder} notation.
 * This code is taken from {@link
 * http://wiki.script.aculo.us/scriptaculous/show/Builder
 * script.aculo.us' Dom Builder} and has been modified to suit our
 * needs.<br/>
 * The original parts of the code do have the following copyright
 * and license notice:<br/>
 * Copyright (c) 2005, 2006 Thomas Fuchs (http://script.aculo.us,
 * http://mir.acu lo.us) <br/>
 * script.aculo.us is freely distributable under the terms of an
 * MIT-style licen se.  // For details, see the script.aculo.us web
 * site: http://script.aculo.us/<br>
 * @author Thomas Fuchs
 * @author Stefan Strigler
 * @return The newly created node
 * @type {@link http://www.w3.org/TR/2000/REC-DOM-Level-2-Core-20001113/core.html#ID-1950641247 Node}
 */
JSJaCPacket.prototype.buildNode = function(elementName) {
  return JSJaCBuilder.buildNode(this.getDoc(),
                                elementName,
                                arguments[1],
                                arguments[2]);
};

/**
 * Appends node created by buildNode to this packets parent node.
 * @param {@link http://www.w3.org/TR/2000/REC-DOM-Level-2-Core-20001113/core.html#ID-1950641247 Node} element The node to append or
 * @param {String} element A name plus an object hash with attributes (optional) plus an array of childnodes (optional)
 * @see #buildNode
 * @return This packet
 * @type JSJaCPacket
 */
JSJaCPacket.prototype.appendNode = function(element) {
  if (typeof element=='object') { // seems to be a prebuilt node
    return this.getNode().appendChild(element)
  } else { // build node
    return this.getNode().appendChild(this.buildNode(element,
                                                     arguments[1],
                                                     arguments[2],
                                                     null,
                                                     this.getNode().namespaceURI));
  }
};


/**
 * A jabber/XMPP presence packet
 * @class Models the XMPP notion of a 'presence' packet
 * @extends JSJaCPacket
 */
function JSJaCPresence() {
  /**
   * @ignore
   */
  this.base = JSJaCPacket;
  this.base('presence');
}
JSJaCPresence.prototype = new JSJaCPacket;

/**
 * Sets the status message for current status. Usually this is set
 * to some human readable string indicating what the user is
 * doing/feel like currently.
 * @param {String} status A status message
 * @return this
 * @type JSJaCPacket
 */
JSJaCPresence.prototype.setStatus = function(status) {
  this._setChildNode("status", status);
  return this;
};
/**
 * Sets the online status for this presence packet.
 * @param {String} show An XMPP complient status indicator. Must
 * be one of 'chat', 'away', 'xa', 'dnd'
 * @return this
 * @type JSJaCPacket
 */
JSJaCPresence.prototype.setShow = function(show) {
  if (show == 'chat' || show == 'away' || show == 'xa' || show == 'dnd')
    this._setChildNode("show",show);
  return this;
};
/**
 * Sets the priority of the resource bind to with this connection
 * @param {int} prio The priority to set this resource to
 * @return this
 * @type JSJaCPacket
 */
JSJaCPresence.prototype.setPriority = function(prio) {
  this._setChildNode("priority", prio);
  return this;
};
/**
 * Some combined method that allowes for setting show, status and
 * priority at once
 * @param {String} show A status message
 * @param {String} status A status indicator as defined by XMPP
 * @param {int} prio A priority for this resource
 * @return this
 * @type JSJaCPacket
 */
JSJaCPresence.prototype.setPresence = function(show,status,prio) {
  if (show)
    this.setShow(show);
  if (status)
    this.setStatus(status);
  if (prio)
    this.setPriority(prio);
  return this;
};

/**
 * Gets the status message of this presence
 * @return The (human readable) status message
 * @type String
 */
JSJaCPresence.prototype.getStatus = function() {
  return this.getChildVal('status');
};
/**
 * Gets the status of this presence.
 * Either one of 'chat', 'away', 'xa' or 'dnd' or null.
 * @return The status indicator as defined by XMPP
 * @type String
 */
JSJaCPresence.prototype.getShow = function() {
  return this.getChildVal('show');
};
/**
 * Gets the priority of this status message
 * @return A resource priority
 * @type int
 */
JSJaCPresence.prototype.getPriority = function() {
  return this.getChildVal('priority');
};


/**
 * A jabber/XMPP iq packet
 * @class Models the XMPP notion of an 'iq' packet
 * @extends JSJaCPacket
 */
function JSJaCIQ() {
  /**
   * @ignore
   */
  this.base = JSJaCPacket;
  this.base('iq');
}
JSJaCIQ.prototype = new JSJaCPacket;

/**
 * Some combined method to set 'to', 'type' and 'id' at once
 * @param {String} to the recepients JID
 * @param {String} type A XMPP compliant iq type (one of 'set', 'get', 'result' and 'error'
 * @param {String} id A packet ID
 * @return this
 * @type JSJaCIQ
 */
JSJaCIQ.prototype.setIQ = function(to,type,id) {
  if (to)
    this.setTo(to);
  if (type)
    this.setType(type);
  if (id)
    this.setID(id);
  return this;
};
/**
 * Creates a 'query' child node with given XMLNS
 * @param {String} xmlns The namespace for the 'query' node
 * @return The query node
 * @type {@link  http://www.w3.org/TR/2000/REC-DOM-Level-2-Core-20001113/core.html#ID-1950641247 Node}
 */
JSJaCIQ.prototype.setQuery = function(xmlns) {
  var query;
  try {
    query = this.getDoc().createElementNS(xmlns,'query');
  } catch (e) {
    query = this.getDoc().createElement('query');
  query.setAttribute('xmlns',xmlns);
  }
  this.getNode().appendChild(query);
  return query;
};

/**
 * Gets the 'query' node of this packet
 * @return The query node
 * @type {@link  http://www.w3.org/TR/2000/REC-DOM-Level-2-Core-20001113/core.html#ID-1950641247 Node}
 */
JSJaCIQ.prototype.getQuery = function() {
  return this.getNode().getElementsByTagName('query').item(0);
};
/**
 * Gets the XMLNS of the query node contained within this packet
 * @return The namespace of the query node
 * @type String
 */
JSJaCIQ.prototype.getQueryXMLNS = function() {
  if (this.getQuery()) {
    return this.getQuery().namespaceURI || this.getQuery().getAttribute('xmlns');
  } else {
    return null;
  }
};

/**
 * Creates an IQ reply with type set to 'result'. If given appends payload to first child if IQ. Payload maybe XML as string or a DOM element (or an array of such elements as well).
 * @param {Element} payload A payload to be appended [optional]
 * @return An IQ reply packet
 * @type JSJaCIQ
 */
JSJaCIQ.prototype.reply = function(payload) {
  var rIQ = this.clone();
  rIQ.setTo(this.getFrom());
  rIQ.setFrom();
  rIQ.setType('result');
  if (payload) {
    if (typeof payload == 'string')
      rIQ.getChild().appendChild(rIQ.getDoc().loadXML(payload));
    else if (payload.constructor == Array) {
      var node = rIQ.getChild();
      for (var i=0; i<payload.length; i++)
        if(typeof payload[i] == 'string')
          node.appendChild(rIQ.getDoc().loadXML(payload[i]));
        else if (typeof payload[i] == 'object')
          node.appendChild(payload[i]);
    }
    else if (typeof payload == 'object')
      rIQ.getChild().appendChild(payload);
  }
  return rIQ;
};

/**
 * A jabber/XMPP message packet
 * @class Models the XMPP notion of an 'message' packet
 * @extends JSJaCPacket
 */
function JSJaCMessage() {
  /**
   * @ignore
   */
  this.base = JSJaCPacket;
  this.base('message');
}
JSJaCMessage.prototype = new JSJaCPacket;

/**
 * Sets the body of the message
 * @param {String} body Your message to be sent along
 * @return this message
 * @type JSJaCMessage
 */
JSJaCMessage.prototype.setBody = function(body) {
  this._setChildNode("body",body);
  return this;
};
/**
 * Sets the subject of the message
 * @param {String} subject Your subject to be sent along
 * @return this message
 * @type JSJaCMessage
 */
JSJaCMessage.prototype.setSubject = function(subject) {
  this._setChildNode("subject",subject);
  return this;
};
/**
 * Sets the 'tread' attribute for this message. This is used to identify
 * threads in chat conversations
 * @param {String} thread Usually a somewhat random hash.
 * @return this message
 * @type JSJaCMessage
 */
JSJaCMessage.prototype.setThread = function(thread) {
  this._setChildNode("thread", thread);
  return this;
};
/**
 * Sets the 'nick' attribute for this message.
 * This is sometime sused to detect the sender nickname when he's not in the roster
 * @param {String} nickname
 * @return this message
 * @type JSJaCMessage
 */
JSJaCMessage.prototype.setNick = function(nick) {
  var aNode = this.getChild("nick");
  var tNode = this.getDoc().createTextNode(nick);
  if (aNode)
    try {
      aNode.replaceChild(tNode,aNode.firstChild);
    } catch (e) { }
  else {
    try {
      aNode = this.getDoc().createElementNS('http://jabber.org/protocol/nick',
                                            "nick");
    } catch (ex) {
      aNode = this.getDoc().createElement("nick")
    }
    this.getNode().appendChild(aNode);
    aNode.appendChild(tNode);
  }
  return this;
};
/**
 * Gets the 'thread' identifier for this message
 * @return A thread identifier
 * @type String
 */
JSJaCMessage.prototype.getThread = function() {
  return this.getChildVal('thread');
};
/**
 * Gets the body of this message
 * @return The body of this message
 * @type String
 */
JSJaCMessage.prototype.getBody = function() {
  return this.getChildVal('body');
};
/**
 * Gets the subject of this message
 * @return The subject of this message
 * @type String
 */
JSJaCMessage.prototype.getSubject = function() {
  return this.getChildVal('subject')
};
/**
 * Gets the nickname of this message
 * @return The nickname of this message
 * @type String
 */
JSJaCMessage.prototype.getNick = function() {
  return this.getChildVal('nick');
};


/**
 * Tries to transform a w3c DOM node to JSJaC's internal representation
 * (JSJaCPacket type, one of JSJaCPresence, JSJaCMessage, JSJaCIQ)
 * @param: {Node
 * http://www.w3.org/TR/2000/REC-DOM-Level-2-Core-20001113/core.html#ID-1950641247}
 * node The node to be transformed
 * @return A JSJaCPacket representing the given node. If node's root
 * elemenent is not one of 'message', 'presence' or 'iq',
 * <code>null</code> is being returned.
 * @type JSJaCPacket
 */
JSJaCPacket.wrapNode = function(node) {
  var oPacket = null;

  switch (node.nodeName.toLowerCase()) {
  case 'presence':
      oPacket = new JSJaCPresence();
      break;
  case 'message':
      oPacket = new JSJaCMessage();
      break;
  case 'iq':
      oPacket = new JSJaCIQ();
      break;
  }

  if (oPacket) {
    oPacket.getDoc().replaceChild(oPacket._importNode(node, true),
                                  oPacket.getNode());
  }

  return oPacket;
};



/**
 * an error packet for internal use
 * @private
 * @constructor
 */
function JSJaCError(code,type,condition) {
  var xmldoc = XmlDocument.create("error","jsjac");

  xmldoc.documentElement.setAttribute('code',code);
  xmldoc.documentElement.setAttribute('type',type);
  if (condition)
    xmldoc.documentElement.appendChild(xmldoc.createElement(condition)).
      setAttribute('xmlns','urn:ietf:params:xml:ns:xmpp-stanzas');
  return xmldoc.documentElement;
}



/**
 * Creates a new set of hash keys
 * @class Reflects a set of sha1/md5 hash keys for securing sessions
 * @constructor
 * @param {Function} func The hash function to be used for creating the keys
 * @param {Debugger} oDbg Reference to debugger implementation [optional]
 */
function JSJaCKeys(func,oDbg) {
  var seed = Math.random();

  /**
   * @private
   */
  this._k = new Array();
  this._k[0] = seed.toString();
  if (oDbg)
    /**
     * Reference to Debugger
     * @type Debugger
     */
    this.oDbg = oDbg;
  else {
    this.oDbg = {};
    this.oDbg.log = function() {};
  }

  if (func) {
    for (var i=1; i<JSJAC_NKEYS; i++) {
      this._k[i] = func(this._k[i-1]);
      oDbg.log(i+": "+this._k[i],4);
    }
  }

  /**
   * @private
   */
  this._indexAt = JSJAC_NKEYS-1;
  /**
   * Gets next key from stack
   * @return New hash key
   * @type String
   */
  this.getKey = function() {
    return this._k[this._indexAt--];
  };
  /**
   * Indicates whether there's only one key left
   * @return <code>true</code> if there's only one key left, false otherwise
   * @type boolean
   */
  this.lastKey = function() { return (this._indexAt == 0); };
  /**
   * Returns number of overall/initial stack size
   * @return Number of keys created
   * @type int
   */
  this.size = function() { return this._k.length; };

  /**
   * @private
   */
  this._getSuspendVars = function() {
    return ('_k,_indexAt').split(',');
  }
}


/**
 * @fileoverview Contains all things in common for all subtypes of connections
 * supported.
 * @author Stefan Strigler steve@zeank.in-berlin.de
 * @version 1.3
 */

/**
 * Creates a new Jabber connection (a connection to a jabber server)
 * @class Somewhat abstract base class for jabber connections. Contains all
 * of the code in common for all jabber connections
 * @constructor
 * @param {JSON http://www.json.org/index} oArg JSON with properties: <br>
 * * <code>httpbase</code> the http base address of the service to be used for
 * connecting to jabber<br>
 * * <code>oDbg</code> (optional) a reference to a debugger interface
 */
function JSJaCConnection(oArg) {

  if (oArg && oArg.oDbg && oArg.oDbg.log) {
      /**
       * Reference to debugger interface
       * (needs to implement method <code>log</code>)
       * @type Debugger
       */
    this.oDbg = oArg.oDbg;
  } else {
    this.oDbg = new Object(); // always initialise a debugger
    this.oDbg.log = function() { };
  }

  if (oArg && oArg.timerval)
    this.setPollInterval(oArg.timerval);
  else
    this.setPollInterval(JSJAC_TIMERVAL);

  if (oArg && oArg.httpbase)
      /**
       * @private
       */
    this._httpbase = oArg.httpbase;

  if (oArg &&oArg.allow_plain)
      /**
       * @private
       */
    this.allow_plain = oArg.allow_plain;
  else
    this.allow_plain = JSJAC_ALLOW_PLAIN;

  if (oArg && oArg.cookie_prefix)
      /**
       * @private
       */
    this._cookie_prefix = oArg.cookie_prefix;
  else
    this._cookie_prefix = "";

  /**
   * @private
   */
  this._connected = false;
  /**
   * @private
   */
  this._events = new Array();
  /**
   * @private
   */
  this._keys = null;
  /**
   * @private
   */
  this._ID = 0;
  /**
   * @private
   */
  this._inQ = new Array();
  /**
   * @private
   */
  this._pQueue = new Array();
  /**
   * @private
   */
  this._regIDs = new Array();
  /**
   * @private
   */
  this._req = new Array();
  /**
   * @private
   */
  this._status = 'intialized';
  /**
   * @private
   */
  this._errcnt = 0;
  /**
   * @private
   */
  this._inactivity = JSJAC_INACTIVITY;
  /**
   * @private
   */
  this._sendRawCallbacks = new Array();
}

// Generates an ID
var STANZA_ID = 1;

function genID() {
  return STANZA_ID++;
}

JSJaCConnection.prototype.connect = function(oArg) {
    this._setStatus('connecting');

    this.domain = oArg.domain || 'localhost';
    this.username = oArg.username;
    this.resource = oArg.resource;
    this.pass = oArg.pass;
    this.register = oArg.register;

    this.authhost = oArg.authhost || this.domain;
    this.authtype = oArg.authtype || 'sasl';

    if (oArg.xmllang && oArg.xmllang != '')
        this._xmllang = oArg.xmllang;
    else
        this._xmllang = 'en';

    this.host = oArg.host || this.domain;
    this.port = oArg.port || 5222;
    if (oArg.secure)
        this.secure = 'true';
    else
        this.secure = 'false';

    if (oArg.wait)
        this._wait = oArg.wait;

    this.jid = this.username + '@' + this.domain;
    this.fulljid = this.jid + '/' + this.resource;

    this._rid  = Math.round( 100000.5 + ( ( (900000.49999) - (100000.5) ) * Math.random() ) );

    // setupRequest must be done after rid is created but before first use in reqstr
    var slot = this._getFreeSlot();
    this._req[slot] = this._setupRequest(true);

    var reqstr = this._getInitialRequestString();

    this.oDbg.log(reqstr,4);

    this._req[slot].r.onreadystatechange =
        JSJaC.bind(function() {
            var r = this._req[slot].r;
            if (r.readyState == 4) {
                this.oDbg.log("async recv: "+r.responseText,4);
                this._handleInitialResponse(r); // handle response
            }
        }, this);

    if (typeof(this._req[slot].r.onerror) != 'undefined') {
        this._req[slot].r.onerror =
            JSJaC.bind(function(e) {
                this.oDbg.log('XmlHttpRequest error',1);
                return false;
            }, this);
    }

    this._req[slot].r.send(reqstr);
};

/**
 * Tells whether this connection is connected
 * @return <code>true</code> if this connections is connected,
 * <code>false</code> otherwise
 * @type boolean
 */
JSJaCConnection.prototype.connected = function() { return this._connected; };

/**
 * Disconnects from jabber server and terminates session (if applicable)
 */
JSJaCConnection.prototype.disconnect = function() {
  this._setStatus('disconnecting');

  if (!this.connected())
    return;
  this._connected = false;

  clearInterval(this._interval);
  clearInterval(this._inQto);

  if (this._timeout)
    clearTimeout(this._timeout); // remove timer

  var slot = this._getFreeSlot();
  // Intentionally synchronous
  this._req[slot] = this._setupRequest(false);

  request = this._getRequestString(false, true);

  this.oDbg.log("Disconnecting: " + request,4);
  this._req[slot].r.send(request);

  try {
    DataStore.removeDB(MINI_HASH, 'jsjac', 'state');
  } catch (e) {}

  this.oDbg.log("Disconnected: "+this._req[slot].r.responseText,2);
  this._handleEvent('ondisconnect');
};

/**
 * Gets current value of polling interval
 * @return Polling interval in milliseconds
 * @type int
 */
JSJaCConnection.prototype.getPollInterval = function() {
  return this._timerval;
};

/**
 * Registers an event handler (callback) for this connection.

 * <p>Note: All of the packet handlers for specific packets (like
 * message_in, presence_in and iq_in) fire only if there's no
 * callback associated with the id.<br>

 * <p>Example:<br/>
 * <code>con.registerHandler('iq', 'query', 'jabber:iq:version', handleIqVersion);</code>


 * @param {String} event One of

 * <ul>
 * <li>onConnect - connection has been established and authenticated</li>
 * <li>onDisconnect - connection has been disconnected</li>
 * <li>onResume - connection has been resumed</li>

 * <li>onStatusChanged - connection status has changed, current
 * status as being passed argument to handler. See {@link #status}.</li>

 * <li>onError - an error has occured, error node is supplied as
 * argument, like this:<br><code>&lt;error code='404' type='cancel'&gt;<br>
 * &lt;item-not-found xmlns='urn:ietf:params:xml:ns:xmpp-stanzas'/&gt;<br>
 * &lt;/error&gt;</code></li>

 * <li>packet_in - a packet has been received (argument: the
 * packet)</li>

 * <li>packet_out - a packet is to be sent(argument: the
 * packet)</li>

 * <li>message_in | message - a message has been received (argument:
 * the packet)</li>

 * <li>message_out - a message packet is to be sent (argument: the
 * packet)</li>

 * <li>presence_in | presence - a presence has been received
 * (argument: the packet)</li>

 * <li>presence_out - a presence packet is to be sent (argument: the
 * packet)</li>

 * <li>iq_in | iq - an iq has been received (argument: the packet)</li>
 * <li>iq_out - an iq is to be sent (argument: the packet)</li>
 * </ul>

 * @param {String} childName A childnode's name that must occur within a
 * retrieved packet [optional]

 * @param {String} childNS A childnode's namespace that must occure within
 * a retrieved packet (works only if childName is given) [optional]

 * @param {String} type The type of the packet to handle (works only if childName and chidNS are given (both may be set to '*' in order to get skipped) [optional]

 * @param {Function} handler The handler to be called when event occurs. If your handler returns 'true' it cancels bubbling of the event. No other registered handlers for this event will be fired.
 */
JSJaCConnection.prototype.registerHandler = function(event) {
  event = event.toLowerCase(); // don't be case-sensitive here
  var eArg = {handler: arguments[arguments.length-1],
              childName: '*',
              childNS: '*',
              type: '*'};
  if (arguments.length > 2)
    eArg.childName = arguments[1];
  if (arguments.length > 3)
    eArg.childNS = arguments[2];
  if (arguments.length > 4)
    eArg.type = arguments[3];
  if (!this._events[event])
    this._events[event] = new Array(eArg);
  else
    this._events[event] = this._events[event].concat(eArg);

  // sort events in order how specific they match criterias thus using
  // wildcard patterns puts them back in queue when it comes to
  // bubbling the event
  this._events[event] =
  this._events[event].sort(function(a,b) {
    var aRank = 0;
    var bRank = 0;
    with (a) {
      if (type == '*')
        aRank++;
      if (childNS == '*')
        aRank++;
      if (childName == '*')
        aRank++;
    }
    with (b) {
      if (type == '*')
        bRank++;
      if (childNS == '*')
        bRank++;
      if (childName == '*')
        bRank++;
    }
    if (aRank > bRank)
      return 1;
    if (aRank < bRank)
      return -1;
    return 0;
  });
  this.oDbg.log("registered handler for event '"+event+"'",2);
};

JSJaCConnection.prototype.unregisterHandler = function(event,handler) {
  event = event.toLowerCase(); // don't be case-sensitive here

  if (!this._events[event])
    return;

  var arr = this._events[event], res = new Array();
  for (var i=0; i<arr.length; i++)
    if (arr[i].handler != handler)
      res.push(arr[i]);

  if (arr.length != res.length) {
    this._events[event] = res;
    this.oDbg.log("unregistered handler for event '"+event+"'",2);
  }
};

/**
 * Register for iq packets of type 'get'.
 * @param {String} childName A childnode's name that must occur within a
 * retrieved packet

 * @param {String} childNS A childnode's namespace that must occure within
 * a retrieved packet (works only if childName is given)

 * @param {Function} handler The handler to be called when event occurs. If your handler returns 'true' it cancels bubbling of the event. No other registered handlers for this event will be fired.
 */
JSJaCConnection.prototype.registerIQGet = function(childName, childNS, handler) {
  this.registerHandler('iq', childName, childNS, 'get', handler);
};

/**
 * Register for iq packets of type 'set'.
 * @param {String} childName A childnode's name that must occur within a
 * retrieved packet

 * @param {String} childNS A childnode's namespace that must occure within
 * a retrieved packet (works only if childName is given)

 * @param {Function} handler The handler to be called when event occurs. If your handler returns 'true' it cancels bubbling of the event. No other registered handlers for this event will be fired.
 */
JSJaCConnection.prototype.registerIQSet = function(childName, childNS, handler) {
  this.registerHandler('iq', childName, childNS, 'set', handler);
};

/**
 * Resumes this connection from saved state (cookie)
 * @return Whether resume was successful
 * @type boolean
 */
JSJaCConnection.prototype.resume = function() {
  try {
    var json = DataStore.getDB(MINI_HASH, 'jsjac', 'state');
    this.oDbg.log('read cookie: '+json,2);
    DataStore.removeDB(MINI_HASH, 'jsjac', 'state');

    return this.resumeFromData(JSJaCJSON.parse(json));
  } catch (e) {}
  return false; // sth went wrong
};

/**
 * Resumes BOSH connection from data
 * @param {Object} serialized jsjac state information
 * @return Whether resume was successful
 * @type boolean
 */
JSJaCConnection.prototype.resumeFromData = function(data) {
  try {
    this._setStatus('resuming');

    for (var i in data)
      if (data.hasOwnProperty(i))
        this[i] = data[i];

    // copy keys - not being very generic here :-/
    if (this._keys) {
      this._keys2 = new JSJaCKeys();
      var u = this._keys2._getSuspendVars();
      for (var i=0; i<u.length; i++)
        this._keys2[u[i]] = this._keys[u[i]];
      this._keys = this._keys2;
    }

    if (this._connected) {
      // don't poll too fast!
      this._handleEvent('onresume');
      setTimeout(JSJaC.bind(this._resume, this),this.getPollInterval());
      this._interval = setInterval(JSJaC.bind(this._checkQueue, this),
           JSJAC_CHECKQUEUEINTERVAL);
      this._inQto = setInterval(JSJaC.bind(this._checkInQ, this),
        JSJAC_CHECKINQUEUEINTERVAL);
    }

    return (this._connected === true);
  } catch (e) {
    if (e.message)
      this.oDbg.log("Resume failed: "+e.message, 1);
    else
      this.oDbg.log("Resume failed: "+e, 1);
    return false;
  }
};

/**
 * Sends a JSJaCPacket
 * @param {JSJaCPacket} packet  The packet to send
 * @param {Function}    cb      The callback to be called if there's a reply
 * to this packet (identified by id) [optional]
 * @param {Object}      arg     Arguments passed to the callback
 * (additionally to the packet received) [optional]
 * @return 'true' if sending was successfull, 'false' otherwise
 * @type boolean
 */
JSJaCConnection.prototype.send = function(packet,cb,arg) {
  if (!packet || !packet.pType) {
    this.oDbg.log("no packet: "+packet, 1);
    return false;
  }

  if (!this.connected())
    return false;

  // generate an ID for the packet
  if (!packet.getID())
    packet.setID(genID());

  // packet xml:lang
  if (!packet.getXMLLang())
    packet.setXMLLang(XML_LANG);

  // remember id for response if callback present
  if (cb)
    this._registerPID(packet, cb, arg);

  try {
    this._handleEvent(packet.pType()+'_out', packet);
    this._handleEvent("packet_out", packet);
    this._pQueue = this._pQueue.concat(packet.xml());
  } catch (e) {
    this.oDbg.log(e.toString(),1);
    return false;
  }

  return true;
};

/**
 * Sends an IQ packet. Has default handlers for each reply type.
 * Those maybe overriden by passing an appropriate handler.
 * @param {JSJaCIQPacket} iq - the iq packet to send
 * @param {Object} handlers - object with properties 'error_handler',
 *                            'result_handler' and 'default_handler'
 *                            with appropriate functions
 * @param {Object} arg - argument to handlers
 * @return 'true' if sending was successfull, 'false' otherwise
 * @type boolean
 */
JSJaCConnection.prototype.sendIQ = function(iq, handlers, arg) {
  if (!iq || iq.pType() != 'iq') {
    return false;
  }

  handlers = handlers || {};
    var error_handler = handlers.error_handler || JSJaC.bind(function(aIq) {
        this.oDbg.log(aIq.xml(), 1);
    }, this);

    var result_handler = handlers.result_handler ||  JSJaC.bind(function(aIq) {
        this.oDbg.log(aIq.xml(), 2);
    }, this);

  var iqHandler = function(aIq, arg) {
    switch (aIq.getType()) {
      case 'error':
      error_handler(aIq);
      break;
      case 'result':
      result_handler(aIq, arg);
      break;
    }
  };
  return this.send(iq, iqHandler, arg);
};

/**
 * Sets polling interval for this connection
 * @param {int} timerval Milliseconds to set timer to
 * @return effective interval this connection has been set to
 * @type int
 */
JSJaCConnection.prototype.setPollInterval = function(timerval) {
  if (timerval && !isNaN(timerval))
    this._timerval = timerval;
  return this._timerval;
};

/**
 * Returns current status of this connection
 * @return String to denote current state. One of
 * <ul>
 * <li>'initializing' ... well
 * <li>'connecting' if connect() was called
 * <li>'resuming' if resume() was called
 * <li>'processing' if it's about to operate as normal
 * <li>'onerror_fallback' if there was an error with the request object
 * <li>'protoerror_fallback' if there was an error at the http binding protocol flow (most likely that's where you interested in)
 * <li>'internal_server_error' in case of an internal server error
 * <li>'suspending' if suspend() is being called
 * <li>'aborted' if abort() was called
 * <li>'disconnecting' if disconnect() has been called
 * </ul>
 * @type String
 */
JSJaCConnection.prototype.status = function() { return this._status; };

/**
 * Suspends this connection (saving state for later resume)
 * Saves state to cookie
 * @return Whether suspend (saving to cookie) was successful
 * @type boolean
 */
JSJaCConnection.prototype.suspend = function(has_pause) {
  var data = this.suspendToData(has_pause);

  try {
    var c = DataStore.setDB(MINI_HASH, 'jsjac', 'state', JSJaCJSON.toString(data));
    return c;
  } catch (e) {
    this.oDbg.log("Failed creating cookie '"+this._cookie_prefix+
                  "JSJaC_State': "+e.message,1);
  }
  return false;
};

/**
 * Suspend connection and return serialized JSJaC connection state
 * @return JSJaC connection state object
 * @type Object
 */
JSJaCConnection.prototype.suspendToData = function(has_pause) {

  // remove timers
  if(has_pause) {
    clearTimeout(this._timeout);
    clearInterval(this._interval);
    clearInterval(this._inQto);

    this._suspend();
  }

  var u = ('_connected,_keys,_ID,_inQ,_pQueue,_regIDs,_errcnt,_inactivity,domain,username,resource,jid,fulljid,_sid,_httpbase,_timerval,_is_polling').split(',');
  u = u.concat(this._getSuspendVars());
  var s = new Object();

  for (var i=0; i<u.length; i++) {
    if (!this[u[i]]) continue; // hu? skip these!
    if (this[u[i]]._getSuspendVars) {
      var uo = this[u[i]]._getSuspendVars();
      var o = new Object();
      for (var j=0; j<uo.length; j++)
        o[uo[j]] = this[u[i]][uo[j]];
    } else
      var o = this[u[i]];

    s[u[i]] = o;
  }

  if(has_pause) {
    this._connected = false;
    this._setStatus('suspending');
  }

  return s;
};

/**
 * @private
 */
JSJaCConnection.prototype._abort = function() {
  clearTimeout(this._timeout); // remove timer

  clearInterval(this._inQto);
  clearInterval(this._interval);

  this._connected = false;

  this._setStatus('aborted');

  this.oDbg.log("Disconnected.",1);
  this._handleEvent('ondisconnect');
  this._handleEvent('onerror',
                    JSJaCError('500','cancel','service-unavailable'));
};

/**
 * @private
 */
JSJaCConnection.prototype._checkInQ = function() {
  for (var i=0; i<this._inQ.length && i<10; i++) {
    var item = this._inQ[0];
    this._inQ = this._inQ.slice(1,this._inQ.length);
    var packet = JSJaCPacket.wrapNode(item);

    if (!packet)
      return;

    this._handleEvent("packet_in", packet);

    if (packet.pType && !this._handlePID(packet)) {
      this._handleEvent(packet.pType()+'_in',packet);
      this._handleEvent(packet.pType(),packet);
    }
  }
};

/**
 * @private
 */
JSJaCConnection.prototype._checkQueue = function() {
  if (this._pQueue.length != 0)
    this._process();
  return true;
};

/**
 * @private
 */
JSJaCConnection.prototype._doAuth = function() {
  if (this.has_sasl && this.authtype == 'nonsasl')
    this.oDbg.log("Warning: SASL present but not used", 1);

  if (!this._doSASLAuth() &&
      !this._doLegacyAuth()) {
    this.oDbg.log("Auth failed for authtype "+this.authtype,1);
    this.disconnect();
    return false;
  }
  return true;
};

/**
 * @private
 */
JSJaCConnection.prototype._doInBandReg = function() {
  if (this.authtype == 'saslanon' || this.authtype == 'anonymous')
    return; // bullshit - no need to register if anonymous

  /* ***
   * In-Band Registration see JEP-0077
   */

  var iq = new JSJaCIQ();
  iq.setType('set');
  iq.setID('reg1');
  iq.appendNode("query", {xmlns: "jabber:iq:register"},
                [["username", this.username],
                 ["password", this.pass]]);

  this.send(iq,this._doInBandRegDone);
};

/**
 * @private
 */
JSJaCConnection.prototype._doInBandRegDone = function(iq) {
  if (iq && iq.getType() == 'error') { // we failed to register
    this.oDbg.log("registration failed for "+this.username,0);
    this._handleEvent('onerror',iq.getChild('error'));
    return;
  }

  this.oDbg.log(this.username + " registered succesfully",0);

  this._doAuth();
};

/**
 * @private
 */
JSJaCConnection.prototype._doLegacyAuth = function() {
  if (this.authtype != 'nonsasl' && this.authtype != 'anonymous')
    return false;

  /* ***
   * Non-SASL Authentication as described in JEP-0078
   */
  var iq = new JSJaCIQ();
  iq.setIQ(null,'get','auth1');
  iq.appendNode('query', {xmlns: 'jabber:iq:auth'},
                [['username', this.username]]);

  this.send(iq,this._doLegacyAuth2);
  return true;
};

/**
 * @private
 */
JSJaCConnection.prototype._doLegacyAuth2 = function(iq) {
  if (!iq || iq.getType() != 'result') {
    if (iq && iq.getType() == 'error')
      this._handleEvent('onerror',iq.getChild('error'));
    this.disconnect();
    return;
  }

  var use_digest = (iq.getChild('digest') != null);

  /* ***
   * Send authentication
   */
  var iq = new JSJaCIQ();
  iq.setIQ(null,'set','auth2');

  query = iq.appendNode('query', {xmlns: 'jabber:iq:auth'},
                        [['username', this.username],
                         ['resource', this.resource]]);

  if (use_digest) { // digest login
    query.appendChild(iq.buildNode('digest', {xmlns: 'jabber:iq:auth'},
                                   hex_sha1(this.streamid + this.pass)));
  } else if (this.allow_plain) { // use plaintext auth
    query.appendChild(iq.buildNode('password', {xmlns: 'jabber:iq:auth'},
                                   this.pass));
  } else {
    this.oDbg.log("no valid login mechanism found",1);
    this.disconnect();
    return false;
  }

  this.send(iq,this._doLegacyAuthDone);
};

/**
 * @private
 */
JSJaCConnection.prototype._doLegacyAuthDone = function(iq) {
  if (iq.getType() != 'result') { // auth' failed
    if (iq.getType() == 'error')
      this._handleEvent('onerror',iq.getChild('error'));
    this.disconnect();
  } else
    this._handleEvent('onconnect');
};

/**
 * @private
 */
JSJaCConnection.prototype._doSASLAuth = function() {
  if (this.authtype == 'nonsasl' || this.authtype == 'anonymous')
    return false;

  if (this.authtype == 'saslanon') {
    if (this.mechs['ANONYMOUS']) {
      this.oDbg.log("SASL using mechanism 'ANONYMOUS'",2);
      return this._sendRaw("<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='ANONYMOUS'/>",
                           this._doSASLAuthDone);
    }
    this.oDbg.log("SASL ANONYMOUS requested but not supported",1);
  } else {
    if (this.mechs['DIGEST-MD5']) {
      this.oDbg.log("SASL using mechanism 'DIGEST-MD5'",2);
      return this._sendRaw("<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='DIGEST-MD5'/>",
                           this._doSASLAuthDigestMd5S1);
    } else if (this.allow_plain && this.mechs['PLAIN']) {
      this.oDbg.log("SASL using mechanism 'PLAIN'",2);
      var authStr = this.username+'@'+
      this.domain+String.fromCharCode(0)+
      this.username+String.fromCharCode(0)+
      this.pass;
      this.oDbg.log("authenticating with '"+authStr+"'",2);
      authStr = b64encode(authStr);
      return this._sendRaw("<auth xmlns='urn:ietf:params:xml:ns:xmpp-sasl' mechanism='PLAIN'>"+authStr+"</auth>",
                           this._doSASLAuthDone);
    }
    this.oDbg.log("No SASL mechanism applied",1);
    this.authtype = 'nonsasl'; // fallback
  }
  return false;
};

/**
 * @private
 */
JSJaCConnection.prototype._doSASLAuthDigestMd5S1 = function(el) {
  if (el.nodeName != "challenge") {
    this.oDbg.log("challenge missing",1);
    this._handleEvent('onerror',JSJaCError('401','auth','not-authorized'));
    this.disconnect();
  } else {
    var challenge = b64decode(el.firstChild.nodeValue);
    this.oDbg.log("got challenge: "+challenge,2);
    this._nonce = challenge.substring(challenge.indexOf("nonce=")+7);
    this._nonce = this._nonce.substring(0,this._nonce.indexOf("\""));
    this.oDbg.log("nonce: "+this._nonce,2);
    if (this._nonce == '' || this._nonce.indexOf('\"') != -1) {
      this.oDbg.log("nonce not valid, aborting",1);
      this.disconnect();
      return;
    }

    this._digest_uri = "xmpp/";
    //     if (typeof(this.host) != 'undefined' && this.host != '') {
    //       this._digest-uri += this.host;
    //       if (typeof(this.port) != 'undefined' && this.port)
    //         this._digest-uri += ":" + this.port;
    //       this._digest-uri += '/';
    //     }
    this._digest_uri += this.domain;

    this._cnonce = cnonce(14);

    this._nc = '00000001';

    var X = this.username+':'+this.domain+':'+this.pass;
    var Y = rstr_md5(str2rstr_utf8(X));

    var A1 = Y+':'+this._nonce+':'+this._cnonce;
    var HA1 = rstr2hex(rstr_md5(A1));

    var A2 = 'AUTHENTICATE:'+this._digest_uri;
    var HA2 = hex_md5(A2);

    var response = hex_md5(HA1+':'+this._nonce+':'+this._nc+':'+
                           this._cnonce+':auth:'+HA2);

    var rPlain = 'username="'+this.username+'",realm="'+this.domain+
    '",nonce="'+this._nonce+'",cnonce="'+this._cnonce+'",nc="'+this._nc+
    '",qop=auth,digest-uri="'+this._digest_uri+'",response="'+response+
    '",charset="utf-8"';

    this.oDbg.log("response: "+rPlain,2);

    this._sendRaw("<response xmlns='urn:ietf:params:xml:ns:xmpp-sasl'>"+
                  b64encode(rPlain)+"</response>",
                  this._doSASLAuthDigestMd5S2);
  }
};

/**
 * @private
 */
JSJaCConnection.prototype._doSASLAuthDigestMd5S2 = function(el) {
  if (el.nodeName == 'failure') {
    if (el.xml)
      this.oDbg.log("auth error: "+el.xml,1);
    else
      this.oDbg.log("auth error",1);
    this._handleEvent('onerror',JSJaCError('401','auth','not-authorized'));
    this.disconnect();
    return;
  }

  var response = b64decode(el.firstChild.nodeValue);
  this.oDbg.log("response: "+response,2);

  var rspauth = response.substring(response.indexOf("rspauth=")+8);
  this.oDbg.log("rspauth: "+rspauth,2);

  var X = this.username+':'+this.domain+':'+this.pass;
  var Y = rstr_md5(str2rstr_utf8(X));

  var A1 = Y+':'+this._nonce+':'+this._cnonce;
  var HA1 = rstr2hex(rstr_md5(A1));

  var A2 = ':'+this._digest_uri;
  var HA2 = hex_md5(A2);

  var rsptest = hex_md5(HA1+':'+this._nonce+':'+this._nc+':'+
                        this._cnonce+':auth:'+HA2);
  this.oDbg.log("rsptest: "+rsptest,2);

  if (rsptest != rspauth) {
    this.oDbg.log("SASL Digest-MD5: server repsonse with wrong rspauth",1);
    this.disconnect();
    return;
  }

    if (el.nodeName == 'success') {
        this._reInitStream(JSJaC.bind(this._doStreamBind, this));
    } else { // some extra turn
        this._sendRaw("<response xmlns='urn:ietf:params:xml:ns:xmpp-sasl'/>",
                      this._doSASLAuthDone);
    }
};

/**
 * @private
 */
JSJaCConnection.prototype._doSASLAuthDone = function (el) {
    if (el.nodeName != 'success') {
        this.oDbg.log("auth failed",1);
        this._handleEvent('onerror',JSJaCError('401','auth','not-authorized'));
        this.disconnect();
    } else {
        this._reInitStream(JSJaC.bind(this._doStreamBind, this));
    }
};

/**
 * @private
 */
JSJaCConnection.prototype._doStreamBind = function() {
  var iq = new JSJaCIQ();
  iq.setIQ(null,'set','bind_1');
  iq.appendNode("bind", {xmlns: "urn:ietf:params:xml:ns:xmpp-bind"},
                [["resource", this.resource]]);
  this.oDbg.log(iq.xml());
  this.send(iq,this._doXMPPSess);
};

/**
 * @private
 */
JSJaCConnection.prototype._doXMPPSess = function(iq) {
  if (iq.getType() != 'result' || iq.getType() == 'error') { // failed
    this.disconnect();
    if (iq.getType() == 'error')
      this._handleEvent('onerror',iq.getChild('error'));
    return;
  }

  this.fulljid = iq.getChildVal("jid");
  this.jid = this.fulljid.substring(0,this.fulljid.lastIndexOf('/'));

  if (!this.legacy_sessions) {
    this._handleEvent('onconnect');
    return;
  }

  iq = new JSJaCIQ();
  iq.setIQ(null,'set','sess_1');
  iq.appendNode("session", {xmlns: "urn:ietf:params:xml:ns:xmpp-session"},
                []);
  this.oDbg.log(iq.xml());
  this.send(iq,this._doXMPPSessDone);
};

/**
 * @private
 */
JSJaCConnection.prototype._doXMPPSessDone = function(iq) {
  if (iq.getType() != 'result' || iq.getType() == 'error') { // failed
    this.disconnect();
    if (iq.getType() == 'error')
      this._handleEvent('onerror',iq.getChild('error'));
    return;
  } else
    this._handleEvent('onconnect');
};

/**
 * @private
 */
JSJaCConnection.prototype._handleEvent = function(event,arg) {
  event = event.toLowerCase(); // don't be case-sensitive here
  this.oDbg.log("incoming event '"+event+"'",3);
  if (!this._events[event])
    return;
  this.oDbg.log("handling event '"+event+"'",2);
  for (var i=0;i<this._events[event].length; i++) {
    var aEvent = this._events[event][i];
    if (typeof aEvent.handler == 'function') {
      try {
        if (arg) {
          if (arg.pType) { // it's a packet
            if ((!arg.getNode().hasChildNodes() && aEvent.childName != '*') ||
        (arg.getNode().hasChildNodes() &&
         !arg.getChild(aEvent.childName, aEvent.childNS)))
              continue;
            if (aEvent.type != '*' &&
                arg.getType() != aEvent.type)
              continue;
            this.oDbg.log(aEvent.childName+"/"+aEvent.childNS+"/"+aEvent.type+" => match for handler "+aEvent.handler,3);
          }
          if (aEvent.handler(arg)) {
            // handled!
            break;
          }
        }
        else
          if (aEvent.handler()) {
            // handled!
            break;
          }
      } catch (e) {

        if (e.fileName&&e.lineNumber) {
            this.oDbg.log(aEvent.handler+"\n>>>"+e.name+": "+ e.message+' in '+e.fileName+' line '+e.lineNumber,1);
        } else {
            this.oDbg.log(aEvent.handler+"\n>>>"+e.name+": "+ e.message,1);
        }

      }
    }
  }
};

/**
 * @private
 */
JSJaCConnection.prototype._handlePID = function(packet) {
  if (!packet.getID())
    return false;

  var jid = packet.getFrom() || this.jid;

  if (packet.getFrom() == this.domain)
    jid = this.jid;

  var id = packet.getID();
  if (this._regIDs[jid] && this._regIDs[jid][id]) {
    try {
      this.oDbg.log("handling id "+id,3);
      var reg = this._regIDs[jid][id];
      if (reg.cb.call(this, packet, reg.arg) === false) {
        // don't unregister
        return false;
      } else {
        delete this._regIDs[jid][id];
        return true;
      }
    } catch (e) {
      // broken handler?
      this.oDbg.log(e.name+": "+ e.message, 1);
      delete this._regIDs[jid][id];
      return true;
    }
  } else {
    this.oDbg.log("not handling id '"+id+"' from jid "+jid, 1);
  }
  return false;
};

/**
 * @private
 */
JSJaCConnection.prototype._handleResponse = function(req) {
  var rootEl = this._parseResponse(req);

  if (!rootEl)
    return;

  for (var i=0; i<rootEl.childNodes.length; i++) {
    if (this._sendRawCallbacks.length) {
      var cb = this._sendRawCallbacks[0];
      this._sendRawCallbacks = this._sendRawCallbacks.slice(1, this._sendRawCallbacks.length);
      cb.fn.call(this, rootEl.childNodes.item(i), cb.arg);
      continue;
    }
    this._inQ = this._inQ.concat(rootEl.childNodes.item(i));
  }
};

/**
 * @private
 */
JSJaCConnection.prototype._parseStreamFeatures = function(doc) {
    if (!doc) {
        this.oDbg.log("nothing to parse ... aborting",1);
        return false;
    }

    var errorTag;
    if (doc.getElementsByTagNameNS) {
        errorTag = doc.getElementsByTagNameNS("http://etherx.jabber.org/streams", "error").item(0);
    } else {
        var errors = doc.getElementsByTagName("error");
        for (var i=0; i<errors.length; i++)
            if (errors.item(i).namespaceURI == "http://etherx.jabber.org/streams" ||
                errors.item(i).getAttribute('xmlns') == "http://etherx.jabber.org/streams") {
                errorTag = errors.item(i);
                break;
            }
    }

    if (errorTag) {
        this._setStatus("internal_server_error");
        clearTimeout(this._timeout); // remove timer
        clearInterval(this._interval);
        clearInterval(this._inQto);
        this._handleEvent('onerror',JSJaCError('503','cancel','session-terminate'));
        this._connected = false;
        this.oDbg.log("Disconnected.",1);
        this._handleEvent('ondisconnect');
        return false;
    }

    this.mechs = new Object();
    var lMec1 = doc.getElementsByTagName("mechanisms");
    this.has_sasl = false;
    for (var i=0; i<lMec1.length; i++)
        if (lMec1.item(i).getAttribute("xmlns") ==
            "urn:ietf:params:xml:ns:xmpp-sasl") {
            this.has_sasl=true;
            var lMec2 = lMec1.item(i).getElementsByTagName("mechanism");
            for (var j=0; j<lMec2.length; j++)
                this.mechs[lMec2.item(j).firstChild.nodeValue] = true;
            break;
        }
    if (this.has_sasl)
        this.oDbg.log("SASL detected",2);
    else {
        this.oDbg.log("No support for SASL detected",2);
        return false;
    }

    // Get the server CAPS (if available)
    this.server_caps=null;
    var sCaps = doc.getElementsByTagName("c");
    for (var i=0; i<sCaps.length; i++) {
      var c_sCaps=sCaps.item(i);
      var x_sCaps=c_sCaps.getAttribute("xmlns");
      var v_sCaps=c_sCaps.getAttribute("ver");

      if ((x_sCaps == NS_CAPS) && v_sCaps) {
        this.server_caps=v_sCaps;
        break;
      }
    }

    // Get legacy session capability if available
    this.legacy_sessions=null;
    if (doc.getElementsByTagName("session")) {
	this.legacy_sessions=true;
    }

    return true;
};

/**
 * @private
 */
JSJaCConnection.prototype._process = function(timerval) {
  if (!this.connected()) {
    this.oDbg.log("Connection lost ...",1);
    if (this._interval)
      clearInterval(this._interval);
    return;
  }

  this.setPollInterval(timerval);

  if (this._timeout)
    clearTimeout(this._timeout);

  var slot = this._getFreeSlot();

  if (slot < 0)
    return;

  if (typeof(this._req[slot]) != 'undefined' &&
      typeof(this._req[slot].r) != 'undefined' &&
      this._req[slot].r.readyState != 4) {
    this.oDbg.log("Slot "+slot+" is not ready");
    return;
  }

  if (!this.isPolling() && this._pQueue.length == 0 &&
      this._req[(slot+1)%2] && this._req[(slot+1)%2].r.readyState != 4) {
    this.oDbg.log("all slots busy, standby ...", 2);
    return;
  }

  if (!this.isPolling())
    this.oDbg.log("Found working slot at "+slot,2);

  this._req[slot] = this._setupRequest(true);

  /* setup onload handler for async send */
  this._req[slot].r.onreadystatechange =
  JSJaC.bind(function() {
               if (this._req[slot].r.readyState == 4) {
                 this._setStatus('processing');
                 this.oDbg.log("async recv: "+this._req[slot].r.responseText,4);
                 this._handleResponse(this._req[slot]);

                 if (!this.connected())
                   return;

                 // schedule next tick
                 if (this._pQueue.length) {
                   this._timeout = setTimeout(JSJaC.bind(this._process, this),100);
                 } else {
                   this.oDbg.log("scheduling next poll in "+this.getPollInterval()+
                                 " msec", 4);
                   this._timeout = setTimeout(JSJaC.bind(this._process, this),this.getPollInterval());
                 }
               }
             }, this);

  try {
    this._req[slot].r.onerror =
      JSJaC.bind(function() {
                   if (!this.connected())
                     return;
                   this._errcnt++;
                   this.oDbg.log('XmlHttpRequest error ('+this._errcnt+')',1);
                   if (this._errcnt > JSJAC_ERR_COUNT) {
                     // abort
                     this._abort();
                     return false;
                   }

                   this._setStatus('onerror_fallback');

                   // schedule next tick
                   setTimeout(JSJaC.bind(this._resume, this),this.getPollInterval());
                   return false;
                 }, this);
  } catch(e) { } // well ... no onerror property available, maybe we
  // can catch the error somewhere else ...

  var reqstr = this._getRequestString();

  if (typeof(this._rid) != 'undefined') // remember request id if any
    this._req[slot].rid = this._rid;

  this.oDbg.log("sending: " + reqstr,4);
  this._req[slot].r.send(reqstr);
};

/**
 * @private
   @param {JSJaCPacket} packet The packet to be sent.
   @param {function} cb The callback to be called when response is received.
   @param {any} arg Optional arguments to be passed to 'cb' when executing it.
   @return Whether registering an ID was successful
   @type boolean
 */
JSJaCConnection.prototype._registerPID = function(packet, cb, arg) {
  this.oDbg.log("registering id for packet "+packet.xml(), 3);
  var id = packet.getID();
  if (!id) {
    this.oDbg.log("id missing", 1);
    return false;
  }

  if (typeof cb != 'function') {
    this.oDbg.log("callback is not a function", 1);
    return false;
  }

  var jid = packet.getTo() || this.jid;

  if (packet.getTo() == this.domain)
    jid = this.jid;

  if (!this._regIDs[jid]) {
    this._regIDs[jid] = {};
  }

  if (this._regIDs[jid][id] != null) {
    this.oDbg.log("id already registered: " + id, 1);
    return false;
  }
  this._regIDs[jid][id] = {
      cb:  cb,
      arg: arg,
      ts:  Date.now()
  };
  this.oDbg.log("registered id "+id,3);
  this._cleanupRegisteredPIDs();
  return true;
};

/**
 * @private
 */
JSJaCConnection.prototype._cleanupRegisteredPIDs = function() {
  var now = Date.now();
  for (var jid in this._regIDs) {
    if (this._regIDs.hasOwnProperty(jid)) {
      for (var id in this._regIDs[jid]) {
        if (this._regIDs[jid].hasOwnProperty(id)) {
          if (this._regIDs[jid][id].ts + JSJAC_REGID_TIMEOUT < now) {
            this.oDbg.log("deleting registered id '"+id+ "' due to timeout", 1);
            delete this._regIDs[jid][id];
          }
        }
      }
    }
  }
};

/**
 * Partial function binding sendEmpty to callback
 * @private
 */
JSJaCConnection.prototype._prepSendEmpty = function(cb, ctx) {
    return function() {
        ctx._sendEmpty(JSJaC.bind(cb, ctx));
    };
};

/**
 * send empty request
 * waiting for stream id to be able to proceed with authentication
 * @private
 */
JSJaCConnection.prototype._sendEmpty = function(cb) {
  var slot = this._getFreeSlot();
  this._req[slot] = this._setupRequest(true);

  this._req[slot].r.onreadystatechange =
  JSJaC.bind(function() {
               if (this._req[slot].r.readyState == 4) {
                 this.oDbg.log("async recv: "+this._req[slot].r.responseText,4);
                   cb(this._req[slot].r); // handle response
               }
             },this);

  if (typeof(this._req[slot].r.onerror) != 'undefined') {
    this._req[slot].r.onerror =
      JSJaC.bind(function(e) {
                   this.oDbg.log('XmlHttpRequest error',1);
                   return false;
                 }, this);
  }

  var reqstr = this._getRequestString();
  this.oDbg.log("sending: " + reqstr,4);
  this._req[slot].r.send(reqstr);
};

/**
 * @private
 */
JSJaCConnection.prototype._sendRaw = function(xml,cb,arg) {
  if (cb)
    this._sendRawCallbacks.push({fn: cb, arg: arg});

  this._pQueue.push(xml);
  this._process();

  return true;
};

/**
 * @private
 */
JSJaCConnection.prototype._setStatus = function(status) {
  if (!status || status == '')
    return;
  if (status != this._status) { // status changed!
    this._status = status;
    this._handleEvent('onstatuschanged', status);
    this._handleEvent('status_changed', status);
  }
};


/**
 * @fileoverview All stuff related to HTTP Binding
 * @author Stefan Strigler steve@zeank.in-berlin.de
 * @version 1.3
 */

/**
 * Instantiates an HTTP Binding session
 * @class Implementation of {@link
 * http://www.xmpp.org/extensions/xep-0206.html XMPP Over BOSH}
 * formerly known as HTTP Binding.
 * @extends JSJaCConnection
 * @constructor
 */
function JSJaCHttpBindingConnection(oArg) {
  /**
   * @ignore
   */
  this.base = JSJaCConnection;
  this.base(oArg);

  // member vars
  /**
   * @private
   */
  this._hold = JSJACHBC_MAX_HOLD;
  /**
   * @private
   */
  this._inactivity = 0;
  /**
   * @private
   */
  this._last_requests = new Object(); // 'hash' storing hold+1 last requests
  /**
   * @private
   */
  this._last_rid = 0;                 // I know what you did last summer
  /**
   * @private
   */
  this._min_polling = 0;

  /**
   * @private
   */
  this._pause = 0;
  /**
   * @private
   */
  this._wait = JSJACHBC_MAX_WAIT;
}
JSJaCHttpBindingConnection.prototype = new JSJaCConnection();

/**
 * Inherit an instantiated HTTP Binding session
 */
JSJaCHttpBindingConnection.prototype.inherit = function(oArg) {
  if (oArg.jid) {
    var oJid = new JSJaCJID(oArg.jid);
    this.domain = oJid.getDomain();
    this.username = oJid.getNode();
    this.resource = oJid.getResource();
  } else {
    this.domain = oArg.domain || 'localhost';
    this.username = oArg.username;
    this.resource = oArg.resource;
  }
  this._sid = oArg.sid;
  this._rid = oArg.rid;
  this._min_polling = oArg.polling;
  this._inactivity = oArg.inactivity;
  this._setHold(oArg.requests-1);
  this.setPollInterval(this._timerval);
  if (oArg.wait)
    this._wait = oArg.wait; // for whatever reason

  this._connected = true;

  this._handleEvent('onconnect');

  this._interval= setInterval(JSJaC.bind(this._checkQueue, this),
                              JSJAC_CHECKQUEUEINTERVAL);
  this._inQto = setInterval(JSJaC.bind(this._checkInQ, this),
                            JSJAC_CHECKINQUEUEINTERVAL);
  this._timeout = setTimeout(JSJaC.bind(this._process, this),
                             this.getPollInterval());
};

/**
 * Sets poll interval
 * @param {int} timerval the interval in seconds
 */
JSJaCHttpBindingConnection.prototype.setPollInterval = function(timerval) {
  if (timerval && !isNaN(timerval)) {
    if (!this.isPolling())
      this._timerval = 100;
    else if (this._min_polling && timerval < this._min_polling*1000)
      this._timerval = this._min_polling*1000;
    else if (this._inactivity && timerval > this._inactivity*1000)
      this._timerval = this._inactivity*1000;
    else
      this._timerval = timerval;
  }
  return this._timerval;
};

/**
 * whether this session is in polling mode
 * @type boolean
 */
JSJaCHttpBindingConnection.prototype.isPolling = function() { return (this._hold == 0) };

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._getFreeSlot = function() {
  for (var i=0; i<this._hold+1; i++)
    if (typeof(this._req[i]) == 'undefined' || typeof(this._req[i].r) == 'undefined' || this._req[i].r.readyState == 4)
      return i;
  return -1; // nothing found
};

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._getHold = function() { return this._hold; };

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._getRequestString = function(raw, last) {
  raw = raw || '';
  var reqstr = '';

  // check if we're repeating a request

  if (this._rid <= this._last_rid && typeof(this._last_requests[this._rid]) != 'undefined') // repeat!
    reqstr = this._last_requests[this._rid].xml;
  else { // grab from queue
    var xml = '';
    while (this._pQueue.length) {
      var curNode = this._pQueue[0];
      xml += curNode;
      this._pQueue = this._pQueue.slice(1,this._pQueue.length);
    }

    reqstr = "<body xml:lang='"+XML_LANG+"' rid='"+this._rid+"' sid='"+this._sid+"' xmlns='http://jabber.org/protocol/httpbind' ";
    if (JSJAC_HAVEKEYS) {
      reqstr += "key='"+this._keys.getKey()+"' ";
      if (this._keys.lastKey()) {
        this._keys = new JSJaCKeys(hex_sha1,this.oDbg);
        reqstr += "newkey='"+this._keys.getKey()+"' ";
      }
    }
    if (last)
      reqstr += "type='terminate'";
    else if (this._reinit) {
      if (JSJACHBC_USE_BOSH_VER)
        reqstr += "xmpp:restart='true' xmlns:xmpp='urn:xmpp:xbosh' to='"+this.domain+"'";
      this._reinit = false;
    }

    if (xml != '' || raw != '') {
      reqstr += ">" + raw + xml + "</body>";
    } else {
      reqstr += "/>";
    }

    this._last_requests[this._rid] = new Object();
    this._last_requests[this._rid].xml = reqstr;
    this._last_rid = this._rid;

    for (var i in this._last_requests)
      if (this._last_requests.hasOwnProperty(i) &&
          i < this._rid-this._hold)
        delete(this._last_requests[i]); // truncate
  }

  return reqstr;
};

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._getInitialRequestString = function() {
  var reqstr = "<body xml:lang='"+XML_LANG+"' content='text/xml; charset=utf-8' hold='"+this._hold+"' xmlns='http://jabber.org/protocol/httpbind' to='"+this.authhost+"' wait='"+this._wait+"' rid='"+this._rid+"'";
  if (this.secure)
    reqstr += " secure='"+this.secure+"'";
  if (JSJAC_HAVEKEYS) {
    this._keys = new JSJaCKeys(hex_sha1,this.oDbg); // generate first set of keys
    key = this._keys.getKey();
    reqstr += " newkey='"+key+"'";
  }

  if (JSJACHBC_USE_BOSH_VER) {
    reqstr += " ver='" + JSJACHBC_BOSH_VERSION + "'";
    reqstr += " xmlns:xmpp='urn:xmpp:xbosh'";
    if (this.authtype == 'sasl' || this.authtype == 'saslanon')
      reqstr += " xmpp:version='1.0'";
  }
  reqstr += "/>";
  return reqstr;
};

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._getStreamID = function(req) {

  this.oDbg.log(req.responseText,4);

  if (!req.responseXML || !req.responseXML.documentElement) {
    this._handleEvent('onerror',JSJaCError('503','cancel','service-unavailable'));
    return;
  }
  var body = req.responseXML.documentElement;

  // any session error?
  if(body.getAttribute('type') == 'terminate') {
    this._handleEvent('onerror',JSJaCError('503','cancel','service-unavailable'));
    return;
  }

  // extract stream id used for non-SASL authentication
  if (body.getAttribute('authid')) {
    this.streamid = body.getAttribute('authid');
    this.oDbg.log("got streamid: "+this.streamid,2);
  }

  if (!this._parseStreamFeatures(body)) {
      this._sendEmpty(JSJaC.bind(this._getStreamID, this));
      return;
  }

  this._timeout = setTimeout(JSJaC.bind(this._process, this),
                             this.getPollInterval());

  if (this.register)
    this._doInBandReg();
  else
    this._doAuth();
};

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._getSuspendVars = function() {
  return ('host,port,secure,_rid,_last_rid,_wait,_min_polling,_inactivity,_hold,_last_requests,_pause').split(',');
};

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._handleInitialResponse = function(req) {
  try {
    // This will throw an error on Mozilla when the connection was refused
    this.oDbg.log(req.getAllResponseHeaders(),4);
    this.oDbg.log(req.responseText,4);
  } catch(ex) {
    this.oDbg.log("No response",4);
  }

  if (req.status != 200 || !req.responseXML) {
    this.oDbg.log("initial response broken (status: "+req.status+")",1);
    this._handleEvent('onerror',JSJaCError('503','cancel','service-unavailable'));
    return;
  }
  var body = req.responseXML.documentElement;

  if (!body || body.tagName != 'body' || body.namespaceURI != 'http://jabber.org/protocol/httpbind') {
    this.oDbg.log("no body element or incorrect body in initial response",1);
    this._handleEvent("onerror",JSJaCError("500","wait","internal-service-error"));
    return;
  }

  // Check for errors from the server
  if (body.getAttribute("type") == "terminate") {
    this.oDbg.log("invalid response:\n" + req.responseText,1);
    clearTimeout(this._timeout); // remove timer
    this._connected = false;
    this.oDbg.log("Disconnected.",1);
    this._handleEvent('ondisconnect');
    this._handleEvent('onerror',JSJaCError('503','cancel','service-unavailable'));
    return;
  }

  // get session ID
  this._sid = body.getAttribute('sid');
  this.oDbg.log("got sid: "+this._sid,2);

  // get attributes from response body
  if (body.getAttribute('polling'))
    this._min_polling = body.getAttribute('polling');

  if (body.getAttribute('inactivity'))
    this._inactivity = body.getAttribute('inactivity');

  if (body.getAttribute('requests'))
    this._setHold(body.getAttribute('requests')-1);
  this.oDbg.log("set hold to " + this._getHold(),2);

  if (body.getAttribute('ver'))
    this._bosh_version = body.getAttribute('ver');

  if (body.getAttribute('maxpause'))
    this._pause = Number.min(body.getAttribute('maxpause'), JSJACHBC_MAXPAUSE);

  // must be done after response attributes have been collected
  this.setPollInterval(this._timerval);

  /* start sending from queue for not polling connections */
  this._connected = true;

  this._inQto = setInterval(JSJaC.bind(this._checkInQ, this),
                            JSJAC_CHECKINQUEUEINTERVAL);
  this._interval= setInterval(JSJaC.bind(this._checkQueue, this),
                              JSJAC_CHECKQUEUEINTERVAL);

  /* wait for initial stream response to extract streamid needed
   * for digest auth
   */
  this._getStreamID(req);
};

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._parseResponse = function(req) {
  if (!this.connected() || !req)
    return null;

  var r = req.r; // the XmlHttpRequest

  try {
    if (r.status == 404 || r.status == 403) {
      // connection manager killed session
      this._abort();
      return null;
    }

    if (r.status != 200 || !r.responseXML) {
      this._errcnt++;
      var errmsg = "invalid response ("+r.status+"):\n" + r.getAllResponseHeaders()+"\n"+r.responseText;
      if (!r.responseXML)
        errmsg += "\nResponse failed to parse!";
      this.oDbg.log(errmsg,1);
      if (this._errcnt > JSJAC_ERR_COUNT) {
        // abort
        this._abort();
        return null;
      }

      if (this.connected()) {
        this.oDbg.log("repeating ("+this._errcnt+")",1);
        this._setStatus('proto_error_fallback');

        // schedule next tick
        setTimeout(JSJaC.bind(this._resume, this),
                   this.getPollInterval());
      }

      return null;
    }
  } catch (e) {
    this.oDbg.log("XMLHttpRequest error: status not available", 1);
    this._errcnt++;
    if (this._errcnt > JSJAC_ERR_COUNT) {
      // abort
      this._abort();
    } else {
      if (this.connected()) {
        this.oDbg.log("repeating ("+this._errcnt+")",1);

        this._setStatus('proto_error_fallback');

        // schedule next tick
        setTimeout(JSJaC.bind(this._resume, this),
                   this.getPollInterval());
      }
    }
    return null;
  }

  var body = r.responseXML.documentElement;
  if (!body || body.tagName != 'body' ||
    body.namespaceURI != 'http://jabber.org/protocol/httpbind') {
    this.oDbg.log("invalid response:\n" + r.responseText,1);

    clearTimeout(this._timeout); // remove timer
    clearInterval(this._interval);
    clearInterval(this._inQto);

    this._connected = false;
    this.oDbg.log("Disconnected.",1);
    this._handleEvent('ondisconnect');

    this._setStatus('internal_server_error');
    this._handleEvent('onerror',
          JSJaCError('500','wait','internal-server-error'));

    return null;
  }

  if (typeof(req.rid) != 'undefined' && this._last_requests[req.rid]) {
    if (this._last_requests[req.rid].handled) {
      this.oDbg.log("already handled "+req.rid,2);
      return null;
    } else
      this._last_requests[req.rid].handled = true;
  }


  // Check for errors from the server
  if (body.getAttribute("type") == "terminate") {
    // read condition
    var condition = body.getAttribute('condition');

    if (condition != "item-not-found") {
      this.oDbg.log("session terminated:\n" + r.responseText,1);

      clearTimeout(this._timeout); // remove timer
      clearInterval(this._interval);
      clearInterval(this._inQto);

      try {
        DataStore.removeDB(MINI_HASH, 'jsjac', 'state');
      } catch (e) {}

      this._connected = false;

      if (condition == "remote-stream-error")
        if (body.getElementsByTagName("conflict").length > 0)
          this._setStatus("session-terminate-conflict");
      if (condition == null)
        condition = 'session-terminate';
      this._handleEvent('onerror',JSJaCError('503','cancel',condition));

      this.oDbg.log("Aborting remaining connections",4);

      for (var i=0; i<this._hold+1; i++) {
        try {
          this._req[i].r.abort();
        } catch(e) { this.oDbg.log(e, 1); }
      }

      this.oDbg.log("parseResponse done with terminating", 3);

      this.oDbg.log("Disconnected.",1);
      this._handleEvent('ondisconnect');
    } else {
      this._errcnt++;
      if (this._errcnt > JSJAC_ERR_COUNT)
        this._abort();
    }
    return null;
  }

  // no error
  this._errcnt = 0;
  return r.responseXML.documentElement;
};

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._reInitStream = function(cb) {
    // tell http binding to reinit stream with/before next request
    this._reinit = true;

    this._sendEmpty(this._prepReInitStreamWait(cb));
};


JSJaCHttpBindingConnection.prototype._prepReInitStreamWait = function(cb) {
    return JSJaC.bind(function(req) {
        this._reInitStreamWait(req, cb);
    }, this);
};

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._reInitStreamWait = function(req, cb) {
    this.oDbg.log("checking for stream features");
    var doc = req.responseXML.documentElement;
    this.oDbg.log(doc);
    if (doc.getElementsByTagNameNS) {
        this.oDbg.log("checking with namespace");
        var features = doc.getElementsByTagNameNS('http://etherx.jabber.org/streams',
                                                'features').item(0);
        if (features) {
            var bind = features.getElementsByTagNameNS('urn:ietf:params:xml:ns:xmpp-bind',
                                                       'bind').item(0);
        }
    } else {
        var featuresNL = doc.getElementsByTagName('stream:features');
        for (var i=0, l=featuresNL.length; i<l; i++) {
            if (featuresNL.item(i).namespaceURI == 'http://etherx.jabber.org/streams' ||
                featuresNL.item(i).getAttribute('xmlns') ==
                'http://etherx.jabber.org/streams') {
                var features = featuresNL.item(i);
                break;
            }
        }
        if (features) {
            var bind = features.getElementsByTagName('bind');
            for (var i=0, l=bind.length; i<l; i++) {
                if (bind.item(i).namespaceURI == 'urn:ietf:params:xml:ns:xmpp-bind' ||
                    bind.item(i).getAttribute('xmlns') ==
                    'urn:ietf:params:xml:ns:xmpp-bind') {
                    bind = bind.item(i);
                    break;
                }
            }
        }
    }
    this.oDbg.log(features);
    this.oDbg.log(bind);

    if (features) {
        if (bind) {
            cb();
        } else {
            this.oDbg.log("no bind feature - giving up",1);
            this._handleEvent('onerror',JSJaCError('503','cancel',"service-unavailable"));
            this._connected = false;
            this.oDbg.log("Disconnected.",1);
            this._handleEvent('ondisconnect');
        }
    } else {
        // wait
        this._sendEmpty(this._prepReInitStreamWait(cb));
    }
};

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._resume = function() {
  /* make sure to repeat last request as we can be sure that
   * it had failed (only if we're not using the 'pause' attribute
   */
  if (this._pause == 0 && this._rid >= this._last_rid)
    this._rid = this._last_rid-1;

  this._process();
};

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._setHold = function(hold)  {
  if (!hold || isNaN(hold) || hold < 0)
    hold = 0;
  else if (hold > JSJACHBC_MAX_HOLD)
    hold = JSJACHBC_MAX_HOLD;
  this._hold = hold;
  return this._hold;
};

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._setupRequest = function(async) {
  var req = new Object();
  var r = XmlHttp.create();
  try {
    r.open("POST",this._httpbase,async);
    r.setRequestHeader('Content-Type','text/xml; charset=utf-8');
  } catch(e) { this.oDbg.log(e,1); }
  req.r = r;
  this._rid++;
  req.rid = this._rid;
  return req;
};

/**
 * @private
 */
JSJaCHttpBindingConnection.prototype._suspend = function() {
  if (this._pause == 0)
    return; // got nothing to do

  var slot = this._getFreeSlot();
  // Intentionally synchronous
  this._req[slot] = this._setupRequest(false);

  var reqstr = "<body xml:lang='"+XML_LANG+"' pause='"+this._pause+"' xmlns='http://jabber.org/protocol/httpbind' sid='"+this._sid+"' rid='"+this._rid+"'";
  if (JSJAC_HAVEKEYS) {
    reqstr += " key='"+this._keys.getKey()+"'";
    if (this._keys.lastKey()) {
      this._keys = new JSJaCKeys(hex_sha1,this.oDbg);
      reqstr += " newkey='"+this._keys.getKey()+"'";
    }

  }
  reqstr += ">";

  while (this._pQueue.length) {
    var curNode = this._pQueue[0];
    reqstr += curNode;
    this._pQueue = this._pQueue.slice(1,this._pQueue.length);
  }

  //reqstr += "<presence type='unavailable' xmlns='jabber:client'/>";
  reqstr += "</body>";

  this.oDbg.log("Disconnecting: " + reqstr,4);
  this._req[slot].r.send(reqstr);
};

/**
 * @author Janusz Dziemidowicz rraptorr@nails.eu.org
 * @fileoverview All stuff related to WebSocket
 * <pre>
 * The WebSocket protocol is a bit of a mess. Various, incompatible,
 * protocol drafts were implemented in browsers. Fortunately, recently
 * a finished protocol was released in RFC6455. Further description
 * assumes RFC6455 WebSocket protocol version.
 *
 * WebSocket browser support. Current (November 2012) browser status:
 * - Chrome 16+ - works properly and supports RFC6455
 * - Firefox 16+ - works properly and support RFC6455 (ealier versions
 *   have problems with proxies)
 * - Opera 12.10 - supports RFC6455, but does not work at all if a
 *   proxy is configured (earlier versions do not support RFC6455)
 * - Internet Explorer 10+ - works properly and supports RFC6455
 *
 * Due to the above status, this code is currently recommended on
 * Chrome 16+, Firefox 16+ and Internet Explorer 10+. Using it on
 * other browsers is discouraged.
 *
 * Please also note that some users are only able to connect to ports
 * 80 and 443. Port 80 is sometimes intercepted by transparent HTTP
 * proxies, which mostly does not support WebSocket, so port 443 is
 * the best choice currently (it does not have to be
 * encrypted). WebSocket also usually does not work well with reverse
 * proxies, be sure to make extensive tests if you use one.
 *
 * There is no standard for XMPP over WebSocket. However, there is a
 * draft (http://tools.ietf.org/html/draft-ietf-xmpp-websocket-00) and
 * this implementation follows it.
 *
 * Tested servers:
 *
 * - node-xmpp-bosh (https://github.com/dhruvbird/node-xmpp-bosh) -
 *   supports RFC6455 and works with no problems since 0.6.1, it also
 *   transparently uses STARTTLS if necessary
 * - wxg (https://github.com/Gordin/wxg) - supports RFC6455 and works
 *   with no problems, but cannot connect to servers requiring
 *   STARTTLS (original wxg at https://github.com/hocken/wxg has some
 *   issues, that were fixed by Gordin).
 * - ejabberd-websockets
 *   (https://github.com/superfeedr/ejabberd-websockets) - does not
 *   support RFC6455 hence it does not work, adapting it to support
 *   RFC6455 should be quite easy for anyone knowing Erlang (some work
 *   in progress can be found on github)
 * - Openfire (http://www.igniterealtime.org/projects/openfire/) -
 *   unofficial plugin is available, but it lacks support
 *   for RFC6455 hence it does not work
 * - Apache Vysper (http://mina.apache.org/vysper/) - does
 *   not support RFC6455 hence does not work
 * - Tigase (http://www.tigase.org/) - works fine since 5.2.0.
 * - MongooseIM (https://github.com/esl/ejabberd) - a fork of ejabberd
 *   with support for XMPP over Websockets.
 * </pre>
 */

/*exported JSJaCWebSocketConnection */

/**
 * Instantiates a WebSocket session.
 * @class Implementation of {@link http://tools.ietf.org/html/draft-ietf-xmpp-websocket-00 | An XMPP Sub-protocol for WebSocket}.
 * @extends JSJaCConnection
 * @constructor
 * @param {Object} oArg connection properties.
 * @param {string} oArg.httpbase WebSocket connection endpoint (i.e. ws://localhost:5280)
 * @param {JSJaCDebugger} [oArg.oDbg] A reference to a debugger implementing the JSJaCDebugger interface.
 */
function JSJaCWebSocketConnection(oArg) {
  this.base = JSJaCConnection;
  this.base(oArg);

  this._ws = null;

  this.registerHandler('onerror', JSJaC.bind(this._cleanupWebSocket, this));
}

JSJaCWebSocketConnection.prototype = new JSJaCConnection();

JSJaCWebSocketConnection.prototype._cleanupWebSocket = function() {
  if (this._ws !== null) {
    this._ws.onclose = null;
    this._ws.onerror = null;
    this._ws.onopen = null;
    this._ws.onmessage = null;

    this._ws.close();
    this._ws = null;
  }
};

/**
 * Connect to a jabber/XMPP server.
 * @param {Object} oArg The configuration to be used for connecting.
 * @param {string} oArg.domain The domain name of the XMPP service.
 * @param {string} oArg.username The username (nodename) to be logged in with.
 * @param {string} oArg.resource The resource to identify the login with.
 * @param {string} oArg.password The user's password.
 * @param {string} [oArg.authzid] Authorization identity. Used to act as another user, in most cases not needed and rarely supported by servers. If present should be a bare JID (user@example.net).
 * @param {boolean} [oArg.allow_plain] Whether to allow plain text logins.
 * @param {boolean} [oArg.allow_scram] Whether to allow SCRAM-SHA-1 authentication. Please note that it is quite slow, do some testing on all required browsers before enabling.
 * @param {boolean} [oArg.register] Whether to register a new account.
 * @param {string} [oArg.authhost] The host that handles the actualy authorization. There are cases where this is different from the settings above, e.g. if there's a service that provides anonymous logins at 'anon.example.org'.
 * @param {string} [oArg.authtype] Must be one of 'sasl' (default), 'nonsasl', 'saslanon', or 'anonymous'.
 * @param {string} [oArg.xmllang] The requested language for this login. Typically XMPP server try to respond with error messages and the like in this language if available.
 */
JSJaCWebSocketConnection.prototype.connect = function(oArg) {
  this._setStatus('connecting');

  this.domain = oArg.domain || 'localhost';
  this.username = oArg.username;
  this.resource = oArg.resource;
  this.pass = oArg.password || oArg.pass;
  this.authzid = oArg.authzid || '';
  this.register = oArg.register;

  this.authhost = oArg.authhost || this.domain;
  this.authtype = oArg.authtype || 'sasl';

  this.jid = this.username + '@' + this.domain;
  this.fulljid = this.jid + '/' + this.resource;

  if (oArg.allow_plain) {
    this._allow_plain = oArg.allow_plain;
  } else {
    this._allow_plain = JSJAC_ALLOW_PLAIN;
  }

  if (oArg.allow_scram) {
    this._allow_scram = oArg.allow_scram;
  } else {
    this._allow_scram = JSJAC_ALLOW_SCRAM;
  }

  if (oArg.xmllang && oArg.xmllang !== '') {
    this._xmllang = oArg.xmllang;
  } else {
    this._xmllang = 'en';
  }

  if (typeof WebSocket === 'undefined') {
    this._handleEvent('onerror', JSJaCError('503', 'cancel', 'service-unavailable'));
    return;
  }

  this._ws = new WebSocket(this._httpbase, 'xmpp');
  this._ws.onclose = JSJaC.bind(this._onclose, this);
  this._ws.onerror = JSJaC.bind(this._onerror, this);
  this._ws.onopen = JSJaC.bind(this._onopen, this);
};

/**
 * @private
 */
JSJaCWebSocketConnection.prototype._onopen = function() {
  var reqstr = this._getInitialRequestString();

  this.oDbg.log(reqstr, 4);

  this._ws.onmessage = JSJaC.bind(this._handleOpenStream, this);
  this._ws.send(reqstr);
};

/**
 * @private
 */
JSJaCWebSocketConnection.prototype._handleOpenStream = function(event) {
  var open, stream;

  this.oDbg.log(event.data, 4);

  open = event.data;
  // skip XML prolog if any
  open = open.substr(open.indexOf('<stream:stream'));
  if (open.substr(-2) !== '/>' && open.substr(-16) !== '</stream:stream>') {
    // some servers send closed opening tag, some not
    open += '</stream:stream>';
  }
  stream = this._parseXml(open);
  if(!stream) {
    this._handleEvent('onerror', JSJaCError('503', 'cancel', 'service-unavailable'));
    return;
  }

  // extract stream id used for non-SASL authentication
  this.streamid = stream.getAttribute('id');

  this.oDbg.log('got streamid: ' + this.streamid, 2);
  this._ws.onmessage = JSJaC.bind(this._handleInitialResponse, this);
};

/**
 * @private
 */
JSJaCWebSocketConnection.prototype._handleInitialResponse = function(event) {
  var doc = this._parseXml(event.data);
  if (!this._parseStreamFeatures(doc)) {
    this._handleEvent('onerror', JSJaCError('503', 'cancel', 'service-unavailable'));
    return;
  }

  this._connected = true;

  if (this.register) {
    this._doInBandReg();
  } else {
    this._doAuth();
  }
};

/**
 * Disconnect from XMPP service
 *
 * When called upon leaving a page needs to use 'onbeforeunload' event
 * as Websocket would be closed already otherwise prior to this call.
 */
JSJaCWebSocketConnection.prototype.disconnect = function() {
  this._setStatus('disconnecting');

  if (!this.connected()) {
    return;
  }
  this._connected = false;

  this.oDbg.log('Disconnecting', 4);
  this._sendRaw('</stream:stream>', JSJaC.bind(this._cleanupWebSocket, this));

  this.oDbg.log('Disconnected', 2);
  this._handleEvent('ondisconnect');
};

/**
 * @private
 */
JSJaCWebSocketConnection.prototype._onclose = function() {
  this.oDbg.log('websocket closed', 2);
  if (this._status !== 'disconnecting') {
    this._connected = false;
    this._handleEvent('onerror', JSJaCError('503', 'cancel', 'service-unavailable'));
  }
};

/**
 * @private
 */
JSJaCWebSocketConnection.prototype._onerror = function() {
  this.oDbg.log('websocket error', 1);
  this._connected = false;
  this._handleEvent('onerror', JSJaCError('503', 'cancel', 'service-unavailable'));
};

/**
 * @private
 */
JSJaCWebSocketConnection.prototype._onmessage = function(event) {
  var stanza, node, packet;

  stanza = event.data;
  this._setStatus('processing');
  if (!stanza || stanza === '') {
    return;
  }

  // WebSocket works only on modern browsers, so it is safe to assume
  // that namespaceURI and getElementsByTagNameNS are available.
  node = this._parseXml(stanza);
  if (node.namespaceURI === NS_STREAM && node.localName === 'error') {
    if (node.getElementsByTagNameNS(NS_STREAMS, 'conflict').length > 0) {
      this._setStatus('session-terminate-conflict');
    }
    this._connected = false;
    this._handleEvent('onerror', JSJaCError('503', 'cancel', 'remote-stream-error'));
    return;
  }

  packet = JSJaCPacket.wrapNode(node);
  if (!packet) {
    return;
  }

  this.oDbg.log('async recv: ' + event.data, 4);
  this._handleEvent('packet_in', packet);

  if (packet.pType && !this._handlePID(packet)) {
    this._handleEvent(packet.pType() + '_in', packet);
    this._handleEvent(packet.pType(), packet);
  }
};

/**
 * Parse single XML stanza. As proposed in XMPP Sub-protocol for
 * WebSocket draft, it assumes that every stanza is sent in a separate
 * WebSocket frame, which greatly simplifies parsing.
 * @private
 */
JSJaCWebSocketConnection.prototype._parseXml = function(s) {
  var doc;

  this.oDbg.log('Parsing: ' + s, 4);
  try {
    doc = XmlDocument.create('stream', NS_STREAM);
    if(s.trim() == '</stream:stream>') {
      // Consider session as closed
      this.oDbg.log("session terminated", 1);

      clearTimeout(this._timeout); // remove timer
      clearInterval(this._interval);
      clearInterval(this._inQto);

      try {
        DataStore.removeDB(MINI_HASH, 'jsjac', 'state');
      } catch (e) {}

      this._connected = false;
      this._handleEvent('onerror',JSJaCError('503','cancel','session-terminate'));

      this.oDbg.log("Disconnected.",1);
      this._handleEvent('ondisconnect');

      return null;
    } else if(s.indexOf('<stream:stream') === -1) {
      // Wrap every stanza into stream element, so that XML namespaces work properly.
      doc.loadXML("<stream:stream xmlns:stream='" + NS_STREAM + "' xmlns='jabber:client'>" + s + "</stream:stream>");
      return doc.documentElement.firstChild;
    } else {
      doc.loadXML(s);
      return doc.documentElement;
    }
  } catch (e) {
    this.oDbg.log('Error: ' + e);
    this._connected = false;
    this._handleEvent('onerror', JSJaCError('500', 'wait', 'internal-service-error'));
  }

  return null;
};

/**
 * @private
 */
JSJaCWebSocketConnection.prototype._getInitialRequestString = function() {
  var streamto, reqstr;

  streamto = this.domain;
  if (this.authhost) {
    streamto = this.authhost;
  }

  reqstr = '<stream:stream to="' + streamto + '" xmlns="jabber:client" xmlns:stream="' + NS_STREAM + '"';
  if (this.authtype === 'sasl' || this.authtype === 'saslanon') {
    reqstr += ' version="1.0"';
  }
  reqstr += '>';
  return reqstr;
};

JSJaCWebSocketConnection.prototype.send = function(packet, cb, arg) {
  this._ws.onmessage = JSJaC.bind(this._onmessage, this);
  if (!packet || !packet.pType) {
    this.oDbg.log('no packet: ' + packet, 1);
    return false;
  }

  if (!this.connected()) {
    return false;
  }

  // remember id for response if callback present
  if (cb) {
    if (!packet.getID()) {
      packet.setID('JSJaCID_' + this._ID++); // generate an ID
    }

    // register callback with id
    this._registerPID(packet, cb, arg);
  }

  try {
    this._handleEvent(packet.pType() + '_out', packet);
    this._handleEvent('packet_out', packet);
    this._ws.send(packet.xml());
  } catch (e) {
    this.oDbg.log(e.toString(), 1);
    return false;
  }

  return true;
};

/**
 * Resuming connections is not supported by WebSocket.
 */
JSJaCWebSocketConnection.prototype.resume = function() {
  return false; // not supported for websockets
};

/**
 * Suspending connections is not supported by WebSocket.
 */
JSJaCWebSocketConnection.prototype.suspend = function() {
  return false; // not supported for websockets
};

/**
 * @private
 */
JSJaCWebSocketConnection.prototype._doSASLAuthScramSha1S1 = function(event) {
  var el = this._parseXml(event.data);
  return JSJaC.bind(JSJaCConnection.prototype._doSASLAuthScramSha1S1, this)(el);
};

/**
 * @private
 */
JSJaCWebSocketConnection.prototype._doSASLAuthScramSha1S2 = function(event) {
  var el = this._parseXml(event.data);
  return JSJaC.bind(JSJaCConnection.prototype._doSASLAuthScramSha1S2, this)(el);
};

/**
 * @private
 */
JSJaCWebSocketConnection.prototype._doSASLAuthDigestMd5S1 = function(event) {
  var el = this._parseXml(event.data);
  return JSJaC.bind(JSJaCConnection.prototype._doSASLAuthDigestMd5S1, this)(el);
};

/**
 * @private
 */
JSJaCWebSocketConnection.prototype._doSASLAuthDigestMd5S2 = function(event) {
  var el = this._parseXml(event.data);
  return JSJaC.bind(JSJaCConnection.prototype._doSASLAuthDigestMd5S2, this)(el);
};

/**
 * @private
 */
JSJaCWebSocketConnection.prototype._doSASLAuthDone = function(event) {
  var el = this._parseXml(event.data);
  return JSJaC.bind(JSJaCConnection.prototype._doSASLAuthDone, this)(el);
};

/**
 * @private
 */
JSJaCWebSocketConnection.prototype._reInitStream = function(cb) {
  var reqstr, streamto = this.domain;
  if (this.authhost) {
    streamto = this.authhost;
  }

  reqstr = '<stream:stream xmlns:stream="' + NS_STREAM + '" xmlns="jabber:client" to="' + streamto + '" version="1.0">';
  this._sendRaw(reqstr, cb);
};

/**
 * @private
 */
JSJaCWebSocketConnection.prototype._sendRaw = function(xml, cb, arg) {
  if (!this._ws) {
    // Socket might have been closed already because of an 'onerror'
    // event. In this case we'd try to send a closing stream element
    // 'ondisconnect' which won't work.
    return false;
  }
  if (cb) {
    this._ws.onmessage = JSJaC.bind(cb, this, arg);
  }
  this._ws.send(xml);
  return true;
};

/*exported JSJaCUtils */

/**
 * Various utilities put together so that they don't pollute global
 * name space.
 * @namespace
 */
var JSJaCUtils = {
  /**
   * XOR two strings of equal length.
   * @param {string} s1 first string to XOR.
   * @param {string} s2 second string to XOR.
   * @return {string} s1 ^ s2.
   */
  xor: function(s1, s2) {
    /*jshint bitwise: false */
    if(!s1) {
      return s2;
    }
    if(!s2) {
      return s1;
    }

    var result = '';
    for(var i = 0; i < s1.length; i++) {
      result += String.fromCharCode(s1.charCodeAt(i) ^ s2.charCodeAt(i));
    }
    return result;
  },

  /**
   * Create nonce value of given size.
   * @param {int} size size of the nonce that should be generated.
   * @return {string} generated nonce.
   */
  cnonce: function(size) {
    var tab = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
    var cnonce = '';
    for (var i = 0; i < size; i++) {
      cnonce += tab.charAt(Math.round(Math.random(new Date().getTime()) * (tab.length - 1)));
    }
    return cnonce;
  },

  /**
   * Current timestamp.
   * @return Seconds since 1.1.1970.
   * @type int
   */
  now: function() {
    if (Date.now && typeof Date.now == 'function') {
      return Date.now();
    } else {
      return new Date().getTime();
    }
  }

};
// License: MIT

/**
 * jQuery JSON plugin 2.4.0
 *
 * @author Brantley Harris, 2009-2011
 * @author Timo Tijhof, 2011-2012
 * @source This plugin is heavily influenced by MochiKit's serializeJSON, which is
 *         copyrighted 2005 by Bob Ippolito.
 * @source Brantley Harris wrote this plugin. It is based somewhat on the JSON.org
 *         website's http://www.json.org/json2.js, which proclaims:
 *         "NO WARRANTY EXPRESSED OR IMPLIED. USE AT YOUR OWN RISK.", a sentiment that
 *         I uphold.
 * @license MIT License <http://www.opensource.org/licenses/mit-license.php>
 */
(function ($) {
    'use strict';

    var escape = /["\\\x00-\x1f\x7f-\x9f]/g,
        meta = {
            '\b': '\\b',
            '\t': '\\t',
            '\n': '\\n',
            '\f': '\\f',
            '\r': '\\r',
            '"' : '\\"',
            '\\': '\\\\'
        },
        hasOwn = Object.prototype.hasOwnProperty;

    /**
     * jQuery.toJSON
     * Converts the given argument into a JSON representation.
     *
     * @param o {Mixed} The json-serializable *thing* to be converted
     *
     * If an object has a toJSON prototype, that will be used to get the representation.
     * Non-integer/string keys are skipped in the object, as are keys that point to a
     * function.
     *
     */
    $.toJSON = typeof JSON === 'object' && JSON.stringify ? JSON.stringify : function (o) {
        if (o === null) {
            return 'null';
        }

        var pairs, k, name, val,
            type = $.type(o);

        if (type === 'undefined') {
            return undefined;
        }

        // Also covers instantiated Number and Boolean objects,
        // which are typeof 'object' but thanks to $.type, we
        // catch them here. I don't know whether it is right
        // or wrong that instantiated primitives are not
        // exported to JSON as an {"object":..}.
        // We choose this path because that's what the browsers did.
        if (type === 'number' || type === 'boolean') {
            return String(o);
        }
        if (type === 'string') {
            return $.quoteString(o);
        }
        if (typeof o.toJSON === 'function') {
            return $.toJSON(o.toJSON());
        }
        if (type === 'date') {
            var month = o.getUTCMonth() + 1,
                day = o.getUTCDate(),
                year = o.getUTCFullYear(),
                hours = o.getUTCHours(),
                minutes = o.getUTCMinutes(),
                seconds = o.getUTCSeconds(),
                milli = o.getUTCMilliseconds();

            if (month < 10) {
                month = '0' + month;
            }
            if (day < 10) {
                day = '0' + day;
            }
            if (hours < 10) {
                hours = '0' + hours;
            }
            if (minutes < 10) {
                minutes = '0' + minutes;
            }
            if (seconds < 10) {
                seconds = '0' + seconds;
            }
            if (milli < 100) {
                milli = '0' + milli;
            }
            if (milli < 10) {
                milli = '0' + milli;
            }
            return '"' + year + '-' + month + '-' + day + 'T' +
                hours + ':' + minutes + ':' + seconds +
                '.' + milli + 'Z"';
        }

        pairs = [];

        if ($.isArray(o)) {
            for (k = 0; k < o.length; k++) {
                pairs.push($.toJSON(o[k]) || 'null');
            }
            return '[' + pairs.join(',') + ']';
        }

        // Any other object (plain object, RegExp, ..)
        // Need to do typeof instead of $.type, because we also
        // want to catch non-plain objects.
        if (typeof o === 'object') {
            for (k in o) {
                // Only include own properties,
                // Filter out inherited prototypes
                if (hasOwn.call(o, k)) {
                    // Keys must be numerical or string. Skip others
                    type = typeof k;
                    if (type === 'number') {
                        name = '"' + k + '"';
                    } else if (type === 'string') {
                        name = $.quoteString(k);
                    } else {
                        continue;
                    }
                    type = typeof o[k];

                    // Invalid values like these return undefined
                    // from toJSON, however those object members
                    // shouldn't be included in the JSON string at all.
                    if (type !== 'function' && type !== 'undefined') {
                        val = $.toJSON(o[k]);
                        pairs.push(name + ':' + val);
                    }
                }
            }
            return '{' + pairs.join(',') + '}';
        }
    };

    /**
     * jQuery.evalJSON
     * Evaluates a given json string.
     *
     * @param str {String}
     */
    $.evalJSON = typeof JSON === 'object' && JSON.parse ? JSON.parse : function (str) {
        /*jshint evil: true */
        return eval('(' + str + ')');
    };

    /**
     * jQuery.secureEvalJSON
     * Evals JSON in a way that is *more* secure.
     *
     * @param str {String}
     */
    $.secureEvalJSON = typeof JSON === 'object' && JSON.parse ? JSON.parse : function (str) {
        var filtered =
            str
            .replace(/\\["\\\/bfnrtu]/g, '@')
            .replace(/"[^"\\\n\r]*"|true|false|null|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?/g, ']')
            .replace(/(?:^|:|,)(?:\s*\[)+/g, '');

        if (/^[\],:{}\s]*$/.test(filtered)) {
            /*jshint evil: true */
            return eval('(' + str + ')');
        }
        throw new SyntaxError('Error parsing JSON, source is not valid.');
    };

    /**
     * jQuery.quoteString
     * Returns a string-repr of a string, escaping quotes intelligently.
     * Mostly a support function for toJSON.
     * Examples:
     * >>> jQuery.quoteString('apple')
     * "apple"
     *
     * >>> jQuery.quoteString('"Where are we going?", she asked.')
     * "\"Where are we going?\", she asked."
     */
    $.quoteString = function (str) {
        if (str.match(escape)) {
            return '"' + str.replace(escape, function (a) {
                var c = meta[a];
                if (typeof c === 'string') {
                    return c;
                }
                c = a.charCodeAt();
                return '\\u00' + Math.floor(c / 16).toString(16) + (c % 16).toString(16);
            }) + '"';
        }
        return '"' + str + '"';
    };

}(jQuery));
/**
 * jQuery.timers - Timer abstractions for jQuery
 * Written by Blair Mitchelmore (blair DOT mitchelmore AT gmail DOT com)
 * Licensed under the WTFPL (http://sam.zoy.org/wtfpl/).
 * Date: 2009/10/16
 *
 * @author Blair Mitchelmore
 * @version 1.2
 *
 **/

jQuery.fn.extend({
    everyTime: function(interval, label, fn, times) {
        return this.each(function() {
            jQuery.timer.add(this, interval, label, fn, times);
        });
    },
    oneTime: function(interval, label, fn) {
        return this.each(function() {
            jQuery.timer.add(this, interval, label, fn, 1);
        });
    },
    stopTime: function(label, fn) {
        return this.each(function() {
            jQuery.timer.remove(this, label, fn);
        });
    }
});

jQuery.extend({
    timer: {
        global: [],
        guid: 1,
        dataKey: "jQuery.timer",
        regex: /^([0-9]+(?:\.[0-9]*)?)\s*(.*s)?$/,
        powers: {
            // Yeah this is major overkill...
            'ms': 1,
            'cs': 10,
            'ds': 100,
            's': 1000,
            'das': 10000,
            'hs': 100000,
            'ks': 1000000
        },
        timeParse: function(value) {
            if (value == undefined || value == null)
                return null;
            var result = this.regex.exec(jQuery.trim(value.toString()));
            if (result[2]) {
                var num = parseFloat(result[1]);
                var mult = this.powers[result[2]] || 1;
                return num * mult;
            } else {
                return value;
            }
        },
        add: function(element, interval, label, fn, times) {
            var counter = 0;

            if (jQuery.isFunction(label)) {
                if (!times)
                    times = fn;
                fn = label;
                label = interval;
            }

            interval = jQuery.timer.timeParse(interval);

            if (typeof interval != 'number' || isNaN(interval) || interval < 0)
                return;

            if (typeof times != 'number' || isNaN(times) || times < 0)
                times = 0;

            times = times || 0;

            var timers = jQuery.data(element, this.dataKey) || jQuery.data(element, this.dataKey, {});

            if (!timers[label])
                timers[label] = {};

            fn.timerID = fn.timerID || this.guid++;

            var handler = function() {
                if ((++counter > times && times !== 0) || fn.call(element, counter) === false)
                    jQuery.timer.remove(element, label, fn);
            };

            handler.timerID = fn.timerID;

            if (!timers[label][fn.timerID])
                timers[label][fn.timerID] = window.setInterval(handler,interval);

            this.global.push( element );

        },
        remove: function(element, label, fn) {
            var timers = jQuery.data(element, this.dataKey), ret;

            if ( timers ) {

                if (!label) {
                    for ( label in timers )
                        this.remove(element, label, fn);
                } else if ( timers[label] ) {
                    if ( fn ) {
                        if ( fn.timerID ) {
                            window.clearInterval(timers[label][fn.timerID]);
                            delete timers[label][fn.timerID];
                        }
                    } else {
                        for ( var fn in timers[label] ) {
                            window.clearInterval(timers[label][fn]);
                            delete timers[label][fn];
                        }
                    }

                    for ( ret in timers[label] ) break;
                    if ( !ret ) {
                        ret = null;
                        delete timers[label];
                    }
                }

                for ( ret in timers ) break;
                if ( !ret )
                    jQuery.removeData(element, this.dataKey);
            }
        }
    }
});

jQuery(window).bind("unload", function() {
    jQuery.each(jQuery.timer.global, function(index, item) {
        jQuery.timer.remove(item);
    });
});
/*!
 * jQuery.ScrollTo
 * Copyright (c) 2007-2014 Ariel Flesler - aflesler<a>gmail<d>com | http://flesler.blogspot.com
 * Licensed under MIT
 * http://flesler.blogspot.com/2007/10/jqueryscrollto.html
 * @projectDescription Easy element scrolling using jQuery.
 * @author Ariel Flesler
 * @version 1.4.9
 */

;(function (factory) {
    // AMD Support
    if (typeof define === 'function' && define.amd) {
        define(['jquery'], factory);
    } else {
        factory(jQuery);
    }
}(function ($) {

    var $scrollTo = $.scrollTo = function( target, duration, settings ) {
        return $(window).scrollTo( target, duration, settings );
    };

    $scrollTo.defaults = {
        axis:'xy',
        duration: parseFloat($.fn.jquery) >= 1.3 ? 0 : 1,
        limit:true
    };

    // Returns the element that needs to be animated to scroll the window.
    // Kept for backwards compatibility (specially for localScroll & serialScroll)
    $scrollTo.window = function( scope ) {
        return $(window)._scrollable();
    };

    // Hack, hack, hack :)
    // Returns the real elements to scroll (supports window/iframes, documents and regular nodes)
    $.fn._scrollable = function() {
        return this.map(function() {
            var elem = this,
                isWin = !elem.nodeName || $.inArray( elem.nodeName.toLowerCase(), ['iframe','#document','html','body'] ) != -1;

                if (!isWin)
                    return elem;

            var doc = (elem.contentWindow || elem).document || elem.ownerDocument || elem;

            return /webkit/i.test(navigator.userAgent) || doc.compatMode == 'BackCompat' ?
                doc.body :
                doc.documentElement;
        });
    };

    $.fn.scrollTo = function( target, duration, settings ) {
        if (typeof duration == 'object') {
            settings = duration;
            duration = 0;
        }
        if (typeof settings == 'function')
            settings = { onAfter:settings };

        if (target == 'max')
            target = 9e9;

        settings = $.extend( {}, $scrollTo.defaults, settings );
        // Speed is still recognized for backwards compatibility
        duration = duration || settings.duration;
        // Make sure the settings are given right
        settings.queue = settings.queue && settings.axis.length > 1;

        if (settings.queue)
            // Let's keep the overall duration
            duration /= 2;
        settings.offset = both( settings.offset );
        settings.over = both( settings.over );

        return this._scrollable().each(function() {
            // Null target yields nothing, just like jQuery does
            if (target == null) return;

            var elem = this,
                $elem = $(elem),
                targ = target, toff, attr = {},
                win = $elem.is('html,body');

            switch (typeof targ) {
                // A number will pass the regex
                case 'number':
                case 'string':
                    if (/^([+-]=?)?\d+(\.\d+)?(px|%)?$/.test(targ)) {
                        targ = both( targ );
                        // We are done
                        break;
                    }
                    // Relative selector, no break!
                    targ = $(targ,this);
                    if (!targ.length) return;
                case 'object':
                    // DOMElement / jQuery
                    if (targ.is || targ.style)
                        // Get the real position of the target
                        toff = (targ = $(targ)).offset();
            }
            
            var offset = $.isFunction(settings.offset) && settings.offset(elem, targ) || settings.offset;
            
            $.each( settings.axis.split(''), function( i, axis ) {
                var Pos = axis == 'x' ? 'Left' : 'Top',
                    pos = Pos.toLowerCase(),
                    key = 'scroll' + Pos,
                    old = elem[key],
                    max = $scrollTo.max(elem, axis);

                if (toff) {// jQuery / DOMElement
                    attr[key] = toff[pos] + ( win ? 0 : old - $elem.offset()[pos] );

                    // If it's a dom element, reduce the margin
                    if (settings.margin) {
                        attr[key] -= parseInt(targ.css('margin'+Pos)) || 0;
                        attr[key] -= parseInt(targ.css('border'+Pos+'Width')) || 0;
                    }

                    attr[key] += offset[pos] || 0;

                    if(settings.over[pos])
                        // Scroll to a fraction of its width/height
                        attr[key] += targ[axis=='x'?'width':'height']() * settings.over[pos];
                } else {
                    var val = targ[pos];
                    // Handle percentage values
                    attr[key] = val.slice && val.slice(-1) == '%' ?
                        parseFloat(val) / 100 * max
                        : val;
                }

                // Number or 'number'
                if (settings.limit && /^\d+$/.test(attr[key]))
                    // Check the limits
                    attr[key] = attr[key] <= 0 ? 0 : Math.min( attr[key], max );

                // Queueing axes
                if (!i && settings.queue) {
                    // Don't waste time animating, if there's no need.
                    if (old != attr[key])
                        // Intermediate animation
                        animate( settings.onAfterFirst );
                    // Don't animate this axis again in the next iteration.
                    delete attr[key];
                }
            });

            animate( settings.onAfter );

            function animate( callback ) {
                $elem.animate( attr, duration, settings.easing, callback && function() {
                    callback.call(this, targ, settings);
                });
            };

        }).end();
    };

    // Max scrolling position, works on quirks mode
    // It only fails (not too badly) on IE, quirks mode.
    $scrollTo.max = function( elem, axis ) {
        var Dim = axis == 'x' ? 'Width' : 'Height',
            scroll = 'scroll'+Dim;

        if (!$(elem).is('html,body'))
            return elem[scroll] - $(elem)[Dim.toLowerCase()]();

        var size = 'client' + Dim,
            html = elem.ownerDocument.documentElement,
            body = elem.ownerDocument.body;

        return Math.max( html[scroll], body[scroll] )
             - Math.min( html[size]  , body[size]   );
    };

    function both( val ) {
        return $.isFunction(val) || typeof val == 'object' ? val : { top:val, left:val };
    };

    // AMD requirement
    return $scrollTo;
}));
/*

Jappix - An open social platform
These are the system JS script for Jappix

-------------------------------------------------

License: dual-licensed under AGPL and MPLv2
Authors: Valérian Saliou, olivierm, regilero, Maranda

*/

// Bundle
var System = (function () {

    /**
     * Alias of this
     * @private
     */
    var self = {};


    /**
     * Gets the current app location
     * @public
     * @return {string}
     */
    self.location = function() {

        try {
            var url = window.location.href;

            // If the URL has variables, remove them
            if(url.indexOf('?') != -1) {
                url = url.split('?')[0];
            }

            if(url.indexOf('#') != -1) {
                url = url.split('#')[0];
            }

            // No "/" at the end
            if(!url.match(/(.+)\/$/)) {
                url += '/';
            }

            return url;
        } catch(e) {
            Console.error('System.location', e);
        }

    };


    /**
     * Checks if we are in developer mode
     * @public
     * @return {boolean}
     */
    self.isDeveloper = function() {

        try {
            return true;
        } catch(e) {
            Console.error('System.isDeveloper', e);
        }

    };


    /**
     * Return class scope
     */
    return self;

})();

var JappixSystem = System;/*

Jappix - An open social platform
These are the constants JS scripts for Jappix

-------------------------------------------------

License: dual-licensed under AGPL and MPLv2
Authors: Stefan Strigler, Valérian Saliou, Kloadut, Maranda

*/

// XMPP XMLNS attributes
var NS_PROTOCOL =      'http://jabber.org/protocol/';
var NS_FEATURES =      'http://jabber.org/features/';
var NS_CLIENT =        'jabber:client';
var NS_IQ =            'jabber:iq:';
var NS_X =             'jabber:x:';
var NS_IETF =          'urn:ietf:params:xml:ns:';
var NS_IETF_XMPP =     NS_IETF + 'xmpp-';
var NS_XMPP =          'urn:xmpp:';

var NS_STORAGE =       'storage:';
var NS_BOOKMARKS =     NS_STORAGE + 'bookmarks';
var NS_ROSTERNOTES =   NS_STORAGE + 'rosternotes';

var NS_JAPPIX =        'jappix:';
var NS_INBOX =         NS_JAPPIX + 'inbox';
var NS_OPTIONS =       NS_JAPPIX + 'options';

var NS_DISCO_ITEMS =   NS_PROTOCOL + 'disco#items';
var NS_DISCO_INFO =    NS_PROTOCOL + 'disco#info';
var NS_VCARD =         'vcard-temp';
var NS_VCARD_P =       NS_VCARD + ':x:update';
var NS_IETF_VCARD4 =   NS_IETF + 'vcard-4.0';
var NS_XMPP_VCARD4 =   NS_XMPP + 'vcard4';
var NS_URN_ADATA =     NS_XMPP + 'avatar:data';
var NS_URN_AMETA =     NS_XMPP + 'avatar:metadata';
var NS_AUTH =          NS_IQ + 'auth';
var NS_AUTH_ERROR =    NS_IQ + 'auth:error';
var NS_REGISTER =      NS_IQ + 'register';
var NS_SEARCH =        NS_IQ + 'search';
var NS_ROSTER =        NS_IQ + 'roster';
var NS_PRIVACY =       NS_IQ + 'privacy';
var NS_PRIVATE =       NS_IQ + 'private';
var NS_VERSION =       NS_IQ + 'version';
var NS_TIME =          NS_IQ + 'time';
var NS_LAST =          NS_IQ + 'last';
var NS_IQDATA =        NS_IQ + 'data';
var NS_XDATA =         NS_X + 'data';
var NS_IQOOB =         NS_IQ + 'oob';
var NS_XOOB =          NS_X + 'oob';
var NS_DELAY =         NS_X + 'delay';
var NS_EXPIRE =        NS_X + 'expire';
var NS_EVENT =         NS_X + 'event';
var NS_XCONFERENCE =   NS_X + 'conference';
var NS_STATS =         NS_PROTOCOL + 'stats';
var NS_MUC =           NS_PROTOCOL + 'muc';
var NS_MUC_USER =      NS_MUC + '#user';
var NS_MUC_ADMIN =     NS_MUC + '#admin';
var NS_MUC_OWNER =     NS_MUC + '#owner';
var NS_MUC_CONFIG =    NS_MUC + '#roomconfig';
var NS_PUBSUB =        NS_PROTOCOL + 'pubsub';
var NS_PUBSUB_EVENT =  NS_PUBSUB + '#event';
var NS_PUBSUB_OWNER =  NS_PUBSUB + '#owner';
var NS_PUBSUB_NMI =    NS_PUBSUB + '#node-meta-info';
var NS_PUBSUB_NC =     NS_PUBSUB + '#node_config';
var NS_PUBSUB_CN =     NS_PUBSUB + '#config-node';
var NS_PUBSUB_RI =     NS_PUBSUB + '#retrieve-items';
var NS_COMMANDS =      NS_PROTOCOL + 'commands';
var NS_BOSH =          NS_PROTOCOL + 'httpbind';
var NS_STREAM =       'http://etherx.jabber.org/streams';
var NS_URN_TIME =      NS_XMPP + 'time';
var NS_URN_PING =      NS_XMPP + 'ping';
var NS_URN_MBLOG =     NS_XMPP + 'microblog:0';
var NS_URN_INBOX =     NS_XMPP + 'inbox';
var NS_URN_FORWARD =   NS_XMPP + 'forward:0';
var NS_URN_MAM =       NS_XMPP + 'mam:tmp';
var NS_URN_DELAY =     NS_XMPP + 'delay';
var NS_URN_RECEIPTS =  NS_XMPP + 'receipts';
var NS_URN_CARBONS =   NS_XMPP + 'carbons:2';
var NS_URN_CORRECT =   NS_XMPP + 'message-correct:0';
var NS_URN_IDLE =      NS_XMPP + 'idle:1';
var NS_URN_REACH =     NS_XMPP + 'reach:0';
var NS_URN_MARKERS =   NS_XMPP + 'chat-markers:0';
var NS_URN_ATTENTION = NS_XMPP + 'attention:0';
var NS_URN_HINTS =     NS_XMPP + 'hints';
var NS_RSM =           NS_PROTOCOL + 'rsm';
var NS_IPV6 =          'ipv6';
var NS_XHTML =         'http://www.w3.org/1999/xhtml';
var NS_XHTML_IM =      NS_PROTOCOL + 'xhtml-im';
var NS_CHATSTATES =    NS_PROTOCOL + 'chatstates';
var NS_HTTP_AUTH =     NS_PROTOCOL + 'http-auth';
var NS_ROSTERX =       NS_PROTOCOL + 'rosterx';
var NS_MOOD =          NS_PROTOCOL + 'mood';
var NS_ACTIVITY =      NS_PROTOCOL + 'activity';
var NS_TUNE =          NS_PROTOCOL + 'tune';
var NS_GEOLOC =        NS_PROTOCOL + 'geoloc';
var NS_NICK =          NS_PROTOCOL + 'nick';
var NS_NOTIFY =        '+notify';
var NS_CAPS =          NS_PROTOCOL + 'caps';
var NS_ATOM =          'http://www.w3.org/2005/Atom';

var NS_STANZAS =       NS_IETF_XMPP + 'stanzas';
var NS_STREAMS =       NS_IETF_XMPP + 'streams';

var NS_TLS =           NS_IETF_XMPP + 'tls';
var NS_SASL =          NS_IETF_XMPP + 'sasl';
var NS_SESSION =       NS_IETF_XMPP + 'session';
var NS_BIND =          NS_IETF_XMPP + 'bind';

var NS_FEATURE_IQAUTH =     NS_FEATURES + 'iq-auth';
var NS_FEATURE_IQREGISTER = NS_FEATURES + 'iq-register';
var NS_FEATURE_COMPRESS =   NS_FEATURES + 'compress';

var NS_COMPRESS = NS_PROTOCOL + 'compress';

var NS_METRONOME_MAM_PURGE = 'http://metronome.im/protocol/mam-purge';

// Available locales
var LOCALES_AVAILABLE_ID = [];
var LOCALES_AVAILABLE_NAMES = [];

// XML lang
var XML_LANG = null;

// Jappix parameters
var JAPPIX_STATIC = null;
var JAPPIX_VERSION = null;
var JAPPIX_MAX_FILE_SIZE = null;
var JAPPIX_MAX_UPLOAD = null;

// Jappix main configuration
var SERVICE_NAME = null;
var SERVICE_DESC = null;
var OWNER_NAME = null;
var OWNER_WEBSITE = null;
var LEGAL = null;
var JAPPIX_RESOURCE = null;
var LOCK_HOST = null;
var ANONYMOUS = null;
var HTTP_AUTH = null;
var REGISTRATION = null;
var BOSH_PROXY = null;
var MANAGER_LINK = null;
var GROUPCHATS_JOIN = null;
var GROUPCHATS_SUGGEST = null;
var ENCRYPTION = null;
var HTTPS_STORAGE = null;
var HTTPS_FORCE = null;
var COMPRESSION = null;
var ADS_ENABLE = null;
var GADS_CLIENT = null;
var GADS_SLOT = null;
var MULTI_FILES = null;
var DEVELOPER = null;
var REGISTER_API = null;

// Jappix hosts configuration
var HOST_MAIN = null;
var HOST_MUC = null;
var HOST_PUBSUB = null;
var HOST_VJUD = null;
var HOST_ANONYMOUS = null;
var HOST_STUN = null;
var HOST_TURN = null;
var HOST_TURN_USERNAME = null;
var HOST_TURN_PASSWORD = null;
var HOST_BOSH = null;
var HOST_BOSH_MAIN = null;
var HOST_BOSH_MINI = null;
var HOST_WEBSOCKET = null;
var HOST_STATIC = null;
var HOST_UPLOAD = null;

// Anonymous mode
var ANONYMOUS_ROOM = null;
var ANONYMOUS_NICK = null;

// Node parameters
var JAPPIX_LOCATION = JappixSystem.location();
var JAPPIX_MINI_CSS = null;
var BOSH_SAME_ORIGIN = false;

// XMPP error stanzas
function STANZA_ERROR(code, type, cond) {
    if(window == this) {
        return new STANZA_ERROR(code, type, cond);
    }

    this.code = code;
    this.type = type;
    this.cond = cond;
}

var ERR_BAD_REQUEST =
    STANZA_ERROR('400', 'modify', 'bad-request');
var ERR_CONFLICT =
    STANZA_ERROR('409', 'cancel', 'conflict');
var ERR_FEATURE_NOT_IMPLEMENTED =
    STANZA_ERROR('501', 'cancel', 'feature-not-implemented');
var ERR_FORBIDDEN =
    STANZA_ERROR('403', 'auth',   'forbidden');
var ERR_GONE =
    STANZA_ERROR('302', 'modify', 'gone');
var ERR_INTERNAL_SERVER_ERROR =
    STANZA_ERROR('500', 'wait',   'internal-server-error');
var ERR_ITEM_NOT_FOUND =
    STANZA_ERROR('404', 'cancel', 'item-not-found');
var ERR_JID_MALFORMED =
    STANZA_ERROR('400', 'modify', 'jid-malformed');
var ERR_NOT_ACCEPTABLE =
    STANZA_ERROR('406', 'modify', 'not-acceptable');
var ERR_NOT_ALLOWED =
    STANZA_ERROR('405', 'cancel', 'not-allowed');
var ERR_NOT_AUTHORIZED =
    STANZA_ERROR('401', 'auth',   'not-authorized');
var ERR_PAYMENT_REQUIRED =
    STANZA_ERROR('402', 'auth',   'payment-required');
var ERR_RECIPIENT_UNAVAILABLE =
    STANZA_ERROR('404', 'wait',   'recipient-unavailable');
var ERR_REDIRECT =
    STANZA_ERROR('302', 'modify', 'redirect');
var ERR_REGISTRATION_REQUIRED =
    STANZA_ERROR('407', 'auth',   'registration-required');
var ERR_REMOTE_SERVER_NOT_FOUND =
    STANZA_ERROR('404', 'cancel', 'remote-server-not-found');
var ERR_REMOTE_SERVER_TIMEOUT =
    STANZA_ERROR('504', 'wait',   'remote-server-timeout');
var ERR_RESOURCE_CONSTRAINT =
    STANZA_ERROR('500', 'wait',   'resource-constraint');
var ERR_SERVICE_UNAVAILABLE =
    STANZA_ERROR('503', 'cancel', 'service-unavailable');
var ERR_SUBSCRIPTION_REQUIRED =
    STANZA_ERROR('407', 'auth',   'subscription-required');
var ERR_UNEXPECTED_REQUEST =
    STANZA_ERROR('400', 'wait',   'unexpected-request');
/*

Jappix - An open social platform
These are the temporary/persistent data store functions

-------------------------------------------------

License: dual-licensed under AGPL and MPLv2
Authors: Valérian Saliou, Maranda

*/

// Bundle
var DataStore = (function () {

    /**
     * Alias of this
     * @private
     */
    var self = {};


    /* Variables */
    self._db_emulated = {};
    self._persistent_emulated = {};


    /**
     * Common: storage adapter
     * @public
     * @param {object} storage_native
     * @param {object} storage_emulated
     * @return {undefined}
     */
    self._adapter = function(storage_native, storage_emulated) {

        try {
            var legacy = !storage_native;

            this.key = function(key) {
                if(legacy) {
                    if(key >= this.length) {
                        return null;
                    }

                    var c = 0;

                    for(var name in storage_emulated) {
                        if(c++ == key)  return name;
                    }

                    return null;
                }

                return storage_native.key(key);
            };

            this.getItem = function(key) {
                if(legacy) {
                    if(storage_emulated[key] !== undefined) {
                        return storage_emulated[key];
                    }

                    return null;
                } else {
                    return storage_native.getItem(key);
                }
            };

            this.setItem = function(key, data) {
                if(legacy) {
                    if(!(key in storage_emulated)) {
                        this.length++;
                    }

                    storage_emulated[key] = (data + '');
                } else {
                    storage_native.setItem(key, data);
                    this.length = storage_native.length;
                }
            };

            this.removeItem = function(key) {
                if(legacy) {
                    if(key in storage_emulated) {
                        this.length--;
                        delete storage_emulated[key];
                    }
                } else {
                    storage_native.removeItem(key);
                    this.length = storage_native.length;
                }
            };

            this.clear = function() {
                if(legacy) {
                    this.length = 0;
                    storage_emulated = {};
                } else {
                    storage_native.clear();
                    this.length = storage_native.length;
                }
            };

            this.length = legacy ? 0 : storage_native.length;
        } catch(e) {
            Console.error('DataStore._adapter', e);
        }

    };


    /**
     * Temporary: sessionStorage class alias for direct access
     */
    self.storageDB = new self._adapter(
        (window.sessionStorage ? sessionStorage : null),
        self._db_emulated
    );


    /**
     * Persistent: localStorage class alias for direct access
     */
    self.storagePersistent = new self._adapter(
        (window.localStorage ? localStorage : null),
        self._persistent_emulated
    );


    /**
     * Temporary: returns whether it is available or not
     * @public
     * @return {boolean}
     */
    self.hasDB = function() {

        var has_db = false;

        try {
            self.storageDB.setItem('hasdb_check', 'ok');
            self.storageDB.removeItem('hasdb_check');

            has_db = true;
        } catch(e) {
            Console.error('DataStore.hasDB', e);
        } finally {
            return has_db;
        }

    };


    /**
     * Temporary: used to read a database entry
     * @public
     * @param {string} dbID
     * @param {string} type
     * @param {string} id
     * @return {object}
     */
    self.getDB = function(dbID, type, id) {

        try {
            try {
                return self.storageDB.getItem(dbID + '_' + type + '_' + id);
            }

            catch(e) {
                Console.error('Error while getting a temporary database entry (' + dbID + ' -> ' + type + ' -> ' + id + ')', e);
            }

            return null;
        } catch(e) {
            Console.error('DataStore.getDB', e);
        }

    };


    /**
     * Temporary: used to update a database entry
     * @public
     * @param {string} dbID
     * @param {string} type
     * @param {string} id
     * @param {type} value
     * @return {boolean}
     */
    self.setDB = function(dbID, type, id, value) {

        try {
            try {
                self.storageDB.setItem(dbID + '_' + type + '_' + id, value);

                return true;
            }

            catch(e) {
                Console.error('Error while writing a temporary database entry (' + dbID + ' -> ' + type + ' -> ' + id + ')', e);
            }

            return false;
        } catch(e) {
            Console.error('DataStore.setDB', e);
        }

    };


    /**
     * Temporary: used to remove a database entry
     * @public
     * @param {string} dbID
     * @param {string} type
     * @param {string} id
     * @return {undefined}
     */
    self.removeDB = function(dbID, type, id) {

        try {
            try {
                self.storageDB.removeItem(dbID + '_' + type + '_' + id);

                return true;
            }

            catch(e) {
                Console.error('Error while removing a temporary database entry (' + dbID + ' -> ' + type + ' -> ' + id + ')', e);
            }

            return false;
        } catch(e) {
            Console.error('DataStore.removeDB', e);
        }

    };


    /**
     * Temporary: used to check a database entry exists
     * @public
     * @param {string} dbID
     * @param {string} type
     * @param {string} id
     * @return {boolean}
     */
    self.existDB = function(dbID, type, id) {

        try {
            return self.getDB(dbID, type, id) !== null;
        } catch(e) {
            Console.error('DataStore.existDB', e);
        }

    };


    /**
     * Temporary: used to clear all the database
     * @public
     * @return {boolean}
     */
    self.resetDB = function() {

        try {
            try {
                self.storageDB.clear();

                Console.info('Temporary database cleared.');

                return true;
            }

            catch(e) {
                Console.error('Error while clearing temporary database', e);

                return false;
            }
        } catch(e) {
            Console.error('DataStore.resetDB', e);
        }

    };


    /**
     * Persistent: returns whether it is available or not
     * @public
     * @return {boolean}
     */
    self.hasPersistent = function() {

        var has_persistent = false;

        try {
            // Try to write something
            self.storagePersistent.setItem('haspersistent_check', 'ok');
            self.storagePersistent.removeItem('haspersistent_check');

            has_persistent = true;
        } catch(e) {
            Console.error('DataStore.hasPersistent', e);
        } finally {
            return has_persistent;
        }

    };


    /**
     * Persistent: used to read a database entry
     * @public
     * @param {string} dbID
     * @param {string} type
     * @param {string} id
     * @return {object}
     */
    self.getPersistent = function(dbID, type, id) {

        try {
            try {
                return self.storagePersistent.getItem(dbID + '_' + type + '_' + id);
            }

            catch(e) {
                Console.error('Error while getting a persistent database entry (' + dbID + ' -> ' + type + ' -> ' + id + ')', e);

                return null;
            }
        } catch(e) {
            Console.error('DataStore.getPersistent', e);
        }

    };


    /**
     * Persistent: used to update a database entry
     * @public
     * @param {string} dbID
     * @param {string} type
     * @param {string} id
     * @param {string} value
     * @return {boolean}
     */
    self.setPersistent = function(dbID, type, id, value) {

        try {
            try {
                self.storagePersistent.setItem(dbID + '_' + type + '_' + id, value);

                return true;
            }

            // Database might be full
            catch(e) {
                Console.warn('Retrying: could not write a persistent database entry (' + dbID + ' -> ' + type + ' -> ' + id + ')', e);

                // Flush it!
                self.flushPersistent();

                // Set the item again
                try {
                    self.storagePersistent.setItem(dbID + ' -> ' + type + '_' + id, value);

                    return true;
                }

                // New error!
                catch(_e) {
                    Console.error('Aborted: error while writing a persistent database entry (' + dbID + ' -> ' + type + ' -> ' + id + ')', _e);
                }
            }

            return false;
        } catch(e) {
            Console.error('DataStore.setPersistent', e);
        }

    };


    /**
     * Persistent: used to remove a database entry
     * @public
     * @param {string} dbID
     * @param {string} type
     * @param {string} id
     * @return {boolean}
     */
    self.removePersistent = function(dbID, type, id) {

        try {
            try {
                self.storagePersistent.removeItem(dbID + '_' + type + '_' + id);

                return true;
            }

            catch(e) {
                Console.error('Error while removing a persistent database entry (' + dbID + ' -> ' + type + ' -> ' + id + ')', e);
            }

            return false;
        } catch(e) {
            Console.error('DataStore.removePersistent', e);
        }

    };


    /**
     * Persistent: used to check a database entry exists
     * @public
     * @param {string} dbID
     * @param {string} type
     * @param {string} id
     * @return {boolean}
     */
    self.existPersistent = function(dbID, type, id) {

        try {
            return self.getPersistent(dbID, type, id) !== null;
        } catch(e) {
            Console.error('DataStore.existPersistent', e);
        }

    };


    /**
     * Persistent: used to clear all the database
     * @public
     * @param {type} name
     * @return {boolean}
     */
    self.resetPersistent = function() {

        try {
            try {
                self.storagePersistent.clear();

                Console.info('Persistent database cleared.');

                return true;
            }

            catch(e) {
                Console.error('Error while clearing persistent database', e);
            }

            return false;
        } catch(e) {
            Console.error('DataStore.resetPersistent', e);
        }

    };


    /**
     * Persistent: used to flush the database
     * @public
     * @param {type} name
     * @return {boolean}
     */
    self.flushPersistent = function() {

        try {
            try {
                // Get the stored session entry
                var session = self.getPersistent('global', 'session', 1);

                // Reset the persistent database
                self.resetPersistent();

                // Restaure the stored session entry
                if(session) {
                    self.setPersistent('global', 'session', 1, session);
                }

                Console.info('Persistent database flushed.');

                return true;
            }

            catch(e) {
                Console.error('Error while flushing persistent database', e);
            }

            return false;
        } catch(e) {
            Console.error('DataStore.flushPersistent', e);
        }

    };


    /**
     * Return class scope
     */
    return self;

})();

var JappixDataStore = DataStore;/* BROWSER DETECT
 * http://www.quirksmode.org/js/detect.html
 * License: dual-licensed under MPLv2 and the original license
 */

var BrowserDetect = {
    init: function () {
        this.browser = this.searchString(this.dataBrowser) || "An unknown browser";
        this.version = this.searchVersion(navigator.userAgent)    ||
                         this.searchVersion(navigator.appVersion) ||
                         "an unknown version";
        this.OS = this.searchString(this.dataOS) || "an unknown OS";
    },

    searchString: function (data) {
        for (var i=0;i<data.length;i++) {
            var dataString = data[i].string;
            var dataProp = data[i].prop;
            this.versionSearchString = data[i].versionSearch || data[i].identity;
            if (dataString) {
                if (dataString.indexOf(data[i].subString) != -1)
                    return data[i].identity;
            }
            else if (dataProp)
                return data[i].identity;
        }
    },

    searchVersion: function (dataString) {
        var index = dataString.indexOf(this.versionSearchString);
        if (index == -1) return;
        return parseFloat(dataString.substring(index+this.versionSearchString.length+1));
    },

    dataBrowser: [
        {
            string: navigator.userAgent,
            subString: "Chrome",
            identity: "Chrome"
        },
        {   string: navigator.userAgent,
            subString: "OmniWeb",
            versionSearch: "OmniWeb/",
            identity: "OmniWeb"
        },
        {
            string: navigator.vendor,
            subString: "Apple",
            identity: "Safari",
            versionSearch: "Version"
        },
        {
            prop: window.opera,
            identity: "Opera"
        },
        {
            string: navigator.vendor,
            subString: "iCab",
            identity: "iCab"
        },
        {
            string: navigator.vendor,
            subString: "KDE",
            identity: "Konqueror"
        },
        {
            string: navigator.userAgent,
            subString: "Firefox",
            identity: "Firefox"
        },
        {
            string: navigator.vendor,
            subString: "Camino",
            identity: "Camino"
        },
        {       // for newer Netscapes (6+)
            string: navigator.userAgent,
            subString: "Netscape",
            identity: "Netscape"
        },
        {
            string: navigator.userAgent,
            subString: "MSIE",
            identity: "Explorer",
            versionSearch: "MSIE"
        },
        {
            string: navigator.userAgent,
            subString: "Gecko",
            identity: "Mozilla",
            versionSearch: "rv"
        },
        {       // for older Netscapes (4-)
            string: navigator.userAgent,
            subString: "Mozilla",
            identity: "Netscape",
            versionSearch: "Mozilla"
        }
    ],

    dataOS : [
        {
            string: navigator.platform,
            subString: "Win",
            identity: "Windows"
        },
        {
            string: navigator.platform,
            subString: "Mac",
            identity: "Mac"
        },
        {
               string: navigator.userAgent,
               subString: "iPhone",
               identity: "iPhone/iPod"
        },
        {
            string: navigator.platform,
            subString: "Linux",
            identity: "Linux"
        }
    ]
};

BrowserDetect.init();
// License: MIT

/*
 *  Console.js
 *
 *  An interface to native console methods
 *  Avoids issues when browser does not have native support for console
 *
 *  @license OS
 *  @author Valérian Saliou <valerian@valeriansaliou.name>
 *  @url https://github.com/valeriansaliou/console.js
 */

var Console = (function () {

  var self = this;


  /* Variables */
  self._available = typeof(window.console) != 'undefined';
  self._has = self._available && JappixSystem.isDeveloper();
  self._console = self._available ? console : {};


  /* Adapters */
  self._adapter = function (level) {
    if (!self._has) {
      return function() {};
    }

    var adapter = null;
    try {
      switch (level) {
        case 0:
          adapter = console.warn; break;
        case 1:
          adapter = console.error; break;
        case 2:
          adapter = console.info; break;
        case 3:
          adapter = console.log; break;
        case 4:
          adapter = console.debug; break;
      }
    } catch (e) {
      adapter = function() {};
    }

    return adapter.bind(self._console);
  };


  /* Methods */
  self.warn = self._adapter(0);
  self.error = self._adapter(1);
  self.info = self._adapter(2);
  self.log = self._adapter(3);
  self.debug = self._adapter(4);


  /* Return class scope */
  return self;

})();

var JappixConsole = Console;/*

Jappix - An open social platform
These are the common JS script for Jappix

-------------------------------------------------

License: dual-licensed under AGPL and MPLv2
Authors: Valérian Saliou, olivierm, regilero, Maranda

*/

// Bundle
var Common = (function () {

    /**
     * Alias of this
     * @private
     */
    var self = {};


    /* Constants */
    self.R_DOMAIN_NAME = /^(([a-zA-Z0-9-\.]+)\.)?[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/i;


    /**
     * Checks if an element exists in the DOM
     * @public
     * @param {string} path
     * @return {boolean}
     */
    self.exists = function(path) {

        var exists = false;

        try {
            if(jQuery(path).size() > 0) {
                exists = true;
            }
        } catch(e) {
            Console.error('Common.exists', e);
        } finally {
            return exists;
        }

    };


    /**
     * Checks if Jappix is connected
     * @public
     * @return {boolean}
     */
    self.isConnected = function() {

        connected = false;

        try {
            if((typeof con != 'undefined') && con && con.connected()) {
                connected = true;
            }
        } catch(e) {
            Console.error('Common.isConnected', e);
        } finally {
            return connected;
        }

    };


    /**
     * Checks if Jappix is connected
     * @public
     * @return {boolean}
     */
    self.hasWebSocket = function() {

        has_websocket = false;

        try {
            if(HOST_WEBSOCKET && typeof window.WebSocket != 'undefined') {
                has_websocket = true;
            }
        } catch(e) {
            Console.error('Common.hasWebSocket', e);
        } finally {
            return has_websocket;
        }

    };


    /**
     * Checks if Jappix has focus
     * @public
     * @return {boolean}
     */
    self.isFocused = function() {

        has_focus = true;

        try {
            if(!document.hasFocus()) {
                has_focus = false;
            }
        } catch(e) {
            Console.error('Common.isFocused', e);
        } finally {
            return has_focus;
        }

    };


    /**
     * Matches a domain name
     * @public
     * @param {string} xid
     * @return {boolean}
     */
    self.isDomain = function(xid) {

        is_domain = false;

        try {
            if(xid.match(self.R_DOMAIN_NAME)) {
                is_domain = true;
            }
        } catch(e) {
            Console.error('Common.isDomain', e);
        } finally {
            return is_domain;
        }

    };


    /**
     * Generates the good XID
     * @public
     * @param {string} xid
     * @param {string} type
     * @return {string}
     */
    self.generateXID = function(xid, type) {

        try {
            // XID needs to be transformed
            xid = xid.toLowerCase();

            if(xid && (xid.indexOf('@') === -1)) {
                // Groupchat XID
                if(type == 'groupchat') {
                    return xid + '@' + HOST_MUC;
                }

                // Gateway XID
                if(self.isDomain(xid) === true) {
                    return xid;
                }

                // User XID
                return xid + '@' + HOST_MAIN;
            }

            // Nothing special (yet bare XID)
            return xid;
        } catch(e) {
            Console.error('Common.generateXID', e);
        }

    };


    /**
     * Gets the asked translated string
     * @public
     * @param {string} string
     * @return {string}
     */
    self._e = function(string) {

        try {
            return string;
        } catch(e) {
            Console.error('Common._e', e);
        }

    };


    /**
     * Replaces '%s' to a given value for a translated string
     * @public
     * @param {string} string
     * @param {string} value
     * @return {string}
     */
    self.printf = function(string, value) {

        try {
            return string.replace('%s', value);
        } catch(e) {
            Console.error('Common.printf', e);
        }

    };


    /**
     * Returns the string after the last given char
     * @public
     * @param {string} given_char
     * @param {string} str
     * @return {string}
     */
    self.strAfterLast = function(given_char, str) {

        try {
            if(!given_char || !str) {
                return '';
            }

            var char_index = str.lastIndexOf(given_char);
            var str_return = str;

            if(char_index >= 0) {
                str_return = str.substr(char_index + 1);
            }

            return str_return;
        } catch(e) {
            Console.error('Common.strAfterLast', e);
        }

    };


    /**
     * Properly explodes a string with a given character
     * @public
     * @param {string} toEx
     * @param {string} toStr
     * @param {number} i
     * @return {string}
     */
    self.explodeThis = function(toEx, toStr, i) {

        try {
            // Get the index of our char to explode
            var index = toStr.indexOf(toEx);

            // We split if necessary the string
            if(index !== -1) {
                if(i === 0) {
                    toStr = toStr.substr(0, index);
                } else {
                    toStr = toStr.substr(index + 1);
                }
            }

            // We return the value
            return toStr;
        } catch(e) {
            Console.error('Common.explodeThis', e);
        }

    };


    /**
     * Cuts the resource of a XID
     * @public
     * @param {string} aXID
     * @return {string}
     */
    self.cutResource = function(aXID) {

        try {
            return self.explodeThis('/', aXID, 0);
        } catch(e) {
            Console.error('Common.cutResource', e);
        }

    };


    /**
     * Gets the resource of a XID
     * @public
     * @param {string} aXID
     * @return {string}
     */
    self.thisResource = function(aXID) {

        resource = '';

        try {
            // Any resource?
            if(self.isFullXID(aXID)) {
                resource = self.explodeThis('/', aXID, 1);
            }
        } catch(e) {
            Console.error('Common.thisResource', e);
        } finally {
            return resource;
        }

    };


    /**
     * Returns whether this XID is full or not
     * @public
     * @param {string} xid
     * @return {boolean}
     */
    self.isFullXID = function(xid) {

        try {
            return xid.indexOf('/') !== -1;
        } catch(e) {
            Console.error('Common.isFullXID', e);

            return false;
        }

    };


    /**
     * nodepreps an XMPP node
     * @public
     * @param {string} node
     * @return {string}
     */
    self.nodeprep = function(node) {

        // Spec: http://tools.ietf.org/html/rfc6122#appendix-A

        try {
            if(!node) {
                return node;
            }

            // Remove prohibited chars
            var prohibited_chars = ['"', '&', '\'', '/', ':', '<', '>', '@'];

            for(var j in prohibited_chars) {
                node = node.replace(prohibited_chars[j], '');
            }

            // Lower case
            node = node.toLowerCase();

            return node;
        } catch(e) {
            Console.error('Common.nodeprep', e);
        }

    };


    /**
     * Encodes quotes in a string
     * @public
     * @param {string} str
     * @return {string}
     */
    self.encodeQuotes = function(str) {

        try {
            return (str + '').htmlEnc();
        } catch(e) {
            Console.error('Common.encodeQuotes', e);
        }

    };


    /**
     * Escapes quotes in a string
     * @public
     * @param {string} str
     * @return {string}
     */
    self.escapeQuotes = function(str) {

        try {
            return escape(self.encodeQuotes(str));
        } catch(e) {
            Console.error('Common.escapeQuotes', e);
        }

    };


    /**
     * Unescapes quotes in a string
     * @public
     * @param {string} str
     * @return {string}
     */
    self.unescapeQuotes = function(str) {

        try {
            return unescape(str);
        } catch(e) {
            Console.error('Common.unescapeQuotes', e);
        }

    };


    /**
     * Gets the bare XID from a XID
     * @public
     * @param {string} xid
     * @return {string}
     */
    self.bareXID = function(xid) {

        try {
            // Cut the resource
            xid = self.cutResource(xid);

            // Launch nodeprep
            if(xid.indexOf('@') !== -1) {
                xid = self.nodeprep(self.getXIDNick(xid, true)) + '@' + self.getXIDHost(xid);
            }

            return xid;
        } catch(e) {
            Console.error('Common.bareXID', e);
        }

    };


    /**
     * Gets the full XID from a XID
     * @public
     * @param {string} xid
     * @return {string}
     */
    self.fullXID = function(xid) {

        try {
            // Normalizes the XID
            var full = self.bareXID(xid);
            var resource = self.thisResource(xid);

            // Any resource?
            if(resource) {
                full += '/' + resource;
            }

            return full;
        } catch(e) {
            Console.error('Common.fullXID', e);
        }

    };


    /**
     * Gets the nick from a XID
     * @public
     * @param {string} aXID
     * @param {boolean} raw_explode
     * @return {string}
     */
    self.getXIDNick = function(aXID, raw_explode) {

        try {
            if(raw_explode !== true) {
                // Gateway nick?
                if(aXID.match(/\\40/)) {
                    return self.explodeThis('\\40', aXID, 0);
                }
            }

            return self.explodeThis('@', aXID, 0);
        } catch(e) {
            Console.error('Common.getXIDNick', e);
        }

    };


    /**
     * Gets the host from a XID
     * @public
     * @param {string} aXID
     * @return {string}
     */
    self.getXIDHost = function(aXID) {

        try {
            return self.explodeThis('@', aXID, 1);
        } catch(e) {
            Console.error('Common.getXIDHost', e);
        }

    };


    /**
     * Checks if we are RTL (Right-To-Left)
     * @public
     * @return {boolean}
     */
    self.isRTL = function() {

        try {
            return (self._e("default:LTR") == 'default:RTL');
        } catch(e) {
            Console.error('Common.isRTL', e);
        }

    };


    /**
     * Checks if anonymous mode is allowed
     * @public
     * @return {boolean}
     */
    self.allowedAnonymous = function() {

        try {
            return (ANONYMOUS == 'on');
        } catch(e) {
            Console.error('Common.allowedAnonymous', e);
        }

    };


    /**
     * Checks if host is locked
     * @public
     * @return {boolean}
     */
    self.lockHost = function() {

        try {
            return (LOCK_HOST == 'on');
        } catch(e) {
            Console.error('Common.lockHost', e);
        }

    };


    /**
     * Gets the bare XID of the user
     * @public
     * @return {string}
     */
    self.getXID = function() {

        try {
            // Return the XID of the user
            if(con.username && con.domain) {
                return con.username + '@' + con.domain;
            }

            return '';
        } catch(e) {
            Console.error('Common.getXID', e);
        }

    };


    /**
     * Gets the full XID of the user
     * @public
     * @return {string}
     */
    self.getFullXID = function() {

        try {
            var xid = self.getXID();

            // Return the full XID of the user
            if(xid) {
                return xid + '/' + con.resource;
            }

            return '';
        } catch(e) {
            Console.error('Common.getFullXID', e);
        }

    };


    /**
     * Generates the colors for a given user XID
     * @public
     * @param {type} xid
     * @return {string}
     */
    self.generateColor = function(xid) {

        try {
            var colors = new Array(
                'ac0000',
                'a66200',
                '007703',
                '00705f',
                '00236b',
                '4e005c'
            );

            var number = 0;

            for(var i = 0; i < xid.length; i++) {
                number += xid.charCodeAt(i);
            }

            var color = '#' + colors[number % (colors.length)];

            return color;
        } catch(e) {
            Console.error('Common.generateColor', e);
        }

    };


    /**
     * Checks if the XID is a gateway
     * @public
     * @param {string} xid
     * @return {boolean}
     */
    self.isGateway = function(xid) {

        is_gateway = true;

        try {
            if(xid.indexOf('@') !== -1) {
                is_gateway = false;
            }
        } catch(e) {
            Console.error('Common.isGateway', e);
        } finally {
            return is_gateway;
        }

    };


    /**
     * Gets the from attribute of a stanza (overrides some servers like Prosody missing from attributes)
     * @public
     * @param {object} stanza
     * @return {string}
     */
    self.getStanzaFrom = function(stanza) {

        try {
            var from = stanza.getFrom();

            // No from, we assume this is our XID
            if(!from) {
                from = self.getXID();
            }

            return from;
        } catch(e) {
            Console.error('Common.getStanzaFrom', e);
        }

    };


    /**
     * Returns whether the stanza has been really sent from our own server or entity
     * @public
     * @param {object} stanza
     * @return {string}
     */
    self.isSafeStanza = function(stanza) {

        var is_safe = false;

        try {
            var from = self.getStanzaFrom(stanza);

            is_safe = (!from || from == con.domain || from == self.getXID()) && true;
        } catch(e) {
            Console.error('Common.isSafeStanza', e);
        } finally {
            return is_safe;
        }

    };


    /**
     * Adds a zero to a date when needed
     * @public
     * @param {number} i
     * @return {string}
     */
    self.padZero = function(i) {

        try {
            // Negative number (without first 0)
            if(i > -10 && i < 0) {
                return '-0' + (i * -1);
            }

            // Positive number (without first 0)
            if(i < 10 && i >= 0) {
                return '0' + i;
            }

            // All is okay
            return i;
        } catch(e) {
            Console.error('Common.padZero', e);
        }

    };


    /**
     * Escapes a string (or an array of string) for a regex usage. In case of an
     * array, escapes are not done "in place", keeping the query unmodified
     * @public
     * @param {object} query
     * @return {object}
     */
    self.escapeRegex = function(query) {

        var result = [];

        try {
            if(query instanceof Array) {
                result = [query.length];

                for(i = 0; i < query.length; i++) {
                    try {
                        result[i] = Common.escapeRegex(query[i]);
                    } catch(e) {
                        Console.error('Common.escapeRegex', e);
                        result[i] = null;
                    }
                }
            } else {
                try {
                    result = query.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, '\\$&');
                } catch(e) {
                    Console.error('Common.escapeRegex[inner]', e);
                }
            }
        } catch(e) {
            Console.error('Common.escapeRegex', e);
        } finally {
            return result;
        }

    };


    /**
     * Returns a random array value
     * @public
     * @param {object} arr
     * @return {object}
     */
    self.randomArrayValue = function(arr) {

        try {
            return arr[Math.floor(Math.random() * arr.length)];
        } catch(e) {
            Console.error('Common.randomArrayValue', e);
        }

    };


    /**
     * Returns whether the browser is mobile or not
     * @public
     * @return {boolean}
     */
    self.isMobile = function() {

        is_mobile = false;

        try {
            is_mobile = /Android|iPhone|iPod|iPad|Windows Phone|BlackBerry|Bada|Maemo|Meego|webOS/i.test(navigator.userAgent);
        } catch(e) {
            Console.error('Common.isMobile', e);
        } finally {
            return is_mobile;
        }

    };


    /**
     * Converts a XML document to a string
     * @public
     * @param {object} xmlData
     * @return {string}
     */
    self.xmlToString = function(xmlData) {

        xml_str = null;

        try {
            // For Mozilla, Firefox, Opera, etc.
            if(window.XMLSerializer) {
                xml_str = (new XMLSerializer()).serializeToString(xmlData);
            }

            // For Internet Explorer
            if(window.ActiveXObject) {
                xml_str = xmlData.xml;
            }
        } catch(e) {
            Console.error('Common.xmlToString', e);
        } finally {
            return xml_str;
        }

    };


    /**
     * Converts a string to a XML document
     * @public
     * @param {string} sXML
     * @return {object}
     */
    self.XMLFromString = function(sXML) {

        try {
            // No data?
            if(!sXML) {
                return '';
            }

            // Add the XML tag
            if(!sXML.match(/^<\?xml/i)) {
                sXML = '<?xml version="1.0"?>' + sXML;
            }

            // Parse it!
            if(window.DOMParser) {
                return (new DOMParser()).parseFromString(sXML, 'text/xml');
            }

            if(window.ActiveXObject) {
                var oXML = new ActiveXObject('Microsoft.XMLDOM');
                oXML.loadXML(sXML);

                return oXML;
            }
        } catch(e) {
            Console.error('Common.XMLFromString', e);

            return '';
        }

    };


    /**
     * Watches for input value change (delays callback)
     * @public
     * @param {function} cb
     * @return {function}
     */
    self.typewatch = function(cb) {

        try {
            var timer = 0;

            return function(callback, ms) {
                clearTimeout(timer);
                timer = setTimeout(callback, ms);
            };
        } catch(e) {
            Console.error('Common.typewatch', e);
        }

    };
    
    /**
     * Convert all applicable characters to HTML entities
     * @public
     * @param {string} cb
     * @return {string}
     */
    self.htmlentities = function(s) {
        return $('<div/>').text(s + '').html();
    };



    /**
     * Return class scope
     */
    return self;

})();

var JappixCommon = Common;
/*

Jappix - An open social platform
These are the date related JS scripts for Jappix

-------------------------------------------------

License: dual-licensed under AGPL and MPLv2
Author: Valérian Saliou

*/

// Bundle
var DateUtils = (function () {

    /**
     * Alias of this
     * @private
     */
    var self = {};


    /* Variables */
    self.last_activity = 0;
    self.presence_last_activity = 0;


    /**
     * Gets a stamp from a date
     * @public
     * @param {Date} date
     * @return {number}
     */
    self.extractStamp = function(date) {

        try {
            return Math.round(date.getTime() / 1000);
        } catch(e) {
            Console.error('DateUtils.extractStamp', e);
        }

    };


    /**
     * Gets the time from a date
     * @public
     * @param {Date} date
     * @return {string}
     */
    self.extractTime = function(date) {

        try {
            return date.toLocaleTimeString();
        } catch(e) {
            Console.error('DateUtils.extractTime', e);
        }

    };


    /**
     * Gets the actual date stamp
     * @public
     * @return {number}
     */
    self.getTimeStamp = function() {

        try {
            return self.extractStamp(new Date());
        } catch(e) {
            Console.error('DateUtils.getTimeStamp', e);
        }

    };


    /**
     * Gets the last user activity in seconds
     * @public
     * @return {number}
     */
    self.getLastActivity = function() {

        try {
            // Last activity not yet initialized?
            if(self.last_activity === 0) {
                return 0;
            }

            return self.getTimeStamp() - self.last_activity;
        } catch(e) {
            Console.error('DateUtils.getLastActivity', e);
        }

    };


    /**
     * Gets the last user activity as a date
     * @public
     * @return {string}
     */
    self.getLastActivityDate = function() {

        try {
            var last_activity = self.last_activity || self.getTimeStamp();

            var last_date = new Date();
            last_date.setTime(last_activity * 1000);

            return self.getDatetime(last_date, 'utc');
        } catch(e) {
            Console.error('DateUtils.getLastActivityDate', e);
        }

    };


    /**
     * Gets the last user available presence in seconds
     * @public
     * @return {number}
     */
    self.getPresenceLast = function() {

        try {
            // Last presence stamp not yet initialized?
            if(self.presence_last_activity === 0) {
                return 0;
            }

            return self.getTimeStamp() - self.presence_last_activity;
        } catch(e) {
            Console.error('DateUtils.getPresenceLast', e);
        }

    };


    /**
     * Generates a normalized datetime
     * @public
     * @param {Date} date
     * @param {string} location
     * @return {string}
     */
    self.getDatetime = function(date, location) {

        /* FROM : http://trac.jwchat.org/jsjac/browser/branches/jsjac_1.0/jsextras.js?rev=221 */

        var year, month, day, hours, minutes, seconds;
        var date_string = null;

        try {
            if(location == 'utc') {
                // UTC date
                year = date.getUTCFullYear();
                month = date.getUTCMonth();
                day = date.getUTCDate();
                hours = date.getUTCHours();
                minutes = date.getUTCMinutes();
                seconds = date.getUTCSeconds();
            } else {
                // Local date
                year = date.getFullYear();
                month = date.getMonth();
                day = date.getDate();
                hours = date.getHours();
                minutes = date.getMinutes();
                seconds = date.getSeconds();
            }

            // Generates the date string
            date_string = year + '-';
            date_string += Common.padZero(month + 1) + '-';
            date_string += Common.padZero(day) + 'T';
            date_string += Common.padZero(hours) + ':';
            date_string += Common.padZero(minutes) + ':';
            date_string += Common.padZero(seconds) + 'Z';

            // Returns the date string
            return date_string;
        } catch(e) {
            Console.error('DateUtils.getDatetime', e);
        }

    };


    /**
     * Generates the time for XMPP
     * @public
     * @param {string} location
     * @return {string}
     */
    self.getXMPPTime = function(location) {

        try {
            return self.getDatetime(
                (new Date()),
                location
            );
        } catch(e) {
            Console.error('DateUtils.getXMPPTime', e);
        }

    };


    /**
     * Generates then human time
     * @public
     * @return {string}
     */
    self.getCompleteTime = function() {

        try {
            var init = new Date();

            var time = Common.padZero(init.getHours()) + ':';
            time += Common.padZero(init.getMinutes()) + ':';
            time += Common.padZero(init.getSeconds());

            return time;
        } catch(e) {
            Console.error('DateUtils.getCompleteTime', e);
        }

    };


    /**
     * Gets the TZO of a date
     * @public
     * @return {string}
     */
    self.getTZO = function() {

        try {
            // Get the date
            var date = new Date();
            var offset = date.getTimezoneOffset();

            // Default vars
            var sign = '';
            var hours = 0;
            var minutes = 0;

            // Process a neutral offset
            if(offset < 0) {
                offset = offset * -1;
                sign = '+';
            }

            // Get the values
            var n_date = new Date(offset * 60 * 1000);
            hours = n_date.getHours() - 1;
            minutes = n_date.getMinutes();

            // Process the TZO
            tzo = sign + Common.padZero(hours) + ':' + Common.padZero(minutes);

            // Return the processed value
            return tzo;
        } catch(e) {
            Console.error('DateUtils.getTZO', e);
        }

    };


    /**
     * Returns a date representing the difference of time between 2 timestamps
     * @public
     * @param {string} now_stamp
     * @param {string} past_stamp
     * @return {Date}
     */
    self.difference = function(now_stamp, past_stamp) {

        try {
            return (new Date()).clearTime().addSeconds(
                past_stamp > 0 ? now_stamp - past_stamp : 0
            );
        } catch(e) {
            Console.error('DateUtils.difference', e);
        }

    };


    /**
     * Parses a XMPP date (yyyy-mm-dd, hh-mm-ss) into an human-readable one
     * @public
     * @param {string} to_parse
     * @return {string}
     */
    self.parse = function(to_parse) {

        try {
            var date = Date.jab2date(to_parse);
            var parsed = date.toLocaleDateString() + ' (' + date.toLocaleTimeString() + ')';

            return parsed;
        } catch(e) {
            Console.error('DateUtils.parse', e);
        }

    };


    /**
     * Parses a XMPP date (yyyy-mm-dd) into an human-readable one
     * @public
     * @param {string} to_parse
     * @return {string}
     */
    self.parseDay = function(to_parse) {

        try {
            var date = Date.jab2date(to_parse);
            var parsed = date.toLocaleDateString();

            return parsed;
        } catch(e) {
            Console.error('DateUtils.parseDay', e);
        }

    };


    /**
     * Parses a XMPP date (hh-mm-ss) into an human-readable one
     * @public
     * @param {string} to_parse
     * @return {string}
     */
    self.parseTime = function(to_parse) {

        try {
            var date = Date.jab2date(to_parse);
            var parsed = date.toLocaleTimeString();

            return parsed;
        } catch(e) {
            Console.error('DateUtils.parseTime', e);
        }

    };


    /**
     * Parses a XMPP date stamp into a relative one
     * @public
     * @param {string} to_parse
     * @return {string}
     */
    self.relative = function(to_parse) {

        try {
            // Get the current date
            var current_date = Date.jab2date(self.getXMPPTime('utc'));
            var current_day = current_date.getDate();
            var current_stamp = current_date.getTime();

            // Parse the given date
            var old_date = Date.jab2date(to_parse);
            var old_day = old_date.getDate();
            var old_stamp = old_date.getTime();
            var old_time = old_date.toLocaleTimeString();

            // Get the day number between the two dates
            var days = Math.round((current_stamp - old_stamp) / 86400000);

            // Invalid date?
            if(isNaN(old_stamp) || isNaN(days)) {
                return self.getCompleteTime();
            }

            // Is it today?
            if(current_day == old_day) {
                return old_time;
            }

            // It is yesterday?
            if(days <= 1) {
                return Common._e("Yesterday") + ' - ' + old_time;
            }

            // Is it less than a week ago?
            if(days <= 7) {
                return Common.printf(Common._e("%s days ago"), days) + ' - ' + old_time;
            }

            // Another longer period
            return old_date.toLocaleDateString() + ' - ' + old_time;
        } catch(e) {
            Console.error('DateUtils.relative', e);
        }

    };


    /**
     * Reads a message delay
     * @public
     * @param {string} node
     * @param {boolean} return_date
     * @return {string|Date}
     */
    self.readMessageDelay = function(node, return_date) {

        try {
            // Initialize
            var delay, d_delay;

            // Read the delay
            d_delay = jQuery(node).find('delay[xmlns="' + NS_URN_DELAY + '"]:first').attr('stamp');

            // Get delay
            if(d_delay) {
                // New delay (valid XEP)
                delay = d_delay;
            } else {
                // Old delay (obsolete XEP!)
                var x_delay = jQuery(node).find('x[xmlns="' + NS_DELAY + '"]:first').attr('stamp');

                if(x_delay) {
                    delay = x_delay.replace(/^(\w{4})(\w{2})(\w{2})T(\w{2}):(\w{2}):(\w{2})Z?(\S+)?/, '$1-$2-$3T$4:$5:$6Z$7');
                }
            }

            // Return a date object?
            if(return_date === true && delay) {
                return Date.jab2date(delay);
            }

            return delay;
        } catch(e) {
            Console.error('DateUtils.readMessageDelay', e);
        }

    };


    /**
     * Return class scope
     */
    return self;

})();

var JappixDateUtils = DateUtils;/*

Jappix - An open social platform
These are the links JS script for Jappix

-------------------------------------------------

License: dual-licensed under AGPL and MPLv2
Authors: Valérian Saliou, Maranda

*/

// Bundle
var Links = (function () {

    /**
     * Alias of this
     * @private
     */
    var self = {};


    /**
     * Apply links in a string
     * @public
     * @param {string} string
     * @param {string} mode
     * @param {string} style
     * @return {string}
     */
    self.apply = function(string, mode, style) {

        try {
            var target;

            // Links style
            if(!style) {
                style = '';
            } else {
                style = ' style="' + style + '"';
            }

             /* UA: Add class */
            style += ' class="new-window"';

            // Open in new tabs
            if(mode != 'xhtml-im') {
                target = ' target="_blank"';
            } else {
                target = '';
            }

            // XMPP address
            string = string.replace(
                /(\s|<br \/>|^)(([a-zA-Z0-9\._-]+)@([a-zA-Z0-9\.\/_-]+))(,|\s|$)/gi,
                '$1<a href="xmpp:$2" target="_blank"' + style + '>$2</a>$5'
            );

            // Simple link
            string = string.replace(
                /(\s|<br \/>|^|\()((https?|ftp|file|xmpp|irc|mailto|vnc|webcal|ssh|ldap|smb|magnet|spotify)(:)([^<>'"\s\)]+))/gim,
                '$1<a href="$2"' + target + style + '>$2</a>'
            );

            return string;
        } catch(e) {
            Console.error('Links.apply', e);
        }

    };


    /**
     * Return class scope
     */
    return self;

})();

var JappixLinks = Links;/*

Jappix - An open social platform
These are the Jappix Mini JS scripts for Jappix

-------------------------------------------------

License: dual-licensed under AGPL and MPLv2
Authors: Valérian Saliou, hunterjm, Camaran, regilero, Kloadut, Maranda

*/

// Jappix Mini globals
var MINI_DISCONNECT             = false;
var MINI_AUTOCONNECT            = false;
var MINI_SHOWPANE               = false;
var MINI_INITIALIZED            = false;
var MINI_ROSTER_INIT            = false;
var MINI_ROSTER_NOGROUP         = 'jm_nogroup';
var MINI_ANONYMOUS              = false;
var MINI_ANIMATE                = false;
var MINI_RANDNICK               = false;
var MINI_GROUPCHAT_PRESENCE     = false;
var MINI_DISABLE_MOBILE         = false;
var MINI_NICKNAME               = '';
var MINI_TITLE                  = null;
var MINI_DOMAIN                 = null;
var MINI_USER                   = null;
var MINI_PASSWORD               = null;
var MINI_HASH                   = null;
var MINI_ACTIVE                 = null;
var MINI_RECONNECT              = 0;
var MINI_RECONNECT_MAX          = 100;
var MINI_RECONNECT_INTERVAL     = 1;
var MINI_PIXEL_STREAM_DURATION  = 300;
var MINI_PIXEL_STREAM_INTERVAL  = 7200;
var MINI_QUEUE                  = [];
var MINI_CHATS                  = [];
var MINI_GROUPCHATS             = [];
var MINI_SUGGEST_CHATS          = [];
var MINI_SUGGEST_GROUPCHATS     = [];
var MINI_SUGGEST_PASSWORDS      = [];
var MINI_PASSWORDS              = [];
var MINI_PRIORITY               = 1;
var MINI_RESOURCE               = JAPPIX_RESOURCE + ' Mini';
var MINI_ERROR_LINK             = 'https://mini.jappix.com/issues';


// Bundle
var JappixMini = (function () {

    /**
     * Alias of this
     * @private
     */
    var self = {};


    /**
     * Setups connection handlers
     * @public
     * @param {object} con
     * @return {undefined}
     */
    self.setupCon = function(con) {

        try {
            con.registerHandler('message', self.handleMessage);
            con.registerHandler('presence', self.handlePresence);
            con.registerHandler('iq', self.handleIQ);
            con.registerHandler('onerror', self.handleError);
            con.registerHandler('onconnect', self.connected);
        } catch(e) {
            JappixConsole.error('JappixMini.setupCon', e);
        }

    };


    /**
     * Connects the user with the given logins
     * @public
     * @param {type} domain
     * @param {type} user
     * @param {type} password
     * @return {boolean}
     */
    self.connect = function(domain, user, password) {

        try {
            oArgs = {};

            // Check BOSH origin
            BOSH_SAME_ORIGIN = Origin.isSame(oArgs.httpbase);

            // We create the new http-binding connection
            con = new JSJaCHttpBindingConnection({
                httpbase: (HOST_BOSH_MINI || HOST_BOSH)
            });

            // And we handle everything that happen
            self.setupCon(con);

            // fixes #339
            var store_resource = (BrowserDetect.browser != 'Explorer');
            var random_resource = null;

            if(store_resource) {
                // Randomize resource?
                random_resource = JappixDataStore.getDB(MINI_HASH, 'jappix-mini', 'resource');
            }

            if(!random_resource) {
                random_resource = MINI_RESOURCE + ' (' + (new Date()).getTime() + ')';
            }

            // We retrieve what the user typed in the login inputs
            oArgs = {};
            oArgs.secure = true;
            oArgs.xmllang = XML_LANG;
            oArgs.resource = random_resource;
            oArgs.domain = domain;

            // Store the resource (for reconnection)
            if(store_resource) {
                JappixDataStore.setDB(MINI_HASH, 'jappix-mini', 'resource', random_resource);
            }

            // Anonymous login?
            if(MINI_ANONYMOUS) {
                // Anonymous mode disabled?
                if(!JappixCommon.allowedAnonymous()) {
                    JappixConsole.warn('Not allowed to use anonymous mode.');

                    // Notify this error
                    self.notifyError();

                    return false;
                }

                // Bad domain?
                else if(JappixCommon.lockHost() && (domain != HOST_ANONYMOUS)) {
                    JappixConsole.warn('Not allowed to connect to this anonymous domain: ' + domain);

                    // Notify this error
                    self.notifyError();

                    return false;
                }

                oArgs.authtype = 'saslanon';
            }

            // Normal login
            else {
                // Bad domain?
                if(JappixCommon.lockHost() && (domain != HOST_MAIN)) {
                    JappixConsole.warn('Not allowed to connect to this main domain: ' + domain);

                    // Notify this error
                    self.notifyError();

                    return false;
                }

                // No nickname?
                if(!MINI_NICKNAME) {
                    MINI_NICKNAME = user;
                }

                oArgs.username = user;
                oArgs.pass = password;
            }

            // We connect !
            con.connect(oArgs);

            JappixConsole.info('Jappix Mini is connecting...');
        } catch(e) {
            JappixConsole.error('JappixMini.connect', e);

            // Reset Jappix Mini
            self.disconnected();
        } finally {
            return false;
        }

    };


    /**
     * When the user is connected
     * @public
     * @return {undefined}
     */
    self.connected = function() {

        try {
            // Do not get the roster if anonymous
            if(!MINI_RECONNECT) {
                // Update the roster
                jQuery('#jappix_mini a.jm_pane.jm_button span.jm_counter').text('0');

                if(MINI_ANONYMOUS) {
                    self.initialize();
                } else {
                    self.getRoster();
                }

                JappixConsole.info('Jappix Mini is now connected.');
            } else {
                self.reconnected();

                JappixConsole.info('Jappix Mini is now reconnected.');
            }

            // Reset reconnect var
            MINI_RECONNECT = 0;
            JappixDataStore.removeDB(MINI_HASH, 'jappix-mini', 'reconnect');

            // Execute enqueued events
            self.dequeue();
        } catch(e) {
            JappixConsole.error('JappixMini.connected', e);
        }

    };


    /**
     * When the user is reconnected
     * @public
     * @return {undefined}
     */
    self.reconnected = function() {

        try {
            var last_presence = JappixDataStore.getDB(MINI_HASH, 'jappix-mini', 'presence-last') || 'available';

            // Flush presence storage
            self.flushStorage('presence');

            // Empty groupchat messages
            jQuery('#jappix_mini div.jm_conversation.jm_type_groupchat div.jm_received-messages div.jm_group').remove();

            // Re-send all presences
            jQuery('#jappix_mini div.jm_status_picker a[data-status="' + JappixCommon.encodeQuotes(last_presence) + '"]').click();
        } catch(e) {
            JappixConsole.error('JappixMini.reconnected', e);
        }

    };


    /**
     * When the user disconnects
     * @public
     * @return {undefined}
     */
    self.saveSession = function() {

        try {
            // Not initialized?
            if(!MINI_INITIALIZED) {
                return;
            }

            // Reset Jappix Mini DOM before saving it
            self.resetPixStream();

            // Save the actual Jappix Mini DOM
            JappixDataStore.setDB(MINI_HASH, 'jappix-mini', 'dom', jQuery('#jappix_mini').html());
            JappixDataStore.setDB(MINI_HASH, 'jappix-mini', 'nickname', MINI_NICKNAME);

            // Save the scrollbar position
            var scroll_position = '';
            var scroll_hash = jQuery('#jappix_mini div.jm_conversation:has(a.jm_pane.jm_clicked)').attr('data-hash');

            if(scroll_hash) {
                scroll_position = document.getElementById('received-' + scroll_hash).scrollTop + '';
            }

            JappixDataStore.setDB(MINI_HASH, 'jappix-mini', 'scroll', scroll_position);

            // Suspend connection
            if(JappixCommon.isConnected()) {
                con.suspend(false);
            } else {
                JappixDataStore.setDB(MINI_HASH, 'jappix-mini', 'reconnect', ((MINI_RECONNECT === 0) ? 0 : (MINI_RECONNECT - 1)));
                self.serializeQueue();
            }

            JappixConsole.info('Jappix Mini session save tool launched.');
        } catch(e) {
            JappixConsole.error('JappixMini.saveSession', e);
        }

    };


    /**
     * Flushes Jappix Mini storage database
     * @public
     * @param {string} r_override
     * @return {undefined}
     */
    self.flushStorage = function(r_override) {

        try {
            var i,
            db_regex, db_current;

            db_regex = new RegExp(('^' + MINI_HASH + '_') + 'jappix-mini' + (r_override ? ('_' + r_override) : ''));

            for(i = 0; i < JappixDataStore.storageDB.length; i++) {
                db_current = JappixDataStore.storageDB.key(i);

                if(db_regex.exec(db_current)) {
                    JappixDataStore.storageDB.removeItem(db_current);
                }
            }

            JappixConsole.log('Jappix Mini DB has been successfully flushed (' + (r_override ? 'partly' : 'completely') + ').');
        } catch(e) {
            JappixConsole.error('JappixMini.flushStorage', e);
        }

    };


    /**
     * Disconnects the connected user
     * @public
     * @return {boolean}
     */
    self.disconnect = function() {

        try {
            // No connection?
            if(!JappixCommon.isConnected()) {
                return false;
            }

            JappixConsole.info('Jappix Mini is disconnecting...');

            // Change markers
            MINI_DISCONNECT = true;
            MINI_INITIALIZED = false;

            // Flush storage
            self.flushStorage();

            // Add disconnection handler
            con.registerHandler('ondisconnect', function() {
                self.disconnected();
            });

            // Disconnect the user
            con.disconnect();

            return false;
        } catch(e) {
            JappixConsole.error('JappixMini.disconnect', e);
        }

    };


    /**
     * When the user is disconnected
     * @public
     * @return {boolean}
     */
    self.disconnected = function() {

        try {
            // Connection error?
            if(!MINI_DISCONNECT || MINI_INITIALIZED) {
                // Reset reconnect timer
                jQuery('#jappix_mini').stopTime();

                // Try to reconnect after a while
                if(MINI_INITIALIZED && (MINI_RECONNECT++ < MINI_RECONNECT_MAX)) {
                    // Set timer
                    jQuery('#jappix_mini').oneTime(MINI_RECONNECT_INTERVAL * 1000, function() {
                        JappixConsole.debug('Trying to reconnect... (attempt: ' + MINI_RECONNECT + ' / ' + MINI_RECONNECT_MAX + ')');

                        // Silently reconnect user
                        self.connect(MINI_DOMAIN, MINI_USER, MINI_PASSWORD);
                    });

                    JappixConsole.info('Jappix Mini is encountering connectivity issues.');
                } else {
                    // Remove the stored items
                    self.flushStorage();

                    // Notify this error
                    self.notifyError();

                    // Reset markers
                    MINI_DISCONNECT = false;
                    MINI_INITIALIZED = false;

                    JappixConsole.info('Jappix Mini is giving up. Server seems to be down.');
                }
            }

            // Normal disconnection?
            else {
                launchMini(false, MINI_SHOWPANE, MINI_DOMAIN, MINI_USER, MINI_PASSWORD);

                // Reset markers
                MINI_DISCONNECT = false;
                MINI_INITIALIZED = false;

                JappixConsole.info('Jappix Mini is now disconnected.');
            }
        } catch(e) {
            JappixConsole.error('JappixMini.disconnected', e);
        }

    };


    /**
     * Handles the incoming errors
     * @public
     * @param {object} err
     * @return {undefined}
     */
    self.handleError = function(err) {

        try {
            // First level error (connection error)
            if(jQuery(err).is('error')) {
                // Notify this error
                self.disconnected();

                JappixConsole.error('First level error received.');
            }
        } catch(e) {
            JappixConsole.error('JappixMini.handleError', e);
        }

    };


    /**
     * Handles the incoming messages
     * @public
     * @param {object} msg
     * @return {undefined}
     */
    self.handleMessage = function(msg) {

        try {
            var type = msg.getType();

            // This is a message Jappix can handle
            if((type == 'chat') || (type == 'normal') || (type == 'groupchat') || !type) {
                // Get the packet data
                var node = msg.getNode();
                var subject = jQuery.trim(msg.getSubject());
                var body = subject ? subject : jQuery.trim(msg.getBody());

                // Get the sender data
                var from = JappixCommon.fullXID(JappixCommon.getStanzaFrom(msg));
                var xid = JappixCommon.bareXID(from);
                var hash = hex_md5(xid);

                // Any attached message body?
                if(body) {
                    // Get more sender data
                    var use_xid = xid;
                    var nick = JappixCommon.thisResource(from);

                    // Read the delay
                    var delay = JappixDateUtils.readMessageDelay(node);
                    var d_stamp;

                    // Manage this delay
                    if(delay) {
                        time = JappixDateUtils.relative(delay);
                        d_stamp = Date.jab2date(delay);
                    }

                    else {
                        time = JappixDateUtils.getCompleteTime();
                        d_stamp = new Date();
                    }

                    // Get the stamp
                    var stamp = JappixDateUtils.extractStamp(d_stamp);

                    // Is this a groupchat private message?
                    if(JappixCommon.exists('#jappix_mini #chat-' + hash + '[data-type="groupchat"]')) {
                        // Regenerate some stuffs
                        if((type == 'chat') || (type == 'normal') || !type) {
                            xid = from;
                            hash = hex_md5(xid);
                        }

                        // XID to use for a groupchat
                        else {
                            use_xid = from;
                        }
                    }

                    // Message type
                    var message_type = 'user-message';

                    // Grouphat values
                    if(type == 'groupchat') {
                        // Old message
                        if(msg.getChild('delay', NS_URN_DELAY) || msg.getChild('x', NS_DELAY)) {
                            message_type = 'old-message';
                        }

                        // System message?
                        if(!nick || subject) {
                            nick = '';
                            message_type = 'system-message';
                        }
                    }

                    // Chat values
                    else {
                        nick = jQuery('#jappix_mini a#friend-' + hash).text().revertHtmlEnc();

                        // No nickname?
                        if(!nick) {
                            // If the roster does not give us any nick the user may have send us a nickname to use with his first message
                            // @see http://xmpp.org/extensions/xep-0172.html
                            var unknown_entry = jQuery('#jappix_mini a.jm_unknown[data-xid="' + xid + '"]');

                            if(unknown_entry.size() > 0) {
                                nick =  unknown_entry.attr('data-nick');
                            } else {
                                var msgnick = msg.getNick();
                                nick = JappixCommon.getXIDNick(xid);

                                if(msgnick) {
                                    // If there is a nickname in the message which differs from the jid-extracted nick then tell it to the user
                                    if(nick != msgnick)
                                         nick = msgnick + ' (' + nick + ')';
                                }

                                // Push that unknown guy in a temporary roster entry
                                unknown_entry = jQuery('<a class="jm_unknown jm_offline" href="#"></a>').attr('data-nick', nick).attr('data-xid', xid);
                                unknown_entry.appendTo('#jappix_mini div.jm_roster div.jm_buddies');
                             }
                        }
                    }

                    // Define the target div
                    var target = '#jappix_mini #chat-' + hash;

                    // Create the chat if it does not exist
                    if(!JappixCommon.exists(target) && (type != 'groupchat')) {
                        self.chat(type, xid, nick, hash);
                    }

                    // Display the message
                    self.displayMessage(type, body, use_xid, nick, hash, time, stamp, message_type);

                    // Notify the user if not focused & the message is not a groupchat old one
                    if((!jQuery(target + ' a.jm_chat-tab').hasClass('jm_clicked') || !JappixCommon.isFocused() || (MINI_ACTIVE != hash)) && (message_type == 'user-message')) {
                        // Play a sound
                        if(type != 'groupchat') {
                            self.soundPlay();
                        }

                        // Show a notification bubble
                        self.notifyMessage(hash);
                    }

                    JappixConsole.log('Message received from: ' + from);
                }

                // Chatstate groupchat filter
                if(JappixCommon.exists('#jappix_mini #chat-' + hash + '[data-type="groupchat"]')) {
                    xid = from;
                    hash = hex_md5(xid);
                }

                // Reset current chatstate
                self.resetChatstate(xid, hash, type);

                // Apply new chatstate (if supported)
                if(jQuery(node).find('active[xmlns="' + NS_CHATSTATES + '"]').size() || jQuery(node).find('composing[xmlns="' + NS_CHATSTATES + '"]').size()) {
                    // Set marker to tell other user supports chatstates
                    jQuery('#jappix_mini #chat-' + hash + ' input.jm_send-messages').attr('data-chatstates', 'true');

                    // Composing?
                    if(jQuery(node).find('composing[xmlns="' + NS_CHATSTATES + '"]').size()) {
                        self.displayChatstate('composing', xid, hash, type);
                    }
                }
            /* UA: show global messages */
            } else if (type == 'headline') {
              $('#modalLabel').html(msg.getSubject());
              $('#modalLabelClose').show();
              $('#modal').modal({keyboard: true, backdrop: false});
              $('#modal').children('div.modal-body').html('<p style="text-align: center; font-size: 14px;">'+msg.getBody()+'</p>');
            }
        } catch(e) {
            JappixConsole.error('JappixMini.handleMessage', e);
        }

    };


    /**
     * Handles the incoming IQs
     * @public
     * @param {object} iq
     * @return {undefined}
     */
    self.handleIQ = function(iq) {

        try {
            // Define some variables
            var iqFrom = JappixCommon.fullXID(JappixCommon.getStanzaFrom(iq));
            var iqID = iq.getID();
            var iqQueryXMLNS = iq.getQueryXMLNS();
            var iqType = iq.getType();
            var iqNode = iq.getNode();
            var iqQuery;

            // Build the response
            var iqResponse = new JSJaCIQ();

            iqResponse.setID(iqID);
            iqResponse.setTo(iqFrom);
            iqResponse.setType('result');

            // Software version query
            if((iqQueryXMLNS == NS_VERSION) && (iqType == 'get')) {
                /* REF: http://xmpp.org/extensions/xep-0092.html */

                iqQuery = iqResponse.setQuery(NS_VERSION);

                iqQuery.appendChild(iq.buildNode('name', {'xmlns': NS_VERSION}, 'Jappix Mini'));
                iqQuery.appendChild(iq.buildNode('version', {'xmlns': NS_VERSION}, JAPPIX_VERSION));
                iqQuery.appendChild(iq.buildNode('os', {'xmlns': NS_VERSION}, navigator.platform));

                con.send(iqResponse);

                JappixConsole.log('Received software version query: ' + iqFrom);
            }

            // Roster push
            else if((iqQueryXMLNS == NS_ROSTER) && (iqType == 'set')) {
                // Display the friend
                self.handleRoster(iq);

                con.send(iqResponse);

                JappixConsole.log('Received a roster push.');
            }

            // Disco info query
            else if((iqQueryXMLNS == NS_DISCO_INFO) && (iqType == 'get')) {
                /* REF: http://xmpp.org/extensions/xep-0030.html */

                iqQuery = iqResponse.setQuery(NS_DISCO_INFO);

                // We set the name of the client
                iqQuery.appendChild(iq.appendNode('identity', {
                    'category': 'client',
                    'type': 'web',
                    'name': 'Jappix Mini',
                    'xmlns': NS_DISCO_INFO
                }));

                // We set all the supported features
                var fArray = [
                    NS_DISCO_INFO,
                    NS_VERSION,
                    NS_ROSTER,
                    NS_MUC,
                    NS_VERSION,
                    NS_URN_TIME
                ];

                for(var i in fArray) {
                    iqQuery.appendChild(iq.buildNode('feature', {'var': fArray[i], 'xmlns': NS_DISCO_INFO}));
                }

                con.send(iqResponse);

                JappixConsole.log('Received a disco#infos query.');
            }

            // User time query
            else if(jQuery(iqNode).find('time').size() && (iqType == 'get')) {
                /* REF: http://xmpp.org/extensions/xep-0202.html */

                var iqTime = iqResponse.appendNode('time', {'xmlns': NS_URN_TIME});
                iqTime.appendChild(iq.buildNode('tzo', {'xmlns': NS_URN_TIME}, JappixDateUtils.getTZO()));
                iqTime.appendChild(iq.buildNode('utc', {'xmlns': NS_URN_TIME}, JappixDateUtils.getXMPPTime('utc')));

                con.send(iqResponse);

                JappixConsole.log('Received local time query: ' + iqFrom);
            }

            // Ping
            else if(jQuery(iqNode).find('ping').size() && (iqType == 'get')) {
                /* REF: http://xmpp.org/extensions/xep-0199.html */

                con.send(iqResponse);

                JappixConsole.log('Received a ping: ' + iqFrom);
            }

            // Not implemented
            else if(!jQuery(iqNode).find('error').size() && ((iqType == 'get') || (iqType == 'set'))) {
                // Append stanza content
                for(var c = 0; c < iqNode.childNodes.length; c++) {
                    iqResponse.getNode().appendChild(iqNode.childNodes.item(c).cloneNode(true));
                }

                // Append error content
                var iqError = iqResponse.appendNode('error', {'xmlns': NS_CLIENT, 'code': '501', 'type': 'cancel'});
                iqError.appendChild(iq.buildNode('feature-not-implemented', {'xmlns': NS_STANZAS}));
                iqError.appendChild(iq.buildNode('text', {'xmlns': NS_STANZAS}, JappixCommon._e("The feature requested is not implemented by the recipient or server and therefore cannot be processed.")));

                con.send(iqResponse);

                JappixConsole.log('Received an unsupported IQ query from: ' + iqFrom);
            }
        } catch(e) {
            JappixConsole.error('JappixMini.handleIQ', e);
        }

    };


    /**
     * Handles the incoming presences
     * @public
     * @param {object} pr
     * @return {undefined}
     */
    self.handlePresence = function(pr) {

        try {
            // Get the values
            var xml           = pr.getNode();
            var from          = JappixCommon.fullXID(JappixCommon.getStanzaFrom(pr));
            var xid           = JappixCommon.bareXID(from);
            var resource      = JappixCommon.thisResource(from);
            var resources_obj = {};

            // Is this a groupchat?
            if(JappixCommon.exists('#jappix_mini div.jm_conversation[data-type="groupchat"][data-xid="' + JappixCommon.escapeQuotes(xid) + '"]')) {
                xid = from;
            }

            // Store presence stanza
            JappixDataStore.setDB(MINI_HASH, 'jappix-mini', 'presence-stanza-' + from, pr.xml());
            resources_obj = self.addResourcePresence(xid, resource);

            // Re-process presence storage for this buddy
            self.processPresence(xid, resource, resources_obj);

            // Display that presence
            self.displayPresence(xid);

            JappixConsole.log('Presence received from: ' + from);
        } catch(e) {
            JappixConsole.error('JappixMini.handlePresence', e);
        }

    };


    /**
     * Reads a stored presence
     * @public
     * @param {string} from
     * @return {undefined}
     */
    self.readPresence = function(from) {

        try {
            var pr = JappixDataStore.getDB(MINI_HASH, 'jappix-mini', 'presence-stanza-' + from);

            if(!pr) {
                pr = '<presence type="unavailable"></presence>';
            }

            return JappixCommon.XMLFromString(pr);
        } catch(e) {
            JappixConsole.error('JappixMini.readPresence', e);
        }

    };


    /**
     * Lists presence resources for an user
     * @public
     * @param {string} xid
     * @return {object}
     */
    self.resourcesPresence = function(xid) {

        var resources_obj = {};

        try {
            var resources_db  = JappixDataStore.getDB(MINI_HASH, 'jappix-mini', 'presence-resources-' + xid);

            if(resources_db) {
                resources_obj = jQuery.evalJSON(resources_db);
            }
        } catch(e) {
            JappixConsole.error('JappixMini.resourcesPresence', e);
        } finally {
            return resources_obj;
        }

    };


    /**
     * Adds a given presence resource for an user
     * @public
     * @param {string} xid
     * @param {string} resource
     * @return {object}
     */
    self.addResourcePresence = function(xid, resource) {

        try {
            var resources_obj = self.resourcesPresence(xid);

            resources_obj[resource] = 1;
            JappixDataStore.setDB(MINI_HASH, 'jappix-mini', 'presence-resources-' + xid, jQuery.toJSON(resources_obj));

            return resources_obj;
        } catch(e) {
            JappixConsole.error('JappixMini.addResourcePresence', e);

            return null;
        }

    };


    /**
     * Removes a given presence resource for an user
     * @public
     * @param {string} xid
     * @param {string} resource
     * @return {object}
     */
    self.removeResourcePresence = function(xid, resource) {

        try {
            var resources_obj = self.resourcesPresence(xid);

            delete resources_obj[resource];
            JappixDataStore.setDB(MINI_HASH, 'jappix-mini', 'presence-resources-' + xid, jQuery.toJSON(resources_obj));

            return resources_obj;
        } catch(e) {
            JappixConsole.error('JappixMini.removeResourcePresence', e);

            return null;
        }

    };


    /**
     * Process presence storage for a given contact
     * @public
     * @param {string} xid
     * @param {string} resource
     * @param {object} resources_obj
     * @return {undefined}
     */
    self.processPresence = function(xid, resource, resources_obj) {

        try {
            if(!xid) {
                JappixConsole.warn('No XID value for precense processing.');
                return;
            }

            // Initialize vars
            var cur_resource, cur_from, cur_pr,
                cur_xml, cur_priority,
                from_highest;

            from_highest = null;
            max_priority = null;

            // Groupchat presence? (no priority here)
            if(xid.indexOf('/') !== -1) {
                from_highest = xid;

                JappixConsole.log('Processed presence for groupchat user: ' + xid);
            } else {
                if(!self.priorityPresence(xid)) {
                    from_highest = xid + '/' + resource;

                    JappixConsole.log('Processed initial presence for regular user: ' + xid + ' (highest priority for: ' + (from_highest || 'none') + ')');
                } else {
                    for(cur_resource in resources_obj) {
                        // Read presence data
                        cur_from = xid + '/' + cur_resource;
                        cur_pr   = JappixDataStore.getDB(MINI_HASH, 'jappix-mini', 'presence-stanza-' + cur_from);

                        if(cur_pr) {
                            // Parse presence data
                            cur_xml      = JappixCommon.XMLFromString(cur_pr);
                            cur_priority = jQuery(cur_xml).find('priority').text();
                            cur_priority = !isNaN(cur_priority) ? parseInt(cur_priority) : 0;

                            // Higher priority?
                            if((cur_priority >= max_priority) || (max_priority === null)) {
                                max_priority = cur_priority;
                                from_highest = cur_from;
                            }
                        }
                    }

                    JappixConsole.log('Processed presence for regular user: ' + xid + ' (highest priority for: ' + (from_highest || 'none') + ')');
                }
            }

            if(from_highest) {
                JappixDataStore.setDB(MINI_HASH, 'jappix-mini', 'presence-priority-' + xid, from_highest);
            } else {
                JappixDataStore.removeDB(MINI_HASH, 'jappix-mini', 'presence-priority-' + xid);
            }
        } catch(e) {
            JappixConsole.error('JappixMini.processPresence', e);
        }

    };


    /**
     * Returns highest presence priority
     * @public
     * @param {string} xid
     * @return {string}
     */
    self.priorityPresence = function(xid) {

        try {
            return JappixDataStore.getDB(MINI_HASH, 'jappix-mini', 'presence-priority-' + xid) || '';
        } catch(e) {
            JappixConsole.error('JappixMini.priorityPresence', e);

            return null;
        }

    };


    /**
     * Displays a Jappix Mini presence
     * @public
     * @param {string} xid
     * @return {undefined}
     */
    self.displayPresence = function(xid) {

        try {
            // Get the values
            var from     = self.priorityPresence(xid);
            var xml      = self.readPresence(from);
            var pr       = jQuery(xml).find('presence');
            var resource = JappixCommon.thisResource(from);
            var bare_xid = JappixCommon.bareXID(xid);
            var hash     = hex_md5(bare_xid);
            var type     = pr.attr('type');
            var show     = pr.find('show').text();

            // Manage the received presence values
            if((type == 'error') || (type == 'unavailable')) {
                show = 'unavailable';
            } else {
                switch(show) {
                    case 'chat':
                    case 'away':
                    case 'xa':
                    case 'dnd':
                        break;

                    default:
                        show = 'available';

                        break;
                }
            }

            // Is this a groupchat presence?
            var groupchat_path = '#jappix_mini #chat-' + hash + '[data-type="groupchat"]';
            var is_groupchat = false;

            if(JappixCommon.exists(groupchat_path)) {
                // Groupchat exists
                is_groupchat = true;

                // Groupchat buddy presence (not me)
                if(resource != JappixCommon.unescapeQuotes(jQuery(groupchat_path).attr('data-nick'))) {
                    // Regenerate some stuffs
                    var groupchat = xid;
                    var groupchat_hash = hash;
                    xid = from;
                    hash = hex_md5(xid);

                    // Process this groupchat user presence
                    var log_message;

                    if(show == 'unavailable') {
                        // Remove from roster view
                        self.removeBuddy(hash, groupchat);

                        // Generate log message
                        log_message = JappixCommon.printf(JappixCommon._e("%s left"), resource.htmlEnc());
                    } else {
                        // Add to roster view
                        self.addBuddy(xid, hash, resource, groupchat);

                        // Generate log message
                        log_message = JappixCommon.printf(JappixCommon._e("%s joined"), resource.htmlEnc());
                    }

                    // Log message in chat view
                    if(MINI_GROUPCHAT_PRESENCE && log_message && (jQuery(groupchat_path).attr('data-init') == 'true')) {
                        self.displayMessage('groupchat', log_message, xid, '', groupchat_hash, JappixDateUtils.getCompleteTime(), JappixDateUtils.getTimeStamp(), 'system-message');
                    }
                }
            }

            // Friend path
            var chat = '#jappix_mini #chat-' + hash;
            var friend = '#jappix_mini a#friend-' + hash;
            var send_input = chat + ' input.jm_send-messages';

            // Is this friend online?
            if(show == 'unavailable') {
                // Offline marker
                jQuery(friend).addClass('jm_offline').removeClass('jm_online jm_hover');

                // Hide the friend just to be safe since the search uses .hide() and .show() which can override the CSS display attribute
                jQuery(friend).hide();

                // Disable the chat tools
                if(is_groupchat) {
                    jQuery(chat).addClass('jm_disabled').attr('data-init', 'false');
                    jQuery(send_input).blur().attr('disabled', true).attr('data-value', JappixCommon._e("Unavailable")).val(JappixCommon._e("Unavailable"));
                }
            } else {
                // Online marker
                jQuery(friend).removeClass('jm_offline').addClass('jm_online');

                // Check against search string
                var search = jQuery('#jappix_mini div.jm_roster div.jm_search input.jm_searchbox').val();
                var regex = new RegExp('((^)|( ))' + JappixCommon.escapeRegex(search), 'gi');
                var nick = JappixCommon.unescapeQuotes(jQuery(friend).data('nick'));

                if(search && !nick.match(regex)) {
                    jQuery(friend).hide();
                } else {
                    jQuery(friend).show();
                }

                // Enable the chat input
                if(is_groupchat) {
                    jQuery(chat).removeClass('jm_disabled');
                    jQuery(send_input).removeAttr('disabled').val('');
                }
            }

            // Change the show presence of this buddy
            jQuery(friend + ' span.jm_presence, ' + chat + ' span.jm_presence').attr('class', 'jm_presence jm_images jm_' + show);

            // Update the presence counter
            self.updateRoster();

            // Update groups visibility
            self.updateGroups();

            JappixConsole.log('Presence displayed for user: ' + xid);
        } catch(e) {
            JappixConsole.error('JappixMini.displayPresence', e);
        }

    };


    /**
     * Handles the MUC main elements
     * @public
     * @param {object} pr
     * @return {undefined}
     */
    self.handleMUC = function(pr) {

        try {
            // We get the xml content
            var xml = pr.getNode();
            var from = JappixCommon.fullXID(JappixCommon.getStanzaFrom(pr));
            var room = JappixCommon.bareXID(from);
            var hash = hex_md5(room);
            var resource = JappixCommon.thisResource(from);

            // Is it a valid server presence?
            var valid = false;

            if(!resource || (resource == JappixCommon.unescapeQuotes(jQuery('#jappix_mini #chat-' + hash + '[data-type="groupchat"]').attr('data-nick')))) {
                valid = true;
            }

            // Password required?
            if(valid && jQuery(xml).find('error[type="auth"] not-authorized').size()) {
                // Create a new prompt
                self.openPrompt(JappixCommon.printf(JappixCommon._e("This room (%s) is protected with a password."), room));

                // When prompt submitted
                jQuery('#jappix_popup div.jm_prompt form').submit(function() {
                    try {
                        // Read the value
                        var password = self.closePrompt();

                        // Any submitted chat to join?
                        if(password) {
                            // Send the password
                            self.presence('', '', '', '', from, password, true, self.handleMUC);

                            // Focus on the pane again
                            self.switchPane('chat-' + hash, hash);
                        }
                    }

                    catch(e) {}

                    finally {
                        return false;
                    }
                });

                return;
            }

            // Nickname conflict?
            else if(valid && jQuery(xml).find('error[type="cancel"] conflict').size()) {
                // New nickname
                var nickname = resource + '_';

                // Send the new presence
                self.presence('', '', '', '', room + '/' + nickname, '', true, self.handleMUC);

                // Update the nickname marker
                jQuery('#jappix_mini #chat-' + hash).attr('data-nick', JappixCommon.escapeQuotes(nickname));
            }

            // Handle normal presence
            else {
                // Start the initial timer
                jQuery('#jappix_mini #chat-' + hash).oneTime('10s', function() {
                    jQuery(this).attr('data-init', 'true');
                });

                // Trigger presence handler
                self.handlePresence(pr);
            }
        } catch(e) {
            JappixConsole.error('JappixMini.handleMUC', e);
        }

    };


    /**
     * Updates the user presence
     * @public
     * @param {string} type
     * @param {string} show
     * @param {number} priority
     * @param {string} status
     * @param {string} to
     * @param {string} password
     * @param {boolean} limit_history
     * @param {function} handler
     * @return {undefined}
     */
    self.presence = function(type, show, priority, status, to, password, limit_history, handler) {

        try {
            var pr = new JSJaCPresence();

            // Add the attributes
            if(to)
                pr.setTo(to);
            if(type)
                pr.setType(type);
            if(show)
                pr.setShow(show);
            if(status)
                pr.setStatus(status);

            if(priority) {
                pr.setPriority(priority);
            } else if(MINI_PRIORITY && !to) {
                pr.setPriority(MINI_PRIORITY);
            }

            // Special presence elements
            if(password || limit_history) {
                var x = pr.appendNode('x', {'xmlns': NS_MUC});

                // Any password?
                if(password) {
                    x.appendChild(pr.buildNode('password', {'xmlns': NS_MUC}, password));
                }

                // Any history limit?
                if(limit_history) {
                    x.appendChild(pr.buildNode('history', {'maxstanzas': 10, 'seconds': 86400, 'xmlns': NS_MUC}));
                }
            }

            // Send the packet
            if(handler) {
                con.send(pr, handler);
            } else {
                con.send(pr);
            }

            JappixConsole.info('Presence sent (to: ' + (to || 'none') + ', show: ' + (show || 'none') + ', type: ' + (type || 'none') + ')');
        } catch(e) {
            JappixConsole.error('JappixMini.presence', e);
        }

    };


    /**
     * Sends a given message
     * @public
     * @param {object} aForm
     * @return {boolean}
     */
    self.sendMessage = function(aForm) {

        try {
            var body = jQuery.trim(aForm.body.value);
            var xid = aForm.xid.value;
            var type = aForm.type.value;
            var hash = hex_md5(xid);

            if(body && xid) {
                // Send the message
                var aMsg = new JSJaCMessage();

                // If the roster does not give us any nick the user may have send us a nickname to use with his first message
                // @see http://xmpp.org/extensions/xep-0172.html
                var known_roster_entry = jQuery('#jappix_mini a.jm_friend[data-xid="' + JappixCommon.escapeQuotes(xid) + '"]');

                if(known_roster_entry.size() === 0) {
                    var subscription = known_roster_entry.attr('data-sub');

                    // The other may not know my nickname if we do not have both a roster entry, or if he doesn't have one
                    if(('both' != subscription) && ('from' != subscription)) {
                        aMsg.setNick(MINI_NICKNAME);
                    }
                }

                // Message data
                aMsg.setTo(xid);
                aMsg.setType(type);
                aMsg.setBody(body);

                // Chatstate
                aMsg.appendNode('active', {'xmlns': NS_CHATSTATES});

                // Send it!
                self.enqueue(aMsg);

                // Clear the input
                aForm.body.value = '';

                // Display the message we sent
                if(type != 'groupchat') {
                    self.displayMessage(type, body, JappixCommon.getXID(), 'me', hash, JappixDateUtils.getCompleteTime(), JappixDateUtils.getTimeStamp(), 'user-message');
                }

                JappixConsole.log('Message (' + type + ') sent to: ' + xid);
            }
        } catch(e) {
            JappixConsole.error('JappixMini.sendMessage', e);
        } finally {
            return false;
        }

    };


    /**
     * Enqueues a stanza (to be sent over the network)
     * @public
     * @param {object} stanza
     * @return {undefined}
     */
    self.enqueue = function(stanza) {

        try {
            // Send stanza over the network or enqueue it?
            if(JappixCommon.isConnected()) {
                con.send(stanza);
            } else {
                MINI_QUEUE.push(
                    stanza.xml()
                );

                JappixConsole.log('Enqueued an event (to be sent when connectivity is back).');
            }
        } catch(e) {
            JappixConsole.error('JappixMini.enqueue', e);
        }

    };


    /**
     * Dequeues stanzas and send them over the network
     * @public
     * @return {undefined}
     */
    self.dequeue = function() {

        try {
            var stanza_str, stanza_childs,
            stanza;

            // Execute deferred tasks
            while(MINI_QUEUE.length) {
                stanza_str = MINI_QUEUE.shift();
                stanza_childs = JappixCommon.XMLFromString(stanza_str).childNodes;

                if(stanza_childs && stanza_childs[0]) {
                    stanza = JSJaCPacket.wrapNode(stanza_childs[0]);
                    con.send(stanza);
                }

                JappixConsole.log('Dequeued a stanza.');
            }
        } catch(e) {
            JappixConsole.error('JappixMini.dequeue', e);
        }

    };


    /**
     * Serializes and store the queue storage
     * @public
     * @return {undefined}
     */
    self.serializeQueue = function() {

        try {
            JappixDataStore.setDB(MINI_HASH, 'jappix-mini', 'queue', jQuery.toJSON(MINI_QUEUE));
        } catch(e) {
            JappixConsole.error('JappixMini.serializeQueue', e);
        }

    };


    /**
     * Unserializes and update the queue storage
     * @public
     * @return {undefined}
     */
    self.unserializeQueue = function() {

        try {
            var start_body, end_body,
            start_args, end_args;

            var s_queue = JappixDataStore.getDB(MINI_HASH, 'jappix-mini', 'queue');
            JappixDataStore.removeDB(MINI_HASH, 'jappix-mini', 'queue');

            if(s_queue) {
                MINI_QUEUE = jQuery.evalJSON(s_queue);
            }
        } catch(e) {
            JappixConsole.error('JappixMini.unserialize', e);
        }

    };


    /**
     * Generates the asked smiley image
     * @public
     * @param {string} image
     * @param {string} text
     * @return {string}
     */
    self.smiley = function(image, text) {

        try {
            return ' <img class="jm_smiley jm_smiley-' + image + ' jm_images" alt="' + JappixCommon.encodeQuotes(text) + '" src="' + JAPPIX_STATIC + 'images/jappix/others/blank.gif' + '" /> ';
        } catch(e) {
            JappixConsole.error('JappixMini.smiley', e);

            return null;
        }

    };


    /**
     * Notifies incoming chat messages
     * @public
     * @param {string} hash
     * @return {undefined}
     */
    self.notifyMessage = function(hash) {

        try {
            // Define the paths
            var tab = '#jappix_mini #chat-' + hash + ' a.jm_chat-tab';
            var notify = tab + ' span.jm_notify';
            var notify_middle = notify + ' span.jm_notify_middle';

            // Notification box not yet added?
            if(!JappixCommon.exists(notify)) {
                jQuery(tab).append(
                    '<span class="jm_notify">' +
                        '<span class="jm_notify_left jm_images"></span>' +
                        '<span class="jm_notify_middle">0</span>' +
                        '<span class="jm_notify_right jm_images"></span>' +
                    '</span>'
                );
            }

            // Increment the notification number
            var number = parseInt(jQuery(notify_middle).text());

            /* UA: Disable Notify counter */
            var disable_notify = false;
            for(var g=0; g < application_groupchat_config.disable_notify.length; g++) {
              if (hex_md5(JappixCommon.bareXID(application_groupchat_config.disable_notify[g], 'groupchat')) == hash) {
                disable_notify = true;
                break;
              }
            }

            if (disable_notify) {
              jQuery(notify_middle).html('&nbsp;');
            } else {
              jQuery(notify_middle).text(number + 1);
            }
            
            // Update the notification counters
            self.notifyCounters();
        } catch(e) {
            JappixConsole.error('JappixMini.notifyMessage', e);
        }

    };


    /**
     * Notifies the user from a session error
     * @public
     * @return {undefined}
     */
    self.notifyError = function() {

        try {
            // Replace the Jappix Mini DOM content
            jQuery('#jappix_mini').html(
                '<div class="jm_starter">' +
                    '<a class="jm_pane jm_button jm_images" href="' + MINI_ERROR_LINK + '" target="_blank" title="' + JappixCommon._e("Click here to solve the error") + '">' +
                        '<span class="jm_counter jm_error jm_images">' + JappixCommon._e("Error") + '</span>' +
                    '</a>' +
                '</div>'
            );
        } catch(e) {
            JappixConsole.error('JappixMini.notifyError', e);
        }

    };


    /**
     * Updates the global counter with the new notifications
     * @public
     * @return {undefined}
     */
    self.notifyCounters = function() {

        try {
            // Count the number of notifications
            var number = 0;

            jQuery('#jappix_mini span.jm_notify span.jm_notify_middle').each(function() {
                number = number + parseInt(jQuery(this).text());
            });

            // Update the notification link counters
            jQuery('#jappix_mini a.jm_switch').removeClass('jm_notifnav');

            if(number) {
                // Left?
                if(jQuery('#jappix_mini div.jm_conversation:visible:first').prevAll().find('span.jm_notify').size())
                    jQuery('#jappix_mini a.jm_switch.jm_left').addClass('jm_notifnav');

                // Right?
                if(jQuery('#jappix_mini div.jm_conversation:visible:last').nextAll().find('span.jm_notify').size())
                    jQuery('#jappix_mini a.jm_switch.jm_right').addClass('jm_notifnav');
            }

            // No saved title? Abort!
            if(MINI_TITLE === null) {
                return;
            }

            // Page title code
            var title = MINI_TITLE;

            // No new stuffs? Reset the title!
            if(number) {
                title = '[' + number + '] ' + title;
            }

            // Apply the title
            document.title = title;
        } catch(e) {
            JappixConsole.error('JappixMini.notifyCounters', e);
        }

    };


    /**
     * Clears the notifications
     * @public
     * @param {string} hash
     * @return {boolean}
     */
    self.clearNotifications = function(hash) {

        try {
            // Not focused?
            if(!JappixCommon.isFocused()) {
                return false;
            }

            // Remove the notifications counter
            jQuery('#jappix_mini #chat-' + hash + ' span.jm_notify').remove();

            // Update the global counters
            self.notifyCounters();

            return true;
        } catch(e) {
            JappixConsole.error('JappixMini.clearNotifications', e);

            return false;
        }

    };


    /**
     * Updates the roster counter
     * @public
     * @return {undefined}
     */
    self.updateRoster = function() {

        try {
            // Update online counter
            jQuery('#jappix_mini a.jm_button span.jm_counter').text(jQuery('#jappix_mini a.jm_online').size());
        } catch(e) {
            JappixConsole.error('JappixMini.updateRoster', e);
        }

    };


    /**
     * Updates the visibility of roster groups
     * @public
     * @return {undefined}
     */
    self.updateGroups = function() {

        try {
            jQuery('.jm_grouped_roster').filter(':not(:has(.jm_friend.jm_online:visible))').hide();
        } catch(e) {
            JappixConsole.error('JappixMini.updateGroups', e);
        }

    };


    /**
     * Updates the chat overflow
     * @public
     * @return {undefined}
     */
    self.updateOverflow = function() {

        try {
            // Process overflow
            var number_visible = parseInt((jQuery(window).width() - 380) / 140);
            var number_visible_dom = jQuery('#jappix_mini div.jm_conversation:visible').size();

            if(number_visible <= 0) {
                number_visible = 1;
            }

            // Need to reprocess?
            if(number_visible != number_visible_dom) {
                // Show hidden chats
                jQuery('#jappix_mini div.jm_conversation:hidden').show();

                // Get total number of chats
                var number_total = jQuery('#jappix_mini div.jm_conversation').size();

                // Must add the overflow switcher?
                if(number_visible < number_total) {
                    // Create the overflow handler?
                    if(!jQuery('#jappix_mini a.jm_switch').size()) {
                        // Add the navigation links
                        jQuery('#jappix_mini div.jm_conversations').before(
                            '<a class="jm_switch jm_left jm_pane jm_images" href="#">' +
                                '<span class="jm_navigation jm_images"></span>' +
                            '</a>'
                        );

                        jQuery('#jappix_mini div.jm_conversations').after(
                            '<a class="jm_switch jm_right jm_pane jm_images" href="#">' +
                                '<span class="jm_navigation jm_images"></span>' +
                            '</a>'
                        );

                        // Add the click events
                        self.overflowEvents();
                    }

                    // Show first visible chats
                    var first_visible = jQuery('#jappix_mini div.jm_conversation:visible:first').index();
                    var index_visible = number_visible - first_visible - 1;

                    jQuery('#jappix_mini div.jm_conversation:visible:gt(' + index_visible + ')').hide();

                    // Close the opened chat
                    if(jQuery('#jappix_mini div.jm_conversation:hidden a.jm_pane.jm_clicked').size()) {
                        self.switchPane();
                    }

                    // Update navigation buttons
                    jQuery('#jappix_mini a.jm_switch').removeClass('jm_nonav');

                    if(!jQuery('#jappix_mini div.jm_conversation:visible:first').prev().size()) {
                        jQuery('#jappix_mini a.jm_switch.jm_left').addClass('jm_nonav');
                    }

                    if(!jQuery('#jappix_mini div.jm_conversation:visible:last').next().size()) {
                        jQuery('#jappix_mini a.jm_switch.jm_right').addClass('jm_nonav');
                    }
                }

                // Must remove the overflow switcher?
                else {
                    jQuery('#jappix_mini a.jm_switch').remove();
                    jQuery('#jappix_mini div.jm_conversation:hidden').show();
                }
            }
        } catch(e) {
            JappixConsole.error('JappixMini.updateOverflow', e);
        }

    };


    /**
     * Click events on the chat overflow
     * @public
     * @return {undefined}
     */
    self.overflowEvents = function() {

        try {
            jQuery('#jappix_mini a.jm_switch').click(function() {
                var this_sel = jQuery(this);

                // Nothing to do?
                if(this_sel.hasClass('jm_nonav')) {
                    return false;
                }

                var hide_this = '';
                var show_this = '';

                // Go left?
                if(this_sel.is('.jm_left')) {
                    show_this = jQuery('#jappix_mini div.jm_conversation:visible:first').prev();

                    if(show_this.size()) {
                        hide_this = jQuery('#jappix_mini div.jm_conversation:visible:last');
                    }
                }

                // Go right?
                else {
                    show_this = jQuery('#jappix_mini div.jm_conversation:visible:last').next();

                    if(show_this.size()) {
                        hide_this = jQuery('#jappix_mini div.jm_conversation:visible:first');
                    }
                }

                // Update overflow content
                if(show_this && show_this.size()) {
                    // Hide
                    if(hide_this && hide_this.size()) {
                        hide_this.hide();

                        // Close the opened chat
                        if(hide_this.find('a.jm_pane').hasClass('jm_clicked')) {
                            self.switchPane();
                        }
                    }

                    // Show
                    show_this.show();

                    // Update navigation buttons
                    jQuery('#jappix_mini a.jm_switch').removeClass('jm_nonav');

                    if((this_sel.is('.jm_right') && !show_this.next().size()) || (this_sel.is('.jm_left') && !show_this.prev().size())) {
                        this_sel.addClass('jm_nonav');
                    }

                    // Update notification counters
                    self.notifyCounters();
                }

                return false;
            });
        } catch(e) {
            JappixConsole.error('JappixMini.overflowEvents', e);
        }

    };


    /**
     * Creates the Jappix Mini DOM content
     * @public
     * @param {string} domain
     * @param {string} user
     * @param {string} password
     * @return {undefined}
     */
    self.create = function(domain, user, password) {

        try {
            // Try to restore the DOM
            var dom = JappixDataStore.getDB(MINI_HASH, 'jappix-mini', 'dom');
            var suspended = false;
            var resumed = false;

            // Reset DOM storage (free memory)
            JappixDataStore.removeDB(MINI_HASH, 'jappix-mini', 'dom');

            // Invalid stored DOM?
            if(dom && isNaN(jQuery(dom).find('a.jm_pane.jm_button span.jm_counter').text())) {
                dom = null;
            }

            // Old DOM? (saved session)
            if(dom) {
                // Attempt to resume connection
                con = new JSJaCHttpBindingConnection();

                self.setupCon(con);
                resumed = con.resume();

                // Read the old nickname
                MINI_NICKNAME = JappixDataStore.getDB(MINI_HASH, 'jappix-mini', 'nickname');

                // Marker
                suspended = true;
                MINI_ROSTER_INIT = true;
            }

            // New DOM?
            else {
                dom = '<div class="jm_position">' +
                        '<div class="jm_conversations"></div>' +

                        '<div class="jm_starter">' +
                            '<div class="jm_roster">' +
                                '<div class="jm_actions">' +
                                    '<a class="jm_logo jm_images" href="https://mini.jappix.com/" target="_blank"></a>' +
                                    '<a class="jm_one-action jm_join jm_images" title="' + JappixCommon._e("Join a chat") + '" href="#"></a>' +
                                    '<a class="jm_one-action jm_status" title="' + JappixCommon._e("Status") + '" href="#">' +
                                        '<span class="jm_presence jm_images jm_available"></span>' +
                                    '</a>' +

                                    '<div class="jm_status_picker">' +
                                        '<a href="#" data-status="available">' +
                                            '<span class="jm_presence jm_images jm_available"></span>' +
                                            '<span class="jm_show_text">' + JappixCommon._e("Available") + '</span>' +
                                        '</a>' +

                                        '<a href="#" data-status="away">' +
                                            '<span class="jm_presence jm_images jm_away"></span>' +
                                            '<span class="jm_show_text">' + JappixCommon._e("Away") + '</span>' +
                                        '</a>' +

                                        '<a href="#" data-status="dnd">' +
                                            '<span class="jm_presence jm_images jm_dnd"></span>' +
                                            '<span class="jm_show_text">' + JappixCommon._e("Busy") + '</span>' +
                                        '</a>' +

                                        '<a href="#" data-status="unavailable">' +
                                            '<span class="jm_presence jm_images jm_unavailable"></span>' +
                                            '<span class="jm_show_text">' + JappixCommon._e("Offline") + '</span>' +
                                        '</a>' +
                                    '</div>' +
                                '</div>' +
                                '<div class="jm_buddies"></div>' +
                                '<div class="jm_search">' +
                                    '<input type="text" class="jm_searchbox jm_images" placeholder="' + JappixCommon._e("Filter") + '" data-value="" />' +
                                '</div>' +
                            '</div>' +

                            '<a class="jm_pane jm_button jm_images" href="#">' +
                                '<span class="jm_counter jm_images">' + JappixCommon._e("Please wait...") + '</span>' +
                            '</a>' +
                        '</div>' +
                      '</div>';
            }

            // Create the DOM
            jQuery('body').append('<div id="jappix_mini" style="display: none;" dir="' + (JappixCommon.isRTL() ? 'rtl' : 'ltr') + '">' + dom + '</div>');

            // Hide the roster picker panels
            jQuery('#jappix_mini a.jm_status.active, #jappix_mini a.jm_join.active').removeClass('active');
            jQuery('#jappix_mini div.jm_status_picker').hide();
            jQuery('#jappix_mini div.jm_chan_suggest').remove();

            // Chat navigation overflow events
            self.overflowEvents();

            // Delay to fix DOM lag bug (CSS file not yet loaded)
            jQuery('#jappix_mini').everyTime(100, function() {
                var this_sel = jQuery(this);

                if(this_sel.is(':visible')) {
                    JappixConsole.info('CSS loaded asynchronously.');

                    this_sel.stopTime();

                    // Re-process chat overflow
                    self.updateOverflow();

                    // Adapt roster height
                    self.adaptRoster();

                    // Update current pixel streams
                    self.updatePixStream();
                }
            });

            // Auto-check if ads should be added
            if(ADS_ENABLE === 'on' && GADS_CLIENT && GADS_SLOT) {
                jQuery('#jappix_mini div.jm_conversations').everyTime('60s', function() {
                    JappixConsole.debug('JappixMini.create[timer]', 'Auto-updating ads...');

                    self.updatePixStream();

                    JappixConsole.debug('JappixMini.create[timer]', 'Done auto-updating ads.');
                });
            }

            // CSS refresh (Safari display bug when restoring old DOM)
            jQuery('#jappix_mini div.jm_starter').css('float', 'right');
            jQuery('#jappix_mini div.jm_conversations, #jappix_mini div.jm_conversation, #jappix_mini a.jm_switch').css('float', 'left');

            // The click events
            jQuery('#jappix_mini a.jm_button').click(function() {
                // Using a try/catch override IE issues
                try {
                    // Presence counter
                    var counter = '#jappix_mini a.jm_pane.jm_button span.jm_counter';

                    // Cannot open the roster?
                    if(jQuery(counter).text() == JappixCommon._e("Please wait...")) {
                        return false;
                    }

                    // Not yet connected?
                    if(jQuery(counter).text() == JappixCommon._e("Chat")) {
                        // Remove the animated bubble
                        jQuery('#jappix_mini div.jm_starter span.jm_animate').remove();

                        // Add a waiting marker
                        jQuery(counter).text(JappixCommon._e("Please wait..."));

                        // Launch the connection!
                        self.connect(domain, user, password);

                        return false;
                    }

                    // Normal actions
                    if(!jQuery(this).hasClass('jm_clicked')) {
                        self.showRoster();
                    } else {
                        self.hideRoster();
                    }
                }

                catch(e) {}

                finally {
                    return false;
                }
            });

            jQuery('#jappix_mini a.jm_status').click(function() {
                // Using a try/catch override IE issues
                try {
                    var this_sel = jQuery(this);
                    var is_active = this_sel.hasClass('active');

                    jQuery('#jappix_mini div.jm_actions a').blur().removeClass('active');

                    if(is_active) {
                        jQuery('#jappix_mini div.jm_status_picker').hide();
                    } else {
                        jQuery('#jappix_mini div.jm_chan_suggest').remove();
                        jQuery('#jappix_mini div.jm_status_picker').show();
                        this_sel.addClass('active');
                    }
                }

                catch(e) {}

                finally {
                    return false;
                }
            });

            jQuery('#jappix_mini div.jm_status_picker a').click(function() {
                // Using a try/catch override IE issues
                try {
                    var this_sel = jQuery(this);

                    // Generate an array of presence change XIDs
                    var pr_xid = [''];

                    jQuery('#jappix_mini div.jm_conversation[data-type="groupchat"]').each(function() {
                        var this_sub_sel = jQuery(this);
                        pr_xid.push(JappixCommon.unescapeQuotes(this_sub_sel.attr('data-xid')) + '/' + JappixCommon.unescapeQuotes(this_sub_sel.attr('data-nick')));
                    });

                    // Loop on XIDs
                    var new_status = this_sel.data('status');

                    jQuery.each(pr_xid, function(key, value) {
                        switch(new_status) {
                            case 'available':
                                self.presence('', '', '', '', value);
                                break;

                            case 'away':
                                self.presence('', 'away', '', '', value);
                                break;

                            case 'dnd':
                                self.presence('', 'dnd', '', '', value);
                                break;

                            case 'unavailable':
                                self.disconnect();
                                break;

                            default:
                                self.presence('', '', '', '', value);
                                break;
                        }
                    });

                    // Switch the status
                    if(new_status != 'unavailable') {
                        jQuery('#jappix_mini a.jm_status span').removeClass('jm_available jm_away jm_dnd jm_unavailable')
                                                               .addClass('jm_' + this_sel.data('status'));

                        jQuery('#jappix_mini div.jm_status_picker').hide();
                        jQuery('#jappix_mini a.jm_status').blur().removeClass('active');
                    }
                }

                catch(e) {}

                finally {
                    return false;
                }
            });

            jQuery('#jappix_mini div.jm_actions a.jm_join').click(function() {
                // Using a try/catch override IE issues
                try {
                    var this_sel = jQuery(this);

                    // Any suggested chat/groupchat?
                    if((MINI_SUGGEST_CHATS && MINI_SUGGEST_CHATS.length) || (MINI_SUGGEST_GROUPCHATS && MINI_SUGGEST_GROUPCHATS.length)) {
                        var is_active = this_sel.hasClass('active');
                        jQuery('#jappix_mini div.jm_actions a').blur().removeClass('active');

                        if(is_active) {
                            jQuery('#jappix_mini div.jm_chan_suggest').remove();
                        } else {
                            // Button style
                            jQuery('#jappix_mini div.jm_status_picker').hide();
                            this_sel.addClass('active');

                            // Generate selector code
                            var chans_html = '';

                            // Generate the groupchat links HTML
                            for(var i = 0; i < MINI_SUGGEST_GROUPCHATS.length; i++) {
                                // Empty value?
                                if(!MINI_SUGGEST_GROUPCHATS[i]) {
                                    continue;
                                }

                                // Using a try/catch override IE issues
                                try {
                                    var chat_room = JappixCommon.bareXID(JappixCommon.generateXID(MINI_SUGGEST_GROUPCHATS[i], 'groupchat'));
                                    var chat_pwd = MINI_SUGGEST_PASSWORDS[i] || '';

                                    chans_html +=
                                    '<a class="jm_suggest_groupchat" href="#" data-xid="' + JappixCommon.escapeQuotes(chat_room) + '" data-pwd="' + JappixCommon.escapeQuotes(chat_pwd) + '">' +
                                        '<span class="jm_chan_icon jm_images"></span>' +
                                        '<span class="jm_chan_name">' + JappixCommon.getXIDNick(chat_room).htmlEnc() + '</span>' +
                                    '</a>';
                                }

                                catch(e) {}
                            }

                            // Any separation space to add?
                            if(chans_html) {
                                chans_html += '<div class="jm_space"></div>';
                            }

                            // Generate the chat links HTML
                            for(var j = 0; j < MINI_SUGGEST_CHATS.length; j++) {
                                // Empty value?
                                if(!MINI_SUGGEST_CHATS[j]) {
                                    continue;
                                }

                                // Using a try/catch override IE issues
                                try {
                                    // Read current chat values
                                    var chat_xid = JappixCommon.bareXID(JappixCommon.generateXID(MINI_SUGGEST_CHATS[j], 'chat'));
                                    var chat_hash = hex_md5(chat_xid);
                                    var chat_nick = jQuery('#jappix_mini a#friend-' + chat_hash).attr('data-nick');

                                    // Get current chat nickname
                                    if(!chat_nick) {
                                        chat_nick = JappixCommon.getXIDNick(chat_xid);
                                    } else {
                                        chat_nick = JappixCommon.unescapeQuotes(chat_nick);
                                    }

                                    // Generate HTML for current chat
                                    chans_html +=
                                    '<a class="jm_suggest_chat" href="#" data-xid="' + JappixCommon.escapeQuotes(chat_xid) + '">' +
                                        '<span class="jm_chan_icon jm_images"></span>' +
                                        '<span class="jm_chan_name">' + JappixCommon.getXIDNick(chat_nick).htmlEnc() + '</span>' +
                                    '</a>';
                                }

                                catch(e) {}
                            }

                            // Any separation space to add?
                            if(chans_html) {
                                chans_html += '<div class="jm_space"></div>';
                            }

                            // Append selector code
                            jQuery('#jappix_mini div.jm_actions').append(
                                '<div class="jm_chan_suggest">' +
                                    chans_html +
/* UA: Delete this. Not needed
                                    '<a class="jm_suggest_prompt" href="#">' +
                                        '<span class="jm_chan_icon"></span>' +
                                        '<span class="jm_chan_name">' + JappixCommon._e("Other") + '</span>' +
                                    '</a>' +
*/
                                '</div>'
                            );

                            // Click events
                            jQuery('#jappix_mini div.jm_chan_suggest a').click(function() {
                                // Using a try/catch override IE issues
                                try {
                                    var this_sub_sel = jQuery(this);

                                    // Chat?
                                    if(this_sub_sel.is('.jm_suggest_chat')) {
                                        var current_chat = JappixCommon.unescapeQuotes(this_sub_sel.attr('data-xid'));

                                        self.chat('chat', current_chat, this_sub_sel.find('span.jm_chan_name').text(), hex_md5(current_chat));
                                    }

                                    // Groupchat?
                                    else if(this_sub_sel.is('.jm_suggest_groupchat')) {
                                        var current_groupchat = JappixCommon.unescapeQuotes(this_sub_sel.attr('data-xid'));
                                        var current_password = this_sub_sel.attr('data-pwd') || null;

                                        if(current_password)
                                            current_password = JappixCommon.unescapeQuotes(current_password);

                                        self.chat('groupchat', current_groupchat, this_sub_sel.find('span.jm_chan_name').text(), hex_md5(current_groupchat), current_password);
                                    }

                                    // Default prompt?
                                    else {
                                        self.groupchatPrompt();
                                    }
                                }

                                catch(e) {}

                                finally {
                                    return false;
                                }
                            });

                            // Adapt chan suggest height
                            self.adaptRoster();
                        }
                    }

                    // Default action
                    else {
                        self.groupchatPrompt();
                    }
                }

                catch(e) {}

                finally {
                    return false;
                }
            });

            // Updates the roster with only searched terms
            jQuery('#jappix_mini div.jm_roster div.jm_search input.jm_searchbox').keyup(function(e) {
                var this_sel = jQuery(this);

                // Avoid buddy navigation to be reseted
                if((e.keyCode == 38) || (e.keyCode == 40)) {
                    return;
                }

                // Escape key pressed?
                if(e.keyCode == 27) {
                    this_sel.val('');
                }

                // Save current value
                this_sel.attr('data-value', this_sel.val());

                // Don't filter at each key up (faster for computer)
                var _this = this;

                JappixCommon.typewatch()(function() {
                    // Using a try/catch to override IE issues
                    try {
                        // Get values
                        var search = jQuery(_this).val();
                        var regex = new RegExp('((^)|( ))' + JappixCommon.escapeRegex(search), 'gi');

                        // Reset results
                        jQuery('#jappix_mini a.jm_friend.jm_hover').removeClass('jm_hover');
                        jQuery('#jappix_mini div.jm_roster div.jm_grouped').show();

                        // If there is no search, we don't need to loop over buddies
                        if(!search) {
                            jQuery('#jappix_mini div.jm_roster div.jm_buddies a.jm_online').show();

                            // Update groups visibility
                            self.updateGroups();

                            return;
                        }

                        // Filter buddies
                        jQuery('#jappix_mini div.jm_roster div.jm_buddies a.jm_online').each(function() {
                            var this_sub_sel = jQuery(this);
                            var nick = JappixCommon.unescapeQuotes(this_sub_sel.data('nick'));

                            if(nick.match(regex)) {
                                this_sub_sel.show();
                            } else {
                                this_sub_sel.hide();
                            }
                        });

                        // Filter groups
                        jQuery('#jappix_mini div.jm_roster div.jm_grouped').each(function() {
                            var this_sub_sel = jQuery(this);

                            if(!this_sub_sel.find('a.jm_online:visible').size()) {
                                this_sub_sel.hide();
                            }
                        });

                        // Focus on the first buddy
                        jQuery('#jappix_mini div.jm_roster div.jm_buddies a.jm_online:visible:first').addClass('jm_hover');
                    }

                    catch(e) {}

                    finally {
                        return false;
                    }
                }, 500);
            });

            // Roster navigation
            jQuery(document).keydown(function(e) {
                // Cannot work if roster is not opened
                if(jQuery('#jappix_mini div.jm_roster').is(':hidden')) {
                    return;
                }

                // Up/Down keys
                if((e.keyCode == 38) || (e.keyCode == 40)) {
                    // Hover the last/first buddy
                    if(!jQuery('#jappix_mini a.jm_online.jm_hover').size()) {
                        if(e.keyCode == 38) {
                            jQuery('#jappix_mini a.jm_online:visible:last').addClass('jm_hover');
                        } else {
                            jQuery('#jappix_mini a.jm_online:visible:first').addClass('jm_hover');
                        }
                    }

                    // Hover the previous/next buddy
                    else if(jQuery('#jappix_mini a.jm_online:visible').size() > 1) {
                        var hover_index = jQuery('#jappix_mini a.jm_online:visible').index(jQuery('a.jm_hover'));

                        // Up (decrement) or down (increment)?
                        if(e.keyCode == 38) {
                            hover_index--;
                        } else {
                            hover_index++;
                        }

                        if(!hover_index) {
                            hover_index = 0;
                        }

                        // No buddy before/after?
                        if(!jQuery('#jappix_mini a.jm_online:visible').eq(hover_index).size()) {
                            if(e.keyCode == 38) {
                                hover_index = jQuery('#jappix_mini a.jm_online:visible:last').index();
                            } else {
                                hover_index = 0;
                            }
                        }

                        // Hover the previous/next buddy
                        jQuery('#jappix_mini a.jm_friend.jm_hover').removeClass('jm_hover');
                        jQuery('#jappix_mini a.jm_online:visible').eq(hover_index).addClass('jm_hover');
                    }

                    // Scroll to the hovered buddy (if out of limits)
                    jQuery('#jappix_mini div.jm_roster div.jm_buddies').scrollTo(jQuery('#jappix_mini a.jm_online.jm_hover'), 0, {margin: true});

                    return false;
                }

                // Enter key
                if((e.keyCode == 13) && jQuery('#jappix_mini a.jm_friend.jm_hover').size()) {
                    jQuery('#jappix_mini a.jm_friend.jm_hover').click();

                    return false;
                }
            });

            // Chat type re-focus
            jQuery(document).keypress(function(evt) {
                // Cannot work if an input/textarea is already focused or chat is not opened
                var path = '#jappix_mini div.jm_conversation div.jm_chat-content';

                if(jQuery('input, textarea').is(':focus') || !jQuery(path).is(':visible')) {
                    return;
                }

                // May cause some compatibility issues
                try {
                    // Get key value
                    var key_value = jQuery.trim(String.fromCharCode(evt.which));

                    // Re-focus on opened chat?
                    if(key_value) {
                        // Path to chat input
                        var path_input = path + ' input.jm_send-messages';

                        // Use a timer to override the DOM lag issue
                        jQuery(document).oneTime(10, function() {
                            // Get input values
                            select_input = jQuery(path_input);
                            value_input = select_input.val();

                            // Append pressed key value
                            select_input.val(value_input + key_value);
                            select_input.focus();

                            // Put cursor at the end of input
                            select_input[0].selectionStart = select_input[0].selectionEnd = value_input.length + 1;
                        });
                    }
                } catch(e) {}
            });

            // Hides the roster when clicking away of Jappix Mini
            jQuery(document).click(function(evt) {
                if(!jQuery(evt.target).parents('#jappix_mini').size() && !JappixCommon.exists('#jappix_popup')) {
                    self.hideRoster();
                }
            });

            // Hides all panes double clicking away of Jappix Mini
            jQuery(document).dblclick(function(evt) {
                if(!jQuery(evt.target).parents('#jappix_mini').size() && !JappixCommon.exists('#jappix_popup')) {
                    self.switchPane();
                }
            });

            // Suspended session resumed?
            if(suspended) {
                // Initialized marker
                MINI_INITIALIZED = true;

                // Not resumed? (need to reconnect)
                if(!resumed) {
                    // Restore previous reconnect counter
                    var reconnect = JappixDataStore.getDB(MINI_HASH, 'jappix-mini', 'reconnect');

                    if(!isNaN(reconnect)) {
                        MINI_RECONNECT = parseInt(reconnect);
                    }

                    // Restore queued functions
                    self.unserializeQueue();

                    // Simulate a network error to get the same silent reconnect effect
                    self.disconnected();
                }

                // Restore chat input values
                jQuery('#jappix_mini div.jm_conversation input.jm_send-messages').each(function() {
                    var this_sub_sel = jQuery(this);
                    var chat_value = this_sub_sel.attr('data-value');

                    if(chat_value) {
                        this_sub_sel.val(chat_value);
                    }
                });

                // Restore roster filter value
                var search_box = jQuery('#jappix_mini div.jm_roster div.jm_search input.jm_searchbox');
                var filter_value = search_box.attr('data-value');

                if(filter_value) {
                    search_box.val(filter_value).keyup();
                }

                // Restore buddy events
                self.eventsBuddy('#jappix_mini a.jm_friend');

                // Restore chat click events
                jQuery('#jappix_mini div.jm_conversation').each(function() {
                    var this_sub_sel = jQuery(this);
                    self.chatEvents(this_sub_sel.attr('data-type'), JappixCommon.unescapeQuotes(this_sub_sel.attr('data-xid')), this_sub_sel.attr('data-hash'));
                });

                // Restore init marker on all groupchats
                jQuery('#jappix_mini div.jm_conversation[data-type="groupchat"]').attr('data-init', 'true');

                // Scroll down to the last message
                var scroll_hash = jQuery('#jappix_mini div.jm_conversation:has(a.jm_pane.jm_clicked)').attr('data-hash');
                var scroll_position = JappixDataStore.getDB(MINI_HASH, 'jappix-mini', 'scroll');

                // Any scroll position?
                if(scroll_position) {
                    scroll_position = parseInt(scroll_position);
                }

                if(scroll_hash) {
                    // Use a timer to override the DOM lag issue
                    jQuery(document).oneTime(200, function() {
                        self.messageScroll(scroll_hash, scroll_position);
                    });
                }

                // Update notification counters
                self.notifyCounters();
            }

            // Can auto-connect?
            else if(MINI_AUTOCONNECT) {
                self.connect(domain, user, password);
            }

            // Cannot auto-connect?
            else {
                // Chat text
                jQuery('#jappix_mini a.jm_pane.jm_button span.jm_counter').text(JappixCommon._e("Chat"));

                // Must animate?
                if(MINI_ANIMATE) {
                    // Add content
                    jQuery('#jappix_mini div.jm_starter').prepend(
                        '<span class="jm_animate jm_images_animate"></span>'
                    );
                }
            }
        } catch(e) {
            JappixConsole.error('JappixMini.create', e);
        }

    };


    /**
     * Buddy events
     * @public
     * @param {string} path
     * @return {undefined}
     */
    self.eventsBuddy = function(path) {

        try {
            var selector = jQuery(path);

            // Restore buddy click events
            selector.click(function() {
                // Using a try/catch override IE issues
                try {
                    var this_sel = jQuery(this);
                    self.chat('chat', JappixCommon.unescapeQuotes(this_sel.attr('data-xid')), JappixCommon.unescapeQuotes(this_sel.attr('data-nick')), this_sel.attr('data-hash'));
                }

                catch(e) {}

                finally {
                    return false;
                }
            });

            // Restore buddy hover events
            selector.hover(function() {
                jQuery('#jappix_mini a.jm_friend.jm_hover').removeClass('jm_hover');
                jQuery(this).addClass('jm_hover');
            }, function() {
                jQuery(this).removeClass('jm_hover');
            });

            // Restore buddy mousedown events
            selector.mousedown(function() {
                jQuery('#jappix_mini a.jm_friend.jm_hover').removeClass('jm_hover');
                jQuery(this).addClass('jm_hover');
            });

            // Restore buddy focus events
            selector.focus(function() {
                jQuery('#jappix_mini a.jm_friend.jm_hover').removeClass('jm_hover');
                jQuery(this).addClass('jm_hover');
            });

            // Restore buddy blur events
            selector.blur(function() {
                jQuery(this).removeClass('jm_hover');
            });
        } catch(e) {
            JappixConsole.error('JappixMini.eventsBuddy', e);
        }

    };


    /**
     * Displays a given message
     * @public
     * @param {string} type
     * @param {string} body
     * @param {string} xid
     * @param {string} nick
     * @param {string} hash
     * @param {string} time
     * @param {string} stamp
     * @param {string} message_type
     * @return {undefined}
     */
    self.displayMessage = function(type, body, xid, nick, hash, time, stamp, message_type) {

        try {
            // Generate path
            var path = '#chat-' + hash;

            // Can scroll?
            var cont_scroll = document.getElementById('received-' + hash);
            var can_scroll = false;

            if(!cont_scroll.scrollTop || ((cont_scroll.clientHeight + cont_scroll.scrollTop) == cont_scroll.scrollHeight)) {
                can_scroll = true;
            }

            // Remove the previous message border if needed
            var last = jQuery(path + ' div.jm_group:last');
            var last_stamp = parseInt(last.attr('data-stamp'));
            var last_b = jQuery(path + ' b:last');
            var last_xid = last_b.attr('data-xid');
            var last_type = last.attr('data-type');
            var grouped = false;
            var header = '';

            if((last_xid == xid) && (message_type == last_type) && ((stamp - last_stamp) <= 1800)) {
                grouped = true;
            } else {
                // Write the message date
                if(nick)
                    header += '<span class="jm_date">' + time + '</span>';

                // Write the buddy name at the top of the message group
                if(type == 'groupchat')
                    header += '<b class="jm_name" style="color: ' + JappixCommon.generateColor(nick) + ';" data-xid="' + JappixCommon.encodeQuotes(xid) + '">' + nick.htmlEnc() + '</b>';
                else if(nick == 'me')
                    header += '<b class="jm_name jm_me" data-xid="' + JappixCommon.encodeQuotes(xid) + '">' + JappixCommon._e("You") + '</b>';
                else
                    header += '<b class="jm_name jm_him" data-xid="' + JappixCommon.encodeQuotes(xid) + '">' + nick.htmlEnc() + '</b>';
            }

            // Apply the /me command
            var me_command = false;

            if(body.match(/^\/me /i)) {
                body = body.replace(/^\/me /i, nick + ' ');

                // Marker
                me_command = true;
            }

            // HTML-encode the message
            body = body.htmlEnc();

            // Apply the smileys
            body = body.replace(/(;-?\))(\s|$)/gi, self.smiley('wink', '$1'))
                       .replace(/(:-?3)(\s|$)/gi, self.smiley('waii', '$1'))
                       .replace(/(:-?\()(\s|$)/gi, self.smiley('unhappy', '$1'))
                       .replace(/(:-?P)(\s|$)/gi, self.smiley('tongue', '$1'))
                       .replace(/(:-?O)(\s|$)/gi, self.smiley('surprised', '$1'))
                       .replace(/(:-?\))(\s|$)/gi, self.smiley('smile', '$1'))
                       .replace(/(\^_?\^)(\s|$)/gi, self.smiley('happy', '$1'))
                       .replace(/(:-?D)(\s|$)/gi, self.smiley('grin', '$1'));

            // Format the text
            body = body.replace(/(^|\s|>|\()((\*)([^<>'"\*\*]+)(\*\*))($|\s|<|\))/gi, '$1<b>$2</b>$6')
                       .replace(/(^|\s|>|\()((\/)([^<>'"\/]+)(\/))($|\s|<|\))/gi, '$1<em>$2</em>$6')
                       .replace(/(^|\s|>|\()((_)([^<>'"_]+)(_))($|\s|<|\))/gi, '$1<span style="text-decoration: underline;">$2</span>$6');

            // Filter the links
            body = JappixLinks.apply(body, 'mini');

            // Generate the message code
            if(me_command) {
                body = '<em>' + body + '</em>';
            }

            body = '<p>' + body + '</p>';

            // Create the message
            if(grouped) {
                jQuery('#jappix_mini #chat-' + hash + ' div.jm_received-messages div.jm_group:last').append(body);
            } else {
                jQuery('#jappix_mini #chat-' + hash + ' div.jm_chatstate_typing').before('<div class="jm_group jm_' + message_type + '" data-type="' + message_type + '" data-stamp="' + stamp + '">' + header + body + '</div>');
            }

            // Scroll to this message
            if(can_scroll) {
                self.messageScroll(hash);
            }
        } catch(e) {
            JappixConsole.error('JappixMini.displayMessage', e);
        }

    };


    /**
     * Switches to a given point
     * @public
     * @param {string} element
     * @param {string} hash
     * @return {undefined}
     */
    self.switchPane = function(element, hash) {

        try {
            // Hide every item
            self.hideRoster();
            jQuery('#jappix_mini a.jm_pane').removeClass('jm_clicked');
            jQuery('#jappix_mini div.jm_chat-content').hide();

            // Show the asked element
            if(element && (element != 'roster')) {
                var current = '#jappix_mini #' + element;

                // Navigate to this chat
                if(jQuery(current).size() && jQuery(current).is(':hidden')) {
                    var click_nav = '';

                    // Before or after?
                    if(jQuery('#jappix_mini div.jm_conversation:visible:first').prevAll().is('#' + element))
                        click_nav = jQuery('#jappix_mini a.jm_switch.jm_left');
                    else
                        click_nav = jQuery('#jappix_mini a.jm_switch.jm_right');

                    // Click previous or next
                    if(click_nav) {
                        while(jQuery(current).is(':hidden') && !click_nav.hasClass('jm_nonav'))
                            click_nav.click();
                    }
                }

                // Show it
                jQuery(current + ' a.jm_pane').addClass('jm_clicked');
                jQuery(current + ' div.jm_chat-content').show();

                // Use a timer to override the DOM lag issue
                jQuery(document).oneTime(10, function() {
                    jQuery(current + ' input.jm_send-messages').focus();
                });

                // Scroll to the last message & adapt chat
                if(hash) {
                    self.messageScroll(hash);
                    self.updatePixStream(hash);
                }
            }
        } catch(e) {
            JappixConsole.error('JappixMini.switchPane', e);
        }

    };


    /**
     * Scrolls to the last chat message
     * @public
     * @param {string} hash
     * @param {string} position
     * @return {undefined}
     */
    self.messageScroll = function(hash, position) {

        try {
            var id = document.getElementById('received-' + hash);

            // No defined position?
            if(!position) {
                position = id.scrollHeight;
            }

            id.scrollTop = position;
        } catch(e) {
            JappixConsole.error('JappixMini.messageScroll', e);
        }

    };


    /**
     * Prompts the user with a given text
     * @public
     * @param {string} text
     * @param {string} value
     * @return {undefined}
     */
    self.openPrompt = function(text, value) {

        try {
            // Initialize
            var prompt = '#jappix_popup div.jm_prompt';
            var input = prompt + ' form input';
            var value_input = input + '[type="text"]';

            // Remove the existing prompt
            self.closePrompt();

            // Add the prompt
            jQuery('body').append(
                '<div id="jappix_popup" dir="' + (JappixCommon.isRTL() ? 'rtl' : 'ltr') + '">' +
                    '<div class="jm_prompt">' +
                        '<form>' +
                            text +
                            '<input class="jm_text" type="text" value="" />' +
                            '<input class="jm_submit" type="submit" value="' + JappixCommon._e("Submit") + '" />' +
                            '<input class="jm_submit" type="reset" value="' + JappixCommon._e("Cancel") + '" />' +
                            '<div class="jm_clear"></div>' +
                        '</form>' +
                    '</div>' +
                '</div>'
            );

            // Vertical center
            var vert_pos = '-' + ((jQuery(prompt).height() / 2) + 10) + 'px';
            jQuery(prompt).css('margin-top', vert_pos);

            // Apply the value?
            if(value) {
                jQuery(value_input).val(value);
            }

            // Focus on the input
            jQuery(document).oneTime(10, function() {
                jQuery(value_input).focus();
            });

            // Cancel event
            jQuery(input + '[type="reset"]').click(function() {
                try {
                    self.closePrompt();
                }

                catch(e) {}

                finally {
                    return false;
                }
            });
        } catch(e) {
            JappixConsole.error('JappixMini.openPrompt', e);
        }

    };


    /**
     * Returns the prompt value
     * @public
     * @return {string}
     */
    self.closePrompt = function() {

        try {
            // Read the value
            var value = jQuery('#jappix_popup div.jm_prompt form input').val();

            // Remove the popup
            jQuery('#jappix_popup').remove();

            return value;
        } catch(e) {
            JappixConsole.error('JappixMini.closePrompt', e);
        }

    };


    /**
     * Opens the new groupchat prompt
     * @public
     * @return {undefined}
     */
    self.groupchatPrompt = function() {

        try {
            // Create a new prompt
            self.openPrompt(JappixCommon._e("Please enter the group chat address to join."));

            // When prompt submitted
            jQuery('#jappix_popup div.jm_prompt form').submit(function() {
                try {
                    // Read the value
                    var join_this = self.closePrompt();

                    // Any submitted chat to join?
                    if(join_this) {
                        // Get the chat room to join
                        chat_room = JappixCommon.bareXID(JappixCommon.generateXID(join_this, 'groupchat'));

                        // Create a new groupchat
                        self.chat('groupchat', chat_room, JappixCommon.getXIDNick(chat_room), hex_md5(chat_room));
                    }
                }

                catch(e) {}

                finally {
                    return false;
                }
            });
        } catch(e) {
            JappixConsole.error('JappixMini.groupchatPrompt', e);
        }

    };


    /**
     * Manages and creates a chat
     * @public
     * @param {string} type
     * @param {string} xid
     * @param {string} nick
     * @param {string} hash
     * @param {string} pwd
     * @param {boolean} show_pane
     * @return {boolean}
     */
    self.chat = function(type, xid, nick, hash, pwd, show_pane) {

        try {
            var current = '#jappix_mini #chat-' + hash;
            var nickname = null;

            // Not yet added?
            if(!JappixCommon.exists(current)) {
                // Groupchat nickname
                if(type == 'groupchat') {
                    // Random nickname?
                    if(!MINI_NICKNAME && MINI_RANDNICK)
                        MINI_NICKNAME = self.randomNick();

                    nickname = MINI_NICKNAME;

                    // No nickname?
                    if(!nickname) {
                        // Create a new prompt
                        self.openPrompt(JappixCommon.printf(JappixCommon._e("Please enter your nickname to join %s."), xid));

                        // When prompt submitted
                        jQuery('#jappix_popup div.jm_prompt form').submit(function() {
                            try {
                                // Read the value
                                var nickname = self.closePrompt();

                                // Update the stored one
                                if(nickname) {
                                    MINI_NICKNAME = nickname;
                                }

                                // Launch it again!
                                self.chat(type, xid, nick, hash, pwd);
                            }

                            catch(e) {}

                            finally {
                                return false;
                            }
                        });

                        return;
                    }
                }

                // Create the HTML markup
                var html = '<div class="jm_conversation jm_type_' + type + '" id="chat-' + hash + '" data-xid="' + JappixCommon.escapeQuotes(xid) + '" data-type="' + type + '" data-nick="' + JappixCommon.escapeQuotes(nick) + '" data-hash="' + hash + '" data-origin="' + JappixCommon.escapeQuotes(JappixCommon.cutResource(xid)) + '">' +
                        '<div class="jm_chat-content">' +
                            '<div class="jm_actions">' +
                                '<span class="jm_nick">' + nick + '</span>';

                // Check if the chat/groupchat exists
                var groupchat_exists = false;
                var chat_exists = false;

                if((type == 'groupchat') && MINI_GROUPCHATS && MINI_GROUPCHATS.length) {
                    for(var g in MINI_GROUPCHATS) {
                        if(xid == JappixCommon.bareXID(JappixCommon.generateXID(MINI_GROUPCHATS[g], 'groupchat'))) {
                            groupchat_exists = true;

                            break;
                        }
                    }
                }

                if((type == 'chat') && MINI_CHATS && MINI_CHATS.length) {
                    for(var c in MINI_CHATS) {
                        if(xid == JappixCommon.bareXID(JappixCommon.generateXID(MINI_CHATS[c], 'chat'))) {
                            chat_exists = true;

                            break;
                        }
                    }
                }

                // Any close button to display?
                if(((type == 'groupchat') && !groupchat_exists) || ((type == 'chat') && !chat_exists) || ((type != 'groupchat') && (type != 'chat'))) {
                    html += '<a class="jm_one-action jm_close jm_images" title="' + JappixCommon._e("Close") + '" href="#"></a>';
                }

                html +=
                    '</div>' +
                        '<div class="jm_pix_stream"></div>' +

                        '<div class="jm_received-messages" id="received-' + hash + '">' +
                            '<div class="jm_chatstate_typing">' + JappixCommon.printf(JappixCommon._e("%s is typing..."), nick.htmlEnc()) + '</div>' +
                        '</div>' +

                        '<form action="#" method="post">' +
                            '<input type="text" class="jm_send-messages" name="body" autocomplete="off" placeholder="' + JappixCommon._e("Chat") + '" data-value="" />' +
                            '<input type="hidden" name="xid" value="' + xid + '" />' +
                            '<input type="hidden" name="type" value="' + type + '" />' +
                        '</form>' +
                    '</div>' +

                    '<a class="jm_pane jm_chat-tab jm_images" href="#">' +
                        '<span class="jm_name">' + nick.htmlEnc() + '</span>' +
                    '</a>' +
                '</div>';

                jQuery('#jappix_mini div.jm_conversations').prepend(html);

                // Get the presence of this friend
                if(type != 'groupchat') {
                    var selector = jQuery('#jappix_mini a#friend-' + hash + ' span.jm_presence');

                    // Default presence
                    var show = 'available';

                    // Read the presence
                    if(selector.hasClass('jm_unavailable') || !selector.size()) {
                        show = 'unavailable';
                    } else if(selector.hasClass('jm_chat')) {
                        show = 'chat';
                    } else if(selector.hasClass('jm_away')) {
                        show = 'away';
                    } else if(selector.hasClass('jm_xa')) {
                        show = 'xa';
                    } else if(selector.hasClass('jm_dnd')) {
                        show = 'dnd';
                    }

                    // Create the presence marker
                    jQuery(current + ' a.jm_chat-tab').prepend('<span class="jm_presence jm_images jm_' + show + '"></span>');
                }

                // The chat events
                self.chatEvents(type, xid, hash);

                // Join the groupchat
                if(type == 'groupchat') {
                    // Add nickname & init values
                    jQuery(current).attr('data-nick', JappixCommon.escapeQuotes(nickname))
                                   .attr('data-init', 'false');

                    // Send the first groupchat presence
                    self.presence('', '', '', '', xid + '/' + nickname, pwd, true, self.handleMUC);
                }
            }

            // Focus on our pane
            if(show_pane !== false) {
                jQuery(document).oneTime(10, function() {
                    self.switchPane('chat-' + hash, hash);
                });
            }

            // Update chat overflow
            self.updateOverflow();
        } catch(e) {
            JappixConsole.error('JappixMini.chat', e);
        } finally {
            return false;
        }

    };


    /**
     * Events on the chat tool
     * @public
     * @param {string} type
     * @param {string} xid
     * @param {string} hash
     * @return {undefined}
     */
    self.chatEvents = function(type, xid, hash) {

        try {
            var current_sel = jQuery('#jappix_mini #chat-' + hash);

            // Submit the form
            current_sel.find('form').submit(function() {
                return self.sendMessage(this);
            });

            // Click on the tab
            current_sel.find('a.jm_chat-tab').click(function() {
                // Using a try/catch override IE issues
                try {
                    // Not yet opened: open it!
                    if(!jQuery(this).hasClass('jm_clicked')) {
                        // Show it!
                        self.switchPane('chat-' + hash, hash);

                        // Clear the eventual notifications
                        self.clearNotifications(hash);
                    } else {
                        self.switchPane();
                    }
                }

                catch(e) {}

                finally {
                    return false;
                }
            });

            // Click on the close button
            current_sel.find('div.jm_actions').click(function(evt) {
                // Using a try/catch override IE issues
                try {
                    // Close button?
                    if(jQuery(evt.target).is('a.jm_close')) {
                        // Gone chatstate
                        if(type != 'groupchat') {
                            self.sendChatstate('gone', xid, hash);
                        }

                        current_sel.stopTime().remove();

                        // Quit the groupchat?
                        if(type == 'groupchat') {
                            // Send an unavailable presence
                            self.presence('unavailable', '', '', '', xid + '/' + JappixCommon.unescapeQuotes(current_sel.attr('data-nick')));

                            // Remove this groupchat!
                            self.removeGroupchat(xid);
                        }

                        // Update chat overflow
                        self.updateOverflow();
                    } else {
                        // Minimize current chat
                        current_sel.find('a.jm_chat-tab.jm_clicked').click();
                    }
                }

                catch(e) {}

                finally {
                    return false;
                }
            });

            // Focus on the chat input
            current_sel.find('input.jm_send-messages').focus(function() {
                self.clearNotifications(hash);
            })

            // Change on the chat input
            .keyup(function() {
                var this_sel = jQuery(this);
                this_sel.attr('data-value', this_sel.val());
            })

            // Chat tabulate or escape press
            .keydown(function(e) {
                // Tabulate?
                if(e.keyCode == 9) {
                    self.switchChat();

                    return false;
                }

                // Escape?
                if(e.keyCode == 27) {
                    if(current_sel.find('a.jm_close').size()) {
                        // Open next/previous chat
                        if(current_sel.next('div.jm_conversation').size()) {
                            current_sel.next('div.jm_conversation').find('a.jm_pane').click();
                        } else if(current_sel.prev('div.jm_conversation').size()) {
                            current_sel.prev('div.jm_conversation').find('a.jm_pane').click();
                        }

                        // Close current chat
                        current_sel.find('a.jm_close').click();
                    }

                    return false;
                }
            });

            // Focus/Blur events
            jQuery('#jappix_mini #chat-' + hash + ' input.jm_send-messages').focus(function() {
                // Store active chat
                MINI_ACTIVE = hash;
            })

            .blur(function() {
                // Reset active chat
                if(MINI_ACTIVE == hash) {
                    MINI_ACTIVE = null;
                }
            });

            // Chatstate events
            self.eventsChatstate(xid, hash, type);
        } catch(e) {
            JappixConsole.error('JappixMini.chatEvents', e);
        }

    };


    /**
     * Opens the next chat
     * @public
     * @return {undefined}
     */
    self.switchChat = function() {

        try {
            if(jQuery('#jappix_mini div.jm_conversation').size() <= 1) {
                return;
            }

            // Get the current chat index
            var chat_index = jQuery('#jappix_mini div.jm_conversation:has(a.jm_pane.jm_clicked)').index();
            chat_index++;

            if(!chat_index) {
                chat_index = 0;
            }

            // No chat after?
            if(!jQuery('#jappix_mini div.jm_conversation').eq(chat_index).size()) {
                chat_index = 0;
            }

            // Avoid disabled chats
            while(jQuery('#jappix_mini div.jm_conversation').eq(chat_index).hasClass('jm_disabled')) {
                chat_index++;
            }

            // Show the next chat
            var chat_hash = jQuery('#jappix_mini div.jm_conversation').eq(chat_index).attr('data-hash');

            if(chat_hash) {
                self.switchPane('chat-' + chat_hash, chat_hash);
            }
        } catch(e) {
            JappixConsole.error('JappixMini.switchChat', e);
        }

    };


    /**
     * Shows the roster
     * @public
     * @return {undefined}
     */
    self.showRoster = function() {

        try {
            self.switchPane('roster');
            jQuery('#jappix_mini div.jm_roster').show();
            jQuery('#jappix_mini a.jm_button').addClass('jm_clicked');

            // Update groups visibility
            self.updateGroups();

            jQuery(document).oneTime(10, function() {
                jQuery('#jappix_mini div.jm_roster div.jm_search input.jm_searchbox').focus();
            });
        } catch(e) {
            JappixConsole.error('JappixMini.showRoster', e);
        }

    };


    /**
     * Hides the roster
     * @public
     * @return {undefined}
     */
    self.hideRoster = function() {

        try {
            // Close the roster pickers
            jQuery('#jappix_mini a.jm_status.active, #jappix_mini a.jm_join.active').click();

            // Hide the roster box
            jQuery('#jappix_mini div.jm_roster').hide();
            jQuery('#jappix_mini a.jm_button').removeClass('jm_clicked');

            // Clear the search box and show all online contacts
            jQuery('#jappix_mini div.jm_roster div.jm_search input.jm_searchbox').val('').attr('data-value', '');
            jQuery('#jappix_mini div.jm_roster div.jm_grouped').show();
            jQuery('#jappix_mini div.jm_roster div.jm_buddies a.jm_online').show();
            jQuery('#jappix_mini a.jm_friend.jm_hover').removeClass('jm_hover');
        } catch(e) {
            JappixConsole.error('JappixMini.hideRoster', e);
        }

    };


    /**
     * Removes a groupchat from DOM
     * @public
     * @param {string} xid
     * @return {undefined}
     */
    self.removeGroupchat = function(xid) {

        try {
            // Remove the groupchat private chats & the groupchat buddies from the roster
            jQuery('#jappix_mini div.jm_conversation[data-origin="' + JappixCommon.escapeQuotes(JappixCommon.cutResource(xid)) + '"], #jappix_mini div.jm_roster div.jm_grouped[data-xid="' + JappixCommon.escapeQuotes(xid) + '"]').remove();

            // Update the presence counter
            self.updateRoster();
        } catch(e) {
            JappixConsole.error('JappixMini.removeGroupchat', e);
        }

    };


    /**
     * Initializes Jappix Mini
     * @public
     * @return {undefined}
     */
    self.initialize = function() {

        try {
            // Update the marker
            MINI_INITIALIZED = true;

            // Send the initial presence
            self.presence();

            // Join the groupchats (first)
            for(var i = 0; i < MINI_GROUPCHATS.length; i++) {
                // Empty value?
                if(!MINI_GROUPCHATS[i]) {
                    continue;
                }

                // Using a try/catch override IE issues
                try {
                    // Current chat room
                    var chat_room = JappixCommon.bareXID(JappixCommon.generateXID(MINI_GROUPCHATS[i], 'groupchat'));

                    // Open the current chat
                    self.chat('groupchat', chat_room, JappixCommon.getXIDNick(chat_room), hex_md5(chat_room), MINI_PASSWORDS[i], MINI_SHOWPANE);
                }

                catch(e) {}
            }

            // Join the chats (then)
            for(var j = 0; j < MINI_CHATS.length; j++) {
                // Empty value?
                if(!MINI_CHATS[j]) {
                    continue;
                }

                // Using a try/catch override IE issues
                try {
                    // Current chat user
                    var chat_xid = JappixCommon.bareXID(JappixCommon.generateXID(MINI_CHATS[j], 'chat'));
                    var chat_hash = hex_md5(chat_xid);
                    var chat_nick = jQuery('#jappix_mini a#friend-' + chat_hash).attr('data-nick');

                    if(!chat_nick) {
                        chat_nick = JappixCommon.getXIDNick(chat_xid);
                    } else {
                        chat_nick = JappixCommon.unescapeQuotes(chat_nick);
                    }

                    // Open the current chat
                    self.chat('chat', chat_xid, chat_nick, chat_hash);
                }

                catch(e) {}
            }

            // Must show the roster?
            if(!MINI_AUTOCONNECT && !MINI_GROUPCHATS.length && !MINI_CHATS.length) {
                jQuery(document).oneTime(10, function() {
                    self.showRoster();
                });
            }
        } catch(e) {
            JappixConsole.error('JappixMini.initialize', e);
        }

    };


    /**
     * Displays a list of roster buddy
     * @public
     * @param {object} buddy_arr
     * @return {boolean}
     */
    self.addListBuddy = function(buddy_arr) {

        try {
            var c, b,
            nick, hash, xid, subscription;

            var buddy_str = '';

            // Loop on groups
            for(c in buddy_arr) {
                buddy_arr[c] = buddy_arr[c].sort();

                // Group: start
                if(c != MINI_ROSTER_NOGROUP) {
                    buddy_str += '<div class="jm_grouped jm_grouped_roster" data-name="' + JappixCommon.escapeQuotes(c) + '">';
                    buddy_str += '<div class="jm_name">' + c.htmlEnc() + '</div>';
                }

                // Loop on buddies
                for(b in buddy_arr[c]) {
                    // Current buddy data
                    buddy_str += self.codeAddBuddy(
                        buddy_arr[c][b][0],
                        buddy_arr[c][b][1],
                        buddy_arr[c][b][2],
                        buddy_arr[c][b][3],
                        false
                    );
                }

                // Group: end
                if(c != MINI_ROSTER_NOGROUP) {
                    buddy_str += '</div>';
                }
            }

            // Append code
            jQuery('#jappix_mini div.jm_roster div.jm_buddies').html(buddy_str);

            // Events on these buddies
            self.eventsBuddy('#jappix_mini a.jm_friend');

            return true;
        } catch(e) {
            JappixConsole.error('JappixMini.addListBuddy', e);

            return false;
        }

    };


    /**
     * Displays a roster buddy
     * @public
     * @param {string} xid
     * @param {string} hash
     * @param {string} nick
     * @param {string} groupchat
     * @param {string} subscription
     * @param {string} group
     * @return {boolean}
     */
    self.addBuddy = function(xid, hash, nick, groupchat, subscription, group) {

        try {
            var bare_xid = JappixCommon.bareXID(xid);

            // Element
            var element = '#jappix_mini a#friend-' + hash;

            // Yet added?
            if(JappixCommon.exists(element)) {
                jQuery(element).remove();
            }

            // Generate the path
            var path = '#jappix_mini div.jm_roster div.jm_buddies';

            // Generate the groupchat group path
            if(groupchat) {
                path = '#jappix_mini div.jm_roster div.jm_grouped_groupchat[data-xid="' + JappixCommon.escapeQuotes(bare_xid) + '"]';

                // Must add a groupchat group?
                if(!JappixCommon.exists(path)) {
                    jQuery('#jappix_mini div.jm_roster div.jm_buddies').append(
                        '<div class="jm_grouped jm_grouped_groupchat" data-xid="' + JappixCommon.escapeQuotes(bare_xid) + '">' +
                            '<div class="jm_name">' + JappixCommon.getXIDNick(groupchat).htmlEnc() + '</div>' +
                        '</div>'
                    );
                }
            } else if(group) {
                path = '#jappix_mini div.jm_roster div.jm_grouped_roster[data-name="' + JappixCommon.escapeQuotes(group) + '"]';

                // Must add a roster group?
                if(!JappixCommon.exists(path)) {
                    jQuery('#jappix_mini div.jm_roster div.jm_buddies').append(
                        '<div class="jm_grouped jm_grouped_roster" data-name="' + JappixCommon.escapeQuotes(group) + '">' +
                            '<div class="jm_name">' + group.htmlEnc() + '</div>' +
                        '</div>'
                    );
                }
            }

            // Append this buddy content
            var code = self.codeAddBuddy(
                nick,
                hash,
                xid,
                subscription
            );

            if(groupchat || group) {
                jQuery(path).append(code);
            } else {
                jQuery(path).prepend(code);
            }

            // Need to hide this buddy?
            if(jQuery('#jappix_mini div.jm_actions a.jm_toggle_view.jm_toggled').size()) {
                jQuery(element).filter('.jm_offline').hide();
            }

            // Events on this buddy
            self.eventsBuddy(element);

            return true;
        } catch(e) {
            JappixConsole.error('JappixMini.addBuddy', e);

            return false;
        }

    };


    /**
     * Returns the code for a single buddy to add
     * @public
     * @param {string} nick
     * @param {string} hash
     * @param {string} xid
     * @param {string} subscription
     * @return {string}
     */
    self.codeAddBuddy = function(nick, hash, xid, subscription) {

        var buddy_str = '';

        try {
            // Buddy: start
            buddy_str += '<a class="jm_friend jm_offline jm_friend-' + hash;
              buddy_str += '" id="friend-' + hash;
              buddy_str += '" title="' + JappixCommon.encodeQuotes(xid) + '"';
              buddy_str += '" data-xid="' + JappixCommon.escapeQuotes(xid) + '"';
              buddy_str += '" data-nick="' + JappixCommon.escapeQuotes(nick) + '"';
              buddy_str += '" data-hash="' + hash + '"';
              buddy_str += ' ' + (subscription ? ' data-sub="' + subscription + '" ' : '');
            buddy_str += '>';

            // Buddy: inner
            buddy_str += '<span class="jm_presence jm_images jm_unavailable"></span>';
            buddy_str += nick.htmlEnc();
            buddy_str += '<span class="jm_jingle_icon jm_images"></span>';

            // Buddy: end
            buddy_str += '</a>';
        } catch(e) {
            JappixConsole.error('JappixMini.codeAddBuddy', e);
        } finally {
            return buddy_str;
        }

    };


    /**
     * Removes a roster buddy
     * @public
     * @param {string} hash
     * @param {string} groupchat
     * @return {undefined}
     */
    self.removeBuddy = function(hash, groupchat) {

        try {
            // Remove the buddy from the roster
            jQuery('#jappix_mini a#friend-' + hash).remove();

            // Empty group?
            var group = '#jappix_mini div.jm_roster div.jm_grouped_groupchat[data-xid="' + JappixCommon.escapeQuotes(groupchat) + '"]';

            if(groupchat && !jQuery(group + ' a.jm_friend').size()) {
                jQuery(group).remove();
            }

            return true;
        } catch(e) {
            JappixConsole.error('JappixMini.removeBuddy', e);

            return false;
        }

    };


    /**
     * Gets the user's roster
     * @public
     * @return {undefined}
     */
    self.getRoster = function() {

        try {
            var iq = new JSJaCIQ();
            iq.setType('get');
            iq.setQuery(NS_ROSTER);
            con.send(iq, self.handleRoster);

            JappixConsole.info('Getting roster...');
        } catch(e) {
            JappixConsole.error('JappixMini.getRoster', e);
        }

    };


    /**
     * Handles the user's roster
     * @public
     * @param {object} iq
     * @return {undefined}
     */
    self.handleRoster = function(iq) {

        try {
            var buddies, pointer,
            cur_buddy, cur_groups, cur_group,
            current, xid, subscription,
            nick, hash, j, c;

            // Added to sort buddies by name
            buddies = {};
            pointer = {};

            // Parse the roster
            jQuery(iq.getQuery()).find('item').each(function() {
                var this_sub_sel = jQuery(this);

                // Get the values
                current = this_sub_sel;
                xid = current.attr('jid');
                subscription = current.attr('subscription');

                // Not a gateway
                if(!JappixCommon.isGateway(xid)) {
                    // Read current values
                    nick = current.attr('name');
                    hash = hex_md5(xid);

                    // No name defined?
                    if(!nick)  nick = JappixCommon.getXIDNick(xid);

                    // Populate buddy array
                    cur_buddy = [];

                    cur_buddy[0] = nick;
                    cur_buddy[1] = hash;
                    cur_buddy[2] = xid;
                    cur_buddy[3] = subscription;

                    // Append to groups this buddy belongs to
                    cur_groups = {};

                    if(this_sub_sel.find('group').size()) {
                        this_sub_sel.find('group').each(function() {
                            cur_group = jQuery(this).text();

                            if(cur_group) {
                                cur_groups[cur_group] = 1;
                            }
                        });
                    } else {
                        cur_groups[MINI_ROSTER_NOGROUP] = 1;
                    }

                    for(var cur_group in cur_groups) {
                        // Prepare multidimentional array
                        if(typeof pointer[cur_group] != 'number') {
                            pointer[cur_group] = 0;
                        }

                        if(typeof buddies[cur_group] != 'object') {
                            buddies[cur_group] = [];
                        }

                        // Push buddy data
                        buddies[cur_group][(pointer[cur_group])++] = cur_buddy;
                    }
                }
            });

            // No buddies? (ATM)
            if(!MINI_ROSTER_INIT) {
                MINI_ROSTER_INIT = true;

                self.addListBuddy(buddies);
            } else {
                for(c in buddies) {
                    for(j = 0; j < buddies[c].length; j++) {
                        if(!buddies[c][j]) {
                            continue;
                        }

                        // Current buddy information
                        nick = buddies[c][j][0];
                        hash = buddies[c][j][1];
                        xid = buddies[c][j][2];
                        subscription = buddies[c][j][3];

                        // Apply current buddy action
                        if(subscription == 'remove') {
                            self.removeBuddy(hash);
                        } else {
                            self.addBuddy(xid, hash, nick, null, subscription, (c != MINI_ROSTER_NOGROUP ? c : null));
                        }
                    }
                }
            }

            // Not yet initialized
            if(!MINI_INITIALIZED) {
                self.initialize();
            }

            JappixConsole.info('Roster got.');
        } catch(e) {
            JappixConsole.error('JappixMini.handleRoster', e);
        }

    };


    /**
     * Adapts the roster height to the window
     * @public
     * @return {undefined}
     */
    self.adaptRoster = function() {

        try {
            // Adapt buddy list height
            var roster_height = jQuery(window).height() - 85;
            jQuery('#jappix_mini div.jm_roster div.jm_buddies').css('max-height', roster_height);

            // Adapt chan suggest height
            var suggest_height = jQuery('#jappix_mini div.jm_roster').height() - 46;
            jQuery('#jappix_mini div.jm_chan_suggest').css('max-height', suggest_height);
        } catch(e) {
            JappixConsole.error('JappixMini.adaptRoster', e);
        }

    };


    /**
     * Generates a random nickname
     * @public
     * @return {string}
     */
    self.randomNick = function() {

        try {
            // First nickname block
            var first_arr = [
                'Just',
                'Bob',
                'Jar',
                'Pedr',
                'Yod',
                'Maz',
                'Vez',
                'Car',
                'Erw',
                'Tiet',
                'Iot',
                'Wal',
                'Bez',
                'Pop',
                'Klop',
                'Zaz',
                'Yoy',
                'Raz'
            ];

            // Second nickname block
            var second_arr = [
                'io',
                'ice',
                'a',
                'u',
                'o',
                'ou',
                'oi',
                'ana',
                'oro',
                'izi',
                'ozo',
                'aza',
                'ato',
                'ito',
                'ofa',
                'oki',
                'ima',
                'omi'
            ];

            // Last nickname block
            var last_arr = [
                't',
                'z',
                'r',
                'n',
                'tt',
                'zz',
                'pp',
                'j',
                'k',
                'v',
                'c',
                'x',
                'ti',
                'to',
                'ta',
                'ra',
                'ro',
                'ri'
            ];

            // Select random values from the arrays
            var rand_nick = JappixCommon.randomArrayValue(first_arr)  +
                            JappixCommon.randomArrayValue(second_arr) +
                            JappixCommon.randomArrayValue(last_arr);

            return rand_nick;
        } catch(e) {
            JappixConsole.error('JappixMini.randomNick', e);
        }

    };


    /**
     * Sends a given chatstate to a given entity
     * @public
     * @param {string} state
     * @param {string} xid
     * @param {string} hash
     * @return {undefined}
     */
    self.sendChatstate = function(state, xid, hash) {

        try {
            var user_type = jQuery('#jappix_mini #chat-' + hash).attr('data-type');
            var user_storage = jQuery('#jappix_mini #chat-' + hash + ' input.jm_send-messages');

            // If the friend client supports chatstates and is online
            if((user_type == 'groupchat') || ((user_type == 'chat') && user_storage.attr('data-chatstates') && !JappixCommon.exists('#jappix_mini a#friend-' + hash + '.jm_offline'))) {
                // Already sent?
                if(user_storage.attr('data-chatstate') == state) {
                    return;
                }

                // Store the state
                user_storage.attr('data-chatstate', state);

                // Send the state
                var aMsg = new JSJaCMessage();
                aMsg.setTo(xid);
                aMsg.setType(user_type);

                aMsg.appendNode(state, {'xmlns': NS_CHATSTATES});

                con.send(aMsg);

                JappixConsole.log('Sent ' + state + ' chatstate to ' + xid);
            }
        } catch(e) {
            JappixConsole.error('JappixMini.sendChatstate', e);
        }

    };


    /**
     * Displays a given chatstate in a given chat
     * @public
     * @param {string} state
     * @param {string} xid
     * @param {string} hash
     * @param {string} type
     * @return {undefined}
     */
    self.displayChatstate = function(state, xid, hash, type) {

        try {
            // Groupchat not supported
            if(type == 'groupchat') {
                return;
            }

            // Composing?
            if(state == 'composing') {
                jQuery('#jappix_mini #chat-' + hash + ' div.jm_chatstate_typing').css('visibility', 'visible');
            } else {
                self.resetChatstate(xid, hash, type);
            }

            JappixConsole.log('Received ' + state + ' chatstate from ' + xid);
        } catch(e) {
            JappixConsole.error('JappixMini.displayChatstate', e);
        }

    };


    /**
     * Resets the chatstate switcher marker
     * @public
     * @param {string} xid
     * @param {string} hash
     * @param {string} type
     * @return {undefined}
     */
    self.resetChatstate = function(xid, hash, type) {

        try {
            // Groupchat not supported
            if(type == 'groupchat') {
                return;
            }

            jQuery('#jappix_mini #chat-' + hash + ' div.jm_chatstate_typing').css('visibility', 'hidden');
        } catch(e) {
            JappixConsole.error('JappixMini.resetChatstate', e);
        }

    };


    /**
     * Adds the chatstate events
     * @public
     * @param {string} xid
     * @param {string} hash
     * @param {string} type
     * @return {undefined}
     */
    self.eventsChatstate = function(xid, hash, type) {

        try {
            // Groupchat not supported
            if(type == 'groupchat') {
                return;
            }

            jQuery('#jappix_mini #chat-' + hash + ' input.jm_send-messages').keyup(function(e) {
                var this_sel = jQuery(this);

                if(e.keyCode != 13) {
                    // Composing a message
                    if(this_sel.val() && (this_sel.attr('data-composing') != 'on')) {
                        // We change the state detect input
                        this_sel.attr('data-composing', 'on');

                        // We send the friend a "composing" chatstate
                        self.sendChatstate('composing', xid, hash);
                    }

                    // Stopped composing a message
                    else if(!this_sel.val() && (this_sel.attr('data-composing') == 'on')) {
                        // We change the state detect input
                        this_sel.attr('data-composing', 'off');

                        // We send the friend an "active" chatstate
                        self.sendChatstate('active', xid, hash);
                    }
                }
            })

            .change(function() {
                // Reset the composing database entry
                jQuery(this).attr('data-composing', 'off');
            })

            .focus(function() {
                var this_sel = jQuery(this);

                // Not needed
                if(this_sel.is(':disabled')) {
                    return;
                }

                // Nothing in the input, user is active
                if(!this_sel.val()) {
                    self.sendChatstate('active', xid, hash);
                } else {
                    self.sendChatstate('composing', xid, hash);
                }
            })

            .blur(function() {
                var this_sel = jQuery(this);

                // Not needed
                if(this_sel.is(':disabled')) {
                    return;
                }

                // Nothing in the input, user is inactive
                if(!this_sel.val()) {
                    self.sendChatstate('inactive', xid, hash);
                } else {
                    self.sendChatstate('paused', xid, hash);
                }
            });
        } catch(e) {
            JappixConsole.error('JappixMini.eventsChatstate', e);
        }

    };


    /**
     * Plays a sound
     * @public
     * @return {boolean}
     */
    self.soundPlay = function() {
        return false; // UA: Disable
        try {
            // Not supported!
            if((BrowserDetect.browser == 'Explorer') && (BrowserDetect.version < 9)) {
                return false;
            }

            // Append the sound container
            if(!JappixCommon.exists('#jappix_mini #jm_audio')) {
                jQuery('#jappix_mini').append(
                    '<div id="jm_audio">' +
                        '<audio preload="auto">' +
                            '<source src="' + JAPPIX_STATIC + 'sounds/receive-message.mp3" />' +
                            '<source src="' + JAPPIX_STATIC + 'sounds/receive-message.oga" />' +
                        '</audio>' +
                    '</div>'
                );
            }

            // Play the sound
            var audio_select = document.getElementById('jm_audio').getElementsByTagName('audio')[0];

            // Avoids Safari bug (2011 and less versions)
            try {
                audio_select.load();
            } finally {
                audio_select.play();
            }
        } catch(e) {
            JappixConsole.error('JappixMini.soundPlay', e);
        } finally {
            return false;
        }

    };


    /**
     * Adapts chat size
     * @public
     * @param {string} conversation_path
     * @return {undefined}
     */
    self.adaptChat = function(conversation_path) {

        try {
            var conversation_sel = jQuery('#jappix_mini div.jm_conversation');

            if(conversation_path) {
                conversation_sel = conversation_sel.filter(conversation_path);
            }

            if(conversation_sel.size()) {
                // Reset before doing anything else...
                conversation_sel.find('div.jm_received-messages').css({
                    'max-height': 'none',
                    'margin-top': 0
                });

                // Update sizes of chat
                var pix_stream_sel = conversation_sel.find('div.jm_pix_stream');
                var received_messages_sel = conversation_sel.find('div.jm_received-messages');
                var pix_stream_height = pix_stream_sel.height();

                if(pix_stream_sel.find('*').size() && pix_stream_height > 0) {
                    received_messages_sel.css({
                        'margin-top': pix_stream_height,
                        'max-height': (received_messages_sel.height() - pix_stream_sel.height())
                    });
                }
            }
        } catch(e) {
            JappixConsole.error('JappixMini.adaptChat', e);
        }

    };


    /**
     * Updates given pixel stream
     * @public
     * @param {string} hash
     * @return {undefined}
     */
    self.updatePixStream = function(hash) {

        try {
            // Feature supported? (we rely on local storage)
            if(window.localStorage !== undefined) {
                // Select chat(s)
                var conversation_path = '#chat-' + hash;
                var conversation_sel = jQuery('#jappix_mini div.jm_conversation');
                var conversation_all_sel = conversation_sel;

                if(hash) {
                    conversation_sel = conversation_sel.filter(conversation_path);
                } else {
                    conversation_sel = conversation_sel.filter(':has(div.jm_chat-content:visible):first');

                    if(conversation_sel.size()) {
                        conversation_path = '#' + conversation_sel.attr('id');
                    } else {
                        conversation_path = null;
                    }
                }

                // Parse stored dates
                var stamp_now = JappixDateUtils.getTimeStamp();
                var stamp_start = JappixDataStore.getPersistent(MINI_HASH, 'pixel-stream', 'start');
                var stamp_end = JappixDataStore.getPersistent(MINI_HASH, 'pixel-stream', 'end');

                var in_schedule = false;
                var to_reschedule = true;

                if(stamp_start && stamp_end && !isNaN(stamp_start) && !isNaN(stamp_end)) {
                    stamp_start = parseInt(stamp_start, 10);
                    stamp_end = parseInt(stamp_end, 10);

                    in_schedule = (stamp_now >= stamp_start && stamp_end >= stamp_now);
                    to_reschedule = (stamp_now >= stamp_end + MINI_PIXEL_STREAM_INTERVAL);
                }

                // Should add ads?
                if(in_schedule || to_reschedule) {
                    // Store new schedules
                    if(to_reschedule) {
                        JappixDataStore.setPersistent(MINI_HASH, 'pixel-stream', 'start', stamp_now);
                        JappixDataStore.setPersistent(MINI_HASH, 'pixel-stream', 'end', stamp_now + MINI_PIXEL_STREAM_DURATION);
                    }

                    // Process HTML code
                    if(conversation_path && ADS_ENABLE === 'on' && GADS_CLIENT && GADS_SLOT) {
                        var pix_stream_sel = conversation_sel.find('div.jm_pix_stream');

                        if(!pix_stream_sel.find('*').size()) {
                            JappixConsole.info('JappixMini.updatePixStream', 'Loading pixel stream...');

                            var pix_stream_other_added = conversation_all_sel.find('div.jm_pix_stream ins.adsbygoogle:first').clone();

                            if(pix_stream_other_added.size()) {
                                JappixConsole.log('JappixMini.updatePixStream', 'Copy existing pixel stream from DOM');

                                pix_stream_sel.html(pix_stream_other_added);
                            } else {
                                JappixConsole.log('JappixMini.updatePixStream', 'Fetch fresh pixel stream from server');

                                pix_stream_sel.html(
                                    '<ins class="adsbygoogle"' +
                                         'style="display:block;width:320px;height:50px;"' +
                                         'data-ad-client="' + JappixCommon.encodeQuotes(GADS_CLIENT) + '"' +
                                         'data-ad-slot="' + JappixCommon.encodeQuotes(GADS_SLOT) + '"></ins>' +
                                    '<script>(adsbygoogle = window.adsbygoogle || []).push({});</script>'
                                );
                            }

                            jQuery.getScript('//pagead2.googlesyndication.com/pagead/js/adsbygoogle.js', function() {
                                self.adaptChat(conversation_path);

                                JappixConsole.info('JappixMini.updatePixStream', 'Finished loading pixel stream');
                            });
                        } else {
                            JappixConsole.info('JappixMini.updatePixStream', 'Pixel stream already loaded');
                        }
                    } else {
                        self.resetPixStream();
                    }
                } else {
                    self.resetPixStream();
                }

                // Update chat height
                if(conversation_path) {
                    self.adaptChat(conversation_path);
                }
            }
        } catch(e) {
            JappixConsole.error('JappixMini.updatePixStream', e);
        }

    };


    /**
     * Resets all pixel streams
     * @public
     * @return {undefined}
     */
    self.resetPixStream = function() {

        try {
            jQuery('#jappix_mini div.jm_pix_stream').empty();
        } catch(e) {
            JappixConsole.error('JappixMini.resetPixStream', e);
        }

    };


    /**
     * Returns whether browser is legacy/unsupported or not (IE 7 and less)
     * @public
     * @return {undefined}
     */
    self.isLegacy = function() {

        try {
            return BrowserDetect.browser == 'Explorer' && BrowserDetect.version <= 7;
        } catch(e) {
            JappixConsole.error('JappixMini.isLegacy', e);
        }

    };


    /**
     * Loads the Jappix Mini stylesheet
     * @public
     * @return {boolean}
     */
    self.loadStylesheet = function() {

        try {
            var css_url = [];
            var css_html = '';

            // Do we know the optimized Get API path?
            if(JAPPIX_MINI_CSS) {
                css_url.push(JAPPIX_MINI_CSS);
            } else {
                // Fallback to non-optimized way, used with standalone Jappix Mini
                css_url.push(JAPPIX_STATIC + 'css/jappix-mini.css');
            }

            // Append final stylesheet HTML
            for(var u in css_url) {
                css_html += '<link rel="stylesheet" href="' + JappixCommon.encodeQuotes(css_url[u].replace(/&amp;/g, '&')) + '" type="text/css" media="all" />';
            }

            jQuery('head').append(css_html);

            return true;
        } catch(e) {
            JappixConsole.error('JappixMini.loadStylesheet', e);

            return false;
        }

    };


    /**
     * Plugin configurator
     * @public
     * @param {object} config_args
     * @return {undefined}
     */
    self.configure = function(config_args) {

        try {
            if(typeof config_args !== 'object') {
                config_args = {};
            }

            // Read configuration subs
            connection_config = config_args.connection   || {};
            application_config = config_args.application || {};

            application_network_config = application_config.network     || {};
            application_interface_config = application_config.interface || {};
            application_user_config = application_config.user           || {};
            application_chat_config = application_config.chat           || {};
            application_groupchat_config = application_config.groupchat || {};

            // Apply new configuration (falling back to defaults if not set)
            MINI_AUTOCONNECT         = application_network_config.autoconnect         || MINI_AUTOCONNECT;
            MINI_SHOWPANE            = application_interface_config.showpane          || MINI_SHOWPANE;
            MINI_ANIMATE             = application_interface_config.animate           || MINI_ANIMATE;
            MINI_RANDNICK            = application_user_config.random_nickname        || MINI_RANDNICK;
            MINI_GROUPCHAT_PRESENCE  = application_groupchat_config.show_presence     || MINI_GROUPCHAT_PRESENCE;
            MINI_DISABLE_MOBILE      = application_interface_config.no_mobile         || MINI_DISABLE_MOBILE;
            MINI_NICKNAME            = application_user_config.nickname               || MINI_NICKNAME;
            MINI_DOMAIN              = connection_config.domain                       || MINI_DOMAIN;
            MINI_USER                = connection_config.user                         || MINI_USER;
            MINI_PASSWORD            = connection_config.password                     || MINI_PASSWORD;
            MINI_RECONNECT_MAX       = application_network_config.reconnect_max       || MINI_RECONNECT_MAX;
            MINI_RECONNECT_INTERVAL  = application_network_config.reconnect_interval  || MINI_RECONNECT_INTERVAL;
            MINI_CHATS               = application_chat_config.open                   || MINI_CHATS;
            MINI_GROUPCHATS          = application_groupchat_config.open              || MINI_GROUPCHATS;
            MINI_SUGGEST_CHATS       = application_chat_config.suggest                || MINI_CHATS;
            MINI_SUGGEST_GROUPCHATS  = application_groupchat_config.suggest           || MINI_SUGGEST_GROUPCHATS;
            MINI_SUGGEST_PASSWORDS   = application_groupchat_config.suggest_passwords || MINI_SUGGEST_PASSWORDS;
            MINI_PASSWORDS           = application_groupchat_config.open_passwords    || MINI_PASSWORDS;
            MINI_PRIORITY            = connection_config.priority                     || MINI_PRIORITY;
            MINI_RESOURCE            = connection_config.resource                     || MINI_RESOURCE;
            MINI_ERROR_LINK          = application_interface_config.error_link        || MINI_ERROR_LINK;
        } catch(e) {
            JappixConsole.error('JappixMini.configure', e);
        }

    };


    /**
     * Plugin processor
     * @public
     * @param {boolean} autoconnect
     * @param {boolean} show_pane
     * @param {string} domain
     * @param {string} user
     * @param {string} password
     * @param {number} priority
     * @return {undefined}
     */
    self.process = function(autoconnect, show_pane, domain, user, password, priority) {

        try {
            // Disabled on mobile?
            if(MINI_DISABLE_MOBILE && JappixCommon.isMobile()) {
                JappixConsole.log('Jappix Mini disabled on mobile.'); return;
            }

            // Legacy browser? (unsupported)
            if(self.isLegacy()) {
                JappixConsole.warn('Jappix Mini cannot load on this browser (unsupported because too old)'); return;
            }

            // Save infos to reconnect
            MINI_DOMAIN = domain;
            MINI_USER = user;
            MINI_PASSWORD = password;
            MINI_HASH = 'jm.' + hex_md5(MINI_USER + '@' + MINI_DOMAIN);

            if(priority !== undefined) {
                MINI_PRIORITY = priority;
            }

            // Anonymous mode?
            if(!user || !password) {
                MINI_ANONYMOUS = true;
            } else {
                MINI_ANONYMOUS = false;
            }

            // Autoconnect (only if storage available to avoid floods)?
            if(autoconnect && JappixDataStore.hasDB()) {
                MINI_AUTOCONNECT = true;
            } else {
                MINI_AUTOCONNECT = false;
            }

            // Show pane?
            if(show_pane) {
                MINI_SHOWPANE = true;
            } else {
                MINI_SHOWPANE = false;
            }

            // Remove Jappix Mini
            jQuery('#jappix_mini').remove();

            // Reconnect?
            if(MINI_RECONNECT) {
                JappixConsole.log('Trying to reconnect (try: ' + MINI_RECONNECT + ')!');

                return self.create(domain, user, password);
            }

            // Load the Mini stylesheet
            self.loadStylesheet();

            // Disables the browser HTTP-requests stopper
            jQuery(document).keydown(function(e) {
                if((e.keyCode == 27) && !JappixSystem.isDeveloper()) {
                    return false;
                }
            });

            // Save the page title
            MINI_TITLE = document.title;

            // Adapts the content to the window size
            jQuery(window).resize(function() {
                self.adaptRoster();
                self.updateOverflow();
            });

            // Logouts when Jappix is closed
            if(BrowserDetect.browser == 'Opera') {
                // Emulates onbeforeunload on Opera (link clicked)
                jQuery('a[href]:not([onclick])').click(function() {
                    var this_sel = jQuery(this);

                    // Link attributes
                    var href = this_sel.attr('href') || '';
                    var target = this_sel.attr('target') || '';

                    // Not new window or JS link
                    if(href && !href.match(/^#/i) && !target.match(/_blank|_new/i)) {
                        self.saveSession();
                    }
                });

                // Emulates onbeforeunload on Opera (form submitted)
                jQuery('form:not([onsubmit])').submit(self.saveSession);
            }

            jQuery(window).bind('beforeunload', self.saveSession);

            // Create the Jappix Mini DOM content
            self.create(domain, user, password);

            JappixConsole.log('Welcome to Jappix Mini! Happy coding in developer mode!');
        } catch(e) {
            JappixConsole.error('JappixMini.process', e);
        }

    };


    /**
     * Plugin launcher
     * @public
     * @param {object} args
     * @return {undefined}
     */
    self.launch = function(args) {

        try {
            // Configure the app
            self.configure(args);

            // Initialize the app!
            self.process(
                MINI_AUTOCONNECT,
                MINI_SHOWPANE,
                MINI_DOMAIN,
                MINI_USER,
                MINI_PASSWORD,
                MINI_PRIORITY
            );
        } catch(e) {
            JappixConsole.error('JappixMini.launch', e);
        }

    };


    /**
     * Return class scope
     */
    return self;

})();

/* Legacy compatibility layer */
var launchMini = JappixMini.process;

// Configuration
XML_LANG = 'en';
JAPPIX_VERSION = jQuery.trim('Primo [1.1.7~dev]');
JAPPIX_STATIC = '/';
