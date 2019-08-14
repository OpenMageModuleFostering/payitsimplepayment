var numOfInstallmentsResponse = 0;
var isLogedIn = 0;
var isLoging = 0;
var curUrl      = window.location.href; 
var baseUrl = "";
jQuery(document).ready(function(){

	
	
	var samePayment = "";
	
	baseUrl = jQuery("#payment-img").attr("data-baseurl");
	if(typeof baseUrl === 'undefined'){
		baseUrl = curUrl.substring(0, curUrl.indexOf('checkout'));
	}


	jQuery(document).on("click", "#payment-schedule-link", function(){
		jQuery("#approval-popup").addClass("overflowHidden");
		jQuery('#payment-schedule, ._popup_overlay').show();
	});
	jQuery(document).on("click", "#complete-payment-schedule-close", function(){
		jQuery("#approval-popup").removeClass("overflowHidden");
		jQuery('#payment-schedule, ._popup_overlay').hide();	
	});

	jQuery(document).on("click", "#i_acknowledge_content_show", function(){
		jQuery("#approval-popup").addClass("overflowHidden");
		jQuery('#termAndConditionpopup, ._popup_overlay').show();		
	});

	jQuery(document).on("click", "#termAndConditionpopupCloseBtn", function(){
		jQuery("#approval-popup").removeClass("overflowHidden");
		jQuery('#termAndConditionpopup, ._popup_overlay').hide();	
	});


	// get number of installment when splitit payment gateway already selected
    if (jQuery('dt#dt_method_pis_cc input#p_method_pis_cc').is(':checked')) {
    	getNumOfInstallments();
    }

    // hide I acknowdge
    jQuery(document).on("click","#i_acknowledge",function(){
    	if(jQuery('#i_acknowledge').is(":checked")){
	    	jQuery(".i_ack_err").hide();
	    }else{
	    	jQuery(".i_ack_err").show();
	    }
    });
    

	// get number of installment on click of splitit payment gateway	
	/*jQuery(document).on("click","dt#dt_method_pis_cc input#p_method_pis_cc", function() {
		alert("sdfsfdf");
		getNumOfInstallments();
    	
    });*/

    jQuery(document).on('click','#checkout-payment-method-load input[type="radio"]',function(e){
    	if(jQuery(this).attr("id") == 'p_method_pis_cc'){
    		//jQuery("#payment-buttons-container button").attr("onclick","");
    		getNumOfInstallments();
    	}
    	else{
    		//jQuery("#payment-buttons-container button").attr("onclick","payment.save();");
    	}
    })
	var isAlreadyClickInFormFields = 0;
	jQuery(document).on("click","#payment_form_pis_cc li", function() {
		

		if(isAlreadyClickInFormFields == 0){
			//jQuery("#payment-buttons-container button").attr("onclick","");
			getNumOfInstallments();	
		}
		
		
    	
    });


    
    /*jQuery("#payment_form_pis_cc li").on("click",function(){
    	alert("sdfsfd");
    });*/

	function getNumOfInstallments(){
		var selectedInstallment = jQuery("#pis_cc_installments_no").val();
		jQuery("body").find(".pis-login-loader").show();
		jQuery("body").find(".terms-condition-loader").hide();
		jQuery.ajax({
	        url : baseUrl+"payitsimple/payment/apiLogin/",
	        type : 'POST',
	        dataType:'json',
	        data:{},
	        success : function(obj){	        	
	        	
	            if (obj.status == true) {
	               /*var html = '<option value="" class="number-of-installments">--Please Select--</option>';
	               jQuery.each( obj.data, function( index, value ){
					    html +=  '<option value="'+index+'">'+value+'</option>';
					});
	               jQuery("#pis_cc_installments_no").html(html);*/
	               /*if(obj.installmentNum == 0){
	               		jQuery("#pis_cc_installments_no option:nth-child(2)").attr("value", "");
	               }*/
	               numOfInstallmentsResponse = 1;
	               isAlreadyClickInFormFields = 1;
	               isLogedIn = 1;
	            }else {
	            	isLogedIn = 0;
	            	alert(obj.error);
	             //showAjaxMessage(test.message,"danger");
	            }
	            //samePayment = jQuery("#payment-buttons-container button").attr("onclick");
	            //jQuery("#payment-buttons-container button").attr("onclick","");
	            jQuery("body").find(".pis-login-loader").hide();

	        },
	        //async:false
	    });
	}
    

});
// close splitit popup when user check I agree
function paymentSave(){
    if(jQuery('#i_acknowledge').is(":checked")){
    	jQuery(".approval-popup_ovelay").hide();
    	// check term checkbox which is hidden
    	jQuery(".terms-conditions div").remove();
		jQuery('#pis_cc_terms').prop('checked', true);
		jQuery('#one-step-checkout-review-terms-agreement-mc_osc_term').prop('checked', true);
		jQuery("#approval-popup").hide();	
    }else{
    	jQuery(".i_ack_err").show();
    }	
	

}

