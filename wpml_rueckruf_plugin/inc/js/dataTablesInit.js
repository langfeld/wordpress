jQuery.noConflict();
(function($) {
           
     
    /* Hilfsfunktion fuer die Formatierung der ausklappbaren Details */
    function fnFormatDetails ( oTable, nTr ) {
        var aData = oTable.fnGetData( nTr );
        var sOut = '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">';
        sOut += '<tr><td class="mehr_class">Arbeitgeber:</td><td>'+ aData[6] +'</td></tr>';
        sOut += '<tr><td class="mehr_class">E-Mail:</td><td>'+ aData[7] +'</td></tr>';
        sOut += '<tr><td class="mehr_class">Kalk.Nr.:</td><td>'+ aData[8] +'</td></tr>';
        sOut += '<tr><td class="mehr_class">Anliegen:</td><td>'+ aData[9] +'</td></tr>';
        sOut += '<tr><td class="mehr_class mehr_separator">Bearbeitet am:</td><td class="mehr_separator">'+ aData[10] +'</td></tr>';
        sOut += '<tr><td class="mehr_class">Bearbeitet von:</td><td>'+ aData[11] +'</td></tr>';
        sOut += '</table>';
        return sOut;
    }


    /* Details Spalte einfuegen */
    var nCloneTh = document.createElement( 'th' );
    var nCloneTd = document.createElement( 'td' );
    nCloneTd.innerHTML = '<span class="mehr_button">+</span>';
    nCloneTd.className = "center";
    jQuery('#rueckruf_uebersicht thead tr').each( function () {
        this.insertBefore( nCloneTh, this.childNodes[0] );
    });
    jQuery('#rueckruf_uebersicht tbody tr').each( function () {
        this.insertBefore(  nCloneTd.cloneNode( true ), this.childNodes[0] );
    });


    /* Tabelle initialisieren, Spalten ausblenden (deren Nummer im fnFormatDetails benoetigt werden) */
    var oTable = jQuery('#rueckruf_uebersicht').dataTable( {
        "aoColumnDefs": [
            { "bSortable": false, "aTargets": [ 0 ] },
            { "targets": zeilen_verstecken, "visible": false }
        ],
        "aaSorting": [[1, 'desc']],
        "iDisplayLength": 25,

        "language": {
            "sEmptyTable":      "Keine Daten in der Tabelle vorhanden",
            "sInfo":            "_START_ bis _END_ von _TOTAL_ Einträgen",
            "sInfoEmpty":       "0 bis 0 von 0 Einträgen",
            "sInfoFiltered":    "(gefiltert von _MAX_ Einträgen)",
            "sInfoPostFix":     "",
            "sInfoThousands":   ".",
            "sLengthMenu":      "_MENU_ Einträge anzeigen",
            "sLoadingRecords":  "Wird geladen...",
            "sProcessing":      "Bitte warten...",
            "sSearch":          "Suchen",
            "sZeroRecords":     "Keine Einträge vorhanden.",
            "oPaginate": {
                "sFirst":       "Erste",
                "sPrevious":    "Zurück",
                "sNext":        "Nächste",
                "sLast":        "Letzte"
            },
            "oAria": {
                "sSortAscending":  ": aktivieren, um Spalte aufsteigend zu sortieren",
                "sSortDescending": ": aktivieren, um Spalte absteigend zu sortieren"
            }
        }
    });


    /* Event Listener fuer das Oeffnen und Schliessen einbinden */
    jQuery('#rueckruf_uebersicht tbody td span').on('click', function () {
        var nTr = jQuery(this).parents('tr')[0];

        if ( jQuery(this).attr("isOpen") === "true" ) {
            // This row is already open - close it
            jQuery(this).html('+');
            oTable.fnClose( nTr );
            jQuery(this).attr("isOpen","false");
        }
        else {
            // Open this row (if attr isOpen is not set, set it)
            jQuery(this).html('-');
            oTable.fnOpen( nTr, fnFormatDetails(oTable, nTr), 'details' );
            jQuery(this).attr("isOpen","true");
        }
    });
            
            
})(jQuery);