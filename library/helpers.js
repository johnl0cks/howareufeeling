$.post_json=function(settings){
	settings.accepts={json: 'application/json'};
	settings.data=JSON.stringify(settings.data);
	//settings.dataType='json';
	settings.method='POST';
	settings.processData=false;
	return this.ajax(settings);
};

jQuery.fn.extend({
	offsetOuterBox: function(){
		var offset=this.offset();
		offset.right=offset.left+this.outerWidth();
		offset.bottom=offset.top+this.outerHeight();
		return offset;
	},
});

$(document).on('click','.closer',function(){
	$(this.parentNode).fadeOut(200);
});

