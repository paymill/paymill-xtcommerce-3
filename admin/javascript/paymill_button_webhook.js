$(document).ready(function() {
    $('#register_webhooks').submit(function( event ){
        event.preventDefault();
        $.post($('#listener').val());
    });

});