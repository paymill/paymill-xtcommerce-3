var isCcSubmitted = false;
$(document).ready(function () {
    if (typeof $.fn.prop !== 'function') {
        $.fn.prop = function(name, value) {
            if (typeof value === 'undefined') {
                return this.attr(name);
            } else {
                return this.attr(name, value);
            }
        };
    }

    $('#card-owner-field').html('<input type="text" value="' + paymill_cc_holder_val + '" id="paymill-card-owner" class="form-row-paymill" />');
    $('#card-number-field').html('<input type="text" value="' + paymill_cc_number_val + '" id="paymill-card-number" class="form-row-paymill" />');
    $('#card-expiry-month-field').html('<select id="paymill-card-expiry-month"></select>');
    $('#card-expiry-year-field').html('<select id="paymill-card-expiry-year"></select>');
    $('#card-cvc-field').html('<input type="text" value="' + paymill_cc_cvc_val + '" id="paymill-card-cvc" class="form-row-paymill" size="5" maxlength="4" />');

    for ( var cc_month_counter in paymill_cc_months ) {
        var cc_month_value = paymill_cc_months[cc_month_counter][0];
        var cc_month_text = paymill_cc_months[cc_month_counter][1];

        $('<option/>').val(cc_month_value).text(cc_month_text).appendTo($('#paymill-card-expiry-month'));
    };

    for ( var cc_year_counter in paymill_cc_years ) {
        var cc_year_value = paymill_cc_years[cc_year_counter][0];
        var cc_year_text = paymill_cc_years[cc_year_counter][1];

        $('<option/>').val(cc_year_value).text(cc_year_text).appendTo($('#paymill-card-expiry-year'));
    };
	
	$('#paymill-card-expiry-month').val(paymill_cc_expiry_month_val);
	$('#paymill-card-expiry-year').val(paymill_cc_expiry_year_val);
	
	$('#paymill-card-number').keyup(function() {
		switch (paymill.cardType($('#paymill-card-number').val()).toLowerCase()) {
			case 'visa':
				$('.card-icon').html('<img src="ext/modules/payment/paymill/public/images/32x20_visa.png" >');
				$('.card-icon').show();
				break;
			case 'mastercard':
				$('.card-icon').html('<img src="ext/modules/payment/paymill/public/images/32x20_mastercard.png" >');
				$('.card-icon').show();
				break;
			case 'american express':
				$('.card-icon').html('<img src="ext/modules/payment/paymill/public/images/32x20_amex.png" >');
				$('.card-icon').show();
				break;
			case 'jcb':
				$('.card-icon').html('<img src="ext/modules/payment/paymill/public/images/32x20_jcb.png" >');
				$('.card-icon').show();
				break;
			case 'maestro':
				$('.card-icon').html('<img src="ext/modules/payment/paymill/public/images/32x20_maestro.png" >');
				$('.card-icon').show();
				break;
			case 'diners club':
				$('.card-icon').html('<img src="ext/modules/payment/paymill/public/images/32x20_dinersclub.png" >');
				$('.card-icon').show();
				break;
			case 'discover':
				$('.card-icon').html('<img src="ext/modules/payment/paymill/public/images/32x20_discover.png" >');
				$('.card-icon').show();
				break;
			case 'unionpay':
				$('.card-icon').html('<img src="ext/modules/payment/paymill/public/images/32x20_unionpay.png" >');
				$('.card-icon').show();
				break;
			case 'unknown':
			default:
				$('.card-icon').hide();
				break;
		}

		$('.card-icon :first-child').css('position', 'absolute');
	});
	
	if (brand !== '') {
		$('.card-icon').html('<img src="ext/modules/payment/paymill/public/images/32x20_' + brand + '.png" >');
		$('.card-icon').show();
		$('.card-icon :first-child').css('position', 'absolute');
	}

    $('form[name="checkout_confirmation"]').submit(function () {
		if (!isCcSubmitted) {
			if (!paymill_cc_fastcheckout) {
				if (!paymill.validateExpiry($("#paymill-card-expiry-month option:selected").val(), $("#paymill-card-expiry-year option:selected").val())) {
					alert(cc_expiery_invalid);
					return false;
				}

				if (!paymill.validateCardNumber($("#paymill-card-number").val())) {
					alert(cc_card_number_invalid);
					return false;
				}

				if ($("#paymill-card-owner").val() === '') {
					alert(cc_owner_invalid);
					return false;
				}
				
				if (!paymill.validateCvc($("#paymill-card-cvc").val())) {
					alert(cc_cvc_number_invalid);
					return false;
				}

				paymill.createToken({
					number: $("#paymill-card-number").val(),
					exp_month: $("#paymill-card-expiry-month option:selected").val(), 
					exp_year: $("#paymill-card-expiry-year option:selected").val(), 
					cvc: $("#paymill-card-cvc").val(),
					amount_int: paymill_total,
					currency: paymill_currency,
					cardholder: $("#paymill-card-owner").val()
				}, PaymillCcResponseHandler);

				return false; 
			} else {
				$('#paymill_form').html('<input type="hidden" name="paymill_token" value="dummyToken" />').submit();
			}
		}
    });
	
	$('#paymill-card-number').focus(function() {
        paymill_cc_fastcheckout = false;
        $('#paymill-card-number').val('');
    });
    
    $('#paymill-card-expiry-month').focus(function() {
        paymill_cc_fastcheckout = false;
    });
    
    $('#paymill-card-expiry-year').focus(function() {
        paymill_cc_fastcheckout = false;
    });
    
    $('#paymill-card-cvc').focus(function() {
        paymill_cc_fastcheckout = false;
        $('#paymill-card-cvc').val('');
    });
    
    $('#paymill-card-owner').focus(function() {
        paymill_cc_fastcheckout = false;
        $('#paymill-card-owner').val('');
    });

    function PaymillCcResponseHandler(error, result) 
    { 
		isCcSubmitted = true;
        if (error) {
            isCcSubmitted = false;
			alert("An API error occured!");
            console.log("An API error occured: " + error.apierror);
            return false;
        } else {
            $('#paymill_form').html('<input type="hidden" name="paymill_token" value="' + result.token + '" />');
			$('#paymill_form').submit();
        }
    }
});
