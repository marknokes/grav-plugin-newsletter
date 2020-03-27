$(document).ready(function() {
    $('head').append('<link href="/user/plugins/newsletter/admin/css/newsletter.css" type="text/css" rel="stylesheet" />');
    var $loadElement =$("#admin-page-load"),
        ajaxName = $loadElement.data("ajax-name"),
        onload = $loadElement.data("onload"),
        ajaxURL = $loadElement.data("ajax-url");
    $.ajax({
        type: 'post',
        dataType: 'json',
        url: ajaxURL,
        data:{[ajaxName]: '_plugin_newsletter_' + onload},
        success: function (response) {
            if(response.count || response.count === 0) {
                count = response.count;
            } else {
                count = 'error retrieving count';
            }
            $("span#subscriber-count").text(count);
        },
        error: function(response) {
            console.log('error', response);
        }
    });
    $(".submit-update").click(function(e){
        e.preventDefault();
        data = {[ajaxName]: '_plugin_newsletter_' + $(this).data("action")};
        $.ajax({
            type: 'post',
            dataType: 'json',
            url: ajaxURL,
            data: data,
            success:function(response) {
                if(response.success) {
                    window.location.reload(false);
                } else if(response.error) {
                    alert("There was an error updating the list.");
                }
            },
            error: function(response) {
                console.log(response);
            }
        });
    });
    $(".submit-email").click(function(e){
        e.preventDefault();
        var conf = confirm("The list will be auto updated before send. Sure you're ready?"),
            $form = $("#email-subscribers"),
            email_body = $("textarea#body").val(),
            email_subject = $("input#email_subject").val(),
            email_greeting = $("input#email_greeting").val();
        if (!conf) {
          return;
        }
        if(!email_body || !email_subject || !email_greeting) {
            alert("Please complete all fields.");
            return;
        }
        data = {
            [ajaxName]       : '_plugin_newsletter_' + $(this).data("action"),
            'email_body'    :email_body,
            'email_subject' :email_subject,
            'email_greeting' :email_greeting
        };
        $.ajax({
            type: 'post',
            dataType: 'json',
            url: ajaxURL,
            data: data,
            success:function(response) {
                if(response.success) {
                    alert("Message sent successfully!");
                    window.location.reload(false);
                } else if(response.error) {
                    alert("There were one or more errors. Check the log for details.");
                }
            },
            error: function(response) {
                console.log('error', response);
            }
        });
    });
});