var isElvSubmitted = false;
$(document).ready(function () {
    if (typeof $.fn.prop !== 'function') {
        $.fn.prop = function(name, value) {
            if (typeof value === 'undefined') {
                return this.attr(name);
            } else {
                return this.attr(name, value);
            }
        }
    }

    $('#account-name-field').html('<input type="text" value="' + paymill_elv_holder + '" id="paymill-bank-owner" class="form-row-paymill" />');
    $('#account-number-field').html('<input type="text" value="' + paymill_elv_account + '" id="paymill-account-number" class="form-row-paymill" />');
    $('#bank-code-field').html('<input type="text" value="' + paymill_elv_code + '" id="paymill-bank-code" class="form-row-paymill" />');

    $('#checkout_confirmation').submit(function (event) {
		event.preventDefault();
		if (!isElvSubmitted) {
			if (!paymill_elv_fastcheckout) {
				
				hideErrorBoxes();
				
				var elvErrorFlag = true;
				
				if (false === paymill.validateAccountNumber($('#paymill-account-number').val())) {
					$("#elv-account-error").text(elv_account_number_invalid);
					$("#elv-account-error").css('display', 'block');
					elvErrorFlag = false;
				}

				if (false === paymill.validateBankCode($('#paymill-bank-code').val())) {
					$("#elv-bankcode-error").text(elv_bank_code_invalid);
					$("#elv-bankcode-error").css('display', 'block');
					elvErrorFlag = false;
				}

				if ($('#paymill-bank-owner').val() === "") {
					$("#elv-holder-error").text(elv_bank_owner_invalid);
					$("#elv-holder-error").css('display', 'block');
					elvErrorFlag = false; 
				}
				
				if (!elvErrorFlag) {
					return elvErrorFlag;
				}

				paymill.createToken({
					number:        $('#paymill-account-number').val(),
					bank:          $('#paymill-bank-code').val(),
					accountholder: $('#paymill-bank-owner').val()
				}, PaymillElvResponseHandler);

				return false;
			} else {
				$('#paymill_form').html('<input type="hidden" name="paymill_token" value="dummyToken" />').submit();
			}
		}
    });
	
	$('#paymill-bank-owner').focus(function() {
        paymill_elv_fastcheckout = false;
        $('#paymill-bank-owner').val('');
    });
    
    $('#paymill-account-number').focus(function() {
		$('#paymill-account-number').val('');
        paymill_elv_fastcheckout = false;
    });
    
    $('#paymill-bank-code').focus(function() {
		$('#paymill-bank-code').val('');
        paymill_elv_fastcheckout = false;
    });
	
	function hideErrorBoxes()
	{
		$("#elv-bankcode-error").css('display', 'none');
		$("#elv-account-error").css('display', 'none');
		$("#card-holder-error").css('display', 'none');
	}

    function PaymillElvResponseHandler(error, result) 
    { 
		isElvSubmitted = true;
        if (error) {
			isElvSubmitted = false;
            window.location = checkout_payment_link;
        } else {
            $('#paymill_form').html('<input type="hidden" name="paymill_token" value="' + result.token + '" />').submit();
            return false;
        }
    }
});
