function getUrlVars() {
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return vars;
}

jQuery(function() {
	jQuery("#tabs").tabs();
	var ctab = getUrlVars()["tab"];
	//alert(ctab);
	if (ctab != undefined){
		var $tabs = jQuery('#tabs').tabs();
    	$tabs.tabs('select', ctab);
	}
	
	jQuery("ul#tab-items li a").click(function(event){
		var url = this;
		var hid = document.getElementById("tz_selectedtab");
		hid.value = url;
	});
	
});