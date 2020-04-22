$('input[type="text"],input[type="password"],input[type="email"],textarea').on("cut change keyup paste",function(){
	if(this.value!==this.defaultValue)
		this.classList.add('modified');
});

function error_display(for_id,message){
	if(message===null){
		var error_display=$('.error_display[for="'+for_id+'"]');
		error_display.text('');
		error_display.removeClass("invalid");
		error_display.addClass("valid");
	}else{
		var error_display=$('.error_display[for="'+for_id+'"]');
		error_display.text(message);
		error_display.removeClass("valid");
		error_display.addClass("invalid");
	}
}

$('input[type="text"],input[type="password"],input[type="email"],input[type="url"]').each(function(i,element){
	element.notify_valid=function(){
		this.setCustomValidity('');
		error_display(this.id,null);
	};
	element.notify_invalid=function(message){
		this.setCustomValidity(message);
		error_display(this.id,message);
	};
	element.validate=function(do_ajax=true){
		var validity=this.validity;
		if(validity.tooShort){
			this.notify_invalid('Too short, must be at least '+this.attributes['minlength'].value+' characters long');
			return false;
		}
		if(validity.tooLong){
			this.notify_invalid('Too long, must no more than '+this.attributes['maxlength'].value+' characters long');
			return false;
		}
		if(validity.patternMismatch){
			this.notify_invalid('Invalid');
			return false;
		}
		if(validity.valueMissing){
			this.notify_invalid('Required');
			return false;
		}
		if(validity.typeMismatch && this.attributes['type'].value=='email'){
			this.notify_invalid('Not a valid email address');
			return false;
		}
		if(validity.typeMismatch && this.attributes['type'].value=='url'){
			this.notify_invalid('Not a valid URL');
			return false;
		}
		if(attr=this.attributes.getNamedItem('must-match')){
			if(this.value!==$('#'+attr.value).val()){
				this.notify_invalid('Does not match');
				return false;
			}
		}
		if(attr=this.attributes.getNamedItem('script-validator')){
			var script=attr.value;
			var func=new Function(script);
			var message=func.apply(this);
			if(message!==null){
				this.notify_invalid(message);
				return false;
			}
		}
		if(attr=this.attributes.getNamedItem('ajax-validator')){
			if(this.ajax_validator_request && this.ajax_validator_request.state()=='pending')
				this.ajax_validator_request.fail();
			if(do_ajax){
				var url=attr.value;
				var input=this;
				function success(response){
					input.ajax_validator_response=response;
					if(response.valid)
						input.notify_valid();
					else
						input.notify_invalid(response.message);
				}
				success.input_element=this;
				this.ajax_validator_request=$.post_json({
					url:url,
					data:this.value,
					success:success,
					dataType:'json',
				});
				//we return false but we don't change anything about its state otherwise until we get a result
				return false;
			}
		}
		
		this.notify_valid();
		return true;
	}
});

$('input[minlength],input[maxlength],input[pattern],input[type="email"],input[type="url"],input[must-match],input[script-validator],input[ajax-validator]').on("cut change keyup paste",function(){
	if(this.validation_prev_value!=this.value){
		this.validation_prev_value=this.value;
		this.validate();
	}
});
			
$('form input[type="submit"]').click(function(event){
	var form_validated=true;
	$(this.form).find('input').each(function(){
		if(this.validate){
			//we don't do ajax validates here because they'll be done on the submit anyways no point in hitting the server twice and delaying the submit
			if(!this.validate(false)){
				this.classList.add('modified');
				form_validated=false;
			}
		}
	});
	if(!form_validated)
		event.preventDefault();
});

$('form').each(function(){
	this.noValidate=true;
	this.disable=function(){
		$(this).find('input, textarea').prop("disabled",true);
	};
	this.enable=function(){
		$(this).find('input, textarea').prop("disabled",false);
	};
	this.restore_default_values=function(){
		$(this).find('input, textarea').each(function(){
			if(this.defaultValue!=undefined)
				this.value=this.defaultValue;
		});
	}
});

jQuery.fn.extend({
	enable: function(){
		return this.each(function(){
			if(this.enable)
				this.enable();
		});
	},
	disable: function() {
		return this.each(function(){
			if(this.disable)
				return this.disable();
		});
	},
	restore_default_values: function() {
		return this.each(function(){
			if(this.restore_default_values)
				return this.restore_default_values();
		});
	},
});
  
$('form').submit(function(event){
	event.preventDefault();
	//ya this project posts everything as json
	var data={};
	$(this).find('input[name], textarea').each(function(){
		var name=this.name;
		if(name.match(/[a-z_][a-z_0-9]*/i))
			data[name]=this.value;
	});
	this.disable();
	
	var form=this;
	var redirect=this.attributes['redirect'];
	if(redirect)
		redirect=redirect.value;
	var request=$.post_json({
		url:this.action,
		data: data,
		dataType: 'json',
		success: function(response){
			if(response.success){
				if(redirect)
					window.location.href=redirect;
				else
					$(form).trigger('success',response);
			}else{
				for(name in response.errors){
					if(form.elements[name])
						form.elements[name].notify_invalid(response.errors[name]);
					else
						error_display(name,response.errors[name]);
				}
			}
		},
	});
	return false;
});
