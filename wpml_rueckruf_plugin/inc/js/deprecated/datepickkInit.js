jQuery(function () {
    
    /* http://crsten.github.io/datepickk/ */

    /* Initialize */
    var datepicker = new Datepickk();

    /* Set container*/
    datepicker.container = document.querySelector('#blocklist_kalender_container');

    /* Set inline*/
    datepicker.inline = true;

    /* Set lang */
    datepicker.lang = 'de';

    /* Set maxSelections */
    datepicker.months = 2;

    /* Set range */
    datepicker.range = true;

    /* Show now */
    datepicker.show();

    //Type: Function
    datepicker.onSelect = function(checked){
        /*Get selectedDates*/
        console.log(datepicker.selectedDates);
    };


});