!function(t){var n={};function o(e){if(n[e])return n[e].exports;var i=n[e]={i:e,l:!1,exports:{}};return t[e].call(i.exports,i,i.exports,o),i.l=!0,i.exports}o.m=t,o.c=n,o.d=function(t,n,e){o.o(t,n)||Object.defineProperty(t,n,{configurable:!1,enumerable:!0,get:e})},o.n=function(t){var n=t&&t.__esModule?function(){return t.default}:function(){return t};return o.d(n,"a",n),n},o.o=function(t,n){return Object.prototype.hasOwnProperty.call(t,n)},o.p="/",o(o.s=730)}({730:function(t,n,o){t.exports=o(731)},731:function(t,n,o){window.onload=function(){!function(t,n,e){"use strict";t(e).ready(function(){o(732),o(733),o(734),console.log("Global js ready!"),t('a[data-toggle="tab"]').on("shown.bs.tab",function(n){history.pushState?history.pushState(null,null,t(this).attr("href")):location.hash=t(this).attr("href")}),t("form[id$='-search']").submit(function(o){t("#formHash").length?t("#formHash").val(n.location.hash):t(this).append(t("<input>",{type:"hidden",id:"formHash",name:"formHash",val:n.location.hash}))})});var i=n.location.hash;if(t("#formHash").length)i=t("#formHash").val();if(""!=i||void 0!=i){var a=t('a[href="'+i+'"]');1==a.length&&a.tab("show")}"https://wiki.restarters.dev"!=n.location.origin&&"https://wiki.restarters.net"!=n.location.origin||(t(".wiki-nav-item").addClass("active"),t(".nav-tabs-block li.nav-item a.nav-link").removeClass("active"),t('.nav-tabs-block li.nav-item a.nav-link[href*="'+n.location.pathname+'"]').each(function(){t(this).addClass("active")}))}(jQuery,window,document)}},732:function(t,n){$(".toggle-dropdown-menu").click(function(){if($(this).hasClass("dropdown-active"))return $(".toggle-dropdown-menu").each(function(){$(this).removeClass("dropdown-active"),$(this).parents().children(".dropdown-menu-items").hide()}),!1;$(".toggle-dropdown-menu").not(this).each(function(){$(this).removeClass("dropdown-active"),$(this).parents().children(".dropdown-menu-items").hide()}),$(this).toggleClass("dropdown-active"),$(this).parents().children(".dropdown-menu-items").show()})},733:function(t,n){},734:function(t,n){}});