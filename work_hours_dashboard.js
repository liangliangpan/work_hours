var workHours_dashboard ={
	
	init: function(){
		var _this = this;
		//$("#update_plan_hours").bind('click',function(){
			;//_this.editHours('plan_hours');
		//});	

		_this.computeLefthours();
		_this.refresh_todo_hours();

	},

	computeLefthours:function(){

		
	},

	refresh_todo_hours:function(type)
	{
		var url = '?c=plugin&a=workhours_unfinished_hours';
		var params = {};
		$.post( url , params , function( data )
		{
			// add content to list
			var data_obj = $.parseJSON( data );
			$("#dashboard_workhours_info_area #unfinished_todo_num").text(data_obj.data.left_todos);
			$("#dashboard_workhours_info_area #unfinished_todo_hours").text(data_obj.data.left_hours);
			
			done();
		} );

		doing();	

	}

};
workHours_dashboard.init();