// close Approval popup
function closeApprovalPopup(){
	jQuery("#approval-popup, .approval-popup_ovelay").hide();
}

// on click Approve Terms and Conditions button
function installmentPlanInit(){
	jQuery("body").find(".terms-condition-loader").css('display', 'inline-block');// show loader of Approve Terms and Conditions button
	// check if splitit login or not
	if(numOfInstallmentsResponse == 0 /*||  checkNewDropDown == 0*/){
		getNumOfInstallments();
	}
	var selectedInstallment = jQuery("#pis_cc_installments_no").val();
	if(selectedInstallment == ""){
		jQuery("body").find(".terms-condition-loader").hide();
		alert("Please select Number of Installments");
		return;
	}
	var selectedText = jQuery("#pis_cc_installments_no option:selected").text();
	var instNotAvail = "Installments are not available.";
	var supportLessThan100 = "Splitit only support amount more than 100";
	if(selectedText === instNotAvail){
		jQuery("body").find(".terms-condition-loader").hide();
		alert(instNotAvail);
		return;	
	}
	if(selectedText === supportLessThan100){
		jQuery("body").find(".terms-condition-loader").hide();
		alert(supportLessThan100);
		return;		
	}
	// validation for empty fields in splitit form
	var selectedCc = jQuery("#pis_cc_cc_type").val();
	if(selectedCc == ""){
		jQuery("body").find(".terms-condition-loader").hide();
		alert("Please select credit card type.");
		return;
	}
	var ccNum = jQuery("#pis_cc_cc_number").val();
	if(ccNum == ""){
		jQuery("body").find(".terms-condition-loader").hide();
		alert("Please input Credit card number.");
		return;
	}
	var ccExp = jQuery("#pis_cc_expiration").val();
	if(ccExp == ""){
		jQuery("body").find(".terms-condition-loader").hide();
		alert("Please select Month.");
		return;
	}
	var ccYear = jQuery("#pis_cc_expiration_yr").val();
	if(ccYear == ""){
		jQuery("body").find(".terms-condition-loader").hide();
		alert("Please select Year.");
		return;
	}
	var ccCVV = jQuery("#pis_cc_cc_cid").val();
	if(ccCVV == ""){
		jQuery("body").find(".terms-condition-loader").hide();
		alert("Please input card verification number.");
		return;
	}

	// uncheck term checkbox which is hidden
	jQuery('#pis_cc_terms').prop('checked', false);
	if(isLogedIn){
		jQuery.ajax({
	        url : baseUrl+"payitsimple/payment/installmentplaninit/",
	        type : 'POST',
	        dataType:'json',
	        data:{"selectedInstallment":selectedInstallment},
	        success : function(obj){	        	
	        	
	            if (obj.status == true) {
	            	jQuery("#approval-popup").remove();
	            	jQuery('body').append(obj.data);

	            }else {
	            	alert(obj.data);
	             
	            }
	            jQuery("body").find(".terms-condition-loader").hide();
	            

	        }
	    });
	}else{
		jQuery("body").find(".terms-condition-loader").hide();
	}
}



