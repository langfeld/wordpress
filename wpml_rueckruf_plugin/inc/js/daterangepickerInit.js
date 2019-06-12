/* https://longbill.github.io/jquery-date-range-picker */

//var blockedDates = ['28.04.2019', '17.05.2019'];

jQuery(function () {

    // Check for IE and stop if detected (because IE sx)
    // IE shows an error: Object doesn’t support property or method
    var ua = window.navigator.userAgent;
    var isIE = /MSIE|Trident/.test(ua);
    if (isIE) {
      
        // IE detected... no sweets for you!
        jQuery('.datepickerinputfields').show();     

    }
    else {

        // Hide date input fields because we use a good browser and can display a sweet date select calendar
        jQuery('.datepickerinputfields').hide();

        jQuery('#datepicker').dateRangePicker({

            format: 'DD.MM.YYYY',
            language: 'de',
            separator: ' bis ',
            startOfWeek: 'monday',

            inline:true,
            container: '#blocklist_kalender_container',
            alwaysOpen:true,

            // Zwei verschiedene Eingabefelder nutzen
            getValue: function() {
                if (jQuery('#datepicker').val() && jQuery('#datepickerEnd').val() )
                    return jQuery('#datepicker').val() + ' bis ' + jQuery('#datepickerEnd').val();
                else
                    return '';
            },
            setValue: function(s,s1,s2) {
                jQuery('#datepicker').val( moment(s1, 'DD.MM.YYYY').format('YYYY-MM-DD') );
                jQuery('#datepickerEnd').val( moment(s2, 'DD.MM.YYYY').format('YYYY-MM-DD') );
            },

            // Bereits blockierte Tage mit einer Klasse versehen
            beforeShowDay: function (input) {

                // Befindet sich das Datum bereits in der Blockierliste?
                var formattedInput = moment(input).format('DD.MM.YYYY');
                if(blockedDates.indexOf(formattedInput) !== -1) {
                    return [true, 'already-picked', 'Dieser Tag wurde für Rückrufwünsche gesperrt.'];
                }

                return [true, '', ''];
            },

            /*showDateFilter: function(time, date) {
                return '<div style="padding:10px"><span style="font-weight:bold">'+date+'</span></div>';
            }*/
        })
        .bind('datepicker-first-date-selected', function(event, obj) {
            // Speichern Button deaktivieren (bis die Range-Auswahl abgeschlossen wurde)
            jQuery('.blocklist_kalender_save_button').attr('disabled', true);
        })
        .bind('datepicker-change',function(event,obj) {
            // Speichern Button reaktivieren
            jQuery('.blocklist_kalender_save_button').attr('disabled', false);
        });

    }

});