(()=>{"use strict";const e=window.React,t=JSON.parse('{"$schema":"https://json.schemastore.org/block.json","apiVersion":2,"name":"igd/gallery","version":"1.0.0","title":"Gallery","category":"igd-category","icon":"format-gallery","description":"Insert a masonry grid gallery with lightbox preview to display Google Drive images and videos.","supports":{"html":false},"attributes":{"isInit":{"type":"boolean","default":true},"isEdit":{"type":"boolean","default":true},"data":{"type":"object","default":{"status":"on","type":"gallery","folders":[],"showFiles":true,"showFolders":true,"sort":{"sortBy":"name","sortDirection":"asc"},"width":"100%","height":"auto","view":"grid","lazyLoad":true,"lazyLoadNumber":100,"maxFileSize":"","galleryStyle":"simple","openNewTab":true,"download":true,"displayFor":"everyone","displayUsers":["everyone"],"displayExcept":[]}}},"keywords":["photo gallery","gallery","google","drive","integrate google drive"],"textdomain":"integrate-google-drive","editorScript":"file:./index.js","editorStyle":"file:./index.css"}'),l=window.wp.components,o=window.wp.blockEditor,{registerBlockType:n}=wp.blocks;n("igd/gallery",{...t,icon:(0,e.createElement)("svg",{width:"18",height:"18",viewBox:"0 0 18 18",fill:"none",xmlns:"http://www.w3.org/2000/svg"},(0,e.createElement)("path",{fileRule:"evenodd",clipRule:"evenodd",d:"M2.77615 0.0311665C2.41559 0.091723 1.91435 0.28429 1.58898 0.48727C0.857221 0.943776 0.38213 1.576 0.113329 2.45093C0.024787 2.73908 0.0243853 2.75484 0.0122128 6.37926L0 10.0183L1.93953 8.08092C4.04141 5.98134 3.95926 6.04886 4.33098 6.11609C4.4644 6.14022 4.81033 6.47199 7.35403 9.01528L10.2264 11.8872L11.6526 10.464C12.98 9.13933 13.0905 9.03892 13.2486 9.01347C13.6016 8.95665 13.5638 8.92473 15.8608 11.2185L18 13.3547L17.9997 8.2083C17.9995 4.83246 17.985 2.97202 17.9575 2.80056C17.8175 1.92671 17.2943 1.0901 16.5777 0.59431C16.239 0.359965 15.9658 0.229202 15.5372 0.0963875L15.2481 0.00683941L9.12167 0.000606883C5.57093 -0.00301203 2.90309 0.00985515 2.77615 0.0311665ZM13.6037 2.60228C13.9914 2.67217 14.4622 2.92316 14.7545 3.21569C15.205 3.66661 15.4076 4.16151 15.4076 4.81107C15.4076 5.45608 15.2048 5.95573 14.7638 6.39712C14.3228 6.83854 13.8236 7.04157 13.1792 7.04157C12.5297 7.04157 12.0489 6.8445 11.5909 6.39056C10.5093 5.31856 10.8116 3.48329 12.1809 2.80852C12.662 2.57148 13.0893 2.50956 13.6037 2.60228ZM2.08111 9.74703L0.00228986 11.828L0.00261122 13.376C0.00277191 14.2546 0.0210106 15.037 0.0447933 15.1853C0.269966 16.5903 1.41069 17.7321 2.81443 17.9575C2.98674 17.9851 5.02293 17.9995 8.80024 17.9997L14.5249 18L9.36271 12.833C6.52353 9.99114 4.19142 7.66599 4.18025 7.66599C4.16908 7.66599 3.22448 8.60244 2.08111 9.74703ZM12.245 11.6772L11.1305 12.7932L13.5783 15.243L16.0262 17.6929L16.1986 17.6152C16.2934 17.5725 16.4788 17.4583 16.6104 17.3613C17.26 16.883 17.7215 16.1812 17.9037 15.3947L17.965 15.1304L15.6827 12.8457C14.4274 11.5892 13.3912 10.5611 13.3799 10.5611C13.3686 10.5611 12.8579 11.0633 12.245 11.6772Z",fill:"url(#paint0_linear_858_4551)"}),(0,e.createElement)("defs",null,(0,e.createElement)("linearGradient",{id:"paint0_linear_858_4551",x1:"1.86486",y1:"-1.24499e-07",x2:"11.1892",y2:"18.8108",gradientUnits:"userSpaceOnUse"},(0,e.createElement)("stop",{stopColor:"#D3C7FF"}),(0,e.createElement)("stop",{offset:"1",stopColor:"#6861FF"})))),edit:function({attributes:t,setAttributes:n}){const{ModuleBuilderModal:i,IgdShortcode:r}=window,{data:a,isInit:s}=t;a.uniqueId||(a.uniqueId="igd_"+Math.random().toString(36).substring(7));const d=()=>{Swal.fire({html:'<div id="igd-gutenberg-module-builder" class="igd-module-builder-modal-wrap"></div>',showConfirmButton:!1,customClass:{container:"igd-module-builder-modal-container"},didOpen(t){const l=document.getElementById("igd-gutenberg-module-builder");ReactDOM.render((0,e.createElement)(i,{initData:a,onUpdate:e=>{n({data:e,isInit:!1}),Swal.close()},onClose:()=>Swal.close()}),l)},willClose(e){const t=document.getElementById("igd-gutenberg-module-builder");ReactDOM.unmountComponentAtNode(t)}})},c=wp.i18n.__("Configure","integrate-google-drive"),u="admin-generic";return(0,e.createElement)("div",{...(0,o.useBlockProps)()},(0,e.createElement)(o.InspectorControls,null,(0,e.createElement)(l.PanelBody,{title:"Settings",icon:"dashicons-shortcode",initialOpen:!0},(0,e.createElement)(l.PanelRow,null,(0,e.createElement)("button",{type:"button",className:"igd-btn btn-primary",onClick:()=>d()},(0,e.createElement)("i",{className:`dashicons dashicons-${u}`}),(0,e.createElement)("span",null,c))))),(0,e.createElement)(o.BlockControls,null,(0,e.createElement)(l.ToolbarGroup,null,(0,e.createElement)(l.ToolbarButton,{icon:u,label:c,text:c,showTooltip:!0,onClick:()=>d()}))),s?(0,e.createElement)("div",{className:"module-builder-placeholder"},(0,e.createElement)("img",{src:`${igd.pluginUrl}/assets/images/shortcode-builder/types/gallery.svg`}),(0,e.createElement)("h3",null,wp.i18n.__("Gallery","integrate-google-drive")),(0,e.createElement)("p",null,wp.i18n.__("Please configure the module first to display the content.","integrate-google-drive")),(0,e.createElement)("button",{type:"button",className:"igd-btn btn-primary",onClick:()=>d()},(0,e.createElement)("i",{className:`dashicons dashicons-${u}`}),(0,e.createElement)("span",null,c))):(0,e.createElement)(r,{data:a,isPreview:!0}))}})})();