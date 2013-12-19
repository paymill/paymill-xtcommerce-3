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

    PaymillCreateElvForm(getSepaState());

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

                if(getSepaState()){
                    elvErrorFlag = PaymillValidateSepaForm();
                } else {
                    elvErrorFlag = PaymillValidateOldElvForm();
                }

                if (!elvErrorFlag) {
                        return elvErrorFlag;
                }

                PaymillCreateElvToken(getSepaState());

                return false;
            } else {
                    $('#paymill_form').html('<input type="hidden" name="paymill_token" value="dummyToken" />').submit();
            }
        }
    });

    PaymillAddElvFormFokusActions(getSepaState());
});

function PaymillValidateSepaForm()
{
    console.log("Starting Validation for SEPA form...");
    var elvErrorFlag = true;
    if(false === ($('#paymill-iban').val() != '')){
        $('#elv-iban-error').text(elv_iban_invalid);
        $('#elv-iban-error').css('display', 'block');
        elvErrorFlag = false;
    }

    if(false === ($('#paymill-bic').val() != '')){
        $('#elv-bic-error').text(elv_iban_invalid);
        $('#elv-bic-error').css('display', 'block');
        elvErrorFlag = false;
    }

    return elvErrorFlag;
}

function PaymillValidateOldElvForm()
{
    console.log("Starting Validation for old form...");
    var elvErrorFlag = true;
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

    return elvErrorFlag;
}

function PaymillAddElvFormFokusActions(SEPA)
{
    $('#paymill-bank-owner').focus(function() {
        paymill_elv_fastcheckout = false;
    });

    if(SEPA){
        $('#paymill-iban').keyup(function() {
            var iban = $('#paymill-iban').val();
            if (!iban.match(/^DE/)) {
                var newVal = "DE";
                if (iban.match(/^.{2}(.*)/)) {
                    newVal += iban.match(/^.{2}(.*)/)[1];
                }
                $('#paymill-iban').val(newVal);
            }
        });

        $('#paymill-iban').focus(function() {
            paymill_elv_fastcheckout = false;
        });

        $('#paymill-bic').focus(function() {
            paymill_elv_fastcheckout = false;
        });
    } else {
        $('#paymill-account-number').focus(function() {
            paymill_elv_fastcheckout = false;
        });

        $('#paymill-bank-code').focus(function() {
            paymill_elv_fastcheckout = false;
        });
    }
}

function PaymillCreateElvForm(SEPA)
{
    $('#account-name-field').html('<input type="text" value="' + paymill_elv_holder + '" id="paymill-bank-owner" class="form-row-paymill" />');
    if(SEPA){
        $('#iban-field').html('<input type="text" value="' + paymill_elv_iban + '" id="paymill-iban" class="form-row-paymill" />');
        $('#bic-field').html('<input type="text" value="' + paymill_elv_bic + '" id="paymill-bic" class="form-row-paymill" />');
    } else {
        $('#account-number-field').html('<input type="text" value="' + paymill_elv_account + '" id="paymill-account-number" class="form-row-paymill" />');
        $('#bank-code-field').html('<input type="text" value="' + paymill_elv_code + '" id="paymill-bank-code" class="form-row-paymill" />');
    }
}

function PaymillCreateElvToken(SEPA)
{
    if(SEPA){ //Sepa Form active
        paymill.createToken({
            iban: $('#paymill-iban').val(),
            bic: $('#paymill-bic').val(),
            accountholder: $('#paymill-bank-owner').val()
        }, PaymillElvResponseHandler);
    } else {
        paymill.createToken({
            number: $('#paymill-account-number').val(),
            bank: $('#paymill-bank-code').val(),
            accountholder: $('#paymill-bank-owner').val()
        }, PaymillElvResponseHandler);
    }
}

function getSepaState()
{
    return sepaActive == "True";
}

function hideErrorBoxes()
{
    $("#card-holder-error").css('display', 'none');

    if(getSepaState()){ //Sepa Form active
        $("#elv-iban-error").css('display', 'none');
        $("#elv-bic-error").css('display', 'none');
    } else {
        $("#elv-bankcode-error").css('display', 'none');
        $("#elv-account-error").css('display', 'none');
    }
}

function PaymillElvResponseHandler(error, result)
{
    isElvSubmitted = true;
    if (error) {
        isElvSubmitted = false;
        console.log(error);
        window.location = $("<div/>").html(checkout_payment_link + error.apierror).text();
    } else {
        $('#paymill_form').html('<input type="hidden" name="paymill_token" value="' + result.token + '" />').submit();
        return false;
    }
}
