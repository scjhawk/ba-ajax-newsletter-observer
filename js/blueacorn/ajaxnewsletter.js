var AjaxSubscribe = Class.create({
    initialize: function(url) {
        this.thisForm    = newsletterSubscriberFormDetail;
        this.emailAddr   = '';
        this.response    = '';
        this.msgTemplate = new Template("<ul class='messages'><li class='#{status}-msg' id='nl_message_container'><ul><li id='ajax_message'>#{message}</li></ul></li></ul>");

        // Assign the class as a variable so that the observer can call the class's methods.
        var parent = this;

        this.thisForm.form.observe('submit', function(ev) {

            var validated = parent.validate(ev);

            if (validated) {
                Event.stop(ev);
                parent.emailAddr = parent.thisForm.form[0].value;
                parent.subscribe();
            }
        });
    },

    validate: function(ev){
        return this.thisForm.validator.validate();
    },

    subscribe: function() {
        var parent = this;
        console.log('Sending request...');
        this.thisForm.form.request({
            method:     'POST',
            parameters: {email: this.emailAddr, 'is-ajax': 1},
            onSuccess:  function (transport) {
                console.log('Request success!');
                parent.response = JSON.parse(transport.responseText);
                console.log(parent.response);
                $('nl_message_container').show();
                parent.addResponse();
                parent.thisForm.form.reset();
            }
        });
    },

    addResponse: function() {
        // Insert the returned message in the <li> tag created on page load, and use the class associated with the status (error or success)
        var mainArea = $$("div.col-main")[0];
        var subscribeResponse = this.msgTemplate.evaluate({'status' : this.response.status, 'message' : this.response.message});
        mainArea.insert({top: subscribeResponse});
        console.log("Response success with templates!");

    }
});

// Create the variable 'ajaxsubscribe' which will be set as an instance of the AjaxSubscribe class
var ajaxsubscribe;