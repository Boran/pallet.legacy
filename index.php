<?php  

/*
 * Generate pallet image 
 * With layout of reels for 5 different scenarios
 * Inputs are provided via web parameters.
 *
 * This is a poorly documented custom hack .
 * Needs to be in /var/www/jobweb/pallet really.
  
 */

// troubleshooting
$debug_flag1=TRUE;
$debug_to_syslog=TRUE;

//------ defaults ----------------

// Pallet canvas size on the screen
//$pwidth=120;  // pallet width/dept in pixels
//$plength=100; // down
$pwidth=360;  // need better reel icon before using bigger sizes
$plength=300; // down
$image_ver='/reelv.jpg';   // reelv2.png
$image_hor='/reelh.jpg';
$heightwarning='';

include_once "./funcs.inc";

// Directories where our script in, where output is stored.
$dir=dirname(__FILE__);
$outdir=$dir . '/out';
$outdirweb = dirname($_SERVER['REQUEST_URI']) . '/out/';
$prog="$dir/index.php";  // @todo: automated

define_syslog_variables();
openlog($prog, LOG_PID | LOG_PERROR, LOG_DAEMON);

if (!is_writable($outdir)) {
    logit('creating ' . $outdir);
    if (!mkdir($outdir, 700, true)) {
        die('Cannot create output directory ' . $outdir);
    }
}

// parameters later
if (! isset($argc)) {   # web call
  /* Note: some arguements are allow as both GET and POST, for easier debugging
   */
  // Generic parameters
  //if (isset($_POST['file']))      {$f=$_POST['file']; }        else { $f='WEB'; } // output.jpg
  if (isset($_POST['file']))    {$f=$_POST['file']; }        else { $f='output.jpg'; } 
  if (isset($_GET['file']))     {$f=$_GET['file'];} 
  if (isset($_POST['company'])) {$dbname=$_POST['company'];} else { $dbname='NONE'; }
  if (isset($_POST['caller']))    {$caller=$_POST['caller'];}  else { $caller='anonymous'; }

  if (isset($_GET['3d']))       {$threed=$_GET['3d'];}       else {$threed=0;}
  if (isset($_POST['layout']))  {$layout=$_POST['layout'];}  else { $layout='versq'; }
  if (isset($_GET['layout']))   {$layout=$_GET['layout'];} 
  if (isset($_POST['rollwidth_mm'])) {$rollwidth_mm=$_POST['rollwidth_mm'];} else { $rollwidth_mm=300; }
  if (isset($_GET['rollwidth_mm']))  {$rollwidth_mm=$_GET['rollwidth_mm'];} 
  if (isset($_POST['diam_mm']))  {$diam_mm=$_POST['diam_mm'];} else { $diam_mm=300; }
  if (isset($_GET['diam_mm']))   {$diam_mm=$_GET['diam_mm'];} 
  if (isset($_POST['rows']))     {$rows=$_POST['rows'];} else { $rows=1; }
  if (isset($_GET['rows']))      {$rows=$_GET['rows'];} 
  if (isset($_POST['plength_mm'])) {$plength_mm=$_POST['plength_mm'];} else { $plength_mm=1000; }
  if (isset($_POST['pwidth_mm']))  {$pwidth_mm=$_POST['pwidth_mm'];} else { $pwidth_mm=1200; }
  $p2=$pwidth_mm;
  $p1=$plength_mm;
  if (isset($_POST['MaxLoadingHeight']))  {$MaxLoadingHeight=$_POST['MaxLoadingHeight'];} else { $MaxLoadingHeight=1500; }
  if (isset($_POST['MaxLoadingWeight']))  {$MaxLoadingWeight=$_POST['MaxLoadingWeight'];} else { $MaxLoadingWeight=800; }
  if (isset($_POST['rollkgs']))           {$rollkgs=$_POST['rollkgs'];} else { $rollkgs=0; }

  // @todo security: stripping of arguments to only neededed chracters

  //if (isset($_POST['debug_flag1']))  {$debug_flag1=$_POST['debug_flag1'];}    else { $debug_flag1=FALSE; }

} else {  // default values
  $rows=1;
  $diam_mm=300;
  $rollwidth_mm=300;
  $p2=$pwidth_mm=1200;
  $p1=$plength_mm=1000;
  $layout='versq';
  var_dump($argv);
}


