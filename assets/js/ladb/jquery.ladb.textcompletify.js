;(function ( $ ) {

    $.fn.ladbTextcompletify = function(options) {

        var settings = $.extend({
            maxCount: 5,
            mentionStrategy: {},
            mentionQueryPath: null
        }, options );

        this.textcomplete([
            {   // Mentions
                match: /\B@(\w*)$/,
                search: function (term, callback) {

                    var suggestions = [];
                    $.each(settings.mentionStrategy, function (username, data) {
                        if (username.indexOf(term) === 0 || (data.displayname !== null) && (data.displayname.toLowerCase().indexOf(term) === 0)) {
                            suggestions.push({ username: username, displayname: data.displayname, avatar: data.avatar });
                        }
                    });

                    if (term.length > 0 && settings.mentionQueryPath) {

                        $.getJSON(settings.mentionQueryPath, { q: term }, function (data) {
                            for (var i = data.suggestions.length - 1; i >= 0; i--) {
                                for (var j = suggestions.length - 1; j >= 0; j--) {
                                    if (suggestions[j].username === data.suggestions[i].username) {
                                        data.suggestions.splice(i, 1);
                                        break;
                                    }
                                }
                            }
                            callback(suggestions.concat(data.suggestions))
                        });
                    } else {
                        callback(suggestions)
                    }

               },
                template: function (suggestion) {
                    return '<img src="' + suggestion.avatar + '" class="img-rounded" width="32" height="32"> ' + suggestion.displayname + ' <span class="small ladb-translucent">(@' + suggestion.username + ')</span>';
                },
                replace: function (suggestion) {
                    return '@' + suggestion.username + ' ';
                },
                index: 1
            } ],{
            maxCount: settings.maxCount
        });

        return this;
    };

} ( jQuery ))