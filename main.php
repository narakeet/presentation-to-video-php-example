<?php

require_once "narakeet_api_client.php";


///////////////////////////////////////////////////////////////////////
//
// SECTION 1: Configuration
//
//
// change this to use an API key that is configured somehow differently

$api_key = getenv("NARAKEET_API_KEY");
if (!isset($api_key) || empty($api_key)) {
    die("Error: NARAKEET_API_KEY is not set or is empty.");
}

// change this to localize the warning messages
$warning_messages = [
  "no-embedded-fonts" => "Fonts are not embedded in this document, the output might look different than on your screen. Save the presentation with fonts embedded if possible.",
	"missing-fonts" => "Some fonts are missing from the document,  the output might look different than on your screen.",
	"too-many-slides" => "This presentation has more slides than your allowed account limit. Some slides will not be processed.",
	"invalid-audio" => "The slide contains an invalid audio file or an unsupported audio format, it will not be included in the result.",
	"audio-too-long" => "The slide contains an audio that exceeds your account limit for embedded audios.",
	"linked-audio" => "This slide is linking to an external audio that is not included in the presentation file. Embed audio files instead of linking them.",
	"invalid-video" => "The slide contains an invalid video file or an unsupported video format, it will not be included in the result.",
	"video-too-long" => "The slide contains a video that exceeds your account limit for embedded audios.",
	"linked-video" => "This slide is linking to an external video that is not included in the presentation file. Embed video videos instead of linking them",
	"static-slide-only" => "This slide has no audio or narration. It will only show a static image.",
	"animated" => "This slide is using Powerpoint animations, which are not currently supported",
	"transition" => "This slide is using Powerpoint transitions which are not currently supported."
];

// do something more useful here, to print progress using percent, message and thumbnail
function print_progress($task_progress) {
  var_dump($task_progress);
}

// change this to use a different file somewhere else on your file system!
function get_pptx_file_path() {
  $pptx_file = getenv("PPTX_FILE");
  if (!isset($pptx_file) || empty($pptx_file)) {
      die("Error: PPTX_FILE key not set or is empty.");
  }
  return realpath(dirname(__FILE__) . "/" . $pptx_file);
}

// change this to configure the video build
function get_video_settings() {
  return [
    "voice" => "victoria",
    "size" => "720p",
    "background" => "corporate-1 fade-in 0.4"
  ];
}

///////////////////////////////////////////////////////////////////////
// 
// SECTION 2: Build process
//

$narakeet_api_client = new NarakeetPresentationApiClient($api_key /*, poll interval defaults to 5 seconds, extension to pptx*/);

// step 1: upload the pptx to Narakeet

$upload_token = $narakeet_api_client->request_upload_token();

echo "Uploading file" . get_pptx_file_path()  . PHP_EOL;

$narakeet_api_client->upload_file($upload_token, get_pptx_file_path());


// step 2: kick-off a conversion task to import the PPTX into Narakeet video format
//
echo "Converting presentation" . PHP_EOL;
$conversion_task = $narakeet_api_client->request_conversion($upload_token);
// wait for it to finish
$conversion_task_result = $narakeet_api_client->poll_until_finished($conversion_task, "print_progress");

if ($conversion_task_result["succeeded"]) {
  if (isset($conversion_task_result["warnings"]) && is_array($conversion_task_result["warnings"])) {
     foreach ($conversion_task_result["warnings"] as $index => $warning) {
        $type = $warning["type"];
        $slide = $warning["scene"] + 1;
        $detail = isset($warning["detail"]) ? $warning["detail"] : "";
        // Using the type as a key to get the internationalized message from $warning_messages
        $message = isset($warning_messages[$type]) ? $warning_messages[$type] : $type;
        echo "Warning in slide #{$slide}: {$message} {$detail}" . PHP_EOL;
     } 
  }
} else {
  die("there was a problem converting the presentation: {$conversion_task_result["message"]}");
}

// step 3: kick off the video build task
//
echo "Building video"  . PHP_EOL;

$build_settings = [
  "description" => basename(get_pptx_file_path()),
  "settings" => get_video_settings()
];
$build_task = $narakeet_api_client->request_build($upload_token, $conversion_task, $build_settings);
// wait for it to finish
$build_task_result = $narakeet_api_client->poll_until_finished($build_task, "print_progress");

if ($build_task_result["succeeded"]) {
  $result_file = $narakeet_api_client->download_to_temp_file($build_task_result["result"]);
  echo "downloaded result to " . $result_file  . PHP_EOL;
  echo "file size " . filesize($result_file) . PHP_EOL;
} else {
  die("there was a problem creating the video: {$conversion_task_result["message"]}");
}

?>
