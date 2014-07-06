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

$time = date("H:i:s",$trackpoint->time);
$date = date("d-m-Y",$trackpoint->time);

echo '<?xml version="1.0" standalone="no"?>'."\n";
?>
<!DOCTYPE svg PUBLIC "-//W3C//DTD SVG 1.1//EN"
  "http://www.w3.org/Graphics/SVG/1.1/DTD/svg11.dtd">
<svg width="1280" height="720"  version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
  <desc>test</desc>
<defs>
  <!--
  <style type="text/css">
   <![CDATA[
    @font-face {
        font-family: Delicious;
        src: url('http://www.css3.info/wp-content/uploads/2008/06/delicious-roman.otf');
    }
   ]]>
 </style>-->
</defs>

  <image x="0" y="0" width="<?= $imageWidth?>" height="<?= $imageHeight?>" xlink:href="<?= $imagePath?>" />
 <!--
  <rect x="0" y="0" width="<?= $imageWidth?>" height="32" fill-opacity=".5" fill="black" stroke="black" stroke-width="0" />
  <text  y="20" x="<?= (int)($imageWidth/2) ?>" style="font-family: 'Delicious'; font-weight:normal; font-style: normal;text-anchor:middle" font-size="20" fill="yellow" >via Guido Baccelli</text>-->

  <path fill="black" fill-opacity=".5" stroke-width="1" d="M 340 730 A 360,190 0 1 1 940 730"/>

  <g transform="translate (<?= $imageWidth*.3?>,<?= $imageHeight-92?>) scale(90,90)">
  <?php makeGauge (180+45,-45,-1,0,120,array(0,20,40,60,80,100,120),10,$trackpoint->cadenceInterpolated,"%03.0f","RPM"); ?>
  </g>

  <g transform="translate (<?= $imageWidth*.5?>,<?= $imageHeight-115?>) scale(150,150)">
  <?php makeGauge (180+45,-45,-1,0,50,array(0,10,20,30,40,50),10,$trackpoint->speedInterpolated*3.6,"%02.1f","Km/h","WILIER"); ?>
  </g>

  <g transform="translate (<?= $imageWidth*.7?>,<?= $imageHeight-92?>) scale(90,90)">
  <?php makeGauge (180+45,-45,-1,60,180,array(60,70,80,90,100,110,120,130,140,150,160,170,180),5,$trackpoint->heartRateBpmInterpolated,"%03.0f","BPM"); ?>
  </g>

  <g transform="translate(1180,360) scale (2,2)">
  <?php makeAltitude($trackpoint->altitudeMetersInterpolated)?>
  </g>
  <text  y="500" x="780" style="font-family: 'monospace'; font-weight:normal; font-style: normal;" font-size="18" fill="yellow" ><?=$time?></text>
  <text  y="500" x="410" style="font-family: 'monospace'; font-weight:normal; font-style: normal;" font-size="18" fill="yellow" ><?=$date?></text>
</svg>
<?php }?>
