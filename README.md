tcx-annotator
=============

A command line utility to overlay video with gps data

![ScreenShot](http://fabrizio.zellini.org/wp-content/uploads/2014/07/Screenshot-P1030915-annotated.mp4-1-300x168.png)

For example, given a video of a bike ride and telemetry data as tcx file, the script combines the two in a video that contains the original video with telemetry data overlay.

The original file is splitted in frames using ffmpeg, each frame and the synchronized trackpoint are processed by a php that produce an svg file.

The output svg file contains the original frame and the telemetry data ( gauges, elevation, etc ) based on trackpoint data.
The svg file is rendered to a jpeg using a renderer ( convert, phantomjs, batik )
Finally, the output movie is done by merging the rendered jpeg

More info on http://fabrizio.zellini.org/uno-script-per-sovraimporre-dati-gps-su-un-video




