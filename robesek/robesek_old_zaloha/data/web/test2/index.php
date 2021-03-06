<!DOCTYPE html>
<?php
   define('BASE_DIR', dirname(__FILE__));
   require_once(BASE_DIR.'/config.php');
   $config = array();
   $debugString = "";
   


  
   function getImgWidth() {
      global $config;
      if($config['vector_preview'])
         return 'style="width:' . $config['width'] . 'px;"';
      else
         return '';
   }

   function getLoadClass() {
      global $config;
      if(array_key_exists('fullscreen', $config) && $config['fullscreen'] == 1)
         return 'class="fullscreen" ';
      else
         return '';
   }

   if (isset($_POST['extrastyle'])) {
      if (file_exists('css/' . $_POST['extrastyle'])) {
         $fp = fopen(BASE_DIR . '/css/extrastyle.txt', "w");
         fwrite($fp, $_POST['extrastyle']);
         fclose($fp);
      }
   }
   $streamButton = "MJPEG-Stream";
   $mjpegmode = 0;
   if(isset($_COOKIE["stream_mode"])) {
      if($_COOKIE["stream_mode"] == "MJPEG-Stream") {
         $streamButton = "Default-Stream";
         $mjpegmode = 1;
      }
   }
   $config = readConfig($config, CONFIG_FILE1);
   $config = readConfig($config, CONFIG_FILE2);
   $video_fps = $config['video_fps'];
   $divider = $config['divider'];
  ?>
<html>
<head>
    <meta charset="UTF-8">
    <title>R.O.B.E.S.E.K.</title>
    <meta name="author" content="DDM Praha 9">
    <meta name="description" content="Web frontend for robot R.O.B.E.S.E.K.">
    <link rel="stylesheet" href="main.css">

</head>
<body onload="setTimeout('init(<?php echo "$mjpegmode, $video_fps, $divider" ?>);', 100);" onresize="Resize()" onkeyup="Sendkey(event.keyCode)">
    <!-- Div for camera stream -->
    <div id="camera" class="active">
        <div style="padding-top: 20rem; font-size: 3rem; text-align:center;">
	    <img id="mjpeg_dest" <?php echo getLoadClass() . getImgWidth();?> <?php if(file_exists("pipan_on")) echo "ontouchstart=\"pipan_start()\""; ?>" src="./loading.jpg">
	</div>
    </div>
    <!-- Image overlay future -->
    <div id="ioverlay">
        <canvas id="ioverlay_display"></canvas>
    </div>
    <!-- Status overlay -->
    <div id="status">
    </div>
    <!-- Touch mode input -->
    <div id="tioverlay">
        <div id="ti_arrows" class="arrows" ondragend="TIOverlay('move','ti_arrows')">
            <div><div onclick="Sendkey(82)" class="checkbox"><input id="ti_brake" type="checkbox" value="0"><label for="ti_break"></label></div></div>
            <div><div onmousedown="Sendkey(87)" onmouseup="Sendkey(32)">&uarr;</div></div>
            <div><div onmousedown="Sendkey(65)" onmouseup="Sendkey(32)">&larr;</div>
            <div onmousedown="Sendkey(83)" onmouseup="Sendkey(32)">&darr;</div>
            <div onmousedown="Sendkey(68)" onmouseup="Sendkey(32)">&rarr;</div></div>
        </div>
        <<div id="ti_joystick" ondragend="TIOverlay('move','ti_joystick')">
        </div>
        <div id="ti_speed" ondragend="TIOverlay('move','ti_speed')">
            <input type="range" min="0" max="0" value="100" oninput="">
        </div>
        <div id="ti_switches" class="arrows" ondragend="TIOverlay('move','ti_switches')">
            <div onclick="Sendkey('idle')" class="checkbox"><input id="ti_idle" type="checkbox" value="0"><label for="ti_idle"></label></div>
            <div><div onclick="Sendkey('raisecube')">+</div>
            <div onclick="Sendkey('lowercube')">-</div></div>
            <div onclick="Sendkey('stealth')" class="checkbox"><input id="ti_stealth" type="checkbox" value="0"><label for="ti_stealth"></label></div>
            <div onclick="Sendkey('lamp')" class="checkbox"><input id="ti_lamp" type="checkbox" value="0"><label for="ti_lamp"></label></div>
        </div>
    </div>
    <!-- Menu -->
    <div id="menu_but" class="active" onmouseover="Display('show','menu')"><div></div><div></div><div></div></div> <!-- div for graphics, do not remove -->
    <div id="menu_leave" onclick="TIOverlay('unload')">&#10006;</div>
    <div id="menu" onmouseover="Display('show','menu')" onmouseleave="Display('hide','menu')">
        <div>
            <div id="tioverlay_status" onclick="TIOverlay('load')">Input mode: keyboard</div>
            <div><input id="menu_tioverlay_opacity" type="range" min="0" max="100" value="0" oninput="Opacity('tioverlay')"></div>
        </div>
        <div>
            <div onclick="Display('show','status_settings')">Manage status bar</div>
            <div><input id="menu_status_opacity" type="range" min="0" max="100" value="0" oninput="Opacity('status')"></div>
        </div>
        <div>
            <div onclick="MakeScreenshot()">Make a screenshot</div>
        </div>
        <div>
            <div onclick="Display('show','ioverlay_settings')">Manage image overlay</div>
            <div><input id="menu_ioverlay_opacity" type="range" min="0" max="100" value="0" oninput="Opacity('ioverlay')"></div>
        </div>
    </div>
    <!-- Settings overlays -->
    <div id="status_settings">
        <div class="ftop">Status overlay settings
            <div style="float: right;" onclick="Display('hide','status_settings')">&#10006;</div>
        </div>
        <div class="fleft"></div>
        <div class="fright"></div>
    </div>
    <div id="ioverlay_settings">
        <div id="ioverlay_header">
            <div class="ftop">Image overlay settings
                <div style="float: right;" onclick="Display('hide','ioverlay_settings')">&#10006;</div>
            </div>
            <div class="fleft">
                Upload file:
                <input type="file" id="ioverlay_input" onchange="SaveIOverlay(this)" accept="image/*">
                <div id="ioverlay_status"></div>
                Type:
                <select id="ioverlay_set_type">
                    <option value="full">Fullscreen</option>
                    <option value="splitH">Horizontal split</option>
                    <option value="splitV">Vertical split</option>
                    <option value="window">Shiftable window WIP</option>
                </select>
                <div style="display: none;">
                    <canvas id="ioverlay_save"></canvas>
                    <img id="ioverlay_save2">
                </div>
            </div>
            <div class="fright">
                Opacity (same as in the menu):<br>
                <input id="menu_ioverlay2_opacity" type="range" min="0" max="100" value="0" oninput="Opacity('ioverlay2')"><br>
                Filling:
                <select id="ioverlay_set_fill">
                    <option value="center">Center</option>
                    <option value="fill">Exact fill</option>
                    <option value="fillS">Scaled fill</option>
                    <option value="repeat">Repeat WIP</option>
                </select>
            </div>
        </div>
        <canvas id=ioverlay_viewer></canvas>
    </div>
    <!-- End of structure -->
    <script src="js/script.js"></script>
    <script src="main.js"></script>

</body>
</html>