// ---------------------
$pscaley=$pwidth_mm/$pwidth;  // ratio of pixes to mm
$pscalex=$plength_mm/$plength;  // ratio of pixes to mm
echo "<h3>Pallet card</h3><p>rollwidth_mm=$rollwidth_mm diam_mm=$diam_mm rows=$rows</p>";
logit("$caller : rollwidth=$rollwidth_mm diam=$diam_mm rows=$rows");
debug1("mm per pixel: pscalex=$pscalex pscaley=$pscaley");
debug1("pallet mm: $pwidth_mm x $plength_mm");
if ($layout=='versq' || $layout=='verint') {  // vertical
  $diam=floor($diam_mm/$pscalex);   
  $rollwidth=floor($rollwidth_mm/$pscaley);  
} else {
  // horizontal: scaling is opposit
  $diam=floor($diam_mm/$pscaley);   
  $rollwidth=floor($rollwidth_mm/$pscalex);  
}
$radius=$diam/2;
$radius_mm=$diam_mm/2;
$CompressedDiameter=floor(sqrt(3*$radius*$radius));
$CompressedDiameter_mm=floor(sqrt(3*$radius_mm*$radius_mm));
$str="px rollwidth=$rollwidth diam=$diam radius=$radius CompressedDiameter=$CompressedDiameter pallet:$pwidth X $plength";
debug1($str);


// -- pallet base --
//$pallet = new Imagick($dir . '/pallet.png');
//$palletprops = $pallet->getImageGeometry();
// canvas: is 2D pallet: add enough space for 3d overhang
// create new canvas for pallet + stacked rolls
//$result = new Imagick();
try
{
  $result = new Imagick();

  $result->newImage($plength+3*$radius, $pwidth+3*$radius, 'white');
  // add picture: $result->compositeImage($pallet, imagick::COMPOSITE_OVER, 0, $palletyoffset);
  $rect = new ImagickDraw();    // the wooden part of the pallet
  $rect->setStrokeColor('SaddleBrown');
  $rect->setStrokeWidth(1);
  $rect->setFillColor('burlywood');
  // simple square pallet
  //$rect->rectangle(0,0, $plength+2, $pwidth+4);    

  // nicer: create planks in both directions: 3 horiz.
  $rect->rectangle(0,0,                  $plength+2, $pwidth/7+4);
  $rect->rectangle(0,$pwidth-$pwidth/1.7,  $plength+2, $pwidth-$pwidth/1.7+$pwidth/7+4);
  $rect->rectangle(0,$pwidth-$pwidth/7,  $plength+2, $pwidth+4);

  $rect->setFillColor('burlywood1');    // dark planks, leave edges offset for realism
  $rect->rectangle(4           ,2, 1*$plength/7, $pwidth+2);
  $rect->rectangle(2*$plength/7,2, 3*$plength/7, $pwidth+2);
  $rect->rectangle(4*$plength/7,2, 5*$plength/7, $pwidth+2);
  $rect->rectangle(6*$plength/7,2, 7*$plength/7-2, $pwidth+2);
  //$rect->setStrokeWidth(3);
  //$rect->setStrokeColor('brown');
  //$rect->line(0,0, 0, $pwidth+2);
  //$rect->line(0,$pwidth+2, $plength+2, $pwidth+2);
  $result->drawImage($rect);

}
catch(Exception $e)
{
    die('Error Imagick: ' . $e->getMessage() );
}

