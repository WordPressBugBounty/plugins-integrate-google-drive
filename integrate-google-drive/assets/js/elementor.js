(()=>{"use strict";function e(e){return btoa(encodeURIComponent(e).replace(/%([0-9A-F]{2})/g,(function(e,t){return String.fromCharCode("0x"+t)})))}function t(e){return t="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e},t(e)}function o(e,t){var o=Object.keys(e);if(Object.getOwnPropertySymbols){var n=Object.getOwnPropertySymbols(e);t&&(n=n.filter((function(t){return Object.getOwnPropertyDescriptor(e,t).enumerable}))),o.push.apply(o,n)}return o}function n(e){for(var t=1;t<arguments.length;t++){var n=null!=arguments[t]?arguments[t]:{};t%2?o(Object(n),!0).forEach((function(t){r(e,t,n[t])})):Object.getOwnPropertyDescriptors?Object.defineProperties(e,Object.getOwnPropertyDescriptors(n)):o(Object(n)).forEach((function(t){Object.defineProperty(e,t,Object.getOwnPropertyDescriptor(n,t))}))}return e}function r(e,o,n){return(o=function(e){var o=function(e,o){if("object"!=t(e)||!e)return e;var n=e[Symbol.toPrimitive];if(void 0!==n){var r=n.call(e,o||"default");if("object"!=t(r))return r;throw new TypeError("@@toPrimitive must return a primitive value.")}return("string"===o?String:Number)(e)}(e,"string");return"symbol"==t(o)?o:o+""}(o))in e?Object.defineProperty(e,o,{value:n,enumerable:!0,configurable:!0,writable:!0}):e[o]=n,e}var i,l,a;i=jQuery,l=window.ModuleBuilderModal,(a={init:function(){i(window).on("elementor/frontend/init",(function(){"undefined"!=typeof elementor&&elementor.channels.editor.on("igd:editor:edit_module",a.initModuleBuilder),a.initPromotion()}))},ready:function(){"undefined"!=typeof elementor&&elementor.hooks.addFilter("elementor_pro/forms/content_template/field/google_drive_upload",(function(t,o,n){var r=o.module_data;r||(r=JSON.stringify({type:"uploader",isFormUploader:"elementor",isRequired:o.required,width:"100%",height:""}));var i=JSON.parse(r),l=i.width,a=i.height;return'<div class="igd igd-shortcode-wrap igd-shortcode-uploader" style="width: '.concat(l,"; height: ").concat(a,";\" data-shortcode-data='").concat(e(r),"'></div>")}),10,3)},initModuleBuilder:function(t){Swal.fire({html:'<div id="igd-elementor-module-builder" class="igd-module-builder-modal-wrap"></div>',showConfirmButton:!1,customClass:{container:"igd-swal igd-module-builder-modal-container"},didOpen:function(){var o={},r=t.$el.hasClass("elementor-control-edit_field_form")||t.$el.hasClass("elementor-control-edit_field_metform"),a=!1;r&&(o={type:"uploader",isFormUploader:a=t.$el.hasClass("elementor-control-edit_field_form")?"elementor":"metform"});var d=window.parent.jQuery('[data-setting="module_data"]');r&&"metform"!==a&&(d=window.parent.jQuery('.elementor-repeater-row-controls.editable [data-setting="module_data"]'));var u=i(d).val();if(u)try{o=JSON.parse(u)}catch(e){o={}}ReactDOM.render(React.createElement(l,{initData:o,onUpdate:function(t){!function(t){var o=JSON.stringify(n(n({},t),{},{isInit:!1}));wp.ajax.post("igd_reset_shortcode_transient",{data:e(o),nonce:igd.nonce}),d.val(o).trigger("input")}(t),Swal.close()},onClose:function(){return Swal.close()},isFormBuilder:a}),document.getElementById("igd-elementor-module-builder"))},willClose:function(){ReactDOM.unmountComponentAtNode(document.getElementById("igd-elementor-module-builder"))}})},initPromotion:function(){if(void 0===parent.document)return!1;parent.document.addEventListener("mousedown",(function(e){var t=parent.document.querySelectorAll(".elementor-element--promotion");if(t.length>0)for(var o=0;o<t.length;o++)if(t[o].contains(e.target)){var n=parent.document.querySelector("#elementor-element--promotion__dialog");if(t[o].querySelector(".icon > i").classList.toString().indexOf("igd")>=0)if(e.stopImmediatePropagation(),n.querySelector(".dialog-buttons-action").style.display="none",null===n.querySelector(".igd-dialog-buttons-action")){var r=document.createElement("a"),i=document.createTextNode(wp.i18n.__("GET PRO","integrate-google-drive"));r.setAttribute("href","".concat(igd.upgradeUrl)),r.classList.add("dialog-button","dialog-action","dialog-buttons-action","elementor-button","elementor-button-success","igd-dialog-buttons-action"),r.appendChild(i),n.querySelector(".dialog-buttons-action").insertAdjacentHTML("afterend",r.outerHTML)}else n.querySelector(".igd-dialog-buttons-action").style.display="";else n.querySelector(".dialog-buttons-action").style.display="",null!==n.querySelector(".igd-dialog-buttons-action")&&(n.querySelector(".igd-dialog-buttons-action").style.display="none");break}}))}}).init(),i(document).ready(a.ready)})();