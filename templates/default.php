<?php 

require_once ("templates/gauges.php");

/**
 * default template
 */

function renderFrame (){

global $trackpoint;
global $imageHeight;
global $imageWidth;
global $imagePath;

echo '<?xml version="1.0" standalone="no"?>'."\n";
?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN"
  "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg width="1280" height="720"  version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
  <desc>test</desc>
<defs>
  <style type="text/css">
   <![CDATA[
    @font-face {
        font-family: Delicious;
        src: url('http://www.css3.info/wp-content/uploads/2008/06/delicious-roman.otf');
    }
   ]]>
 </style>
</defs>

  <image x="0" y="0" width="<?= $imageWidth?>" height="<?= $imageHeight?>" xlink:href="<?= $imagePath?>" />

  <path fill="black" fill-opacity=".5" stroke-width="1" d="M 360 730 A 350,180 0 1 1 920 730"/>

  <g transform="translate (<?= $imageWidth*.3?>,<?= $imageHeight-90?>) scale(80,80)">
  <?php makeGauge (180+45,-45,-1,0,140,array(0,20,40,60,80,100,120,140),10,$trackpoint->cadenceInterpolated,"%03.0f","RPM"); ?>
  </g>

  <g transform="translate (<?= $imageWidth*.5?>,<?= $imageHeight-110?>) scale(150,150)">
  <?php makeGauge (180+45,-45,-1,0,50,array(0,10,20,30,40,50),10,$trackpoint->speedInterpolated*3.6,"%02.0f","Km/h"); ?>
  </g>

  <g transform="translate (<?= $imageWidth*.7?>,<?= $imageHeight-90?>) scale(80,80)">
  <?php makeGauge (180+45,-45,-1,60,180,array(60,70,80,90,100,110,120,130,140,150,160,170,180),5,$trackpoint->heartRateBpmInterpolated,"%03.0f","BPM"); ?>
  </g>
</svg>
<?php }?>