// ---------- Each reel ----
//$reel   = new Imagick($dir . $image_ver);
//$reel->scaleImage($diam, $rollwidth);  // Scale reel accoring to diameter and width
//$reel->resizeImage($diam, $rollwidth);  // looks better than scaling
if ($layout=='versq' || $layout=='verint') {
  if ($threed==1) {
    //$offset3d=$radius*0.5;
    $offset3d=$radius*0.2;
    $circle = new ImagickDraw();
    $circle->setStrokeColor('black');
    $circle->setFillColor('snow1');
    $circle->ellipse($radius,$radius, $radius,$radius, 0,360); // Roll bottom originXY radiusXY,
    $circle->setFillColor('white');
    $circle->ellipse($radius+$offset3d,$radius+$offset3d, $radius,$radius, 0,360); // Roll top originXY radiusXY,
    $circle->setStrokeWidth(1);
    $coreradius= 96/2/$pscalex; // defsult core size 96mm in px
    $core = new ImagickDraw();
    $core->setFillColor('darkgrey');
    $core->setStrokeColor('black');
    $core->setStrokeWidth(1); 
    //$core->circle($radius+$offset3d, $radius+$offset3d, $radius, $radius+$coreradius); //
    $core->circle($radius+$offset3d, $radius+$offset3d, $radius+$offset3d, $radius+$offset3d+$coreradius); //
    //$core->ellipse($radius+$offset3d, $radius+$offset3d, $radius, $radius+$coreradius, 0,360); //
    $reel   = new Imagick();
    $reel->newImage($diam+$offset3d, $diam+$offset3d, new ImagickPixel( 'none' ) );
    $reel->setImageOpacity(0.07);  // allow row layers to be seen a bit

  } else {
    $circle = new ImagickDraw(); 
    $circle->setFillColor('white'); 
    //$circle->setFillColor('lightgrey'); 
    $circle->setStrokeColor('grey'); 
    //$circle->circle($radius, $radius, $radius, $diam-1); //
    $circle->ellipse($radius,$radius, $radius,$radius, 0,360); //
    $circle->setStrokeColor('black'); 
    $circle->setStrokeWidth(1); 
    $coreradius= 96/2/$pscalex; // defsult core size 96mm in px
    $core = new ImagickDraw();
    $core->setFillColor('darkgrey');
    $core->circle($radius, $radius, $radius, $radius+$coreradius); //
    $reel   = new Imagick();
    $reel->newImage($diam, $diam, new ImagickPixel( 'none' ) );
    $reel->setImageOpacity(0.1);  // allow space free on pallet to be hidden
  }

  $reel->drawImage($circle);
  $reel->drawImage($core);
  // TODO: stroke colour + size for core and outer edge

} else if ($layout=='horsq' || $layout=='horint' || $layout=='horpyr') {
  // three ellipses, rectangle
  $margin=2;
  $circle = new ImagickDraw(); 
  $circle->setStrokeColor('darkgrey'); 
  $circle->setFillColor('lightgrey'); 
  $circle->ellipse($rollwidth+$radius/3-$margin,$radius, $radius/3,$radius, 0,360); // Roll bottom originXY radiusXY, 
  $circle->setStrokeColor('none'); 
  $circle->setStrokeWidth(1); 
  $circle->rectangle($radius/3,0, $rollwidth+$radius/3,$diam); 
  $circle->setStrokeWidth(2); 
  $circle->setStrokeColor('darkgrey'); 
  $circle->line($radius/3,$diam, $rollwidth+$radius/3,$diam); // bottom line

  $circle->setFillColor('silver'); 
  $circle->ellipse($radius/3,$radius, $radius/3,$radius, 0,360); // reel top (on the left)
  $circle->line($radius/3,0, $rollwidth-$radius/3,0); // reel top (on the left)
  // core
  $circle->setStrokeColor('black'); 
  $circle->setFillColor('darkgrey');
  $coreradius= 96/2/$pscalex; // defsult core size 96mm in px
  $circle->ellipse($radius/3,$radius, $radius/8,$coreradius, 0,360); // core

  $reel   = new Imagick();   // square canvas to put above reel on
  $reel->newImage($rollwidth+$radius/3*2, $diam, new ImagickPixel( 'none' ) );
  $reel->setImageOpacity(0.01);  // allow space free on pallet to be visible
  $reel->drawImage($circle);

} else {
  $offset3d=7;
  $circle = new ImagickDraw();
  $circle->setStrokeColor('black');   
  $circle->setFillColor('gray');
  $circle->ellipse($radius,$radius, $radius,$radius, 0,360); // Roll bottom originXY radiusXY, 
  $circle->setFillColor('lightgray');
  //$circle->circle($radius+10, $radius+10, $radius+10, $diam-1); //
  $circle->ellipse($radius+$offset3d,$radius+$offset3d, $radius,$radius, 0,360); // Roll bottom originXY radiusXY, 
  //$circle->ellipse($radius,$radius, $radius,$radius, 0,360); //
  //$circle->circle($radius-10, $radius-10, $radius-20, $diam-20); //
  $circle->setStrokeWidth(1);
  $coreradius= 96/2/$pscalex; // defsult core size 96mm in px
  $core = new ImagickDraw();
  $core->setFillColor('darkgrey');
  $core->circle($radius+$offset3d, $radius+$offset3d, $radius, $radius+$coreradius); //

  $reel   = new Imagick();
  $reel->newImage($diam, $diam, new ImagickPixel( 'none' ) );
  $reel->setImageOpacity(0.1);  // allow space free on pallet to be visible
  $reel->drawImage($circle);
  $reel->drawImage($core);
  //$reel->rotateImage('none', 90);  
}


// Origin
#$x=0; $y=$palletyoffset+15; 
#$x=$pwidth*.90; $y=$palletyoffset-40; 
$x=2; $y=2;
#$result->compositeImage($reel, imagick::COMPOSITE_OVER, $x,$y);

//$rowoffset=$p1/200;
$rowoffset=7;


