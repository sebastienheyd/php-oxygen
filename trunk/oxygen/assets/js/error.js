$(document).ready(function() {
    $('.fragments').hide();
    $('#stack').hide();

    $('#showStack').click(function(event) {
       event.preventDefault();
       $('#stack').slideToggle();
    });

    $('.stackItem').click(function() {
       $(this).find('.fragments').toggle();
    });
});