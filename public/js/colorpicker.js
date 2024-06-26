/**
 * Minified by jsDelivr using UglifyJS v3.1.10.
 * Original file: /npm/jquery-simplecolorpicker@0.3.1/jquery.simplecolorpicker.js
 * 
 * Do NOT use SRI with dynamically generated files! More information: https://www.jsdelivr.com/using-sri-with-dynamic-files
 */
!function(e){"use strict";var t=function(e,t){this.init("simplecolorpicker",e,t)};t.prototype={constructor:t,init:function(t,o,i){var s=this;if(s.type=t,s.$select=e(o),s.$select.hide(),s.options=e.extend({},e.fn.simplecolorpicker.defaults,i),s.$colorList=null,!0===s.options.picker){var c=s.$select.find("> option:selected").text();s.$icon=e('<span class="simplecolorpicker icon" title="'+c+'" style="background-color: '+s.$select.val()+';" role="button" tabindex="0"></span>').insertAfter(s.$select),s.$icon.on("click."+s.type,e.proxy(s.showPicker,s)),s.$picker=e('<span class="simplecolorpicker picker '+s.options.theme+'"></span>').appendTo(document.body),s.$colorList=s.$picker,e(document).on("mousedown."+s.type,e.proxy(s.hidePicker,s)),s.$picker.on("mousedown."+s.type,e.proxy(s.mousedown,s))}else s.$inline=e('<span class="simplecolorpicker inline '+s.options.theme+'"></span>').insertAfter(s.$select),s.$colorList=s.$inline;s.$select.find("> option").each(function(){var t=e(this),o=t.val(),i=t.is(":selected"),c=t.is(":disabled"),r="";!0===i&&(r=" data-selected");var n="";!0===c&&(n=" data-disabled");var l="";!1===c&&(l=' title="'+t.text()+'"');var p="";!1===c&&(p=' role="button" tabindex="0"');var a=e('<span class="color"'+l+' style="background-color: '+o+';" data-color="'+o+'"'+r+n+p+"></span>");s.$colorList.append(a),a.on("click."+s.type,e.proxy(s.colorSpanClicked,s));!0===t.next().is("optgroup")&&s.$colorList.append('<span class="vr"></span>')})},selectColor:function(t){var o=this.$colorList.find("> span.color").filter(function(){return e(this).data("color").toLowerCase()===t.toLowerCase()});o.length>0?this.selectColorSpan(o):console.error("The given color '"+t+"' could not be found")},showPicker:function(){var e=this.$icon.offset();this.$picker.css({left:e.left-6,top:e.top+this.$icon.outerHeight()}),this.$picker.show(this.options.pickerDelay)},hidePicker:function(){this.$picker.hide(this.options.pickerDelay)},selectColorSpan:function(e){var t=e.data("color"),o=e.prop("title");e.siblings().removeAttr("data-selected"),e.attr("data-selected",""),!0===this.options.picker&&(this.$icon.css("background-color",t),this.$icon.prop("title",o),this.hidePicker()),this.$select.val(t)},colorSpanClicked:function(t){!1===e(t.target).is("[data-disabled]")&&(this.selectColorSpan(e(t.target)),this.$select.trigger("change"))},mousedown:function(e){e.stopPropagation(),e.preventDefault()},destroy:function(){!0===this.options.picker&&(this.$icon.off("."+this.type),this.$icon.remove(),e(document).off("."+this.type)),this.$colorList.off("."+this.type),this.$colorList.remove(),this.$select.removeData(this.type),this.$select.show()}},e.fn.simplecolorpicker=function(o){var i=e.makeArray(arguments);return i.shift(),this.each(function(){var s=e(this),c=s.data("simplecolorpicker"),r="object"==typeof o&&o;void 0===c&&s.data("simplecolorpicker",c=new t(this,r)),"string"==typeof o&&c[o].apply(c,i)})},e.fn.simplecolorpicker.defaults={theme:"",picker:!1,pickerDelay:0}}(jQuery);

$(document).ready(() => {
	$('select[name="colorpicker"]').simplecolorpicker({
		picker: true
	}).on('change', () => {
		let valor = $('select[name="colorpicker"]').val();
		$('input[name=rColor]').css({ 
			color: valor 
		}).attr({ 
			value: valor.replace('#', '') 
		});
	});
})