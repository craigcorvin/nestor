(function ($) {

  $.fn.tabletocard = function(options) {

    // Establish our default settings
    var settings = $.extend({
      ignore: [], // comma delineated list of cells to not display in card view
      responsive: false,
      displayTitle: true,
      columnsForTitle: [], // comma delineated list of cells to use as card title
      cardWidth: 299,
      cardHeight: 415,
      cardMarginTop: 5,
      cardMarginRight: 5,
      cardMarginBottom: 5,
      cardMarginLeft: 5
    }, options);

    this.each(function () {
      var eachTable = $(this);

      if (settings.ignore) {
        var ignore = {};
        $.each(settings.ignore, function (index, value) {
          ignore[value] = true;
        });
      }

      var forms = [];
      var allForms = [];
      var content = $('<div class="card-container">');

      // get thead 
      var thead = $(eachTable).find('thead');
      var headers = [];

      $(thead).find('th,td').each(function (thKey, thValue) {
        headers[thKey] = $(thValue).text();
      });

      // get tbody
      var tbody = $(eachTable).find('tbody');

      // body of each card
      $(tbody).find('tr').each(function (trkey, trvalue) {
        var cardElement = $('<div class="card">').css({
          width: +settings.cardWidth + 'px',
          minHeight: +settings.cardHeight + 'px',
          margin: +settings.cardMarginTop + 'px ' + +settings.cardMarginRight + 'px ' + +settings.cardMarginBottom + 'px ' + +settings.cardMarginLeft + 'px',
        });

        var cardContentElement = $('<div class="card-content">');

        var cardTitleElement = $('<div class="card-title">');

        content.append(cardElement);
        cardElement.append(cardContentElement);
        cardContentElement.append(cardTitleElement);

        var rowContent = [];

        $(this).find('td').each(function (tdKey, tdValue) {
          var tdForms = $(this).find('form');
          rowContent[tdKey] = $(tdValue).text()

          if (tdForms.length > 0) {
            forms[tdKey] = tdForms;
            allForms.push(tdForms);
          }
        });

        if (settings.columnsForTitle) {
          var title = '';

        	$.each(settings.columnsForTitle, function (index, value) {
            title += rowContent[value] + ' ';
          });
        }

        if (settings.displayTitle) {
          cardTitleElement.text(title);
        }

        $.each(headers, function (rowIndex, rowValue) {
          if (typeof forms[rowIndex] !== 'undefined') {
            cardContentElement.append(forms[rowIndex])
          } else if (typeof ignore[rowIndex] == 'undefined') {
            var rowText = rowValue + ": ";

            cardContentElement.append(
              $('<div class="header">')
                .attr('title', rowValue)
                .text(rowText)
                .append(
                  $('<div class="row-content">').text(rowContent[rowIndex])
                )
            )
          }
        });
      });

      $(eachTable).after(content);

      if (!settings.responsive) {
        $(eachTable).remove();
      } else {
        respond();

        $(window).bind('resize', function (event) {
          respond();
        });

        function respond() {
          if ($(window).width() < settings.responsive) {
            $('.card-container').show();
            $(eachTable).hide();

            if (allForms.length > 0) {
                $('div.card-content').each(function (cardKey, cardVal) {
                  $(cardVal).append(allForms[cardKey]);
                })
            }
          } else {
            $('.card-container').hide();
            $(eachTable).show();

            if (allForms.length > 0) {
              $(tbody).find('tr td:first-child').each(function (trKey, trVal) {
                $(trVal).append(allForms[trKey]);
              });
            }
          }
        }
      }
    });
  }
}(jQuery));