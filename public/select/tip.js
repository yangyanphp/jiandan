
$(function () {
    
        $('.tip-container').click(function () {
        	var checkValue=$("#block_list").val();
        	var checkText=$("#block_list").find("option:selected").text();
        	var flag = true;
        	
        	$.ajax({
	             type: "POST",
	             url: "/index.php?g=admin&m=tixian&a=checkBlack",
	             data: {mid:checkValue},
	             async:false,
	             dataType: "text",
	             success: function(data){
	                   if(data==2){
	                	   parent.layer.msg('该用户已在黑名单当中', {icon: 2});
	                	   flag = false;
	                	    
	                   }     
	             }
	         });		  
				  
			if(flag){
				
				var tipcon = '<div class="tip-input">' +
                '<input type="hidden" class="house-tip" placeholder="'+checkText+'" name="black_name[]" value="'+checkValue+'">' +
                '<span>'+checkText+'</span>' +
                '<span class="del"></span>' +
                '</div>';
	            $('.tip').prepend(tipcon);
	            
	            // 删除表单
			}
			
			$('.del').click(function () {
	        	
	            $(this).parent().remove();
	        });
        });

        
        
    });
