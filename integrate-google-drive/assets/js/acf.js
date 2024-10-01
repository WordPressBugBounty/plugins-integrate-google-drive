(()=>{"use strict";function e(e){var t;return!e||!e.type||("application/vnd.google-apps.folder"===e.type||"application/vnd.google-apps.folder"===(null===(t=e.shortcutDetails)||void 0===t?void 0:t.targetMimeType))}function t(e,t){var n=arguments.length>2&&void 0!==arguments[2]?arguments[2]:{},o=e.id,l=e.iconLink,r=e.thumbnailLink;e.accountId;e.shortcutDetails&&(o=e.shortcutDetails.targetId);var a=n.w,c=n.h;a||(a=64),c||(c=64);var d=null==l?void 0:l.replace("/16/","/".concat(a,"/"));return r&&(d=function(e){var t,i;return"reader"===(null===(t=e.permissions)||void 0===t||null===(t=t.users)||void 0===t||null===(t=t.anyoneWithLink)||void 0===t?void 0:t.role)||"writer"===(null===(i=e.permissions)||void 0===i||null===(i=i.users)||void 0===i||null===(i=i.anyoneWithLink)||void 0===i?void 0:i.role)}(e)?"custom"===t?"https://drive.google.com/thumbnail?id=".concat(o,"&sz=w").concat(a,"-h").concat(c):"https://drive.google.com/thumbnail?id=".concat(o,"small"===t?"&sz=w300-h300":"medium"===t?"&sz=w600-h400":"large"===t?"&sz=w1024-h768":"full"===t?"&sz=w2048":"&sz=w300-h300"):r.includes("google.com")?"custom"===t?r.replace("=s220","=w".concat(a,"-h").concat(c)):"small"===t?r.replace("=s220","=w300-h300"):"medium"===t?r.replace("=s220","=h600-nu"):"large"===t?r.replace("=s220","=w1024-h768-p-k-nu"):"full"===t?r.replace("=s220",""):r.replace("=s220","=w200-h190-p-k-nu"):function(e,t){var n=arguments.length>2&&void 0!==arguments[2]?arguments[2]:300,o=arguments.length>3&&void 0!==arguments[3]?arguments[3]:300,l={id:e.id,accountId:e.accountId,size:t};"custom"===t&&(l.w=n,l.h=o);var r=i(JSON.stringify(l));return"".concat(igd.homeUrl,"/?igd_preview_image=").concat(r)}(e,t,a,c)),d}function i(e){return btoa(encodeURIComponent(e).replace(/%([0-9A-F]{2})/g,(function(e,t){return String.fromCharCode("0x"+t)})))}function n(e){return function(e){if(Array.isArray(e))return o(e)}(e)||function(e){if("undefined"!=typeof Symbol&&null!=e[Symbol.iterator]||null!=e["@@iterator"])return Array.from(e)}(e)||function(e,t){if(!e)return;if("string"==typeof e)return o(e,t);var i=Object.prototype.toString.call(e).slice(8,-1);"Object"===i&&e.constructor&&(i=e.constructor.name);if("Map"===i||"Set"===i)return Array.from(e);if("Arguments"===i||/^(?:Ui|I)nt(?:8|16|32)(?:Clamped)?Array$/.test(i))return o(e,t)}(e)||function(){throw new TypeError("Invalid attempt to spread non-iterable instance.\nIn order to be iterable, non-array objects must have a [Symbol.iterator]() method.")}()}function o(e,t){(null==t||t>e.length)&&(t=e.length);for(var i=0,n=new Array(t);i<t;i++)n[i]=e[i];return n}var l,r,a;l=jQuery,r=window.ModuleBuilderModal,a={init:function(){l(document).on("click",".igd-acf-button",(function(e){var t=l(this);Swal.fire({html:'<div id="igd-select-files" class="igd-module-builder-modal-wrap"></div>',showConfirmButton:!1,customClass:{container:"igd-swal igd-module-builder-modal-container"},didOpen:function(e){ReactDOM.render(React.createElement(r,{initData:{},onUpdate:function(e){a.addFiles(t,e),Swal.close()},onClose:function(){return Swal.close()},isSelectFiles:!0}),document.getElementById("igd-select-files"))},willClose:function(e){ReactDOM.unmountComponentAtNode(document.getElementById("igd-select-files"))}})})),l(document).on("click",".igd-acf-field .file-remove",(function(e){e.preventDefault();var t=l(this).data("id"),i=l(this).closest(".igd-acf-field"),n=JSON.parse(l("input",i).val()).filter((function(e){return e.id!==t}));l("input",i).val(JSON.stringify(n)),l(this).closest("tr").remove()}))},addFiles:function(o,r){var a=r.folders,c=void 0===a?[]:a,d=[],s=o.parents(".igd-acf-field");l(".empty-row",s).remove();var u=l('<tr><td><img class="file-icon" src=""/></td><td class="file-name" style="width: 300px;overflow: hidden;white-space: nowrap;text-overflow: ellipsis;display: block;"></td><td class="file-id"></td><td class="file-actions"><a href="" target="_blank" class="button file-view">View</a> <a href="" class="button file-download">Download</a> <a href="#" class="button button-link-delete file-remove" data-id="">Remove</a></td></tr>');c.forEach((function(n){var o=n.id,r=n.accountId,a=(n.name,e(n)?"file_ids=".concat(i(JSON.stringify([o])),"&ignore_limit=1"):"id=".concat(o,"&accountId=").concat(r)),c="".concat(igd.homeUrl,"?igd_download=1&").concat(a,"&ignore_limit=1"),f="https://drive.google.com/file/d/".concat(n.id,"/preview?rm=minimal"),m=t(n,"full");d.push({id:n.id,account_id:n.accountId,name:n.name,type:n.type,size:n.size,embed_link:f,download_link:c,view_link:n.webViewLink,icon_link:n.iconLink,thumbnail_link:m});var p=u.clone();p.find(".file-icon").attr("src",n.iconLink),p.find(".file-name").text(n.name),p.find(".file-id").text(n.id),p.find(".file-view").attr("href",n.webViewLink),p.find(".file-download").attr("href",c),p.find(".file-remove").data("id",n.id),l("table",s).append(p)}));var f=l("input",s),m=f.val()?JSON.parse(f.val()):[];f.val(JSON.stringify([].concat(n(m),d)))}},l(document).ready(a.init)})();