/* ------------ layout the reels -------------------*/
if ($layout == 'versq') {     // -- vertical square --
  $across=floor($p1/$diam_mm);
  $up=floor($p2/$diam_mm);   // round() if we want to allow an overhang
  $nrollsperrow=$across * $up;   // nr rolls per row
  $rollsperpallet=$nrollsperrow*$rows;
  $palletheight=$rollwidth_mm*$rows;
  echo "vertical square: nrollsperrow=$nrollsperrow across=$across up=$up rollsperpallet=$rollsperpallet palletheight=$palletheight<br>";

  if ($threed==1) {
    // Display from bottom-right to top left
    #$rowoffset=$rollwidth_mm/$pscalex/3;  // how much to offset each row
    //$rowoffset=$radius*0.8;
    $rowoffset=$radius*0.5;
    //$rowoffset=0;
    for ($row = 0; $row < $rows; $row++) {
      for ($j = $up; $j >0; $j--) {
	for ($i = $across; $i >0; $i--) {
	  //debug1("$i $j $row : ");
	  $result->compositeImage($reel, imagick::COMPOSITE_OVER,
	    //($i)*$diam-$radius -($rows-$row-2)*$rowoffset, $j*$diam-$diam +$row*$rowoffset);
	    ($i)*$diam-$radius +$row*$rowoffset -5, $j*$diam-$diam +$row*$rowoffset);
	    // TODO
	}
      }
    }

  } else {  // threed
    $rowoffset=$radius*0.3;
  for ($row = 0; $row < $rows; $row++) {
    for ($j = 0; $j < $up; $j++) {
      for ($i = 0; $i < $across; $i++) {
	$result->compositeImage($reel, imagick::COMPOSITE_OVER, 
	  $x+ $row*$rowoffset + $i*$diam, $y  +$j*$diam);
      }
    }
  }
  }


} else if ($layout == 'verint') {  // -- vertical interlinked --
  if ($threed==1) {
    $rowoffset=$radius*0.25;
  } else {  // threed
    $rowoffset=$radius*0.25;
    //$rowoffset=$rollwidth_mm/$pscalex/3;  // how much to offset each row
  }
  $across=floor($p1/$diam_mm);
  $up=1 +floor(($p2-$diam_mm)/$CompressedDiameter_mm);  
  if ( ($p1-$diam_mm*$across) >= $radius_mm) {
    $nrollsperrow=$across * $up;
  } else {
    $nrollsperrow=$across * $up  - floor($up/2);
  }
  $rollsperpallet=0;
  $palletheight=$rollwidth_mm*$rows;
  // TODO: draw from bottom right
  for ($row = 0; $row < $rows; $row++) {
  for ($j = 0; $j < $up; $j++) {
    for ($i = 0; $i < $across; $i++) {
      // calculate X and Y Left and Top
      if ($j==0) {   // first row is easy
        $top=0; 
      } else { 
        $fuzz=$radius/4; // spacing 1/2nd row: idont know why this is needed
        $top=$diam // first row height
          +floor(($j-1)*$CompressedDiameter) -$fuzz;
      }
      if (($j % 2)==0) { // even rows
        $left= $i*$diam;
      } else {
        $left= $i*$diam +floor($radius);
      }

      if ( (($j % 2)!=0)    // odd rows
       && ($i==$across-1) // last roll on the right of this row
       && ( ($p1-$diam_mm*$across)< $radius_mm) ) {
         // do not create a roll on the right, not rnough space
      } else {        // add reel image
        $rollsperpallet++;
        $result->compositeImage($reel, imagick::COMPOSITE_OVER,
          $x+ $row*$rowoffset +$left, $y +$row*$rowoffset +$top);
      }
    }
  }
  }
  echo "vertical interlinked: nrollsperrow=$nrollsperrow across=$across up=$up rollsperpallet=$rollsperpallet palletheight=$palletheight<br>";


} else if ($layout == 'horsq') {     // -- horiz. square --
  $rowoffset=$radius*0.75;
  $across=1;
  $up=floor($p2/$diam_mm);   
  $nrollsperrow=$across * $up;   // nr rolls per row
  $rollsperpallet=$nrollsperrow*$rows;
  $palletheight=$diam_mm*$rows;
  echo "horizontal square: nrollsperrow=$nrollsperrow across=$across up=$up rollsperpallet=$rollsperpallet palletheight=$palletheight<br>";
  for ($row = 0; $row < $rows; $row++) {
    for ($j = 0; $j < $up; $j++) {
      $result->compositeImage($reel, imagick::COMPOSITE_OVER,
        $x+ $row*$rowoffset , $y  +$j*$diam);
    }
  }


} else if ($layout == 'horint') {     // -- horiz. interlink --
  $rowoffset=$radius*0.65;
  $across=1;
  $up=floor($p2/$diam_mm);
  $rollsperpallet=0;
  $palletheight=$diam_mm+ $CompressedDiameter_mm*($rows-1);
  for ($row = 0; $row < $rows; $row++) {
    for ($j = 0; $j < $up; $j++) {

      // calculate X and Y Left and Top
      if (($row % 2)==0) {    // even rows
        $top=$diam*$j;
      } else {
        $top=$diam*$j +$radius;
      }
      //echo "row=$row j=$j top=$top <br>";
      if ( (($row % 2)!=0)    // odd rows
       && ($j==$up-1) // last roll on the right of this row
       && ( ($p2-$diam_mm*$up)< $radius_mm) ) {
         // do not create a roll on the bottom, not enough space
      } else {        // add reel image
        $rollsperpallet++;
        $result->compositeImage($reel, imagick::COMPOSITE_OVER,
          $x+ $row*$rowoffset , $y +$top);
      }
    }
  }
  echo "horizontal interlinked: across=$across up=$up rollsperpallet=$rollsperpallet palletheight=$palletheight <br>";


} else if ($layout == 'horpyr') {     // -- horiz. pyramid --
  $rowoffset=$radius*0.65;
  $across=1;
  $up=floor($p2/$diam_mm);
  $rollsperpallet=0;  
  $palletheight=$diam_mm+ $CompressedDiameter_mm*($rows-1);
  for ($row = 0; $row < $rows; $row++) {
    for ($j = 0; $j < $up; $j++) {

      if (($row % 2)==0) {    // even rows
        $top=$diam*$j;
        if ( ($row>2*$j+1) 
          || ($j>$up-$row/2-1) ) {
          //echo "even row=$row j=$j top=$top SKIP $up " . $row/2 . "<br>";;
          // hide reel on pyramid edge
        } else {
          //echo "even row=$row j=$j top=$top <br>";
          $rollsperpallet++;
          $result->compositeImage($reel, imagick::COMPOSITE_OVER,
            $x+ $row*$rowoffset , $y +$top);
        }
      } else {
        $top=$diam*$j +$radius;
        if ( ($row>2*$j+1) || ($j>$up-$row/2-1) ) {
          // hide reel on pyramid edge
        } else {
          //echo "odd row=$row j=$j top=$top <br>";
          $rollsperpallet++;
          $result->compositeImage($reel, imagick::COMPOSITE_OVER,
            $x+ $row*$rowoffset , $y +$top);
        }

      }
    }
  }
  echo "horizontal pyramid: across=$across up=$up rollsperpallet=$rollsperpallet palletheight=$palletheight<br>";


} else {  // testing, draw one reel
   //$reel->rotateImage('none', 90);  
   $result->compositeImage($reel, imagick::COMPOSITE_OVER, 1, 1);
   //$result->rotateImage('none', 33);  
}

