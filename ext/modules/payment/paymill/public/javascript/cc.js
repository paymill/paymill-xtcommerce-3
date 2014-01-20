var isCcSubmitted = false;
$(document).ready(function() {
	if (typeof $.fn.prop !== 'function') {
		$.fn.prop = function(name, value) {
			if (typeof value === 'undefined') {
				return this.attr(name);
			} else {
				return this.attr(name, value);
			}
		};
	}

    PaymillCreateCCForm();
    PaymillAddCardDetection();

	$('#checkout_confirmation').submit(function(event) {
		event.preventDefault();
            if (!isCcSubmitted) {
                if (!paymill_cc_fastcheckout) {
                    hideErrorBoxes();
                    var ccErrorFlag = true;

                    if (!paymill.validateExpiry($("#paymill-card-expiry-month option:selected").val(), $("#paymill-card-expiry-year option:selected").val())) {
                        $("#card-expiry-error").text(cc_expiery_invalid);
                        $("#card-expiry-error").css('display', 'block');
                        ccErrorFlag = false;
                    }

                    if (!paymill.validateCardNumber($("#paymill-card-number").val())) {
                        $("#card-number-error").text(cc_card_number_invalid);
                        $("#card-number-error").css('display', 'block');
                        ccErrorFlag = false;
                    }

                    if (!paymill.validateHolder($("#paymill-card-owner").val())) {
                        $("#card-owner-error").text(cc_owner_invalid);
                        $("#card-owner-error").css('display', 'block');
                        ccErrorFlag = false;
                    }

                    if (!paymill.validateCvc($("#paymill-card-cvc").val()) && paymill.cardType($("#paymill-card-number").val()).toLowerCase() !== 'maestro') {
                        $("#card-cvc-error").text(cc_cvc_number_invalid);
                        $("#card-cvc-error").css('display', 'block');
                        ccErrorFlag = false;
                    }

                    if (!ccErrorFlag) {
                        return ccErrorFlag;
                    }

                    var cvc = '000';

                    if ($("#paymill-card-cvc").val() !== '') {
                        cvc = $("#paymill-card-cvc").val();
                    }

                    paymill.createToken({
                        number:     $("#paymill-card-number").val(),
                        exp_month:  $("#paymill-card-expiry-month option:selected").val(),
                        exp_year:   $("#paymill-card-expiry-year option:selected").val(),
                        cvc:        cvc,
                        amount_int: paymill_total,
                        currency:   paymill_currency,
                        cardholder: $("#paymill-card-owner").val()
                    }, PaymillCcResponseHandler);

                    return false;
                } else {
                    $('#paymill_form').append('<input type="hidden" name="paymill_token" value="dummyToken" />');
                    $('#paymill_form').submit();
                }
            }
        });

    PaymillAddCCFormFokusActions();
});

function PaymillCreateCCForm()
{
    $('#card-owner-field').html('<input type="text" value="' + paymill_cc_holder_val + '" id="paymill-card-owner" class="form-row-paymill" />');
    $('#card-number-field').html('<input type="text" value="' + paymill_cc_number_val + '" id="paymill-card-number" class="form-row-paymill" />');
    $('#card-expiry-month-field').html('<select id="paymill-card-expiry-month"></select>');
    $('#card-expiry-year-field').html('<select id="paymill-card-expiry-year"></select>');
    $('#card-cvc-field').html('<input type="text" value="' + paymill_cc_cvc_val + '" id="paymill-card-cvc" class="form-row-paymill" size="5" maxlength="4" />');

    for ( var cc_month_counter in paymill_cc_months ) {
        var cc_month_value = paymill_cc_months[cc_month_counter][0];
        var cc_month_text = paymill_cc_months[cc_month_counter][1];

        $('<option/>').val(cc_month_value).text(replaceUmlauts(cc_month_text)).appendTo($('#paymill-card-expiry-month'));
    };

    for ( var cc_year_counter in paymill_cc_years ) {
        var cc_year_value = paymill_cc_years[cc_year_counter][0];
        var cc_year_text = paymill_cc_years[cc_year_counter][1];

        $('<option/>').val(cc_year_value).text(cc_year_text).appendTo($('#paymill-card-expiry-year'));
    };

    $('#paymill-card-expiry-month option').eq(paymill_cc_expiry_month_val-1).prop('selected', true);
    $('#paymill-card-expiry-year').val(paymill_cc_expiry_year_val);
    var cssClass = "paymill-card-number-";
    console.log(paymill_cc_card_type);
    switch (paymill_cc_card_type) {
        case 'unknown':
            $('#paymill-card-number').removeClass();
            $('#paymill-card-number').addClass('form-row-paymill');
            break;
        case 'carte bleue':
            $('#paymill-card-number').removeClass();
            $('#paymill-card-number').addClass('form-row-paymill ' + cssClass + 'carte-bleue');
            break;
        case 'china_union_pay':
            $('#paymill-card-number').removeClass();
            $('#paymill-card-number').addClass('form-row-paymill ' + cssClass + 'unionpay');
            break;
        case 'diners':
        case 'dankort':
        case 'carta-si':
        case 'maestro':
        case 'discover':
        case 'jcb':
        case 'amex':
        case 'mastercard':
        case 'visa':
            $('#paymill-card-number').removeClass();
            $('#paymill-card-number').addClass('form-row-paymill ' + cssClass + brand);
            break;
    }
}

