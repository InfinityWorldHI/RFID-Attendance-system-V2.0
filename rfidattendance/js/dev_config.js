$(document).ready(function(){

	//Add Device 
	$(document).on('click', '#dev_add', function(){

		var dev_name = $('#dev_name').val();
		var dev_dep = $('#dev_dep').val();

		$.ajax({
		  url: 'dev_config.php',
		  type: 'POST',
		  data: {
		    'dev_add': 1,
		    'dev_name': dev_name,
		    'dev_dep': dev_dep,
		  },
		  success: function(response){
		    $('#dev_name').val('');
		    $('#dev_dep').val('');

		    if (response == 1) {			    
		    	$('.alert_dev').fadeIn(500);
			    $('.alert_dev').html('<p class="alert alert-success">A new device has been added successfully</p>');
		        $('#new-device').modal('hide');
		        setTimeout(function () {
			        $('.alert_dev').fadeOut(500);
			        
			    }, 2000);
		    }
		    else {
	    		$('.alert_dev').fadeIn(500);
		    	$('.alert_dev').html(response);

		    	setTimeout(function () {
			        $('.alert_dev').fadeOut(500);
			    }, 2000);
		    }

		    $.ajax({
		      url: "dev_up.php",
		      type: 'POST',
		      data: {
		          'dev_up': 1,
		      }
		      }).done(function(data) {
		      $('#devices').html(data);
		    });
		  }
		});
	});

	//Device Token update
	$(document).on('click', '.dev_uid_up', function(){

		var el = this;
		var dev_id = $(this).data('id');

		bootbox.confirm("Do you really want to Update this Device Token?", function(result) {
			if(result){
			     // AJAX Request
			     $.ajax({
			       url: 'dev_config.php',
			       type: 'POST',
			       data: { 
			          'dev_uid_up': 1,
			          'dev_id_up': dev_id,
			       },
			       success: function(response){

			        $(el).closest('tr').css('background','#5cb85c');
			        $(el).closest('tr').fadeOut(300,function(){
			              $(this).show();
			        });
			        if(response == 1){			    
				    	$('.alert_dev').fadeIn(500);
			        	$('.alert_dev').html('<p class="alert alert-success">The device Token has been updated successfully</p>');

				        setTimeout(function () {
					        $('.alert_dev').fadeOut(500);
					        
					    }, 2000);
			            $.ajax({
			              	url: "dev_up.php",
			              	type: 'POST',
			              	data: {
			                  'dev_up': 1,
			              	}
			              	}).done(function(data) {
			              	$('#devices').html(data);
			            });
			        }
				    else {
			    		$('.alert_dev').fadeIn(500);
				    	$('.alert_dev').html(response);

				    	setTimeout(function () {
					        $('.alert_dev').fadeOut(500);
					    }, 2000);
				    }
			       }
			     });
		   	}
		});
	});

	//Delete Device
	$(document).on('click', '.dev_del', function(){

		var el = this;
		var deleteid = $(this).data('id');

		bootbox.confirm("Do you really want to delete this Device?", function(result) {
		if(result){
		     // AJAX Request
		     $.ajax({
		       url: 'dev_config.php',
		       type: 'POST',
		       data: { 
		          'dev_del': 1,
		          'dev_sel': deleteid,
		       },
		       success: function(response){

		         // Removing row from HTML Table
		          if(response == 1){
		              $(el).closest('tr').css('background','#d9534f');
		              $(el).closest('tr').fadeOut(800,function(){
		                $(this).remove();
		              });

		              $.ajax({
	                    url: "dev_up.php",
	                    type: 'POST',
	                    data: {
	                        'dev_up': 1,
	                    }
	                    }).done(function(data) {
	                    $('#devices').html(data);
	                  });
		          }
		          else{
		            $('.alert_dev_del').fadeIn(500);
		            $('.alert_dev_del').html(response);

		            setTimeout(function () {
		                $('.alert_dev_del').fadeOut(500);
		            }, 2000);
		              bootbox.alert('Device not deleted.');
		          }
		       }
		     });
		   }
		});
	});

	//Device Mode
	$(document).on('click', '.mode_sel', function(){

		var el = this;
    	var dev_mode = $(this).attr("value");
		var dev_id = $(this).data('id');

		bootbox.confirm("Do you really want to change this Device Mode?", function(result) {
			if(result){
			     // AJAX Request
			     $.ajax({
			       url: 'dev_config.php',
			       type: 'POST',
			       data: { 
			          'dev_mode_set': 1,
			          'dev_mode': dev_mode,
			          'dev_id': dev_id,
			       },
			       success: function(response){
			       		
			          	if(response == 1){
			          		// bootbox.alert('<p class="alert alert-success">The device Mode has been updated successfully</p>');
			              	$(el).closest('tr').css('background','#5cb85c');
					        $(el).closest('tr').fadeOut(300,function(){
					              $(this).show();
					        });

			              	$.ajax({
								url: "dev_up.php",
		                    	type: 'POST',
		                    	data: {
		                        'dev_up': 1,
		                    	}
		                    }).done(function(data) {
		                    	$('#devices').html(data);
		                  	});
			          	}
			          	else{
				            $('.alert_dev_del').fadeIn(500);
				            $('.alert_dev_del').html(response);

				            setTimeout(function () {
				                $('.alert_dev_del').fadeOut(500);
				            }, 2000);
				              bootbox.alert('Device not changed.');
		         	 	}
			       	}
			     });
		   	}
		   	else{
			   	$.ajax({
		            url: "dev_up.php",
		            type: 'POST',
		            data: {
		                'dev_up': 1,
		            }
		            }).done(function(data) {
		            $('#devices').html(data);
	          	});
		   	}
		});
	});
});