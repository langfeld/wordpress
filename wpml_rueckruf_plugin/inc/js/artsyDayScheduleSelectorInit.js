jQuery.noConflict();
(function ($) {


    // Array mit den nächsten 30 Tagen erzeugen
    var today = new Date();
    var year = today.getFullYear();
    var month = today.getMonth();
    var date = today.getDate();
    var daysToShow = [];
    var daysToShowIDs = [];
    for (var i = 0; i < (dayShowCount + 1); i++) {

        // Datum hochzaehlen und formatiert in Array speichern
        var dateX = new Date(year, month, date + i);

        var dateXday = dateX.getDate();
        if (dateXday < 10) {
            dateXday = "0" + dateXday;
        }

        var dateXmonth = dateX.getMonth() + 1;
        if (dateXmonth < 10) {
            dateXmonth = "0" + dateXmonth;
        }

        daysToShow.push(dateXday + "." + dateXmonth);
    }
    for (var i = 0; i < (dayShowCount + 1); i++) {
        daysToShowIDs.push(i);
    }


    // Tabelle initialisieren
    jQuery("#day-schedule").dayScheduleSelector({
        days: daysToShowIDs,
        stringDays: daysToShow,
        interval: blockInterval,
        startTime: blockStartTime,
        endTime: blockEndTime
    });


    // Auswahl-Event
    jQuery("#day-schedule").on('selected.artsy.dayScheduleSelector', function (e, selected) {
        //window.console.log(selected);
    });


    // Vorausgewaehlte (blockierte) Felder
    jQuery("#day-schedule").data('artsy.dayScheduleSelector').deserialize( blockedTimes );
    
    
    // Bei Klick auf das Datum den ganzen Tag blockieren / freischalten
    jQuery("#day-schedule .schedule-header th").on('click', function(){
        var clickedDay = jQuery(this).text();
        jQuery("#day-schedule .time-slot[data-day='"+clickedDay+"']").each(function(){
            var ths = jQuery(this);
            
            // Nur auswaehlen, wenn es kein Wochenende ist
            if( !ths.hasClass('weekend') ) {
                jQuery(this).attr('data-selected', 'selected');
            }
            
        });
    });
    
    
    // Warnung bei leerem Themenbereich
    jQuery("#day-schedule .time-slot").on('click', function(){
        
        if(!jQuery('.arp_themenselectbox').val()) {
            var error_text = "<br>Der Themenbereich wurde nicht ausgewählt. Änderungen können nicht gespeichert werden.";
            jQuery('#arp_errorbox').slideDown('slow').find('span').html(error_text); /*.parent().delay(6000).slideUp();*/
        }
        return;
        
    });
    
    
    // Hover Effekt auch auf die vertikalen Spalten anwenden
    jQuery("#day-schedule .time-slot").mouseenter(function(){
        var classString = jQuery(this).attr('data-day');
        if(classString) { 
            var classStringB = classString.replace("\.","");
            jQuery("#day-schedule").find( "#day" + classStringB ).addClass('schedule-header-bg');
        }
    }).mouseleave(function(){
        var classString = jQuery(this).attr('data-day');
        if(classString) { 
            var classStringB = classString.replace("\.","");
            jQuery("#day-schedule").find( "#day" + classStringB ).removeClass('schedule-header-bg');
        }
    });
    
    
    
    // Bereits vergebene Uhrzeiten initial per Ajax einlesen
    $.ajax({
        url: ajaxurl,
        method: 'post',
        dataType: 'json',
        data: {
            'action':'rueckruf_ajax_blacklistget_vergeben',
            't' : $('.arp_themenselectbox').val()
        },
        success:function(data) {
            $("#day-schedule").data('artsy.dayScheduleSelector').masquerade(data); 
        }
    });
    

    
    // Blockliste bei Aenderung der Themen-Selectbox neu einlesen
    jQuery('body').on('change', '.arp_themenselectbox', function(){

        // Uebergebene Variable
         var themenauswahl = $(this).val();

         // Wurde etwas ausgewaehlt?
         if(themenauswahl.length > 0) {

            // Evtl. Fehler aufheben
            jQuery('#arp_errorbox').slideUp('fast');

            // Ajax-Anfrage absenden
            $.ajax({
                url: ajaxurl,
                method: 'post',
                dataType: 'json',
                data: {
                    'action':'rueckruf_ajax_blacklistget',
                    't' : themenauswahl
                },
                success:function(data) {

                   // Wurden Daten/Uhrzeiten zurueck gegeben?
                   if ( data.length == 0 ) {

                       // Auswahl leeren
                       $('.time-slot').removeAttr('data-selected');

                       // Vergebene Termine entfernen
                       $('.time-slot').removeClass('masquerade');

                       // Read-Only entfernen
                       $(".time-slot").removeClass('readonly');

                       // Bereits vergebene Uhrzeiten per Ajax einlesen
                       $.ajax({
                           url: ajaxurl,
                           method: 'post',
                           dataType: 'json',
                           data: {
                               'action':'rueckruf_ajax_blacklistget_vergeben',
                               't' : themenauswahl
                           },
                           success:function(data) {
                               $("#day-schedule").data('artsy.dayScheduleSelector').masquerade(data); 
                           }
                       });

                   } 
                   else {
                       // Auswahl leeren
                       $('.time-slot').removeAttr('data-selected');

                       // Vergebene Termine entfernen
                       $('.time-slot').removeClass('masquerade');

                       // Auswahl mit DB Daten fuellen
                       $("#day-schedule").data('artsy.dayScheduleSelector').deserialize(data);  

                       // Read-Only entfernen
                       $(".time-slot").removeClass('readonly');

                       // Bereits vergebene Uhrzeiten per Ajax einlesen
                       $.ajax({
                           url: ajaxurl,
                           method: 'post',
                           dataType: 'json',
                           data: {
                               'action':'rueckruf_ajax_blacklistget_vergeben',
                               't' : themenauswahl
                           },
                           success:function(data) {
                               $("#day-schedule").data('artsy.dayScheduleSelector').masquerade(data); 
                           }
                       });
                   }
                },
                error: function(errorThrown){
                   window.console.log('Fehler');
                }
            }); 
         
        }
        else {
            
            // Read-Only aktivieren
            $(".time-slot").addClass('readonly');
            
        }

    });


})(jQuery);