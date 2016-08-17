function cookieCnil(){
    if ($.cookie('cookieCnil') === undefined) {
        $('#cookieCnil').fadeIn();

        $(window).click(function () {
            $('#cookieCnil').fadeOut(300);
            $.cookie('cookieCnil', "viewed", {expires: 365});
        });

        $(window).scroll(function () {
            if ($(this).scrollTop() > 1) {
                $('#cookieCnil').fadeOut(300);
                $.cookie('cookieCnil', "viewed", {expires: 365});
            }
        });
    }
}

$(function () {
    cookieCnil(); // Cookie CNIL

    // your jQuery custom
});