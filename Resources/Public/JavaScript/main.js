$(document).ready(function() {

    initSearch();

});

function initSearch() {

    $(document.body).on('submit', '.neos-search.ajax form', function(event) {

        var form = this;

        if (event.originalEvent !== undefined) {

            event.preventDefault(this);

            getSearchResults($(this).serializeArray(), form);

        }

    });

    $(document.body).on('keyup', '.neos-search.ajax form .search-param', function() {

        var length = $(this).val().length;
        if (length > 2) {

            var form = $(this).closest('form');
            getSearchResults($(form).serializeArray(), form);

        }

    });

    $(document.body).on('click', '.neos-search.ajax .search-results .submit-search', function(event) {

        event.preventDefault();

        $(this).closest('.neos-search.ajax').find('.submit-form').val('1');
        $(this).closest('.neos-search.ajax').find('form').submit();

    });

}

var ajaxSearch = $.ajax();

function getSearchResults(data, form) {

    $(form).next('.search-results').html('<li class="text-center"><i class="fa fa-spinner fa-pulse"></i></li>');

    var searchParameter = '';
    var currentPath = '';
    var submitForm = 0;
    $.each(data, function(index, value) {

        if (value.name == 'currentNodePath') {

            currentPath = value.value;

        }

        if (value.name == 'searchParameter') {

            searchParameter = value.value;

        }

        if (value.name == 'submitForm') {

            submitForm = value.value;

        }

    });

    ajaxSearch.abort();

    ajaxSearch = $.ajax({
        url: '/ajax-neos-search',
        method: 'POST',
        contentType: 'application/json',
        data: JSON.stringify({searchParameter: searchParameter, currentNodePath: currentPath, submitForm: submitForm}),
        dataType: 'json'
    }).done(function(data) {

        $(form).next('.search-results').html('');

        var results = data.results;

        $.each(results, function(index, value) {

            var text = '';
            if (value.findString) {
                text = '<div class="text">'+value.findString+'</div>';
            }

            $(form).next('.search-results').append('<li><a href="'+value.uri+'?q='+searchParameter+'" title="'+value.title+'">'+value.title+'</a>'+text+'</li>');

        });

        $(form).next('.search-results').append('<li><a href="#" class="submit-search" title="Weitere Ergebnisse anzeigen">Weitere Ergebnisse anzeigen</a><div class="text">Über diesen Link kommen Sie zur Ergebnissseite</div></li>');

    });

}