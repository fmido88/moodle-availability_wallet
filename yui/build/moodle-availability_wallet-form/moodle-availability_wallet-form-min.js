YUI.add("moodle-availability_wallet-form",function(t,a){M.availability_wallet=M.availability_wallet||{},M.availability_wallet.form=t.Object(M.core_availability.plugin),M.availability_wallet.form.initInner=function(){},M.availability_wallet.form.instId=1,M.availability_wallet.form.getNode=function(a){"use strict";var l,i="cost"+M.availability_wallet.form.instId;return M.availability_wallet.form.instId+=1,l="",l=(l+='<label for="'+i+'">')+(M.util.get_string("fieldlabel","availability_wallet")+" </label>"),i=t.Node.create("<span>"+(l+=' <input type="text" name="cost" id="'+i+'" step="0.01">')+"</span>"),a.cost!==undefined&&i.one("input[name=cost]").set("value",a.cost),M.availability_wallet.form.addedEvents||(M.availability_wallet.form.addedEvents=!0,t.one(".availability-field").delegate("change",function(){M.core_availability.form.update()},".availability_wallet input[name=cost]")),i},M.availability_wallet.form.fillValue=function(a,l){"use strict";a.cost=parseFloat(l.one("input[name=cost]").get("value"))},M.availability_wallet.form.fillErrors=function(a,l){"use strict";var i={};this.fillValue(i,l),(i.cost===undefined||""===i.cost||i.cost<=0)&&a.push("availability_wallet:validnumber")}},"@VERSION@",{requires:["base","node","event","moodle-core_availability-form"]});