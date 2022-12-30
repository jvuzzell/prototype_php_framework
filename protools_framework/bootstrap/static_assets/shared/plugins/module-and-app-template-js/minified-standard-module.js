var StandardModule=function(e){var t={},o=function(e,t,r){for(var a=!1,i=0;i<t.length;i++)if(e===t[i]){a=!0;break}return a?o(n(r),t,r):e},n=function(e){return"module_"+e+"_"+Math.floor(Math.random()*Math.floor(1e5))},r=function(e){if(void 0!==e.props&&void 0!==e.props.eventListeners&&null!==e.props.eventListeners&&"object"==typeof e.props.eventListeners){for(var t=Object.keys(e.props.eventListeners),o=e.state.key,n=c(o),r=0;r<t.length;r++)for(var a=e.props.eventListeners[t[r]],i=Object.keys(a),s=0;s<i.length;s++)a[i[s]].eventInit(o,n);n.commit.state({eventListenersExist:!0},!1)}},a=function(e,t=null){var r=StandardModule.getAllInstances(),a="",u=e.state,d=!1;if(void 0!==e.hooks&&null!==e.hooks&&"object"==typeof e.hooks&&void 0!==e.hooks.beforeCreate&&null!==e.hooks.beforeCreate&&"function"==typeof e.hooks.beforeCreate&&(d=!0),a=void 0===u.key?o(n(u.moduleName),Object.keys(r),u.moduleName):o(u.key,Object.keys(r),u.moduleName),e.state.key=a,d&&e.hooks.beforeCreate(e.state),s(a,new StandardModule.construct(e,t)),void 0!==e.parentApplicationNames&&null!==e.parentApplicationNames&&"object"==typeof e.parentApplicationNames&&i(a,e),void 0!==e.subscriptions&&null!==e.subscriptions&&"object"==typeof e.subscriptions)l(a,e);else if(void 0!==e.parentApplicationNames&&null!==e.parentApplicationNames&&"object"==typeof e.parentApplicationNames)for(var f=0;f<e.parentApplicationNames.length;f++)p(a,e.parentApplicationNames[f]);return c(a).hooks.afterCreate(e.state),a},i=function(t,o){for(var n=0;n<o.parentApplicationNames.length;n++){e.getInstance(o.parentApplicationNames[n]).dispatch("registerModule",{key:t,moduleObj:c(t)})}},s=function(e,o){t[e]=o},c=function(e=""){return""!==e&&null!=e&&t[e]},u=function(){return t},l=function(t="",o={}){for(var n=Object.keys(o.subscriptions),r=0;r<n.length;r++){e.getInstance(n[r]).get("getModuleFromStore",{moduleKey:t})||StandardModule.setParentApplication(t,{parentApplicationNames:[n[r]]})}for(var a=0;a<n.length;a++)for(var i=e.getInstance(n[a]),s=o.subscriptions[n[a]],c=0;c<s.length;c++)i.dispatch("addSubscribers",{publisherKey:s[c],subscriberKey:t}),i.dispatch("addSubscribers",{publisherKey:t,subscriberKey:s[c]})},p=function(t,o){if(void 0===o)return console.warn("StandardModule Plugin, subscribeToAllAppNotifications: applicationId is undefined. Subscriptions failed."),!1;var n=e.getInstance(o);if(void 0!==n&&n.hasOwnProperty("get")){var r=n.get("getAllModulesFromStore"),a=Object.keys(r),i=a.indexOf(t);a.splice(i,1),StandardModule.setSubscriptions(t,{subscriptions:{[o]:a}})}else console.warn("Module subscriptions for application failed: "+o)};return void 0===Array.prototype.unique&&(Array.prototype.unique=function(){for(var e=this.concat(),t=0;t<e.length;++t)for(var o=t+1;o<e.length;++o)e[t]===e[o]&&e.splice(o--,1);return e}),{construct:function(t={},o){var n={get:{},commit:{},dispatch:{},hooks:{beforeCreate:function(e){console.error("1. This will not run unless defined during module registration",e)},beforeUpdate:function(e){console.log("2. Module will update: "+e.key)},onUpdate:function(e){console.log("3. Module updating: "+e.key)},afterUpdate:function(e){console.log("4.  Module updated: "+e.key)},afterCreate:function(e){console.log("5. Module was created: "+e.key)},beforeMount:function(e){console.log("6. Module will mount: "+e.key)},onMount:function(e){console.log("7. Module mounting: "+e.key)},afterMount:function(e){console.log("8. Module has mounted: "+e.key)}}},r={ref:"",props:{},state:{key:"",moduleName:"",parentApplicationNames:[],eventListeners:{}},inlineTemplateNode:o},a={notifyApplication:function(t="",o={}){for(var r=n.get.state("parentApplicationNames"),a=0;a<r.length;a++)e.getInstance(r[a]).dispatch("updateApplication",{notifierType:"module",notifierKey:t,notifierStateDelta:o})},hasStateChanged:function(e){var t=n.get.state(),o={},r=!1;for(var i in o.stateChanged=!1,o.diff=a.compareObjects(t,e),o.diff)if(null!==o.diff[i]){r=!0;break}return Object.keys(o.diff).length>0&&!0===r&&(o.stateChanged=!0),o}};return n.get.state=function(e=""){return""==e?r.state:r.state[e]},n.get.props=function(e=""){return""==e?r.props:r.props[e]},n.get.ref=function(){return r.ref},n.get.observers=function(e=""){return""!==e&&(observerIndex=r.observers.indexOf(e),observer=r.observers[observerIndex]),""==e?r.observers:observer},n.get.inlineTemplateNode=function(){return r.inlineTemplateNode},n.commit.state=function(e={},t=!0,o=!0){var i=a.hasStateChanged(e);if(!0===i.stateChanged){for(var s in i.diff)null!==i.diff[s]&&(r.state[s]=i.diff[s]);if(console.log("Change in state: ",e),!t)return;if(n.dispatch.render(),!o)return;a.notifyApplication(n.get.state("key"),e)}},n.commit.props=function(e={}){for(var t in e)r.props[t]=e[t]},n.commit.ref=function(e=""){r.ref=e},n.dispatch.update=function(e,t){},n.dispatch.notifyApplication=function(e,t){a.notifyApplication(e,t)},n.dispatch.mount=function(){n.hooks.beforeMount(n.get.state()),n.hooks.onMount(n.get.state()),n.hooks.afterMount(n.get.state()),n.dispatch.notifyApplication(n.get.state("key"),n.get.state())},n.dispatch.render=function(){n.hooks.beforeUpdate(n.get.state()),n.hooks.onUpdate(n.get.state()),n.hooks.afterUpdate(n.get.state())},n.dispatch.registerInstance=function(e={}){if(void 0!==e.get&&null!==e.get&&"object"!=typeof e.get)for(var t=Object.keys(e.get),o=0;o<t.length;o++)n.get[t[o]]=e.get[t[o]];if(void 0!==e.commit&&null!==e.commit&&"object"==typeof e.commit){var r=Object.keys(e.commit);for(o=0;o<r.length;o++)n.commit[r[o]]=e.commit[r[o]]}if(void 0!==e.dispatch&&null!==e.dispatch&&"object"==typeof e.dispatch){var a=Object.keys(e.dispatch);for(o=0;o<a.length;o++)n.dispatch[a[o]]=e.dispatch[a[o]]}if(void 0!==e.hooks&&null!==e.hooks&&"object"==typeof e.hooks){var i=Object.keys(e.hooks),s="";for(o=0;o<i.length;o++){switch(i[o]){case"beforeCreate":s="beforeCreate";break;case"created":s="created";break;case"beforeMount":s="beforeMount";break;case"onMount":s="onMount";break;case"afterMount":s="afterMount";break;case"beforeUpdate":s="beforeUpdate";break;case"onUpdate":s="onUpdate";break;case"afterUpdate":s="afterUpdate";break;default:s=i[o]}n.hooks[s]=e.hooks[i[o]]}}return n.commit.ref(e.ref),n.commit.props(e.props),n.commit.state(e.state,!1),n},n.dispatch.createInlineTemplate=function(e,t){var o=StandardModule.templateToHTML(e).querySelector("div");return o.setAttribute("data-key",t),r.inlineTemplateNode=o,n.get.inlineTemplateNode()},n.get.parent=function(){return{commit:n.commit,dispatch:n.dispatch,hooks:n.hooks}},n.dispatch.parent=function(){return{get:n.get,commit:n.commit,hooks:n.hooks}},n.commit.parent=function(){return{get:n.get,dispatch:n.dispatch,hooks:n.hooks}},n.hooks.parent=function(){return{get:n.get,commit:n.commit,dispatch:n.dispatch,hooks:n.hooks}},
a.compareObjects=function(e={},t={}){if(!t||"[object Object]"!==Object.prototype.toString.call(t))return e;var o,n={},r=function(e,t,o){var r=Object.prototype.toString.call(e),i=Object.prototype.toString.call(t);if("[object Undefined]"!==i)if(r===i)if("[object Object]"!==r)"[object Array]"!==r?"[object Function]"===r?e.toString()!==t.toString()&&(n[o]=t):e!==t&&(n[o]=t):function(e,t){if(e.length!==t.length)return!1;for(var o=0;o<e.length;o++)if(e[o]!==t[o])return!1;return!0}(e,t)||(n[o]=t);else{var s=a.compareObjects(e,t);Object.keys(s).length>1&&(n[o]=s)}else n[o]=t;else n[o]=null};for(o in e)e.hasOwnProperty(o)&&r(e[o],t[o],o);for(o in t)t.hasOwnProperty(o)&&(e[o]||e[o]===t[o]||(n[o]=t[o]));return n},n.dispatch.registerInstance(t),n},registration:function(e,t=!1){var o=e.state,n="",i={};if(void 0===o.moduleName||null===o.moduleName)return console.warn("Standard Application: module instance not registered; state.moduleName not specified"),!1;var s='[data-inline-template="'+e.state.moduleName+'"]',u=document.querySelectorAll(s);if(u.length>0)for(var l=0;l<u.length;l++){StandardModule.getInstance(u[l].getAttribute("data-key"))||(null!==u[l].getAttribute("data-ref")&&(e.ref=u[l].getAttribute("data-ref")),n=a(e,u[l]),u[l].setAttribute("data-key",n),i[n]=c(n),i[n].dispatch.mount(),r(e))}else t||(i[n=a(e)]=c(n),i[n].dispatch.mount(),r(e));return t&&(i[n=a(e)]=c(n),i[n].dispatch.mount()),i},storeInstance:s,getInstance:c,getAllInstances:u,getModulesByModuleName:function(e=""){for(var t=Object.keys(u()),o={},n=0;n<t.length;n++){var r=c(t[n]);r.get.state("moduleName")===e&&(o[t[n]]=r)}return o},setSubscriptions:l,setParentApplication:i,subscribeToAllAppNotifications:p,templateToHTML:function(e){var t=function(){if(!window.DOMParser)return!1;var e=new DOMParser;try{e.parseFromString("x","text/html")}catch(e){return!1}return!0}();if(t)return(new DOMParser).parseFromString(e,"text/html").body;var o=document.createElement("div");return o.innerHTML=e,o},setEventListeners:r}}(StandardApplication);