var workHours ={
	
	init: function(){
		var _this = this;
		$("#update_plan_hours").bind('click',function(){
			_this.editHours('plan_hours');
		});	

		$("#update_left_hours").bind('click',function(){
			_this.editHours('left_hours');
		});	

		_this.bindEditNumber('plan_hours');
		$("#edit_plan_hours").bind('blur',function(){
			_this.updateHours('plan_hours',$("#edit_plan_hours").val());
		});

		_this.bindEditNumber('left_hours');
		$("#edit_left_hours").bind('blur',function(){
			_this.updateHours('left_hours',$("#edit_left_hours").val());
		});
		_this.updateProgressBar();


	},

	updateProgressBar:function(){
		var plan_hours = parseFloat($("#update_plan_hours").text());
		var left_hours = parseFloat($("#update_left_hours").text());
		if(plan_hours > 0){

			var percent = (1 - left_hours/plan_hours)*100;
			$("#workhours_progress").css('width', Math.round(percent)+"%");
			$("#workhours_progress").css('display','inline');
			$("#percent_num").text(Math.round(percent)+"%");
			$("#percent_num").css('display','inline');
		}else{
			$("#workhours_progress").css('display','none');
			$("#percent_num").css('display','none');
		}
		
	},

	bindEditNumber:function(hoursType){
		var _this = this;
		$("#edit_"+hoursType).bind('keyup',function(e){
			if(e.keyCode == 13){
				_this.updateHours(hoursType,$("#edit_"+hoursType).val());
			}else if(e.keyCode == 27){ //escape
				_this.displayHours(hoursType);
			}else if(e.keyCode < 48 || e.keyCode > 57){ // 0-9
				this.value=this.value.replace(/\D/g, ''); // only allow numbers
			}
		});
	},

	displayHours:function(hoursType){
		$("#update_"+hoursType).css('display','inline');
		$("#edit_"+hoursType).css('display','none');
	},

	editHours:function(hoursType){
		$("#update_"+hoursType).css('display','none');
		$("#edit_"+hoursType).css('display','inline');
		$("#edit_"+hoursType).val($("#update_"+hoursType).text());
		$("#edit_"+hoursType).select();
		$("#edit_"+hoursType).focus();
	},

	updateHours:function(hourType, hourVal){

		var tid = parseInt($('#workhours_container').attr('tid'));
		var url = '?c=plugin&a=workhours_update' ;
		var params = { 'tid' : tid  , 'target' : hourType, 'hours': hourVal};
		var _this = this;
		$.post( url , params , function( data )
		{
			var data_obj = $.parseJSON( data );
			 
			if( data_obj.err_code == 0 )
			{
				$("#update_"+hourType).text(hourVal);
				_this.displayHours(hourType);

				if( hourType == 'left_hours' &&  parseInt(hourVal) == 0)
				{
					if( confirm(__('JS_PL_WORK_HOURS_MARK_TODO_READ_CONFIRM')) )
					{
						mark_todo_done(tid);
					}
				}

				//update bar
				if(hourType == 'plan_hours'){
					$("#t-"+tid+" #dash_plan_bar").attr('plan_hours',hourVal);
				}else{
					$("#t-"+tid+" #dash_plan_bar").attr('left_hours',hourVal);
				}
				var width = 100*(parseFloat($("#t-"+tid+" #dash_plan_bar").attr('plan_hours'))/20);
				$("#t-"+tid+" #dash_plan_bar").css("width",width+"%");
				width = 100*(parseFloat($("#t-"+tid+" #dash_plan_bar").attr('plan_hours') - $("#t-"+tid+" #dash_plan_bar").attr('left_hours'))/20);
				$("#t-"+tid+" #dash_progress_bar").css("width",width+"%");
			}
			else
			{
				alert( __('JS_API_ERROR_INFO' , [ data_obj.err_code , data_obj.message ] ) );
			}
			_this.updateProgressBar();

			done();
			

		} );

		doing();

	}



};




