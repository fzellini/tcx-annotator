<?php
/**
 * make a gauge
 * $startDegree     e.g. 270
 * $endDegree       e.g. 0
 * $direction       1 or -1
 * $min,$max        e.g. 0,60
 * $majorTicks[]    e.g  [0,10,20,30,40,50,60]
 * $minorTicks      e.g 5
 *
 */
function makeGauge ($startDegree,$endDegree,$direction,$min,$max,$majorTicks,$minorTicks,$invalue,$format,$label,$firma=false){

  $majorL=.75;
  $minorL=.9;
  $majorT=.015;
  $minorT=.01;
  $fontL =.65;

  echo "<g transform=\"scale (1,-1)\" id=\"gauge\">\n";


  //echo "<circle cx=\"0\" cy=\"0\" r=\"1\" stroke=\"yellow\" stroke-width=\".01\" fill=\"none\" />";

  // initial arc
  $xArcStart = cos(deg2rad($startDegree));
  $yArcStart = sin(deg2rad($startDegree));
  $xArcEnd = cos(deg2rad($endDegree));
  $yArcEnd = sin(deg2rad($endDegree));

  echo "<path  fill=\"none\" stroke=\"yellow\" stroke-width=\"${majorT}\" d=\"M ${xArcStart} ${yArcStart} A 1 1 0 1 0 ${xArcEnd} ${yArcEnd}\"/>\n";


  $nTicks = count ($majorTicks);
  $stepDeg = ($startDegree-$endDegree) / ($nTicks-1) * $direction;
  $stepDegMinor = $stepDeg/$minorTicks;
  $degCnt = $startDegree;

  $mi=0;
  foreach ($majorTicks as $majorTick){
    $xTickS =  cos(deg2rad($degCnt));
    $yTickS =  sin(deg2rad($degCnt));
    $xTickE = $xTickS*$majorL;
    $yTickE = $yTickS*$majorL;
    $xTickF = $xTickS*$fontL;
    $yTickF = $yTickS*$fontL;

    
    
    echo "<path  stroke=\"yellow\" stroke-width=\"${majorT}\" d=\"M ${xTickS} ${yTickS} L ${xTickE} ${yTickE}\"/>\n";

    echo "<g transform=\"translate(${xTickF},${yTickF})\">\n";

    echo "<text dy=\".4em\" text-anchor=\"middle\" transform=\"scale (-1,1) rotate (180)\" x=\"0\" y=\"0\" font-family=\"Verdana\" font-size=\"0.1\" fill=\"yellow\" >${majorTick}</text>\n";
    echo ("</g>\n");


    if ($mi<$nTicks-1) {
    for ($i=1;$i<$minorTicks; $i++){
      $xTickS =   cos(deg2rad($degCnt+$i*$stepDegMinor));
      $yTickS =   sin(deg2rad($degCnt+$i*$stepDegMinor));
      $xTickE = $xTickS*$minorL;
      $yTickE = $yTickS*$minorL;
      echo "<path  stroke=\"yellow\" stroke-width=\"${majorT}\" d=\"M ${xTickS} ${yTickS} L ${xTickE} ${yTickE}\"/>\n";
    }}

    $mi++;
    $degCnt+=$stepDeg;
  }
  // compute rotation based on value
  $value = $invalue-$min;

  $rotateDeg = $startDegree + ($startDegree-$endDegree) / ($max-$min)  * $value*$direction;
  
  echo "<path transform=\"rotate (${rotateDeg} 0 0)\" fill=\"yellow\" stroke=\"yellow\" stroke-width=\"${majorT}\" d=\"M 0 -0.02 L 0 .02 L ${majorL} 0 Z\"/>\n";

//  $spd= sprintf ("%02.0f",$invalue);
  $spd= sprintf ($format,$invalue);
// speed
    echo "<text text-anchor=\"middle\" transform=\"scale (-1,1) rotate (180)\" x=\"0\" y=\"0.4\" font-family=\"Verdana\" font-size=\"0.2\" fill=\"yellow\" >${spd}</text>\n";

    echo "<text text-anchor=\"middle\" transform=\"scale (-1,1) rotate (180)\" x=\"0\" y=\"-0.3\" font-family=\"Verdana\" font-size=\"0.2\" fill=\"yellow\" >${label}</text>\n";

    if ($firma){
      echo "<text text-anchor=\"middle\" transform=\"scale (-1,1) rotate (180)\" x=\"0\" y=\"0.65\" font-family=\"Verdana\" font-size=\"0.14\" fill=\"yellow\" >$firma</text>\n";
    }

  echo "</g>\n";

}

/**
 * make an aircraft altitude panel
 * @param $altitude
 */
function makeAltitude ($altitude){

  $step=10;
  $span=$step*6;

  $scale=4.7;


  $offset = $altitude - (int)($altitude / $step) * $step;

  $span2ll = (int)($span*.6);
  $span2hh = (int)($span*.6);

  $span2ls = $span2ll*$scale;
  $span2hs = $span2hh*$scale;

  $height = $span2hs+$span2hs+20;
  $starty = -$span2hs-10;

  echo "<rect x=\"-5\" y=\"$starty\" width=\"55\" height=\"$height\"  stroke-width=\"0\" stroke=\"black\" fill=\"black\" fill-opacity=\".5\"/>";

  for ($a=-$span;$a<$span;$a+=2){

    $y=$a-$offset;
    if ($y > $span2hh || $y<-$span2ll) continue;

    $y*=$scale;
    $y=-$y;
    $x2=10;
    $stw=.2;
    if ($a%$step==0){
      $v=sprintf ("%02.0d",$a+(int)($altitude/$step)*$step);
      echo "<text  dy=\".3em\" x=\"18\" y=\"$y\" font-family=\"Verdana\" font-size=\"9\" fill=\"yellow\" >$v</text>\n";
      $x2=15;
      $stw=.5;
    }
    $stw*=$scale;
    echo "<line x1=\"0\" y1=\"$y\" x2=\"$x2\" y2=\"$y\"  stroke=\"yellow\" stroke-width=\"$stw\" />";

  }
  echo "<line x1=\"0\" y1=\"-$span2hs\" x2=\"0\" y2=\"$span2ls\"  stroke=\"yellow\" stroke-width=\"1\" />";

  $y=0;
  echo '<path fill="black" stroke="yellow" stroke-width="1" d="M 0 0 L 10 0 l 5 -8 l 30 0 l 0 16 l -30 0 l -5 -8" />'."\n";

  $v=sprintf ("%02.1f",$altitude);
  echo "<text  dy=\".35em\" x=\"15\" y=\"0\" font-family=\"Verdana\" font-size=\"12\" fill=\"yellow\" >$v</text>\n";


}

?>