if ($palletheight>$MaxLoadingHeight) { 
  echo "<h3>MaxLoadingHeight $MaxLoadingHeight exceeded.</h3>";
}
echo "<br>";

// ------------ prepare display ----------------------

$result->setImageFormat('jpg');
//$result->rotateImage('white', -45);  
// TODO: or tilt?
if ($f == 'download') {
  debug1("send pallet.jpg to $caller for download");
  //$result->scaleImage(120, 100); // reduce to thumbnail
  ob_clean();                         // Clear buffer
  Header("Content-Description: File Transfer");
  Header("Content-Type: application/force-download");
  header('Content-Type: image/jpeg'); // Send JPEG header
  Header("Content-Disposition: attachment; filename=" . "pallet.jpg");
  $result->writeImage($dir . '/out/' . $f);  // Write to disk anyway?
  //header('Content-Length: ' . $dir . '/out/' . $f);  // TODO: Calculate image size?
  echo $result;                          // Output to browser

} else {
  //$result->scaleImage(75, 90); // reduce to thumbnail
  //$result->scaleImage(200, 240); // increase
  try
  {
    $result->writeImage($outdir . '/' . $f);       // Write to disk
  }
  catch(Exception $e)
  {
      die('Error Imagick: ' . $e->getMessage() );
  }
  //echo "<img src=/$d/out/$f alt='Generated image'>";
  echo "<img src=$outdirweb$f alt='Generated image'>";
}
//echo "<img src=/$d/out/output2.jpg alt='Generated image'>";
$result->destroy();

?>

