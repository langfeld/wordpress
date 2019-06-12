/* 
 * VideoJS Player init
 */

/*var options, player;
options = {
   controlBar: {
      children: [
         'playToggle',
         'volumePanel',
         'progressControl',
         'remainingTimeDisplay',
         'qualitySelector',
         'fullscreenToggle'
      ]
   }
};*/

// Optionen auf jeden VideoJS Player anwenden
$(function(){
  $('video').each(function(){    
      /*player = videojs($(this)[0], options);*/
      videojs($(this)[0]).controlBar.addChild('QualitySelector');
  });
});