function PaymillAddCardDetection()
{
    var cssClass = "paymill-card-number-";

    $('#paymill-card-number').keyup(function() {
        var brand = detectCreditcardBranding($('#paymill-card-number').val()).toLowerCase();
        console.log("Brand detected: " + brand);
        switch (brand) {
            case 'unknown':
                $('#paymill-card-number').removeClass();
                $('#paymill-card-number').addClass('form-row-paymill');
                break;
            case 'carte bleue':
                $('#paymill-card-number').removeClass();
                $('#paymill-card-number').addClass('form-row-paymill ' + cssClass + 'carte-bleue');
                break;
            case 'china unionpay':
                $('#paymill-card-number').removeClass();
                $('#paymill-card-number').addClass('form-row-paymill ' + cssClass + 'unionpay');
                break;
            case 'diners club':
                $('#paymill-card-number').removeClass();
                $('#paymill-card-number').addClass('form-row-paymill ' + cssClass + 'diners');
                break;
            case 'dankort':
            case 'carta-si':
            case 'maestro':
            case 'discover':
            case 'jcb':
            case 'amex':
            case 'mastercard':
            case 'visa':
                $('#paymill-card-number').removeClass();
                $('#paymill-card-number').addClass('form-row-paymill ' + cssClass + brand);
                break;
        }
    });
}

function PaymillAddCCFormFokusActions()
{
    $('#paymill-card-number').focus(function() {
        paymill_cc_fastcheckout = false;
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
    });
}

function hideErrorBoxes()
{
    $("#card-cvc-error").css('display', 'none');
    $("#card-owner-error").css('display', 'none');
    $("#card-number-error").css('display', 'none');
    $("#card-expiry-error").css('display', 'none');
}

function PaymillCcResponseHandler(error, result)
{
    isCcSubmitted = true;
    if (error) {
        isCcSubmitted = false;
        console.log(error);
        window.location = $("<div/>").html(checkout_payment_link + error.apierror).text();
        return false;
    } else {
        $('#paymill_form').html('<input type="hidden" name="paymill_token" value="' + result.token + '" />');
        $('#paymill_form').submit();
    }
}

function detectCreditcardBranding(creditcardNumber) {
    var brand = 'unknown';
    if (creditcardNumber.match(/^\d{6}/)) {
        switch (true) {
            case /^(415006|497|407497|513)/.test(creditcardNumber):
                brand = "carte bleue";
                break;
            case /^(45399[78]|432913|5255)/.test(creditcardNumber):
                brand = "carta si";
                break;
            case /^(4571|5019)/.test(creditcardNumber):
                brand = "dankort";
                break;
            case /^(62|88)/.test(creditcardNumber):
                brand = "china unionpay";
                break;
            case /^6(011|5)/.test(creditcardNumber):
                brand = "discover";
                break;
            case /^3(0[0-5]|[68])/.test(creditcardNumber):
                brand = "diners club";
                break;
            case /^(5018|5020|5038|5893|6304|6759|6761|6762|6763|0604|6390)/.test(creditcardNumber):
                brand = "maestro";
                break;
            case /^(2131|1800|35)/.test(creditcardNumber):
                brand = "jcb";
                break;
            case /^(3[47])/.test(creditcardNumber):
                brand = "amex";
                break;
            case /^(5[1-5])/.test(creditcardNumber):
                brand = "mastercard";
                break;
            case /^(4)/.test(creditcardNumber):
                brand = "visa";
                break;
        }
    }
    return brand;
}

function replaceUmlauts(string){

    string = string.replace(/&Auml;/,'\u00c4');
    string = string.replace(/&auml;/,'\u00e4');
    string = string.replace(/&Ouml;/,'\u00d6');
    string = string.replace(/&ouml;/,'\u00f6');
    string = string.replace(/&Uuml;/,'\u00dc');
    string = string.replace(/&uuml;/,'\u00fc');
    string = string.replace(/Ä;/,'\u00c4');
    string = string.replace(/ä;/,'\u00e4');
    string = string.replace(/Ö/,'\u00d6');
    string = string.replace(/ö/,'\u00f6');
    string = string.replace(/Ü/,'\u00dc');
    string = string.replace(/ü/,'\u00fc');

    return string;
}