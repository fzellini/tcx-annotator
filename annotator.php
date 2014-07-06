#!/usr/bin/env php
<?php

  $TEMPDIR = "/tmp/annotator";
  $IMAGENAME="image-%06d.jpg";

  $defaultTemplate="default";
  $defaultRasterizer="phantomjs";



  function alog ($message){
    echo $message."\n";
  }

  function customError($errno, $errstr,$errfile, $errline,$errcontext)  {
    echo "Error: errno [$errno]; errstr [$errstr]; errfile [$errfile]; errline [$errline]\n";
    die;
  }


  class Lap {
    public $rawStartTime;
    public $startTime;        // unixtime 
    public $totalTimeSeconds;
    public $distanceMeters;
    public $averageHeartRateBpm;
    public $maximumHeartRateBpm;
    public $calories;
    public $intensity;
    /**
     * Array of TrackPoint
     */
    public $trackPoints;

  }
  
  class TrackPoint {

    public $rawTime;
    public $time;             // unixtime 
    public $latitudeDegrees;
    public $longitudeDegrees;
    public $altitudeMeters;
    public $distanceMeters;
    public $heartRateBpm;
    public $cadence;
    public $speed;
    /**
     * lap reference ?
     */
    public $lap;

  }


  function parseTcxFile ($tcxFile){
// load tcx file in array of Telemetry data

    $s = file_get_contents ($tcxFile);
    $xml = new SimpleXmlElement($s);
    $xml->registerXPathNamespace('v2', 'http://www.garmin.com/xmlschemas/TrainingCenterDatabase/v2');

    $tcxLaps = $xml->xpath("//v2:Lap");
    $laps=Array();

    foreach ($tcxLaps as $tcxLap ){
      
      $tcxLap->registerXPathNamespace('v2', 'http://www.garmin.com/xmlschemas/TrainingCenterDatabase/v2');


      $lap = new Lap();
      $lap->trackPoints=Array();
      $lap->startTime = (string)$tcxLap["StartTime"];

      alog ("Lap StartTime [{$lap->startTime}]");

      $tcxTrackpoints = $tcxLap->xpath("//v2:Trackpoint");


      foreach ($tcxTrackpoints as $tcxTrackPoint){
        //print_r ($tcxTrackPoint);
        $trackpoint = new TrackPoint();
        $trackpoint->rawTime= (string)$tcxTrackPoint->Time;
        $trackpoint->time = strtotime($tcxTrackPoint->Time);

        $trackpoint->latitudeDegrees= (double) ($tcxTrackPoint->Position->LatitudeDegrees);
        $trackpoint->longitudeDegrees= (double)$tcxTrackPoint->Position->LongitudeDegrees;
        $trackpoint->altitudeMeters= (double)$tcxTrackPoint->AltitudeMeters;
        $trackpoint->distanceMeters= (double)$tcxTrackPoint->DistanceMeters;
        $trackpoint->heartRateBpm= (double)$tcxTrackPoint->HeartRateBpm->Value;
        $trackpoint->cadence = (double)$tcxTrackPoint->Cadence;
        $trackpoint->speed = (double)$tcxTrackPoint->Extensions->TPX->Speed;
        $trackpoint->lap = &$lap;
        $lap->trackPoints[]=$trackpoint;
        //print_r ($trackpoint); die;
      
      }
      $laps[]=$lap;
    }
    return $laps;

  }

  function usage (){
    global $argv;
    global $defaultTemplate;
    global $TEMPDIR;

    echo "usage: ".$argv[0]." [OPTION]\n";
    echo "  -i, --input-video=<video file>          set input video file\n";
    echo "  -g, --video-timing=<video start>@<video offset> set video start time @ offset\n";
    echo "  -t, --tcx=<tcx file>                    set tcx (telemetry) data file\n";
    echo "  -o, --output-video=<outfile>            set output video file\n";
    echo "      --ffmpeg-options=<ffmpeg options>   set ffmpeg options\n";
    echo "      --ffmpeg-infile-options=<ffmpeg infile options>   set ffmpeg infile options\n";
    echo "      --ffmpeg-outfile-options=<ffmpeg outfile options>   set ffmpeg outfile options\n";
    echo "  -r, --rasterizer=<rasterizer>           external svg to jpeg converter: magick | batik | phantomjs, default phantomjs\n";         
    echo "  -T, --template=<input template>         input template name (default ${defaultTemplate})\n";
    echo "      --list-templates                    list available templates and exit\n";
    echo "      --skip-split                        skip initial video split in frames\n";
    echo "      --skip-render                       skip frame rendering\n";
    echo "      --temp-dir=<tempdir location>       temp dir location (default $TEMPDIR )\n";
    echo "      --frames=<frames>                   process only <frames> frames (default all)\n";
    echo "  -h, --help  Print this help\n";
    echo "Ex. {$argv[0]} -i infile.mp4 --video-timing=2014-06-30T04:48:17Z@00:00:00 -t trip.tcx -o outfile.mkv\n";
    die;
  }

  set_error_handler("customError");

  $frames=false;
  $template = $defaultTemplate;
  $rasterizer = $defaultRasterizer;
  $templates=array();



  $skipSplit=false;
  $skipRender=false;
  $videoTimingsRaw=Array ();

  $ffmpegOptions="-y -loglevel warning";
  $ffmpegInFileOptions="-r 30";
  $ffmpegOutFileOptions="-vcodec libx264 -b:v 10000k -profile:v baseline -level 3.1";

  $videoInFile=false;
  $videoOutFile=false;
  $tcxFile=false;
  $jpegQuality = 90;


  $longopts  = array(
      "input-video:",     
      "video-timing:",  
      "tcx:",
      "output-video:",
      "ffmpeg-options:",
      "ffmpeg-infile-options:",
      "ffmpeg-outfile-options:",
      "rasterizer:",
      "template:",
      "list-templates",
      "skip-split",
      "skip-render",
      "quality:",
      "temp-dir:",
      "frames:",
      "help"         
  );
  $options = getopt("i:g:t:o:r:T:h", $longopts);
  foreach ($options as $key=>$value){
    switch ($key){
      case 'h':
      case 'help':
        usage();
      case 'r':
      case 'rasterizer':
        switch ($value){
          case 'batik':
          case 'magick':
          case 'phantomjs':
            $rasterizer = $value;
            break;
          default:
            usage();
        }
        $rasterizer = $value;        
        break;
      case 'q':
      case 'quality':
        $jpegQuality=(int) $value;
        break;
      case 'g':
      case 'video-timing':
        if (!preg_match ("/([^@]+)@([\d.]+):([\d.]+):([\d.]+)/",$value,$arr)){
          echo ("video offset bad format: it should be HH:MM:SS@<iso8601 datetime> ( ex. 2014-07-02T15:15:35Z@00:00:07)\n");
          die;
        }
        $videoTimingsRaw[]=$value;
        break;
      case 't':
      case 'tcx':
        $tcxFile=$value;
        break;
      case 'i':
      case 'input-video':
        $videoInFile = $value;
        break;
      case 'o':
      case 'output-video':
        $videoOutFile = $value;
        break;
      case 'ffmpeg-options':
        $ffmpegOptions= $value;
        break;
      case 'ffmpeg-infile-options':
        $ffmpegInFileOptions= $value;
        break;
      case 'ffmpeg-outfile-options':
        $ffmpegOutFileOptions= $value;
        break;
      case 'T':
      case 'template':
        $template=$value;
        break;
      case 'list-templates':
        echo "Available templates:\n";
        foreach ($templates as $template){
          echo "\t${template}\n";
        }
        die;
      case 'skip-split':
        $skipSplit=true;
        break;
      case 'skip-render':
        $skipRender=true;
        break;
      case 'temp-dir':
        $TEMPDIR=$value;
        break;
      case 'frames':
        $frames=(int)$value;
        break;

    }
  }

  if (!count($videoTimingsRaw) || ( !$videoInFile && $skipSplit ) || !$tcxFile ){
    echo "Some needed parameter missing\n";
    usage();
  }

  alog ("Input video file: [$videoInFile]");
  alog ("Video timing");
  print_r ($videoTimingsRaw);
  alog ("tcx file: [$tcxFile]");
  alog ("Output video file: [$videoOutFile]");
  alog ("Rasterizer:  [$rasterizer]");
  alog ("ffmpeg options:  [$ffmpegOptions]");
  alog ("ffmpeg infile options:  [$ffmpegInFileOptions]");
  alog ("ffmpeg outfile options:  [$ffmpegOutFileOptions]");
  alog ("TEMPDIR: [$TEMPDIR]");
  if ($frames){
    alog ("frames: [$frames]");
  }

  $SRCFRAMEDIR = $TEMPDIR."/src";
  $DSTFRAMEDIR = $TEMPDIR."/dst";

  if (file_exists($TEMPDIR) && is_file($TEMPDIR)){unlink($TEMPDIR);}

  if (!file_exists($TEMPDIR)){
    mkdir($TEMPDIR);
  }

  alog ("template: [$template]");

  require_once ("templates/${template}.php");

  // parse videoTimings
  $videoTimings=array();
  foreach ($videoTimingsRaw as $videoTimingRaw ){
    if (!preg_match ("/([^@]+)@([\d.]+):([\d.]+):([\d.]+)/",$videoTimingRaw,$arr)){
      alog ("video offset bad format: it should be HH:MM:SS@<iso8601 datetime>");exit (1);
    }
    //print_r ($arr);
    $offset = $arr[2]*3600+$arr[3]*60+$arr[4];
    //alog ("offset: [$offset]");
    $time = strtotime ($arr[1]);
    //alog ("time [$time]");
    $videoTimings[$offset]=(object)array ("offset"=>(double)$offset,"time"=>$time);
  }
  ksort ($videoTimings,SORT_NUMERIC);

  if (!isset ($videoTimings["0"])){
    $fel = reset($videoTimings);
    //alog ("Inserting start timing");
    $videoTimings["0"]=(object)array ("offset"=>0,"time"=>$fel->time-$fel->offset);
  }  
  krsort ($videoTimings,SORT_NUMERIC);
  //print_r ($videoTimings);

  $ffprobe = shell_exec ("ffprobe -v quiet -print_format json -show_format -show_streams ${videoInFile}");
  $videoData = json_decode ($ffprobe);

  // try to parse video data
  foreach ($videoData->streams as $stream ){
    if ($stream->codec_type=='video'){
      $imageWidth = $stream->width;
      $imageHeight = $stream->height;
      eval('$timeBase='.$stream->avg_frame_rate.";");
      $timeBase = 1/$timeBase;
    }
    //print_r ($stream);
  }
  alog ("imageWidth  [$imageWidth]");
  alog ("imageHeight [$imageHeight]");
  alog ("timeBase    [$timeBase]");

  // convert video in single frames
  if ($frames){
    $cmd="ffmpeg -loglevel warning -y  -i ${videoInFile} -q:v 0 -frames ${frames} \"${SRCFRAMEDIR}/${IMAGENAME}\"";
  }else{
    $cmd="ffmpeg -loglevel warning -y -i ${videoInFile} -q:v 0 \"${SRCFRAMEDIR}/${IMAGENAME}\"";
  }

  if (!$skipSplit){
    alog ("Splitting video in frames [${cmd}]... please wait");
    system("rm -rf ${SRCFRAMEDIR}");
    mkdir($SRCFRAMEDIR);
    system ($cmd);
    alog ("Done, frames are in [${SRCFRAMEDIR}]");
  }

  alog ("parsing tcx ...");
  $laps = parseTcxFile ($tcxFile);
  $trackPoints=array();

  foreach ($laps as $lap) {
    foreach ($lap->trackPoints as $trackpoint){
      $trackPoints[$trackpoint->time] = $trackpoint;      
    }
  }
  ksort ($trackPoints, SORT_NUMERIC);
  //print_r ($trackPoints);die;

  alog ("done.");
  // loop images

  $i=1;  
  $iTrack=0;
  $synced=false;
  $oldOffset=false;
  $ib=1;

  if (!$skipRender){
    system("rm -rf ${DSTFRAMEDIR}");
    mkdir($DSTFRAMEDIR);
  }
  while ( !$skipRender ){
    $srcframe = sprintf ("${SRCFRAMEDIR}/${IMAGENAME}",$i);
    //alog ($srcframe);
    if (!file_exists($srcframe)){
      $srcframe1 = sprintf ("${SRCFRAMEDIR}/${IMAGENAME}",$i+1);
      if (!file_exists($srcframe1)){
        break;
      } else {
        $srcframe = sprintf ("${SRCFRAMEDIR}/${IMAGENAME}",$i-1);
      }
    }

    // dato framenr calcolare tempo del frame
    $frameTime = ($i-1)*$timeBase;
    

    foreach ($videoTimings as $videoTiming){
      if ($frameTime>=$videoTiming->offset){
        break;
      }      
    }
    if ($oldOffset===false || $oldOffset != $videoTiming->offset){
      $synced=false;
      alog ("Sync requested");
    }
    $oldOffset= $videoTiming->offset;

    $absFrameTime=$frameTime-$videoTiming->offset;
    $absFrameTime+=$videoTiming->time;
    alog ("frameTime: [$frameTime], absFrameTime: [$absFrameTime], timing [{$videoTiming->offset}]");

    // now get closest telemetry record

    if (!$synced){
      // sync
      $trackpoint = reset ($trackPoints);
      while ($trackpoint && ($trackpoint->time < $absFrameTime)) {
        $trackpoint=next ($trackPoints);        
      }     
      // good ?
      if (abs ($trackpoint->time-$absFrameTime) < 1){
        alog ("Good, synced {$trackpoint->time}");
        $synced=true;
        // linear interpolation step
        $nexttp = next ($trackPoints);
        if ($nexttp){
          $speedStep = ($nexttp->speed-$trackpoint->speed) / ($nexttp->time-$trackpoint->time);
          $speedStep*=$timeBase;
          alog ("speed [{$trackpoint->speed}], next [{$nexttp->speed}] speedStep: [$speedStep]");

          $cadenceStep = ($nexttp->cadence-$trackpoint->cadence) / ($nexttp->time-$trackpoint->time);
          $cadenceStep*=$timeBase;
          alog ("cadence [{$trackpoint->cadence}], next [{$nexttp->cadence}] cadenceStep: [$cadenceStep]");

          $heartStep = ($nexttp->heartRateBpm-$trackpoint->heartRateBpm) / ($nexttp->time-$trackpoint->time);
          $heartStep*=$timeBase;
          alog ("heart [{$trackpoint->heartRateBpm}], next [{$nexttp->heartRateBpm}] heartStep: [$heartStep]");

          $altitudeStep = ($nexttp->altitudeMeters-$trackpoint->altitudeMeters) / ($nexttp->time-$trackpoint->time);
          $altitudeStep *=$timeBase;
          alog ("altitude [{$trackpoint->altitudeMeters}], next [{$nexttp->altitudeMeters}] altitudeStep: [$altitudeStep]");


          $ib=$i;
        }
        prev ($trackPoints);        
      } 
    }
    if ($synced){
      if (abs ($trackpoint->time-$absFrameTime) >= 1){
        $trackpoint=next ($trackPoints);        
        if (abs ($trackpoint->time-$absFrameTime) < 1){
          alog ("Good, advanced {$trackpoint->time}");  
          // linear interpolation step
          $nexttp = next ($trackPoints);
          if ($nexttp){
            $speedStep = ($nexttp->speed-$trackpoint->speed) / ($nexttp->time-$trackpoint->time);
            $speedStep*=$timeBase;
            alog ("speed [{$trackpoint->speed}], next [{$nexttp->speed}] speedStep: [$speedStep]");

            $cadenceStep = ($nexttp->cadence-$trackpoint->cadence) / ($nexttp->time-$trackpoint->time);
            $cadenceStep*=$timeBase;
            alog ("cadence [{$trackpoint->cadence}], next [{$nexttp->cadence}] cadenceStep: [$cadenceStep]");

            $heartStep = ($nexttp->heartRateBpm-$trackpoint->heartRateBpm) / ($nexttp->time-$trackpoint->time);
            $heartStep*=$timeBase;
            alog ("heart [{$trackpoint->heartRateBpm}], next [{$nexttp->heartRateBpm}] heartStep: [$heartStep]");

            $altitudeStep = ($nexttp->altitudeMeters-$trackpoint->altitudeMeters) / ($nexttp->time-$trackpoint->time);
            $altitudeStep *=$timeBase;
            alog ("altitude [{$trackpoint->altitudeMeters}], next [{$nexttp->altitudeMeters}] altitudeStep: [$altitudeStep]");


            $ib=$i;
          }
          prev ($trackPoints);        
        } else {
          alog ("Bad sync {$trackpoint->time}");          
          $synced=false;
        }
      } 
    }
    if ($synced){

      $trackpoint->speedInterpolated = $trackpoint->speed + ($i-$ib)*$speedStep;
      alog ("speed [{$trackpoint->speed}], interpolated [{$trackpoint->speedInterpolated}]");

      $trackpoint->cadenceInterpolated = $trackpoint->cadence + ($i-$ib)*$cadenceStep;
      alog ("cadence [{$trackpoint->cadence}], interpolated [{$trackpoint->cadenceInterpolated}]");

      $trackpoint->heartRateBpmInterpolated = $trackpoint->heartRateBpm + ($i-$ib)*$heartStep;
      alog ("heartBPM [{$trackpoint->heartRateBpm}], interpolated [{$trackpoint->heartRateBpmInterpolated}]");

      $trackpoint->altitudeMetersInterpolated = $trackpoint->altitudeMeters + ($i-$ib)*$altitudeStep;
      alog ("altitudeMeters [{$trackpoint->altitudeMeters}], interpolated [{$trackpoint->altitudeMetersInterpolated}]");

      $imagePath = $srcframe;

	    ob_start();
      renderFrame ();
	    $buffer = ob_get_clean();

      $svg="$TEMPDIR/x.svg";
	    file_put_contents($svg, $buffer);

      $command=false;
      $rasterizer = 'phantomjs';
      $oFile = sprintf ("${DSTFRAMEDIR}/${IMAGENAME}",$i);
      switch ($rasterizer){
        case 'magick':
		      $command= "convert -quality ${jpegQuality} $svg ${oFile}";
          break;
        case 'batik':
          $q = $jpegQuality/100;
          $command="rasterizer -m image/jpeg -q ${q} -d ${oFile} $svg";
          break;
        case 'phantomjs':
          $command="phantomjs rasterize.js $svg ${oFile}";
          break;
      }
      if ($command) {
        alog ("Executing [$command]");
        system ($command);
      }
    }
    $i++;
    if ($frames!==false){
      $frames--;
      if (!$frames) break;
    }
  }
  alog ("Done. Output frames are in [$DSTFRAMEDIR]");

  if ($videoOutFile){
    $cmd= "ffmpeg $ffmpegOptions $ffmpegInFileOptions -i \"${DSTFRAMEDIR}/${IMAGENAME}\" $ffmpegOutFileOptions  $videoOutFile < /dev/null";
    alog ("Creating output file [$videoOutFile]");
    alog ("Executing [$cmd]");
    system($cmd);
  }
