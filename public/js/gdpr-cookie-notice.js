function gdprCookieNotice(e){function o(o){for(var t=0;t<w.length;t++)if(e[w[t]]&&!o[w[t]])for(var n=0;n<e[w[t]].length;n++){var i=e[w[t]][n];g.remove(i),document.cookie=i+"=; expires=Thu, 01 Jan 1970 00:00:01 GMT;",!0}}function t(){document.documentElement.classList.remove(k+"-loaded")}function n(t){var n={date:new Date,necessary:!0,performace:!0,analytics:!0,marketing:!1};if(t)for(var i=0;i<w.length;i++)n[w[i]]=document.getElementById(k+"-cookie_"+w[i]).checked,console.log(n[w[i]]),console.log(w[i]),console.log("----");g.set(p,n,{expires:e.expiration,domain:e.domain}),o(n),E=new CustomEvent("gdprCookiesEnabled",{detail:n}),document.dispatchEvent(E)}function i(){if(h)return!1;var e=c("bar.html");document.body.insertAdjacentHTML("beforeend",e),d(),h=!0}function c(o,t){var n=f[o],i=gdprCookieNoticeLocales[e.locale];if(t?t+="_":t="",!("string"==typeof n&&i instanceof Object))return!1;for(var c in i)return n.replace(/({([^}]+)})/g,function(e){var o=e.replace(/{/,"").replace(/}/,"");return"prefix"==o?t.slice(0,-1):i[o]?i[o]:i[t+o]?i[t+o]:e})}function a(){if(v)return!1;var o=c("modal.html");document.body.insertAdjacentHTML("beforeend",o);var t=document.querySelector("."+k+"-modal-cookies");t.innerHTML+=c("category.html","cookie_essential");var n=document.querySelector("."+k+"-modal-cookie-input"),i=document.querySelector("."+k+"-modal-cookie-input-switch");i.innerHTML=gdprCookieNoticeLocales[e.locale].always_on,i.classList.add(k+"-modal-cookie-state"),i.classList.remove(k+"-modal-cookie-input-switch"),n.remove(),e.performace&&(t.innerHTML+=c("category.html","cookie_performace")),e.analytics&&(t.innerHTML+=c("category.html","cookie_analytics")),e.marketing&&(t.innerHTML+=c("category.html","cookie_marketing")),l(),b?(document.getElementById(k+"-cookie_performace").checked=b.performace,document.getElementById(k+"-cookie_analytics").checked=b.analytics,document.getElementById(k+"-cookie_marketing").checked=b.marketing):(document.getElementById(k+"-cookie_performace").checked=!0,document.getElementById(k+"-cookie_analytics").checked=!0,document.getElementById(k+"-cookie_marketing").checked=!1),v=!0}function r(){a(),document.documentElement.classList.add(k+"-show-modal")}function s(){document.documentElement.classList.remove(k+"-show-modal")}function d(){var e=document.querySelectorAll("."+k+"-nav-item-settings")[0],o=document.querySelectorAll("."+k+"-nav-item-accept")[0];e.addEventListener("click",function(e){e.preventDefault(),r()}),o.addEventListener("click",function(e){e.preventDefault(),n(),t()})}function l(){var o=document.querySelectorAll("."+k+"-modal-close")[0],i=document.querySelectorAll("."+k+"-modal-footer-item-statement")[0],c=document.querySelectorAll("."+k+"-modal-cookie-title"),a=document.querySelectorAll("."+k+"-modal-footer-item-save")[0];o.addEventListener("click",function(){return s(),!1}),i.addEventListener("click",function(o){o.preventDefault(),window.location.href=e.statement});for(var r=0;r<c.length;r++)c[r].addEventListener("click",function(){return this.parentNode.parentNode.classList.toggle("open"),!1});a.addEventListener("click",function(e){e.preventDefault(),a.classList.add("saved"),setTimeout(function(){a.classList.remove("saved")},1e3),n(!0),s(),t()})}function m(){var e=document;return Math.max(e.body.scrollHeight,e.documentElement.scrollHeight,e.body.offsetHeight,e.documentElement.offsetHeight,e.body.clientHeight,e.documentElement.clientHeight)}function u(){var e=window.innerHeight||(document.documentElement||document.body).clientHeight,o=m(),t=window.pageYOffset||(document.documentElement||document.body.parentNode||document.body).scrollTop,n=o-e;return Math.floor(t/n*100)>25&&!y&&(y=!0,!0)}var p="gdprcookienotice",k="gdpr-cookie-notice",f=window[k+"-templates"],g=Cookies.noConflict(),v=!1,h=!1,y=!1,w=["performace","analytics","marketing"];e.locale||(e.locale="en"),e.timeout||(e.timeout=500),e.domain||(e.domain=null),e.expiration||(e.expiration=30);var b=function(){return g.getJSON(p)}(),E=new CustomEvent("gdprCookiesEnabled",{detail:b});b?(o(b),document.dispatchEvent(E)):(function(){i(),setTimeout(function(){document.documentElement.classList.add(k+"-loaded")},e.timeout)}(),e.implicit&&function(){window.addEventListener("scroll",function e(){u()&&(n(),window.removeEventListener("click",e))})}());var _=document.querySelectorAll("."+k+"-settings-button");if(_)for(var C=0;C<_.length;C++)_[C].addEventListener("click",function(e){e.preventDefault(),r()})}!function(e){var o=!1;if("function"==typeof define&&define.amd&&(define(e),o=!0),"object"==typeof exports&&(module.exports=e(),o=!0),!o){var t=window.Cookies,n=window.Cookies=e();n.noConflict=function(){return window.Cookies=t,n}}}(function(){function e(){for(var e=0,o={};e<arguments.length;e++){var t=arguments[e];for(var n in t)o[n]=t[n]}return o}function o(t){function n(o,i,c){var a;if("undefined"!=typeof document){if(arguments.length>1){if(c=e({path:"/"},n.defaults,c),"number"==typeof c.expires){var r=new Date;r.setMilliseconds(r.getMilliseconds()+864e5*c.expires),c.expires=r}c.expires=c.expires?c.expires.toUTCString():"";try{a=JSON.stringify(i),/^[\{\[]/.test(a)&&(i=a)}catch(e){}i=t.write?t.write(i,o):encodeURIComponent(String(i)).replace(/%(23|24|26|2B|3A|3C|3E|3D|2F|3F|40|5B|5D|5E|60|7B|7D|7C)/g,decodeURIComponent),o=encodeURIComponent(String(o)),o=o.replace(/%(23|24|26|2B|5E|60|7C)/g,decodeURIComponent),o=o.replace(/[\(\)]/g,escape);var s="";for(var d in c)c[d]&&(s+="; "+d,!0!==c[d]&&(s+="="+c[d]));return document.cookie=o+"="+i+s}o||(a={});for(var l=document.cookie?document.cookie.split("; "):[],m=/(%[0-9A-Z]{2})+/g,u=0;u<l.length;u++){var p=l[u].split("="),k=p.slice(1).join("=");this.json||'"'!==k.charAt(0)||(k=k.slice(1,-1));try{var f=p[0].replace(m,decodeURIComponent);if(k=t.read?t.read(k,f):t(k,f)||k.replace(m,decodeURIComponent),this.json)try{k=JSON.parse(k)}catch(e){}if(o===f){a=k;break}o||(a[f]=k)}catch(e){}}return a}}return n.set=n,n.get=function(e){return n.call(n,e)},n.getJSON=function(){return n.apply({json:!0},[].slice.call(arguments))},n.defaults={},n.remove=function(o,t){n(o,"",e(t,{expires:-1}))},n.withConverter=o,n}return o(function(){})}),window["gdpr-cookie-notice-templates"]={},window["gdpr-cookie-notice-templates"]["bar.html"]='<div class="gdpr-cookie-notice">\n  <p class="gdpr-cookie-notice-description">{description}</p>\n  <nav class="gdpr-cookie-notice-nav">\n    <a href="#" class="gdpr-cookie-notice-nav-item gdpr-cookie-notice-nav-item-settings">{settings}</a>\n    <a href="#" class="gdpr-cookie-notice-nav-item gdpr-cookie-notice-nav-item-accept gdpr-cookie-notice-nav-item-btn">{accept}</a>\n  </div>\n</div>\n',window["gdpr-cookie-notice-templates"]["category.html"]='<li class="gdpr-cookie-notice-modal-cookie">\n  <div class="gdpr-cookie-notice-modal-cookie-row">\n    <h3 class="gdpr-cookie-notice-modal-cookie-title">{title}</h3>\n    <input type="checkbox" name="gdpr-cookie-notice-{prefix}" checked="checked" id="gdpr-cookie-notice-{prefix}" class="gdpr-cookie-notice-modal-cookie-input">\n    <label class="gdpr-cookie-notice-modal-cookie-input-switch" for="gdpr-cookie-notice-{prefix}"></label>\n  </div>\n  <p class="gdpr-cookie-notice-modal-cookie-info">{desc}</p>\n</li>\n',window["gdpr-cookie-notice-templates"]["modal.html"]='<div class="gdpr-cookie-notice-modal">\n  <div class="gdpr-cookie-notice-modal-content">\n    <div class="gdpr-cookie-notice-modal-header">\n      <h2 class="gdpr-cookie-notice-modal-title">{settings}</h2>\n      <button type="button" class="gdpr-cookie-notice-modal-close"></button>\n    </div>\n    <ul class="gdpr-cookie-notice-modal-cookies"></ul>\n    <div class="gdpr-cookie-notice-modal-footer">\n      <a href="#" class="gdpr-cookie-notice-modal-footer-item gdpr-cookie-notice-modal-footer-item-statement">{statement}</a>\n      <a href="#" class="gdpr-cookie-notice-modal-footer-item gdpr-cookie-notice-modal-footer-item-save gdpr-cookie-notice-modal-footer-item-btn"><span>{save}</span></a>\n    </div>\n  </div>\n</div>\n',window["gdpr-cookie-notice-templates"]["settings.html"]='<div class="gdpr-cookie-notice-settings">\n  <p class="gdpr-cookie-notice-settings-button gdpr-cookie-notice-nav-item gdpr-cookie-notice-nav-item-accept gdpr-cookie-notice-nav-item-btn"><a href="#">{revoke}</a></p>\n</div>\n';var gdprCookieNoticeLocales={};gdprCookieNoticeLocales.en={description:'We use cookies to help our website function, and analytical cookies to improve our website. Please click <strong class="gdpr-cookie-notice-settings-button">Cookie Settings</strong> to amend cookie settings or click OK to accept all.',settings:"Cookie settings",accept:"OK",revoke:"Cookie settings",statement:"Our cookie policy",save:"Save settings",always_on:"Always on",cookie_essential_title:"Essential website cookies",cookie_essential_desc:"Necessary cookies help make a website usable by enabling basic functions like page navigation and access to secure areas of the website. The website cannot function properly without these cookies.",cookie_performace_title:"Performance cookies",cookie_performace_desc:"These cookies are used to enhance the performance and functionality of our websites but are non-essential to their use. For example they are used by our hosting service for load balancing.",cookie_analytics_title:"Analytics cookies",cookie_analytics_desc:"We use analytical cookies to analyse the use of our website and its performance, in order to improve it (for example, to count the number of visitors and to see how visitors move around our website when they are using it).",cookie_marketing_title:"Marketing cookies",cookie_marketing_desc:"We do not use any marketing cookies."};